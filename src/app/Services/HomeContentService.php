<?php

namespace App\Services;

use App\Models\HomeSection;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Escrita do conteúdo da página inicial (blocos, contadores e depoimentos).
 *
 * Os controllers só validam e respondem; o que mexe em banco ou disco fica
 * aqui — inclusive a limpeza do HTML do bloco de vídeo, que vem de um editor
 * rico e por isso nunca é salvo como chegou.
 */
class HomeContentService
{
    /** Pastas (disco `public`) das imagens da página inicial. */
    private const SECTION_IMAGE_DIR = 'home/secoes';
    private const TESTIMONIAL_PHOTO_DIR = 'home/depoimentos';

    public function __construct(private readonly ContentSanitizer $sanitizer)
    {
    }

    /** Atualiza o bloco; só o de vídeo tem texto em HTML, que é sanitizado. */
    public function updateSection(HomeSection $section, array $data): HomeSection
    {
        if (array_key_exists('description', $data) && $section->key === HomeSection::VIDEO) {
            $data['description'] = $this->sanitizer->sanitize($data['description']);
        }

        // Bloco sem botão: limpa o que tiver sobrado de quando ele tinha um.
        if (in_array($section->key, HomeSection::WITHOUT_BUTTON, true)) {
            $data['button_label'] = null;
            $data['button_route'] = null;
        }

        $section->fill($data)->save();

        return $section;
    }

    /** Grava a nova imagem do bloco, apagando a anterior do disco. */
    public function replaceSectionImage(HomeSection $section, UploadedFile $file): HomeSection
    {
        return $this->replaceFile($section, 'image_path', self::SECTION_IMAGE_DIR, $file);
    }

    public function removeSectionImage(HomeSection $section): HomeSection
    {
        return $this->removeFile($section, 'image_path');
    }

    /** Grava a nova foto do depoimento, apagando a anterior do disco. */
    public function replaceTestimonialPhoto(Testimonial $testimonial, UploadedFile $file): Testimonial
    {
        return $this->replaceFile($testimonial, 'photo_path', self::TESTIMONIAL_PHOTO_DIR, $file);
    }

    public function removeTestimonialPhoto(Testimonial $testimonial): Testimonial
    {
        return $this->removeFile($testimonial, 'photo_path');
    }

    /** Exclui o depoimento e a foto do disco. */
    public function deleteTestimonial(Testimonial $testimonial): void
    {
        $this->deleteFile($testimonial->photo_path);
        $testimonial->delete();
    }

    /**
     * Próxima posição livre de uma lista ordenada (contadores, depoimentos):
     * o item novo entra no fim, sem empurrar o que já está no ar.
     *
     * @param  class-string<Model>  $modelo
     */
    public function nextPosition(string $modelo): int
    {
        return (int) $modelo::max('position') + 1;
    }

    private function replaceFile(Model $registro, string $coluna, string $pasta, UploadedFile $file): Model
    {
        $this->deleteFile($registro->{$coluna});

        $registro->{$coluna} = $file->store($pasta, 'public');
        $registro->save();

        return $registro;
    }

    private function removeFile(Model $registro, string $coluna): Model
    {
        $this->deleteFile($registro->{$coluna});

        $registro->{$coluna} = null;
        $registro->save();

        return $registro;
    }

    private function deleteFile(?string $caminho): void
    {
        if ($caminho) {
            Storage::disk('public')->delete($caminho);
        }
    }
}
