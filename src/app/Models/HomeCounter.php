<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Número do bloco de contadores da página inicial
 * (ex.: 392 "Municípios no Estado de São Paulo").
 *
 * @property bool $active
 * @property int $position
 * @property string $label
 * @property int $value
 * @property string|null $suffix complemento depois do número ("mil", "%")
 */
class HomeCounter extends Model
{
    use HasFactory;

    protected $fillable = ['active', 'position', 'label', 'value', 'suffix'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'position' => 'integer',
            'value' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /** Ordem do painel; o id desempata contadores na mesma posição. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }
}
