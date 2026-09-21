<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

/**
 * Banner (hero) de uma página do site.
 *
 * Não se cria nem se exclui banner: as chaves são fixas e o registro nasce com
 * um conteúdo padrão na primeira leitura. Só os textos e a foto mudam.
 */
#[OA\Schema(
    schema: 'Banner',
    title: 'Banner',
    properties: [
        new OA\Property(property: 'key', type: 'string', enum: ['noticias', 'vestibular', 'provao-paulista', 'institucional', 'cursos'], example: 'noticias'),
        new OA\Property(property: 'label', description: 'Linha curta acima da faixa vermelha.', type: 'string', nullable: true, example: 'Notícias UNIVESP'),
        new OA\Property(property: 'title', type: 'string', example: 'Fique por dentro do que acontece na universidade'),
        new OA\Property(property: 'image_url', description: 'Foto de fundo; `null` deixa o hero no fundo escuro.', type: 'string', nullable: true, example: 'http://localhost:8019/storage/banners/noticias.webp'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
final class Banner {}
