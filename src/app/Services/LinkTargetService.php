<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Destino de um link editável no painel: um endereço (`url`) ou um arquivo
 * enviado (`file_path` + `file_name`), nunca os dois. Gravar a URL apaga o
 * arquivo do disco; enviar um arquivo limpa a URL.
 *
 * Vale para qualquer model com as colunas `kind`, `url`, `file_path` e
 * `file_name` (ProvaoLink, VestibularBlock do tipo link). Quando o painel não
 * diz o tipo (`kind`), ele sai do destino (kindFor).
 */
class LinkTargetService
{
    /** Preenche o destino de um link novo: arquivo, se veio, ou a URL. */
    public function fillNew(Model $link, array $data, ?UploadedFile $file, string $dir): void
    {
        if ($file) {
            $this->attachFile($link, $file, $dir);
        }

        $link->kind = ($data['kind'] ?? null) ?: $this->kindFor($link->file_name ?? $link->url);
    }

    /**
     * Aplica uma edição (os campos já foram preenchidos no model). Uma URL
     * nova apaga o arquivo; sem `kind` explícito, o tipo acompanha a URL nova.
     */
    public function applyUpdate(Model $link, array $data): void
    {
        if (!empty($data['url']) && $link->file_path) {
            $this->deleteFile($link->file_path);
            $link->file_path = null;
            $link->file_name = null;
        }

        if (!array_key_exists('kind', $data) && $link->isDirty('url')) {
            $link->kind = $this->kindFor($link->url);
        }
    }

    /** Troca o destino pelo arquivo; o tipo acompanha, a não ser que o painel tenha fixado outro. */
    public function replaceFile(Model $link, UploadedFile $file, string $dir, ?string $kind = null): void
    {
        $this->attachFile($link, $file, $dir);
        $link->kind = $kind ?? $this->kindFor($link->file_name);
    }

    /** Tira o arquivo; sem URL, o link fica sem destino e some do site. */
    public function removeFile(Model $link): void
    {
        $this->deleteFile($link->file_path);
        $link->file_path = null;
        $link->file_name = null;
    }

    public function deleteFile(?string $caminho): void
    {
        if ($caminho) {
            Storage::disk('public')->delete($caminho);
        }
    }

    /**
     * O ícone que o destino pede: PDF, documento de texto, vídeo ou link
     * comum. Olha a extensão (do arquivo ou do fim da URL) e os sites de vídeo.
     */
    public function kindFor(?string $destino): string
    {
        if (!$destino) {
            return 'link';
        }

        $host = strtolower((string) parse_url($destino, PHP_URL_HOST));
        if (preg_match('/(^|\.)(youtube\.com|youtu\.be|vimeo\.com)$/', $host)) {
            return 'video';
        }

        $caminho = parse_url($destino, PHP_URL_PATH) ?: $destino;
        $extensao = strtolower(pathinfo($caminho, PATHINFO_EXTENSION));

        return match ($extensao) {
            'pdf' => 'pdf',
            'doc', 'docx', 'odt', 'rtf' => 'document',
            'mp4', 'webm', 'mov' => 'video',
            default => 'link',
        };
    }

    private function attachFile(Model $link, UploadedFile $file, string $dir): void
    {
        $this->deleteFile($link->file_path);
        $link->file_path = $file->store($dir, 'public');
        $link->file_name = $file->getClientOriginalName();
        $link->url = null;
    }
}
