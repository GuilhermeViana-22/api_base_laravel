<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Seção do vestibular com os blocos já carregados por quem montou: o site
 * carrega só os visíveis, o painel carrega todos.
 *
 * @mixin \App\Models\VestibularSection
 */
class VestibularSectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'position' => $this->position,
            'spacing_top' => $this->spacing_top,
            'name' => $this->name,
            'divider' => $this->divider,
            'blocks' => VestibularBlockResource::collection($this->whenLoaded('blocks')),
        ];
    }
}
