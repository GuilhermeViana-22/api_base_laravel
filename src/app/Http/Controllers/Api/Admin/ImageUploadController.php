<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImageUploadRequest;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Upload das imagens inseridas no corpo do texto (TinyMCE).
 * Responde `{ "location": url }`, o formato que o editor espera.
 */
class ImageUploadController extends Controller
{
    public function __construct(private readonly PostService $posts)
    {
    }

    #[OA\Post(
        path: '/admin/uploads/images',
        summary: 'Envia uma imagem do corpo do texto',
        description: 'Usada pelo editor (TinyMCE) quando se insere uma imagem no meio da notícia. '
            .'A resposta é `{ "location": url }`, o formato que o editor espera — '
            .'não segue o `data` do resto da API.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/ImageUpload'),
        tags: ['Painel · Uploads'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Imagem guardada.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'location', type: 'string', example: 'http://localhost:8019/storage/posts/conteudo/foto.webp'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function store(ImageUploadRequest $request): JsonResponse
    {
        $url = $this->posts->storeContentImage($request->file('file'));

        return response()->json(['location' => $url], 201);
    }
}
