<?php

namespace Tests\Feature\Sitio;

use App\Enums\Material;
use App\Fotos\Variante;
use App\Models\Servicio;
use App\Models\Trabajo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GaleriaDeTrabajosTest extends TestCase
{
    use RefreshDatabase;

    public function test_sin_trabajos_publicados_la_seccion_sigue_en_pie(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('id="trabajos"', false);
        $response->assertSee('Todavía no hay trabajos en la galería.', false);
    }

    public function test_la_rejilla_lleva_el_material_y_el_servicio_de_cada_trabajo(): void
    {
        $servicio = Servicio::factory()->create(['nombre' => 'Limpieza especializada']);

        Trabajo::factory()->create([
            'titulo' => 'Air Force 1 blancos',
            'material' => Material::Lona,
            'servicio_id' => $servicio->id,
        ]);

        $response = $this->get(route('home'));

        $response->assertSee('Air Force 1 blancos', false);
        $response->assertSee('Lona', false);
        $response->assertSee('Limpieza especializada', false);
    }

    public function test_un_trabajo_sin_publicar_no_llega_a_la_rejilla(): void
    {
        $borrador = Trabajo::factory()->borrador()->create(['titulo' => 'Par a medio terminar']);

        $response = $this->get(route('home'));

        $response->assertDontSee('Par a medio terminar', false);
        $response->assertDontSee($borrador->despues()->webp(Variante::Miniatura), false);
        $response->assertDontSee($borrador->despues()->webp(Variante::Grande), false);
    }

    public function test_la_rejilla_sirve_miniaturas_y_el_comparador_la_foto_grande(): void
    {
        $trabajo = Trabajo::factory()->create();

        $response = $this->get(route('home'));

        $response->assertSee($trabajo->despues()->webp(Variante::Miniatura), false);
        $response->assertSee($trabajo->antes()->webp(Variante::Grande), false);
        $response->assertSee($trabajo->despues()->webp(Variante::Grande), false);
    }

    public function test_cada_foto_lleva_el_material_y_el_servicio_en_su_alt(): void
    {
        $servicio = Servicio::factory()->create(['nombre' => 'Limpieza básica']);

        Trabajo::factory()->create([
            'material' => Material::Gamuza,
            'servicio_id' => $servicio->id,
        ]);

        $response = $this->get(route('home'));

        $response->assertSee('alt="Antes: Gamuza, Limpieza básica"', false);
        $response->assertSee('alt="Después: Gamuza, Limpieza básica"', false);
    }

    public function test_un_trabajo_sin_servicio_en_el_catalogo_conserva_su_material_en_el_alt(): void
    {
        Trabajo::factory()->sinServicio()->create(['material' => Material::Cuero]);

        $response = $this->get(route('home'));

        $response->assertSee('alt="Antes: Cuero"', false);
        $response->assertSee('alt="Después: Cuero"', false);
    }

    public function test_la_manija_es_un_deslizador_con_su_propia_etiqueta(): void
    {
        Trabajo::factory()->create(['titulo' => 'Air Force 1 blancos']);

        $response = $this->get(route('home'));

        $response->assertSee('type="range"', false);
        $response->assertSee('aria-label="Mover para comparar el antes y el después de Air Force 1 blancos"', false);
    }

    public function test_la_rejilla_pagina_en_vez_de_traer_todos_los_trabajos(): void
    {
        Trabajo::factory()->count(8)->sequence(fn ($sequence) => [
            'titulo' => 'Par número '.($sequence->index + 1),
            'orden' => $sequence->index + 1,
        ])->create();

        $primera = $this->get(route('home'));

        $primera->assertSee('Par número 6', false);
        $primera->assertDontSee('Par número 7', false);
        $primera->assertSee('Ver más trabajos', false);

        $segunda = $this->get(route('home', ['trabajos' => 2]));

        $segunda->assertSee('Par número 7', false);
        $segunda->assertSee('Par número 8', false);
        $segunda->assertDontSee('Par número 6', false);
        $segunda->assertSee('Trabajos anteriores', false);
    }

    public function test_las_fotos_de_la_rejilla_no_compiten_con_la_portada_por_la_descarga(): void
    {
        Trabajo::factory()->count(3)->create();

        $html = $this->get(route('home'))->getContent();

        // De inmediato, las dos de la portada y el logotipo del encabezado, y
        // ni una más. Las diez diferidas son la miniatura y el par de cada uno
        // de los tres Trabajos, más el logotipo del pie.
        $this->assertSame(3, substr_count($html, 'loading="eager"'));
        $this->assertSame(10, substr_count($html, 'loading="lazy"'));
    }
}
