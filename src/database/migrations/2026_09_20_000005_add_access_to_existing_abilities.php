<?php

use App\Enums\PermissionAction;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Acrescenta o "Acessar" nas permissões que já existiam.
 *
 * A coluna nasceu depois: as matrizes gravadas até aqui têm `view`, `create`
 * e companhia, mas não a chave da tela. Sem esta migration, quem já tinha
 * papel perderia o menu inteiro, porque o painel passou a exigir `access`
 * para mostrar a funcionalidade.
 *
 * Linha vazia continua vazia: ela é a forma de dizer "não enxerga esta tela".
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('roles')->get() as $papel) {
            DB::table('roles')->where('id', $papel->id)->update([
                'abilities' => json_encode($this->comAcesso($papel->abilities)),
            ]);
        }

        foreach (DB::table('users')->whereNotNull('abilities')->get() as $usuario) {
            DB::table('users')->where('id', $usuario->id)->update([
                'abilities' => json_encode($this->comAcesso($usuario->abilities)),
            ]);
        }
    }

    public function down(): void
    {
        foreach (DB::table('roles')->get() as $papel) {
            DB::table('roles')->where('id', $papel->id)->update([
                'abilities' => json_encode($this->semAcesso($papel->abilities)),
            ]);
        }

        foreach (DB::table('users')->whereNotNull('abilities')->get() as $usuario) {
            DB::table('users')->where('id', $usuario->id)->update([
                'abilities' => json_encode($this->semAcesso($usuario->abilities)),
            ]);
        }
    }

    private function comAcesso(?string $json): array
    {
        return array_map(
            fn (array $acoes) => Role::comDependencias($acoes),
            json_decode($json ?: '{}', true) ?: [],
        );
    }

    private function semAcesso(?string $json): array
    {
        return array_map(
            fn (array $acoes) => array_values(array_diff($acoes, [PermissionAction::Access->value])),
            json_decode($json ?: '{}', true) ?: [],
        );
    }
};
