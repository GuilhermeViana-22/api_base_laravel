<?php

namespace App\Http\Resources;

use App\Models\CarouselSlide;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Slide como o site recebe: só o que a tela desenha.
 *
 * O botão vem pronto (ou `null`), com as cores já resolvidas para o padrão
 * quando o painel não escolheu nenhuma — assim o site não precisa saber os
 * valores de fábrica.
 *
 * @mixin \App\Models\CarouselSlide
 */
class PublicCarouselSlideResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'image_url' => $this->imageUrl(),
            'button' => $this->when($this->hasButton(), fn () => [
                'label' => $this->button_label,
                'route' => $this->button_route,
                'color' => $this->button_color ?? CarouselSlide::DEFAULT_BUTTON_COLOR,
                'text_color' => $this->button_text_color ?? CarouselSlide::DEFAULT_BUTTON_TEXT_COLOR,
            ], null),
        ];
    }
}
