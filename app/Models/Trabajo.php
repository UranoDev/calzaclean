<?php

namespace App\Models;

use App\Enums\Material;
use App\Fotos\Foto;
use Database\Factories\TrabajoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Un par ya limpiado que se publica en la galería, con su foto de antes y su
 * foto de después. Es contenido, no catálogo: sobrevive a que se borre el
 * Servicio que se le aplicó.
 *
 * @property int $id
 * @property string|null $titulo
 * @property Material $material
 * @property int|null $servicio_id
 * @property string $foto_antes ruta base de la foto de antes, sin variante ni extensión
 * @property string $foto_despues ruta base de la foto de después, sin variante ni extensión
 * @property int $orden
 * @property bool $publicado
 * @property string $titulo_en_pantalla
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['titulo', 'material', 'servicio_id', 'foto_antes', 'foto_despues', 'orden', 'publicado'])]
class Trabajo extends Model
{
    /** @use HasFactory<TrabajoFactory> */
    use HasFactory;

    /**
     * Cuántos pares trae la galería de una vez: los que se ven al abrir la
     * portada y los que agrega cada toque de «Ver más resultados».
     */
    public const TANDA = 4;

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
     * Cómo se rotula el par en el Sitio. El título es opcional; sin título, el
     * Trabajo se presenta por su Material.
     *
     * @return Attribute<string, never>
     */
    protected function tituloEnPantalla(): Attribute
    {
        return Attribute::get(fn (): string => filled($this->titulo)
            ? $this->titulo
            : $this->material->etiqueta());
    }

    /**
     * Cómo se describe una de las fotos del par para quien no la ve: el
     * momento, el Material y el Servicio que se le aplicó. Sin Servicio en el
     * catálogo queda el Material, que es lo que nunca falta.
     *
     * @param  string  $momento  'Antes' o 'Después'
     */
    public function descripcionDeFoto(string $momento): string
    {
        $servicio = $this->servicio?->nombre;

        return filled($servicio)
            ? "{$momento}: {$this->material->etiqueta()}, {$servicio}"
            : "{$momento}: {$this->material->etiqueta()}";
    }

    /**
     * Guarda un Trabajo nuevo en la primera posición de la lista: los demás
     * bajan un lugar y conservan el orden que el Dueño les acomodó.
     */
    public function guardarDePrimero(): void
    {
        DB::transaction(function (): void {
            // La lista se corre entera antes de insertar. A medias dejaría dos
            // Trabajos peleando por el mismo lugar. Va por el query builder
            // crudo para no mover el `updated_at` de pares que no cambiaron.
            static::query()->toBase()->increment('orden');

            $this->orden = 1;
            $this->save();
        });
    }

    /**
     * Mueve el Trabajo un lugar en la lista. Renumera a todos, porque dos
     * Trabajos con el mismo orden dejan la lista a merced del desempate.
     *
     * @param  int  $desplazamiento  -1 para subir, 1 para bajar
     */
    public function mover(int $desplazamiento): void
    {
        $ids = static::query()->ordenados()->pluck('id')->all();

        $posicion = array_search($this->id, $ids, true);

        if (! is_int($posicion)) {
            return;
        }

        $destino = $posicion + $desplazamiento;

        if ($destino < 0 || $destino >= count($ids)) {
            return;
        }

        [$ids[$posicion], $ids[$destino]] = [$ids[$destino], $ids[$posicion]];

        DB::transaction(function () use ($ids): void {
            foreach ($ids as $orden => $id) {
                static::query()->whereKey($id)->update(['orden' => $orden + 1]);
            }
        });
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
     * El par que enseña la portada: el primero de la lista que esté publicado.
     * Es el mismo orden que el Dueño acomoda con las flechas del Panel, así
     * que subir un Trabajo al primer lugar lo pone en la portada. Sin ninguno
     * publicado devuelve null y la portada usa su imagen de respaldo.
     */
    public static function deLaPortada(): ?self
    {
        return static::query()->publicados()->ordenados()->first();
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
