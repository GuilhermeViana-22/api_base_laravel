<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Bloco de chave fixa da página inicial.
 *
 * Nem todo bloco tem tudo: `supports_image` e `supports_button` dizem o que
 * aquela chave aceita, e o bloco sem botão nunca devolve botão. Só o bloco
 * `video` tem `video_url`.
 */
#[OA\Schema(
    schema: 'HomeSection',
    title: 'Bloco da página inicial',
    properties: [
        new OA\Property(property: 'key', type: 'string', enum: ['video', 'mapa', 'manual', 'transparencia', 'depoimentos'], example: 'mapa'),
        new OA\Property(property: 'active', description: 'Bloco inativo não vai para o site.', type: 'boolean', example: true),
        new OA\Property(property: 'title', type: 'string', example: 'Polos Univesp'),
        new OA\Property(property: 'description', description: 'Texto do bloco; no bloco de vídeo é HTML.', type: 'string', nullable: true),
        new OA\Property(property: 'image_url', type: 'string', nullable: true),
        new OA\Property(property: 'video_url', description: 'Só no bloco `video`.', type: 'string', nullable: true, example: 'https://www.youtube.com/watch?v=kCJQ2VPTCqI'),
        new OA\Property(property: 'button_label', type: 'string', nullable: true, example: 'Saiba mais'),
        new OA\Property(property: 'button_route', type: 'string', nullable: true, example: '/polo'),
        new OA\Property(property: 'supports_image', type: 'boolean', example: true),
        new OA\Property(property: 'supports_button', type: 'boolean', example: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
final class HomeSection {}
