<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Nova ordem do carrossel: a lista completa de ids, na ordem desejada.
 *
 * Mandar a lista inteira (em vez de "subir um") deixa a ordem final explícita
 * e evita duas pessoas embaralharem o carrossel ao mesmo tempo.
 */
class ReorderCarouselSlidesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:carousel_slides,id'],
        ];
    }

    public function attributes(): array
    {
        return ['ids' => 'ordem dos slides'];
    }
}
