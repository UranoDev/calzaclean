<?php

namespace Tests\Feature\Contenido;

use App\Enums\Material;
use App\Models\ColoniaRecoleccion;
use App\Models\Pregunta;
use App\Models\Testimonio;
use App\Models\Trabajo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContenidoTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_trabajo_guarda_su_material_como_enum(): void
    {
        $trabajo = Trabajo::factory()->create(['material' => Material::Gamuza]);

        $this->assertSame(Material::Gamuza, $trabajo->fresh()->material);
        $this->assertSame('Gamuza', $trabajo->material->etiqueta());
    }

    public function test_los_trabajos_sin_publicar_quedan_fuera(): void
    {
        Trabajo::factory()->create(['titulo' => 'Air Force 1 blancos']);
        Trabajo::factory()->borrador()->create(['titulo' => 'Botas de gamuza']);

        $this->assertSame(['Air Force 1 blancos'], Trabajo::query()->publicados()->pluck('titulo')->all());
    }

    public function test_borrar_un_trabajo_no_borra_su_testimonio(): void
    {
        $testimonio = Testimonio::factory()->conTrabajo()->create();

        $testimonio->trabajo->delete();

        $this->assertModelExists($testimonio->fresh());
        $this->assertNull($testimonio->fresh()->trabajo_id);
    }

    public function test_las_preguntas_sin_publicar_quedan_fuera(): void
    {
        Pregunta::factory()->create(['pregunta' => '¿Cuánto tardan?']);
        Pregunta::factory()->borrador()->create(['pregunta' => '¿Recogen a domicilio?']);

        $this->assertSame(['¿Cuánto tardan?'], Pregunta::query()->publicadas()->pluck('pregunta')->all());
    }

    public function test_las_colonias_inactivas_quedan_fuera(): void
    {
        ColoniaRecoleccion::factory()->create(['nombre' => 'Centro']);
        ColoniaRecoleccion::factory()->inactiva()->create(['nombre' => 'La Valla']);

        $this->assertSame(['Centro'], ColoniaRecoleccion::query()->activas()->pluck('nombre')->all());
    }

    public function test_solo_un_trabajo_publicado_le_pone_foto_al_testimonio(): void
    {
        $testimonio = Testimonio::factory()->conTrabajo()->create();

        $this->assertNotNull($testimonio->trabajoVisible());

        $testimonio->trabajo->update(['publicado' => false]);

        $this->assertNull($testimonio->fresh()->trabajoVisible());
    }

    public function test_los_testimonios_pueden_no_tener_trabajo(): void
    {
        $testimonio = Testimonio::factory()->create();

        $this->assertNull($testimonio->trabajo_id);
        $this->assertNull($testimonio->trabajo);
    }
}
