<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\VestibularBlock */
class VestibularBlockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'position' => $this->position,
            'spacing_top' => $this->spacing_top,
            'type' => $this->type,
            'text' => $this->text,
            'style' => $this->style,
            'html' => $this->html,
            'kind' => $this->kind,
            'underline' => $this->underline,
            'source' => $this->type === 'link' ? $this->source() : null,
            'url' => $this->url,
            'file_name' => $this->file_name,
            'href' => $this->type === 'link' ? $this->href() : null,
            'youtube_id' => $this->type === 'video' ? $this->youtubeId() : null,
        ];
    }
}
