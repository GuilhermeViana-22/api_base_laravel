<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImageUploadRequest;
use App\Http\Requests\Admin\TestimonialRequest;
use App\Http\Resources\TestimonialResource;
use App\Models\Testimonial;
use App\Services\HomeContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Depoimentos de ex-alunos (`/api/admin/home/testimonials`).
 *
 * CRUD completo, com a foto em rotas próprias. Listagem sem paginação: a tela
 * mostra todos, na ordem em que aparecem na página inicial.
 */
class TestimonialController extends Controller
{
    public function __construct(private readonly HomeContentService $home)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return TestimonialResource::collection(Testimonial::ordered()->get());
    }

    public function store(TestimonialRequest $request): JsonResponse
    {
        $depoimento = new Testimonial($request->validated());
        $depoimento->position = $request->validated('position') ?? $this->home->nextPosition(Testimonial::class);
        $depoimento->save();

        return (new TestimonialResource($depoimento))->response()->setStatusCode(201);
    }

    public function update(TestimonialRequest $request, Testimonial $testimonial): TestimonialResource
    {
        $testimonial->fill($request->validated())->save();

        return new TestimonialResource($testimonial);
    }

    /** DELETE: some o registro e a foto do disco. */
    public function destroy(Testimonial $testimonial): Response
    {
        $this->home->deleteTestimonial($testimonial);

        return response()->noContent();
    }

    /** POST multipart com o campo `file`; substitui a foto anterior. */
    public function storePhoto(ImageUploadRequest $request, Testimonial $testimonial): TestimonialResource
    {
        return new TestimonialResource($this->home->replaceTestimonialPhoto($testimonial, $request->file('file')));
    }

    public function destroyPhoto(Testimonial $testimonial): TestimonialResource
    {
        return new TestimonialResource($this->home->removeTestimonialPhoto($testimonial));
    }
}
