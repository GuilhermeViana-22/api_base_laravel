<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Erro de negócio da autenticação (ver App\Exceptions\AuthException).
 *
 * O front decide pelo `code`, que é estável, e só exibe a `message`. Alguns
 * erros trazem campos extras: `attempts_remaining` (código errado),
 * `retry_after` (reenvio em espera) e `verification` (e-mail não confirmado).
 */
#[OA\Schema(
    schema: 'AuthError',
    title: 'Erro da autenticação',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'E-mail ou senha incorretos.'),
        new OA\Property(
            property: 'code',
            type: 'string',
            enum: [
                'invalid_credentials',
                'email_not_verified',
                'email_already_verified',
                'verification_code_invalid',
                'verification_code_expired',
                'verification_too_many_attempts',
                'verification_resend_cooldown',
                'verification_email_failed',
                'too_many_requests',
            ],
            example: 'invalid_credentials',
        ),
        new OA\Property(property: 'attempts_remaining', type: 'integer', example: 3, nullable: true),
        new OA\Property(property: 'retry_after', description: 'Segundos até poder pedir outro código.', type: 'integer', example: 42, nullable: true),
        new OA\Property(property: 'verification', ref: '#/components/schemas/Verification', nullable: true),
    ],
    type: 'object',
)]
final class AuthError {}
