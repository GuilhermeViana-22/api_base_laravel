<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * A própria conta, como quem está logado a recebe (`GET /auth/me`).
 *
 * Vem com o papel e a matriz já resolvida (`abilities`): é com isso que o
 * painel monta o menu, libera rotas e esconde botões.
 */
#[OA\Schema(
    schema: 'User',
    title: 'Usuário',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 7),
        new OA\Property(property: 'name', type: 'string', example: 'Maria Souza'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'maria@exemplo.com'),
        new OA\Property(property: 'email_verified', type: 'boolean', example: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-09-10T13:45:00.000000Z'),
        new OA\Property(
            property: 'role',
            description: 'Papel no painel. Nulo em quem só tem cadastro no site e não entra no CMS.',
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 3),
                new OA\Property(property: 'name', type: 'string', example: 'Editor de Notícias'),
                new OA\Property(property: 'slug', type: 'string', example: 'editor-de-noticias'),
                new OA\Property(property: 'is_master', type: 'boolean', example: false),
            ],
            type: 'object',
            nullable: true,
        ),
        new OA\Property(
            property: 'abilities',
            description: 'O que a conta pode fazer, por módulo. O Master recebe tudo marcado.',
            type: 'object',
            example: ['posts' => ['access', 'view', 'create', 'update'], 'home' => ['access', 'view']],
        ),
        new OA\Property(
            property: 'session_timeout_minutes',
            description: 'Minutos de inatividade que o painel aceita antes de deslogar. Vem de Configurações > Segurança.',
            type: 'integer',
            example: 30,
        ),
    ],
    type: 'object',
)]
final class User {}
