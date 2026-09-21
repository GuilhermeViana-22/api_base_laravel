<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Papel do painel e a matriz de permissões que ele concede. */
#[OA\Schema(
    schema: 'Role',
    title: 'Papel',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 3),
        new OA\Property(property: 'name', type: 'string', example: 'Editor de Notícias'),
        new OA\Property(property: 'slug', type: 'string', example: 'editor-de-noticias'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Publica e edita notícias, sem excluir.'),
        new OA\Property(
            property: 'abilities',
            description: 'Matriz módulo => ações. Vazia no Master, que ignora a matriz e pode tudo.',
            type: 'object',
            example: ['posts' => ['view', 'create', 'update'], 'home' => ['view']],
        ),
        new OA\Property(property: 'is_master', description: 'Ignora a matriz: acesso total ao painel.', type: 'boolean', example: false),
        new OA\Property(property: 'locked', description: 'Papel de sistema: a tela não edita nem exclui.', type: 'boolean', example: false),
        new OA\Property(property: 'users_count', description: 'Quantas contas usam este papel.', type: 'integer', example: 5),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
    type: 'object',
)]
final class Role {}
