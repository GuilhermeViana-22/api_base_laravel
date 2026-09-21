<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImageUploadRequest;
use App\Http\Requests\Admin\UpdateHomeSectionRequest;
use App\Http\Resources\HomeSectionResource;
use App\Models\HomeSection;
use App\Services\HomeContentService;
use OpenApi\Attributes as OA;

/**
 * Blocos de chave fixa da página inicial (`/api/admin/home/sections/{key}`).
 *
 * Recurso único por chave: só lê e atualiza (não há criar nem excluir). A
 * imagem tem rotas próprias, como nos banners e nas capas de notícia.
 */
class HomeSectionController extends Controller
{
    public function __construct(private readonly HomeContentService $home)
    {
    }

    /** GET /api/admin/home/sections/{key} */
    #[OA\Get(
        path: '/admin/home/sections/{key}',
        summary: 'Um bloco da página inicial, para editar',
        description: 'Recurso único por chave: não há criar nem excluir. Na primeira leitura o bloco nasce '
            .'com o conteúdo padrão, então esta rota não responde 404. Diferente da rota do site, aqui o bloco '
            .'inativo também abre.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Página inicial'],
        parameters: [new OA\Parameter(ref: '#/components/parameters/homeSectionKey')],
        responses: [
            new OA\Response(
                response: 200,
                description: 'O bloco, com `supports_image` e `supports_button` dizendo o que ele aceita.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/HomeSection'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ],
    )]
    public function show(string $key): HomeSectionResource
    {
        return new HomeSectionResource(HomeSection::forKey($key));
    }

    /** PATCH /api/admin/home/sections/{key} */
    #[OA\Patch(
        path: '/admin/home/sections/{key}',
        summary: 'Atualiza um bloco da página inicial (envio parcial)',
        description: 'Duas regras variam conforme a chave: `video_url` só existe no bloco `video`, e o par de '
            .'botão não existe no bloco `video`. Enviar um campo que o bloco não tem responde 422. '
            .'A imagem tem rotas próprias.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'active', type: 'boolean', example: true),
                    new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Polos Univesp'),
                    new OA\Property(property: 'description', description: 'Texto do bloco; no bloco `video` é HTML do editor rico.', type: 'string', maxLength: 5000, nullable: true),
                    new OA\Property(property: 'video_url', description: 'Só no bloco `video`; nos outros, 422.', type: 'string', format: 'uri', maxLength: 255, nullable: true, example: 'https://www.youtube.com/watch?v=kCJQ2VPTCqI'),
                    new OA\Property(property: 'button_label', description: 'Rótulo e destino andam juntos. Não existe no bloco `video`.', type: 'string', maxLength: 60, nullable: true, example: 'Saiba mais'),
                    new OA\Property(property: 'button_route', description: 'Precisa ser um dos caminhos de `GET /site/rotas`.', type: 'string', nullable: true, example: '/polo'),
                ],
                type: 'object',
            ),
        ),
        tags: ['Painel · Página inicial'],
        parameters: [new OA\Parameter(ref: '#/components/parameters/homeSectionKey')],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Bloco atualizado.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/HomeSection'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function update(UpdateHomeSectionRequest $request, string $key): HomeSectionResource
    {
        return new HomeSectionResource($this->home->updateSection(HomeSection::forKey($key), $request->validated()));
    }

    /** POST multipart com o campo `file`; substitui a imagem anterior. */
    #[OA\Post(
        path: '/admin/home/sections/{key}/image',
        summary: 'Envia a imagem do bloco',
        description: 'Substitui a imagem anterior, que sai do disco junto. Só os blocos com `supports_image` '
            .'usam imagem (`mapa`, `manual` e `depoimentos`).',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/ImageUpload'),
        tags: ['Painel · Página inicial'],
        parameters: [new OA\Parameter(ref: '#/components/parameters/homeSectionKey')],
        responses: [
            new OA\Response(
                response: 200,
                description: 'O bloco, já com `image_url` novo.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/HomeSection'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function storeImage(ImageUploadRequest $request, string $key): HomeSectionResource
    {
        return new HomeSectionResource(
            $this->home->replaceSectionImage(HomeSection::forKey($key), $request->file('file')),
        );
    }

    #[OA\Delete(
        path: '/admin/home/sections/{key}/image',
        summary: 'Tira a imagem do bloco',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Página inicial'],
        parameters: [new OA\Parameter(ref: '#/components/parameters/homeSectionKey')],
        responses: [
            new OA\Response(
                response: 200,
                description: 'O bloco, agora com `image_url` nulo.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/HomeSection'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ],
    )]
    public function destroyImage(string $key): HomeSectionResource
    {
        return new HomeSectionResource($this->home->removeSectionImage(HomeSection::forKey($key)));
    }
}
