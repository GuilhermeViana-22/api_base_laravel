<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Páginas de um grupo (Principais, Cursos, Institucional...), como a tela lista. */
#[OA\Schema(
    schema: 'SitePageGroup',
    title: 'Grupo de páginas do site',
    properties: [
        new OA\Property(property: 'group', type: 'string', example: 'Principais'),
        new OA\Property(property: 'pages', type: 'array', items: new OA\Items(ref: '#/components/schemas/SitePage')),
    ],
    type: 'object',
)]
final class SitePageGroup {}
