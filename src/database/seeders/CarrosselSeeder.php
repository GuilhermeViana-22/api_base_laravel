<?php

namespace Database\Seeders;

use App\Models\CarouselSlide;
use App\Services\CarouselSlideService;
use Database\Seeders\Concerns\CopiaArquivosParaStorage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Slides do carrossel do topo da página inicial, como foram montados no
 * painel (Home > Carrossel).
 *
 * As imagens vêm de database/seeders/arquivos/carrossel/ e vão para a mesma
 * pasta que o painel usa (CarouselSlideService::IMAGE_DIR).
 *
 * Pode rodar quantas vezes quiser: cada slide é achado pela posição, então
 * rodar de novo atualiza em vez de duplicar. Depois do seeder, tudo segue
 * editável, reordenável e excluível no painel.
 */
class CarrosselSeeder extends Seeder
{
    use CopiaArquivosParaStorage;

    /** Botões no azul-escuro com texto branco, como foram salvos. */
    private const BOTAO = ['button_color' => '#172833', 'button_text_color' => '#FFFFFF'];

    private const SLIDES = [
        [
            'title' => "Prova acontece dia 26/04, às 13h; publicação oficial dos locais de prova será feita em 15/04, no site vestibular.univesp.br",
            'imagem' => 'vestibular-prova-26-04.jpg',
            'button_label' => 'Vestibular',
            'button_route' => '/vestibular',
        ],
        [
            'title' => 'Provão Paulista oferece 7.586 vagas em cursos superiores no segundo semestre',
            'imagem' => 'provao-paulista-7586-vagas.jpg',
            'button_label' => 'Saiba mais',
            'button_route' => '/noticias',
        ],
        [
            'title' => 'Univesp participa do 31 CIAED sobre o futuro da educação a distância',
            'imagem' => 'univesp-31-ciaed.jpg',
            'button_label' => 'Acesse',
            'button_route' => '/noticias',
        ],
    ];

    public function run(): void
    {
        foreach (self::SLIDES as $indice => $dados) {
            $slide = CarouselSlide::query()->firstOrNew(['position' => $indice + 1]);
            $imagem = $this->copiarParaStorage(CarouselSlideService::IMAGE_DIR.'/'.$dados['imagem']);

            // Imagem trocada no painel depois do último seed: a anterior não é mais usada.
            if ($slide->image_path && $slide->image_path !== $imagem) {
                Storage::disk('public')->delete($slide->image_path);
            }

            unset($dados['imagem']);
            $slide->fill(['active' => true, ...$dados, ...self::BOTAO]);
            $slide->image_path = $imagem;
            $slide->save();
        }
    }
}
