<?php

namespace App\Models;

use App\Models\Concerns\HasLinkTarget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Link do card "Informações sobre a Matrícula" em /provao-paulista.
 *
 * O destino é um endereço (`url`) ou um arquivo enviado no painel
 * (`file_path`), nunca os dois: quem grava um apaga o outro (ProvaoService).
 * O `kind` escolhe o ícone no site.
 *
 * @property int $id
 * @property int $position
 * @property string|null $label rótulo em negrito, acima do link
 * @property string $text texto do link
 * @property string $kind link|video|pdf|document
 * @property string|null $url
 * @property string|null $file_path
 * @property string|null $file_name nome original do arquivo enviado
 */
class ProvaoLink extends Model
{
    use HasLinkTarget;

    public const KINDS = ['link', 'video', 'pdf', 'document'];

    protected $fillable = ['position', 'label', 'text', 'kind', 'url'];

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }

    /** Só os que têm para onde ir: link sem destino não sai no site. */
    public function scopeWithTarget(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNotNull('file_path')->orWhereNotNull('url'));
    }
}
