<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Uma rota do site: o caminho e o nome que o select do painel exibe. */
#[OA\Schema(
    schema: 'SiteRoute',
    title: 'Rota do site',
    properties: [
        new OA\Property(property: 'path', type: 'string', example: '/cursos/engenharia'),
        new OA\Property(property: 'label', type: 'string', example: 'Cursos • Engenharia'),
    ],
    type: 'object',
)]
final class SiteRoute {}
