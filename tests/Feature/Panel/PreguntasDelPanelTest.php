<?php

namespace Tests\Feature\Panel;

use App\Models\Pregunta;
use App\Models\Testimonio;
use App\Models\Trabajo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PreguntasDelPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_la_pantalla_monta_el_componente(): void
    {
        Pregunta::factory()->create(['pregunta' => '¿Cuánto tardan?']);

        $respuesta = $this->get(route('panel.preguntas'));

        $respuesta->assertOk();
        $respuesta->assertSee('¿Cuánto tardan?');
        $respuesta->assertSeeLivewire('panel.preguntas');
        $respuesta->assertDontSee('Esta pantalla todavía no está construida');
    }

    public function test_dar_de_alta_una_pregunta_y_publicarla(): void
    {
        Livewire::test('panel.preguntas')
            ->call('agregarPregunta')
            ->set('preguntas.0.pregunta', '¿Recogen a domicilio?')
            ->set('preguntas.0.respuesta', "Sí, en las colonias de la lista.\nEscríbenos por WhatsApp.")
            ->call('guardarPreguntas')
            ->assertHasNoErrors()
            ->assertSee('Las preguntas quedaron guardadas.');

        $pregunta = Pregunta::query()->sole();

        $this->assertSame('¿Recogen a domicilio?', $pregunta->pregunta);
        $this->assertSame("Sí, en las colonias de la lista.\nEscríbenos por WhatsApp.", $pregunta->respuesta);
        $this->assertTrue($pregunta->publicada);
        $this->assertSame(1, $pregunta->orden);
        $this->assertSame(['¿Recogen a domicilio?'], Pregunta::query()->publicadas()->pluck('pregunta')->all());
    }

    public function test_dar_de_alta_un_testimonio_y_publicarlo(): void
    {
        Livewire::test('panel.preguntas')
            ->call('abrir', 'testimonios')
            ->call('agregarTestimonio')
            ->set('testimonios.0.nombre', 'Marisol')
            ->set('testimonios.0.texto', 'Los dejaron como nuevos.')
            ->call('guardarTestimonios')
            ->assertHasNoErrors()
            ->assertSee('Los testimonios quedaron guardados.');

        $testimonio = Testimonio::query()->sole();

        $this->assertSame('Marisol', $testimonio->nombre);
        $this->assertSame('Los dejaron como nuevos.', $testimonio->texto);
        $this->assertNull($testimonio->trabajo_id);
        $this->assertTrue($testimonio->publicado);
        $this->assertSame(1, $testimonio->orden);
    }

    public function test_una_pregunta_sin_publicar_no_entra_en_la_vista_previa(): void
    {
        Pregunta::factory()->create(['pregunta' => '¿Cuánto tardan?', 'orden' => 1]);
        Pregunta::factory()->create(['pregunta' => '¿Lavan gamuza?', 'orden' => 2]);

        Livewire::test('panel.preguntas')
            ->set('preguntas.1.publicada', false)
            ->call('guardarPreguntas')
            ->assertHasNoErrors();

        $this->assertSame(
            ['¿Cuánto tardan?'],
            Pregunta::query()->publicadas()->ordenadas()->pluck('pregunta')->all(),
        );
    }

    public function test_el_html_que_se_escriba_se_muestra_escapado(): void
    {
        Livewire::test('panel.preguntas')
            ->call('agregarPregunta')
            ->set('preguntas.0.pregunta', '<b>¿Lavan a mano?</b>')
            ->set('preguntas.0.respuesta', '<script>alert(1)</script>')
            ->call('guardarPreguntas')
            ->assertHasNoErrors()
            ->assertSee('<b>¿Lavan a mano?</b>')
            ->assertDontSee('<b>¿Lavan a mano?</b>', false)
            ->assertDontSee('<script>alert(1)</script>', false);

        $this->assertSame('<b>¿Lavan a mano?</b>', Pregunta::query()->sole()->pregunta);
    }

    public function test_el_html_de_un_testimonio_tambien_se_muestra_escapado(): void
    {
        Livewire::test('panel.preguntas')
            ->call('abrir', 'testimonios')
            ->call('agregarTestimonio')
            ->set('testimonios.0.nombre', '<i>Marisol</i>')
            ->set('testimonios.0.texto', '<script>alert(1)</script>')
            ->call('guardarTestimonios')
            ->assertHasNoErrors()
            ->assertSee('<i>Marisol</i>')
            ->assertDontSee('<i>Marisol</i>', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_un_testimonio_ligado_a_un_trabajo_publicado_lleva_su_foto(): void
    {
        $trabajo = Trabajo::factory()->create(['titulo' => 'Air Force 1 blancos']);

        Livewire::test('panel.preguntas')
            ->call('abrir', 'testimonios')
            ->call('agregarTestimonio')
            ->set('testimonios.0.nombre', 'Marisol')
            ->set('testimonios.0.texto', 'Los dejaron como nuevos.')
            ->set('testimonios.0.trabajo_id', (string) $trabajo->id)
            ->call('guardarTestimonios')
            ->assertHasNoErrors()
            ->assertSee('Después de la limpieza de Air Force 1 blancos');

        $this->assertSame($trabajo->id, Testimonio::query()->sole()->trabajo_id);
    }

    public function test_un_testimonio_de_un_trabajo_despublicado_se_sigue_mostrando_sin_foto(): void
    {
        $trabajo = Trabajo::factory()->borrador()->create(['titulo' => 'Botas de gamuza']);

        $testimonio = Testimonio::factory()->create([
            'nombre' => 'Marisol',
            'texto' => 'Los dejaron como nuevos.',
            'trabajo_id' => $trabajo->id,
        ]);

        Livewire::test('panel.preguntas')
            ->call('abrir', 'testimonios')
            ->assertSee('Los dejaron como nuevos.')
            ->assertDontSee('Después de la limpieza de Botas de gamuza');

        $this->assertNull($testimonio->trabajoVisible());
    }

    public function test_borrar_el_trabajo_deja_el_testimonio_en_pie_sin_foto(): void
    {
        $trabajo = Trabajo::factory()->create(['titulo' => 'Air Force 1 blancos']);

        $testimonio = Testimonio::factory()->create([
            'nombre' => 'Marisol',
            'texto' => 'Los dejaron como nuevos.',
            'trabajo_id' => $trabajo->id,
        ]);

        $trabajo->delete();

        Livewire::test('panel.preguntas')
            ->call('abrir', 'testimonios')
            ->assertSee('Los dejaron como nuevos.')
            ->assertDontSee('Después de la limpieza de Air Force 1 blancos');

        $this->assertModelExists($testimonio->fresh());
        $this->assertNull($testimonio->fresh()->trabajo_id);
        $this->assertNull($testimonio->fresh()->trabajoVisible());
    }

    public function test_reordenar_cambia_el_orden_con_el_que_se_publican(): void
    {
        Pregunta::factory()->create(['pregunta' => '¿Cuánto tardan?', 'orden' => 1]);
        Pregunta::factory()->create(['pregunta' => '¿Lavan gamuza?', 'orden' => 2]);

        Livewire::test('panel.preguntas')
            ->call('mover', 'preguntas', 'pregunta-2', -1)
            ->call('guardarPreguntas')
            ->assertHasNoErrors();

        $this->assertSame(
            ['¿Lavan gamuza?', '¿Cuánto tardan?'],
            Pregunta::query()->ordenadas()->pluck('pregunta')->all(),
        );
    }

    public function test_borrar_una_pregunta_pide_confirmacion(): void
    {
        Pregunta::factory()->create(['pregunta' => '¿Cuánto tardan?']);

        Livewire::test('panel.preguntas')
            ->call('confirmarBorrado', 'preguntas', 'pregunta-1')
            ->assertSee('¿Borrar «¿Cuánto tardan?» de la lista?')
            ->assertSee('Para quitarla del sitio sin borrarla, desmarca «Publicada».')
            ->call('cancelarBorrado')
            ->assertDontSee('¿Borrar «¿Cuánto tardan?» de la lista?');

        $this->assertSame(1, Pregunta::query()->count());

        Livewire::test('panel.preguntas')
            ->call('confirmarBorrado', 'preguntas', 'pregunta-1')
            ->call('borrar', 'preguntas', 'pregunta-1');

        $this->assertSame(0, Pregunta::query()->count());
    }

    public function test_borrar_un_testimonio_pide_confirmacion(): void
    {
        Testimonio::factory()->create(['nombre' => 'Marisol']);

        Livewire::test('panel.preguntas')
            ->call('abrir', 'testimonios')
            ->call('confirmarBorrado', 'testimonios', 'testimonio-1')
            ->assertSee('¿Borrar el testimonio de «Marisol» de la lista?')
            ->call('borrar', 'testimonios', 'testimonio-1');

        $this->assertSame(0, Testimonio::query()->count());
    }

    public function test_los_campos_largos_tienen_limite(): void
    {
        Livewire::test('panel.preguntas')
            ->call('agregarPregunta')
            ->set('preguntas.0.pregunta', str_repeat('a', 121))
            ->set('preguntas.0.respuesta', str_repeat('b', 601))
            ->call('guardarPreguntas')
            ->assertHasErrors(['preguntas.0.pregunta', 'preguntas.0.respuesta'])
            ->assertSee('La pregunta no puede pasar de 120 caracteres.')
            ->assertSee('La respuesta no puede pasar de 600 caracteres.');

        $this->assertSame(0, Pregunta::query()->count());

        Livewire::test('panel.preguntas')
            ->call('abrir', 'testimonios')
            ->call('agregarTestimonio')
            ->set('testimonios.0.nombre', str_repeat('a', 61))
            ->set('testimonios.0.texto', str_repeat('b', 401))
            ->call('guardarTestimonios')
            ->assertHasErrors(['testimonios.0.nombre', 'testimonios.0.texto']);

        $this->assertSame(0, Testimonio::query()->count());
    }

    public function test_una_pregunta_sin_texto_no_se_guarda(): void
    {
        Livewire::test('panel.preguntas')
            ->call('agregarPregunta')
            ->call('guardarPreguntas')
            ->assertHasErrors(['preguntas.0.pregunta', 'preguntas.0.respuesta'])
            ->assertSee('Escribe la pregunta.')
            ->assertSee('Escribe la respuesta.');

        $this->assertSame(0, Pregunta::query()->count());
    }

    public function test_la_pantalla_abre_en_preguntas_y_cambia_a_testimonios(): void
    {
        Pregunta::factory()->create(['pregunta' => '¿Cuánto tardan?']);
        Testimonio::factory()->create(['texto' => 'Los dejaron como nuevos.']);

        Livewire::test('panel.preguntas')
            ->assertSee('¿Cuánto tardan?')
            ->assertDontSee('Los dejaron como nuevos.')
            ->call('abrir', 'testimonios')
            ->assertSee('Los dejaron como nuevos.')
            ->assertDontSee('¿Cuánto tardan?');
    }
}
