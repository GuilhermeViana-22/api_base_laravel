<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Parâmetros que aparecem em mais de uma rota, declarados uma vez só e
 * referenciados por `#/components/parameters/<nome>`.
 */
#[OA\Parameter(
    parameter: 'page',
    name: 'page',
    description: 'Página desejada. A primeira é a 1.',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'integer', minimum: 1, example: 1),
)]
#[OA\Parameter(
    parameter: 'bannerKey',
    name: 'key',
    description: 'Qual banner. As chaves são fixas: uma por página do site que tem hero.',
    in: 'path',
    required: true,
    schema: new OA\Schema(type: 'string', enum: ['noticias', 'vestibular', 'provao-paulista', 'institucional', 'cursos']),
)]
#[OA\Parameter(
    parameter: 'homeSectionKey',
    name: 'key',
    description: 'Qual bloco da página inicial. As chaves são fixas.',
    in: 'path',
    required: true,
    schema: new OA\Schema(type: 'string', enum: ['video', 'mapa', 'manual', 'transparencia', 'depoimentos']),
)]
final class Parameters {}
