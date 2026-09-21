<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Texto de abertura da vitrine `/cursos`. */
#[OA\Schema(
    schema: 'CourseLanding',
    title: 'Vitrine de cursos',
    properties: [
        new OA\Property(property: 'description', description: 'HTML do editor rico, acima dos cards.', type: 'string', nullable: true),
    ],
    type: 'object',
)]
final class CourseLanding {}
