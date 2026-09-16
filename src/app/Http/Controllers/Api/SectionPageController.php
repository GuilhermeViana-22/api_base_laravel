<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\SectionPages;
use Illuminate\Http\JsonResponse;

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
    public function index(string $secao): JsonResponse
    {
        return response()->json(['data' => SectionPages::for($secao)]);
    }
}
