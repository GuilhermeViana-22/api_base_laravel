<?php

namespace Database\Seeders;

use App\Models\ProvaoLink;
use App\Models\ProvaoPage;
use Database\Seeders\Concerns\CopiaArquivosParaStorage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Conteúdo da página /provao-paulista, como foi montado no painel.
 *
 * Pode rodar quantas vezes quiser: a página é uma só, e cada link é achado
 * pela posição no card, então rodar de novo atualiza em vez de duplicar.
 * Depois do seeder, tudo continua editável e excluível pelo painel.
 *
 * O PDF do tutorial vem de `database/seeders/arquivos/provao/arquivos/` e é
 * copiado para o mesmo caminho no disco `public` (ver CopiaArquivosParaStorage).
 *
 * Em produção, rode uma vez: `php artisan db:seed --class=ProvaoPaulistaSeeder`.
 */
class ProvaoPaulistaSeeder extends Seeder
{
    use CopiaArquivosParaStorage;

    public function run(): void
    {
        $pagina = ProvaoPage::query()->first() ?? new ProvaoPage();
        $pagina->fill([
            'title' => 'Provão Paulista 2026 – Ingresso no 2º semestre de 2026',
            'content' => $this->conteudo(),
            'card_title' => 'Informações sobre a Matrícula',
            'card_content' => $this->textoDoCard(),
        ])->save();

        foreach ($this->links() as $indice => $dados) {
            $this->gravarLink($indice + 1, $dados);
        }
    }

    /**
     * @param  array{label: string, text: string, kind: string, url?: string, file?: string, file_name?: string}  $dados
     */
    private function gravarLink(int $posicao, array $dados): void
    {
        $link = ProvaoLink::query()->firstOrNew(['position' => $posicao]);
        $caminhoNovo = isset($dados['file']) ? $this->copiarParaStorage($dados['file']) : null;

        // Arquivo trocado no painel depois do último seed: o anterior não é mais usado.
        if ($link->file_path && $link->file_path !== $caminhoNovo) {
            Storage::disk('public')->delete($link->file_path);
        }

        $link->fill([
            'label' => $dados['label'],
            'text' => $dados['text'],
            'kind' => $dados['kind'],
            'url' => $caminhoNovo ? null : $dados['url'],
        ]);
        $link->file_path = $caminhoNovo;
        $link->file_name = $caminhoNovo ? ($dados['file_name'] ?? basename($caminhoNovo)) : null;
        $link->save();
    }

    /** Os links do card, na ordem do site. */
    private function links(): array
    {
        return [
            [
                'label' => 'Tutorial em vídeo com o passo a passo para fazer sua matrícula:',
                'text' => 'Veja o Vídeo Tutorial de matrícula',
                'kind' => 'video',
                'url' => 'https://youtu.be/84a_-OPz3Pk',
            ],
            [
                'label' => 'Tutorial em PDF com o passo a passo para fazer sua matrícula:',
                'text' => 'Veja o PDF',
                'kind' => 'pdf',
                'file' => 'provao/arquivos/tutorial-matricula-provao-paulista.pdf',
                'file_name' => 'Tutorial-Matricula_Provao_Paulista.pdf',
            ],
            [
                'label' => 'Portaria – Normas Operacionais de Matrícula:',
                'text' => 'Veja as normas operacionais',
                'kind' => 'link',
                'url' => 'https://www.doe.sp.gov.br/executivo/secretaria-de-ciencia-tecnologia-e-inovacao/'
                    .'portaria-pr-16-2026-normas-operacionais-de-matricula-do-processo-seletivo-provao-paulista-2025-20260401111652141747078',
            ],
        ];
    }

    /** Texto, cronograma e aviso (HTML do editor, no formato que o ContentSanitizer deixa passar). */
    private function conteudo(): string
    {
        $linhas = [
            ['15:00 do dia 13/04/2026', 'Divulgação dos candidatos(as) aprovados(as) na 1ª chamada'],
            ['Das 9:00 do dia 14/04 às 18:00 do dia 15/04/2026', 'Matrícula da 1ª chamada'],
            ['15:00 do dia 22/04/2026', 'Divulgação dos candidatos(as) aprovados(as) na 2ª chamada'],
            ['Das 9:00 do dia 23/04 às 18:00 do dia 24/04/2026', 'Matrícula da 2ª chamada'],
            ['15:00 do dia 04/05/2026', 'Divulgação dos candidatos(as) aprovados(as) na 3ª chamada'],
            ['Das 9:00 do dia 05/05 às 18:00 do dia 06/05/2026', 'Matrícula da 3ª chamada'],
        ];

        $corpo = implode('', array_map(
            fn (array $linha) => "<tr><td>{$linha[0]}</td><td>{$linha[1]}</td></tr>",
            $linhas,
        ));

        return '<p>Cronograma com as datas de divulgação das listas de pessoas aprovadas no Provão Paulista e das datas para matrícula:</p>'
            .'<table><thead><tr><th>Datas</th><th>Evento</th></tr></thead><tbody>'.$corpo.'</tbody></table>'
            .'<p class="texto-centro"><strong>ATENÇÃO:</strong> Caso você perca a data de matrícula da sua chamada, '
            .'será considerado(a) desistente. A vaga será disponibilizada para outro candidato(a), da chamada subsequente.</p>';
    }

    private function textoDoCard(): string
    {
        return '<p>Divulgações de chamadas, Edital e demais informações sobre o processo seletivo: '
            .'<a href="https://provaopaulistaseriado.vunesp.com.br/" target="_blank" rel="noopener noreferrer">https://provaopaulistaseriado.vunesp.com.br/</a>.</p>'
            .'<p>Para iniciar sua pré-matrícula on-line, acesse o link: '
            .'<a href="https://sei.univesp.br/processoSeletivo" target="_blank" rel="noopener noreferrer">https://sei.univesp.br/processoSeletivo</a>.</p>';
    }
}
