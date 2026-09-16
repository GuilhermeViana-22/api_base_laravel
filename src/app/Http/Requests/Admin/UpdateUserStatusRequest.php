<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Troca da situação de um usuário (ativo, inativo, demitido, férias).
 *
 * É a única escrita que o painel faz sobre outra pessoa, por isso a regra
 * mais importante está aqui: ninguém muda a própria situação — sairia do ar
 * sozinho, e a decisão sobre uma conta é sempre de outra pessoa.
 */
class UpdateUserStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(UserStatus::class)],
        ];
    }

    public function attributes(): array
    {
        return ['status' => 'situação'];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $alvo = $this->route('user');

                if ($alvo && $this->user() && $alvo->is($this->user())) {
                    $validator->errors()->add('status', 'Você não pode alterar a sua própria situação.');
                }
            },
        ];
    }
}
