<?php

use App\Enums\UserStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dados de gestão de quem usa o painel: a situação (ver UserStatus) e o polo
 * a que a pessoa está ligada. Ambos entram na listagem de Usuários e são
 * filtráveis, por isso têm índice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('status', 20)->default(UserStatus::DEFAULT->value)->after('email_verified_at')->index();
            $table->string('polo', 120)->nullable()->after('status')->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['polo']);
            $table->dropColumn(['status', 'polo']);
        });
    }
};
