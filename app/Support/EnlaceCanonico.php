<?php

namespace App\Support;

use Illuminate\Support\Facades\Request;

/**
 * El único lugar donde se arma una dirección absoluta del Sitio. Todas salen
 * de `app.url`: el canónico de cada página, la imagen de vista previa, el
 * sitemap y los datos estructurados. Cambiar el dominio es cambiar esa
 * variable, y ninguna vista lo escribe a mano.
 */
final class EnlaceCanonico
{
    /**
     * El dominio del Sitio, sin diagonal al final.
     */
    public static function raiz(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    /**
     * La dirección absoluta de una ruta del Sitio. La portada queda como
     * `https://dominio/`, con la diagonal; el resto sin ella.
     */
    public static function a(string $ruta = '/'): string
    {
        $ruta = trim($ruta, '/');

        return $ruta === '' ? self::raiz().'/' : self::raiz().'/'.$ruta;
    }

    /**
     * La dirección absoluta de una ruta con nombre.
     */
    public static function deRuta(string $nombre): string
    {
        return self::a(route($nombre, absolute: false));
    }

    /**
     * El canónico de la página que se está sirviendo. Sale de la ruta pedida,
     * no del dominio con el que llegó la petición: así una visita por otro
     * nombre de servidor sigue apuntando al canónico.
     */
    public static function actual(): string
    {
        return self::a(Request::path());
    }
}
