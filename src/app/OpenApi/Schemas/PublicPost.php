<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Notícia como o site público vê: sem status, autor ou datas de edição.
 *
 * Nas listagens vem `excerpt` (as primeiras palavras) e não vem `content`; na
 * página da notícia (`GET /posts/{id}`) é o contrário.
 */
#[OA\Schema(
    schema: 'PublicPost',
    title: 'Notícia (site)',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 42),
        new OA\Property(property: 'title', type: 'string', example: 'Univesp abre inscrições para o vestibular 2027'),
        new OA\Property(property: 'subtitle', type: 'string', nullable: true, example: 'São 15 mil vagas em 10 cursos de graduação'),
        new OA\Property(property: 'excerpt', description: 'Resumo curto para o card da grade.', type: 'string', nullable: true, example: 'As inscrições vão de 10 de outubro a 5 de novembro...'),
        new OA\Property(property: 'content', description: 'HTML do corpo do texto. Só na página da notícia.', type: 'string', nullable: true),
        new OA\Property(property: 'image_url', type: 'string', nullable: true, example: 'http://localhost:8019/storage/posts/capas/capa.webp'),
        new OA\Property(property: 'image_credit', type: 'string', nullable: true, example: 'Divulgação/Univesp'),
        new OA\Property(property: 'image_caption', type: 'string', nullable: true, example: 'Alunos no polo de Santos'),
        new OA\Property(property: 'featured', type: 'boolean', example: true),
        new OA\Property(property: 'published_at', type: 'string', format: 'date-time', nullable: true),
    ],
    type: 'object',
)]
final class PublicPost {}
