<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Usuário como a listagem do painel recebe.
 *
 * Só o necessário para a tabela: nada de senha, token ou código de
 * verificação — o que não está aqui não sai do servidor.
 */
#[OA\Schema(
    schema: 'AdminUser',
    title: 'Usuário (painel)',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 7),
        new OA\Property(property: 'name', type: 'string', example: 'Maria Souza'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'maria@exemplo.com'),
        new OA\Property(property: 'polo', type: 'string', nullable: true, example: 'Santos'),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive', 'dismissed', 'on_vacation'], example: 'active'),
        new OA\Property(property: 'email_verified', type: 'boolean', example: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(
            property: 'role',
            description: 'Papel no painel. Nulo em quem só tem cadastro no site e não entra no CMS.',
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 3),
                new OA\Property(property: 'name', type: 'string', example: 'Editor de Notícias'),
                new OA\Property(property: 'slug', type: 'string', example: 'editor-de-noticias'),
                new OA\Property(property: 'is_master', type: 'boolean', example: false),
            ],
            type: 'object',
            nullable: true,
        ),
        new OA\Property(property: 'is_me', description: 'É a conta de quem fez a requisição. A tabela usa para desabilitar a troca de situação.', type: 'boolean', example: false),
    ],
    type: 'object',
)]
final class AdminUser {}
