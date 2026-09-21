<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\SectionPages;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Relação das páginas de uma seção (`/api/secoes/{secao}/paginas`).
 *
 * Leitura pública e sem banco: as listas são fixas (SectionPages::PAGES) e,
 * por ora, cada página tem só slug e nome. O conteúdo vem depois. Seção fora
 * da lista nem chega aqui: a rota só aceita SectionPages::sections().
 */
class SectionPageController extends Controller
{
    /** GET /api/secoes/{secao}/paginas */
    #[OA\Get(
        path: '/secoes/{secao}/paginas',
        summary: 'Páginas internas de uma seção',
        description: 'Lista fixa e sem banco: por ora cada página tem só slug e nome — o conteúdo vem depois. '
            .'Seção fora da lista nem chega ao controller: a rota só aceita as três abaixo.',
        tags: ['Site · Navegação'],
        parameters: [
            new OA\Parameter(
                name: 'secao',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', enum: ['institucional', 'pesquisa', 'transparencia'], example: 'institucional'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'As páginas da seção, na ordem do menu.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/SectionPage')),
                ], type: 'object'),
            ),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ],
    )]
    public function index(string $secao): JsonResponse
    {
        return response()->json(['data' => SectionPages::for($secao)]);
    }
}
