<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Bloco de chave fixa da página inicial.
 *
 * Não se cria nem se exclui bloco pela API: as chaves são fixas (KEYS) e o
 * registro nasce com o conteúdo padrão (DEFAULTS) na primeira leitura, como
 * acontece com os banners.
 *
 * @property string $key
 * @property bool $active
 * @property string|null $title
 * @property string|null $description HTML no vídeo, texto corrido nos demais
 * @property string|null $image_path caminho no disco `public`
 * @property string|null $video_url endereço do YouTube (só no bloco de vídeo)
 * @property string|null $button_label
 * @property string|null $button_route rota do site (App\Support\SiteRoutes)
 */
class HomeSection extends Model
{
    /** Vídeo de apresentação da Univesp, com texto em editor rico. */
    public const VIDEO = 'video';

    /** Mapa dos polos: imagem, texto e botão. */
    public const MAP = 'mapa';

    /** Manual do aluno: imagem, texto e botão. */
    public const MANUAL = 'manual';

    /** Transparência: texto e botão. */
    public const TRANSPARENCY = 'transparencia';

    /** Cabeçalho do bloco de depoimentos (a lista fica em Testimonial). */
    public const TESTIMONIALS = 'depoimentos';

    /** Chaves aceitas nas rotas `/home/sections/{key}`. */
    public const KEYS = [self::VIDEO, self::MAP, self::MANUAL, self::TRANSPARENCY, self::TESTIMONIALS];

    /** Blocos que têm imagem própria (o vídeo usa a capa do YouTube). */
    public const WITH_IMAGE = [self::MAP, self::MANUAL, self::TESTIMONIALS];

    /** Blocos sem botão: o de vídeo é só o player e o texto ao lado. */
    public const WITHOUT_BUTTON = [self::VIDEO];

    /** Conteúdo com que cada bloco nasce, igual ao que o site mostra hoje. */
    public const DEFAULTS = [
        self::VIDEO => [
            'title' => 'A Univesp',
            'description' => '<p>Criada em 2012 como Fundação, somos uma instituição de Ensino Superior mantida pelo Governo do Estado de São Paulo, vinculada à Secretaria de Ciência, Tecnologia e Inovação, com credenciamento como universidade pelo Conselho Estadual de Educação e pelo MEC.</p>',
            'video_url' => 'https://www.youtube.com/watch?v=kCJQ2VPTCqI',
        ],
        self::MAP => [
            'title' => 'Polos Univesp',
            'description' => 'Polos são as unidades acadêmicas e operacionais para o desenvolvimento de atividades presenciais relativas aos cursos oferecidos na modalidade a distância. Os locais contam com infraestrutura física e tecnológica, além de profissionais para apoio aos alunos.',
            'button_label' => 'Saiba mais',
            'button_route' => '/polo',
        ],
        self::MANUAL => [
            'title' => 'Manual do Aluno',
            'description' => 'Um guia que irá ajudar a esclarecer as principais dúvidas que você possa ter durante o curso.',
            'button_label' => 'Saiba mais',
            'button_route' => '/institucional',
        ],
        self::TRANSPARENCY => [
            'title' => 'Transparência',
            'description' => 'A Univesp, como Fundação mantida pelo Governo do Estado de São Paulo, disponibiliza todas as informações de sua administração por meio do Portal da Transparência Estadual.',
            'button_label' => 'Saiba mais',
            'button_route' => '/transparencia',
        ],
        self::TESTIMONIALS => [
            'title' => 'Depoimentos de ex-alunos Univesp',
            'description' => 'A UNIVESP me ajudou a alcançar um objetivo que eu não achava possível.',
        ],
    ];

    /** `key` entra aqui só para o firstOrCreate de forKey(); o PATCH nunca a envia. */
    protected $fillable = [
        'key',
        'active',
        'title',
        'description',
        'video_url',
        'button_label',
        'button_route',
    ];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    /** Bloco da chave informada, criado com o conteúdo padrão se ainda não existir. */
    public static function forKey(string $key): self
    {
        $section = static::firstOrCreate(['key' => $key], self::DEFAULTS[$key] ?? []);

        // Recém-criado, o modelo em memória só tem o que foi escrito: sem
        // reler, colunas com valor padrão no banco (como `active`) viriam nulas.
        if ($section->wasRecentlyCreated) {
            $section->refresh();
        }

        // A criação é um detalhe interno da primeira leitura: sem isto o
        // JsonResource responderia 201 (Created) a um GET ou PATCH.
        $section->wasRecentlyCreated = false;

        return $section;
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    public function hasButton(): bool
    {
        return $this->supportsButton() && filled($this->button_label) && filled($this->button_route);
    }

    public function supportsButton(): bool
    {
        return !in_array($this->key, self::WITHOUT_BUTTON, true);
    }

    public function supportsImage(): bool
    {
        return in_array($this->key, self::WITH_IMAGE, true);
    }
}
