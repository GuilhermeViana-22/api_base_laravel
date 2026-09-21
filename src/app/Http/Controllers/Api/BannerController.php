<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use OpenApi\Attributes as OA;

/** Leitura pública dos banners (sem login). */
class BannerController extends Controller
{
    /** GET /api/banners/{key}: textos e foto do hero da página. */
    #[OA\Get(
        path: '/banners/{key}',
        summary: 'Banner (hero) de uma página do site',
        description: 'Chave fora da lista nem chega ao controller: a rota só aceita as chaves fixas. '
            .'Na primeira leitura o banner nasce com o conteúdo padrão, então esta rota nunca responde 404.',
        tags: ['Site · Banners'],
        parameters: [new OA\Parameter(ref: '#/components/parameters/bannerKey')],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Textos e foto do hero.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Banner'),
                ], type: 'object'),
            ),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ],
    )]
    public function show(string $key): BannerResource
    {
        return new BannerResource(Banner::forKey($key));
    }
}
