<?php

namespace App\Support;

/**
 * Páginas internas das seções do site (Institucional, Pesquisa, ...).
 *
 * Por ora é só a relação das rotas: nenhuma delas tem conteúdo próprio ainda.
 * A API devolve slug + nome (`GET /api/secoes/{secao}/paginas`) e o painel
 * monta o submenu da seção a partir daí, para a lista existir num lugar só —
 * mexer aqui muda o menu sem tocar no front.
 */
final class SectionPages
{
    /** Seção => (slug da página => nome exibido), na ordem em que aparecem no menu. */
    public const PAGES = [
        'institucional' => [
            'historia' => 'História',
            'missao-visao-e-valores' => 'Missão, Visão e Valores',
            'estrutura-conselhos' => 'Estrutura/Conselhos',
            'pdi' => 'PDI',
            'marca' => 'Marca',
            'univesp-em-numeros' => 'Univesp em Números',
            'boletins-mensais' => 'Boletins mensais – Comunicação',
            'canais-univesp-tv' => 'Relação dos Canais Univesp TV',
            'carta-de-servicos' => 'Atendimento/Carta de Serviços ao Usuário',
            'guia-do-orientador-de-polo' => 'Guia do Orientador de Polo',
            'solicitacao-de-videoaulas' => 'Solicitação de videoaulas',
            'parceiros' => 'Parceiros',
            'empresas-parceiras-estagios' => 'Empresas Parceiras – Estágios',
            'prestacao-de-servico-voluntario' => 'Prestação de Serviço Voluntário',
            'agenda-do-presidente' => 'Agenda do Presidente',
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

    /** Seções aceitas na rota `/secoes/{secao}/paginas`. */
    public static function sections(): array
    {
        return array_keys(self::PAGES);
    }

    /**
     * As páginas de uma seção, como a API responde.
     *
     * @return array<int, array{slug: string, name: string}>
     */
    public static function for(string $section): array
    {
        $pages = self::PAGES[$section] ?? [];

        return array_map(
            fn (string $slug, string $name) => ['slug' => $slug, 'name' => $name],
            array_keys($pages),
            $pages,
        );
    }

    public static function has(string $section, string $slug): bool
    {
        return array_key_exists($slug, self::PAGES[$section] ?? []);
    }
}
