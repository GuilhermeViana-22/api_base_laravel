<?php

namespace App\Http\Requests\Admin;

/** Slide novo: o texto é obrigatório; a imagem sobe depois, por rota própria. */
class StoreCarouselSlideRequest extends CarouselSlideRequest
{
    public function rules(): array
    {
        return $this->baseRules('required');
    }
}
