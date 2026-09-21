<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Curso na relação da coluna da esquerda: só o que o link precisa. */
#[OA\Schema(
    schema: 'CourseSummary',
    title: 'Curso (resumo)',
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Engenharia de Computação'),
        new OA\Property(property: 'slug', type: 'string', example: 'engenharia-de-computacao'),
        new OA\Property(property: 'path', type: 'string', example: '/cursos/engenharia-de-computacao'),
        new OA\Property(property: 'level', type: 'string', nullable: true, example: 'GRADUAÇÃO'),
        new OA\Property(property: 'image_url', description: 'Capa do card na vitrine `/cursos`.', type: 'string', nullable: true),
    ],
    type: 'object',
)]
final class CourseSummary {}
