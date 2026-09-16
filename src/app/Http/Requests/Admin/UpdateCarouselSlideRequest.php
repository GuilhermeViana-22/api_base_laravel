<?php

namespace App\Http\Requests\Admin;

/** Edição do slide: aceita envio parcial (PATCH), campo a campo. */
class UpdateCarouselSlideRequest extends CarouselSlideRequest
{
    public function rules(): array
    {
        return $this->baseRules('sometimes');
    }
}
