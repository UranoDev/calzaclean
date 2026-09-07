<?php

namespace Tests\Feature\Sitio;

use App\Enums\Material;
use App\Models\Testimonio;
use App\Models\Trabajo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestimoniosTest extends TestCase
{
    use RefreshDatabase;

    public function test_sin_testimonios_publicados_la_seccion_no_se_dibuja(): void
    {
        Testimonio::factory()->borrador()->create(['nombre' => 'Ximena']);

        $respuesta = $this->get(route('home'));

        $respuesta->assertDontSee('Testimonios');
        $respuesta->assertDontSee('id="testimonios"', false);
        $respuesta->assertDontSee('Ximena');
    }

    public function test_los_publicados_salen_con_nombre_y_texto_en_el_orden_del_panel(): void
    {
        Testimonio::factory()->create(['nombre' => 'Ricardo', 'texto' => 'Quedaron como nuevos.', 'orden' => 2]);
        Testimonio::factory()->create(['nombre' => 'Ximena', 'texto' => 'Me los entregaron a tiempo.', 'orden' => 1]);
        Testimonio::factory()->borrador()->create(['nombre' => 'Sofía']);

        $respuesta = $this->get(route('home'));

        $respuesta->assertSee('Testimonios');
        $respuesta->assertSee('Ricardo');
        $respuesta->assertSee('Quedaron como nuevos.');
        $respuesta->assertDontSee('Sofía');

        $html = $respuesta->getContent();

        $this->assertLessThan(strpos($html, 'Ricardo'), strpos($html, 'Ximena'));
    }

    public function test_el_testimonio_ligado_a_un_trabajo_lleva_su_foto_de_despues(): void
    {
        $trabajo = Trabajo::factory()->create([
            'material' => Material::Gamuza,
            'foto_despues' => 'trabajos/2026/09/par-de-gamuza-despues',
        ]);

        Testimonio::factory()->create(['nombre' => 'Ximena', 'trabajo_id' => $trabajo->id]);

        $html = $this->get(route('home'))->getContent();

        // El mismo Trabajo también está en la galería: la foto se busca dentro
        // de la sección de Testimonios, no en toda la página.
        $seccion = substr($html, (int) strpos($html, 'id="testimonios"'));

        $this->assertStringContainsString('par-de-gamuza-despues-miniatura.webp', $seccion);
        $this->assertStringContainsString('Después: Gamuza', $seccion);
    }

    public function test_el_testimonio_de_un_trabajo_despublicado_se_muestra_sin_foto(): void
    {
        $trabajo = Trabajo::factory()->borrador()->create([
            'foto_despues' => 'trabajos/2026/09/par-despublicado-despues',
        ]);

        Testimonio::factory()->create(['nombre' => 'Ximena', 'trabajo_id' => $trabajo->id]);

        $respuesta = $this->get(route('home'));

        $respuesta->assertSee('Ximena');
        $respuesta->assertDontSee('par-despublicado-despues-miniatura.webp', false);
    }

    public function test_el_testimonio_sin_trabajo_se_muestra_solo_con_su_texto(): void
    {
        Testimonio::factory()->create(['nombre' => 'Ximena', 'texto' => 'Me los entregaron a tiempo.']);

        $this->get(route('home'))
            ->assertSee('Ximena')
            ->assertSee('Me los entregaron a tiempo.');
    }
}
