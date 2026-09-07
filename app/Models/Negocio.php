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
     * Deja el WhatsApp en puros dígitos, con lada de país y sin el 1 que
     * México pedía para celulares: es la forma que acepta `wa.me`.
     */
    public static function normalizarWhatsapp(?string $valor): ?string
    {
        $digitos = (string) preg_replace('/\D/', '', (string) $valor);

        // Prefijo internacional escrito como 00 en vez de +.
        if (str_starts_with($digitos, '00')) {
            $digitos = substr($digitos, 2);
        }

        // El 1 de México va después del 52 y wa.me no lo usa.
        if (strlen($digitos) === 13 && str_starts_with($digitos, '521')) {
            $digitos = '52'.substr($digitos, 3);
        }

        // Diez dígitos son un número local: le falta la lada de país.
        if (strlen($digitos) === 10) {
            $digitos = '52'.$digitos;
        }

        return $digitos === '' ? null : $digitos;
    }

    /**
     * El renglón del taller. Si todavía no existe, se crea vacío.
     */
    public static function actual(): self
    {
        return static::query()->orderBy('id')->first() ?? static::query()->create();
    }

    /**
     * El WhatsApp se guarda ya normalizado, venga de donde venga: del Panel,
     * del seeder o de una prueba. La vista previa del Panel se apoya en esto
     * para enseñar el enlace antes de guardar.
     */
    protected function setWhatsappAttribute(?string $valor): void
    {
        $this->attributes['whatsapp'] = self::normalizarWhatsapp($valor);
    }

    /**
     * El enlace de WhatsApp con el mensaje precargado. Es el único lugar donde
     * se arma: el Sitio y la vista previa del Panel piden los dos aquí. Sin
     * número cargado no hay conversación a dónde mandar a nadie y devuelve
     * `null`.
     */
    public function enlaceWhatsapp(?string $mensaje = null): ?string
    {
        if (blank($this->whatsapp)) {
            return null;
        }

        return 'https://wa.me/'.$this->whatsapp
            .(filled($mensaje) ? '?text='.rawurlencode($mensaje) : '');
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
