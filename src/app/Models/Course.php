<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Curso com página própria no site (`/cursos/{slug}`).
 *
 * O `slug` é o endereço da página e o que o menu "Cursos" do cabeçalho usa:
 * um curso novo e ligado vira rota e item de menu sozinho (ver SiteMenu).
 *
 * Os textos são HTML do editor rico, já filtrado pelo ContentSanitizer, e
 * ficam um de cada lado da faixa de informações da página: `description` é a
 * apresentação e `content` traz o material (matriz curricular, PPCs, links).
 *
 * @property int $id
 * @property bool $active
 * @property int $position
 * @property string $name
 * @property string $slug
 * @property string|null $level
 * @property string|null $duration
 * @property int|null $poles
 * @property string|null $description
 * @property string|null $content
 * @property string|null $image_path capa da vitrine `/cursos`
 */
class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'active',
        'position',
        'name',
        'slug',
        'level',
        'duration',
        'poles',
        'description',
        'content',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'position' => 'integer',
            'poles' => 'integer',
        ];
    }

    /** Só os cursos que o site mostra. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /** Ordem do menu e da coluna da esquerda da página de curso. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }

    /** Caminho da página no site, o mesmo que o menu aponta. */
    public function path(): string
    {
        return "/cursos/{$this->slug}";
    }

    /** URL pública da capa, usada no card da vitrine. */
    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }
}
