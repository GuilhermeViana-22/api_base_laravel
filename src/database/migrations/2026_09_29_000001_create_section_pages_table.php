<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Páginas internas das seções do site (Institucional, Pesquisa, Transparência).
 *
 * Até aqui a relação vivia numa constante (SectionPages::PAGES), só com slug e
 * nome. Agora cada página tem rótulo (o texto do menu), título e conteúdo, e
 * o painel cria, edita, reordena e exclui sem ninguém tocar no código.
 *
 * A migration já traz as páginas que existiam, na mesma ordem e com os mesmos
 * slugs: menus, permissões (`pages.<secao>.<slug>`) e links divulgados
 * continuam valendo.
 *
 * Não há coluna de "publicada": tirar uma página do ar continua sendo papel de
 * Configurações > Páginas do site (SitePage), que já agenda e esconde por
 * caminho.
 */
return new class extends Migration
{
    /** As páginas que existiam na constante, na ordem do menu. */
    private const PAGINAS = [
        'institucional' => [
            'historia' => 'História',
            'missao-visao-e-valores' => 'Missão, visão e valores',
            'estrutura-conselhos' => 'Estrutura/Conselhos',
            'pdi' => 'PDI',
            'marca' => 'Marca',
            'univesp-em-numeros' => 'Univesp em Números',
            'boletins-mensais' => 'Boletins mensais – Comunicação',
            'canais-univesp-tv' => 'Relação dos canais Univesp TV',
            'carta-de-servicos' => 'Atendimento/Carta de Serviços ao Usuário',
            'guia-do-orientador-de-polo' => 'Guia do Orientador de Polo',
            'solicitacao-de-videoaulas' => 'Solicitação de videoaulas',
            'parceiros' => 'Parceiros',
            'empresas-parceiras-estagios' => 'Empresas Parceiras – Estágios',
            'prestacao-de-servico-voluntario' => 'Prestação de Serviço Voluntário',
            'agenda-do-presidente' => 'Agenda do presidente',
        ],
        'pesquisa' => [
            'congresso-academico-univesp' => 'Congresso Acadêmico Univesp',
            'professores-e-pesquisadores' => 'Professores e Pesquisadores',
            'iniciacao-cientifica' => 'Iniciação científica',
            'grupo-levia' => 'Grupo LEVIA',
            'difusao-cientifica' => 'Difusão Científica',
        ],
        'transparencia' => [
            'portal-de-compras-do-governo-federal' => 'Portal de Compras do Governo Federal',
            'bolsa-de-estudos' => 'Bolsa de Estudos',
            'chamamento-publico-polos' => 'Chamamento Público Polos',
            'chamamento-publico-oportunidade-ja' => 'Chamamento Público – Oportunidade Já',
            'chamamento-publico-conselho-de-usuarios' => 'Chamamento Público – Conselho de Usuários da Univesp',
            'concursos' => 'Concursos',
            'concurso-docente' => 'Concurso Docente',
            'convenio-para-estagio' => 'Convênio para Estágio',
            'acessibilidade' => 'Acessibilidade',
            'credenciamento' => 'Credenciamento',
            'facilitadores' => 'Facilitadores',
            'lgpd' => 'Lei Geral de Proteção de Dados Pessoais (LGPD)',
            'licitacoes' => 'Licitações',
            'medidas-preventivas-contra-o-coronavirus' => 'Medidas Preventivas Contra o Coronavírus',
            'monitoria' => 'Monitoria',
            'normas-internas' => 'Normas Internas',
            'pss-supervisor-pedagogico' => 'PSS Supervisor Pedagógico',
            'pss-docentes' => 'PSS Docentes',
            'regulacao' => 'Regulação',
            'relatorios-e-balancos' => 'Relatórios e Balanços',
            'atas-dos-conselhos' => 'Atas dos Conselhos',
            'plano-de-contratacoes-anual' => 'Plano de Contratações Anual',
        ],
    ];

    /**
     * Texto que já está publicado no site atual, no formato do editor
     * (cor por classe, sem `style`, como o ContentSanitizer exige).
     */
    private const CONTEUDO_INICIAL = [
        'institucional' => [
            'missao-visao-e-valores' => '<p><span class="texto-vermelho"><strong>Missão</strong></span></p>'
                .'<p>Promover o desenvolvimento humano e profissional por meio do ensino, da pesquisa e da '
                .'expansão da educação digital e das metodologias inovadoras.</p>'
                .'<p><span class="texto-vermelho"><strong>Visão</strong></span></p>'
                .'<p>Consolidar-se como instituição de referência nacional em educação digital.</p>'
                .'<p><span class="texto-vermelho"><strong>Valores</strong></span></p>'
                .'<p>A Universidade Virtual do Estado de São Paulo está comprometida com os valores de: Transparência</p>'
                .'<p>▪ Cidadania<br>▪ Ética<br>▪ Responsabilidade social<br>▪ Inovação</p>',
        ],
    ];

    public function up(): void
    {
        Schema::create('section_pages', function (Blueprint $table) {
            $table->id();
            // institucional, pesquisa ou transparencia (SectionPages::SECTIONS)
            $table->string('section', 40);
            // Trecho da URL: /institucional/{slug}. Fixo depois de criado.
            $table->string('slug', 120);
            // Texto do menu (navbar, coluna da esquerda e sidebar do painel)
            $table->string('label', 120);
            // Título da página: aba do navegador e destaque do topo
            $table->string('title');
            // HTML do editor, já filtrado pelo ContentSanitizer
            $table->longText('content')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['section', 'slug']);
            $table->index(['section', 'position']);
        });

        $agora = now();
        $linhas = [];

        foreach (self::PAGINAS as $secao => $paginas) {
            $posicao = 0;
            foreach ($paginas as $slug => $nome) {
                $linhas[] = [
                    'section' => $secao,
                    'slug' => $slug,
                    'label' => $nome,
                    'title' => $nome,
                    'content' => self::CONTEUDO_INICIAL[$secao][$slug] ?? null,
                    'position' => ++$posicao,
                    'created_at' => $agora,
                    'updated_at' => $agora,
                ];
            }
        }

        DB::table('section_pages')->insert($linhas);
    }

    public function down(): void
    {
        Schema::dropIfExists('section_pages');
    }
};
