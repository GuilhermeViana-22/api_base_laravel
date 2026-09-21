<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Botão do slide, já pronto para o site desenhar.
 *
 * As cores chegam resolvidas: quando o painel não escolheu nenhuma, vêm os
 * valores de fábrica, então o front não precisa conhecê-los.
 */
#[OA\Schema(
    schema: 'SlideButton',
    title: 'Botão do slide',
    properties: [
        new OA\Property(property: 'label', type: 'string', example: 'Inscreva-se'),
        new OA\Property(property: 'route', type: 'string', example: '/vestibular'),
        new OA\Property(property: 'color', type: 'string', example: '#172833'),
        new OA\Property(property: 'text_color', type: 'string', example: '#FFFFFF'),
    ],
    type: 'object',
    nullable: true,
)]
final class SlideButton {}
