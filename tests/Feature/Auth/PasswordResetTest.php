<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Fortify\Features;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::resetPasswords());
    }

    public function test_desde_el_login_se_llega_a_recuperar_la_contrasena(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Olvidé mi contraseña')
            ->assertSee(route('password.request'));
    }

    public function test_la_pantalla_para_pedir_el_enlace_se_muestra(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Recupera tu acceso');
    }

    public function test_pedir_el_enlace_manda_el_correo_a_la_cuenta(): void
    {
        Notification::fake();

        $cuenta = User::factory()->create();

        $this->post(route('password.email'), ['email' => $cuenta->email])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($cuenta, ResetPassword::class);
    }

    public function test_un_correo_desconocido_no_manda_ningun_enlace(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'nadie@calzaclean.mx']);

        Notification::assertNothingSent();
    }

    public function test_la_pantalla_de_contrasena_nueva_se_muestra_con_el_enlace(): void
    {
        Notification::fake();

        $cuenta = User::factory()->create();

        $this->post(route('password.email'), ['email' => $cuenta->email]);

        Notification::assertSentTo($cuenta, ResetPassword::class, function (ResetPassword $aviso) use ($cuenta): bool {
            $this->get(route('password.reset', ['token' => $aviso->token, 'email' => $cuenta->email]))
                ->assertOk()
                ->assertSee('Pon una contraseña nueva')
                ->assertSee($cuenta->email);

            return true;
        });
    }

    public function test_la_contrasena_queda_cambiada_y_sirve_para_entrar(): void
    {
        Notification::fake();

        $cuenta = User::factory()->create();

        $this->post(route('password.email'), ['email' => $cuenta->email]);

        Notification::assertSentTo($cuenta, ResetPassword::class, function (ResetPassword $aviso) use ($cuenta): bool {
            $this->post(route('password.update'), [
                'token' => $aviso->token,
                'email' => $cuenta->email,
                'password' => 'taller-nuevo-2026',
                'password_confirmation' => 'taller-nuevo-2026',
            ])->assertSessionHasNoErrors();

            return true;
        });

        $this->assertTrue(Hash::check('taller-nuevo-2026', $cuenta->fresh()->password));

        $this->post(route('login.store'), [
            'email' => $cuenta->email,
            'password' => 'taller-nuevo-2026',
        ])->assertRedirect(route('panel.inicio', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_las_dos_cuentas_del_panel_pueden_cambiar_su_contrasena(): void
    {
        Notification::fake();

        $cuentas = User::factory()->count(2)->create();

        foreach ($cuentas as $indice => $cuenta) {
            $nueva = "contrasena-del-taller-{$indice}";

            $this->post(route('password.email'), ['email' => $cuenta->email]);

            Notification::assertSentTo($cuenta, ResetPassword::class, function (ResetPassword $aviso) use ($cuenta, $nueva): bool {
                $this->post(route('password.update'), [
                    'token' => $aviso->token,
                    'email' => $cuenta->email,
                    'password' => $nueva,
                    'password_confirmation' => $nueva,
                ])->assertSessionHasNoErrors();

                return true;
            });

            $this->assertTrue(Hash::check($nueva, $cuenta->fresh()->password));
        }
    }

    public function test_un_enlace_que_nadie_emitio_no_cambia_la_contrasena(): void
    {
        $cuenta = User::factory()->create();

        $this->post(route('password.update'), [
            'token' => 'un-token-que-nadie-emitio',
            'email' => $cuenta->email,
            'password' => 'taller-nuevo-2026',
            'password_confirmation' => 'taller-nuevo-2026',
        ])->assertSessionHasErrors('email');

        $this->assertFalse(Hash::check('taller-nuevo-2026', $cuenta->fresh()->password));
    }

    public function test_las_dos_contrasenas_tienen_que_coincidir(): void
    {
        Notification::fake();

        $cuenta = User::factory()->create();

        $this->post(route('password.email'), ['email' => $cuenta->email]);

        Notification::assertSentTo($cuenta, ResetPassword::class, function (ResetPassword $aviso) use ($cuenta): bool {
            $this->post(route('password.update'), [
                'token' => $aviso->token,
                'email' => $cuenta->email,
                'password' => 'taller-nuevo-2026',
                'password_confirmation' => 'otra-cosa-distinta',
            ])->assertSessionHasErrors('password');

            return true;
        });

        $this->assertFalse(Hash::check('taller-nuevo-2026', $cuenta->fresh()->password));
    }

    public function test_el_correo_de_restablecimiento_no_lleva_texto_en_ingles(): void
    {
        $cuenta = User::factory()->create(['name' => 'Eli']);

        $mensaje = (new ResetPassword('un-token-de-prueba'))->toMail($cuenta);

        $cuerpo = (string) $mensaje->render();

        $this->assertSame('Cambia la contraseña de tu cuenta del Panel', $mensaje->subject);
        $this->assertStringContainsString('Hola, Eli:', $cuerpo);
        $this->assertStringContainsString('Poner una contraseña nueva', $cuerpo);
        $this->assertStringContainsString('El enlace sirve por 60 minutos.', $cuerpo);
        $this->assertStringContainsString(
            route('password.reset', ['token' => 'un-token-de-prueba', 'email' => $cuenta->email]),
            $cuerpo,
        );

        foreach (['Reset Password', 'Regards', 'All rights reserved', 'If you did not request', 'having trouble clicking'] as $ingles) {
            $this->assertStringNotContainsString($ingles, $cuerpo);
        }
    }

    public function test_los_avisos_en_pantalla_estan_en_espanol(): void
    {
        Notification::fake();

        $cuenta = User::factory()->create();

        $this->post(route('password.email'), ['email' => $cuenta->email])
            ->assertSessionHas('status', 'Te mandamos el enlace al correo de tu cuenta.');

        $this->post(route('password.update'), [
            'token' => 'un-token-que-nadie-emitio',
            'email' => $cuenta->email,
            'password' => 'taller-nuevo-2026',
            'password_confirmation' => 'taller-nuevo-2026',
        ])->assertSessionHasErrors([
            'email' => 'Este enlace ya no sirve. Pide uno nuevo desde «Olvidé mi contraseña».',
        ]);
    }

    public function test_con_el_envio_por_log_el_correo_queda_escrito_en_el_log(): void
    {
        $archivo = storage_path('logs/prueba-de-correo-'.Str::random(8).'.log');

        config()->set('logging.channels.prueba-de-correo', [
            'driver' => 'single',
            'path' => $archivo,
            'level' => 'debug',
        ]);
        config()->set('mail.default', 'log');
        config()->set('mail.log_channel', 'prueba-de-correo');

        $cuenta = User::factory()->create();

        try {
            $this->post(route('password.email'), ['email' => $cuenta->email])
                ->assertRedirect()
                ->assertSessionHasNoErrors()
                ->assertSessionHas('status', 'Te mandamos el enlace al correo de tu cuenta.');

            $this->assertFileExists($archivo);

            $escrito = (string) file_get_contents($archivo);

            $this->assertStringContainsString($cuenta->email, $escrito);
            $this->assertStringContainsString('reset-password', $escrito);
            $this->assertStringContainsString('Poner una contraseña nueva', $escrito);
        } finally {
            @unlink($archivo);
        }
    }
}
