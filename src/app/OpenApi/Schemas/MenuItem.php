<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Item do menu do cabeçalho, com o submenu já montado.
 *
 * Item sem submenu traz `children` vazio. Nos itens de seção, o primeiro filho
 * é a página de abertura ("Visão geral") e os demais são as páginas internas.
 */
#[OA\Schema(
    schema: 'MenuItem',
    title: 'Item do menu',
    properties: [
        new OA\Property(property: 'label', type: 'string', example: 'Institucional'),
        new OA\Property(property: 'path', type: 'string', example: '/institucional'),
        new OA\Property(property: 'children', type: 'array', items: new OA\Items(ref: '#/components/schemas/SiteRoute')),
    ],
    type: 'object',
)]
final class MenuItem {}
