<?php

namespace Tests\Feature\Panel;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CrearDuenaTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_comando_crea_la_cuenta_de_la_duena(): void
    {
        $this->artisan('calzaclean:crear-duena')
            ->expectsQuestion('Nombre', 'Rosa')
            ->expectsQuestion('Correo', 'rosa@calzaclean.mx')
            ->expectsQuestion('Contraseña', 'taller-de-tenis')
            ->expectsQuestion('Repite la contraseña', 'taller-de-tenis')
            ->assertSuccessful();

        $duena = User::query()->sole();

        $this->assertSame('Rosa', $duena->name);
        $this->assertSame('rosa@calzaclean.mx', $duena->email);
        $this->assertTrue(Hash::check('taller-de-tenis', $duena->password));
        $this->assertTrue($duena->hasVerifiedEmail());
    }

    public function test_el_comando_no_crea_una_segunda_cuenta(): void
    {
        User::factory()->create();

        $this->artisan('calzaclean:crear-duena')
            ->expectsOutputToContain('Ya hay una cuenta creada.')
            ->assertFailed();

        $this->assertSame(1, User::query()->count());
    }

    public function test_el_comando_rechaza_una_contrasena_que_no_coincide(): void
    {
        $this->artisan('calzaclean:crear-duena')
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
        $this->artisan('calzaclean:crear-duena')
            ->expectsQuestion('Nombre', 'Rosa')
            ->expectsQuestion('Correo', 'rosa arroba calzaclean')
            ->expectsQuestion('Contraseña', 'taller-de-tenis')
            ->expectsQuestion('Repite la contraseña', 'taller-de-tenis')
            ->expectsOutputToContain('El correo no tiene forma de correo.')
            ->assertFailed();

        $this->assertSame(0, User::query()->count());
    }
}
