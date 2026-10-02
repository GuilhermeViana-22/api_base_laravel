<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LinkFileRequest;
use App\Http\Resources\VestibularBlockResource;
use App\Models\VestibularBlock;
use App\Services\VestibularService;
use OpenApi\Attributes as OA;

/** Arquivo de um bloco de link do vestibular (`/admin/vestibular/blocks/{block}/file`). */
class VestibularBlockFileController extends Controller
{
    public function __construct(private readonly VestibularService $vestibular)
    {
    }

    #[OA\Post(
        path: '/admin/vestibular/blocks/{block}/file',
        summary: 'Envia o arquivo de um link do vestibular',
        description: 'O arquivo vira o destino do link: a `url` é apagada, e o arquivo anterior sai do disco. Só vale para blocos do tipo link.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/LinkFile'),
        tags: ['Painel · Vestibular'],
        parameters: [
            new OA\Parameter(name: 'block', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'O bloco, agora apontando para o arquivo.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/VestibularBlock'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function store(LinkFileRequest $request, VestibularBlock $block): VestibularBlockResource
    {
        abort_unless($block->type === 'link', 422, 'Só blocos de link recebem arquivo.');

        return new VestibularBlockResource($this->vestibular->replaceFile($block, $request->file('file'), $request->validated('kind')));
    }

    #[OA\Delete(
        path: '/admin/vestibular/blocks/{block}/file',
        summary: 'Tira o arquivo de um link do vestibular',
        description: 'O arquivo sai do disco. Sem endereço, o link some do site até receber outro destino.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Vestibular'],
        parameters: [
            new OA\Parameter(name: 'block', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'O bloco, sem arquivo.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/VestibularBlock'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ],
    )]
    public function destroy(VestibularBlock $block): VestibularBlockResource
    {
        return new VestibularBlockResource($this->vestibular->removeFile($block));
    }
}
