<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/** Link do card "Informações sobre a Matrícula": um endereço ou um arquivo enviado. */
#[OA\Schema(
    schema: 'ProvaoLink',
    title: 'Link do Provão Paulista',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'position', description: 'Ordem no card.', type: 'integer', example: 1),
        new OA\Property(property: 'label', description: 'Rótulo em negrito, acima do link.', type: 'string', nullable: true, example: 'Tutorial em PDF com o passo a passo para fazer sua matrícula:'),
        new OA\Property(property: 'text', description: 'Texto do link.', type: 'string', example: 'Veja o PDF'),
        new OA\Property(property: 'kind', description: 'Ícone no site.', type: 'string', enum: ['link', 'video', 'pdf', 'document'], example: 'pdf'),
        new OA\Property(property: 'source', description: 'De onde vem o destino; null quando ainda não tem.', type: 'string', enum: ['url', 'file'], nullable: true),
        new OA\Property(property: 'url', description: 'Endereço, quando o destino é um link.', type: 'string', nullable: true),
        new OA\Property(property: 'file_name', description: 'Nome original do arquivo enviado.', type: 'string', nullable: true, example: 'tutorial-matricula.pdf'),
        new OA\Property(property: 'href', description: 'Para onde o link leva (o arquivo ou o endereço).', type: 'string', nullable: true),
    ],
    type: 'object',
)]
final class ProvaoLink {}
