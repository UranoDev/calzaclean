<?php

namespace Tests\Feature\Sitio;

use App\Models\Negocio;
use App\Models\Servicio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreciosTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_seccion_de_la_portada_lista_los_servicios_activos(): void
    {
        Servicio::factory()->create(['nombre' => 'Limpieza básica', 'aplica_a' => 'Cuero, piel y tela', 'precio' => 120]);

        $response = $this->get(route('home'));

        $response->assertSee('Limpieza básica', false);
        $response->assertSee('Cuero, piel y tela', false);
        $response->assertSee('$120', false);
    }

    public function test_un_servicio_desactivado_no_aparece_en_ninguna_de_las_dos_vistas(): void
    {
        Servicio::factory()->inactivo()->create(['nombre' => 'Bolsas']);
        Servicio::factory()->extra()->inactivo()->create(['nombre' => 'Entrega express']);

        $this->get(route('home'))->assertDontSee('Bolsas', false)->assertDontSee('Entrega express', false);
        $this->get(route('precios'))->assertDontSee('Bolsas', false)->assertDontSee('Entrega express', false);
    }

    public function test_los_extras_van_al_final_y_con_el_signo_de_mas(): void
    {
        Servicio::factory()->create(['nombre' => 'Limpieza básica', 'precio' => 120, 'orden' => 1]);
        Servicio::factory()->extra()->create(['nombre' => 'Blanqueamiento de suelas', 'precio' => 50, 'orden' => 2]);

        $html = $this->get(route('precios'))->getContent();

        $this->assertStringContainsString('+$50', $html);
        $this->assertGreaterThan(
            strpos($html, 'Limpieza básica'),
            strpos($html, 'Blanqueamiento de suelas'),
        );
    }

    public function test_los_extras_aclaran_que_se_suman_al_servicio(): void
    {
        Servicio::factory()->extra()->create(['nombre' => 'Entrega express']);

        $this->get(route('precios'))->assertSee('Se suman al precio del servicio.', false);
    }

    public function test_sin_extras_cargados_no_se_dibuja_el_grupo(): void
    {
        Servicio::factory()->create(['nombre' => 'Limpieza básica']);

        $this->get(route('precios'))->assertDontSee('Se suman al precio del servicio.', false);
    }

    public function test_la_lista_ofrece_mandar_una_foto_por_whatsapp(): void
    {
        Negocio::factory()->create(['whatsapp' => '52 442 123 4567']);
        Servicio::factory()->create();

        $response = $this->get(route('precios'));

        $response->assertSee('¿No sabes cuál te toca? Mándanos una foto y te decimos.', false);
        $response->assertSee(rawurlencode('Hola, les mando una foto de mis tenis para saber qué servicio me toca.'), false);
    }

    public function test_los_precios_usan_cifras_tabulares(): void
    {
        Servicio::factory()->create(['precio' => 120]);
        Servicio::factory()->extra()->create(['precio' => 50]);

        $html = $this->get(route('precios'))->getContent();

        $this->assertSame(2, substr_count($html, 'tabular-nums'));
    }

    public function test_la_portada_lleva_a_la_pagina_de_precios(): void
    {
        Servicio::factory()->create();

        $this->get(route('home'))->assertSee(route('precios'), false);
    }

    public function test_la_pagina_de_precios_responde_con_el_logo_y_su_encabezado(): void
    {
        $response = $this->get(route('precios'));

        $response->assertOk();
        $response->assertSee('<h1', false);
        $response->assertSee('Precios', false);
        $response->assertSee('/img/logo-calzaclean.png', false);
    }

    public function test_la_pagina_de_precios_deja_fuera_la_navegacion_y_el_pie(): void
    {
        Negocio::factory()->create();

        $response = $this->get(route('precios'));

        $response->assertDontSee('Secciones del sitio', false);
        $response->assertDontSee('Revive tus tenis, revive tu juego', false);
        $response->assertDontSee('#como-funciona', false);
    }

    public function test_la_pagina_de_precios_declara_su_imagen_de_vista_previa(): void
    {
        $response = $this->get(route('precios'));

        $response->assertSee('property="og:image" content="'.url('/img/precios-vista-previa.png').'"', false);
        $response->assertSee('name="twitter:card"', false);
        $this->assertFileExists(public_path('img/precios-vista-previa.png'));
    }

    public function test_sin_servicios_cargados_la_lista_lo_dice(): void
    {
        $this->get(route('precios'))->assertSee('Todavía no hay precios cargados.', false);
    }
}
