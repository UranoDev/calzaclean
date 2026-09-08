<?php

namespace Tests\Feature\Sitio;

use App\Enums\Material;
use App\Fotos\Variante;
use App\Models\Negocio;
use App\Models\Pregunta;
use App\Models\Testimonio;
use App\Models\Trabajo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortadaTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_portada_responde_y_lleva_la_linea_comercial(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Cada material, su técnica.', false);
    }

    public function test_la_portada_rotula_todas_las_secciones_del_recorrido(): void
    {
        // Preguntas, Testimonios y Contacto solo se dibujan con contenido
        // publicado: el recorrido completo se ve con el Sitio ya cargado.
        Negocio::factory()->create();
        Pregunta::factory()->create();
        Testimonio::factory()->create();

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
        Negocio::factory()->create(['whatsapp' => '52 442 123 4567']);

        $response = $this->get(route('home'));

        $response->assertSee('https://wa.me/524421234567', false);
    }

    public function test_sin_numero_cargado_no_se_muestra_el_boton_de_whatsapp(): void
    {
        Negocio::factory()->sinWhatsapp()->create();

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('wa.me', false);
    }

    public function test_el_pie_no_dibuja_redes_cuando_no_hay_ninguna_cargada(): void
    {
        Negocio::factory()->sinRedes()->create();

        $response = $this->get(route('home'));

        $response->assertDontSee('Instagram', false);
    }

    public function test_la_portada_ofrece_cotizar_por_whatsapp_y_ver_precios(): void
    {
        Negocio::factory()->create();

        $response = $this->get(route('home'));

        $response->assertSee('Cotizar por WhatsApp', false);
        $response->assertSee('Ver precios', false);
        $response->assertSee(rawurlencode('Hola, quiero cotizar la limpieza de mis tenis.'), false);
    }

    public function test_la_portada_lleva_las_cuatro_senales_de_confianza(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('Limpieza a mano', false);
        $response->assertSee('Un producto por material', false);
        $response->assertSee('San Juan del Río', false);
        $response->assertSee('Entrega en 72 horas', false);
    }

    public function test_la_portada_muestra_el_primer_trabajo_publicado_de_la_lista(): void
    {
        // El más reciente va segundo en la lista: lo que manda es el orden, no
        // la fecha de alta.
        $primero = Trabajo::factory()->create([
            'titulo' => 'Air Force 1 blancos',
            'material' => Material::Lona,
            'orden' => 1,
            'created_at' => now()->subWeek(),
        ]);

        Trabajo::factory()->create([
            'titulo' => 'Botas de gamuza',
            'orden' => 2,
            'created_at' => now(),
        ]);

        // La galería de más abajo enseña los dos, así que la comparación se
        // hace contra lo que va antes de la sección de Trabajos.
        $portada = $this->portada($this->get(route('home'))->getContent());

        $this->assertStringContainsString($primero->antes()->webp(Variante::Grande), $portada);
        $this->assertStringContainsString($primero->despues()->webp(Variante::Grande), $portada);
        $this->assertStringContainsString('Air Force 1 blancos', $portada);
        $this->assertStringNotContainsString('Botas de gamuza', $portada);
    }

    public function test_subir_un_trabajo_al_primer_lugar_lo_pone_en_la_portada(): void
    {
        Trabajo::factory()->create(['titulo' => 'Botas de gamuza', 'orden' => 1]);
        $segundo = Trabajo::factory()->create(['titulo' => 'Air Force 1 blancos', 'orden' => 2]);

        $antes = $this->portada($this->get(route('home'))->getContent());

        $this->assertStringContainsString('Botas de gamuza', $antes);

        $segundo->mover(-1);

        $despues = $this->portada($this->get(route('home'))->getContent());

        $this->assertStringContainsString('Air Force 1 blancos', $despues);
        $this->assertStringNotContainsString('Botas de gamuza', $despues);
    }

    public function test_un_trabajo_sin_publicar_en_el_primer_lugar_no_deja_la_portada_vacia(): void
    {
        $borrador = Trabajo::factory()->borrador()->create(['titulo' => 'Todavía sin publicar', 'orden' => 1]);
        $publicado = Trabajo::factory()->create(['titulo' => 'Air Force 1 blancos', 'orden' => 2]);

        $portada = $this->portada($this->get(route('home'))->getContent());

        $this->assertStringContainsString($publicado->antes()->webp(Variante::Grande), $portada);
        $this->assertStringContainsString('Air Force 1 blancos', $portada);
        $this->assertStringNotContainsString($borrador->antes()->webp(Variante::Grande), $portada);
        $this->assertStringNotContainsString('/img/logo-original.jpeg', $portada);
    }

    public function test_un_trabajo_sin_publicar_no_llega_a_la_portada(): void
    {
        $borrador = Trabajo::factory()->borrador()->create(['created_at' => now()]);

        $response = $this->get(route('home'));

        $response->assertDontSee($borrador->antes()->webp(Variante::Grande), false);
        $response->assertSee('/img/logo-original.jpeg', false);
    }

    public function test_sin_trabajos_publicados_la_portada_usa_la_imagen_de_respaldo(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('/img/logo-original.jpeg', false);
        $this->assertFileExists(public_path('img/logo-original.jpeg'));
    }

    public function test_la_foto_de_portada_carga_de_inmediato_y_el_resto_del_sitio_diferido(): void
    {
        Trabajo::factory()->create();

        $html = $this->get(route('home'))->getContent();

        // De inmediato entran el par de la portada y el logotipo del
        // encabezado. Diferidos, la miniatura del Trabajo en la galería, su par
        // dentro del comparador y el logotipo del pie.
        $this->assertSame(3, substr_count($html, 'loading="eager"'));
        $this->assertSame(4, substr_count($html, 'loading="lazy"'));
        $this->assertSame(2, substr_count($html, 'width="1600" height="1200"'));
    }

    public function test_la_imagen_de_respaldo_tambien_declara_sus_dimensiones(): void
    {
        $html = $this->get(route('home'))->getContent();

        // La imagen de respaldo y el logotipo del encabezado.
        $this->assertSame(2, substr_count($html, 'loading="eager"'));
        $this->assertStringContainsString('width="1002"', $html);
        $this->assertStringContainsString('height="612"', $html);
    }

    public function test_sin_aviso_no_hay_franja_arriba_de_la_portada(): void
    {
        Negocio::factory()->create();

        $response = $this->get(route('home'));

        $response->assertDontSee('role="status"', false);
    }

    public function test_con_aviso_encendido_la_franja_va_antes_que_la_portada(): void
    {
        Negocio::factory()->conAviso('Cerramos del 24 al 26 de diciembre.')->create();

        $html = $this->get(route('home'))->getContent();

        $this->assertStringContainsString('Cerramos del 24 al 26 de diciembre.', $html);
        $this->assertLessThan(
            strpos($html, 'id="portada"'),
            strpos($html, 'Cerramos del 24 al 26 de diciembre.'),
        );
    }

    /**
     * El tramo de la página que va antes de la sección de Trabajos.
     */
    private function portada(string $html): string
    {
        return substr($html, 0, (int) strpos($html, 'id="trabajos"'));
    }
}
