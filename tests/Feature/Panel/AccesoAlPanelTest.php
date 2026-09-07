<?php

namespace Tests\Feature\Panel;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccesoAlPanelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<int, array<int, string>>
     */
    public static function pantallasDelPanel(): array
    {
        return [
            ['panel.inicio'],
            ['panel.trabajos'],
            ['panel.precios'],
            ['panel.preguntas'],
            ['panel.ajustes.contacto'],
            ['panel.ajustes.negocio'],
            ['panel.ajustes.aviso'],
        ];
    }

    public function test_una_visita_anonima_al_panel_termina_en_el_login(): void
    {
        $this->get(route('panel.inicio'))->assertRedirect(route('login'));
    }

    #[DataProvider('pantallasDelPanel')]
    public function test_cada_pantalla_del_panel_pide_entrar(string $ruta): void
    {
        $this->get(route($ruta))->assertRedirect(route('login'));
    }

    #[DataProvider('pantallasDelPanel')]
    public function test_la_duena_alcanza_cada_pantalla_del_panel(string $ruta): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route($ruta))->assertOk();
    }

    public function test_no_hay_ninguna_ruta_para_crear_una_cuenta_desde_el_navegador(): void
    {
        $this->assertFalse(Route::has('register'));
        $this->assertFalse(Route::has('register.store'));
        $this->assertFalse(Route::has('password.request'));
        $this->assertFalse(Route::has('password.reset'));

        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
        $this->get('/forgot-password')->assertNotFound();
    }

    public function test_entrar_lleva_al_panel(): void
    {
        $duena = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $duena->email,
            'password' => 'password',
        ])->assertRedirect(route('panel.inicio', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_ajustes_abre_en_la_pantalla_de_contacto(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('panel.ajustes'))->assertRedirect(route('panel.ajustes.contacto', absolute: false));
    }

    public function test_el_inicio_del_panel_lista_las_cuatro_entradas(): void
    {
        $this->actingAs(User::factory()->create());

        $respuesta = $this->get(route('panel.inicio'));

        $respuesta->assertOk();
        $respuesta->assertSee('Trabajos');
        $respuesta->assertSee('Precios');
        $respuesta->assertSee('Preguntas y testimonios');
        $respuesta->assertSee('Ajustes');
    }

    public function test_las_tres_pantallas_de_ajustes_se_enlazan_entre_si(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (['contacto', 'negocio', 'aviso'] as $pantalla) {
            $respuesta = $this->get(route('panel.ajustes.'.$pantalla));

            $respuesta->assertOk();
            $respuesta->assertSee(route('panel.ajustes.contacto'), false);
            $respuesta->assertSee(route('panel.ajustes.negocio'), false);
            $respuesta->assertSee(route('panel.ajustes.aviso'), false);
        }
    }

    public function test_el_panel_lleva_la_navegacion_de_las_cuatro_entradas_en_cada_pantalla(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (self::pantallasDelPanel() as [$ruta]) {
            $respuesta = $this->get(route($ruta));

            foreach (['panel.trabajos', 'panel.precios', 'panel.preguntas', 'panel.ajustes'] as $entrada) {
                $respuesta->assertSee(route($entrada), false);
            }
        }
    }
}
