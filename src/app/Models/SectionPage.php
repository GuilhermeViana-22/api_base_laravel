<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Página interna de uma seção do site (`/institucional/{slug}`, ...).
 *
 * `label` é o texto do menu e `title` o título da página; costumam ser iguais,
 * mas um rótulo curto no menu ("PDI") pode abrir uma página de título longo.
 * `content` é HTML do editor rico, já filtrado pelo ContentSanitizer.
 *
 * O `slug` não muda depois de criado: é a URL divulgada e a chave da
 * permissão da página (`pages.<secao>.<slug>`).
 *
 * @property int $id
 * @property string $section
 * @property string $slug
 * @property string $label
 * @property string $title
 * @property string|null $content
 * @property int $position
 */
class SectionPage extends Model
{
    protected $fillable = [
        'section',
        'slug',
        'label',
        'title',
        'content',
        'position',
    ];

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    public function scopeInSection(Builder $query, string $section): Builder
    {
        return $query->where('section', $section);
    }

    /** Ordem do menu. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }

    /** Caminho da página no site, o mesmo que o menu aponta. */
    public function path(): string
    {
        return "/{$this->section}/{$this->slug}";
    }

    /** Chave da página na árvore de permissões (PanelResources). */
    public function permissionKey(): string
    {
        return "pages.{$this->section}.{$this->slug}";
    }
}
