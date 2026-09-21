<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Tudo o que a página inicial precisa, numa resposta só.
 *
 * Em `sections` só entram os blocos ativos, indexados pela chave — bloco
 * desligado no painel simplesmente não aparece na resposta. O carrossel do
 * topo fica de fora: tem rota própria (`GET /carousel-slides`).
 */
#[OA\Schema(
    schema: 'HomeContent',
    title: 'Conteúdo da página inicial',
    properties: [
        new OA\Property(
            property: 'sections',
            description: 'Blocos ativos, por chave.',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(ref: '#/components/schemas/HomeSection'),
        ),
        new OA\Property(property: 'counters', type: 'array', items: new OA\Items(ref: '#/components/schemas/HomeCounter')),
        new OA\Property(property: 'testimonials', type: 'array', items: new OA\Items(ref: '#/components/schemas/Testimonial')),
    ],
    type: 'object',
)]
final class HomeContent {}
