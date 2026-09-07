<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'sitio.inicio')->name('home');
Route::view('precios', 'sitio.precios')->name('precios');

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
