<?php

namespace Tests\Feature\Contenido;

use App\Models\Negocio;
use App\Models\Servicio;
use Database\Seeders\ContenidoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContenidoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_deja_ocho_servicios_de_los_cuales_dos_son_extra(): void
    {
        $this->seed(ContenidoSeeder::class);

        $this->assertSame(8, Servicio::query()->count());
        $this->assertSame(6, Servicio::query()->catalogo()->count());
        $this->assertSame(2, Servicio::query()->extras()->count());
    }

    public function test_carga_la_lista_de_precios_del_taller(): void
    {
        $this->seed(ContenidoSeeder::class);

        $precios = Servicio::query()->ordenados()->pluck('precio', 'nombre')->all();

        $this->assertSame([
            'Limpieza básica' => 120,
            'Limpieza especializada' => 170,
            'Limpieza infantil' => 100,
            'Botas' => 150,
            'Bolsas' => 185,
            'Mochilas' => 200,
            'Blanqueamiento de suelas' => 50,
            'Entrega express' => 100,
        ], $precios);
    }

    public function test_los_dos_extras_van_al_final_y_se_escriben_con_signo_de_mas(): void
    {
        $this->seed(ContenidoSeeder::class);

        $extras = Servicio::query()->extras()->ordenados()->get();

        $this->assertSame(['Blanqueamiento de suelas', 'Entrega express'], $extras->pluck('nombre')->all());
        $this->assertSame(['+$50', '+$100'], $extras->map->precio_formateado->all());
        $this->assertSame(
            [7, 8],
            $extras->pluck('orden')->all(),
        );
    }

    public function test_correrlo_dos_veces_no_duplica_renglones(): void
    {
        $this->seed(ContenidoSeeder::class);
        $this->seed(ContenidoSeeder::class);

        $this->assertSame(8, Servicio::query()->count());
        $this->assertSame(1, Negocio::query()->count());
    }

    public function test_siembra_el_negocio_con_los_datos_del_taller(): void
    {
        $this->seed(ContenidoSeeder::class);

        $negocio = Negocio::actual();

        $this->assertSame('+52 427 180 3585', $negocio->whatsapp);
        $this->assertSame('San Juan del Río, Qro.', $negocio->direccion);
        $this->assertSame('https://www.instagram.com/calza_clean_/', $negocio->instagram);
    }

    public function test_no_pisa_lo_que_la_duena_ya_edito_en_el_negocio(): void
    {
        Negocio::actual()->update(['direccion' => 'Av. Juárez 12, San Juan del Río']);

        $this->seed(ContenidoSeeder::class);

        $this->assertSame('Av. Juárez 12, San Juan del Río', Negocio::actual()->direccion);
    }
}
