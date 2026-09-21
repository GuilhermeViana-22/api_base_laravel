<?php

namespace App\Console\Commands;

use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Saída de emergência do controle de acesso: `php artisan panel:master fulano@univesp.br`.
 *
 * O painel se administra sozinho (Equipe → Papéis), mas se a única conta Master
 * for perdida não sobra ninguém para devolver o acesso pela tela. Este comando
 * é o caminho de volta, e por isso também reativa a conta.
 */
class SetMasterUser extends Command
{
    protected $signature = 'panel:master {email : E-mail da conta que passa a ser Master}';

    protected $description = 'Dá o papel Master (acesso total ao painel) para uma conta existente';

    public function handle(): int
    {
        $usuario = User::where('email', $this->argument('email'))->first();

        if (!$usuario) {
            $this->error("Nenhuma conta com o e-mail {$this->argument('email')}.");

            return self::FAILURE;
        }

        $master = Role::where('slug', Role::MASTER)->first();

        if (!$master) {
            $this->error('O papel Master não existe. Rode as migrations primeiro.');

            return self::FAILURE;
        }

        $usuario->forceFill([
            'role_id' => $master->id,
            'status' => UserStatus::Active,
        ])->save();

        $this->info("{$usuario->name} agora é Master e pode entrar no painel.");

        return self::SUCCESS;
    }
}
