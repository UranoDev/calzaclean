<?php

namespace Tests\Feature\Sitio;

use App\Models\Pregunta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreguntasTest extends TestCase
{
    use RefreshDatabase;

    public function test_sin_preguntas_publicadas_la_seccion_no_se_dibuja(): void
    {
        Pregunta::factory()->borrador()->create(['pregunta' => '¿Lavan botas de trabajo?']);

        $respuesta = $this->get(route('home'));

        $respuesta->assertDontSee('Preguntas frecuentes');
        $respuesta->assertDontSee('id="preguntas"', false);
        $respuesta->assertDontSee('¿Lavan botas de trabajo?');
    }

    public function test_sin_preguntas_publicadas_el_menu_no_lleva_a_la_seccion(): void
    {
        $this->get(route('home'))->assertDontSee('href="#preguntas"', false);
    }

    public function test_las_publicadas_salen_en_el_orden_del_panel(): void
    {
        Pregunta::factory()->create(['pregunta' => '¿Cuánto tardan?', 'orden' => 2]);
        Pregunta::factory()->create(['pregunta' => '¿Cómo pago?', 'orden' => 1]);
        Pregunta::factory()->borrador()->create(['pregunta' => '¿Lavan botas de trabajo?']);

        $respuesta = $this->get(route('home'));

        $respuesta->assertSee('Preguntas frecuentes');
        $respuesta->assertSee('href="#preguntas"', false);
        $respuesta->assertDontSee('¿Lavan botas de trabajo?');

        $html = $respuesta->getContent();

        $this->assertLessThan(
            strpos($html, '¿Cuánto tardan?'),
            strpos($html, '¿Cómo pago?'),
        );
    }

    public function test_la_respuesta_de_cada_pregunta_llega_en_el_html(): void
    {
        Pregunta::factory()->create([
            'pregunta' => '¿Cuánto tardan?',
            'respuesta' => 'La entrega es en 72 horas.',
        ]);

        $this->get(route('home'))->assertSee('La entrega es en 72 horas.');
    }

    public function test_sin_javascript_las_respuestas_se_ven_todas(): void
    {
        Pregunta::factory()->count(3)->create();

        $html = $this->get(route('home'))->getContent();

        // El acordeón llega abierto y lo cierra el script: quien no corre
        // JavaScript lee las tres respuestas completas.
        $acordeon = substr($html, (int) strpos($html, 'data-acordeon'));
        $acordeon = substr($acordeon, 0, (int) strpos($acordeon, '</ul>'));

        $this->assertSame(3, substr_count($acordeon, '<details open'));
    }
}
