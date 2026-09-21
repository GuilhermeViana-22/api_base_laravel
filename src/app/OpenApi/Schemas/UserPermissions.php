<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** O que o modal "Gerenciar permissões" mostra de uma pessoa. */
#[OA\Schema(
    schema: 'UserPermissions',
    title: 'Permissões de uma pessoa',
    properties: [
        new OA\Property(
            property: 'user',
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 7),
                new OA\Property(property: 'name', type: 'string', example: 'Maria Souza'),
                new OA\Property(property: 'email', type: 'string', format: 'email'),
            ],
            type: 'object',
        ),
        new OA\Property(
            property: 'role',
            description: 'Papel que serve de base. Nulo em quem não entra no painel.',
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 3),
                new OA\Property(property: 'name', type: 'string', example: 'Editor'),
                new OA\Property(property: 'is_master', type: 'boolean', example: false),
                new OA\Property(property: 'abilities', type: 'object', example: ['posts' => ['view', 'update']]),
            ],
            type: 'object',
            nullable: true,
        ),
        new OA\Property(
            property: 'overrides',
            description: 'Exceções só desta pessoa. Lista vazia numa tela significa "não enxerga".',
            type: 'object',
            example: ['pages.institucional.historia' => ['view', 'update'], 'pages.transparencia' => []],
        ),
        new OA\Property(
            property: 'effective',
            description: 'Papel e exceções já somados: o que vale de verdade, tela a tela.',
            type: 'object',
            example: ['posts' => ['view', 'update'], 'pages.institucional.historia' => ['view', 'update']],
        ),
        new OA\Property(property: 'exceptions_count', type: 'integer', example: 2),
    ],
    type: 'object',
)]
final class UserPermissions {}
