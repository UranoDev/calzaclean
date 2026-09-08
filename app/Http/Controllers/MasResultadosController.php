<?php

namespace App\Http\Controllers;

use App\Models\Trabajo;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * La siguiente tanda de la galería. Devuelve los pares sueltos —sin encabezado
 * ni pie— para que el botón «Ver más resultados» los agregue al final de la
 * rejilla sin recargar la portada.
 */
class MasResultadosController extends Controller
{
    public function __invoke(Request $request): View
    {
        $desde = max(0, (int) $request->query('desde', 0));

        $trabajos = Trabajo::query()
            ->publicados()
            ->ordenados()
            ->with('servicio')
            ->skip($desde)
            ->take(Trabajo::TANDA)
            ->get();

        return view('sitio.mas-resultados', ['trabajos' => $trabajos]);
    }
}
