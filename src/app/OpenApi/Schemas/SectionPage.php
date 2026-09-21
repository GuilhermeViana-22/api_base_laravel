<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Página interna de uma seção. Por ora só a rota: slug e nome exibido — o
 * conteúdo próprio de cada página vem depois.
 */
#[OA\Schema(
    schema: 'SectionPage',
    title: 'Página de seção',
    properties: [
        new OA\Property(property: 'slug', type: 'string', example: 'missao-visao-e-valores'),
        new OA\Property(property: 'name', type: 'string', example: 'Missão, Visão e Valores'),
    ],
    type: 'object',
)]
final class SectionPage {}
