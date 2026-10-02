<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProvaoPageRequest;
use App\Http\Resources\ProvaoPageResource;
use App\Models\ProvaoLink;
use App\Models\ProvaoPage;
use App\Services\ProvaoService;
use OpenApi\Attributes as OA;

/**
 * Textos da página /provao-paulista (`/api/admin/provao-paulista`).
 *
 * Recurso único: só lê e atualiza. Os links do card têm rotas próprias
 * (ProvaoLinkController); aqui eles vêm todos, inclusive os sem destino.
 */
class ProvaoController extends Controller
{
    public function __construct(private readonly ProvaoService $provao)
    {
    }

    #[OA\Get(
        path: '/admin/provao-paulista',
        summary: 'Textos e links da página do Provão Paulista',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Provão Paulista'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'A página, com todos os links.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/ProvaoPage'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
        ],
    )]
    public function show(): ProvaoPageResource
    {
        return $this->pagina(ProvaoPage::current());
    }

    #[OA\Patch(
        path: '/admin/provao-paulista',
        summary: 'Atualiza os textos da página do Provão Paulista',
        description: 'Só os campos presentes no corpo mudam. Os dois HTML passam pelo filtro do editor.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', maxLength: 255, nullable: true),
                    new OA\Property(property: 'content', description: 'HTML do editor rico.', type: 'string', nullable: true),
                    new OA\Property(property: 'card_title', type: 'string', maxLength: 255, nullable: true),
                    new OA\Property(property: 'card_content', description: 'HTML do editor rico.', type: 'string', nullable: true),
                ],
                type: 'object',
            ),
        ),
        tags: ['Painel · Provão Paulista'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Página atualizada.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/ProvaoPage'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function update(ProvaoPageRequest $request): ProvaoPageResource
    {
        return $this->pagina($this->provao->updatePage($request->validated()));
    }

    private function pagina(ProvaoPage $pagina): ProvaoPageResource
    {
        return new ProvaoPageResource($pagina, ProvaoLink::ordered()->get());
    }
}
