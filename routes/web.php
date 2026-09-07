<?php

use App\Http\Controllers\MapaDelSitioController;
use App\Http\Controllers\RobotsController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'sitio.inicio')->name('home');
Route::view('precios', 'sitio.precios')->name('precios');
Route::view('cuidado-de-tenis', 'sitio.cuidado-de-tenis')->name('cuidado-de-tenis');

// Los dos archivos que lee un buscador. Se sirven desde una ruta y no desde
// `public/` para que el dominio salga de la configuración.
Route::get('sitemap.xml', MapaDelSitioController::class)->name('mapa-del-sitio');
Route::get('robots.txt', RobotsController::class)->name('robots');

Route::middleware(['auth'])->prefix('panel')->name('panel.')->group(function () {
    Route::view('/', 'panel.inicio')->name('inicio');
    Route::view('trabajos', 'panel.trabajos')->name('trabajos');
    Route::view('precios', 'panel.precios')->name('precios');
    Route::view('preguntas', 'panel.preguntas')->name('preguntas');

    // Ajustes se abre en tres pantallas; la entrada del menú lleva a la primera.
    Route::redirect('ajustes', 'panel/ajustes/contacto')->name('ajustes');
    Route::view('ajustes/contacto', 'panel.ajustes.contacto')->name('ajustes.contacto');
    Route::view('ajustes/negocio', 'panel.ajustes.negocio')->name('ajustes.negocio');
    Route::view('ajustes/aviso', 'panel.ajustes.aviso')->name('ajustes.aviso');
});

require __DIR__.'/settings.php';
