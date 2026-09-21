<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Nível de complexidade de senha, como a tela o descreve. */
#[OA\Schema(
    schema: 'PasswordComplexity',
    title: 'Complexidade de senha',
    properties: [
        new OA\Property(property: 'key', type: 'string', enum: ['basica', 'media', 'forte'], example: 'media'),
        new OA\Property(property: 'label', type: 'string', example: 'Média'),
        new OA\Property(property: 'description', type: 'string', example: 'Oito caracteres, com letras e números.'),
    ],
    type: 'object',
)]
final class PasswordComplexity {}
