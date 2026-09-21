<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Curso na coluna da esquerda da página de cursos: só o que o link precisa.
 * O HTML dos textos fica fora daqui — ele vem em `GET /cursos/{slug}`.
 *
 * @mixin \App\Models\Course
 */
class CourseSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'path' => $this->path(),
            'level' => $this->level,
            'image_url' => $this->imageUrl(),
        ];
    }
}
