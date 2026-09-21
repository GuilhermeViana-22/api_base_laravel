<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Resposta que traz só um recado em português (logout, 401, 404, 403...). */
#[OA\Schema(
    schema: 'MessageResponse',
    title: 'Mensagem',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Logout realizado com sucesso.'),
    ],
    type: 'object',
)]
final class MessageResponse {}
