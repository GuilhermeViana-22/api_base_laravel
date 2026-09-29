<?php

namespace App\Services;

use App\Models\SectionPage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Regras de escrita das páginas de seção: slug, posição e HTML filtrado.
 *
 * Validação fica nos FormRequests; aqui só o que acontece ao gravar.
 */
class SectionPageService
{
    public function __construct(private readonly ContentSanitizer $sanitizer)
    {
    }

    /** Página nova entra no fim do menu; sem slug, ele nasce do rótulo. */
    public function create(string $section, array $data): SectionPage
    {
        $data = $this->sanitized($data);
        $data['section'] = $section;
        $data['slug'] = $data['slug'] ?? Str::slug($data['label']);
        $data['position'] = (int) SectionPage::inSection($section)->max('position') + 1;

        return SectionPage::create($data);
    }

    /** Rótulo, título e conteúdo; o slug não muda (ver SectionPage). */
    public function update(SectionPage $pagina, array $data): SectionPage
    {
        $pagina->update($this->sanitized($data));

        return $pagina->refresh();
    }

    /**
     * Nova ordem do menu: os slugs na ordem desejada. Página que ficou de fora
     * da lista vai para o fim, na ordem em que estava.
     *
     * @param  array<int, string>  $slugs
     */
    public function reorder(string $section, array $slugs): void
    {
        DB::transaction(function () use ($section, $slugs) {
            foreach (array_values($slugs) as $posicao => $slug) {
                SectionPage::inSection($section)->where('slug', $slug)->update(['position' => $posicao + 1]);
            }

            SectionPage::inSection($section)
                ->whereNotIn('slug', $slugs)
                ->ordered()
                ->get()
                ->each(fn (SectionPage $pagina, int $indice) => $pagina->update([
                    'position' => count($slugs) + $indice + 1,
                ]));
        });
    }

    /** O site renderiza o conteúdo com v-html: nunca gravar HTML sem filtro. */
    private function sanitized(array $data): array
    {
        if (array_key_exists('content', $data) && $data['content'] !== null) {
            $data['content'] = $this->sanitizer->sanitize($data['content']);
        }

        return $data;
    }
}
