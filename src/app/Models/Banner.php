<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Banner (hero) de uma página do site, editável pelo painel.
 *
 * Não se cria nem se exclui banner pela API: as chaves são fixas (KEYS) e o
 * registro nasce com os textos padrão (DEFAULTS) na primeira leitura.
 *
 * @property int $id
 * @property string $key
 * @property string $label texto pequeno acima da faixa vermelha
 * @property string $title texto da faixa vermelha
 * @property string|null $image_path caminho no disco `public`
 */
class Banner extends Model
{
    /** Banner do topo de /noticias (o rótulo também aparece em /noticias/{id}). */
    public const NEWS = 'noticias';

    /** Chaves aceitas nas rotas `/banners/{key}`. */
    public const KEYS = [self::NEWS];

    /** Textos com que cada banner nasce, iguais aos do site original. */
    public const DEFAULTS = [
        self::NEWS => [
            'label' => 'Notícias UNIVESP',
            'title' => 'Fique por dentro do que acontece na universidade',
        ],
    ];

    protected $fillable = ['key', 'label', 'title'];

    /** Banner da chave informada, criado com os textos padrão se ainda não existir. */
    public static function forKey(string $key): self
    {
        $banner = static::firstOrCreate(['key' => $key], self::DEFAULTS[$key]);

        // A criação é um detalhe interno da primeira leitura: sem isto o
        // JsonResource responderia 201 (Created) a um GET ou PUT.
        $banner->wasRecentlyCreated = false;

        return $banner;
    }

    /** URL pública da foto de fundo, ou null quando não há foto. */
    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }
}
