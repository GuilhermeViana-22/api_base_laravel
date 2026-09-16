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
 * @property string|null $label texto pequeno acima da faixa vermelha (opcional só em OPTIONAL_LABEL)
 * @property string $title texto da faixa vermelha
 * @property string|null $image_path caminho no disco `public`
 */
class Banner extends Model
{
    /** Banner do topo de /noticias (o rótulo também aparece em /noticias/{id}). */
    public const NEWS = 'noticias';

    /** Banner do topo de /vestibular. */
    public const VESTIBULAR = 'vestibular';

    /** Banner do topo de /provao-paulista. */
    public const PROVAO = 'provao-paulista';

    /** Banner do topo de /institucional. */
    public const INSTITUTIONAL = 'institucional';

    /** Chaves aceitas nas rotas `/banners/{key}`. */
    public const KEYS = [self::NEWS, self::VESTIBULAR, self::PROVAO, self::INSTITUTIONAL];

    /**
     * Banners em que o rótulo pode ficar vazio. Em notícias ele é obrigatório
     * porque também identifica a seção no topo de cada notícia.
     */
    public const OPTIONAL_LABEL = [self::VESTIBULAR, self::PROVAO, self::INSTITUTIONAL];

    /** Textos com que cada banner nasce, iguais aos do site original. */
    public const DEFAULTS = [
        self::NEWS => [
            'label' => 'Notícias UNIVESP',
            'title' => 'Fique por dentro do que acontece na universidade',
        ],
        self::VESTIBULAR => [
            'label' => 'Vestibular UNIVESP',
            'title' => 'Inscrições, editais e resultados do vestibular',
        ],
        self::PROVAO => [
            'label' => 'Provão Paulista',
            'title' => 'A sua vaga na universidade pública pelo Provão Paulista',
        ],
        self::INSTITUTIONAL => [
            'label' => 'Institucional',
            'title' => 'Conheça a UNIVESP',
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
