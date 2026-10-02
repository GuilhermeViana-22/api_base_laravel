<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** Textos da página /provao-paulista; só os campos enviados mudam. */
class ProvaoPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'content' => ['sometimes', 'nullable', 'string'],
            'card_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'card_content' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'título',
            'content' => 'conteúdo',
            'card_title' => 'título do card',
            'card_content' => 'texto do card',
        ];
    }
}
