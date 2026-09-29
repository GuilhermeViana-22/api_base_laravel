<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Página de uma seção, nas duas formas que a API devolve: o item da lista
 * (menu) e a página completa, com o conteúdo.
 */
#[OA\Schema(
    schema: 'SectionPage',
    title: 'Página de seção (item da lista)',
    properties: [
        new OA\Property(property: 'slug', type: 'string', example: 'missao-visao-e-valores'),
        new OA\Property(property: 'name', description: 'Rótulo do menu.', type: 'string', example: 'Missão, visão e valores'),
        new OA\Property(property: 'title', description: 'Título da página.', type: 'string', example: 'Missão, visão e valores'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'SectionPageDetail',
    title: 'Página de seção (completa)',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 2),
        new OA\Property(property: 'section', type: 'string', enum: ['institucional', 'pesquisa', 'transparencia'], example: 'institucional'),
        new OA\Property(property: 'slug', description: 'Trecho da URL; não muda depois de criado.', type: 'string', example: 'missao-visao-e-valores'),
        new OA\Property(property: 'path', type: 'string', example: '/institucional/missao-visao-e-valores'),
        new OA\Property(property: 'label', description: 'Texto do menu.', type: 'string', example: 'Missão, visão e valores'),
        new OA\Property(property: 'title', description: 'Título da página (aba do navegador e topo).', type: 'string', example: 'Missão, visão e valores'),
        new OA\Property(property: 'content', description: 'HTML do editor, já filtrado pelo ContentSanitizer.', type: 'string', nullable: true, example: '<p><span class="texto-vermelho"><strong>Missão</strong></span></p>'),
        new OA\Property(property: 'position', type: 'integer', example: 2),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
    type: 'object',
)]
final class SectionPage {}
