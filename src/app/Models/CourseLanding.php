<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Texto de abertura da vitrine `/cursos` (não entra na página de um curso).
 *
 * Existe um registro só: a primeira leitura cria o texto padrão, como o banner.
 *
 * @property string|null $description HTML do editor rico
 */
class CourseLanding extends Model
{
    protected $fillable = ['description'];

    /** O texto da vitrine; a primeira leitura cria o registro se ainda não existir. */
    public static function current(): self
    {
        return static::query()->first() ?? static::create(['description' => null]);
    }
}
