<?php

use App\Enums\PermissionAction;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Papéis do painel e o vínculo de cada conta com um deles.
 *
 * Até aqui qualquer conta confirmada entrava no painel inteiro. A partir desta
 * migration o acesso é explícito: `users.role_id` nulo significa "conta do site
 * público", sem painel. Por isso ela promove a conta mais antiga a Master:
 * sem isso ninguém entraria depois do deploy.
 *
 * As permissões ficam num JSON (`abilities`) em vez de uma tabela-pivô porque
 * a tela lê e grava a matriz inteira de uma vez; o catálogo de módulos e ações
 * vive em código (App\Support\PanelResources), que é quem valida o conteúdo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('slug', 60)->unique();
            $table->string('description', 255)->nullable();
            // { "posts": ["view", "create"], "home": ["view"] }
            $table->json('abilities');
            // Ignora a matriz e libera tudo, inclusive módulos que ainda não existem.
            $table->boolean('is_master')->default(false);
            // Papel de sistema: não pode ser editado nem excluído pela tela.
            $table->boolean('locked')->default(false);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('polo')->constrained()->nullOnDelete();
        });

        $agora = now();
        $tudo = fn (array $modulos) => collect($modulos)
            ->mapWithKeys(fn (string $modulo) => [$modulo => PermissionAction::values()])
            ->all();

        DB::table('roles')->insert([
            [
                'name' => 'Master',
                'slug' => Role::MASTER,
                'description' => 'Acesso total ao painel, incluindo equipe, papéis e configurações.',
                'abilities' => json_encode([]),
                'is_master' => true,
                'locked' => true,
                'created_at' => $agora,
                'updated_at' => $agora,
            ],
            [
                'name' => 'Administrador',
                'slug' => 'administrador',
                'description' => 'Cuida de todo o conteúdo do site, sem mexer em equipe e configurações.',
                'abilities' => json_encode($tudo(['posts', 'home', 'banners', 'pages', 'users'])),
                'is_master' => false,
                'locked' => false,
                'created_at' => $agora,
                'updated_at' => $agora,
            ],
            [
                'name' => 'Editor',
                'slug' => 'editor',
                'description' => 'Publica e edita conteúdo, mas não exclui nada.',
                'abilities' => json_encode([
                    'posts' => ['access', 'view', 'create', 'update'],
                    'home' => ['access', 'view', 'create', 'update'],
                    'banners' => ['access', 'view', 'update'],
                    'pages' => ['access', 'view', 'update'],
                ]),
                'is_master' => false,
                'locked' => false,
                'created_at' => $agora,
                'updated_at' => $agora,
            ],
        ]);

        // A conta mais antiga vira Master para o painel não ficar sem dono.
        // Para trocar depois: `php artisan panel:master email@exemplo.com`.
        $master = DB::table('roles')->where('slug', Role::MASTER)->value('id');
        $primeira = DB::table('users')->orderBy('id')->value('id');

        if ($primeira) {
            DB::table('users')->where('id', $primeira)->update(['role_id' => $master]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });

        Schema::dropIfExists('roles');
    }
};
