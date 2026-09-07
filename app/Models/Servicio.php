<?php

namespace App\Models;

use App\Support\Precio;
use Database\Factories\ServicioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Una línea del catálogo con precio. Un Servicio con `es_extra` levantado es un
 * Extra: se suma al precio base y nunca se vende solo.
 *
 * @property int $id
 * @property string $nombre
 * @property string|null $aplica_a
 * @property int $precio
 * @property bool $es_extra
 * @property int $orden
 * @property bool $activo
 * @property string $precio_formateado
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['nombre', 'aplica_a', 'precio', 'es_extra', 'orden', 'activo'])]
class Servicio extends Model
{
    /** @use HasFactory<ServicioFactory> */
    use HasFactory;

    /**
     * Los Trabajos a los que se les aplicó este Servicio.
     *
     * @return HasMany<Trabajo, $this>
     */
    public function trabajos(): HasMany
    {
        return $this->hasMany(Trabajo::class);
    }

    /**
     * El precio como se escribe en pantalla.
     *
     * @return Attribute<string, never>
     */
    protected function precioFormateado(): Attribute
    {
        return Attribute::get($this->formatearPrecio(...));
    }

    /**
     * Un Extra lleva el signo de más por delante y un Servicio no. La regla de
     * escritura vive en `Precio`, que comparte con la Zona de recolección.
     */
    private function formatearPrecio(): string
    {
        return Precio::formatear($this->precio, $this->es_extra);
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function activos(Builder $query): void
    {
        $query->where('activo', true);
    }

    /**
     * El catálogo: los Servicios que sí se venden solos.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function catalogo(Builder $query): void
    {
        $query->where('es_extra', false);
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function extras(Builder $query): void
    {
        $query->where('es_extra', true);
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
            'precio' => 'integer',
            'es_extra' => 'boolean',
            'orden' => 'integer',
            'activo' => 'boolean',
        ];
    }
}
