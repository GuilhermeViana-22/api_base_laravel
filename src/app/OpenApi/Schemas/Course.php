<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Curso com página própria no site.
 *
 * `description` e `content` são HTML do editor rico, já filtrado ao salvar, e
 * ficam um de cada lado da faixa de informações (nível, duração e polos).
 */
#[OA\Schema(
    schema: 'Course',
    title: 'Curso',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'active', type: 'boolean', example: true),
        new OA\Property(property: 'position', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Engenharia de Computação'),
        new OA\Property(property: 'slug', type: 'string', example: 'engenharia-de-computacao'),
        new OA\Property(property: 'path', description: 'Endereço da página no site.', type: 'string', example: '/cursos/engenharia-de-computacao'),
        new OA\Property(property: 'level', type: 'string', nullable: true, example: 'GRADUAÇÃO'),
        new OA\Property(property: 'duration', type: 'string', nullable: true, example: '5 ANOS'),
        new OA\Property(property: 'poles', description: 'Quantos polos ofertam o curso.', type: 'integer', nullable: true, example: 461),
        new OA\Property(property: 'description', description: 'Apresentação, exibida antes da faixa de informações.', type: 'string', nullable: true, example: '<p>Com duração de cinco anos, forma o profissional…</p>'),
        new OA\Property(property: 'content', description: 'Material do curso, exibido depois da faixa.', type: 'string', nullable: true, example: '<p><a href="…/PPC-BTI_2025.pdf">PPC 2025</a></p>'),
        new OA\Property(property: 'image_url', description: 'Capa do card na vitrine `/cursos`.', type: 'string', nullable: true),
    ],
    type: 'object',
)]
final class Course {}
