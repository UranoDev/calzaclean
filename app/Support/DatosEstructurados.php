<?php

namespace App\Support;

use App\Models\Negocio;
use App\Models\Servicio;

/**
 * La ficha del taller que leen los buscadores: el mismo Negocio que ve quien
 * entra al Sitio, escrito en el vocabulario de schema.org.
 *
 * Todo campo sale de lo que hay cargado. El que no tiene dato no se emite: una
 * dirección vacía deja fuera su campo en vez de publicar un valor de relleno.
 */
final class DatosEstructurados
{
    /**
     * La localidad del taller. Es el mismo dato que el pie del Sitio dice con
     * palabras, no un dato que el Dueño cargue.
     */
    private const LOCALIDAD = 'San Juan del Río';

    private const ESTADO = 'Querétaro';

    private const PAIS = 'MX';

    private const MONEDA = 'MXN';

    /**
     * La imagen que representa al taller, la misma que se ve al compartir el
     * enlace.
     */
    public const VISTA_PREVIA = '/img/vista-previa.png';

    /**
     * La ficha completa, lista para serializar.
     *
     * @return array<string, mixed>
     */
    public static function delNegocio(): array
    {
        $negocio = Negocio::actual();

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => (string) config('app.name'),
            'url' => EnlaceCanonico::a('/'),
            'image' => EnlaceCanonico::a(self::VISTA_PREVIA),
            'address' => self::direccion($negocio),
            'telephone' => self::telefono($negocio),
            'openingHours' => Horarios::interpretar($negocio->horarios),
            'priceRange' => self::rangoDePrecios(),
            'currenciesAccepted' => self::MONEDA,
            'areaServed' => self::ciudadQueSeAtiende($negocio),
            'sameAs' => self::redes($negocio),
        ], fn (mixed $valor): bool => $valor !== null && $valor !== []);
    }

    /**
     * La ficha serializada como la espera un `<script type="application/ld+json">`.
     * Los signos de menor y mayor salen escapados: un dato del Panel no puede
     * cerrar la etiqueta.
     */
    public static function json(): string
    {
        return (string) json_encode(
            self::delNegocio(),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
        );
    }

    /**
     * Sin dirección cargada no hay campo de dirección. La localidad y el estado
     * acompañan a la calle, nunca van solos.
     *
     * @return array<string, string>|null
     */
    private static function direccion(Negocio $negocio): ?array
    {
        if (blank($negocio->direccion)) {
            return null;
        }

        return [
            '@type' => 'PostalAddress',
            'streetAddress' => $negocio->direccion,
            'addressLocality' => self::LOCALIDAD,
            'addressRegion' => self::ESTADO,
            'addressCountry' => self::PAIS,
        ];
    }

    /**
     * El WhatsApp del Negocio es el teléfono del taller. Se guarda en dígitos,
     * y acá sale en la forma internacional.
     */
    private static function telefono(Negocio $negocio): ?string
    {
        return blank($negocio->whatsapp) ? null : '+'.$negocio->whatsapp;
    }

    /**
     * El rango sale de la lista publicada, así que no puede contradecirla. Los
     * Extras quedan fuera: no se venden solos, y meterlos bajaría el piso del
     * rango por debajo de cualquier servicio real.
     */
    private static function rangoDePrecios(): ?string
    {
        $precios = Servicio::query()->activos()->catalogo()->pluck('precio');

        if ($precios->isEmpty()) {
            return null;
        }

        $menor = Precio::formatear((int) $precios->min());
        $mayor = Precio::formatear((int) $precios->max());

        return $menor === $mayor ? $menor : $menor.' - '.$mayor;
    }

    /**
     * La ciudad donde trabaja el taller. Las zonas de recolección no van acá:
     * son categorías de cobro del Sitio, no lugares que un buscador reconozca.
     * Sin dirección cargada no hay ciudad que declarar.
     *
     * @return array<string, string>|null
     */
    private static function ciudadQueSeAtiende(Negocio $negocio): ?array
    {
        if (blank($negocio->direccion)) {
            return null;
        }

        return [
            '@type' => 'City',
            'name' => self::LOCALIDAD,
            'addressRegion' => self::ESTADO,
        ];
    }

    /**
     * Las redes cargadas. Una sin URL no se dibuja en el pie ni se declara acá.
     *
     * @return list<string>
     */
    private static function redes(Negocio $negocio): array
    {
        return array_values(array_map(
            fn (array $red): string => $red['url'],
            $negocio->redes,
        ));
    }
}
