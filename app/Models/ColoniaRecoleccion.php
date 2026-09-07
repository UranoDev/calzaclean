<?php

namespace App\Models;

use Database\Factories\ColoniaRecoleccionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Zona donde se recoge y se entrega a domicilio.
 *
 * @property int $id
 * @property string $nombre
 * @property int $orden
 * @property bool $activa
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['nombre', 'orden', 'activa'])]
#[Table('colonias_recoleccion')]
class ColoniaRecoleccion extends Model
{
    /** @use HasFactory<ColoniaRecoleccionFactory> */
    use HasFactory;

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
            'orden' => 'integer',
            'activa' => 'boolean',
        ];
    }
}
