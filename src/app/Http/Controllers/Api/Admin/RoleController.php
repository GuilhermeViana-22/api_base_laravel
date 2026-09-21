<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Support\PanelResources;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

/**
 * Papéis do painel e a matriz de permissões (`/api/admin/roles`).
 *
 * A listagem manda junto a árvore de telas e as ações (`modules`, `actions`):
 * é ela que desenha as linhas e colunas da tela, então o front não guarda
 * cópia nenhuma, tela nova no backend aparece sozinha na matriz.
 */
class RoleController extends Controller
{
    #[OA\Get(
        path: '/admin/roles',
        summary: 'Papéis do painel e o catálogo da matriz',
        description: 'Junto com os papéis vão `modules` e `actions`: as linhas e as colunas da matriz de '
            .'permissões, na ordem em que a tela deve mostrá-las. O papel Master vem com `is_master` e '
            .'`locked`: ele ignora a matriz e não pode ser editado.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Equipe'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Os papéis e o catálogo.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Role')),
                    new OA\Property(property: 'modules', type: 'array', items: new OA\Items(ref: '#/components/schemas/PanelResource')),
                    new OA\Property(property: 'actions', type: 'array', items: new OA\Items(ref: '#/components/schemas/PanelAction')),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
        ],
    )]
    public function index(): AnonymousResourceCollection
    {
        $papeis = Role::withCount('users')->orderByDesc('is_master')->orderBy('name')->get();

        return RoleResource::collection($papeis)->additional([
            'modules' => PanelResources::tree(),
            'actions' => PanelResources::actions(),
        ]);
    }

    #[OA\Post(
        path: '/admin/roles',
        summary: 'Cria um papel',
        description: 'O `slug` sai do nome quando não vem no corpo. Marcar `create`, `update` ou `delete` '
            .'liga o `view` do mesmo módulo: não existe editar o que não se pode ver.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'abilities'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 60, minLength: 3, example: 'Editor de Notícias'),
                    new OA\Property(property: 'description', type: 'string', maxLength: 255, nullable: true, example: 'Publica e edita notícias, sem excluir.'),
                    new OA\Property(
                        property: 'abilities',
                        description: 'Matriz módulo => ações, como a tela mostra.',
                        type: 'object',
                        example: ['posts' => ['view', 'create', 'update'], 'home' => ['view']],
                    ),
                ],
                type: 'object',
            ),
        ),
        tags: ['Painel · Equipe'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Papel criado.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Role'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function store(RoleRequest $request): JsonResponse
    {
        $papel = Role::create([
            'name' => $request->validated('name'),
            'slug' => $request->validated('slug') ?? $this->slugLivre($request->validated('name')),
            'description' => $request->validated('description'),
            'abilities' => $request->abilities(),
        ]);

        return (new RoleResource($papel))->response()->setStatusCode(201);
    }

    #[OA\Patch(
        path: '/admin/roles/{role}',
        summary: 'Atualiza um papel (envio parcial)',
        description: 'Só os campos presentes no corpo mudam. O papel Master responde 422: é do sistema.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 60, minLength: 3, example: 'Editor de Notícias'),
                    new OA\Property(property: 'description', type: 'string', maxLength: 255, nullable: true),
                    new OA\Property(
                        property: 'abilities',
                        description: 'Matriz módulo => ações, como a tela mostra.',
                        type: 'object',
                        example: ['posts' => ['view', 'update']],
                    ),
                ],
                type: 'object',
            ),
        ),
        tags: ['Painel · Equipe'],
        parameters: [
            new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 3)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Atualizado.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Role'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function update(RoleRequest $request, Role $role): RoleResource
    {
        $role->fill($request->safe()->only(['name', 'description', 'slug']));

        if ($request->has('abilities')) {
            $role->abilities = $request->abilities();
        }

        $role->save();

        return new RoleResource($role);
    }

    #[OA\Delete(
        path: '/admin/roles/{role}',
        summary: 'Exclui um papel',
        description: 'Recusa (422) se for o Master ou se ainda houver gente usando o papel, antes é preciso '
            .'mover essas pessoas para outro.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Equipe'],
        parameters: [
            new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 3)),
        ],
        responses: [
            new OA\Response(response: 204, ref: '#/components/responses/NoContent'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(
                response: 422,
                description: 'Papel de sistema ou ainda em uso.',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ValidationError',
                    example: [
                        'message' => 'Ainda há 3 pessoas com este papel.',
                        'errors' => ['role' => ['Ainda há 3 pessoas com este papel.']],
                    ],
                ),
            ),
        ],
    )]
    public function destroy(Role $role): Response
    {
        if ($role->locked) {
            abort(422, 'O papel Master é do sistema e não pode ser excluído.');
        }

        $emUso = $role->users()->count();

        if ($emUso > 0) {
            $pessoas = $emUso === 1 ? '1 pessoa' : "{$emUso} pessoas";
            abort(422, "Ainda há {$pessoas} com este papel. Mova essas contas para outro papel antes de excluir.");
        }

        $role->delete();

        return response()->noContent();
    }

    /** Slug a partir do nome, com sufixo numérico se já existir outro igual. */
    private function slugLivre(string $nome): string
    {
        $base = Str::slug($nome) ?: 'papel';
        $slug = $base;
        $sufixo = 2;

        while (Role::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$sufixo}";
            $sufixo++;
        }

        return $slug;
    }
}
