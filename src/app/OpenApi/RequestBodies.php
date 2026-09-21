<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Corpos de requisição que se repetem, declarados uma vez só.
 *
 * Toda imagem do painel sobe do mesmo jeito — `multipart/form-data` com o
 * campo `file` — e passa pela mesma validação (App\Http\Requests\Admin\ImageUploadRequest).
 *
 * `CourseUpdate` está aqui por outro motivo: PUT e PATCH do curso aceitam
 * exatamente os mesmos campos, e repeti-los nos dois atributos deixaria a
 * lista fácil de sair de sincronia.
 */
#[OA\RequestBody(
    request: 'ImageUpload',
    required: true,
    description: 'Envio da imagem em `multipart/form-data`.',
    content: new OA\MediaType(
        mediaType: 'multipart/form-data',
        schema: new OA\Schema(
            required: ['file'],
            properties: [
                new OA\Property(
                    property: 'file',
                    description: 'JPEG, PNG, WebP ou GIF, até 5 MB.',
                    type: 'string',
                    format: 'binary',
                ),
            ],
            type: 'object',
        ),
    ),
)]
#[OA\RequestBody(
    request: 'CourseUpdate',
    required: true,
    description: 'Só os campos presentes no corpo são alterados.',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'name', type: 'string', maxLength: 255, minLength: 3, example: 'Engenharia de Computação'),
            new OA\Property(property: 'slug', description: 'Endereço da página (`/cursos/{slug}`). Em branco, sai do nome.', type: 'string', maxLength: 255, minLength: 3, example: 'engenharia-de-computacao'),
            new OA\Property(property: 'level', type: 'string', maxLength: 60, nullable: true, example: 'GRADUAÇÃO'),
            new OA\Property(property: 'duration', type: 'string', maxLength: 60, nullable: true, example: '5 ANOS'),
            new OA\Property(property: 'poles', description: 'Quantos polos ofertam o curso.', type: 'integer', minimum: 0, nullable: true, example: 461),
            new OA\Property(property: 'description', description: 'HTML do editor rico, exibido antes da faixa de informações.', type: 'string', nullable: true),
            new OA\Property(property: 'content', description: 'HTML do editor rico com o material do curso, exibido depois da faixa.', type: 'string', nullable: true),
            new OA\Property(property: 'position', description: 'Ordem no menu.', type: 'integer', minimum: 0, nullable: true),
            new OA\Property(property: 'active', type: 'boolean', example: true),
        ],
        type: 'object',
    ),
)]
final class RequestBodies {}
