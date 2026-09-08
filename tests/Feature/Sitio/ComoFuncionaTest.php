<?php

namespace Tests\Feature\Sitio;

use App\Models\Negocio;
use App\Models\ZonaRecoleccion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComoFuncionaTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_cuatro_pasos_van_en_una_lista_ordenada(): void
    {
        $html = $this->get(route('home'))->getContent();

        $this->assertStringContainsString('<ol role="list"', $html);
        $this->assertStringContainsString('Nos escribes por WhatsApp con una foto', $html);
        $this->assertStringContainsString('Dejas el par', $html);
        $this->assertStringContainsString('Lo lavamos a mano según el material', $html);
        $this->assertStringContainsString('Pasas por tus tenis, completamente renovados', $html);
    }

    public function test_los_pasos_se_leen_en_orden(): void
    {
        $html = $this->get(route('home'))->getContent();

        $posiciones = [
            strpos($html, 'Nos escribes por WhatsApp con una foto'),
            strpos($html, 'Dejas el par'),
            strpos($html, 'Lo lavamos a mano según el material'),
            strpos($html, 'Pasas por tus tenis, completamente renovados'),
        ];

        $ordenadas = $posiciones;
        sort($ordenadas);

        $this->assertSame($ordenadas, $posiciones);
    }

    public function test_el_numero_que_se_ve_no_se_lee_dos_veces(): void
    {
        $html = $this->get(route('home'))->getContent();

        // El círculo con el número es decoración: el orden ya lo anuncia el `ol`.
        $this->assertMatchesRegularExpression('/aria-hidden="true"\s*>1</', $html);
    }

    public function test_el_paso_de_entrega_dice_72_horas_y_el_extra_express(): void
    {
        // El plazo se dice al dejar el par, que es cuando la persona pregunta.
        $this->get(route('home'))
            ->assertSee('La entrega es en 72 horas; con el servicio express, +$100, sale en menos de 24.', false);
    }

    public function test_sin_zonas_activas_no_hay_bloque_de_recoleccion(): void
    {
        ZonaRecoleccion::factory()->inactiva()->create(['nombre' => 'Centro']);

        $response = $this->get(route('home'));

        $response->assertDontSee('Recolección a domicilio', false);
        $response->assertDontSee('Centro', false);
        $response->assertSee('Lo traes al taller, en San Juan del Río.', false);
    }

    public function test_con_zonas_activas_aparece_la_lista_y_el_paso_la_menciona(): void
    {
        Negocio::factory()->create(['whatsapp' => '52 442 123 4567']);
        ZonaRecoleccion::factory()->create(['nombre' => 'La Peña', 'orden' => 2]);
        ZonaRecoleccion::factory()->create(['nombre' => 'Centro', 'orden' => 1]);
        ZonaRecoleccion::factory()->inactiva()->create(['nombre' => 'Vista Hermosa']);

        $response = $this->get(route('home'));

        $response->assertSee('Recolección a domicilio', false);
        $response->assertSee('La Peña', false);
        $response->assertDontSee('Vista Hermosa', false);
        $response->assertSee('pasamos por él si tu zona está en la lista de abajo', false);

        $html = $response->getContent();
        $this->assertLessThan(strpos($html, 'La Peña'), strpos($html, 'Centro'));
    }

    public function test_la_recoleccion_manda_a_preguntar_por_whatsapp(): void
    {
        Negocio::factory()->create(['whatsapp' => '52 442 123 4567']);
        ZonaRecoleccion::factory()->create(['nombre' => 'Centro']);

        $this->get(route('home'))
            ->assertSee(rawurlencode('Hola, quiero saber si pasan a recoger mis tenis en mi zona.'), false);
    }

    public function test_cada_zona_se_lista_con_su_costo(): void
    {
        Negocio::factory()->create(['whatsapp' => '52 442 123 4567']);
        ZonaRecoleccion::factory()->create(['nombre' => 'Centro', 'orden' => 1]);
        ZonaRecoleccion::factory()->conCosto(50)->create(['nombre' => 'Fuera del centro', 'orden' => 2]);

        $this->get(route('home'))
            ->assertSee('Centro — sin costo', false)
            ->assertSee('Fuera del centro — +$50', false);
    }

    public function test_una_zona_sin_costo_no_se_escribe_con_cero(): void
    {
        Negocio::factory()->create(['whatsapp' => '52 442 123 4567']);
        ZonaRecoleccion::factory()->create(['nombre' => 'Centro']);

        // Un cero en una lista de precios se lee como error.
        $this->get(route('home'))->assertDontSee('Centro — $0', false);
    }

    public function test_la_recoleccion_dice_que_se_recoge_desde_un_solo_par(): void
    {
        Negocio::factory()->create(['whatsapp' => '52 442 123 4567']);
        ZonaRecoleccion::factory()->create(['nombre' => 'Centro']);

        $this->get(route('home'))->assertSee('Recogemos desde un solo par', false);
    }
}
