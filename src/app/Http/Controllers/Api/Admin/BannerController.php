<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBannerRequest;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use App\Services\BannerService;

/**
 * Edição dos banners pelo painel (`/api/admin/banners/{key}`, exige login).
 * Recurso único por chave: só lê e atualiza (não há criar nem excluir).
 */
class BannerController extends Controller
{
    public function __construct(private readonly BannerService $banners)
    {
    }

    /** GET /api/admin/banners/{key} */
    public function show(string $key): BannerResource
    {
        return new BannerResource(Banner::forKey($key));
    }

    /** PUT /api/admin/banners/{key}: rótulo e título. */
    public function update(UpdateBannerRequest $request, string $key): BannerResource
    {
        return new BannerResource($this->banners->updateTexts(Banner::forKey($key), $request->validated()));
    }
}
