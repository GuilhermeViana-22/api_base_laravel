<?php

namespace App\Http\Resources;

use App\Models\SectionPage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Página de seção completa: a do painel e a que o site abre
 * (`/secoes/{secao}/paginas/{slug}`).
 *
 * @mixin SectionPage
 */
class SectionPageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'section' => $this->section,
            'slug' => $this->slug,
            'path' => $this->path(),
            'label' => $this->label,
            'title' => $this->title,
            'content' => $this->content,
            'position' => $this->position,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
