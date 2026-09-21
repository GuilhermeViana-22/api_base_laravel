<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Nova ordem dos cursos: a lista completa de ids, na ordem desejada.
 *
 * É a mesma ordem do menu "Cursos" do cabeçalho e da coluna da esquerda da
 * página de curso. Mandar a lista inteira (em vez de "subir um") deixa a
 * ordem final explícita e evita duas pessoas embaralharem o menu ao mesmo
 * tempo.
 */
class ReorderCoursesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:courses,id'],
        ];
    }

    public function attributes(): array
    {
        return ['ids' => 'ordem dos cursos'];
    }
}
