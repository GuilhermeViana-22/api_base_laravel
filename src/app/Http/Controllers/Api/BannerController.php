<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Models\Banner;

/** Leitura pública dos banners (sem login). */
class BannerController extends Controller
{
    /** GET /api/banners/{key}: textos e foto do hero da página. */
    public function show(string $key): BannerResource
    {
        return new BannerResource(Banner::forKey($key));
    }
}
