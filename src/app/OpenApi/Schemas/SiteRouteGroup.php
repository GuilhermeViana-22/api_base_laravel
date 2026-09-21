<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Grupo do select "para onde o botão leva": as rotas principais e, depois, um
 * grupo por seção com as páginas internas dela.
 */
#[OA\Schema(
    schema: 'SiteRouteGroup',
    title: 'Grupo de rotas',
    properties: [
        new OA\Property(property: 'group', type: 'string', example: 'Principais'),
        new OA\Property(property: 'routes', type: 'array', items: new OA\Items(ref: '#/components/schemas/SiteRoute')),
    ],
    type: 'object',
)]
final class SiteRouteGroup {}
