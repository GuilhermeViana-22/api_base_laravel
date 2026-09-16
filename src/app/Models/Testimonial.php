<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Depoimento de ex-aluno mostrado na página inicial.
 *
 * @property bool $active
 * @property int $position
 * @property string $name
 * @property string $quote
 * @property string|null $photo_path caminho no disco `public`
 */
class Testimonial extends Model
{
    use HasFactory;

    protected $fillable = ['active', 'position', 'name', 'quote'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }
}
