<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\SiteRoutes;
use App\Support\SectionPages;
use Illuminate\Http\JsonResponse;

/**
 * Rotas públicas do site (`/api/site/rotas`).
 *
 * Abastece o select "para onde o botão leva" do painel. Vêm agrupadas
 * (Principais, Institucional, Pesquisa, Transparência) porque a lista é longa
 * — as páginas internas saem de SectionPages, então a resposta acompanha
 * sozinha qualquer página nova.
 */
class SiteRouteController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => SiteRoutes::grouped()]);
    }
}
