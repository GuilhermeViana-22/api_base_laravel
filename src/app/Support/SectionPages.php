<?php

namespace App\Support;

use App\Models\SectionPage;

/**
 * Páginas internas das seções do site (Institucional, Pesquisa, ...).
 *
 * As páginas moram no banco (SectionPage) e são geridas pelo painel; esta
 * classe é o ponto único de leitura para quem monta menu, rotas e permissões
 * (SiteMenu, SiteRoutes, PanelResources). Uma página criada no painel
 * aparece nos três sem ninguém tocar no código.
 *
 * As seções em si são fixas: cada uma tem rota própria no site.
 */
final class SectionPages
{
    /** Seções que têm páginas internas. */
    public const SECTIONS = ['institucional', 'pesquisa', 'transparencia'];

    /**
     * Caminhos que não podem virar slug: são telas do painel dentro da mesma
     * URL (`/arearestrita/institucional/nova`, `.../banner`) ou rotas da API
     * (`/admin/secoes/{secao}/paginas/reorder`, `.../imagens`).
     */
    public const RESERVED_SLUGS = ['nova', 'banner', 'reorder', 'imagens'];

    /** Seções aceitas nas rotas `/secoes/{secao}/...`. */
    public static function sections(): array
    {
        return self::SECTIONS;
    }

    /**
     * As páginas de uma seção, na ordem do menu, como a API responde.
     *
     * @return array<int, array{slug: string, name: string, title: string}>
     */
    public static function for(string $section): array
    {
        return SectionPage::inSection($section)
            ->ordered()
            ->get(['slug', 'label', 'title'])
            ->map(fn (SectionPage $pagina) => [
                'slug' => $pagina->slug,
                'name' => $pagina->label,
                'title' => $pagina->title,
            ])
            ->all();
    }

    public static function has(string $section, string $slug): bool
    {
        return SectionPage::inSection($section)->where('slug', $slug)->exists();
    }
}
