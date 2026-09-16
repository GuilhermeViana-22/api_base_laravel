<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicCarouselSlideResource;
use App\Models\CarouselSlide;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Carrossel da página inicial, como o site lê (`/api/carousel-slides`).
 *
 * Só slides ativos e com imagem, na ordem definida no painel.
 */
class CarouselSlideController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return PublicCarouselSlideResource::collection(CarouselSlide::visible()->ordered()->get());
    }
}
