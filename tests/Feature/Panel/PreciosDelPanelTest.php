<?php

namespace Tests\Feature\Panel;

use App\Models\Servicio;
use App\Models\Trabajo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PreciosDelPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_la_pantalla_de_precios_monta_el_componente(): void
    {
        Servicio::factory()->create(['nombre' => 'Limpieza básica', 'precio' => 120]);

        $respuesta = $this->get(route('panel.precios'));

        $respuesta->assertOk();
        $respuesta->assertSee('Limpieza básica');
        $respuesta->assertSeeLivewire('panel.precios');
        $respuesta->assertDontSee('Esta pantalla todavía no está construida');
    }

    public function test_dar_de_alta_un_servicio(): void
    {
        Livewire::test('panel.precios')
            ->call('agregarServicio')
            ->set('renglones.0.nombre', 'Limpieza infantil')
            ->set('renglones.0.aplica_a', 'Hasta talla 20 de niño')
            ->set('renglones.0.precio', '100')
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('Los precios quedaron guardados.');

        $servicio = Servicio::query()->sole();

        $this->assertSame('Limpieza infantil', $servicio->nombre);
        $this->assertSame('Hasta talla 20 de niño', $servicio->aplica_a);
        $this->assertSame(100, $servicio->precio);
        $this->assertFalse($servicio->es_extra);
        $this->assertTrue($servicio->activo);
    }

    public function test_dar_de_alta_un_extra_lo_deja_al_final_y_con_signo_de_mas(): void
    {
        Servicio::factory()->create(['nombre' => 'Limpieza básica', 'precio' => 120, 'orden' => 1]);

        Livewire::test('panel.precios')
            ->call('agregarExtra')
            ->set('renglones.1.nombre', 'Entrega express')
            ->set('renglones.1.precio', '100')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame(
            ['Limpieza básica', 'Entrega express'],
            Servicio::query()->ordenados()->pluck('nombre')->all(),
        );

        $extra = Servicio::query()->extras()->sole();

        $this->assertTrue($extra->es_extra);
        $this->assertSame('+$100', $extra->precio_formateado);
    }

    public function test_editar_el_precio_se_refleja_sin_limpiar_cache(): void
    {
        $servicio = Servicio::factory()->create(['nombre' => 'Limpieza especializada', 'precio' => 170]);

        Livewire::test('panel.precios')
            ->set('renglones.0.precio', '190')
            ->call('guardar')
            ->assertHasNoErrors();

        // Lo que lee el Sitio es la consulta al modelo, sin caché de por medio.
        $this->assertSame(190, Servicio::query()->activos()->findOrFail($servicio->id)->precio);
        $this->assertSame('$190', $servicio->fresh()->precio_formateado);
    }

    public function test_desactivar_un_servicio_lo_saca_de_la_lista_publicada(): void
    {
        Servicio::factory()->create(['nombre' => 'Bolsas', 'precio' => 185, 'orden' => 1]);
        Servicio::factory()->create(['nombre' => 'Mochilas', 'precio' => 200, 'orden' => 2]);

        Livewire::test('panel.precios')
            ->set('renglones.1.activo', false)
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertDontSee('$200');

        $this->assertFalse(Servicio::query()->where('nombre', 'Mochilas')->sole()->activo);
        $this->assertSame(['Bolsas'], Servicio::query()->activos()->ordenados()->pluck('nombre')->all());
    }

    public function test_un_extra_desactivado_no_altera_el_precio_de_ningun_servicio(): void
    {
        Servicio::factory()->create(['nombre' => 'Limpieza básica', 'precio' => 120, 'orden' => 1]);
        Servicio::factory()->extra()->create(['nombre' => 'Entrega express', 'precio' => 100, 'orden' => 2]);

        Livewire::test('panel.precios')
            ->set('renglones.1.activo', false)
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame(120, Servicio::query()->where('nombre', 'Limpieza básica')->sole()->precio);
        $this->assertSame(100, Servicio::query()->where('nombre', 'Entrega express')->sole()->precio);
        $this->assertSame(['Limpieza básica'], Servicio::query()->activos()->ordenados()->pluck('nombre')->all());
        $this->assertSame([], Servicio::query()->activos()->extras()->pluck('nombre')->all());
    }

    public function test_el_orden_de_la_tabla_es_el_orden_del_sitio(): void
    {
        Servicio::factory()->create(['nombre' => 'Limpieza básica', 'precio' => 120, 'orden' => 1]);
        Servicio::factory()->create(['nombre' => 'Limpieza especializada', 'precio' => 170, 'orden' => 2]);
        Servicio::factory()->create(['nombre' => 'Botas', 'precio' => 150, 'orden' => 3]);

        $componente = Livewire::test('panel.precios');

        $clave = 'servicio-'.Servicio::query()->where('nombre', 'Botas')->sole()->id;

        $componente
            ->call('mover', $clave, -1)
            ->call('mover', $clave, -1)
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame(
            ['Botas', 'Limpieza básica', 'Limpieza especializada'],
            Servicio::query()->activos()->catalogo()->ordenados()->pluck('nombre')->all(),
        );
    }

    public function test_los_extras_van_al_final_del_orden_aunque_esten_arriba_en_la_tabla(): void
    {
        Servicio::factory()->extra()->create(['nombre' => 'Blanqueamiento de suelas', 'precio' => 50, 'orden' => 1]);
        Servicio::factory()->create(['nombre' => 'Limpieza básica', 'precio' => 120, 'orden' => 2]);

        Livewire::test('panel.precios')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame(
            ['Limpieza básica', 'Blanqueamiento de suelas'],
            Servicio::query()->ordenados()->pluck('nombre')->all(),
        );
    }

    public function test_no_se_guarda_un_nombre_vacio_ni_un_precio_negativo(): void
    {
        Livewire::test('panel.precios')
            ->call('agregarServicio')
            ->set('renglones.0.nombre', '')
            ->set('renglones.0.precio', '-10')
            ->call('guardar')
            ->assertHasErrors([
                'renglones.0.nombre' => 'required',
                'renglones.0.precio' => 'min',
            ]);

        $this->assertSame(0, Servicio::query()->count());
    }

    public function test_el_precio_no_admite_centavos(): void
    {
        Livewire::test('panel.precios')
            ->call('agregarServicio')
            ->set('renglones.0.nombre', 'Botas')
            ->set('renglones.0.precio', '150.50')
            ->call('guardar')
            ->assertHasErrors(['renglones.0.precio' => 'integer']);

        $this->assertSame(0, Servicio::query()->count());
    }

    public function test_borrar_un_servicio_avisa_cuantos_trabajos_lo_mencionan(): void
    {
        $servicio = Servicio::factory()->create(['nombre' => 'Botas', 'precio' => 150]);
        $trabajo = Trabajo::factory()->create(['servicio_id' => $servicio->id]);

        $clave = 'servicio-'.$servicio->id;

        Livewire::test('panel.precios')
            ->call('confirmarBorrado', $clave)
            ->assertSet('trabajosQueLoMencionan', 1)
            ->assertSee('Lo menciona 1 trabajo')
            ->call('borrar', $clave);

        $this->assertSame(0, Servicio::query()->count());

        // Un Trabajo es contenido: sobrevive a que se borre su Servicio.
        $this->assertModelExists($trabajo->fresh());
        $this->assertNull($trabajo->fresh()->servicio_id);
    }

    public function test_la_vista_previa_escribe_los_extras_con_signo_de_mas(): void
    {
        Servicio::factory()->create(['nombre' => 'Limpieza básica', 'precio' => 120, 'orden' => 1]);
        Servicio::factory()->extra()->create(['nombre' => 'Entrega express', 'precio' => 100, 'orden' => 2]);

        Livewire::test('panel.precios')
            ->assertSeeInOrder(['Limpieza básica', '$120', 'Extras', 'Entrega express', '+$100']);
    }

    public function test_la_vista_previa_toma_el_precio_antes_de_guardarlo(): void
    {
        Servicio::factory()->create(['nombre' => 'Limpieza básica', 'precio' => 120]);

        Livewire::test('panel.precios')
            ->set('renglones.0.precio', '135')
            ->assertSee('$135');

        $this->assertSame(120, Servicio::query()->sole()->precio);
    }
}
