<?php

namespace Database\Seeders;

use App\Models\VestibularBlock;
use App\Models\VestibularSection;
use App\Services\ContentSanitizer;
use Illuminate\Database\Seeder;

/**
 * Conteúdo da página /vestibular, copiado do site da Univesp
 * (univesp.br/vestibular, 2026 a 2º semestre de 2017).
 *
 * Os links apontam para os arquivos onde estão hoje (univesp.br, Vunesp,
 * DOE); qualquer um pode virar upload pelo painel depois. Duas correções em
 * relação ao original: o "vestibular.univesp.br" de 2025 era um endereço
 * relativo quebrado, e a portaria nº 30 de 2024 terminava em `.pd` (404).
 *
 * Pode rodar quantas vezes quiser: a seção é achada pela posição na página e
 * o bloco pela posição na seção, então rodar de novo atualiza em vez de
 * duplicar. O HTML passa pelo mesmo filtro do painel (ContentSanitizer).
 *
 * Em produção, rode uma vez: `php artisan db:seed --class=VestibularSeeder`.
 */
class VestibularSeeder extends Seeder
{
    /**
     * Espaços (px) que fogem da regra automática do site, medidos nos prints
     * do original: o Elementor não espaça os blocos de forma uniforme.
     * Chave: "seção" ou "seção.bloco", contando do zero.
     */
    private const ESPACOS = [
        '0.3' => 17,
        '1' => 61,
        '1.4' => 17,
        '2.1' => 8,
        '2.7' => 34,
        '3' => 35,
        '3.0' => 48,
        '3.1' => 26,
        '3.2' => 20,
        '4' => 35,
        '4.0' => 32,
        '4.2' => 35,
        '4.10' => 56,
        '5' => 79,
        '5.0' => 40,
        '8.1' => 38,
        '9.1' => 39,
        '10.1' => 38,
    ];

    public function run(ContentSanitizer $sanitizer): void
    {
        foreach ($this->secoes() as $indice => [$nome, $divisoria, $blocos]) {
            $secao = VestibularSection::query()->firstOrNew(['position' => $indice + 1]);
            $secao->fill(['name' => $nome, 'divider' => $divisoria, 'spacing_top' => self::ESPACOS["{$indice}"] ?? null])->save();

            foreach ($blocos as $posicao => $dados) {
                if (isset($dados['html'])) {
                    $dados['html'] = $sanitizer->sanitize($dados['html']);
                }

                $bloco = VestibularBlock::query()->firstOrNew(['section_id' => $secao->id, 'position' => $posicao + 1]);
                $bloco->forceFill(array_merge([
                    'text' => null, 'style' => null, 'html' => null, 'kind' => null,
                    'url' => null, 'file_path' => null, 'file_name' => null, 'underline' => false,
                    'spacing_top' => self::ESPACOS["{$indice}.{$posicao}"] ?? null,
                ], $dados))->save();
            }
        }
    }

    private static function titulo(string $texto, string $estilo): array
    {
        return ['type' => 'title', 'text' => $texto, 'style' => $estilo];
    }

    private static function texto(string $html): array
    {
        return ['type' => 'text', 'html' => $html];
    }

    /** No original todos os links usam o ícone de PDF, inclusive os que levam ao DOE e ao YouTube. */
    private static function link(string $texto, string $url, bool $sublinhado): array
    {
        return ['type' => 'link', 'text' => $texto, 'url' => $url, 'kind' => 'pdf', 'underline' => $sublinhado];
    }

    private static function video(string $url): array
    {
        return ['type' => 'video', 'url' => $url];
    }

