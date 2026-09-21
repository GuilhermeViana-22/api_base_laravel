<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HomeCounterResource;
use App\Http\Resources\HomeSectionResource;
use App\Http\Resources\TestimonialResource;
use App\Models\HomeCounter;
use App\Models\HomeSection;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Conteúdo da página inicial como o site lê (`/api/home`).
 *
 * Vem tudo numa resposta só — blocos, contadores e depoimentos — porque a
 * home precisa de todos ao mesmo tempo: três requisições virariam três
 * saltos de layout enquanto a página monta. O carrossel continua na rota
 * dele, que o topo da página busca primeiro.
 */
class HomeContentController extends Controller
{
    #[OA\Get(
        path: '/home',
        summary: 'Conteúdo da página inicial, de uma vez',
        description: 'Blocos, contadores e depoimentos numa resposta só: a home precisa de todos ao mesmo tempo '
            .'e três requisições virariam três saltos de layout enquanto a página monta. '
            .'Só entra o que está ativo. O carrossel do topo tem rota própria (`GET /carousel-slides`).',
        tags: ['Site · Página inicial'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tudo o que a página inicial desenha (menos o carrossel).',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/HomeContent'),
                ], type: 'object'),
            ),
        ],
    )]
    public function index(): JsonResponse
    {
        $blocos = collect(HomeSection::KEYS)
            ->mapWithKeys(fn (string $key) => [$key => new HomeSectionResource(HomeSection::forKey($key))])
            ->filter(fn (HomeSectionResource $bloco) => $bloco->resource->active);

        return response()->json([
            'data' => [
                'sections' => $blocos,
                'counters' => HomeCounterResource::collection(HomeCounter::active()->ordered()->get()),
                'testimonials' => TestimonialResource::collection(Testimonial::active()->ordered()->get()),
            ],
        ]);
    }
}
