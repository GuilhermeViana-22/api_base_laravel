<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Quantos usuários há em cada situação, sem filtro: os cards do topo da tela. */
#[OA\Schema(
    schema: 'UserCounts',
    title: 'Contagem de usuários',
    properties: [
        new OA\Property(property: 'total', type: 'integer', example: 128),
        new OA\Property(property: 'active', type: 'integer', example: 110),
        new OA\Property(property: 'inactive', type: 'integer', example: 12),
        new OA\Property(property: 'dismissed', type: 'integer', example: 4),
        new OA\Property(property: 'on_vacation', type: 'integer', example: 2),
    ],
    type: 'object',
)]
final class UserCounts {}
