<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;

/**
 * Leitura do destino de um link (ver App\Services\LinkTargetService):
 * de onde ele vem e para onde leva no site.
 *
 * @property string|null $url
 * @property string|null $file_path
 */
trait HasLinkTarget
{
    /** `file` quando aponta para um arquivo enviado, `url` quando para um endereço. */
    public function source(): ?string
    {
        return match (true) {
            $this->file_path !== null => 'file',
            $this->url !== null => 'url',
            default => null,
        };
    }

    /** Para onde o link leva no site. */
    public function href(): ?string
    {
        return $this->file_path ? Storage::disk('public')->url($this->file_path) : $this->url;
    }
}
