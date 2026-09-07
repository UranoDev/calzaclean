<?php

namespace Tests\Feature\Sitio;

use App\Models\ColoniaRecoleccion;
use App\Models\Negocio;
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
        $this->assertStringContainsString('Te avisamos cuando está', $html);
    }

    public function test_los_pasos_se_leen_en_orden(): void
    {
        $html = $this->get(route('home'))->getContent();

        $posiciones = [
            strpos($html, 'Nos escribes por WhatsApp con una foto'),
            strpos($html, 'Dejas el par'),
            strpos($html, 'Lo lavamos a mano según el material'),
            strpos($html, 'Te avisamos cuando está'),
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
        $this->get(route('home'))
            ->assertSee('La entrega es en 72 horas. Con la entrega express, +$100, sale en menos de 24.', false);
    }

    public function test_sin_colonias_activas_no_hay_bloque_de_recoleccion(): void
    {
        ColoniaRecoleccion::factory()->inactiva()->create(['nombre' => 'Centro']);

        $response = $this->get(route('home'));

        $response->assertDontSee('Recolección a domicilio', false);
        $response->assertDontSee('Centro', false);
        $response->assertSee('Lo traes al taller, en San Juan del Río.', false);
    }

    public function test_con_colonias_activas_aparece_la_lista_y_el_paso_la_menciona(): void
    {
        Negocio::factory()->create(['whatsapp' => '52 442 123 4567']);
        ColoniaRecoleccion::factory()->create(['nombre' => 'La Peña', 'orden' => 2]);
        ColoniaRecoleccion::factory()->create(['nombre' => 'Centro', 'orden' => 1]);
        ColoniaRecoleccion::factory()->inactiva()->create(['nombre' => 'Vista Hermosa']);

        $response = $this->get(route('home'));

        $response->assertSee('Recolección a domicilio', false);
        $response->assertSee('La Peña', false);
        $response->assertDontSee('Vista Hermosa', false);
        $response->assertSee('pasamos por él si tu colonia está en la lista de abajo', false);

        $html = $response->getContent();
        $this->assertLessThan(strpos($html, 'La Peña'), strpos($html, 'Centro'));
    }

    public function test_la_recoleccion_manda_a_preguntar_por_whatsapp(): void
    {
        Negocio::factory()->create(['whatsapp' => '52 442 123 4567']);
        ColoniaRecoleccion::factory()->create(['nombre' => 'Centro']);

        $this->get(route('home'))
            ->assertSee(rawurlencode('Hola, quiero saber si pasan a recoger mis tenis en mi colonia.'), false);
    }

    public function test_la_recoleccion_no_promete_costo_ni_minimo_de_pares(): void
    {
        Negocio::factory()->create(['whatsapp' => '52 442 123 4567']);
        ColoniaRecoleccion::factory()->create(['nombre' => 'Centro']);

        $html = $this->get(route('home'))->getContent();
        $bloque = substr($html, (int) strpos($html, 'Recolección a domicilio'));
        $bloque = substr($bloque, 0, (int) strpos($bloque, 'id="materiales"'));

        // El costo por zona y el mínimo de pares siguen sin decidirse
        // (CONTEXT.md § Lo que sigue abierto): el bloque no los inventa.
        $this->assertStringNotContainsString('$', $bloque);
        $this->assertStringNotContainsString('mínimo', $bloque);
        $this->assertStringNotContainsString('pares', $bloque);
    }
}
