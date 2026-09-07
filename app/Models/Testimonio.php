<?php

namespace App\Models;

use Database\Factories\TestimonioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * La reseña de un Cliente publicada en el Sitio. El Trabajo al que corresponde
 * es opcional.
 *
 * @property int $id
 * @property string $nombre
 * @property string $texto
 * @property int|null $trabajo_id
 * @property int $orden
 * @property bool $publicado
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['nombre', 'texto', 'trabajo_id', 'orden', 'publicado'])]
class Testimonio extends Model
{
    /** @use HasFactory<TestimonioFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Trabajo, $this>
     */
    public function trabajo(): BelongsTo
    {
        return $this->belongsTo(Trabajo::class);
    }

    /**
     * El Trabajo cuya foto acompaña al Testimonio. Solo cuenta si sigue
     * existiendo, está publicado y tiene su foto de después: un Testimonio de
     * un Trabajo despublicado o borrado se sigue mostrando, sin foto.
     */
    public function trabajoVisible(): ?Trabajo
    {
        $trabajo = $this->trabajo;

        if ($trabajo === null || ! $trabajo->publicado || blank($trabajo->foto_despues)) {
            return null;
        }

        return $trabajo;
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function publicados(Builder $query): void
    {
        $query->where('publicado', true);
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function ordenados(Builder $query): void
    {
        $query->orderBy('orden')->orderBy('id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'publicado' => 'boolean',
        ];
    }
}
