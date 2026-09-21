<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBannerRequest;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use App\Services\BannerService;
use OpenApi\Attributes as OA;

/**
 * Edição dos banners pelo painel (`/api/admin/banners/{key}`, exige login).
 * Recurso único por chave: só lê e atualiza (não há criar nem excluir).
 */
class BannerController extends Controller
{
    public function __construct(private readonly BannerService $banners)
    {
    }

    /** GET /api/admin/banners/{key} */
    #[OA\Get(
        path: '/admin/banners/{key}',
        summary: 'Banner de uma página, para editar',
        description: 'Mesmo conteúdo da rota pública (`GET /banners/{key}`), lido pela tela de edição do painel.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Banners'],
        parameters: [new OA\Parameter(ref: '#/components/parameters/bannerKey')],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Textos e foto do banner.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Banner'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ],
    )]
    public function show(string $key): BannerResource
    {
        return new BannerResource(Banner::forKey($key));
    }

    /** PUT /api/admin/banners/{key}: rótulo e título. */
    #[OA\Put(
        path: '/admin/banners/{key}',
        summary: 'Atualiza os textos do banner',
        description: 'Só rótulo e título; a foto tem rotas próprias. Os limites seguem o espaço do hero: '
            .'o rótulo é uma linha curta e o título precisa caber na faixa vermelha em até 3 linhas. '
            .'O rótulo só pode ficar vazio em `vestibular`, `provao-paulista`, `institucional` e `cursos`.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title'],
                properties: [
                    new OA\Property(property: 'label', type: 'string', maxLength: 60, nullable: true, example: 'Notícias UNIVESP'),
                    new OA\Property(property: 'title', type: 'string', maxLength: 150, example: 'Fique por dentro do que acontece na universidade'),
                ],
                type: 'object',
            ),
        ),
        tags: ['Painel · Banners'],
        parameters: [new OA\Parameter(ref: '#/components/parameters/bannerKey')],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Banner atualizado.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Banner'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function update(UpdateBannerRequest $request, string $key): BannerResource
    {
        return new BannerResource($this->banners->updateTexts(Banner::forKey($key), $request->validated()));
    }
}
