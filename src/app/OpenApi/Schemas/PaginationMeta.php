<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Estado da paginação, como vem em `meta`.
 *
 * `current_page` e `last_page` são o que o botão "Carregar mais" do site usa
 * para saber se ainda há página seguinte.
 */
#[OA\Schema(
    schema: 'PaginationMeta',
    title: 'Meta da paginação',
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'from', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'to', type: 'integer', nullable: true, example: 9),
        new OA\Property(property: 'last_page', type: 'integer', example: 4),
        new OA\Property(property: 'per_page', type: 'integer', example: 9),
        new OA\Property(property: 'total', type: 'integer', example: 31),
        new OA\Property(property: 'path', type: 'string', example: 'http://localhost:8019/api/posts'),
        new OA\Property(
            property: 'links',
            description: 'Os botões de página, como o Laravel monta.',
            type: 'array',
            items: new OA\Items(properties: [
                new OA\Property(property: 'url', type: 'string', nullable: true),
                new OA\Property(property: 'label', type: 'string', example: '1'),
                new OA\Property(property: 'active', type: 'boolean', example: true),
            ], type: 'object'),
        ),
    ],
    type: 'object',
)]
final class PaginationMeta {}
