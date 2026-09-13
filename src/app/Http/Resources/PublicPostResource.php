<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Notícia como o site público vê: sem status, autor ou datas de edição.
 *
 * - Listagens (grade, Últimas Notícias, destaques): trazem `excerpt`, sem `content`.
 * - Página da notícia (`show`): traz `content` completo.
 *
 * @mixin \App\Models\Post
 */
class PublicPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isShow = $request->route()?->getActionMethod() === 'show';

        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'excerpt' => $this->excerpt(),
            'content' => $this->when($isShow, $this->content),
            'image_url' => $this->imageUrl(),
            'image_credit' => $this->image_credit,
            'image_caption' => $this->image_caption,
            'featured' => $this->featured,
            'published_at' => $this->published_at,
        ];
    }
}
