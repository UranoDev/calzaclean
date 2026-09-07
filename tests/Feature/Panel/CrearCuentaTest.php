<?php

namespace Tests\Feature\Panel;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CrearCuentaTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_comando_crea_una_cuenta(): void
    {
        $this->artisan('calzaclean:crear-cuenta')
            ->expectsQuestion('Nombre', 'Rosa')
            ->expectsQuestion('Correo', 'rosa@calzaclean.mx')
            ->expectsQuestion('Contraseña', 'taller-de-tenis')
            ->expectsQuestion('Repite la contraseña', 'taller-de-tenis')
            ->assertSuccessful();

        $cuenta = User::query()->sole();

        $this->assertSame('Rosa', $cuenta->name);
        $this->assertSame('rosa@calzaclean.mx', $cuenta->email);
        $this->assertTrue(Hash::check('taller-de-tenis', $cuenta->password));
        $this->assertTrue($cuenta->hasVerifiedEmail());
    }

    public function test_el_comando_crea_una_segunda_cuenta(): void
    {
        User::factory()->create(['email' => 'rosa@calzaclean.mx']);

        $this->artisan('calzaclean:crear-cuenta')
            ->expectsQuestion('Nombre', 'Quien mantiene el sitio')
            ->expectsQuestion('Correo', 'soporte@calzaclean.mx')
            ->expectsQuestion('Contraseña', 'taller-de-tenis')
            ->expectsQuestion('Repite la contraseña', 'taller-de-tenis')
            ->assertSuccessful();

        $this->assertSame(2, User::query()->count());
        $this->assertTrue(User::query()->where('email', 'soporte@calzaclean.mx')->exists());
    }

    public function test_un_correo_repetido_falla_y_el_error_dice_cual_es(): void
    {
        User::factory()->create(['email' => 'rosa@calzaclean.mx']);

        $this->artisan('calzaclean:crear-cuenta')
            ->expectsQuestion('Nombre', 'Rosa otra vez')
            ->expectsQuestion('Correo', 'rosa@calzaclean.mx')
            ->expectsQuestion('Contraseña', 'taller-de-tenis')
            ->expectsQuestion('Repite la contraseña', 'taller-de-tenis')
            ->expectsOutputToContain('Ya hay una cuenta con el correo rosa@calzaclean.mx.')
            ->assertFailed();

        $this->assertSame(1, User::query()->count());
    }

    public function test_con_las_tres_opciones_el_comando_corre_sin_preguntar_nada(): void
    {
        $this->artisan('calzaclean:crear-cuenta', [
            '--nombre' => 'Rosa',
            '--correo' => 'rosa@calzaclean.mx',
            '--contrasena' => 'taller-de-tenis',
        ])->assertSuccessful();

        $cuenta = User::query()->sole();

        $this->assertSame('Rosa', $cuenta->name);
        $this->assertSame('rosa@calzaclean.mx', $cuenta->email);
        $this->assertTrue(Hash::check('taller-de-tenis', $cuenta->password));
    }

    public function test_las_opciones_que_falten_se_siguen_preguntando(): void
    {
        $this->artisan('calzaclean:crear-cuenta', ['--nombre' => 'Rosa'])
            ->expectsQuestion('Correo', 'rosa@calzaclean.mx')
            ->expectsQuestion('Contraseña', 'taller-de-tenis')
            ->expectsQuestion('Repite la contraseña', 'taller-de-tenis')
            ->assertSuccessful();

        $this->assertSame('Rosa', User::query()->sole()->name);
    }

    public function test_una_opcion_vacia_se_rechaza_en_vez_de_preguntar(): void
    {
        $this->artisan('calzaclean:crear-cuenta', [
            '--nombre' => '',
            '--correo' => 'rosa@calzaclean.mx',
            '--contrasena' => 'taller-de-tenis',
        ])
            ->expectsOutputToContain('El nombre no puede quedar vacío.')
            ->assertFailed();

        $this->assertSame(0, User::query()->count());
    }

    public function test_el_comando_rechaza_una_contrasena_que_no_coincide(): void
    {
        $this->artisan('calzaclean:crear-cuenta')
            ->expectsQuestion('Nombre', 'Rosa')
            ->expectsQuestion('Correo', 'rosa@calzaclean.mx')
            ->expectsQuestion('Contraseña', 'taller-de-tenis')
            ->expectsQuestion('Repite la contraseña', 'taller-de-tenías')
            ->expectsOutputToContain('Las dos contraseñas no coinciden.')
            ->assertFailed();

        $this->assertSame(0, User::query()->count());
    }

    public function test_el_comando_rechaza_un_correo_sin_forma_de_correo(): void
    {
        $this->artisan('calzaclean:crear-cuenta')
            ->expectsQuestion('Nombre', 'Rosa')
            ->expectsQuestion('Correo', 'rosa arroba calzaclean')
            ->expectsQuestion('Contraseña', 'taller-de-tenis')
            ->expectsQuestion('Repite la contraseña', 'taller-de-tenis')
            ->expectsOutputToContain('El correo no tiene forma de correo.')
            ->assertFailed();

        $this->assertSame(0, User::query()->count());
    }

    public function test_las_dos_cuentas_entran_al_panel(): void
    {
        $duena = User::factory()->create(['email' => 'rosa@calzaclean.mx']);
        $soporte = User::factory()->create(['email' => 'soporte@calzaclean.mx']);

        foreach ([$duena, $soporte] as $cuenta) {
            $this->post(route('login.store'), [
                'email' => $cuenta->email,
                'password' => 'password',
            ])->assertRedirect(route('panel.inicio', absolute: false));

            $this->assertAuthenticatedAs($cuenta);

            $this->get(route('panel.inicio'))->assertOk();

            $this->post(route('logout'));
        }
    }
}
