<?php

namespace App\Http\Resources;

use App\Models\ProvaoLink;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * A página /provao-paulista com os links do card.
 *
 * Quem monta escolhe os links: o site só recebe os que têm destino, e o
 * painel recebe todos (inclusive os que ainda esperam endereço ou arquivo).
 *
 * @mixin \App\Models\ProvaoPage
 */
class ProvaoPageResource extends JsonResource
{
    /** @param  Collection<int, ProvaoLink>  $links */
    public function __construct($resource, private readonly Collection $links)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'title' => $this->title,
            'content' => $this->content,
            'card_title' => $this->card_title,
            'card_content' => $this->card_content,
            'links' => ProvaoLinkResource::collection($this->links),
        ];
    }
}
