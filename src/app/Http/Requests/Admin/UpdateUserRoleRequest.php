<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Dá, troca ou tira o acesso de alguém ao painel (`role_id`).
 *
 * `role_id: null` remove o acesso sem apagar a conta, a pessoa continua
 * cadastrada no site. As duas travas daqui existem para o painel não ficar
 * sem dono: ninguém mexe no próprio papel e o último Master não pode ser
 * rebaixado.
 */
class UpdateUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role_id' => ['present', 'nullable', 'integer', 'exists:roles,id'],
        ];
    }

    public function attributes(): array
    {
        return ['role_id' => 'papel'];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $alvo = $this->route('user');

                if ($alvo && $this->user() && $alvo->is($this->user())) {
                    $validator->errors()->add('role_id', 'Você não pode alterar o seu próprio papel.');

                    return;
                }

                if ($alvo && $this->tiraOUltimoMaster($alvo)) {
                    $validator->errors()->add('role_id', 'Este é o único Master do painel. Promova outra pessoa antes.');
                }
            },
        ];
    }

    private function tiraOUltimoMaster($alvo): bool
    {
        $master = Role::where('slug', Role::MASTER)->first();

        if (!$master || $alvo->role_id !== $master->id) {
            return false;
        }

        return (int) $this->input('role_id') !== $master->id
            && $master->users()->count() === 1;
    }
}
