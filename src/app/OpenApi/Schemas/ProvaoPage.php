<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Página /provao-paulista: textos da coluna da direita e os links do card. */
#[OA\Schema(
    schema: 'ProvaoPage',
    title: 'Página do Provão Paulista',
    properties: [
        new OA\Property(property: 'title', description: 'Título vermelho do topo.', type: 'string', nullable: true),
        new OA\Property(property: 'content', description: 'HTML do editor rico: texto, cronograma (tabela) e aviso.', type: 'string', nullable: true),
        new OA\Property(property: 'card_title', description: 'Título do card de matrícula.', type: 'string', nullable: true, example: 'Informações sobre a Matrícula'),
        new OA\Property(property: 'card_content', description: 'HTML do editor rico, acima dos links do card.', type: 'string', nullable: true),
        new OA\Property(
            property: 'links',
            description: 'No site, só os que têm destino; no painel, todos.',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/ProvaoLink'),
        ),
    ],
    type: 'object',
)]
final class ProvaoPage {}
