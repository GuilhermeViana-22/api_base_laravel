<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Slide do carrossel como o painel edita: todos os campos do formulário. */
#[OA\Schema(
    schema: 'CarouselSlide',
    title: 'Slide do carrossel (painel)',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 3),
        new OA\Property(property: 'active', description: 'Slide inativo não vai para o site.', type: 'boolean', example: true),
        new OA\Property(property: 'position', description: 'Ordem no carrossel, do menor para o maior.', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Inscrições abertas para o vestibular'),
        new OA\Property(property: 'image_url', description: 'Slide sem imagem não aparece no site.', type: 'string', nullable: true, example: 'http://localhost:8019/storage/carrossel/slide-3.webp'),
        new OA\Property(property: 'button_label', type: 'string', nullable: true, example: 'Inscreva-se'),
        new OA\Property(property: 'button_route', description: 'Caminho de uma página do site (ver `GET /site/rotas`).', type: 'string', nullable: true, example: '/vestibular'),
        new OA\Property(property: 'button_color', type: 'string', nullable: true, example: '#172833'),
        new OA\Property(property: 'button_text_color', type: 'string', nullable: true, example: '#FFFFFF'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
final class CarouselSlide {}
