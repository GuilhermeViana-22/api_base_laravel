<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProvaoLinkRequest;
use App\Http\Requests\Admin\ReorderProvaoLinksRequest;
use App\Http\Resources\ProvaoLinkResource;
use App\Models\ProvaoLink;
use App\Services\ProvaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

/**
 * Links do card "Informações sobre a Matrícula" (`/api/admin/provao-paulista/links`).
 *
 * O destino é uma URL ou um arquivo. Ao criar, o arquivo pode vir junto
 * (multipart); depois, ele é trocado em `/links/{link}/file`.
 */
class ProvaoLinkController extends Controller
{
    public function __construct(private readonly ProvaoService $provao)
    {
    }

    #[OA\Post(
        path: '/admin/provao-paulista/links',
        summary: 'Cria um link no card de matrícula',
        description: 'Entra no fim do card. Mande `url` (JSON ou multipart) ou `file` (multipart). '
            .'Sem `kind`, o tipo sai do destino: `.pdf` vira PDF, YouTube/Vimeo vira vídeo, DOC/DOCX/ODT vira documento.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'multipart/form-data',
                    schema: new OA\Schema(
                        required: ['text'],
                        properties: [
                            new OA\Property(property: 'label', type: 'string', maxLength: 255, nullable: true),
                            new OA\Property(property: 'text', type: 'string', maxLength: 255, example: 'Veja o PDF'),
                            new OA\Property(property: 'kind', type: 'string', enum: ['link', 'video', 'pdf', 'document'], nullable: true),
                            new OA\Property(property: 'url', description: 'Obrigatório sem `file`.', type: 'string', nullable: true),
                            new OA\Property(property: 'file', description: 'PDF, DOC, DOCX, ODT ou RTF, até 20 MB. Obrigatório sem `url`.', type: 'string', format: 'binary'),
                        ],
                        type: 'object',
                    ),
                ),
            ],
        ),
        tags: ['Painel · Provão Paulista'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Link criado.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/ProvaoLink'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function store(ProvaoLinkRequest $request): JsonResponse
    {
        $link = $this->provao->createLink($request->validated(), $request->file('file'));

        return (new ProvaoLinkResource($link))->response()->setStatusCode(201);
    }

    #[OA\Patch(
        path: '/admin/provao-paulista/links/{link}',
        summary: 'Atualiza um link do card de matrícula',
        description: 'Só os campos enviados mudam. Gravar uma `url` apaga o arquivo que o link tinha; '
            .'a `url` só fica vazia se o link aponta para um arquivo.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'label', type: 'string', maxLength: 255, nullable: true),
                    new OA\Property(property: 'text', type: 'string', maxLength: 255),
                    new OA\Property(property: 'kind', type: 'string', enum: ['link', 'video', 'pdf', 'document'], nullable: true),
                    new OA\Property(property: 'url', type: 'string', nullable: true),
                ],
                type: 'object',
            ),
        ),
        tags: ['Painel · Provão Paulista'],
        parameters: [
            new OA\Parameter(name: 'link', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Link atualizado.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/ProvaoLink'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function update(ProvaoLinkRequest $request, ProvaoLink $link): ProvaoLinkResource
    {
        return new ProvaoLinkResource($this->provao->updateLink($link, $request->validated()));
    }

    #[OA\Delete(
        path: '/admin/provao-paulista/links/{link}',
        summary: 'Exclui um link do card de matrícula',
        description: 'O arquivo enviado, se houver, sai do disco junto.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Provão Paulista'],
        parameters: [
            new OA\Parameter(name: 'link', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 204, ref: '#/components/responses/NoContent'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ],
    )]
    public function destroy(ProvaoLink $link): Response
    {
        $this->provao->deleteLink($link);

        return response()->noContent();
    }

    #[OA\Post(
        path: '/admin/provao-paulista/links/reorder',
        summary: 'Reordena os links do card de matrícula',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['ids'],
                properties: [
                    new OA\Property(property: 'ids', description: 'Ids dos links, na ordem do card.', type: 'array', items: new OA\Items(type: 'integer'), minItems: 1, example: [3, 1, 2]),
                ],
                type: 'object',
            ),
        ),
        tags: ['Painel · Provão Paulista'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Todos os links, já na ordem nova.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ProvaoLink')),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function reorder(ReorderProvaoLinksRequest $request): AnonymousResourceCollection
    {
        $this->provao->reorder($request->validated('ids'));

        return ProvaoLinkResource::collection(ProvaoLink::ordered()->get());
    }
}
