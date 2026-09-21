<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Capa de cada curso (card da vitrine /cursos) e o texto de abertura da
 * mesma página. A página de um curso (/cursos/{slug}) não usa estes dados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('content');
        });

        Schema::create('course_landings', function (Blueprint $table) {
            $table->id();
            $table->longText('description')->nullable();
            $table->timestamps();
        });

        DB::table('course_landings')->insert([
            'description' => $this->textoDaVitrine(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('course_landings');

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }

    private function textoDaVitrine(): string
    {
        return '<p>Oferecemos cursos de graduação e pós a distância de qualidade em todas as regiões do Estado de São Paulo.</p>'
            .'<p>Na UNIVESP, o aluno aprende de forma mediada. O aprendizado acontece tanto em momentos on-line como em encontros presenciais, nos chamados polos.</p>'
            .'<p>Grande parte do curso é realizado em Ambiente Virtual de Aprendizagem (AVVA). Trata-se de uma plataforma on-line na qual os estudantes desenvolvem atividades acadêmicas que incluem assistir a videoaulas, acessar material didático e bibliografia das disciplinas e tirar dúvidas do conteúdo em fóruns com facilitadores.</p>'
            .'<p>Já o polo é o espaço físico onde os alunos contam com infraestrutura (computadores, impressoras e acesso à internet) e realizam atividades como prova, discussões em grupo, trabalhos com auxílio dos orientadores. Na unidade presencial, também é possível solicitar serviços de secretaria acadêmica, assim como tirar suas dúvidas sobre o AVVA.</p>'
            .'<p><a href="/institucional">Normas Acadêmicas</a></p>';
    }
};
