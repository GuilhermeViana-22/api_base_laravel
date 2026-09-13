<?php

namespace App\Models;

use App\Enums\PostStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Notícia do site.
 *
 * - Publicada: `status = published` e `published_at` já alcançado.
 * - Agendada: `status = published` com `published_at` no futuro.
 * - Destaque: `featured = true`; a página inicial mostra no máximo MAX_FEATURED.
 *
 * @property int $id
 * @property PostStatus $status
 * @property string $title
 * @property string|null $subtitle
 * @property string $content HTML do TinyMCE, já filtrado pelo ContentSanitizer
 * @property string|null $image_path caminho no disco `public`
 * @property bool $featured
 * @property \Illuminate\Support\Carbon|null $published_at
 */
class Post extends Model
{
    use HasFactory;

    /** Quantos destaques a página inicial exibe (e o máximo que pode existir). */
    public const MAX_FEATURED = 2;

    /** Palavras do trecho exibido nos cards da grade e das Últimas Notícias. */
    public const EXCERPT_WORDS = 10;

    protected $fillable = [
        'status',
        'title',
        'subtitle',
        'content',
        'image_credit',
        'image_caption',
        'featured',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /** Quem cadastrou a notícia no painel. */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Só o que o site público pode mostrar: publicado e com a data já alcançada. */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', PostStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /** URL pública da foto de capa, ou null quando a notícia está sem foto. */
    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    /**
     * Trecho do início do texto, sem HTML: "Primeiras dez palavras do texto...".
     *
     * As tags viram espaço (senão "</p><p>" colaria palavras de parágrafos
     * diferentes) e as entidades (&nbsp;, &eacute;) viram caracteres.
     */
    public function excerpt(int $words = self::EXCERPT_WORDS): string
    {
        $text = preg_replace('/<[^>]*>/', ' ', (string) $this->content);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', $text));

        return Str::words($text, $words, '...');
    }
}
