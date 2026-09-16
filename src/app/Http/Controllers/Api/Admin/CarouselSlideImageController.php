<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImageUploadRequest;
use App\Http\Resources\CarouselSlideResource;
use App\Models\CarouselSlide;
use App\Services\CarouselSlideService;

/** Imagem do slide: sub-recurso único (`/carousel-slides/{slide}/image`). */
class CarouselSlideImageController extends Controller
{
    public function __construct(private readonly CarouselSlideService $slides)
    {
    }

    /** POST multipart com o campo `file`; substitui a imagem anterior. */
    public function store(ImageUploadRequest $request, CarouselSlide $carouselSlide): CarouselSlideResource
    {
        return new CarouselSlideResource($this->slides->replaceImage($carouselSlide, $request->file('file')));
    }

    public function destroy(CarouselSlide $carouselSlide): CarouselSlideResource
    {
        return new CarouselSlideResource($this->slides->removeImage($carouselSlide));
    }
}
