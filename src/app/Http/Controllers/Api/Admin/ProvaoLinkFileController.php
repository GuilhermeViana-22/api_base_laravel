<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProvaoLinkFileRequest;
use App\Http\Resources\ProvaoLinkResource;
use App\Models\ProvaoLink;
use App\Services\ProvaoService;
use OpenApi\Attributes as OA;

/** Arquivo de um link do Provão (`/admin/provao-paulista/links/{link}/file`). */
class ProvaoLinkFileController extends Controller
{
    public function __construct(private readonly ProvaoService $provao)
    {
    }

    #[OA\Post(
        path: '/admin/provao-paulista/links/{link}/file',
        summary: 'Envia o arquivo de um link do Provão',
        description: 'O arquivo vira o destino do link: a `url` que ele tinha é apagada, e o arquivo anterior sai do disco.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/LinkFile'),
        tags: ['Painel · Provão Paulista'],
        parameters: [
            new OA\Parameter(name: 'link', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'O link, agora apontando para o arquivo.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/ProvaoLink'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function store(ProvaoLinkFileRequest $request, ProvaoLink $link): ProvaoLinkResource
    {
        return new ProvaoLinkResource($this->provao->replaceFile($link, $request->file('file'), $request->validated('kind')));
    }

    #[OA\Delete(
        path: '/admin/provao-paulista/links/{link}/file',
        summary: 'Tira o arquivo de um link do Provão',
        description: 'O arquivo sai do disco. Sem endereço, o link some do site até receber outro destino.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Provão Paulista'],
        parameters: [
            new OA\Parameter(name: 'link', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'O link, sem arquivo.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/ProvaoLink'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ],
    )]
    public function destroy(ProvaoLink $link): ProvaoLinkResource
    {
        return new ProvaoLinkResource($this->provao->removeFile($link));
    }
}
