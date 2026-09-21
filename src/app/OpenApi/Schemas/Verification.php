<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Estado do código pendente, para a tela de verificação montar os contadores
 * de expiração e de reenvio sem conhecer as regras da API.
 *
 * Nunca traz o código nem o hash dele.
 */
#[OA\Schema(
    schema: 'Verification',
    title: 'Código pendente',
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'maria@exemplo.com'),
        new OA\Property(property: 'code_length', description: 'Quantos dígitos o código tem.', type: 'integer', example: 6),
        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', example: '2026-09-16T14:00:00.000000Z'),
        new OA\Property(property: 'resend_available_at', description: 'A partir de quando é possível pedir outro código.', type: 'string', format: 'date-time', example: '2026-09-16T13:46:00.000000Z'),
        new OA\Property(property: 'attempts_remaining', type: 'integer', example: 5),
    ],
    type: 'object',
)]
final class Verification {}
