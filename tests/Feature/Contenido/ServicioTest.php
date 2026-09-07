<?php

namespace Tests\Feature\Contenido;

use App\Models\Servicio;
use App\Models\Trabajo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServicioTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_servicio_escribe_su_precio_sin_signo_de_mas(): void
    {
        $servicio = Servicio::factory()->create(['precio' => 120]);

        $this->assertSame('$120', $servicio->precio_formateado);
    }

    public function test_un_extra_escribe_su_precio_con_signo_de_mas(): void
    {
        $extra = Servicio::factory()->extra()->create(['precio' => 100]);

        $this->assertSame('+$100', $extra->precio_formateado);
    }

    public function test_borrar_un_servicio_no_borra_sus_trabajos(): void
    {
        $servicio = Servicio::factory()->create();
        $trabajo = Trabajo::factory()->create(['servicio_id' => $servicio->id]);

        $servicio->delete();

        $this->assertModelExists($trabajo->fresh());
        $this->assertNull($trabajo->fresh()->servicio_id);
    }

    public function test_el_catalogo_deja_los_extras_afuera(): void
    {
        Servicio::factory()->create(['nombre' => 'Limpieza básica']);
        Servicio::factory()->extra()->create(['nombre' => 'Entrega express']);

        $this->assertSame(['Limpieza básica'], Servicio::query()->catalogo()->pluck('nombre')->all());
        $this->assertSame(['Entrega express'], Servicio::query()->extras()->pluck('nombre')->all());
    }

    public function test_los_servicios_inactivos_quedan_fuera(): void
    {
        Servicio::factory()->create(['nombre' => 'Botas']);
        Servicio::factory()->inactivo()->create(['nombre' => 'Bolsas']);

        $this->assertSame(['Botas'], Servicio::query()->activos()->pluck('nombre')->all());
    }
}
