<?php

namespace App\Http\Middleware;

use App\Support\PanelResources;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Porteiro das rotas do painel: `->middleware('pode:posts,delete')`.
 *
 * Vem sempre depois do `auth:api`, aqui já existe usuário; o que se decide é
 * se o papel dele alcança aquele módulo e aquela ação. A resposta 403 segue o
 * formato `{ message, code }` dos erros de autenticação, que o `ApiError` do
 * front já sabe ler.
 */
class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $modulo, string $acao): Response
    {
        // Chave escrita errada na rota viraria permissão liberada em silêncio.
        abort_unless(PanelResources::has($modulo), 500, "Tela desconhecida: {$modulo}.");

        $usuario = $request->user();

        if (!$usuario?->pode($modulo, $acao)) {
            return response()->json([
                'message' => 'Você não tem permissão para esta ação.',
                'code' => 'forbidden',
                'module' => $modulo,
                'action' => $acao,
            ], 403);
        }

        return $next($request);
    }
}
