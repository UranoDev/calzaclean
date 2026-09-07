<?php

namespace Tests\Feature\Panel;

use App\Enums\Material;
use App\Fotos\Foto;
use App\Fotos\ProcesadorDeFotos;
use App\Models\Servicio;
use App\Models\Trabajo;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\FotosDeEjemplo;
use Tests\TestCase;

class TrabajosDelPanelTest extends TestCase
{
    use RefreshDatabase;

    private Filesystem $disco;

    protected function setUp(): void
    {
        parent::setUp();

        $this->disco = Storage::fake('public');
        Storage::fake('local');

        $this->actingAs(User::factory()->create());
    }

    public function test_la_pantalla_de_trabajos_monta_el_componente(): void
    {
        $trabajo = Trabajo::factory()->create(['titulo' => 'Air Force 1 blancos']);

        $respuesta = $this->get(route('panel.trabajos'));

        $respuesta->assertOk();
        $respuesta->assertSee('Air Force 1 blancos');
        $respuesta->assertSeeLivewire('panel.trabajos');
        $respuesta->assertDontSee('Esta pantalla todavía no está construida');

        $this->assertSame('Air Force 1 blancos', $trabajo->titulo_en_pantalla);
    }

    public function test_dar_de_alta_un_trabajo_con_sus_dos_fotos(): void
    {
        $servicio = Servicio::factory()->create(['nombre' => 'Limpieza especializada', 'es_extra' => false, 'activo' => true]);

        Livewire::test('panel.trabajos')
            ->call('abrirAlta')
            ->set('fotoAntes', UploadedFile::fake()->image('antes.jpg', 1200, 900))
            ->set('fotoDespues', UploadedFile::fake()->image('despues.jpg', 1200, 900))
            ->set('material', Material::Gamuza->value)
            ->set('servicio', (string) $servicio->id)
            ->set('titulo', 'Air Force 1 blancos')
            ->set('publicado', true)
            ->call('guardar')
            ->assertHasNoErrors();

        $trabajo = Trabajo::query()->sole();

        $this->assertSame('Air Force 1 blancos', $trabajo->titulo);
        $this->assertSame(Material::Gamuza, $trabajo->material);
        $this->assertSame($servicio->id, $trabajo->servicio_id);
        $this->assertTrue($trabajo->publicado);

        // Cuatro archivos por foto: la grande y la miniatura, en WebP y en JPEG.
        $this->assertCount(8, $this->disco->allFiles());

        foreach ([...$trabajo->antes()->archivos(), ...$trabajo->despues()->archivos()] as $ruta) {
            $this->disco->assertExists($ruta);
        }
    }

    public function test_un_trabajo_sin_titulo_se_presenta_por_su_material(): void
    {
        Livewire::test('panel.trabajos')
            ->call('abrirAlta')
            ->set('fotoAntes', UploadedFile::fake()->image('antes.jpg'))
            ->set('fotoDespues', UploadedFile::fake()->image('despues.jpg'))
            ->set('material', Material::Sintetico->value)
            ->call('guardar')
            ->assertHasNoErrors();

        $trabajo = Trabajo::query()->sole();

        $this->assertNull($trabajo->titulo);
        $this->assertSame('Sintético', $trabajo->titulo_en_pantalla);
    }

    public function test_un_trabajo_sin_foto_de_despues_no_se_puede_publicar_y_el_error_dice_cual_falta(): void
    {
        Livewire::test('panel.trabajos')
            ->call('abrirAlta')
            ->set('fotoAntes', UploadedFile::fake()->image('antes.jpg'))
            ->set('material', Material::Lona->value)
            ->set('publicado', true)
            ->call('guardar')
            ->assertHasErrors('fotoDespues')
            ->assertHasNoErrors('fotoAntes')
            ->assertSee('Falta la foto de después.');

        $this->assertDatabaseCount('trabajos', 0);
        $this->assertSame([], $this->disco->allFiles());
    }

    public function test_un_trabajo_sin_ninguna_de_las_dos_fotos_nombra_las_dos(): void
    {
        Livewire::test('panel.trabajos')
            ->call('abrirAlta')
            ->set('material', Material::Piel->value)
            ->set('publicado', true)
            ->call('guardar')
            ->assertHasErrors(['fotoAntes', 'fotoDespues'])
            ->assertSee('Falta la foto de antes.')
            ->assertSee('Falta la foto de después.');

        $this->assertDatabaseCount('trabajos', 0);
    }

    public function test_un_trabajo_sin_publicar_se_guarda_aunque_le_falte_una_foto(): void
    {
        Livewire::test('panel.trabajos')
            ->call('abrirAlta')
            ->set('fotoAntes', UploadedFile::fake()->image('antes.jpg'))
            ->set('material', Material::Cuero->value)
            ->call('guardar')
            ->assertHasNoErrors();

        $trabajo = Trabajo::query()->sole();

        $this->assertFalse($trabajo->publicado);
        $this->assertSame('', $trabajo->foto_despues);
    }

    public function test_publicar_desde_la_lista_pide_las_dos_fotos(): void
    {
        $trabajo = Trabajo::factory()->borrador()->create([
            'titulo' => 'Botas de gamuza',
            'foto_despues' => '',
        ]);

        Livewire::test('panel.trabajos')
            ->call('alternarPublicado', $trabajo->id)
            ->assertHasErrors('lista')
            ->assertSee('Para publicar «Botas de gamuza» falta la foto de después.');

        $this->assertFalse($trabajo->fresh()->publicado);
    }

