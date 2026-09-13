<?php

namespace App\Http\Requests\Admin;

/**
 * PUT/PATCH aceitam envio parcial: só os campos presentes são validados
 * e alterados (ex.: `{ "featured": true }` pelo atalho da tabela).
 */
class UpdatePostRequest extends StorePostRequest
{
    public function rules(): array
    {
        return collect(parent::rules())
            ->map(fn (array $rules) => ['sometimes', ...array_diff($rules, ['required', 'present'])])
            ->all();
    }
}
