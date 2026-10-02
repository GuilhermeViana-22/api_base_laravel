<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Bloco de uma seção do vestibular: título, texto, link/arquivo ou vídeo. */
#[OA\Schema(
    schema: 'VestibularBlock',
    title: 'Bloco do vestibular',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'position', type: 'integer', example: 1),
        new OA\Property(property: 'spacing_top', description: 'Espaço acima, em px. Nulo: o automático do site.', type: 'integer', nullable: true),
        new OA\Property(property: 'type', type: 'string', enum: ['title', 'text', 'link', 'video']),
        new OA\Property(property: 'text', description: 'Título ou texto do link.', type: 'string', nullable: true),
        new OA\Property(property: 'style', description: 'Estilo do título.', type: 'string', enum: ['default', 'large', 'highlight'], nullable: true),
        new OA\Property(property: 'html', description: 'HTML do editor rico (bloco de texto).', type: 'string', nullable: true),
        new OA\Property(property: 'kind', description: 'Ícone do link.', type: 'string', enum: ['link', 'video', 'pdf', 'document'], nullable: true),
        new OA\Property(property: 'underline', description: 'Link sublinhado.', type: 'boolean'),
        new OA\Property(property: 'source', description: 'Destino do link: endereço ou arquivo enviado.', type: 'string', enum: ['url', 'file'], nullable: true),
        new OA\Property(property: 'url', description: 'Endereço do link ou URL do YouTube do vídeo.', type: 'string', nullable: true),
        new OA\Property(property: 'file_name', description: 'Nome original do arquivo enviado.', type: 'string', nullable: true),
        new OA\Property(property: 'href', description: 'Para onde o link leva (arquivo ou endereço).', type: 'string', nullable: true),
        new OA\Property(property: 'youtube_id', description: 'Id do vídeo, para o embed.', type: 'string', nullable: true, example: 'epKJlEc9lug'),
    ],
    type: 'object',
)]
final class VestibularBlock {}
