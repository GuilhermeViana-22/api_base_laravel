<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImageUploadRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\PostService;

/** Foto de capa do post: sub-recurso único (`/posts/{post}/cover`). */
class PostCoverController extends Controller
{
    public function __construct(private readonly PostService $posts)
    {
    }

    /** POST multipart com o campo `file`; substitui a capa anterior. */
    public function store(ImageUploadRequest $request, Post $post): PostResource
    {
        return new PostResource($this->posts->replaceCover($post, $request->file('file'))->load('author'));
    }

    public function destroy(Post $post): PostResource
    {
        return new PostResource($this->posts->removeCover($post)->load('author'));
    }
}
