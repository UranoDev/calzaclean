<?php

namespace Tests\Feature\Sitio;

use Tests\TestCase;

class PortadaTest extends TestCase
{
    public function test_la_portada_responde_y_lleva_la_linea_comercial(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Cada material, su técnica.', false);
    }

    public function test_la_portada_rotula_todas_las_secciones_del_recorrido(): void
    {
        $response = $this->get(route('home'));

        foreach (['servicios', 'trabajos', 'como-funciona', 'materiales', 'preguntas', 'testimonios', 'contacto'] as $ancla) {
            $response->assertSee('id="'.$ancla.'"', false);
        }
    }

    public function test_el_pie_lleva_el_eslogan_del_logo(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('Revive tus tenis, revive tu juego', false);
    }

    public function test_el_boton_de_whatsapp_apunta_al_numero_del_negocio(): void
    {
        config(['sitio.whatsapp' => '52 442 123 4567']);

        $response = $this->get(route('home'));

        $response->assertSee('https://wa.me/524421234567', false);
    }

    public function test_sin_numero_cargado_no_se_muestra_el_boton_de_whatsapp(): void
    {
        config(['sitio.whatsapp' => null]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('wa.me', false);
    }

    public function test_el_pie_no_dibuja_redes_cuando_no_hay_ninguna_cargada(): void
    {
        config(['sitio.redes' => []]);

        $response = $this->get(route('home'));

        $response->assertDontSee('Instagram', false);
    }
}
