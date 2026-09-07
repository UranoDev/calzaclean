<?php

namespace Tests\Feature\Panel;

use App\Models\ColoniaRecoleccion;
use App\Models\Negocio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AjustesDelPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_las_tres_pantallas_de_ajustes_montan_su_componente(): void
    {
        Negocio::factory()->create();

        foreach (['contacto', 'negocio', 'aviso'] as $pantalla) {
            $respuesta = $this->get(route('panel.ajustes.'.$pantalla));

            $respuesta->assertOk();
            $respuesta->assertSeeLivewire('panel.ajustes.'.$pantalla);
            $respuesta->assertDontSee('Esta pantalla todavía no está construida');
        }
    }

    /**
     * @return list<array{string}>
     */
    public static function formasDelNumero(): array
    {
        return [
            'con espacios' => ['52 427 180 3585'],
            'con el signo de más' => ['+52 427 180 3585'],
            'con el 1 de México' => ['+52 1 427 180 3585'],
        ];
    }

    #[DataProvider('formasDelNumero')]
    public function test_el_whatsapp_se_guarda_normalizado(string $escrito): void
    {
        Livewire::test('panel.ajustes.contacto')
            ->set('whatsapp', $escrito)
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame('524271803585', Negocio::actual()->whatsapp);
    }

    public function test_la_pantalla_muestra_el_mismo_enlace_que_usa_el_sitio(): void
    {
        Livewire::test('panel.ajustes.contacto')
            ->set('whatsapp', '+52 1 427 180 3585')
            ->assertSee('https://wa.me/524271803585');
    }

    public function test_sin_numero_la_pantalla_avisa_que_no_hay_boton_en_el_sitio(): void
    {
        Negocio::factory()->sinWhatsapp()->create();

        Livewire::test('panel.ajustes.contacto')
            ->assertSee('Mientras el número esté vacío, el sitio no dibuja ningún botón de WhatsApp.');
    }

    public function test_el_whatsapp_es_obligatorio(): void
    {
        Livewire::test('panel.ajustes.contacto')
            ->set('whatsapp', '')
            ->call('guardar')
            ->assertHasErrors(['whatsapp' => 'required'])
            ->assertSee('Escribe el número de WhatsApp.');
    }

    public function test_una_red_que_no_es_una_direccion_no_se_guarda(): void
    {
        Livewire::test('panel.ajustes.contacto')
            ->set('whatsapp', '524271803585')
            ->set('redes.instagram', '@calza_clean_')
            ->call('guardar')
            ->assertHasErrors(['redes.instagram' => 'url'])
            ->assertSee('Pega la dirección completa del perfil, la que empieza con https://.');
    }

    public function test_cargar_la_red_de_x_la_hace_aparecer_en_el_pie(): void
    {
        Negocio::factory()->sinRedes()->create();

        $this->get(route('home'))->assertDontSee('https://x.com/calzaclean', false);

        Livewire::test('panel.ajustes.contacto')
            ->set('whatsapp', '524271803585')
            ->set('redes.x', 'https://x.com/calzaclean')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->get(route('home'))->assertSee('https://x.com/calzaclean', false);
    }

    public function test_una_red_vaciada_se_guarda_en_nulo_y_deja_de_dibujarse(): void
    {
        Negocio::factory()->create(['instagram' => 'https://www.instagram.com/calza_clean_/']);

        Livewire::test('panel.ajustes.contacto')
            ->set('whatsapp', '524271803585')
            ->set('redes.instagram', '')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertNull(Negocio::actual()->instagram);
        $this->get(route('home'))->assertDontSee('Instagram', false);
    }

    public function test_guardar_los_horarios_y_la_direccion(): void
    {
        Livewire::test('panel.ajustes.negocio')
            ->set('horarios', 'Lunes a viernes de 10:00 a 19:00')
            ->set('direccion', 'San Juan del Río, Qro.')
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('Los datos del negocio quedaron guardados.');

        $negocio = Negocio::actual();

        $this->assertSame('Lunes a viernes de 10:00 a 19:00', $negocio->horarios);
        $this->assertSame('San Juan del Río, Qro.', $negocio->direccion);
    }

    public function test_dar_de_alta_una_colonia_y_ordenarla(): void
    {
        Livewire::test('panel.ajustes.negocio')
            ->call('agregarColonia')
            ->set('colonias.0.nombre', 'Centro')
            ->call('agregarColonia')
            ->set('colonias.1.nombre', 'La Valla')
            ->call('mover', 'nueva-2', -1)
            ->call('guardar')
            ->assertHasNoErrors();

        $colonias = ColoniaRecoleccion::query()->ordenadas()->pluck('nombre')->all();

        $this->assertSame(['La Valla', 'Centro'], $colonias);
    }

    public function test_una_colonia_sin_nombre_no_se_guarda(): void
    {
        Livewire::test('panel.ajustes.negocio')
            ->call('agregarColonia')
            ->call('guardar')
            ->assertHasErrors(['colonias.0.nombre' => 'required'])
            ->assertSee('Escribe el nombre de la colonia.');

        $this->assertSame(0, ColoniaRecoleccion::query()->count());
    }

    public function test_sin_ninguna_colonia_activa_la_pantalla_dice_que_falta_para_que_aparezca(): void
    {
        ColoniaRecoleccion::factory()->inactiva()->create(['nombre' => 'Centro']);

        Livewire::test('panel.ajustes.negocio')
            ->assertSee('Mientras no haya ninguna colonia activa, el sitio no menciona la recolección a domicilio.');

        $this->get(route('home'))->assertDontSee('recolección', false);
    }

    public function test_con_una_colonia_activa_la_pantalla_la_lista(): void
    {
        ColoniaRecoleccion::factory()->create(['nombre' => 'Centro']);

        Livewire::test('panel.ajustes.negocio')
            ->assertSee('Centro')
            ->assertDontSee('Mientras no haya ninguna colonia activa');
    }

    public function test_borrar_una_colonia_la_saca_de_la_lista(): void
    {
        $colonia = ColoniaRecoleccion::factory()->create(['nombre' => 'Centro']);

        Livewire::test('panel.ajustes.negocio')
            ->call('confirmarBorrado', 'colonia-'.$colonia->id)
            ->assertSee('¿Borrar «Centro» de la lista?')
            ->call('borrar', 'colonia-'.$colonia->id);

        $this->assertSame(0, ColoniaRecoleccion::query()->count());
    }

    public function test_un_aviso_encendido_sin_texto_no_dibuja_la_franja(): void
    {
        Livewire::test('panel.ajustes.aviso')
            ->set('activo', true)
            ->set('texto', '')
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('El aviso no tiene texto, así que el sitio no muestra la franja.');

        $this->assertFalse(Negocio::actual()->aviso_visible);
        $this->get(route('home'))->assertOk();
    }

    public function test_un_aviso_con_texto_y_encendido_llega_al_sitio(): void
    {
        Livewire::test('panel.ajustes.aviso')
            ->set('texto', 'Cerramos del 24 al 26 de diciembre.')
            ->set('activo', true)
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('El aviso quedó guardado.');

        $this->get(route('home'))->assertSee('Cerramos del 24 al 26 de diciembre.', false);
    }

    public function test_el_sitio_se_ve_sin_ningun_aviso(): void
    {
        Negocio::factory()->create();

        $respuesta = $this->get(route('home'));

        $respuesta->assertOk();
        $respuesta->assertSee('Cada material, su técnica.', false);
        $respuesta->assertDontSee('role="status"', false);
    }

    public function test_apagar_el_aviso_lo_quita_del_sitio_sin_borrar_el_texto(): void
    {
        Negocio::factory()->conAviso('Cerramos del 24 al 26 de diciembre.')->create();

        Livewire::test('panel.ajustes.aviso')
            ->set('activo', false)
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('«Mostrar el aviso» está desmarcado: el sitio no muestra la franja.');

        $this->assertSame('Cerramos del 24 al 26 de diciembre.', Negocio::actual()->aviso_texto);
        $this->get(route('home'))->assertDontSee('Cerramos del 24 al 26 de diciembre.', false);
    }
}
