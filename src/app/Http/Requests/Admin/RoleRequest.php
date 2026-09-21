<?php

namespace App\Http\Requests\Admin;

use App\Enums\PermissionAction;
use App\Models\Role;
use App\Support\PanelResources;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Criação e edição de um papel do painel.
 *
 * A matriz chega inteira (`abilities`), como a tela mostra: módulo => ações.
 * Só o que existe no catálogo passa, um módulo inventado aqui viraria uma
 * permissão que nenhuma rota confere.
 */
class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $papel = $this->route('role');
        $criando = $this->isMethod('POST');

        return [
            'name' => [$criando ? 'required' : 'sometimes', 'string', 'min:3', 'max:60'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'abilities' => [$criando ? 'required' : 'sometimes', 'array'],
            // "abilities.posts" => ["view", "update"]
            'abilities.*' => ['array'],
            'abilities.*.*' => [Rule::enum(PermissionAction::class)],
            'slug' => [
                'sometimes',
                'string',
                'max:60',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('roles', 'slug')->ignore($papel?->id),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'description' => 'descrição',
            'abilities' => 'permissões',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $papel = $this->route('role');

                // O Master é papel de sistema: mexer nele deixaria o painel sem dono.
                if ($papel instanceof Role && $papel->locked) {
                    $validator->errors()->add('name', 'O papel Master é do sistema e não pode ser alterado.');
                }

                foreach (array_keys($this->input('abilities', [])) as $modulo) {
                    if (!PanelResources::has((string) $modulo)) {
                        $validator->errors()->add('abilities', "A tela \"{$modulo}\" não existe.");
                    }
                }
            },
        ];
    }

    /** A matriz já limpa e com `view` onde faltava. */
    public function abilities(): array
    {
        return Role::sanitize($this->validated('abilities', []));
    }
}