    /**
     * As seções na ordem da página: [nome no painel, divisória acima, blocos].
     *
     * @return array<int, array{0: string, 1: string, 2: array<int, array<string, mixed>>}>
     */
    private function secoes(): array
    {
        return [
            ['Vestibular 2026', 'none', [
                self::titulo('VESTIBULAR UNIVESP 2026', 'default'),
                self::texto('<p>A Univesp oferece 24.029 vagas nos cursos de graduação nos eixos de Licenciaturas (Letras, Matemática e Pedagogia), Computação (Tecnologia da Informação, Ciência de Dados, Engenharia de Computação e Inteligência Artificial) e Negócios e Produção (Processos Gerenciais, Administração e Engenharia de Produção).</p><p>Inscrições e informações em: <a href="http://vestibular.univesp.br" target="_blank">vestibular.univesp.br </a></p>'),
                self::titulo('Inscrições Vestibular 2026: vestibular.univesp.br', 'large'),
                self::link('Matrícula - PORTARIA PR Nº 28, DE 27 DE MAIO DE 2026.', 'https://univesp.br/wp-content/uploads/2026/05/SEI_0108989382_DOE__Portaria__Secao_1___Normativo_-1-1.pdf', false),
                self::link('Portaria Vestibular 2026', 'https://www.doe.sp.gov.br/executivo/secretaria-de-ciencia-tecnologia-e-inovacao/portaria-pr-01-2026-estabelece-as-normas-do-processo-seletivo-de-2026-202601301314561604837', false),
                self::link('Manual do Candidato', 'https://documento.vunesp.com.br/documento/stream/NzYwMTA1Nw%3d%3d', false),
                self::link('Vestibular ao vivo 2026', 'https://www.youtube.com/live/JC54q6C2Deo', false),
            ]],
            ['Vestibular 2025', 'none', [
                self::titulo('Outros Vestibulares', 'highlight'),
                self::titulo('VESTIBULAR UNIVESP 2025', 'default'),
                self::texto('<p>A Univesp abre no dia <strong>06/02, às 10h,</strong> as inscrições para <strong>22.935 vagas</strong> do Vestibular anual 2025, destinadas a <strong>432</strong> polos, de <strong>373 municípios</strong> (capital, interior e litoral).</p><p>São oferecidos nove cursos gratuitos: Letras, Matemática e Pedagogia (Eixo de Licenciatura), Ciência de Dados, Engenharia de Computação e Tecnologia da Informação (Eixo de Computação), e Administração, Engenharia de Produção e Tecnologia em Processos Gerenciais (Eixo de Negócios e Produção).</p><p>As inscrições terminam no dia <strong>07/04/25, às 23h59,</strong> e devem ser feitas pelo site: <strong><em>vestibular.univesp.br.</em></strong> A prova (objetiva e redação) ocorrerá no dia 1<strong>8/05, às 13h,</strong> e os locais oficiais serão divulgados no dia <strong>09/05, a partir das 10h.</strong> O início das aulas está previsto para o final de julho de 2025. Neste ano, a Univesp ofertou ainda 2.631 vagas para o Provão Paulista. Os aprovados também ingressam em julho.</p><p>Inscrições e informações em: <a href="https://vestibular.univesp.br" target="_blank">vestibular.univesp.br </a></p>'),
                self::titulo('Inscrições Vestibular 2025: vestibular.univesp.br', 'large'),
                self::link('Portaria Vestibular 2025', 'https://univesp.br/wp-content/uploads/2025/vestibular/PORTARIA_PR_N__14__DE_31_DE_JANEIRO_DE_2025.pdf', false),
                self::link('Manual do Candidato', 'https://univesp.br/wp-content/uploads/2025/vestibular/Manual_do_Candidato__-_Processo_Seletivo_Vestibular_2025.pdf', false),
            ]],
            ['Vestibular 2024', 'solid', [
                self::titulo('VESTIBULAR UNIVESP 2024 MATRÍCULA', 'default'),
                self::texto('<p>Confira o passo a passo para efetivação da matrícula em:</p>'),
                self::link('Tutorial Matricula', 'https://assets.univesp.br/blackboard/matricula/TutorialMatricula.pdf', false),
                self::texto('<p>ou Vídeo tutorial:</p>'),
                self::video('https://youtu.be/epKJlEc9lug'),
                self::texto('<p>Acesse o site para a realização da matrícula no link: <a href="https://sei.univesp.br/processoSeletivo/#/" target="_blank">Processo Seletivo</a></p>'),
                self::texto('<p>O processo seletivo da Universidade Virtual do Estado de São Paulo (Univesp) para 2024 ofereceu mais de 23 mil vagas, em mais de 400 polos, distribuídos por todas as regiões do Estado.</p><p>O vestibular ofertou nove cursos, com três eixos básicos de ingresso: Licenciatura (Letras, Matemática, Pedagogia), Computação (Tecnologia da Informação, Ciência de Dados, Engenharia de Computação) e Negócios e Produção (Tecnologia em Processos Gerenciais, Administração e Engenharia de Produção).</p><p>O início das aulas está previsto para o final de <strong>julho de 2024.</strong></p>'),
                self::link('Prova Vestibular 2024', 'https://univesp.br/wp-content/uploads/2025/vestibular/prova-univesp-2024-2-vestibular.pdf', true),
                self::link('Gabarito Prova Vestibular 2024', 'https://univesp.br/wp-content/uploads/2025/vestibular/gabarito-definitivo-univesp-2024.pdf', true),
                self::link('PORTARIA UNIVESP PR Nº 18, DE 31 DE JANEIRO DE 2024.', 'https://univesp.br/wp-content/uploads/2025/vestibular/PORTARIA-UNIVESP-18-2024.pdf', true),
                self::link('PORTARIA UNIVESP PR Nº 28, DE 18 DE MARÇO DE 2024.', 'https://univesp.br/wp-content/uploads/2025/vestibular/PORTARIA-UNIVESP-28-2024.pdf', true),
                self::link('PORTARIA UNIVESP PR Nº 30, DE 8 DE ABRIL DE 2024.', 'https://univesp.br/wp-content/uploads/2025/vestibular/PORTARIA-UNIVESP-30-2024.pdf', true),
                self::link('PORTARIA UNIVESP PR Nº 51, DE 21 DE JUNHO DE 2024', 'https://univesp.br/wp-content/uploads/2025/vestibular/PORTARIA-UNIVESP-51-2024.pdf', true),
                self::link('Manual do Candidato', 'https://univesp.br/wp-content/uploads/2025/vestibular/Manual_do_Candidato__-_Processo_Seletivo_Vestibular_2025.pdf', true),
                self::texto('<p>Mais informações em: <a href="https://univesp.fatvestibulares.com.br/home/" target="_blank">fatvestibulares</a></p>'),
            ]],
            ['Vestibular 2023', 'dotted', [
                self::titulo("VESTIBULAR UNIVESP 2023\nMATRÍCULA", 'default'),
                self::texto('<p>Confira o passo a passo para efetivação da matrícula em: <a href="https://univesp.br/wp-content/uploads/2025/vestibular/Instru__o_Matr_cula_2023.pdf" target="_blank">instruções para matrícula</a> Acesse o site para a realização da matrícula no link: <a href="https://sei.univesp.br/processoSeletivo/#/" target="_blank">faça aqui sua matrícula virtual (online)</a> ou acesse direto: <a href="https://sei.univesp.br/processoSeletivo/#/" target="_blank">Processo Seletivo</a> O processo seletivo da Universidade Virtual do Estado de São Paulo (Univesp) para 2023 ofereceu mais de 25 mil vagas, em mais de 400 polos, distribuídos por todas as regiões do Estado. O vestibular ofertou nove cursos, com três eixos básicos de ingresso: Licenciatura (Pedagogia, Letras, Matemática), Computação (Ciência de Dados, Tecnologia da Informação, Engenharia de Computação) e Negócios e Produção (Engenharia de Produção, Administração e Tecnologia em Processos Gerenciais). O início das aulas está previsto para o final de <strong>julho de 2023.</strong></p>'),
                self::link('Portaria Vestibular 2023_versão final_retificada em 04-02-2023', 'https://univesp.br/wp-content/uploads/2025/vestibular/UVSP2204_306_20230206103732.pdf', true),
                self::link('Portaria nº 26, de 03 de fevereiro de 2023', 'https://univesp.br/wp-content/uploads/2025/vestibular/Portaria%20n%C2%BA%2026%2C%20de%2003%20de%20fevereiro%20de%202023.pdf', true),
                self::link('Portaria Univesp PR Nº 25, de 1 de fevereiro de 2023. Edital de Abertura de Inscrições de 06.02 até o dia 30.03.2023', 'https://univesp.br/wp-content/uploads/2025/vestibular/Portaria%20Univesp%20PR%20N%C2%BA%2025%2C%20de%201%20de%20fevereiro%20de%202023.%20Edital%20de%20Abertura%20de%20Inscri%C3%A7%C3%B5es%20de%2006.02%20at%C3%A9%20o%20dia%2030.03.2023.pdf', true),
                self::link('Prova (atualizado 30.05.2023)', 'https://univesp.br/wp-content/uploads/2025/vestibular/Prova%20-%202023.pdf', true),
                self::link('Gabarito (atualizado 30.05.2023)', 'https://univesp.br/wp-content/uploads/2025/vestibular/Gabarito%20-%202023.pdf', true),
                self::link('Portaria matrícula (26/06/23)', 'https://univesp.br/wp-content/uploads/2025/vestibular/Portaria%20matr%C3%ADcula%202023.pdf', true),
                self::texto('<p>Mais informações em: <a href="https://univesp.br/univesp_divulga" target="_blank">Univesp Divulga</a></p>'),
            ]],
            ['Vestibular 2022', 'dotted', [
                self::titulo('Vestibular 2022', 'large'),
                self::texto('<p>As inscrições para o vestibular 2022 da Univesp foram encerradas no dia 25/04. O atual processo seletivo é o maior já realizado pela universidade, em expansão territorial, opções de cursos e número de vagas.</p><p>Foram ofertadas mais de 31.125 vagas, destinadas a nove cursos: Licenciaturas em Pedagogia, Letras, Matemática, Bacharelados em Ciência de Dados, Tecnologia da Informação, Engenharia de Computação, Engenharia de Produção, e os novos, em Administração e Processos Gerenciais.</p><p>O vestibular foi voltado para 402 polos, distribuídos por 347 municípios do Estado. O ingresso dos alunos será em agosto de 2022.</p><p>Todas as informações em: <a href="https://www.vunesp.com.br/UVSP2102" target="_blank">vestibular.univesp.br</a></p>'),
                self::link('Portaria Vestibular 2022', 'https://univesp.br/wp-content/uploads/2025/vestibular/Portaria%20Vestibular%202022.pdf', true),
                self::link('Vagas por polo', 'https://univesp.br/wp-content/uploads/2025/vestibular/Vagas%20por%20polo%20-%202022.pdf', true),
                self::link('Manual do Candidato 2022', 'https://univesp.br/wp-content/uploads/2025/vestibular/Manual%20do%20Candidato%202022.pdf', true),
                self::link('Cronograma Vestibular 2022', 'https://univesp.br/wp-content/uploads/2025/vestibular/Cronograma%20Vestibular%202022.pdf', true),
                self::link('Portaria - Instruções para Matrículas', 'https://univesp.br/wp-content/uploads/2025/vestibular/Portaria%20-%20Instru%C3%A7%C3%B5es%20para%20Matr%C3%ADculas%20-%202022.pdf', true),
                self::link('Tutorial Matrículas', 'https://univesp.br/wp-content/uploads/2025/vestibular/Tutorial%20Matr%C3%ADculas%20-%202022.pdf', true),
                self::link('Prova Objetiva 2022', 'https://univesp.br/wp-content/uploads/2025/vestibular/Prova%20Objetiva%202022.pdf', true),
                self::link('Gabarito Prova Objetiva 2022', 'https://univesp.br/wp-content/uploads/2025/vestibular/Gabarito%20Prova%20Objetiva%202022.pdf', true),
                self::texto('<p><strong>Em caso de dúvidas, entre em contato:</strong><br /><a title="Link: https://www.vunesp.com.br/faleConosco" href="https://www.vunesp.com.br/faleConosco">Fale Conosco</a><br /><img decoding="async" class="emoji" style="background-color: #ffffff;" role="img" draggable="false" src="https://s.w.org/images/core/emoji/17.0.2/svg/1f4de.svg" alt="📞" /> Disque Vunesp: 11 3874-6300</p>'),
            ]],
            ['Vestibular 2021', 'dotted', [
                self::titulo('Vestibular Univesp 2021', 'large'),
                self::link('Tutorial Matrículas On-Line', 'https://univesp.br/wp-content/uploads/2025/vestibular/Tutorial%20Matr%C3%ADculas%20On-Line%20-%202021.pdf', true),
                self::link('Portaria nº 37, de 22 de julho de 2021 - Instruções para a efetivação da matrícula on-line', 'https://univesp.br/wp-content/uploads/2025/vestibular/Portaria%20n%C2%BA%2037%2C%20de%2022%20de%20julho%20de%202021%20-%20Instru%C3%A7%C3%B5es%20para%20a%20efetiva%C3%A7%C3%A3o%20da%20matr%C3%ADcula%20on-line.pdf', true),
                self::link('Portaria Univesp PR Nº 15, de 09 de abril de 2021', 'https://univesp.br/wp-content/uploads/2025/vestibular/Portaria%20Univesp%20PR%20N%C2%BA%2015%2C%20de%2009%20de%20abril%20de%202021.pdf', true),
                self::link('Vagas por polo', 'https://univesp.br/wp-content/uploads/2025/vestibular/Vagas%20por%20polo%202021.pdf', true),
                self::link('Manual do Candidato', 'https://univesp.br/wp-content/uploads/2025/vestibular/Manual%20do%20Candidato%202021.pdf', true),
                self::link('Cartilha de Orientação aos Candidatos- Covid-19', 'https://univesp.br/wp-content/uploads/2025/vestibular/Cartilha%20de%20Orientac%CC%A7a%CC%83o%20aos%20Candidatos-%20Covid-19%202021.pdf', true),
                self::link('Protocolos para Aplicação de Provas de Concursos, Vestibulares e Avaliações em Decorrência da Pandemia (Covid-19)', 'https://univesp.br/wp-content/uploads/2025/vestibular/Protocolos%20para%20Aplica%C3%A7%C3%A3o%20de%20Provas%20de%20Concursos%2C%20Vestibulares%20e%20Avalia%C3%A7%C3%B5es%20em%20Decorr%C3%AAncia%20da%20Pandemia%20%28Covid-19%29.pdf', true),
                self::link('Ata da Reunião Centro de Contingência Covid-19', 'https://univesp.br/wp-content/uploads/2025/vestibular/Ata%20da%20Reuni%C3%A3o%20Centro%20de%20Conting%C3%AAncia%20Covid-19.pdf', true),
                self::link('Nota Técnica Centro de Contingência do Coronavírus', 'https://univesp.br/wp-content/uploads/2025/vestibular/Nota%20Te%CC%81cnica%20Centro%20de%20Continge%CC%82ncia%20do%20Coronav%C3%ADrus.pdf', true),
                self::link('Prova Eixo de Licenciatura', 'https://univesp.br/wp-content/uploads/2025/vestibular/Prova%20Eixo%20de%20Licenciatura%202021.pdf', true),
                self::link('Gabarito Prova Eixo de Licenciatura', 'https://univesp.br/wp-content/uploads/2025/vestibular/Gabarito%20Prova%20Eixo%20de%20Licenciatura%202021.pdf', true),
                self::link('Prova Eixo de Computação', 'https://univesp.br/wp-content/uploads/2025/vestibular/Prova%20Eixo%20de%20Computa%C3%A7%C3%A3o%202021.pdf', true),
                self::link('Gabarito Prova Eixo de Computação', 'https://univesp.br/wp-content/uploads/2025/vestibular/Gabarito%20Prova%20Eixo%20de%20Computa%C3%A7%C3%A3o%202021.pdf?_t=1738947891', true),
            ]],
            ['Vestibular 2020', 'none', [
                self::titulo('Vestibular 2020', 'large'),
                self::link('Manual do Candidato', 'https://univesp.br/wp-content/uploads/2025/vestibular/Manual%20do%20Candidato%202020.pdf', true),
                self::link('Prova', 'https://univesp.br/wp-content/uploads/2025/vestibular/Prova%202020.pdf', true),
                self::link('Gabarito', 'https://univesp.br/wp-content/uploads/2025/vestibular/Gabarito%202020.pdf', true),
            ]],
            ['Vestibular 2019.2', 'none', [
                self::titulo('Vestibular 2019.2', 'large'),
                self::link('Prova Univesp 2019.2', 'https://univesp.br/wp-content/uploads/2025/vestibular/Prova%20Univesp%202019.2.pdf', true),
                self::link('Gabarito Univesp 2019.2', 'https://univesp.br/wp-content/uploads/2025/vestibular/Gabarito%20Univesp%202019.2.pdf', true),
            ]],
            ['Vestibular 2º semestre 2018', 'none', [
                self::titulo('Vestibular 2º semestre 2018', 'large'),
                self::link('Prova Univesp 2018', 'https://univesp.br/wp-content/uploads/2025/vestibular/Prova%20Univesp%202018%202__Semestre_de_2018.pdf', true),
                self::link('Gabarito Univesp 2018', 'https://univesp.br/wp-content/uploads/2025/vestibular/Gabarito%20Univesp%202018%202__Semestre_de_2018_-_Gabarito.pdf', true),
            ]],
            ['Vestibular 1º semestre 2018', 'none', [
                self::titulo('Vestibular 1º semestre de 2018', 'large'),
                self::link('Prova Univesp 2018', 'https://univesp.br/wp-content/uploads/2025/vestibular/Prova%20Univesp%202018.pdf', true),
                self::link('Gabarito Univesp 2018', 'https://univesp.br/wp-content/uploads/2025/vestibular/Gabarito%20Univesp%202018.pdf', true),
            ]],
            ['Vestibular 2º semestre 2017', 'none', [
                self::titulo('Vestibular 2º semestre de 2017', 'large'),
                self::link('Prova Univesp 2017', 'https://univesp.br/wp-content/uploads/2025/vestibular/Prova%20Univesp%202017.pdf', true),
                self::link('Gabarito Univesp 2017', 'https://univesp.br/wp-content/uploads/2025/vestibular/Gabarito%20Univesp%202017.pdf', true),
            ]],
        ];
    }
}
