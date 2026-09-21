<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cursos da graduação e da pós, cada um com página própria em /cursos/{slug}.
 *
 * O `slug` é o endereço da página no site e o que o menu do cabeçalho usa:
 * criar um curso aqui cria a rota e o item de menu, sem tocar no front.
 *
 * Os dois campos de texto são HTML do editor rico e ficam um de cada lado da
 * faixa de informações da página: `description` é a apresentação (antes da
 * faixa) e `content` traz o material do curso — matriz curricular, PPCs e
 * demais links (depois dela).
 *
 * A tabela nasce com os cursos que o site oferece hoje, na ordem do menu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('position')->default(0)->index();
            $table->string('name');
            $table->string('slug')->unique();
            // Faixa de informações: nível, duração e quantos polos ofertam.
            $table->string('level', 60)->nullable();
            $table->string('duration', 60)->nullable();
            $table->unsignedInteger('poles')->nullable();
            $table->longText('description')->nullable();
            $table->longText('content')->nullable();
            $table->timestamps();
        });

        DB::table('courses')->insert($this->cursosDoSite());
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }

    /**
     * Os cursos como o site mostra hoje. Só Engenharia de Computação nasce com
     * os textos; nos demais, a equipe escreve pelo painel.
     */
    private function cursosDoSite(): array
    {
        $cursos = [
            ['Engenharia de Computação', 'engenharia-de-computacao', 'GRADUAÇÃO', '5 ANOS', 461],
            ['Engenharia de Produção', 'engenharia-de-producao', 'GRADUAÇÃO', null, null],
            ['Licenciatura em Matemática', 'licenciatura-em-matematica', 'GRADUAÇÃO', null, null],
            ['Pedagogia', 'pedagogia', 'GRADUAÇÃO', null, null],
            ['Letras – Habilitação em Língua Portuguesa', 'letras-habilitacao-em-lingua-portuguesa', 'GRADUAÇÃO', null, null],
            ['Bacharelado em Tecnologia da Informação', 'bacharelado-em-tecnologia-da-informacao', 'GRADUAÇÃO', null, null],
            ['Bacharelado em Ciência de Dados', 'bacharelado-em-ciencia-de-dados', 'GRADUAÇÃO', null, null],
            ['Bacharelado em Administração', 'bacharelado-em-administracao', 'GRADUAÇÃO', null, null],
            ['Bacharelado em Inteligência Artificial', 'bacharelado-em-inteligencia-artificial', 'GRADUAÇÃO', null, null],
            ['Tecnologia em Processos Gerenciais', 'tecnologia-em-processos-gerenciais', 'GRADUAÇÃO', null, null],
            [
                'Especialização em Processos Didático-Pedagógico para Cursos na Modalidade a Distância',
                'especializacao-em-processos-didatico-pedagogico-para-cursos-na-modalidade-a-distancia',
                'ESPECIALIZAÇÃO',
                null,
                null,
            ],
        ];

        $agora = now();

        return array_map(fn (array $curso, int $indice) => [
            'active' => true,
            'position' => $indice + 1,
            'name' => $curso[0],
            'slug' => $curso[1],
            'level' => $curso[2],
            'duration' => $curso[3],
            'poles' => $curso[4],
            'description' => $curso[1] === 'engenharia-de-computacao' ? $this->apresentacaoDaComputacao() : null,
            'content' => $curso[1] === 'engenharia-de-computacao' ? $this->materialDaComputacao() : null,
            'created_at' => $agora,
            'updated_at' => $agora,
        ], $cursos, array_keys($cursos));
    }

    private function apresentacaoDaComputacao(): string
    {
        return '<p>Com duração de cinco anos, forma o profissional para atuar na área, realizando, entre outras '
            .'atividades: análise, planejamento e desenvolvimento de sistemas computacionais centralizados e '
            .'distribuídos, sistemas embarcados, desenvolvimento e uso de tecnologias de comunicação, sistemas '
            .'multimídia e hipermídia, redes de computadores, bem como gestão de sistemas industriais e comerciais '
            .'e de empresas de computação.</p>'
            .'<p>Com essa abrangente formação o profissional pode especificar, projetar, implementar, integrar, '
            .'testar e manter sistemas de hardware e software e, assim, trabalhar no desenvolvimento de produtos, '
            .'aplicações e serviços em qualquer área da informática e da tecnologia da informação, atendendo a '
            .'demanda de indústrias, empresas, grupos financeiros, centros de pesquisa e desenvolvimento, '
            .'universidades, estabelecimentos de ensino e do setor de serviços públicos.</p>';
    }

    private function materialDaComputacao(): string
    {
        $documentos = [
            'PPC 2025' => 'https://apps.univesp.br/manual-do-aluno/assets/PPC/engenharia-da-computacao/PPC-BTI_2025.pdf',
            'MATRIZ CURRICULAR 2024' => 'https://apps.univesp.br/manual-do-aluno/assets/matriz-curricular/EC_matrizcurricular.pdf',
            'PPC 2024' => 'https://apps.univesp.br/manual-do-aluno/assets/PPC/engenharia-da-computacao/novoPPC-BTI.pdf',
            'MATRIZ CURRICULAR 2020' => 'https://univesp.br/sites/58f6506869226e9479d38201/assets/5db214307c1bd15cf783c6c9/EC.pdf',
            'PPC 2020' => 'https://apps.univesp.br/manual-do-aluno/assets/PPC/engenharia-da-computacao/PPC-BTI.pdf',
        ];

        $links = '';
        foreach ($documentos as $rotulo => $url) {
            $links .= sprintf('<p><a href="%s" target="_blank" rel="noopener noreferrer">%s</a></p>', $url, $rotulo);
        }

        return '<p>Curso gratuito com duração de cinco anos, realizado por meio de atividades desenvolvidas a '
            .'distância e de encontros presenciais no polo de apoio em que o aluno estiver matriculado.</p>'
            .$links
            .'<p><a href="https://ava.univesp.br/" target="_blank" rel="noopener noreferrer">Ambiente Virtual de '
            .'Aprendizagem (AVA)</a></p>'
            .'<p>Todos os polos Univesp ofertam este curso. '
            .'<a href="/polo">Pesquise aqui o polo mais próximo de sua casa.</a></p>';
    }
};
