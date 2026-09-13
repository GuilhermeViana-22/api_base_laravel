<?php

namespace App\Services;

use App\Models\Banner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** Escrita dos banners: textos e foto de fundo (arquivo no disco `public`). */
class BannerService
{
    /** Pasta (disco `public`) das fotos de fundo dos banners. */
    private const IMAGE_DIR = 'banners';

    /** Atualiza rótulo e título. */
    public function updateTexts(Banner $banner, array $data): Banner
    {
        $banner->update($data);

        return $banner;
    }

    /** Grava a nova foto de fundo, apagando a anterior do disco. */
    public function replaceImage(Banner $banner, UploadedFile $file): Banner
    {
        $this->deleteImageFile($banner);

        $banner->image_path = $file->store(self::IMAGE_DIR, 'public');
        $banner->save();

        return $banner;
    }

    /** Remove a foto de fundo; o site volta ao fundo escuro. */
    public function removeImage(Banner $banner): Banner
    {
        $this->deleteImageFile($banner);

        $banner->image_path = null;
        $banner->save();

        return $banner;
    }

    private function deleteImageFile(Banner $banner): void
    {
        if ($banner->image_path) {
            Storage::disk('public')->delete($banner->image_path);
        }
    }
}
