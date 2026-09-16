<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImageUploadRequest;
use App\Http\Requests\Admin\UpdateHomeSectionRequest;
use App\Http\Resources\HomeSectionResource;
use App\Models\HomeSection;
use App\Services\HomeContentService;

/**
 * Blocos de chave fixa da página inicial (`/api/admin/home/sections/{key}`).
 *
 * Recurso único por chave: só lê e atualiza (não há criar nem excluir). A
 * imagem tem rotas próprias, como nos banners e nas capas de notícia.
 */
class HomeSectionController extends Controller
{
    public function __construct(private readonly HomeContentService $home)
    {
    }

    /** GET /api/admin/home/sections/{key} */
    public function show(string $key): HomeSectionResource
    {
        return new HomeSectionResource(HomeSection::forKey($key));
    }

    /** PATCH /api/admin/home/sections/{key} */
    public function update(UpdateHomeSectionRequest $request, string $key): HomeSectionResource
    {
        return new HomeSectionResource($this->home->updateSection(HomeSection::forKey($key), $request->validated()));
    }

    /** POST multipart com o campo `file`; substitui a imagem anterior. */
    public function storeImage(ImageUploadRequest $request, string $key): HomeSectionResource
    {
        return new HomeSectionResource(
            $this->home->replaceSectionImage(HomeSection::forKey($key), $request->file('file')),
        );
    }

    public function destroyImage(string $key): HomeSectionResource
    {
        return new HomeSectionResource($this->home->removeSectionImage(HomeSection::forKey($key)));
    }
}
