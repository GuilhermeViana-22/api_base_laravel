<?php

namespace App\Services;

use App\Models\CarouselSlide;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Regras de escrita do carrossel, usadas pelos controllers do painel.
 *
 * Os controllers só validam e respondem; posição, arquivo no disco e ordem
 * ficam aqui.
 */
class CarouselSlideService
{
    /** Pasta (disco `public`) das imagens dos slides. */
    private const IMAGE_DIR = 'carrossel';

    /** Cria o slide no fim da fila, para não empurrar os que já estão no ar. */
    public function create(array $data): CarouselSlide
    {
        $slide = new CarouselSlide($data);
        $slide->position = (int) CarouselSlide::max('position') + 1;
        $slide->save();

        return $slide;
    }

    /** Atualiza só os campos enviados (o PATCH do painel pode ser parcial). */
    public function update(CarouselSlide $slide, array $data): CarouselSlide
    {
        $slide->fill($data)->save();

        return $slide;
    }

    /** Exclui o slide e a imagem do disco. */
    public function delete(CarouselSlide $slide): void
    {
        $this->deleteImageFile($slide);
        $slide->delete();
    }

    /** Grava a nova imagem, apagando a anterior do disco. */
    public function replaceImage(CarouselSlide $slide, UploadedFile $file): CarouselSlide
    {
        $this->deleteImageFile($slide);

        $slide->image_path = $file->store(self::IMAGE_DIR, 'public');
        $slide->save();

        return $slide;
    }

    /** Tira a imagem; sem ela o slide sai do site mesmo estando ativo. */
    public function removeImage(CarouselSlide $slide): CarouselSlide
    {
        $this->deleteImageFile($slide);

        $slide->image_path = null;
        $slide->save();

        return $slide;
    }

    /**
     * Regrava as posições na ordem dos ids recebidos.
     *
     * Numa transação: uma ordem pela metade deixaria o carrossel embaralhado.
     * Ids que não estão na lista vão para o fim, mantendo a ordem relativa.
     *
     * @param  array<int, int>  $ids
     */
    public function reorder(array $ids): void
    {
        DB::transaction(function () use ($ids) {
            foreach (array_values($ids) as $posicao => $id) {
                CarouselSlide::whereKey($id)->update(['position' => $posicao + 1]);
            }

            CarouselSlide::whereNotIn('id', $ids)
                ->orderBy('position')
                ->orderBy('id')
                ->get()
                ->each(fn (CarouselSlide $slide, int $indice) => $slide->update([
                    'position' => count($ids) + $indice + 1,
                ]));
        });
    }

    private function deleteImageFile(CarouselSlide $slide): void
    {
        if ($slide->image_path) {
            Storage::disk('public')->delete($slide->image_path);
        }
    }
}
