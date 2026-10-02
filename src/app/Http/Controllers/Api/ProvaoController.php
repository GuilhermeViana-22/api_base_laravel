<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProvaoPageResource;
use App\Models\ProvaoLink;
use App\Models\ProvaoPage;
use OpenApi\Attributes as OA;

/** Página /provao-paulista no site público (`/api/provao-paulista`). */
class ProvaoController extends Controller
{
    #[OA\Get(
        path: '/provao-paulista',
        summary: 'Conteúdo da página /provao-paulista',
        description: 'Título, texto (com o cronograma), o card de matrícula e os links dele, na ordem do painel. '
            .'Link ainda sem endereço nem arquivo não vem.',
        tags: ['Site · Provão Paulista'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'A página.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/ProvaoPage'),
                ], type: 'object'),
            ),
        ],
    )]
    public function show(): ProvaoPageResource
    {
        return new ProvaoPageResource(ProvaoPage::current(), ProvaoLink::withTarget()->ordered()->get());
    }
}
