<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Course */
class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'active' => $this->active,
            'position' => $this->position,
            'name' => $this->name,
            'slug' => $this->slug,
            'path' => $this->path(),
            'level' => $this->level,
            'duration' => $this->duration,
            'poles' => $this->poles,
            'description' => $this->description,
            'content' => $this->content,
            'image_url' => $this->imageUrl(),
        ];
    }
}
