<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Nó da árvore de telas do painel, uma linha da matriz de permissões.
 *
 * `children` aparece nas áreas que têm telas dentro (Página inicial e
 * Páginas, esta última com as seções e cada página delas). O que não for
 * marcado numa tela herda o que estiver marcado na área acima.
 */
#[OA\Schema(
    schema: 'PanelResource',
    title: 'Tela do painel',
    properties: [
        new OA\Property(property: 'key', description: 'Chave hierárquica, separada por ponto.', type: 'string', example: 'pages.institucional.historia'),
        new OA\Property(property: 'label', type: 'string', example: 'História'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Notícias do site, imagem de capa e imagens do corpo do texto.'),
        new OA\Property(
            property: 'children',
            description: 'Telas dentro desta área, no mesmo formato.',
            type: 'array',
            items: new OA\Items(type: 'object'),
            nullable: true,
        ),
    ],
    type: 'object',
)]
final class PanelResource {}
