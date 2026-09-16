<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexUsersRequest;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Http\Resources\AdminUserResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
    public function index(IndexUsersRequest $request): AnonymousResourceCollection
    {
        $filtros = $request->validated();
        [$coluna, $direcao] = $request->sorting();

        $pagina = User::query()
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
    public function updateStatus(UpdateUserStatusRequest $request, User $user): AdminUserResource
    {
        $user->update(['status' => $request->validated('status')]);

        return new AdminUserResource($user);
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
