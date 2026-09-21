<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseLanding;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Regras de escrita dos cursos, usadas pelo controller do painel.
 *
 * Os controllers só validam e respondem; posição, ordem e a limpeza do HTML
 * dos dois textos (que vêm do editor rico) ficam aqui.
 */
class CourseService
{
    private const COVER_DIR = 'cursos/capas';

    public function __construct(private readonly ContentSanitizer $sanitizer)
    {
    }

    /** Cria o curso no fim da lista, para não empurrar os que já estão no menu. */
    public function create(array $data): Course
    {
        $curso = new Course($this->sanitized($data));
        $curso->position = $data['position'] ?? (int) Course::max('position') + 1;
        $curso->save();

        return $curso;
    }

    /** Atualiza só os campos enviados (o PATCH do painel pode ser parcial). */
    public function update(Course $course, array $data): Course
    {
        $course->fill($this->sanitized($data))->save();

        return $course;
    }

    public function delete(Course $course): void
    {
        $this->deleteFile($course->image_path);
        $course->delete();
    }

    public function replaceCover(Course $course, UploadedFile $file): Course
    {
        $this->deleteFile($course->image_path);
        $course->image_path = $file->store(self::COVER_DIR, 'public');
        $course->save();

        return $course;
    }

    public function removeCover(Course $course): Course
    {
        $this->deleteFile($course->image_path);
        $course->image_path = null;
        $course->save();

        return $course;
    }

    public function updateLanding(array $data): CourseLanding
    {
        $pagina = CourseLanding::current();

        if (array_key_exists('description', $data)) {
            $pagina->description = $data['description'] !== null
                ? $this->sanitizer->sanitize($data['description'])
                : null;
            $pagina->save();
        }

        return $pagina;
    }

    private function deleteFile(?string $caminho): void
    {
        if ($caminho) {
            Storage::disk('public')->delete($caminho);
        }
    }

    /**
     * Regrava as posições na ordem dos ids recebidos — é ela que o menu do
     * site e a coluna da esquerda da página de curso seguem.
     *
     * Numa transação: uma ordem pela metade deixaria o menu embaralhado.
     * Ids que não estão na lista vão para o fim, mantendo a ordem relativa.
     *
     * @param  array<int, int>  $ids
     */
    public function reorder(array $ids): void
    {
        DB::transaction(function () use ($ids) {
            foreach (array_values($ids) as $posicao => $id) {
                Course::whereKey($id)->update(['position' => $posicao + 1]);
            }

            Course::whereNotIn('id', $ids)
                ->ordered()
                ->get()
                ->each(fn (Course $curso, int $indice) => $curso->update([
                    'position' => count($ids) + $indice + 1,
                ]));
        });
    }

    /** O site renderiza os dois textos com v-html: nunca gravar HTML sem filtro. */
    private function sanitized(array $data): array
    {
        foreach (['description', 'content'] as $campo) {
            if (array_key_exists($campo, $data) && $data[$campo] !== null) {
                $data[$campo] = $this->sanitizer->sanitize($data[$campo]);
            }
        }

        return $data;
    }
}
