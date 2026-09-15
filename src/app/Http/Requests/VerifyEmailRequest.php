<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesEmail;
use Illuminate\Foundation\Http\FormRequest;

class VerifyEmailRequest extends FormRequest
{
    use NormalizesEmail;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $length = $this->codeLength();

        return [
            'email' => ['required', 'string', 'email', 'exists:users,email'],
            'code' => ['required', 'string', "digits:{$length}"],
        ];
    }

    public function messages(): array
    {
        $length = $this->codeLength();

        return [
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'email.exists' => 'Não encontramos um cadastro com este e-mail.',
            'code.required' => 'Digite o código enviado para o seu e-mail.',
            'code.digits' => "O código tem {$length} números.",
        ];
    }

    private function codeLength(): int
    {
        return (int) config('auth.verification.code_length');
    }
}
