<?php

namespace App\Support;

use App\Models\Course;
use App\Models\SitePage;

/**
 * Menu do site público, como o cabeçalho (Navbar) monta.
 *
 * Espelha o menu do painel: os itens com páginas internas (Institucional,
 * Pesquisa e Transparência) abrem os mesmos filhos que a sidebar mostra,
 * porque saem da mesma lista (SectionPages). Assim, uma página nova aparece
 * no painel e no site sem tocar no front.
 *
 * "Cursos" é o único item que vem do banco: cada curso ligado no painel entra
 * como uma opção do submenu, apontando para a página dele.
 *
 * Os caminhos são conferidos contra SiteRoutes num teste: nenhum item do menu
 * pode apontar para uma rota que o site não tem.
 *
 * O que Configurações escondeu (SitePage) não sai daqui: item oculto some do
 * cabeçalho, e um item cujos filhos sumiram todos some junto.
 */
final class SiteMenu
{
    /** Primeira opção dos itens de seção: a página de abertura dela. */
    private const OVERVIEW_LABEL = 'Visão geral';

    /** Primeira opção do menu de cursos, antes dos cursos em si. */
    private const ALL_COURSES_LABEL = 'Todos os cursos';

    /**
     * Itens do menu, na ordem em que aparecem.
     *
     * - `children`: submenu fixo (caminho => nome).
     * - `section`: submenu montado com as páginas daquela seção (SectionPages).
     * - `courses`: submenu montado com os cursos ligados no painel.
     */
    private const ITEMS = [
        ['label' => 'Vestibular', 'path' => '/vestibular'],
        ['label' => 'Provão Paulista', 'path' => '/provao-paulista'],
        ['label' => 'Cursos', 'path' => '/cursos', 'courses' => true],
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
        $ocultas = SitePage::hiddenPaths();

        $itens = array_map(static function (array $item) use ($ocultas): array {
            return [
                'label' => $item['label'],
                'path' => $item['path'],
                'children' => array_values(array_filter(
                    self::childrenOf($item),
                    fn (array $filho) => !$ocultas->contains($filho['path']),
                )),
            ];
        }, self::ITEMS);

        // O item de topo sai quando ele mesmo está oculto. Se ele tinha filhos e
        // todos sumiram, some também: o menu não abre um dropdown vazio.
        return array_values(array_filter(
            $itens,
            fn (array $item) => !$ocultas->contains($item['path'])
                && !(self::hasChildren($item['path']) && $item['children'] === []),
        ));
    }

    /** O item nasce com submenu? (fixo, de seção ou de cursos) */
    private static function hasChildren(string $path): bool
    {
        foreach (self::ITEMS as $item) {
            if ($item['path'] === $path) {
                return isset($item['children']) || isset($item['section']) || isset($item['courses']);
            }
        }

        return false;
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

        // Cursos: a página com todos e, depois, um item por curso no ar.
        if (isset($item['courses'])) {
            $filhos = [['path' => $item['path'], 'label' => self::ALL_COURSES_LABEL]];

            foreach (Course::active()->ordered()->get() as $curso) {
                $filhos[] = ['path' => $curso->path(), 'label' => $curso->name];
            }

            return $filhos;
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
