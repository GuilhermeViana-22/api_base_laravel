<?php

namespace App\Http\Resources;

use App\Support\SiteRoutes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Situação de uma página do site na tela de Configurações.
 *
 * `hidden_now` é o que vale para o visitante agora: pode ser `true` por a
 * página estar desligada ou por estar dentro da janela agendada.
 *
 * @mixin \App\Models\SitePage
 */
class SitePageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'path' => $this->path,
            'label' => $this->rotulo(),
            'visible' => $this->is_visible,
            'hidden_from' => $this->hidden_from,
            'hidden_until' => $this->hidden_until,
            'hidden_now' => $this->estaOculta(),
        ];
    }

    /** O nome que o painel mostra vem do catálogo, não do banco. */
    private function rotulo(): ?string
    {
        foreach (SiteRoutes::grouped() as $grupo) {
            foreach ($grupo['routes'] as $rota) {
                if ($rota['path'] === $this->path) {
                    return $rota['label'];
                }
            }
        }

        return null;
    }
}
