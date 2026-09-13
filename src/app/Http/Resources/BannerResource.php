<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Banner como o site e o painel recebem.
 *
 * @mixin \App\Models\Banner
 */
class BannerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'title' => $this->title,
            'image_url' => $this->imageUrl(),
            'updated_at' => $this->updated_at,
        ];
    }
}
