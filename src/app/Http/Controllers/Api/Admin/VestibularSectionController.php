<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderVestibularRequest;
use App\Http\Requests\Admin\VestibularSectionRequest;
use App\Http\Resources\VestibularSectionResource;
use App\Models\VestibularSection;
use App\Services\VestibularService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

/**
 * Seções da página /vestibular no painel (`/api/admin/vestibular`).
 *
 * `index` devolve a página inteira (todas as seções com todos os blocos,
 * inclusive os que ainda não aparecem no site).
 */
class VestibularSectionController extends Controller
{
    public function __construct(private readonly VestibularService $vestibular)
    {
    }

    #[OA\Get(
        path: '/admin/vestibular',
        summary: 'Página do vestibular no painel',
        description: 'Todas as seções, com todos os blocos (inclusive link sem destino e vídeo sem URL).',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Vestibular'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'As seções em ordem.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/VestibularSection')),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
        ],
    )]
    public function index(): AnonymousResourceCollection
    {
        return VestibularSectionResource::collection(VestibularSection::ordered()->with('blocks')->get());
    }

    #[OA\Post(
        path: '/admin/vestibular/sections',
        summary: 'Cria uma seção no fim da página',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Vestibular 2027'),
                    new OA\Property(property: 'divider', type: 'string', enum: ['none', 'solid', 'dotted']),
                    new OA\Property(property: 'spacing_top', description: 'Espaço acima, em px (nulo: automático).', type: 'integer', nullable: true),
                ],
                type: 'object',
            ),
        ),
        tags: ['Painel · Vestibular'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Seção criada, sem blocos.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/VestibularSection'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function store(VestibularSectionRequest $request): JsonResponse
    {
        $secao = $this->vestibular->createSection($request->validated())->load('blocks');

        return (new VestibularSectionResource($secao))->response()->setStatusCode(201);
    }

    #[OA\Patch(
        path: '/admin/vestibular/sections/{section}',
        summary: 'Atualiza nome ou divisória de uma seção',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'divider', type: 'string', enum: ['none', 'solid', 'dotted']),
                    new OA\Property(property: 'spacing_top', description: 'Espaço acima, em px (nulo: automático).', type: 'integer', nullable: true),
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
                description: 'Seção atualizada.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/VestibularSection'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function update(VestibularSectionRequest $request, VestibularSection $section): VestibularSectionResource
    {
        return new VestibularSectionResource($this->vestibular->updateSection($section, $request->validated())->load('blocks'));
    }

    #[OA\Delete(
        path: '/admin/vestibular/sections/{section}',
        summary: 'Exclui uma seção e todos os blocos dela',
        description: 'Os arquivos enviados nos links da seção saem do disco junto.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Vestibular'],
        parameters: [
            new OA\Parameter(name: 'section', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 204, ref: '#/components/responses/NoContent'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ],
    )]
    public function destroy(VestibularSection $section): Response
    {
        $this->vestibular->deleteSection($section);

        return response()->noContent();
    }

    #[OA\Post(
        path: '/admin/vestibular/sections/reorder',
        summary: 'Reordena as seções',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['ids'],
                properties: [
                    new OA\Property(property: 'ids', description: 'Ids das seções, na ordem da página.', type: 'array', items: new OA\Items(type: 'integer'), minItems: 1, example: [3, 1, 2]),
                ],
                type: 'object',
            ),
        ),
        tags: ['Painel · Vestibular'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Todas as seções, já na ordem nova.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/VestibularSection')),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function reorder(ReorderVestibularRequest $request): AnonymousResourceCollection
    {
        $this->vestibular->reorderSections($request->validated('ids'));

        return $this->index();
    }
}
