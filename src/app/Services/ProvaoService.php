<?php

namespace App\Services;

use App\Models\ProvaoLink;
use App\Models\ProvaoPage;
use App\Support\Positions;
use Illuminate\Http\UploadedFile;

/**
 * Regras de escrita da página /provao-paulista, usadas pelo painel.
 *
 * O destino dos links (URL ou arquivo, nunca os dois, e o tipo que sai do
 * destino) fica no LinkTargetService, o mesmo da página do vestibular.
 */
class ProvaoService
{
    public const FILE_DIR = 'provao/arquivos';

    public function __construct(
        private readonly ContentSanitizer $sanitizer,
        private readonly LinkTargetService $targets,
    ) {
    }

    /** Atualiza só os campos enviados; os dois textos passam pelo filtro de HTML. */
    public function updatePage(array $data): ProvaoPage
    {
        foreach (['content', 'card_content'] as $campo) {
            if (array_key_exists($campo, $data) && $data[$campo] !== null) {
                $data[$campo] = $this->sanitizer->sanitize($data[$campo]);
            }
        }

        $pagina = ProvaoPage::current();
        $pagina->fill($data)->save();

        return $pagina;
    }

    /** Cria o link no fim do card, com endereço ou com o arquivo enviado junto. */
    public function createLink(array $data, ?UploadedFile $file): ProvaoLink
    {
        unset($data['file']);
        $link = new ProvaoLink($data);
        $link->position = Positions::next(ProvaoLink::query());
        $this->targets->fillNew($link, $data, $file, self::FILE_DIR);
        $link->save();

        return $link;
    }

    public function updateLink(ProvaoLink $link, array $data): ProvaoLink
    {
        // `kind` vazio é o mesmo que não mandar: o tipo sai do destino.
        if (array_key_exists('kind', $data) && $data['kind'] === null) {
            unset($data['kind']);
        }

        $link->fill($data);
        $this->targets->applyUpdate($link, $data);
        $link->save();

        return $link;
    }

    public function replaceFile(ProvaoLink $link, UploadedFile $file, ?string $kind = null): ProvaoLink
    {
        $this->targets->replaceFile($link, $file, self::FILE_DIR, $kind);
        $link->save();

        return $link;
    }

    public function removeFile(ProvaoLink $link): ProvaoLink
    {
        $this->targets->removeFile($link);
        $link->save();

        return $link;
    }

    public function deleteLink(ProvaoLink $link): void
    {
        $this->targets->deleteFile($link->file_path);
        $link->delete();
    }

    /** @param  array<int, int>  $ids */
    public function reorder(array $ids): void
    {
        Positions::reorder(ProvaoLink::query(), $ids);
    }
}
