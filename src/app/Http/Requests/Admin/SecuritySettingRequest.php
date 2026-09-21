<?php

namespace App\Http\Requests\Admin;

use App\Support\SecuritySettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Ajustes de segurança que a tela envia.
 *
 * O tempo de sessão é escolhido numa lista fechada: um número solto abriria
 * espaço para "5 minutos" (ninguém trabalha) ou "um ano" (token eterno).
 */
class SecuritySettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_timeout_minutes' => ['sometimes', 'integer', Rule::in(SecuritySettings::SESSION_TIMEOUTS)],
            'password_complexity' => ['sometimes', 'string', Rule::in(SecuritySettings::COMPLEXITIES)],
            'require_two_factor' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'session_timeout_minutes' => 'tempo de sessão',
            'password_complexity' => 'complexidade de senha',
            'require_two_factor' => 'autenticação em dois fatores',
        ];
    }
}