    public function test_un_archivo_que_no_es_foto_se_rechaza(): void
    {
        Livewire::test('panel.trabajos')
            ->call('abrirAlta')
            ->set('fotoAntes', UploadedFile::fake()->create('lista.txt', 4, 'text/plain'))
            ->set('material', Material::Lona->value)
            ->call('guardar')
            ->assertHasErrors('fotoAntes');

        $this->assertDatabaseCount('trabajos', 0);
        $this->assertSame([], $this->disco->allFiles());
    }

    public function test_editar_reemplaza_la_foto_y_borra_la_que_estaba(): void
    {
        $procesador = new ProcesadorDeFotos;

        $trabajo = Trabajo::factory()->create([
            'foto_antes' => $procesador->procesar(FotosDeEjemplo::chica())->base,
            'foto_despues' => $procesador->procesar(FotosDeEjemplo::chica())->base,
        ]);

        $rutaVieja = $trabajo->foto_antes;

        Livewire::test('panel.trabajos')
            ->call('editar', $trabajo->id)
            ->set('fotoAntes', UploadedFile::fake()->image('otra.jpg'))
            ->call('guardar')
            ->assertHasNoErrors();

        $trabajo->refresh();

        $this->assertNotSame($rutaVieja, $trabajo->foto_antes);

        foreach ((new Foto($rutaVieja))->archivos() as $ruta) {
            $this->disco->assertMissing($ruta);
        }

        foreach ($trabajo->antes()->archivos() as $ruta) {
            $this->disco->assertExists($ruta);
        }
    }

    public function test_borrar_pide_confirmacion_y_se_lleva_los_archivos(): void
    {
        $procesador = new ProcesadorDeFotos;

        $trabajo = Trabajo::factory()->create([
            'foto_antes' => $procesador->procesar(FotosDeEjemplo::chica())->base,
            'foto_despues' => $procesador->procesar(FotosDeEjemplo::chica())->base,
        ]);

        $componente = Livewire::test('panel.trabajos')
            ->call('confirmarBorrado', $trabajo->id)
            ->assertSet('porBorrar', $trabajo->id)
            ->assertSee('¿Borrar este trabajo? También se borran sus fotos.');

        $this->assertDatabaseCount('trabajos', 1);

        $componente->call('cancelarBorrado')->assertSet('porBorrar', null);

        $this->assertDatabaseCount('trabajos', 1);

        $componente->call('borrar', $trabajo->id);

        $this->assertDatabaseMissing('trabajos', ['id' => $trabajo->id]);
        $this->assertSame([], $this->disco->allFiles());
    }

    public function test_un_trabajo_despublicado_no_aparece_en_el_sitio(): void
    {
        $publicado = Trabajo::factory()->create(['titulo' => 'Air Force 1 blancos']);

        $this->assertSame(['Air Force 1 blancos'], Trabajo::query()->publicados()->pluck('titulo')->all());

        Livewire::test('panel.trabajos')
            ->call('alternarPublicado', $publicado->id)
            ->assertHasNoErrors();

        $this->assertFalse($publicado->fresh()->publicado);
        $this->assertSame([], Trabajo::query()->publicados()->pluck('titulo')->all());
    }

    public function test_los_botones_de_subir_y_bajar_reordenan_la_lista(): void
    {
        $primero = Trabajo::factory()->create(['titulo' => 'Primero', 'orden' => 1]);
        $segundo = Trabajo::factory()->create(['titulo' => 'Segundo', 'orden' => 2]);
        $tercero = Trabajo::factory()->create(['titulo' => 'Tercero', 'orden' => 3]);

        $componente = Livewire::test('panel.trabajos')->call('subir', $tercero->id);

        $this->assertSame(['Primero', 'Tercero', 'Segundo'], $this->ordenActual());

        $componente->call('bajar', $primero->id);

        $this->assertSame(['Tercero', 'Primero', 'Segundo'], $this->ordenActual());

        // El primero de la lista no sube más allá del primer lugar.
        $componente->call('subir', $tercero->id);

        $this->assertSame(['Tercero', 'Primero', 'Segundo'], $this->ordenActual());

        $this->assertSame($segundo->id, Trabajo::query()->ordenados()->get()->last()->id);
    }

    public function test_un_trabajo_nuevo_entra_al_final_de_la_lista(): void
    {
        Trabajo::factory()->create(['titulo' => 'El que ya estaba', 'orden' => 1]);

        Livewire::test('panel.trabajos')
            ->call('abrirAlta')
            ->set('fotoAntes', UploadedFile::fake()->image('antes.jpg'))
            ->set('fotoDespues', UploadedFile::fake()->image('despues.jpg'))
            ->set('material', Material::Ante->value)
            ->set('titulo', 'El nuevo')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame(['El que ya estaba', 'El nuevo'], $this->ordenActual());
    }

    public function test_el_formulario_no_ofrece_extras_como_servicio_aplicado(): void
    {
        Servicio::factory()->create(['nombre' => 'Limpieza básica', 'es_extra' => false, 'activo' => true]);
        Servicio::factory()->create(['nombre' => 'Entrega express', 'es_extra' => true, 'activo' => true]);
        Servicio::factory()->create(['nombre' => 'Servicio dado de baja', 'es_extra' => false, 'activo' => false]);

        Livewire::test('panel.trabajos')
            ->call('abrirAlta')
            ->assertSee('Limpieza básica')
            ->assertDontSee('Entrega express')
            ->assertDontSee('Servicio dado de baja');
    }

    /**
     * @return list<string>
     */
    private function ordenActual(): array
    {
        return Trabajo::query()->ordenados()->pluck('titulo')->all();
    }
}
