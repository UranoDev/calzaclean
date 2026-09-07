<?php

namespace Tests\Feature\Contenido;

use App\Models\Negocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NegocioTest extends TestCase
{
    use RefreshDatabase;

    public function test_actual_crea_el_renglon_cuando_todavia_no_existe(): void
    {
        $this->assertSame(0, Negocio::query()->count());

        $negocio = Negocio::actual();

        $this->assertModelExists($negocio);
        $this->assertSame(1, Negocio::query()->count());
    }

    public function test_actual_devuelve_siempre_el_mismo_renglon(): void
    {
        $primero = Negocio::actual();

        $this->assertSame($primero->id, Negocio::actual()->id);
        $this->assertSame(1, Negocio::query()->count());
    }

    public function test_un_aviso_encendido_sin_texto_no_se_muestra(): void
    {
        $negocio = Negocio::factory()->create(['aviso_texto' => null, 'aviso_activo' => true]);

        $this->assertFalse($negocio->aviso_visible);
    }

    public function test_un_aviso_con_texto_y_apagado_no_se_muestra(): void
    {
        $negocio = Negocio::factory()->create(['aviso_texto' => 'Cerramos el lunes.', 'aviso_activo' => false]);

        $this->assertFalse($negocio->aviso_visible);
    }

    public function test_un_aviso_con_texto_y_encendido_se_muestra(): void
    {
        $negocio = Negocio::factory()->conAviso('Cerramos el lunes.')->create();

        $this->assertTrue($negocio->aviso_visible);
    }

    public function test_una_red_sin_url_no_se_dibuja(): void
    {
        $negocio = Negocio::factory()->create([
            'instagram' => 'https://www.instagram.com/calza_clean_/',
            'facebook' => null,
            'x' => null,
            'tiktok' => null,
        ]);

        $this->assertSame(['instagram'], array_keys($negocio->redes));
        $this->assertSame('Instagram', $negocio->redes['instagram']['nombre']);
    }

    public function test_sin_ninguna_red_cargada_la_lista_queda_vacia(): void
    {
        $negocio = Negocio::factory()->sinRedes()->create();

        $this->assertSame([], $negocio->redes);
    }
}
