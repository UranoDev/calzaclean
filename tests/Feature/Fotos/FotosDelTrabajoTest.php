<?php

namespace Tests\Feature\Fotos;

use App\Fotos\ProcesadorDeFotos;
use App\Fotos\Variante;
use App\Models\Trabajo;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FotosDeEjemplo;
use Tests\TestCase;

class FotosDelTrabajoTest extends TestCase
{
    use RefreshDatabase;

    private Filesystem $disco;

    protected function setUp(): void
    {
        parent::setUp();

        $this->disco = Storage::fake('public');
    }

    public function test_borrar_un_trabajo_borra_sus_archivos_incluidas_las_miniaturas(): void
    {
        $procesador = new ProcesadorDeFotos;

        $trabajo = Trabajo::factory()->create([
            'foto_antes' => $procesador->procesar(FotosDeEjemplo::acostada(1))->base,
            'foto_despues' => $procesador->procesar(FotosDeEjemplo::chica())->base,
        ]);

        $this->assertCount(8, $this->disco->allFiles());

        $trabajo->delete();

        $this->assertSame([], $this->disco->allFiles());
    }

    public function test_borrar_un_trabajo_cuyos_archivos_ya_no_estan_no_falla(): void
    {
        $trabajo = Trabajo::factory()->create();

        $trabajo->delete();

        $this->assertDatabaseMissing('trabajos', ['id' => $trabajo->id]);
    }

    public function test_borrar_un_trabajo_no_toca_las_fotos_de_los_demas(): void
    {
        $procesador = new ProcesadorDeFotos;

        $suyo = Trabajo::factory()->create(['foto_antes' => $procesador->procesar(FotosDeEjemplo::chica())->base]);
        $ajeno = Trabajo::factory()->create(['foto_antes' => $procesador->procesar(FotosDeEjemplo::chica())->base]);

        $suyo->delete();

        foreach ($ajeno->antes()->archivos() as $ruta) {
            $this->disco->assertExists($ruta);
        }
    }

    public function test_la_rejilla_pide_la_miniatura_y_el_comparador_la_grande(): void
    {
        $trabajo = Trabajo::factory()->create([
            'foto_antes' => (new ProcesadorDeFotos)->procesar(FotosDeEjemplo::chica())->base,
        ]);

        $foto = $trabajo->antes();

        $rejilla = Blade::render(
            '<x-foto-trabajo :foto="$foto" alt="Tenis de gamuza antes de la limpieza" />',
            ['foto' => $foto],
        );

        $this->assertStringContainsString($foto->webp(Variante::Miniatura), $rejilla);
        $this->assertStringContainsString($foto->jpeg(Variante::Miniatura), $rejilla);
        $this->assertStringNotContainsString($foto->webp(Variante::Grande), $rejilla);

        $comparador = Blade::render(
            '<x-foto-trabajo :foto="$foto" variante="grande" alt="Tenis de gamuza antes de la limpieza" />',
            ['foto' => $foto],
        );

        $this->assertStringContainsString($foto->webp(Variante::Grande), $comparador);
        $this->assertStringContainsString($foto->jpeg(Variante::Grande), $comparador);
    }

    public function test_una_foto_sin_ruta_no_tiene_archivos_que_borrar(): void
    {
        $trabajo = Trabajo::factory()->create(['foto_antes' => '', 'foto_despues' => '']);

        $this->assertSame([], $trabajo->antes()->archivos());
        $this->assertFalse($trabajo->antes()->existe());

        $trabajo->delete();

        $this->assertDatabaseMissing('trabajos', ['id' => $trabajo->id]);
    }
}
