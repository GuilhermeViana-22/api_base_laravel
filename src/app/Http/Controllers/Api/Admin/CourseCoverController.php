<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImageUploadRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Services\CourseService;
use OpenApi\Attributes as OA;

/** Capa do card da vitrine `/cursos` (`/courses/{course}/cover`). */
class CourseCoverController extends Controller
{
    public function __construct(private readonly CourseService $courses)
    {
    }

    #[OA\Post(
        path: '/admin/courses/{course}/cover',
        summary: 'Envia a capa do card na vitrine de cursos',
        description: 'Substitui a capa anterior. A foto só aparece em `/cursos`; a página do curso não a usa.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/ImageUpload'),
        tags: ['Painel · Cursos'],
        parameters: [
            new OA\Parameter(name: 'course', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'O curso, já com `image_url` novo.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Course'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function store(ImageUploadRequest $request, Course $course): CourseResource
    {
        return new CourseResource($this->courses->replaceCover($course, $request->file('file')));
    }

    #[OA\Delete(
        path: '/admin/courses/{course}/cover',
        summary: 'Tira a capa do card na vitrine de cursos',
        description: 'O arquivo sai do disco; o curso continua, sem capa no card.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Cursos'],
        parameters: [
            new OA\Parameter(name: 'course', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'O curso, agora com `image_url` nulo.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Course'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ],
    )]
    public function destroy(Course $course): CourseResource
    {
        return new CourseResource($this->courses->removeCover($course));
    }
}
