<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Depoimento de ex-aluno exibido na página inicial. */
#[OA\Schema(
    schema: 'Testimonial',
    title: 'Depoimento',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 5),
        new OA\Property(property: 'active', type: 'boolean', example: true),
        new OA\Property(property: 'position', type: 'integer', example: 0),
        new OA\Property(property: 'name', type: 'string', example: 'João Pereira'),
        new OA\Property(property: 'quote', type: 'string', example: 'A UNIVESP me ajudou a alcançar um objetivo que eu não achava possível.'),
        new OA\Property(property: 'photo_url', type: 'string', nullable: true, example: 'http://localhost:8019/storage/home/depoimentos/joao.webp'),
    ],
    type: 'object',
)]
final class Testimonial {}
