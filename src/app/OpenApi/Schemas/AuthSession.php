<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Sessão aberta: a conta e o token Bearer que as rotas do painel exigem.
 *
 * A validade sai de AUTH_TOKEN_TTL_MINUTES (8 horas por padrão).
 */
#[OA\Schema(
    schema: 'AuthSession',
    title: 'Sessão',
    properties: [
        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
        new OA\Property(property: 'access_token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...'),
        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', nullable: true, example: '2026-09-16T21:45:00.000000Z'),
    ],
    type: 'object',
)]
final class AuthSession {}
