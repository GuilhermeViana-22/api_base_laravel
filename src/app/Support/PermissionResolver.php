<?php

namespace App\Support;

use App\Enums\PermissionAction;
use App\Models\User;

/**
 * Junta papel e exceções da pessoa numa resposta só: "pode ou não pode?".
 *
 * A conta é feita em duas camadas, da tela mais específica para a área mais
 * geral (`pages.institucional.historia` → `pages.institucional` → `pages`):
 *
 * 1. exceção daquela pessoa naquele nível, se existir, ela sempre vence;
 * 2. senão, o que o papel marcou naquele nível;
 * 3. senão, sobe um nível e repete. Não achou nada em nenhum: não pode.
 *
 * É por isso que uma exceção vazia (`[]`) também é uma decisão: ela tira a
 * pessoa daquela tela mesmo que o papel libere a área inteira.
 */
final class PermissionResolver
{
    public static function allows(User $usuario, string $chave, PermissionAction|string $acao): bool
    {
        if (!$usuario->temAcessoAoPainel()) {
            return false;
        }

        if ($usuario->role?->is_master) {
            return true;
        }

        $acao = $acao instanceof PermissionAction ? $acao->value : $acao;
        $excecoes = $usuario->abilities ?? [];
        $doPapel = $usuario->role?->abilities ?? [];

        foreach (PanelResources::ancestry($chave) as $nivel) {
            if (array_key_exists($nivel, $excecoes)) {
                return in_array($acao, (array) $excecoes[$nivel], true);
            }

            if (array_key_exists($nivel, $doPapel)) {
                return in_array($acao, (array) $doPapel[$nivel], true);
            }
        }

        return false;
    }

    /**
     * O mapa pronto de tudo o que a pessoa pode, chave a chave.
     *
     * Vai inteiro para o painel no login e em `/auth/me`: com ele o front
     * decide menu, rotas e botões sem perguntar nada de novo à API.
     *
     * @return array<string, array<int, string>>
     */
    public static function effective(User $usuario): array
    {
        if (!$usuario->temAcessoAoPainel()) {
            return [];
        }

        $mapa = [];

        foreach (PanelResources::keys() as $chave) {
            $acoes = array_values(array_filter(
                PermissionAction::values(),
                fn (string $acao) => self::allows($usuario, $chave, $acao),
            ));

            if ($acoes) {
                $mapa[$chave] = $acoes;
            }
        }

        return $mapa;
    }

    /**
     * Quantas telas a pessoa tem marcadas de um jeito diferente do papel.
     *
     * É o "2 exceções em relação ao papel Editor" que a tela mostra.
     */
    public static function exceptionCount(User $usuario): int
    {
        return count($usuario->abilities ?? []);
    }
}
