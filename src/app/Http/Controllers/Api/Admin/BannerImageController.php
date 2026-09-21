<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImageUploadRequest;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use App\Services\BannerService;
use OpenApi\Attributes as OA;

/** Foto de fundo do banner: sub-recurso único (`/banners/{key}/image`). */
class BannerImageController extends Controller
{
    public function __construct(private readonly BannerService $banners)
    {
    }

    /** POST multipart com o campo `file`; substitui a foto anterior. */
    #[OA\Post(
        path: '/admin/banners/{key}/image',
        summary: 'Envia a foto de fundo do banner',
        description: 'Substitui a foto anterior, que sai do disco junto.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/ImageUpload'),
        tags: ['Painel · Banners'],
        parameters: [new OA\Parameter(ref: '#/components/parameters/bannerKey')],
        responses: [
            new OA\Response(
                response: 200,
                description: 'O banner, já com `image_url` novo.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Banner'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function store(ImageUploadRequest $request, string $key): BannerResource
    {
        return new BannerResource($this->banners->replaceImage(Banner::forKey($key), $request->file('file')));
    }

    /** DELETE: tira a foto; o site mostra o fundo escuro. */
    #[OA\Delete(
        path: '/admin/banners/{key}/image',
        summary: 'Tira a foto de fundo do banner',
        description: 'Sem foto, o site mostra o hero no fundo escuro.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Banners'],
        parameters: [new OA\Parameter(ref: '#/components/parameters/bannerKey')],
        responses: [
            new OA\Response(
                response: 200,
                description: 'O banner, agora com `image_url` nulo.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Banner'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ],
    )]
    public function destroy(string $key): BannerResource
    {
        return new BannerResource($this->banners->removeImage(Banner::forKey($key)));
    }
}
