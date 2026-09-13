<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImageUploadRequest;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;

/**
 * Upload das imagens inseridas no corpo do texto (TinyMCE).
 * Responde `{ "location": url }`, o formato que o editor espera.
 */
class ImageUploadController extends Controller
{
    public function __construct(private readonly PostService $posts)
    {
    }

    public function store(ImageUploadRequest $request): JsonResponse
    {
        $url = $this->posts->storeContentImage($request->file('file'));

        return response()->json(['location' => $url], 201);
    }
}
