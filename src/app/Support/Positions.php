<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Ordem manual de listas do painel (coluna `position`).
 */
final class Positions
{
    /**
     * Regrava as posições na ordem dos ids recebidos, dentro do escopo dado
     * (ex.: os blocos de uma seção). Ids que não vieram vão para o fim,
     * mantendo a ordem relativa. Numa transação: ordem pela metade embaralha
     * o site.
     *
     * @param  array<int, int>  $ids
     */
    public static function reorder(Builder $escopo, array $ids): void
    {
        DB::transaction(function () use ($escopo, $ids) {
            foreach (array_values($ids) as $posicao => $id) {
                (clone $escopo)->whereKey($id)->update(['position' => $posicao + 1]);
            }

            (clone $escopo)->whereKeyNot($ids)
                ->orderBy('position')->orderBy('id')
                ->get()
                ->each(fn ($item, int $indice) => $item->update(['position' => count($ids) + $indice + 1]));
        });
    }

    /** A próxima posição livre no escopo (fim da lista). */
    public static function next(Builder $escopo): int
    {
        return (int) (clone $escopo)->max('position') + 1;
    }
}
