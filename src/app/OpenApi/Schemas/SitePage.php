<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Página do site e a visibilidade dela. */
#[OA\Schema(
    schema: 'SitePage',
    title: 'Página do site',
    properties: [
        new OA\Property(property: 'path', type: 'string', example: '/vestibular'),
        new OA\Property(property: 'label', type: 'string', nullable: true, example: 'Vestibular'),
        new OA\Property(property: 'visible', description: 'Chave do CMS. `false` tira a página por tempo indeterminado.', type: 'boolean', example: true),
        new OA\Property(property: 'hidden_from', description: 'Começo da janela em que a página fica oculta.', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'hidden_until', description: 'Fim da janela; vazio é por tempo indeterminado.', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'hidden_now', description: 'O que vale para o visitante neste momento.', type: 'boolean', example: false),
    ],
    type: 'object',
)]
final class SitePage {}
