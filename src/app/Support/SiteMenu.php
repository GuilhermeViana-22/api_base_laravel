<?php

namespace App\Support;

/**
 * Menu do site público, como o cabeçalho (Navbar) monta.
 *
 * Espelha o menu do painel: os itens com páginas internas (Institucional,
 * Pesquisa e Transparência) abrem os mesmos filhos que a sidebar mostra,
 * porque saem da mesma lista (SectionPages). Assim, uma página nova aparece
 * no painel e no site sem tocar no front.
 *
 * Os caminhos são conferidos contra SiteRoutes num teste: nenhum item do menu
 * pode apontar para uma rota que o site não tem.
 */
final class SiteMenu
{
    /** Primeira opção dos itens de seção: a página de abertura dela. */
    private const OVERVIEW_LABEL = 'Visão geral';

    /**
     * Itens do menu, na ordem em que aparecem.
     *
     * - `children`: submenu fixo (caminho => nome).
     * - `section`: submenu montado com as páginas daquela seção (SectionPages).
     */
    private const ITEMS = [
        ['label' => 'Vestibular', 'path' => '/vestibular'],
        ['label' => 'Provão Paulista', 'path' => '/provao-paulista'],
        [
            'label' => 'Cursos',
            'path' => '/cursos',
            'children' => [
                '/cursos' => 'Todos os cursos',
                '/cursos/engenharia' => 'Engenharia',
                '/cursos/engenharia-computacao' => 'Engenharia de Computação',
            ],
        ],
        ['label' => 'Notícias', 'path' => '/noticias'],
        ['label' => 'Polos', 'path' => '/polo'],
        ['label' => 'Institucional', 'path' => '/institucional', 'section' => 'institucional'],
        ['label' => 'Pesquisa', 'path' => '/pesquisa', 'section' => 'pesquisa'],
        ['label' => 'Transparência', 'path' => '/transparencia', 'section' => 'transparencia'],
        ['label' => 'Carreira Univesp', 'path' => '/carreira-univesp'],
    ];

    /**
     * O menu inteiro, com os filhos já resolvidos.
     *
     * @return array<int, array{label: string, path: string, children: array<int, array{label: string, path: string}>}>
     */
    public static function tree(): array
    {
        return array_map(static function (array $item): array {
            return [
                'label' => $item['label'],
                'path' => $item['path'],
                'children' => self::childrenOf($item),
            ];
        }, self::ITEMS);
    }

    /**
     * Todos os caminhos do menu (itens e filhos), para conferência.
     *
     * @return array<int, string>
     */
    public static function paths(): array
    {
        $caminhos = [];

        foreach (self::tree() as $item) {
            $caminhos[] = $item['path'];
            foreach ($item['children'] as $filho) {
                $caminhos[] = $filho['path'];
            }
        }

        return array_values(array_unique($caminhos));
    }

    /** @return array<int, array{label: string, path: string}> */
    private static function childrenOf(array $item): array
    {
        if (isset($item['children'])) {
            return array_map(
                fn (string $path, string $label) => ['path' => $path, 'label' => $label],
                array_keys($item['children']),
                $item['children'],
            );
        }

        if (!isset($item['section'])) {
            return [];
        }

        // Seção: a página de abertura e, depois, as páginas internas dela.
        $secao = $item['section'];
        $filhos = [['path' => $item['path'], 'label' => self::OVERVIEW_LABEL]];

        foreach (SectionPages::for($secao) as $pagina) {
            $filhos[] = ['path' => "/{$secao}/{$pagina['slug']}", 'label' => $pagina['name']];
        }

        return $filhos;
    }
}
