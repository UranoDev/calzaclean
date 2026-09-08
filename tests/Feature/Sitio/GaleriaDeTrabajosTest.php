<?php

namespace Tests\Feature\Sitio;

use App\Enums\Material;
use App\Fotos\Variante;
use App\Models\Servicio;
use App\Models\Trabajo;
use Illuminate\Database\Eloquent\Collection;
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

    /**
     * Seis pares publicados, en el orden que la Dueña acomodó con las flechas.
     *
     * @return Collection<int, Trabajo>
     */
    private function seisPares(): Collection
    {
        return Trabajo::factory()->count(6)->sequence(fn ($secuencia) => [
            'titulo' => 'Par número '.($secuencia->index + 1),
            'orden' => $secuencia->index + 1,
        ])->create();
    }

    public function test_la_portada_trae_cuatro_pares_y_el_boton_dice_cuantos_quedan(): void
    {
        $this->seisPares();

        $response = $this->get(route('home'));

        $response->assertSee('Par número 4', false);
        $response->assertDontSee('Par número 5', false);
        $response->assertDontSee('Par número 6', false);
        $response->assertSee('Ver más resultados (quedan 2)', false);
    }

    public function test_con_cuatro_o_menos_publicados_el_boton_no_se_dibuja(): void
    {
        Trabajo::factory()->count(4)->create();

        $this->get(route('home'))->assertDontSee('Ver más resultados', false);
    }

    public function test_sin_javascript_el_boton_es_un_enlace_a_la_pagina_de_resultados(): void
    {
        $this->seisPares();

        $response = $this->get(route('home'));

        $response->assertSee('href="'.route('resultados').'"', false);

        $this->get(route('resultados'))
            ->assertOk()
            ->assertSee('Par número 1', false)
            ->assertSee('Par número 6', false)
            ->assertDontSee('Ver más resultados', false);
    }

    public function test_la_siguiente_tanda_sigue_donde_quedo_la_rejilla(): void
    {
        $this->seisPares();

        $tanda = $this->get(route('resultados.mas', ['desde' => 4]));

        $tanda->assertOk();
        $tanda->assertSee('Par número 5', false);
        $tanda->assertSee('Par número 6', false);
        $tanda->assertDontSee('Par número 4', false);

        // Es la pieza suelta que se agrega a la rejilla, no una página.
        $tanda->assertDontSee('<title>', false);
    }

    public function test_los_despublicados_no_cuentan_para_el_total_ni_llegan_a_la_tanda(): void
    {
        Trabajo::factory()->count(4)->sequence(fn ($secuencia) => [
            'titulo' => 'Par número '.($secuencia->index + 1),
            'orden' => $secuencia->index + 1,
        ])->create();

        Trabajo::factory()->borrador()->count(3)->sequence(fn ($secuencia) => [
            'titulo' => 'Par a medio terminar '.($secuencia->index + 1),
            'orden' => $secuencia->index + 5,
        ])->create();

        $this->get(route('home'))->assertDontSee('Ver más resultados', false);

        $this->get(route('resultados.mas', ['desde' => 4]))
            ->assertOk()
            ->assertDontSee('Par a medio terminar', false);

        $this->get(route('resultados'))->assertDontSee('Par a medio terminar', false);
    }

    public function test_la_tanda_respeta_el_orden_de_las_flechas_del_panel(): void
    {
        $pares = $this->seisPares();

        // La Dueña sube el sexto al primer lugar: la portada lo enseña y la
        // tanda arranca desde donde quedó la rejilla, ya reacomodada.
        $pares->last()->update(['orden' => 0]);

        $this->get(route('home'))->assertSee('Par número 6', false);

        $tanda = $this->get(route('resultados.mas', ['desde' => 4]));

        $tanda->assertSee('Par número 4', false);
        $tanda->assertSee('Par número 5', false);
        $tanda->assertDontSee('Par número 6', false);
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
