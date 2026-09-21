<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Resposta 422 do Laravel: a mensagem do primeiro erro e, em `errors`, todas
 * as mensagens por campo (o painel usa para marcar o formulário).
 */
#[OA\Schema(
    schema: 'ValidationError',
    title: 'Erro de validação',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'O título é obrigatório.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(type: 'array', items: new OA\Items(type: 'string')),
            example: ['title' => ['O título é obrigatório.']],
        ),
    ],
    type: 'object',
)]
final class ValidationError {}
