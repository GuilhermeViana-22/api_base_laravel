<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\SiteMenu;
use App\Support\SiteRoutes;
use Illuminate\Http\JsonResponse;

/**
 * Rotas e menu do site público.
 *
 * - `/api/site/rotas`: todas as rotas, agrupadas, para o select "para onde o
 *   botão leva" do painel.
 * - `/api/site/menu`: o menu do cabeçalho do site, com os submenus já
 *   montados.
 *
 * As duas listas saem de SectionPages, então uma página nova de seção aparece
 * no painel e no site sem ninguém tocar no front.
 */
class SiteRouteController extends Controller
{
    /** GET /api/site/rotas */
    public function index(): JsonResponse
    {
        return response()->json(['data' => SiteRoutes::grouped()]);
    }

    /** GET /api/site/menu */
    public function menu(): JsonResponse
    {
        return response()->json(['data' => SiteMenu::tree()]);
    }
}
