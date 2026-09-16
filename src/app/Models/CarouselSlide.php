<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Slide do carrossel da página inicial.
 *
 * @property bool $active
 * @property int $position
 * @property string $title
 * @property string|null $image_path caminho no disco `public`
 * @property string|null $button_label sem rótulo não há botão
 * @property string|null $button_route rota do site (App\Support\SiteRoutes)
 * @property string|null $button_color fundo do botão, em hexadecimal
 * @property string|null $button_text_color texto do botão, em hexadecimal
 */
class CarouselSlide extends Model
{
    use HasFactory;

    /** Cores com que o botão nasce (as do site: fundo escuro, texto branco). */
    public const DEFAULT_BUTTON_COLOR = '#172833';
    public const DEFAULT_BUTTON_TEXT_COLOR = '#FFFFFF';

    protected $fillable = [
        'active',
        'position',
        'title',
        'button_label',
        'button_route',
        'button_color',
        'button_text_color',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'position' => 'integer',
        ];
    }

    /** URL pública da imagem, ou null enquanto o slide não tem foto. */
    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    /** Só há botão quando existe rótulo e destino. */
    public function hasButton(): bool
    {
        return filled($this->button_label) && filled($this->button_route);
    }

    /** O site só mostra slide ativo e com imagem: sem foto não há o que exibir. */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('active', true)->whereNotNull('image_path');
    }

    /** Ordem do painel; o id desempata slides na mesma posição. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }
}
