<?php

namespace App\Models;

use App\Support\Precio;
use Database\Factories\ZonaRecoleccionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Un área donde se recoge y se entrega a domicilio, con su costo. El costo va
 * en pesos enteros y un `0` es una zona sin costo.
 *
 * @property int $id
 * @property string $nombre
 * @property int $costo
 * @property int $orden
 * @property bool $activa
 * @property string $costo_formateado
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['nombre', 'costo', 'orden', 'activa'])]
#[Table('zonas_recoleccion')]
class ZonaRecoleccion extends Model
{
    /** @use HasFactory<ZonaRecoleccionFactory> */
    use HasFactory;

    /**
     * El costo como se escribe en pantalla.
     *
     * @return Attribute<string, never>
     */
    protected function costoFormateado(): Attribute
    {
        return Attribute::get($this->formatearCosto(...));
    }

    /**
     * Una zona sin costo se escribe con palabras: un `$0` en una lista de
     * precios se lee como error. Con costo, el monto se suma a lo que cuesta la
     * limpieza, así que lleva el signo de más de `Precio`, el mismo de un Extra.
     */
    private function formatearCosto(): string
    {
        return $this->costo === 0 ? 'sin costo' : Precio::formatear($this->costo, seSuma: true);
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function activas(Builder $query): void
    {
        $query->where('activa', true);
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function ordenadas(Builder $query): void
    {
        $query->orderBy('orden')->orderBy('nombre');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'costo' => 'integer',
            'orden' => 'integer',
            'activa' => 'boolean',
        ];
    }
}
