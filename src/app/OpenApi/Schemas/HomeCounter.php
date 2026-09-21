<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Contador da página inicial (ex.: 392 "Municípios no Estado de São Paulo"). */
#[OA\Schema(
    schema: 'HomeCounter',
    title: 'Contador',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 2),
        new OA\Property(property: 'active', type: 'boolean', example: true),
        new OA\Property(property: 'position', type: 'integer', example: 0),
        new OA\Property(property: 'label', type: 'string', example: 'Municípios no Estado de São Paulo'),
        new OA\Property(property: 'value', type: 'integer', example: 392),
        new OA\Property(property: 'suffix', description: 'O que vem depois do número (ex.: `mil`, `+`).', type: 'string', nullable: true, example: '+'),
    ],
    type: 'object',
)]
final class HomeCounter {}
