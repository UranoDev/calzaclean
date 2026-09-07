<?php

namespace App\Models;

use App\Enums\Material;
use App\Fotos\Foto;
use Database\Factories\TrabajoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Un par ya limpiado que se publica en la galería, con su foto de antes y su
 * foto de después. Es contenido, no catálogo: sobrevive a que se borre el
 * Servicio que se le aplicó.
 *
 * @property int $id
 * @property string $titulo
 * @property Material $material
 * @property int|null $servicio_id
 * @property string $foto_antes ruta base de la foto de antes, sin variante ni extensión
 * @property string $foto_despues ruta base de la foto de después, sin variante ni extensión
 * @property int $orden
 * @property bool $publicado
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['titulo', 'material', 'servicio_id', 'foto_antes', 'foto_despues', 'orden', 'publicado'])]
class Trabajo extends Model
{
    /** @use HasFactory<TrabajoFactory> */
    use HasFactory;

    /**
     * Borrar un Trabajo se lleva sus archivos, miniaturas incluidas: si no, el
     * disco acumula fotos que ya no se ven en ningún lado.
     */
    protected static function booted(): void
    {
        static::deleted(function (Trabajo $trabajo): void {
            $trabajo->antes()->borrar();
            $trabajo->despues()->borrar();
        });
    }

    /**
     * La foto de antes, con sus cuatro versiones.
     */
    public function antes(): Foto
    {
        return new Foto($this->foto_antes);
    }

    /**
     * La foto de después, con sus cuatro versiones.
     */
    public function despues(): Foto
    {
        return new Foto($this->foto_despues);
    }

    /**
     * El Servicio que se le aplicó, si todavía está en el catálogo.
     *
     * @return BelongsTo<Servicio, $this>
     */
    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }

    /**
     * Los Testimonios que corresponden a este Trabajo.
     *
     * @return HasMany<Testimonio, $this>
     */
    public function testimonios(): HasMany
    {
        return $this->hasMany(Testimonio::class);
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
        $query->orderBy('orden')->orderByDesc('id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'material' => Material::class,
            'orden' => 'integer',
            'publicado' => 'boolean',
        ];
    }
}
