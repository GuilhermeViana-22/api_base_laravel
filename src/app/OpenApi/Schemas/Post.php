<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Notícia completa, como o painel usa.
 *
 * Na listagem (`GET /admin/posts`) o `content` fica de fora para não pesar a
 * resposta; no detalhe, na criação e na edição ele vem inteiro. O `author` só
 * aparece nas rotas que carregam a relação.
 */
#[OA\Schema(
    schema: 'Post',
    title: 'Notícia (painel)',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 42),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published'], example: 'published'),
        new OA\Property(property: 'title', type: 'string', example: 'Univesp abre inscrições para o vestibular 2027'),
        new OA\Property(property: 'subtitle', type: 'string', nullable: true, example: 'São 15 mil vagas em 10 cursos de graduação'),
        new OA\Property(property: 'content', description: 'HTML do corpo do texto. Ausente na listagem.', type: 'string', nullable: true, example: '<p>As inscrições vão de...</p>'),
        new OA\Property(property: 'image_url', description: 'Foto de capa já com a URL montada.', type: 'string', nullable: true, example: 'http://localhost:8019/storage/posts/capas/capa.webp'),
        new OA\Property(property: 'image_credit', type: 'string', nullable: true, example: 'Divulgação/Univesp'),
        new OA\Property(property: 'image_caption', type: 'string', nullable: true, example: 'Alunos no polo de Santos'),
        new OA\Property(property: 'featured', description: 'Destaque da página inicial (no máximo 2).', type: 'boolean', example: false),
        new OA\Property(property: 'published_at', description: 'Data no futuro significa notícia agendada.', type: 'string', format: 'date-time', nullable: true, example: '2026-09-14T09:00:00.000000Z'),
        new OA\Property(property: 'author', ref: '#/components/schemas/PostAuthor'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
final class Post {}
