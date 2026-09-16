<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderCarouselSlidesRequest;
use App\Http\Requests\Admin\StoreCarouselSlideRequest;
use App\Http\Requests\Admin\UpdateCarouselSlideRequest;
use App\Http\Resources\CarouselSlideResource;
use App\Models\CarouselSlide;
use App\Services\CarouselSlideService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * CRUD do carrossel da página inicial (`/api/admin/carousel-slides`, exige login).
 *
 * Validação nos FormRequests, regras de escrita no CarouselSlideService.
 * A listagem não é paginada de propósito: são poucos slides e a tela precisa
 * de todos juntos para reordenar.
 */
class CarouselSlideController extends Controller
{
    public function __construct(private readonly CarouselSlideService $slides)
    {
    }

    /** GET /api/admin/carousel-slides */
    public function index(): AnonymousResourceCollection
    {
        return CarouselSlideResource::collection(CarouselSlide::ordered()->get());
    }

    /** POST /api/admin/carousel-slides */
    public function store(StoreCarouselSlideRequest $request): JsonResponse
    {
        $slide = $this->slides->create($request->validated());

        return (new CarouselSlideResource($slide))->response()->setStatusCode(201);
    }

    /** GET /api/admin/carousel-slides/{carousel_slide} */
    public function show(CarouselSlide $carouselSlide): CarouselSlideResource
    {
        return new CarouselSlideResource($carouselSlide);
    }

    /** PATCH /api/admin/carousel-slides/{carousel_slide} */
    public function update(UpdateCarouselSlideRequest $request, CarouselSlide $carouselSlide): CarouselSlideResource
    {
        return new CarouselSlideResource($this->slides->update($carouselSlide, $request->validated()));
    }

    /** DELETE /api/admin/carousel-slides/{carousel_slide}: some o registro e a imagem. */
    public function destroy(CarouselSlide $carouselSlide): Response
    {
        $this->slides->delete($carouselSlide);

        return response()->noContent();
    }

    /** POST /api/admin/carousel-slides/reorder: recebe os ids na ordem desejada. */
    public function reorder(ReorderCarouselSlidesRequest $request): AnonymousResourceCollection
    {
        $this->slides->reorder($request->validated('ids'));

        return CarouselSlideResource::collection(CarouselSlide::ordered()->get());
    }
}
