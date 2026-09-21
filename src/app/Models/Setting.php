<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Ajuste do CMS guardado por chave (`settings`).
 *
 * Quem lê e escreve são as classes de Support (SecuritySettings), que sabem o
 * formato e o padrão de cada chave. O model só guarda.
 *
 * @property string $key
 * @property mixed $value
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    /** O valor guardado, ou o padrão de quem perguntou. */
    public static function valor(string $chave, mixed $padrao = null): mixed
    {
        return static::find($chave)?->value ?? $padrao;
    }

    public static function guardar(string $chave, mixed $valor): void
    {
        static::updateOrCreate(['key' => $chave], ['value' => $valor]);
    }
}
