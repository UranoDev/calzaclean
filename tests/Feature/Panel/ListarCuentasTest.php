<?php

namespace Tests\Feature\Panel;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ListarCuentasTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_comando_lista_cada_cuenta_con_su_nombre_correo_y_alta(): void
    {
        User::factory()->create([
            'name' => 'Rosa',
            'email' => 'rosa@calzaclean.mx',
            'created_at' => '2026-09-01 10:00:00',
        ]);

        User::factory()->create([
            'name' => 'Soporte',
            'email' => 'soporte@calzaclean.mx',
            'created_at' => '2026-09-06 10:00:00',
        ]);

        $this->assertSame(0, Artisan::call('calzaclean:cuentas'));

        $salida = Artisan::output();

        $this->assertStringContainsString('Rosa', $salida);
        $this->assertStringContainsString('rosa@calzaclean.mx', $salida);
        $this->assertStringContainsString('01/09/2026', $salida);
        $this->assertStringContainsString('Soporte', $salida);
        $this->assertStringContainsString('soporte@calzaclean.mx', $salida);
        $this->assertStringContainsString('06/09/2026', $salida);
    }

    public function test_sin_ninguna_cuenta_el_comando_dice_como_crear_una(): void
    {
        $this->assertSame(0, Artisan::call('calzaclean:cuentas'));

        $salida = Artisan::output();

        $this->assertStringContainsString('Todavía no hay ninguna cuenta.', $salida);
        $this->assertStringContainsString('calzaclean:crear-cuenta', $salida);
    }
}
