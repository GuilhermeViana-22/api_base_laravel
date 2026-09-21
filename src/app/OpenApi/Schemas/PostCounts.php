<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Totais das notícias, sem filtro: os cards do topo da listagem do painel.
 *
 * `max_featured` é o limite de destaques da página inicial — vem junto para o
 * painel não repetir a regra do backend.
 */
#[OA\Schema(
    schema: 'PostCounts',
    title: 'Contagem de notícias',
    properties: [
        new OA\Property(property: 'total', type: 'integer', example: 31),
        new OA\Property(property: 'published', type: 'integer', example: 24),
        new OA\Property(property: 'draft', type: 'integer', example: 7),
        new OA\Property(property: 'featured', type: 'integer', example: 2),
        new OA\Property(property: 'max_featured', type: 'integer', example: 2),
    ],
    type: 'object',
)]
final class PostCounts {}
