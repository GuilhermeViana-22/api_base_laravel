<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
| Rota base: aviso de que a API está no ar, em JSON (não há front aqui).
| Também confere o banco, sem expor detalhes do erro. 503 quando ele falha,
| para quem monitora enxergar o problema pelo status.
*/
Route::get('/', function () {
    try {
        DB::connection()->getPdo();
        $banco = 'conectado';
    } catch (\Throwable) {
        $banco = 'indisponível';
    }

    $ok = $banco === 'conectado';

    return response()->json([
        'status' => $ok ? 'ok' : 'erro',
        'mensagem' => $ok ? 'API UNIVESP funcionando.' : 'API no ar, mas sem acesso ao banco.',
        'banco' => $banco,
        'api' => url('/api'),
        'horario' => now()->toIso8601String(),
    ], $ok ? 200 : 503);
});
