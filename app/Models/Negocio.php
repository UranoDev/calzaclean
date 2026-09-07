<?php

namespace App\Models;

use Database\Factories\NegocioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Los datos únicos del taller. Es un singleton: un solo renglón, al que se
 * llega siempre por `Negocio::actual()`.
 *
 * @property int $id
 * @property string|null $whatsapp
 * @property string|null $horarios
 * @property string|null $direccion
 * @property string|null $instagram
 * @property string|null $facebook
 * @property string|null $x
 * @property string|null $tiktok
 * @property string|null $aviso_texto
 * @property bool $aviso_activo
 * @property bool $aviso_visible
 * @property array<string, array{nombre: string, url: string}> $redes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['whatsapp', 'horarios', 'direccion', 'instagram', 'facebook', 'x', 'tiktok', 'aviso_texto', 'aviso_activo'])]
class Negocio extends Model
{
    /** @use HasFactory<NegocioFactory> */
    use HasFactory;

    /**
     * Los campos de red, con el nombre que se lee en pantalla.
     *
     * @var array<string, string>
     */
    public const REDES = [
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'x' => 'X',
        'tiktok' => 'TikTok',
    ];

    /**
     * El renglón del taller. Si todavía no existe, se crea vacío.
     */
    public static function actual(): self
    {
        return static::query()->orderBy('id')->first() ?? static::query()->create();
    }

    /**
     * Las redes que sí tienen URL, con su nombre.
     *
     * @return Attribute<array<string, array{nombre: string, url: string}>, never>
     */
    protected function redes(): Attribute
    {
        return Attribute::get($this->redesCargadas(...));
    }

    /**
     * Una red sin URL no se dibuja: el pie del Sitio solo muestra las que están
     * cargadas.
     *
     * @return array<string, array{nombre: string, url: string}>
     */
    private function redesCargadas(): array
    {
        $urls = [
            'instagram' => $this->instagram,
            'facebook' => $this->facebook,
            'x' => $this->x,
            'tiktok' => $this->tiktok,
        ];

        $redes = [];

        foreach ($urls as $campo => $url) {
            if ($url !== null && $url !== '') {
                $redes[$campo] = ['nombre' => self::REDES[$campo], 'url' => $url];
            }
        }

        return $redes;
    }

    /**
     * Si la franja del Aviso se dibuja. Un Aviso encendido sin texto dejaría una
     * franja vacía arriba del Sitio.
     *
     * @return Attribute<bool, never>
     */
    protected function avisoVisible(): Attribute
    {
        return Attribute::get(fn (): bool => $this->aviso_activo && filled($this->aviso_texto));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'aviso_activo' => 'boolean',
        ];
    }
}
