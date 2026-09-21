<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HomeCounterRequest;
use App\Http\Resources\HomeCounterResource;
use App\Models\HomeCounter;
use App\Services\HomeContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

/**
 * Contadores da página inicial (`/api/admin/home/counters`).
 *
 * São poucos e a tela edita todos juntos, por isso a listagem não é paginada.
 */
class HomeCounterController extends Controller
{
    public function __construct(private readonly HomeContentService $home)
    {
    }

    #[OA\Get(
        path: '/admin/home/counters',
        summary: 'Todos os contadores, na ordem da página',
        description: 'Sem paginação: são poucos e a tela edita todos juntos. Aqui vêm também os inativos.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Página inicial'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Os contadores.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/HomeCounter')),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ],
    )]
    public function index(): AnonymousResourceCollection
    {
        return HomeCounterResource::collection(HomeCounter::ordered()->get());
    }

    #[OA\Post(
        path: '/admin/home/counters',
        summary: 'Cria um contador',
        description: 'Sem `position`, o contador entra no fim da fila.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['label', 'value'],
                properties: [
                    new OA\Property(property: 'label', type: 'string', maxLength: 255, minLength: 3, example: 'Municípios no Estado de São Paulo'),
                    new OA\Property(property: 'value', type: 'integer', maximum: 1000000000, minimum: 0, example: 392),
                    new OA\Property(property: 'suffix', description: 'O que vem depois do número (ex.: `mil`, `+`).', type: 'string', maxLength: 20, nullable: true, example: '+'),
                    new OA\Property(property: 'position', description: 'Ordem na página. Em branco, vai para o fim.', type: 'integer', minimum: 0, nullable: true),
                    new OA\Property(property: 'active', type: 'boolean', example: true),
                ],
                type: 'object',
            ),
        ),
        tags: ['Painel · Página inicial'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Contador criado.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/HomeCounter'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function store(HomeCounterRequest $request): JsonResponse
    {
        $contador = new HomeCounter($request->validated());
        $contador->position = $request->validated('position') ?? $this->home->nextPosition(HomeCounter::class);
        $contador->save();

        return (new HomeCounterResource($contador))->response()->setStatusCode(201);
    }

    #[OA\Put(
        path: '/admin/home/counters/{counter}',
        summary: 'Atualiza um contador (envio parcial)',
        description: 'PUT e PATCH fazem a mesma coisa: só os campos presentes no corpo são alterados.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'label', type: 'string', maxLength: 255, minLength: 3, example: 'Municípios no Estado de São Paulo'),
                    new OA\Property(property: 'value', type: 'integer', maximum: 1000000000, minimum: 0, example: 392),
                    new OA\Property(property: 'suffix', description: 'O que vem depois do número (ex.: `mil`, `+`).', type: 'string', maxLength: 20, nullable: true, example: '+'),
                    new OA\Property(property: 'position', description: 'Ordem na página. Em branco, vai para o fim.', type: 'integer', minimum: 0, nullable: true),
                    new OA\Property(property: 'active', type: 'boolean', example: true),
                ],
                type: 'object',
            ),
        ),
        tags: ['Painel · Página inicial'],
        parameters: [
            new OA\Parameter(name: 'counter', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 2)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Atualizado.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/HomeCounter'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    #[OA\Patch(
        path: '/admin/home/counters/{counter}',
        summary: 'Atualiza um contador (envio parcial)',
        description: 'Mesmo comportamento do PUT.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'label', type: 'string', maxLength: 255, minLength: 3, example: 'Municípios no Estado de São Paulo'),
                    new OA\Property(property: 'value', type: 'integer', maximum: 1000000000, minimum: 0, example: 392),
                    new OA\Property(property: 'suffix', description: 'O que vem depois do número (ex.: `mil`, `+`).', type: 'string', maxLength: 20, nullable: true, example: '+'),
                    new OA\Property(property: 'position', description: 'Ordem na página. Em branco, vai para o fim.', type: 'integer', minimum: 0, nullable: true),
                    new OA\Property(property: 'active', type: 'boolean', example: true),
                ],
                type: 'object',
            ),
        ),
        tags: ['Painel · Página inicial'],
        parameters: [
            new OA\Parameter(name: 'counter', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 2)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Atualizado.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/HomeCounter'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function update(HomeCounterRequest $request, HomeCounter $counter): HomeCounterResource
    {
        $counter->fill($request->validated())->save();

        return new HomeCounterResource($counter);
    }

    #[OA\Delete(
        path: '/admin/home/counters/{counter}',
        summary: 'Exclui um contador',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Página inicial'],
        parameters: [
            new OA\Parameter(name: 'counter', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 2)),
        ],
        responses: [
            new OA\Response(response: 204, ref: '#/components/responses/NoContent'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ],
    )]
    public function destroy(HomeCounter $counter): Response
    {
        $counter->delete();

        return response()->noContent();
    }
}
