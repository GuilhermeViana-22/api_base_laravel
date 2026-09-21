<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Endereços da paginação do Laravel, como vêm em `links`. */
#[OA\Schema(
    schema: 'PaginationLinks',
    title: 'Links da paginação',
    properties: [
        new OA\Property(property: 'first', type: 'string', nullable: true, example: 'http://localhost:8019/api/posts?page=1'),
        new OA\Property(property: 'last', type: 'string', nullable: true, example: 'http://localhost:8019/api/posts?page=4'),
        new OA\Property(property: 'prev', type: 'string', nullable: true, example: null),
        new OA\Property(property: 'next', type: 'string', nullable: true, example: 'http://localhost:8019/api/posts?page=2'),
    ],
    type: 'object',
)]
final class PaginationLinks {}
