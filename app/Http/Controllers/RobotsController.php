<?php

namespace App\Http\Controllers;

use App\Support\EnlaceCanonico;
use Illuminate\Http\Response;

/**
 * Lo que se le dice a un buscador antes de que empiece a leer: dónde está el
 * mapa del Sitio y qué direcciones no tiene caso recorrer.
 */
class RobotsController extends Controller
{
    /**
     * Lo que queda fuera del recorrido. El Panel y el acceso están detrás de
     * la sesión, así que un buscador solo encontraría la pantalla de entrada.
     *
     * @var list<string>
     */
    private const FUERA = ['/panel/', '/settings/', '/login', '/forgot-password', '/reset-password'];

    public function __invoke(): Response
    {
        $renglones = ['User-agent: *'];

        foreach (self::FUERA as $ruta) {
            $renglones[] = 'Disallow: '.$ruta;
        }

        $renglones[] = '';
        $renglones[] = 'Sitemap: '.EnlaceCanonico::deRuta('mapa-del-sitio');

        return response(implode("\n", $renglones)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
