<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImageUploadRequest;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use App\Services\BannerService;

/** Foto de fundo do banner: sub-recurso único (`/banners/{key}/image`). */
class BannerImageController extends Controller
{
    public function __construct(private readonly BannerService $banners)
    {
    }

    /** POST multipart com o campo `file`; substitui a foto anterior. */
    public function store(ImageUploadRequest $request, string $key): BannerResource
    {
        return new BannerResource($this->banners->replaceImage(Banner::forKey($key), $request->file('file')));
    }

    /** DELETE: tira a foto; o site mostra o fundo escuro. */
    public function destroy(string $key): BannerResource
    {
        return new BannerResource($this->banners->removeImage(Banner::forKey($key)));
    }
}
