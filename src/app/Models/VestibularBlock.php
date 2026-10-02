<?php

namespace App\Models;

use App\Models\Concerns\HasLinkTarget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bloco de uma seção do vestibular. O `type` diz quais colunas valem:
 *
 * - `title`: `text` e `style` (default, large ou highlight, o vermelho).
 * - `text`: `html` (editor rico, já filtrado pelo ContentSanitizer).
 * - `link`: `text`, `kind` (ícone), `underline` e o destino: `url` ou
 *   arquivo enviado (`file_path`), nunca os dois (LinkTargetService).
 * - `video`: `url` do YouTube, exibido embutido.
 *
 * @property int $id
 * @property int $section_id
 * @property int $position
 * @property string $type
 * @property string|null $text
 * @property string|null $style
 * @property string|null $html
 * @property string|null $kind
 * @property string|null $url
 * @property string|null $file_path
 * @property string|null $file_name
 * @property bool $underline
 * @property int|null $spacing_top espaço acima, em px (nulo = automático)
 */
class VestibularBlock extends Model
{
    use HasLinkTarget;

    public const TYPES = ['title', 'text', 'link', 'video'];

    public const TITLE_STYLES = ['default', 'large', 'highlight'];

    protected $fillable = ['position', 'type', 'text', 'style', 'html', 'kind', 'url', 'underline', 'spacing_top'];

    protected function casts(): array
    {
        return ['position' => 'integer', 'underline' => 'boolean', 'spacing_top' => 'integer'];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(VestibularSection::class, 'section_id');
    }

    /**
     * O que o site mostra: link sem destino e vídeo sem URL ficam de fora
     * (aparecem só no painel, esperando ser completados).
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->whereIn('type', ['title', 'text'])
            ->orWhere(fn (Builder $l) => $l->where('type', 'link')
                ->where(fn (Builder $d) => $d->whereNotNull('url')->orWhereNotNull('file_path')))
            ->orWhere(fn (Builder $v) => $v->where('type', 'video')->whereNotNull('url')));
    }

    /** Id do vídeo do YouTube, para o embed (`youtu.be/ID`, `watch?v=ID`, `live/ID`, `embed/ID`, `shorts/ID`). */
    public function youtubeId(): ?string
    {
        return self::youtubeIdFrom($this->url);
    }

    public static function youtubeIdFrom(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $caminho = (string) parse_url($url, PHP_URL_PATH);

        if (preg_match('/(^|\.)youtu\.be$/', $host)) {
            $id = trim($caminho, '/');
        } elseif (preg_match('/(^|\.)youtube(-nocookie)?\.com$/', $host)) {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            $id = $query['v'] ?? (preg_match('~^/(embed|live|shorts)/([^/?]+)~', $caminho, $m) ? $m[2] : null);
        } else {
            return null;
        }

        return $id && preg_match('/^[\w-]{6,20}$/', $id) ? $id : null;
    }
}
