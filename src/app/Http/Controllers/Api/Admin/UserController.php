<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexUsersRequest;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Http\Resources\AdminUserResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Usuários do painel (`/api/admin/users`, exige login).
 *
 * Só leitura e troca de situação: contas nascem pelo cadastro público e não
 * são criadas nem excluídas por aqui.
 */
class UserController extends Controller
{
    /**
     * GET /api/admin/users?name=&email=&polo=&status=&registered_from=&registered_to=&sort=&per_page=&page=
     *
     * Junto com a página pedida vão dois blocos que a tela usa e que não
     * dependem do filtro: `counts` (quantos em cada situação, para os cards)
     * e `polos` (os polos já cadastrados, para o select do filtro) — assim o
     * front não guarda nenhuma dessas listas.
     */
    #[OA\Get(
        path: '/admin/users',
        summary: 'Contas do painel, paginadas',
        description: 'Junto com a página pedida vão dois blocos que não dependem do filtro: `counts` '
            .'(quantos em cada situação, para os cards) e `polos` (os polos já cadastrados, para o select do '
            .'filtro) — assim o front não guarda nenhuma dessas listas. As contas nascem pelo cadastro público: '
            .'não se cria nem se exclui usuário por aqui.',
        security: [['bearerAuth' => []]],
        tags: ['Painel · Usuários'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/page'),
            new OA\Parameter(
                name: 'name',
                description: 'Procura por parte do nome.',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', maxLength: 255, example: 'maria'),
            ),
            new OA\Parameter(
                name: 'email',
                description: 'Procura por parte do e-mail.',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', maxLength: 255),
            ),
            new OA\Parameter(
                name: 'polo',
                description: 'Procura por parte do polo.',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', maxLength: 120, example: 'Santos'),
            ),
            new OA\Parameter(
                name: 'status',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['active', 'inactive', 'dismissed', 'on_vacation'], example: 'active'),
            ),
            new OA\Parameter(
                name: 'registered_from',
                description: 'Cadastro a partir desta data (o dia inteiro conta).',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date', example: '2026-01-01'),
            ),
            new OA\Parameter(
                name: 'registered_to',
                description: 'Cadastro até esta data (o dia inteiro conta). Não pode ser antes de `registered_from`.',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date', example: '2026-09-16'),
            ),
            new OA\Parameter(
                name: 'access',
                description: '`panel` traz só quem tem papel (tela Equipe); `site`, só os cadastros do site.',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['panel', 'site'], example: 'panel'),
            ),
            new OA\Parameter(
                name: 'role_id',
                description: 'Só as contas com este papel.',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 3),
            ),
            new OA\Parameter(
                name: 'sort',
                description: '`recent` (padrão), `oldest`, `name` ou `polo`.',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['recent', 'oldest', 'name', 'polo'], example: 'recent'),
            ),
            new OA\Parameter(
                name: 'per_page',
                description: 'Itens por página. Padrão 15.',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', maximum: 100, minimum: 1, example: 15),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'A página pedida, mais os totais e os polos.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/AdminUser')),
                    new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                    new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                    new OA\Property(property: 'counts', ref: '#/components/schemas/UserCounts'),
                    new OA\Property(
                        property: 'polos',
                        description: 'Os polos já usados por alguém, em ordem alfabética.',
                        type: 'array',
                        items: new OA\Items(type: 'string'),
                        example: ['Santos', 'São Paulo'],
                    ),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function index(IndexUsersRequest $request): AnonymousResourceCollection
    {
        $filtros = $request->validated();
        [$coluna, $direcao] = $request->sorting();

        $pagina = User::query()
            ->with('role')
            // A tela Equipe pede ?access=panel; a de Usuários não manda nada
            // e continua vendo todo mundo.
            ->when(($filtros['access'] ?? null) === 'panel', fn ($q) => $q->whereNotNull('role_id'))
            ->when(($filtros['access'] ?? null) === 'site', fn ($q) => $q->whereNull('role_id'))
            ->when($filtros['role_id'] ?? null, fn ($q, $papel) => $q->where('role_id', $papel))
            ->when($filtros['name'] ?? null, fn ($q, $nome) => $q->where('name', 'like', "%{$nome}%"))
            ->when($filtros['email'] ?? null, fn ($q, $email) => $q->where('email', 'like', "%{$email}%"))
            ->when($filtros['polo'] ?? null, fn ($q, $polo) => $q->where('polo', 'like', "%{$polo}%"))
            ->when($filtros['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            // A data de cadastro é filtrada pelo dia inteiro, nos dois extremos.
            ->when($filtros['registered_from'] ?? null, fn ($q, $de) => $q->whereDate('created_at', '>=', $de))
            ->when($filtros['registered_to'] ?? null, fn ($q, $ate) => $q->whereDate('created_at', '<=', $ate))
            ->orderBy($coluna, $direcao)
            ->orderByDesc('id')
            ->paginate($request->perPage())
            ->withQueryString();

        return AdminUserResource::collection($pagina)->additional([
            'counts' => $this->counts(),
            'polos' => $this->polos(),
        ]);
    }

    /** PATCH /api/admin/users/{user}/status */
    #[OA\Patch(
        path: '/admin/users/{user}/status',
        summary: 'Troca a situação de um usuário',
        description: 'A única escrita que o painel faz sobre outra pessoa. Ninguém muda a própria situação: '
            .'sairia do ar sozinho, e a decisão sobre uma conta é sempre de outra pessoa — tentar responde 422 '
            .'(o campo `is_me` da listagem existe para a tabela já desabilitar a ação).',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive', 'dismissed', 'on_vacation'], example: 'inactive'),
                ],
                type: 'object',
            ),
        ),
        tags: ['Painel · Usuários'],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 7)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Situação trocada.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/AdminUser'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(
                response: 422,
                description: 'Situação inválida, ou a conta é a de quem fez a requisição.',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ValidationError',
                    example: [
                        'message' => 'Você não pode alterar a sua própria situação.',
                        'errors' => ['status' => ['Você não pode alterar a sua própria situação.']],
                    ],
                ),
            ),
        ],
    )]
    public function updateStatus(UpdateUserStatusRequest $request, User $user): AdminUserResource
    {
        $user->update(['status' => $request->validated('status')]);

        return new AdminUserResource($user);
    }

    /** PATCH /api/admin/users/{user}/role */
    #[OA\Patch(
        path: '/admin/users/{user}/role',
        summary: 'Dá, troca ou tira o acesso ao painel',
        description: 'O papel é o que define o acesso: `role_id` nulo tira a pessoa do painel sem apagar a '
            .'conta (ela continua cadastrada no site). Duas travas mantêm o painel com dono: ninguém altera o '
            .'próprio papel e o último Master não pode ser rebaixado — as duas respondem 422.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['role_id'],
                properties: [
                    new OA\Property(property: 'role_id', description: 'Papel do painel; `null` tira o acesso.', type: 'integer', nullable: true, example: 3),
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
                description: 'Papel trocado.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/AdminUser'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        ],
    )]
    public function updateRole(UpdateUserRoleRequest $request, User $user): AdminUserResource
    {
        $user->update(['role_id' => $request->validated('role_id')]);

        return new AdminUserResource($user->load('role'));
    }

    /**
     * Total geral e total por situação, sem filtro.
     *
     * @return array<string, int>
     */
    private function counts(): array
    {
        $porSituacao = User::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $contagem = ['total' => (int) $porSituacao->sum()];

        foreach (UserStatus::cases() as $situacao) {
            $contagem[$situacao->value] = (int) ($porSituacao[$situacao->value] ?? 0);
        }

        return $contagem;
    }

    /**
     * Polos já usados por alguém, em ordem alfabética.
     *
     * @return array<int, string>
     */
    private function polos(): array
    {
        return User::query()
            ->whereNotNull('polo')
            ->distinct()
            ->orderBy('polo')
            ->pluck('polo')
            ->all();
    }
}
