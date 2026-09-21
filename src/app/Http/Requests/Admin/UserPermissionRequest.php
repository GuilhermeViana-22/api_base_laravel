<?php

namespace App\Http\Requests\Admin;

use App\Enums\PermissionAction;
use App\Models\Role;
use App\Support\PanelResources;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Papel e exceções de uma pessoa, como o modal "Gerenciar permissões" envia.
 *
 * As exceções chegam inteiras e substituem as anteriores. Lista vazia numa
 * tela é uma escolha ("não enxerga"), por isso `overrides` aceita arrays
 * vazios, o que não pode é chave fora do catálogo ou ação inventada.
 *
 * As duas travas são as mesmas da troca de papel: ninguém mexe nas próprias
 * permissões e o último Master não é rebaixado, senão o painel fica sem dono.
 */
class UserPermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role_id' => ['present', 'nullable', 'integer', 'exists:roles,id'],
            'overrides' => ['sometimes', 'array'],
            'overrides.*' => ['array'],
            'overrides.*.*' => [Rule::enum(PermissionAction::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'role_id' => 'papel',
            'overrides' => 'exceções',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $alvo = $this->route('user');

                if ($alvo && $this->user() && $alvo->is($this->user())) {
                    $validator->errors()->add('role_id', 'Você não pode alterar as suas próprias permissões.');

                    return;
                }

                foreach (array_keys($this->input('overrides', [])) as $chave) {
                    if (!PanelResources::has((string) $chave)) {
                        $validator->errors()->add('overrides', "A tela \"{$chave}\" não existe.");
                    }
                }

                if ($alvo && $this->tiraOUltimoMaster($alvo)) {
                    $validator->errors()->add('role_id', 'Este é o único Master do painel. Promova outra pessoa antes.');
                }
            },
        ];
    }

    /**
     * As exceções limpas: só telas do catálogo, só ações válidas, e as
     * dependências resolvidas (Role::comDependencias). A tela vazia continua
     * vazia, é a forma de dizer "esta pessoa não vê esta funcionalidade".
     *
     * @return array<string, array<int, string>>
     */
    public function overrides(): array
    {
        $limpas = [];

        foreach ($this->validated('overrides', []) as $chave => $acoes) {
            if (!PanelResources::has((string) $chave)) {
                continue;
            }

            $acoes = array_values(array_intersect(PermissionAction::values(), array_map('strval', (array) $acoes)));

            $limpas[$chave] = Role::comDependencias($acoes);
        }

        return $limpas;
    }

    private function tiraOUltimoMaster($alvo): bool
    {
        $master = Role::where('slug', Role::MASTER)->first();

        if (!$master || $alvo->role_id !== $master->id) {
            return false;
        }

        return (int) $this->input('role_id') !== $master->id && $master->users()->count() === 1;
    }
}
