<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Seção da página /vestibular (em geral, um ano), com os blocos em ordem. */
#[OA\Schema(
    schema: 'VestibularSection',
    title: 'Seção do vestibular',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'position', type: 'integer', example: 1),
        new OA\Property(property: 'spacing_top', description: 'Espaço acima, em px. Nulo: o automático do site.', type: 'integer', nullable: true),
        new OA\Property(property: 'name', description: 'Nome no painel; não aparece no site.', type: 'string', example: 'Vestibular 2026'),
        new OA\Property(property: 'divider', description: 'Linha acima da seção.', type: 'string', enum: ['none', 'solid', 'dotted']),
        new OA\Property(
            property: 'blocks',
            description: 'No site, só os visíveis (link com destino, vídeo com URL); no painel, todos.',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/VestibularBlock'),
        ),
    ],
    type: 'object',
)]
final class VestibularSection {}
