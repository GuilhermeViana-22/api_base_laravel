<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderVestibularRequest;
use App\Http\Requests\Admin\VestibularBlockRequest;
use App\Http\Resources\VestibularBlockResource;
use App\Models\VestibularBlock;
use App\Models\VestibularSection;
use App\Services\VestibularService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

/**
 * Blocos de uma seção do vestibular: título, texto, link/arquivo ou vídeo.
 *
 * O tipo é escolhido ao criar e não muda. O arquivo de um link pode vir junto
 * na criação (multipart); depois, troca em `/blocks/{block}/file`.
 */
class VestibularBlockController extends Controller
{
    public function __construct(private readonly VestibularService $vestibular)
    {
    }

    #[OA\Post(
        path: '/admin/vestibular/sections/{section}/blocks',
        summary: 'Cria um bloco no fim da seção',
        description: 'Campos por tipo: `title` (text, style), `text` (html), `link` (text, kind, underline e url ou file) '
            .'e `video` (url do YouTube). Sem `kind`, o ícone do link sai do destino.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'multipart/form-data',
                    schema: new OA\Schema(
                        required: ['type'],
                        properties: [
                            new OA\Property(property: 'type', type: 'string', enum: ['title', 'text', 'link', 'video']),
                            new OA\Property(property: 'text', type: 'string', maxLength: 500),
                            new OA\Property(property: 'style', type: 'string', enum: ['default', 'large', 'highlight']),
                            new OA\Property(property: 'html', type: 'string'),
                            new OA\Property(property: 'kind', type: 'string', enum: ['link', 'video', 'pdf', 'document']),
                            new OA\Property(property: 'underline', type: 'boolean'),
                            new OA\Property(property: 'url', type: 'string'),
                            new OA\Property(property: 'spacing_top', description: 'Espaço acima, em px (nulo: automático).', type: 'integer'),
                            new OA\Property(property: 'file', description: 'Só para link: PDF, DOC, DOCX, ODT ou RTF, até 20 MB.', type: 'string', format: 'binary'),
                        ],
                        type: 'object',
                    ),
                ),
            ],
        ),
        tags: ['Painel · Vestibular'],
        parameters: [
            new OA\Parameter(name: 'section', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Bloco criado.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/VestibularBlock'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function store(VestibularBlockRequest $request, VestibularSection $section): JsonResponse
    {
        $bloco = $this->vestibular->createBlock($section, $request->validated(), $request->file('file'));

        return (new VestibularBlockResource($bloco))->response()->setStatusCode(201);
    }

    #[OA\Patch(
        path: '/admin/vestibular/blocks/{block}',
        summary: 'Atualiza um bloco',
        description: 'Só os campos do tipo do bloco. Num link, gravar uma `url` apaga o arquivo que ele tinha.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'text', type: 'string', maxLength: 500),
                    new OA\Property(property: 'style', type: 'string', enum: ['default', 'large', 'highlight']),
                    new OA\Property(property: 'html', type: 'string'),
                    new OA\Property(property: 'kind', type: 'string', enum: ['link', 'video', 'pdf', 'document'], nullable: true),
                    new OA\Property(property: 'underline', type: 'boolean'),
                    new OA\Property(property: 'url', type: 'string', nullable: true),
                    new OA\Property(property: 'spacing_top', description: 'Espaço acima, em px (nulo: automático).', type: 'integer', nullable: true),
                ],
                type: 'object',
            ),
        ),
        tags: ['Painel · Vestibular'],
        parameters: [
            new OA\Parameter(name: 'block', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Bloco atualizado.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/VestibularBlock'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function update(VestibularBlockRequest $request, VestibularBlock $block): VestibularBlockResource
    {
        return new VestibularBlockResource($this->vestibular->updateBlock($block, $request->validated()));
    }

    #[OA\Delete(
        path: '/admin/vestibular/blocks/{block}',
        summary: 'Exclui um bloco',
        description: 'O arquivo enviado, se houver, sai do disco junto.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Vestibular'],
        parameters: [
            new OA\Parameter(name: 'block', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 204, ref: '#/components/responses/NoContent'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ],
    )]
    public function destroy(VestibularBlock $block): Response
    {
        $this->vestibular->deleteBlock($block);

        return response()->noContent();
    }

    #[OA\Post(
        path: '/admin/vestibular/sections/{section}/blocks/reorder',
        summary: 'Reordena os blocos de uma seção',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['ids'],
                properties: [
                    new OA\Property(property: 'ids', description: 'Ids dos blocos da seção, na ordem desejada.', type: 'array', items: new OA\Items(type: 'integer'), minItems: 1, example: [3, 1, 2]),
                ],
                type: 'object',
            ),
        ),
        tags: ['Painel · Vestibular'],
        parameters: [
            new OA\Parameter(name: 'section', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Os blocos da seção, já na ordem nova.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/VestibularBlock')),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function reorder(ReorderVestibularRequest $request, VestibularSection $section): AnonymousResourceCollection
    {
        $this->vestibular->reorderBlocks($section, $request->validated('ids'));

        return VestibularBlockResource::collection($section->blocks()->get());
    }
}
