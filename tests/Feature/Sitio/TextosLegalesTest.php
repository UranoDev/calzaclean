<?php

namespace Tests\Feature\Sitio;

use App\Models\ZonaRecoleccion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Las dos páginas de texto legal. Lo que se prueba acá no es la prosa —ninguna
 * prueba la lee— sino lo que se puede contradecir solo: que el aviso no hable
 * de lo que el Sitio no hace, que las tres cláusulas decididas estén, y que la
 * recolección no se prometa sin zonas cargadas.
 */
class TextosLegalesTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_dos_paginas_se_abren_y_dicen_desde_cuando_rigen(): void
    {
        foreach (['aviso-de-privacidad', 'terminos-y-condiciones'] as $pagina) {
            $this->get(route($pagina))
                ->assertOk()
                ->assertSee('Última actualización:');
        }
    }

    public function test_el_pie_lleva_a_las_dos_paginas_desde_la_portada(): void
    {
        $this->get(route('home'))
            ->assertSee(route('aviso-de-privacidad'), false)
            ->assertSee(route('terminos-y-condiciones'), false);
    }

    public function test_el_aviso_nombra_al_responsable_su_domicilio_y_su_correo(): void
    {
        $this->get(route('aviso-de-privacidad'))
            ->assertSee('Eli Mikael Rosales')
            ->assertSee('Antonio Caso #3, San Juan del Río, Querétaro, C.P. 76800')
            ->assertSee((string) config('mail.from.address'));
    }

    /**
     * El aviso describe lo que el Sitio hace de verdad: no hay formularios, no
     * hay analítica y no hay cookies de terceros. Un aviso copiado de una
     * plantilla habla de las tres.
     */
    public function test_el_aviso_no_inventa_lo_que_el_sitio_no_recoge(): void
    {
        $html = $this->get(route('aviso-de-privacidad'))->getContent();

        foreach (['a través de nuestros formularios', 'formulario de contacto', 'Google Analytics', 'cookies de terceros para'] as $frase) {
            $this->assertStringNotContainsString($frase, $html, "El aviso menciona [{$frase}], que no existe en el Sitio.");
        }
    }

    public function test_el_aviso_separa_las_finalidades_secundarias_y_dice_como_negarse(): void
    {
        $this->get(route('aviso-de-privacidad'))
            ->assertSee('Finalidades secundarias')
            ->assertSee('Cómo negarse a las secundarias')
            ->assertSee('Negarse a las finalidades secundarias no cambia el servicio ni su precio.')
            ->assertSee('Derechos ARCO')
            ->assertSee('Cómo revocar el consentimiento')
            ->assertSee('Hoy no se transfieren datos personales a terceros.');
    }

    public function test_los_terminos_traen_las_tres_clausulas_decididas_con_sus_plazos(): void
    {
        $respuesta = $this->get(route('terminos-y-condiciones'));

        $respuesta->assertSee('Garantía de relavado');
        $respuesta->assertSee('se relava sin costo');

        $respuesta->assertSee('Calzado no recogido');
        $respuesta->assertSee('El par se guarda un mes, contado desde el día en que el taller avisó que estaba listo.');
        $respuesta->assertSee('el par se dona');

        $respuesta->assertSee('Responsabilidad por daño');
        $respuesta->assertSee('el taller responde hasta el monto del servicio contratado');
    }

    public function test_los_terminos_dicen_los_plazos_y_dejan_mandando_al_catalogo(): void
    {
        $this->get(route('terminos-y-condiciones'))
            ->assertSee('La entrega es en 72 horas, contadas desde que el par queda en el taller.')
            ->assertSee('el par sale en menos de 24 horas')
            ->assertSee('Si un precio de esta página no coincide con esa lista, el que vale es el de la lista.')
            ->assertSee(route('precios'), false);
    }

    public function test_los_terminos_dicen_lo_que_no_se_acepta(): void
    {
        $this->get(route('terminos-y-condiciones'))
            ->assertSee('Calzado con la suela despegada.')
            ->assertSee('Calzado con hongos.')
            ->assertSee('Una mancha advertida antes de empezar no da lugar a devolución.');
    }

    public function test_sin_zonas_cargadas_los_terminos_no_prometen_recoleccion(): void
    {
        $this->get(route('terminos-y-condiciones'))->assertDontSee('Recolección a domicilio');
    }

    public function test_con_zonas_cargadas_los_terminos_listan_cada_una_con_su_costo(): void
    {
        ZonaRecoleccion::factory()->create(['nombre' => 'Centro', 'costo' => 0, 'orden' => 1]);
        ZonaRecoleccion::factory()->create(['nombre' => 'Fuera del centro', 'costo' => 50, 'orden' => 2]);
        ZonaRecoleccion::factory()->create(['nombre' => 'Apagada', 'costo' => 90, 'activa' => false]);

        $this->get(route('terminos-y-condiciones'))
            ->assertSee('Recolección a domicilio')
            ->assertSee('se recoge desde un solo par')
            ->assertSee('Centro — sin costo')
            ->assertSee('Fuera del centro — +$50')
            ->assertDontSee('Apagada')
            ->assertDontSee('$0');
    }
}
