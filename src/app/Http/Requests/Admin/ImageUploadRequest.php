<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ImageUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 5 MB, mesmos formatos que o front aceita no seletor.
            'file' => ['required', 'image', 'mimes:jpeg,png,webp,gif', 'max:5120'],
        ];
    }

    public function attributes(): array
    {
        return ['file' => 'imagem'];
    }
}
