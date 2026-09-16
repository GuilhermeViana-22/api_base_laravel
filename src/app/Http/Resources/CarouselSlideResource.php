<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Slide como o painel recebe: tudo o que o formulário edita, mais a URL da
 * imagem já montada.
 *
 * @mixin \App\Models\CarouselSlide
 */
class CarouselSlideResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'active' => $this->active,
            'position' => $this->position,
            'title' => $this->title,
            'image_url' => $this->imageUrl(),
            'button_label' => $this->button_label,
            'button_route' => $this->button_route,
            'button_color' => $this->button_color,
            'button_text_color' => $this->button_text_color,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
