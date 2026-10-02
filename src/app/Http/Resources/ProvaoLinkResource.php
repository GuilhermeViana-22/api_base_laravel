<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ProvaoLink */
class ProvaoLinkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'position' => $this->position,
            'label' => $this->label,
            'text' => $this->text,
            'kind' => $this->kind,
            'source' => $this->source(),
            'url' => $this->url,
            'file_name' => $this->file_name,
            'href' => $this->href(),
        ];
    }
}
