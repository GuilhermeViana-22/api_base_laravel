<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** Texto de abertura da vitrine `/cursos`. */
class CourseLandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return ['description' => 'texto da vitrine'];
    }
}
