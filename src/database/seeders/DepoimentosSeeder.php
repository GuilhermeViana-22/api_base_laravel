<?php

namespace Database\Seeders;

use App\Models\HomeSection;
use App\Models\Testimonial;
use App\Services\HomeContentService;
use Database\Seeders\Concerns\CopiaArquivosParaStorage;
use Illuminate\Database\Seeder;

/**
 * Bloco de depoimentos da página inicial, como está publicado no site.
 *
 * Antes, os quatro depoimentos, a foto grande e a quebra da frase ficavam
 * fixos no front, e o que o painel salvava não aparecia. Agora tudo sai do
 * banco: título, frase (com as quebras de linha do layout), foto da seção e
 * os depoimentos com foto. As imagens vêm de database/seeders/arquivos/home/,
 * nas mesmas pastas que o painel usa (HomeContentService).
 *
 * Pode rodar quantas vezes quiser: a seção é uma só, e cada depoimento é
 * achado pela posição. Depois do seeder, tudo segue editável no painel.
 */
class DepoimentosSeeder extends Seeder
{
    use CopiaArquivosParaStorage;

    private const DEPOIMENTOS = [
        ['Maria Ferreira', 'A Univesp me permitiu estudar e trabalhar ao mesmo tempo, com uma ótima flexibilidade.', 'maria-ferreira.png'],
        ['João Santos', 'Os professores são excelentes e o conteúdo é muito bem organizado.', 'joao-santos.jpg'],
        ['Pedro Paulo', 'Estudar à distância na Univesp foi essencial para conquistar meu diploma.', 'pedro-paulo.jpg'],
        ['Fernanda Silva', 'A Univesp me ajudou a crescer profissionalmente com uma educação acessível.', 'fernanda-silva.jpg'],
    ];

    public function run(): void
    {
        $secao = HomeSection::forKey(HomeSection::TESTIMONIALS);
        $secao->fill([
            'active' => true,
            'title' => 'Depoimentos de ex-alunos Univesp',
            // Cada linha é uma linha no site, como no layout publicado.
            'description' => "A UNIVESP me ajudou a alcançar\num objetivo que eu não achava\npossível.",
        ]);
        $secao->image_path = $this->copiarParaStorage(HomeContentService::SECTION_IMAGE_DIR.'/depoimentos-ex-alunos.webp');
        $secao->save();

        foreach (self::DEPOIMENTOS as $indice => [$nome, $frase, $foto]) {
            $depoimento = Testimonial::query()->firstOrNew(['position' => $indice + 1]);
            $depoimento->fill(['active' => true, 'name' => $nome, 'quote' => $frase]);
            $depoimento->photo_path = $this->copiarParaStorage(HomeContentService::TESTIMONIAL_PHOTO_DIR."/{$foto}");
            $depoimento->save();
        }
    }
}
