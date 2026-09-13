<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Notícia completa, como o painel usa.
 *
 * Na listagem do painel (`index`) o `content` fica de fora para não pesar;
 * no detalhe, criação e edição ele vem inteiro.
 *
 * @mixin \App\Models\Post
 */
class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'content' => $this->when(! $this->isCollection($request), $this->content),
            'image_url' => $this->imageUrl(),
            'image_credit' => $this->image_credit,
            'image_caption' => $this->image_caption,
            'featured' => $this->featured,
            'published_at' => $this->published_at,
            'author' => $this->whenLoaded('author', fn () => $this->author?->only('id', 'name')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /** Na listagem o corpo do texto só pesaria a resposta. */
    private function isCollection(Request $request): bool
    {
        return $request->route()?->getActionMethod() === 'index';
    }
}
