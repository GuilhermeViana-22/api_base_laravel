<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Seção da página /vestibular (em geral, um ano: "Vestibular 2024").
 *
 * O `name` é só para o painel; o que aparece no site são os blocos. A
 * divisória (`divider`) é a linha acima da seção: nenhuma, sólida ou
 * pontilhada, como no site da Univesp.
 *
 * @property int $id
 * @property int $position
 * @property string $name
 * @property string $divider none|solid|dotted
 * @property int|null $spacing_top espaço acima, em px (nulo = automático)
 */
class VestibularSection extends Model
{
    public const DIVIDERS = ['none', 'solid', 'dotted'];

    protected $fillable = ['position', 'name', 'divider', 'spacing_top'];

    protected function casts(): array
    {
        return ['position' => 'integer', 'spacing_top' => 'integer'];
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(VestibularBlock::class, 'section_id')->orderBy('position')->orderBy('id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }
}
