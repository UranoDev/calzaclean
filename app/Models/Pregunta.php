<?php

namespace App\Models;

use Database\Factories\PreguntaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Una entrada de la sección de preguntas frecuentes, en texto plano.
 *
 * @property int $id
 * @property string $pregunta
 * @property string $respuesta
 * @property int $orden
 * @property bool $publicada
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['pregunta', 'respuesta', 'orden', 'publicada'])]
class Pregunta extends Model
{
    /** @use HasFactory<PreguntaFactory> */
    use HasFactory;

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function publicadas(Builder $query): void
    {
        $query->where('publicada', true);
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function ordenadas(Builder $query): void
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
            'publicada' => 'boolean',
        ];
    }
}
