<?php

namespace App\Services;

use App\Models\VestibularBlock;
use App\Models\VestibularSection;
use App\Support\Positions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Regras de escrita da página /vestibular, usadas pelo painel.
 *
 * Seções e blocos entram no fim da lista; a ordem muda por reorder. O HTML
 * dos blocos de texto passa pelo ContentSanitizer. O destino dos blocos de
 * link (URL ou arquivo) segue o LinkTargetService, o mesmo do Provão.
 */
class VestibularService
{
    public const FILE_DIR = 'vestibular/arquivos';

    public function __construct(
        private readonly ContentSanitizer $sanitizer,
        private readonly LinkTargetService $targets,
    ) {
    }

    public function createSection(array $data): VestibularSection
    {
        $secao = new VestibularSection($data);
        $secao->position = Positions::next(VestibularSection::query());
        $secao->divider ??= 'none';
        $secao->save();

        return $secao;
    }

    public function updateSection(VestibularSection $section, array $data): VestibularSection
    {
        $section->fill($data)->save();

        return $section;
    }

    /** Some com os blocos (cascade no banco) e com os arquivos deles no disco. */
    public function deleteSection(VestibularSection $section): void
    {
        DB::transaction(function () use ($section) {
            $section->blocks()->whereNotNull('file_path')->pluck('file_path')
                ->each(fn (string $caminho) => $this->targets->deleteFile($caminho));
            $section->delete();
        });
    }

    /** @param  array<int, int>  $ids */
    public function reorderSections(array $ids): void
    {
        Positions::reorder(VestibularSection::query(), $ids);
    }

    /** Cria o bloco no fim da seção; um link pode vir com o arquivo junto. */
    public function createBlock(VestibularSection $section, array $data, ?UploadedFile $file): VestibularBlock
    {
        unset($data['file']);
        $bloco = new VestibularBlock($this->sanitized($data));
        $bloco->section_id = $section->id;
        $bloco->position = Positions::next($section->blocks()->getQuery());

        if ($bloco->type === 'title') {
            $bloco->style ??= 'default';
        }

        if ($bloco->type === 'link') {
            $this->targets->fillNew($bloco, $data, $file, self::FILE_DIR);
        }

        $bloco->save();

        return $bloco;
    }

    public function updateBlock(VestibularBlock $block, array $data): VestibularBlock
    {
        // `kind` vazio é o mesmo que não mandar: o tipo sai do destino.
        if (array_key_exists('kind', $data) && $data['kind'] === null) {
            unset($data['kind']);
        }

        $block->fill($this->sanitized($data));

        if ($block->type === 'link') {
            $this->targets->applyUpdate($block, $data);
        }

        $block->save();

        return $block;
    }

    public function deleteBlock(VestibularBlock $block): void
    {
        $this->targets->deleteFile($block->file_path);
        $block->delete();
    }

    /** @param  array<int, int>  $ids */
    public function reorderBlocks(VestibularSection $section, array $ids): void
    {
        Positions::reorder($section->blocks()->getQuery()->reorder(), $ids);
    }

    public function replaceFile(VestibularBlock $block, UploadedFile $file, ?string $kind = null): VestibularBlock
    {
        $this->targets->replaceFile($block, $file, self::FILE_DIR, $kind);
        $block->save();

        return $block;
    }

    public function removeFile(VestibularBlock $block): VestibularBlock
    {
        $this->targets->removeFile($block);
        $block->save();

        return $block;
    }

    /** O site renderiza o texto com v-html: nunca gravar HTML sem filtro. */
    private function sanitized(array $data): array
    {
        if (array_key_exists('html', $data) && $data['html'] !== null) {
            $data['html'] = $this->sanitizer->sanitize($data['html']);
        }

        return $data;
    }
}
