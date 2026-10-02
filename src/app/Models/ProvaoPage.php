<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Texto da página /provao-paulista (coluna da direita).
 *
 * Existe um registro só: a primeira leitura cria o registro vazio, como a
 * vitrine de cursos. Os links do card ficam em ProvaoLink.
 *
 * @property string|null $title título vermelho do topo
 * @property string|null $content HTML do editor rico: texto, cronograma e aviso
 * @property string|null $card_title título do card "Informações sobre a Matrícula"
 * @property string|null $card_content HTML do editor rico, acima dos links do card
 */
class ProvaoPage extends Model
{
    protected $fillable = ['title', 'content', 'card_title', 'card_content'];

    /** A página; a primeira leitura cria o registro se ainda não existir. */
    public static function current(): self
    {
        return static::query()->first() ?? static::create();
    }
}
