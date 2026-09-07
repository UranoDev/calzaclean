<?php

namespace Tests\Feature\Sitio;

use App\Enums\Material;
use App\Models\Negocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CuidadoDeTenisTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_guia_cubre_los_seis_materiales(): void
    {
        $respuesta = $this->get(route('cuidado-de-tenis'));

        $respuesta->assertOk();

        foreach (Material::cases() as $material) {
            $respuesta->assertSee($material->etiqueta());
        }
    }

    public function test_cada_material_dice_como_guardarlos_que_evitar_y_cada_cuanto(): void
    {
        $respuesta = $this->get(route('cuidado-de-tenis'));

        $respuesta->assertSee('Cómo guardarlos');
        $respuesta->assertSee('Qué no hacer');
        $respuesta->assertSee('Cada cuánto');
    }

    public function test_la_guia_es_contenido_escrito_y_no_repite_el_catalogo(): void
    {
        $respuesta = $this->get(route('cuidado-de-tenis'));

        $respuesta->assertDontSee('Limpieza básica');
        $respuesta->assertDontSee('Servicios y precios');
    }

    public function test_la_guia_termina_en_whatsapp_y_en_la_lista_de_precios(): void
    {
        Negocio::factory()->create(['whatsapp' => '4271803585']);

        $this->get(route('cuidado-de-tenis'))
            ->assertSee('Mandar una foto por WhatsApp')
            ->assertSee('https://wa.me/524271803585', false)
            ->assertSee(route('precios'), false);
    }

    public function test_sin_whatsapp_cargado_la_guia_no_manda_a_escribir(): void
    {
        $this->get(route('cuidado-de-tenis'))
            ->assertDontSee('Mandar una foto por WhatsApp')
            ->assertSee(route('precios'), false);
    }

    public function test_la_portada_lleva_a_la_guia(): void
    {
        $this->get(route('home'))->assertSee(route('cuidado-de-tenis'), false);
    }

    public function test_desde_la_guia_el_menu_vuelve_a_la_portada(): void
    {
        $respuesta = $this->get(route('cuidado-de-tenis'));

        $respuesta->assertSee('href="'.route('home').'#servicios"', false);
        $respuesta->assertDontSee('href="#servicios"', false);
    }

    public function test_en_la_portada_el_menu_salta_dentro_de_la_misma_pagina(): void
    {
        $this->get(route('home'))->assertSee('href="#servicios"', false);
    }
}
