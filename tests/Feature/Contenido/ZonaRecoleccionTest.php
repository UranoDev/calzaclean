<?php

namespace Tests\Feature\Contenido;

use App\Models\Servicio;
use App\Models\ZonaRecoleccion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZonaRecoleccionTest extends TestCase
{
    use RefreshDatabase;

    public function test_una_zona_sin_costo_lo_escribe_con_palabras(): void
    {
        $zona = ZonaRecoleccion::factory()->create(['costo' => 0]);

        $this->assertSame('sin costo', $zona->costo_formateado);
    }

    public function test_una_zona_con_costo_lo_escribe_con_signo_de_mas(): void
    {
        $zona = ZonaRecoleccion::factory()->conCosto(50)->create();

        $this->assertSame('+$50', $zona->costo_formateado);
    }

    public function test_el_signo_de_mas_sale_de_la_misma_regla_que_los_extras(): void
    {
        $zona = ZonaRecoleccion::factory()->conCosto(1500)->create();
        $extra = Servicio::factory()->extra()->create(['precio' => 1500]);

        $this->assertSame($extra->precio_formateado, $zona->costo_formateado);
    }

    public function test_las_zonas_se_ordenan_por_orden_y_despues_por_nombre(): void
    {
        ZonaRecoleccion::factory()->create(['nombre' => 'Fuera del centro', 'orden' => 2]);
        ZonaRecoleccion::factory()->create(['nombre' => 'Centro', 'orden' => 1]);

        $this->assertSame(
            ['Centro', 'Fuera del centro'],
            ZonaRecoleccion::query()->ordenadas()->pluck('nombre')->all(),
        );
    }
}
