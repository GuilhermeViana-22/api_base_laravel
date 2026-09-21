<?php

namespace App\Models;

use App\Enums\PermissionAction;
use App\Support\PanelResources;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Papel do painel: um nome e a matriz de permissões que ele concede.
 *
 * `abilities` é um mapa módulo => ações (`{"posts": ["view", "update"]}`). O
 * master ignora a matriz: `pode()` responde sempre `true`, e é por isso que ele
 * continua valendo quando um módulo novo aparece no catálogo.
 *
 * @property string $name
 * @property string $slug
 * @property array<string, array<int, string>> $abilities
 * @property bool $is_master
 * @property bool $locked papel de sistema: a tela não edita nem exclui
 */
class Role extends Model
{
    use HasFactory;

    /** Papel de sistema criado na migration; serve de referência no código. */
    public const MASTER = 'master';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'abilities',
        'is_master',
        'locked',
    ];

    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'is_master' => 'boolean',
            'locked' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function pode(string $modulo, PermissionAction|string $acao): bool
    {
        if ($this->is_master) {
            return true;
        }

        $acao = $acao instanceof PermissionAction ? $acao->value : $acao;

        return in_array($acao, $this->abilities[$modulo] ?? [], true);
    }

    /**
     * A matriz limpa: só telas e ações do catálogo, sem repetição e com as
     * dependências resolvidas.
     *
     * "Acessar" entra em qualquer linha marcada, porque é a chave da tela, e
     * "Ver" entra junto de quem cria, edita ou exclui: não existe mexer no que
     * não se enxerga. Linha vazia continua vazia, é assim que se diz "esta
     * pessoa não vê esta funcionalidade".
     *
     * @param  array<string, array<int, string>>  $abilities
     * @return array<string, array<int, string>>
     */
    public static function sanitize(array $abilities): array
    {
        $limpa = [];

        foreach (PanelResources::keys() as $tela) {
            $acoes = array_values(array_intersect(
                PermissionAction::values(),
                array_map('strval', $abilities[$tela] ?? []),
            ));

            if (!$acoes) {
                continue;
            }

            $limpa[$tela] = self::comDependencias($acoes);
        }

        return $limpa;
    }

    /**
     * Acrescenta o que a marcação implica, na ordem das colunas.
     *
     * @param  array<int, string>  $acoes
     * @return array<int, string>
     */
    public static function comDependencias(array $acoes): array
    {
        if (!$acoes) {
            return [];
        }

        $acoes[] = PermissionAction::Access->value;

        if (array_intersect(PermissionAction::writes(), $acoes)) {
            $acoes[] = PermissionAction::View->value;
        }

        return array_values(array_intersect(PermissionAction::values(), array_unique($acoes)));
    }
}
