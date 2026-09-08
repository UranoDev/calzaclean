<?php

namespace App\Http\Controllers;

use App\Support\EnlaceCanonico;
use Illuminate\Http\Response;

/**
 * El mapa que los buscadores leen para saber qué páginas tiene el Sitio. Cada
 * dirección sale de su ruta con nombre, sobre el dominio canónico.
 */
class MapaDelSitioController extends Controller
{
    /**
     * Las páginas públicas del Sitio. El Panel no entra: está detrás del
     * acceso y `robots.txt` lo deja fuera.
     *
     * @var list<string>
     */
    private const PAGINAS = ['home', 'precios', 'resultados', 'cuidado-de-tenis', 'aviso-de-privacidad', 'terminos-y-condiciones'];

    public function __invoke(): Response
    {
        $direcciones = array_map(EnlaceCanonico::deRuta(...), self::PAGINAS);

        $cuerpo = view('sitio.mapa-del-sitio', ['direcciones' => $direcciones])->render();

        return response($cuerpo, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
