<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bloco da página inicial, como painel e site recebem.
 *
 * @mixin \App\Models\HomeSection
 */
class HomeSectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->key,
            'active' => $this->active,
            'title' => $this->title,
            'description' => $this->description,
            'image_url' => $this->imageUrl(),
            'video_url' => $this->video_url,
            // Bloco sem botão nunca devolve botão, mesmo que haja sobra no banco
            // de antes de ele perder o botão.
            'button_label' => $this->when($this->supportsButton(), $this->button_label, null),
            'button_route' => $this->when($this->supportsButton(), $this->button_route, null),
            'supports_image' => $this->supportsImage(),
            'supports_button' => $this->supportsButton(),
            'updated_at' => $this->updated_at,
        ];
    }
}
