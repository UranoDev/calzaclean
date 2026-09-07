<?php

namespace Tests\Feature\Sitio;

use App\Models\Negocio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContactoTest extends TestCase
{
    use RefreshDatabase;

    public function test_sin_datos_del_negocio_la_seccion_no_se_dibuja(): void
    {
        $respuesta = $this->get(route('home'));

        $respuesta->assertDontSee('Contacto');
        $respuesta->assertDontSee('id="contacto"', false);
    }

    public function test_la_seccion_muestra_lo_que_se_guardo_en_el_panel(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('panel.ajustes.negocio')
            ->set('horarios', 'Lunes a viernes de 10:00 a 19:00')
            ->set('direccion', 'Av. Juárez 12, San Juan del Río, Qro.')
            ->call('guardar')
            ->assertHasNoErrors();

        Livewire::test('panel.ajustes.contacto')
            ->set('whatsapp', '+52 427 180 3585')
            ->set('redes.instagram', 'https://www.instagram.com/calza_clean_/')
            ->call('guardar')
            ->assertHasNoErrors();

        $respuesta = $this->get(route('home'));

        $respuesta->assertSee('Contacto');
        $respuesta->assertSee('Lunes a viernes de 10:00 a 19:00');
        $respuesta->assertSee('Av. Juárez 12, San Juan del Río, Qro.');
        $respuesta->assertSee('https://wa.me/524271803585', false);
        $respuesta->assertSee('https://www.instagram.com/calza_clean_/', false);
    }

    public function test_un_dato_vacio_no_deja_su_rotulo(): void
    {
        Negocio::factory()->create(['horarios' => null]);

        $respuesta = $this->get(route('home'));

        $respuesta->assertSee('Dirección');
        $respuesta->assertDontSee('Horarios');
    }

    public function test_la_pagina_no_le_pide_nada_a_google(): void
    {
        Negocio::factory()->create(['direccion' => 'Av. Juárez 12, San Juan del Río, Qro.']);

        $respuesta = $this->get(route('home'));

        // No queda marco incrustado ni el JavaScript que lo cargaba: el único
        // camino hacia Google es el enlace, y lo abre quien lo presiona.
        $respuesta->assertDontSee('<iframe', false);
        $respuesta->assertDontSee('data-mapa', false);
        $respuesta->assertDontSee('Ver el mapa');

        $respuesta->assertSee('Av. Juárez 12, San Juan del Río, Qro.');
        $respuesta->assertSee('https://www.google.com/maps/search/?api=1&amp;query=', false);
        $respuesta->assertSee('Abrir en Google Maps');
    }

    public function test_sin_direccion_no_hay_panel_de_direccion(): void
    {
        Negocio::factory()->create(['direccion' => null]);

        $respuesta = $this->get(route('home'));

        $respuesta->assertSee('Contacto');
        $respuesta->assertDontSee('Dirección');
        $respuesta->assertDontSee('Abrir en Google Maps');
    }
}
