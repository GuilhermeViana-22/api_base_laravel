<?php

namespace App\Services;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Regras de escrita das notícias, usadas pelos controllers do painel.
 *
 * Os controllers só validam e respondem; tudo o que altera banco ou disco
 * (sanitizar o HTML, datar a publicação, guardar e apagar fotos) fica aqui.
 */
class PostService
{
    /** Pasta (disco `public`) das fotos de capa. */
    private const COVER_DIR = 'posts/capas';

    /** Pasta (disco `public`) das imagens inseridas no meio do texto. */
    private const CONTENT_IMAGES_DIR = 'posts/conteudo';

    public function __construct(private readonly ContentSanitizer $sanitizer)
    {
    }

    /** Cria a notícia em nome de quem está logado no painel. */
    public function create(array $data, User $author): Post
    {
        $post = new Post();
        $post->author()->associate($author);

        return $this->fill($post, $data);
    }

    /** Atualiza só os campos enviados (o PATCH do painel pode ser parcial). */
    public function update(Post $post, array $data): Post
    {
        return $this->fill($post, $data);
    }

    /** Exclui a notícia e a foto de capa do disco. */
    public function delete(Post $post): void
    {
        $this->deleteCoverFile($post);
        $post->delete();
    }

    /** Grava uma nova foto de capa, apagando a anterior do disco. */
    public function replaceCover(Post $post, UploadedFile $file): Post
    {
        $this->deleteCoverFile($post);

        $post->image_path = $file->store(self::COVER_DIR, 'public');
        $post->save();

        return $post;
    }

    /** Remove a foto de capa; crédito e legenda perdem o sentido e saem junto. */
    public function removeCover(Post $post): Post
    {
        $this->deleteCoverFile($post);

        $post->forceFill([
            'image_path' => null,
            'image_credit' => null,
            'image_caption' => null,
        ])->save();

        return $post;
    }

    /** Guarda uma imagem inserida no meio do texto pelo TinyMCE e devolve a URL pública. */
    public function storeContentImage(UploadedFile $file): string
    {
        $path = $file->store(self::CONTENT_IMAGES_DIR, 'public');

        return Storage::disk('public')->url($path);
    }

    private function fill(Post $post, array $data): Post
    {
        $post->fill($data);

        // O site renderiza o texto com v-html: nunca gravar HTML sem filtro.
        if (array_key_exists('content', $data)) {
            $post->content = $this->sanitizer->sanitize($data['content']);
        }

        // Publicar sem data = publicar agora.
        if ($post->status === PostStatus::Published && $post->published_at === null) {
            $post->published_at = now();
        }

        $post->save();

        return $post->load('author');
    }

    private function deleteCoverFile(Post $post): void
    {
        if ($post->image_path) {
            Storage::disk('public')->delete($post->image_path);
        }
    }
}
