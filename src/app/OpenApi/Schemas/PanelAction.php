<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Coluna da matriz de permissões: o que se pode fazer num módulo. */
#[OA\Schema(
    schema: 'PanelAction',
    title: 'Ação do painel',
    properties: [
        new OA\Property(property: 'key', type: 'string', enum: ['view', 'create', 'update', 'delete'], example: 'view'),
        new OA\Property(property: 'label', type: 'string', example: 'Ver'),
    ],
    type: 'object',
)]
final class PanelAction {}
