<?php

namespace Tests\Feature\Panel;

use App\Mail\CorreoDePrueba;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class ProbarCorreoTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_comando_manda_el_mensaje_de_prueba_al_destino(): void
    {
        Mail::fake();

        $this->assertSame(0, Artisan::call('calzaclean:probar-correo', ['destino' => 'eli@calzaclean.com']));

        Mail::assertSent(CorreoDePrueba::class, fn (CorreoDePrueba $correo): bool => $correo->hasTo('eli@calzaclean.com'));
    }

    public function test_el_comando_rechaza_un_destino_que_no_es_correo(): void
    {
        Mail::fake();

        $this->assertSame(1, Artisan::call('calzaclean:probar-correo', ['destino' => 'no-es-un-correo']));

        $this->assertStringContainsString('no tiene forma de correo', Artisan::output());

        Mail::assertNothingSent();
    }

    public function test_el_comando_reporta_el_error_real_del_servidor(): void
    {
        Mail::shouldReceive('to')->once()->andThrow(
            new TransportException('Connection could not be established with host "mail.calzaclean.com".'),
        );

        $this->assertSame(1, Artisan::call('calzaclean:probar-correo', ['destino' => 'eli@calzaclean.com']));

        $salida = Artisan::output();

        $this->assertStringContainsString('El correo no salió.', $salida);
        $this->assertStringContainsString('Connection could not be established with host', $salida);
    }

    public function test_con_el_envio_por_log_el_comando_avisa_que_el_mensaje_no_sale(): void
    {
        Mail::fake();

        config()->set('mail.default', 'log');

        $this->assertSame(0, Artisan::call('calzaclean:probar-correo', ['destino' => 'eli@calzaclean.com']));

        $salida = Artisan::output();

        $this->assertStringContainsString('no sale a internet', $salida);
        $this->assertStringContainsString('.env', $salida);
    }

    public function test_el_mensaje_de_prueba_no_lleva_texto_en_ingles(): void
    {
        $cuerpo = (new CorreoDePrueba)->render();

        $this->assertStringContainsString('Prueba de envío de CalzaClean', $cuerpo);
        $this->assertStringContainsString('calzaclean:probar-correo', $cuerpo);

        foreach (['Regards', 'All rights reserved', 'Whoops'] as $ingles) {
            $this->assertStringNotContainsString($ingles, $cuerpo);
        }
    }
}
