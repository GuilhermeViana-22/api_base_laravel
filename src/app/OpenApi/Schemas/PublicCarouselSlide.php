<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Slide como o site recebe: só o que a tela desenha. */
#[OA\Schema(
    schema: 'PublicCarouselSlide',
    title: 'Slide do carrossel (site)',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 3),
        new OA\Property(property: 'title', type: 'string', example: 'Inscrições abertas para o vestibular'),
        new OA\Property(property: 'image_url', type: 'string', nullable: true, example: 'http://localhost:8019/storage/carrossel/slide-3.webp'),
        new OA\Property(property: 'button', ref: '#/components/schemas/SlideButton'),
    ],
    type: 'object',
)]
final class PublicCarouselSlide {}
