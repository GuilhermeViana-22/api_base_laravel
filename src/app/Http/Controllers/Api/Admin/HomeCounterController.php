<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HomeCounterRequest;
use App\Http\Resources\HomeCounterResource;
use App\Models\HomeCounter;
use App\Services\HomeContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Contadores da página inicial (`/api/admin/home/counters`).
 *
 * São poucos e a tela edita todos juntos, por isso a listagem não é paginada.
 */
class HomeCounterController extends Controller
{
    public function __construct(private readonly HomeContentService $home)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return HomeCounterResource::collection(HomeCounter::ordered()->get());
    }

    public function store(HomeCounterRequest $request): JsonResponse
    {
        $contador = new HomeCounter($request->validated());
        $contador->position = $request->validated('position') ?? $this->home->nextPosition(HomeCounter::class);
        $contador->save();

        return (new HomeCounterResource($contador))->response()->setStatusCode(201);
    }

    public function update(HomeCounterRequest $request, HomeCounter $counter): HomeCounterResource
    {
        $counter->fill($request->validated())->save();

        return new HomeCounterResource($counter);
    }

    public function destroy(HomeCounter $counter): Response
    {
        $counter->delete();

        return response()->noContent();
    }
}
