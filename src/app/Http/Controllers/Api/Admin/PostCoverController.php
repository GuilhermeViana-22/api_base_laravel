<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImageUploadRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\PostService;
use OpenApi\Attributes as OA;

/** Foto de capa do post: sub-recurso único (`/posts/{post}/cover`). */
class PostCoverController extends Controller
{
    public function __construct(private readonly PostService $posts)
    {
    }

    /** POST multipart com o campo `file`; substitui a capa anterior. */
    #[OA\Post(
        path: '/admin/posts/{post}/cover',
        summary: 'Envia a foto de capa da notícia',
        description: 'Substitui a capa anterior, que sai do disco junto. A capa é o hero da notícia, '
            .'o card da grade e o destaque da página inicial.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/ImageUpload'),
        tags: ['Painel · Notícias'],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 42)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'A notícia, já com `image_url` novo.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Post'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function store(ImageUploadRequest $request, Post $post): PostResource
    {
        return new PostResource($this->posts->replaceCover($post, $request->file('file'))->load('author'));
    }

    #[OA\Delete(
        path: '/admin/posts/{post}/cover',
        summary: 'Tira a foto de capa da notícia',
        description: 'O arquivo sai do disco; a notícia continua, sem capa.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Notícias'],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 42)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'A notícia, agora com `image_url` nulo.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Post'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ],
    )]
    public function destroy(Post $post): PostResource
    {
        return new PostResource($this->posts->removeCover($post)->load('author'));
    }
}
