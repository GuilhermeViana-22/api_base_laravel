<?php

namespace App\Models;

use App\Support\SiteRoutes;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Uma página do site que o CMS resolveu esconder.
 *
 * Só existe linha para página com exceção: sem registro, a página está no ar.
 *
 * A coluna chama `is_visible` porque `visible` é nome reservado no Eloquent:
 * `$this->visible` dentro do model resolveria para a propriedade `$visible`
 * (lista de atributos serializáveis) em vez da coluna, e a página sumiria do
 * site sem ninguém ter pedido.
 * Esconder pode ser por tempo indeterminado (`visible = false`) ou dentro de
 * uma janela (`hidden_from`/`hidden_until`), a leitura por data espelha o
 * `Post::scopePublished()`, então a página volta sozinha quando o prazo passa,
 * sem job nenhum rodando.
 *
 * @property string $path
 * @property bool $is_visible
 * @property \Illuminate\Support\Carbon|null $hidden_from
 * @property \Illuminate\Support\Carbon|null $hidden_until
 */
class SitePage extends Model
{
    protected $fillable = [
        'path',
        'is_visible',
        'hidden_from',
        'hidden_until',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'hidden_from' => 'datetime',
            'hidden_until' => 'datetime',
        ];
    }

    /** A linha daquele caminho, criando na hora se for a primeira mexida. */
    public static function forPath(string $path): self
    {
        abort_unless(SiteRoutes::has($path), 404);

        return static::firstOrCreate(['path' => $path], ['is_visible' => true]);
    }

    /**
     * A página está fora do ar agora?
     *
     * Desligada é o caso simples. Com janela, vale o intervalo: começo vazio
     * conta como "desde sempre" e fim vazio, como "por tempo indeterminado".
     */
    public function estaOculta(?CarbonInterface $quando = null): bool
    {
        if (!$this->is_visible) {
            return true;
        }

        if (!$this->hidden_from && !$this->hidden_until) {
            return false;
        }

        $quando ??= now();

        return ($this->hidden_from === null || $quando->greaterThanOrEqualTo($this->hidden_from))
            && ($this->hidden_until === null || $quando->lessThanOrEqualTo($this->hidden_until));
    }

    /**
     * Os caminhos escondidos neste momento.
     *
     * É o que o site pede uma vez por visita para responder 404 em quem digita
     * a URL direto, e o que filtra o menu do cabeçalho.
     *
     * @return Collection<int, string>
     */
    public static function hiddenPaths(?CarbonInterface $quando = null): Collection
    {
        return static::query()
            ->where(fn (Builder $q) => $q->where('is_visible', false)
                ->orWhereNotNull('hidden_from')
                ->orWhereNotNull('hidden_until'))
            ->get()
            ->filter(fn (self $pagina) => $pagina->estaOculta($quando))
            ->pluck('path')
            ->values();
    }
}
