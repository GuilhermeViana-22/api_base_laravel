<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesEmail;
use App\Support\SecuritySettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    use NormalizesEmail;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            // E-mail de conta ainda não confirmada pode se cadastrar de novo (recebe outro código).
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->whereNotNull('email_verified_at'),
            ],
            // A exigência vem de Configurações > Segurança (SecuritySettings).
            'password' => ['required', 'string', 'max:255', SecuritySettings::passwordRule(), 'confirmed'],
        ];
    }

    public function messages(): array
    {
        $password = SecuritySettings::passwordMessage();

        return [
            'name.required' => 'Informe seu nome.',
            'name.min' => 'O nome precisa ter pelo menos 3 letras.',
            'name.max' => 'O nome pode ter no máximo 255 caracteres.',
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'email.max' => 'O e-mail pode ter no máximo 255 caracteres.',
            'email.unique' => 'Já existe uma conta com este e-mail. Faça login.',
            'password.required' => 'Crie uma senha.',
            'password.min' => $password,
            'password.max' => 'A senha pode ter no máximo 255 caracteres.',
            'password.regex' => $password,
            'password.confirmed' => 'As senhas não conferem.',
        ];
    }
}
