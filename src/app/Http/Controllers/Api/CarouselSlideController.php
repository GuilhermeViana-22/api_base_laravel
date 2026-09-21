<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicCarouselSlideResource;
use App\Models\CarouselSlide;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Carrossel da página inicial, como o site lê (`/api/carousel-slides`).
 *
 * Só slides ativos e com imagem, na ordem definida no painel.
 */
class CarouselSlideController extends Controller
{
    #[OA\Get(
        path: '/carousel-slides',
        summary: 'Slides do carrossel do topo da página inicial',
        description: 'Só slides ativos e com imagem, na ordem definida no painel. Sem paginação.',
        tags: ['Site · Página inicial'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Os slides que vão ao ar.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/PublicCarouselSlide')),
                ], type: 'object'),
            ),
        ],
    )]
    public function index(): AnonymousResourceCollection
    {
        return PublicCarouselSlideResource::collection(CarouselSlide::visible()->ordered()->get());
    }
}
