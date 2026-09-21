<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Ajustes de segurança do painel. */
#[OA\Schema(
    schema: 'SecuritySettings',
    title: 'Segurança',
    properties: [
        new OA\Property(property: 'session_timeout_minutes', description: 'Validade do token e tempo de inatividade aceito pelo painel.', type: 'integer', example: 30),
        new OA\Property(property: 'password_complexity', type: 'string', enum: ['basica', 'media', 'forte'], example: 'media'),
        new OA\Property(property: 'require_two_factor', description: 'Guardado, ainda sem efeito: o projeto não tem segundo fator.', type: 'boolean', example: false),
    ],
    type: 'object',
)]
final class SecuritySettings {}
