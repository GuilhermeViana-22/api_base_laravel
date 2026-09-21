<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserPermissionRequest;
use App\Models\Role;
use App\Models\User;
use App\Support\PanelResources;
use App\Support\PermissionResolver;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Permissões de uma pessoa (`/api/admin/users/{user}/permissions`).
 *
 * É o que abre no "Gerenciar permissões" da lista de usuários: o papel dela, a
 * árvore de telas do painel, o que o papel já concede em cada uma e as
 * exceções marcadas só para ela.
 *
 * O papel continua sendo a base; a exceção é sempre por tela, inclusive para
 * menos, uma exceção vazia tira a pessoa daquela tela mesmo com o papel
 * liberando a área inteira.
 */
class UserPermissionController extends Controller
{
    #[OA\Get(
        path: '/admin/users/{user}/permissions',
        summary: 'Permissões de uma pessoa, com o papel e as exceções',
        description: 'Devolve a árvore de telas (`resources`), as ações (`actions`), o papel da pessoa com a '
            .'matriz dele (`role`), as exceções marcadas só para ela (`overrides`) e o resultado final já '
            .'somado (`effective`), que é o que vale na hora de abrir uma tela.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Equipe'],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 7)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'As permissões da pessoa.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/UserPermissions'),
                    new OA\Property(property: 'resources', type: 'array', items: new OA\Items(ref: '#/components/schemas/PanelResource')),
                    new OA\Property(property: 'actions', type: 'array', items: new OA\Items(ref: '#/components/schemas/PanelAction')),
                    new OA\Property(property: 'roles', description: 'Papéis que podem ser escolhidos no modal.', type: 'array', items: new OA\Items(ref: '#/components/schemas/Role')),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ],
    )]
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'data' => $this->permissoesDe($user),
            'resources' => PanelResources::tree(),
            'actions' => PanelResources::actions(),
            'roles' => Role::orderByDesc('is_master')->orderBy('name')->get()
                ->map(fn (Role $papel) => [
                    'id' => $papel->id,
                    'name' => $papel->name,
                    'slug' => $papel->slug,
                    'description' => $papel->description,
                    'abilities' => (object) $papel->abilities,
                    'is_master' => $papel->is_master,
                    'locked' => $papel->locked,
                    'users_count' => $papel->users()->count(),
                    'updated_at' => $papel->updated_at,
                ]),
        ]);
    }

    #[OA\Put(
        path: '/admin/users/{user}/permissions',
        summary: 'Salva o papel e as exceções de uma pessoa',
        description: 'O corpo traz o papel (`role_id`, nulo tira o acesso ao painel) e as exceções '
            .'(`overrides`), sempre inteiras: o que não vier some. Uma tela com lista vazia é uma exceção '
            .'válida, significa "não enxerga", mesmo que o papel libere a área. Ninguém edita as próprias '
            .'permissões, e o último Master não pode ser rebaixado.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'role_id', description: 'Papel base; `null` tira do painel.', type: 'integer', nullable: true, example: 3),
                    new OA\Property(
                        property: 'overrides',
                        description: 'Exceções por tela. `[]` numa tela significa "não enxerga".',
                        type: 'object',
                        example: ['pages.institucional.historia' => ['view', 'update'], 'pages.transparencia' => []],
                    ),
                ],
                type: 'object',
            ),
        ),
        tags: ['Painel · Equipe'],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 7)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Permissões salvas.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/UserPermissions'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function update(UserPermissionRequest $request, User $user): JsonResponse
    {
        $user->fill([
            'role_id' => $request->validated('role_id'),
            'abilities' => $request->overrides(),
        ])->save();

        return response()->json(['data' => $this->permissoesDe($user->fresh())]);
    }

    /** @return array<string, mixed> */
    private function permissoesDe(User $usuario): array
    {
        $usuario->loadMissing('role');

        return [
            'user' => [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
            ],
            'role' => $usuario->role ? [
                'id' => $usuario->role->id,
                'name' => $usuario->role->name,
                'is_master' => $usuario->role->is_master,
                'abilities' => (object) $usuario->role->abilities,
            ] : null,
            'overrides' => (object) ($usuario->abilities ?? []),
            'effective' => (object) PermissionResolver::effective($usuario),
            'exceptions_count' => PermissionResolver::exceptionCount($usuario),
        ];
    }
}
