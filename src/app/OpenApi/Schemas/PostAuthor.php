<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Quem escreveu a notícia, como o painel mostra na listagem. */
#[OA\Schema(
    schema: 'PostAuthor',
    title: 'Autor da notícia',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 7),
        new OA\Property(property: 'name', type: 'string', example: 'Maria Souza'),
    ],
    type: 'object',
    nullable: true,
)]
final class PostAuthor {}
