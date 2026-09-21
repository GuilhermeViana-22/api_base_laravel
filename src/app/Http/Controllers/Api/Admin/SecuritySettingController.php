<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SecuritySettingRequest;
use App\Support\SecuritySettings;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Segurança do painel (`/api/admin/settings/security`).
 *
 * Três ajustes que valem para todo mundo: quanto tempo a sessão dura, o nível
 * exigido das senhas e a chave do segundo fator, que fica guardada mas ainda
 * não tem efeito (o projeto não tem 2FA).
 */
class SecuritySettingController extends Controller
{
    #[OA\Get(
        path: '/admin/settings/security',
        summary: 'Ajustes de segurança do painel',
        description: 'Junto com os valores vão as opções que a tela oferece: tempos de sessão e níveis de '
            .'complexidade de senha, com o texto de cada um. `require_two_factor` é guardado mas ainda não '
            .'tem efeito, porque o projeto não tem segundo fator.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Configurações'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Os ajustes e as opções da tela.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/SecuritySettings'),
                    new OA\Property(property: 'session_timeouts', type: 'array', items: new OA\Items(type: 'integer'), example: [15, 30, 60, 120, 240, 480]),
                    new OA\Property(property: 'complexities', type: 'array', items: new OA\Items(ref: '#/components/schemas/PasswordComplexity')),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
        ],
    )]
    public function show(): JsonResponse
    {
        return response()->json([
            'data' => SecuritySettings::all(),
            'session_timeouts' => SecuritySettings::SESSION_TIMEOUTS,
            'complexities' => SecuritySettings::complexityOptions(),
        ]);
    }

    #[OA\Put(
        path: '/admin/settings/security',
        summary: 'Salva os ajustes de segurança',
        description: 'O tempo de sessão passa a valer no próximo login (validade do token) e o painel usa o '
            .'mesmo número para deslogar quem ficou parado. A complexidade vale no cadastro a partir da '
            .'próxima senha digitada.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'session_timeout_minutes', type: 'integer', enum: [15, 30, 60, 120, 240, 480], example: 30),
                    new OA\Property(property: 'password_complexity', type: 'string', enum: ['basica', 'media', 'forte'], example: 'media'),
                    new OA\Property(property: 'require_two_factor', description: 'Guardado, ainda sem efeito.', type: 'boolean', example: false),
                ],
                type: 'object',
            ),
        ),
        tags: ['Painel · Configurações'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ajustes salvos.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/SecuritySettings'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function update(SecuritySettingRequest $request): JsonResponse
    {
        return response()->json(['data' => SecuritySettings::save($request->validated())]);
    }
}
