<?php

namespace App\Http\Requests\Admin;

use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validação da notícia criada pelo painel.
 *
 * Qualquer pessoa logada no painel pode escrever (ainda não há papéis),
 * por isso `authorize` é sempre true: a rota já exige `auth:api`.
 */
class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(PostStatus::class)],
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'content' => ['present', 'nullable', 'string'],
            'image_credit' => ['nullable', 'string', 'max:150'],
            'image_caption' => ['nullable', 'string', 'max:255'],
            'featured' => ['boolean'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    /** Nomes dos campos nas mensagens de erro. */
    public function attributes(): array
    {
        return [
            'title' => 'título',
            'subtitle' => 'subtítulo',
            'content' => 'texto',
            'image_credit' => 'crédito da foto',
            'image_caption' => 'legenda da foto',
            'featured' => 'destaque',
            'published_at' => 'data de publicação',
        ];
    }

    /**
     * A página inicial mostra no máximo Post::MAX_FEATURED destaques: marcar
     * mais um exige tirar o destaque de outra notícia antes.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $post = $this->route('post');

                if (! $this->boolean('featured') || ($post instanceof Post && $post->featured)) {
                    return;
                }

                if (Post::where('featured', true)->count() >= Post::MAX_FEATURED) {
                    $validator->errors()->add(
                        'featured',
                        'A página inicial já tem '.Post::MAX_FEATURED.' destaques. Remova o destaque de outra notícia antes de marcar esta.',
                    );
                }
            },
        ];
    }
}
