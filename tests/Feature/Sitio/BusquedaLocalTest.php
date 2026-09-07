<?php

namespace Tests\Feature\Sitio;

use App\Models\Negocio;
use App\Models\Servicio;
use App\Models\ZonaRecoleccion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusquedaLocalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://calzaclean.com']);
    }

    public function test_los_datos_estructurados_describen_al_negocio_cargado(): void
    {
        Negocio::factory()->create([
            'direccion' => 'Av. Juárez 12, San Juan del Río, Qro.',
            'horarios' => "Lunes a viernes de 10:00 a 19:00\nSábados de 10:00 a 14:00",
            'whatsapp' => '4271803585',
            'instagram' => 'https://www.instagram.com/calza_clean_/',
        ]);

        Servicio::factory()->create(['nombre' => 'Limpieza infantil', 'precio' => 100]);
        Servicio::factory()->create(['nombre' => 'Mochilas', 'precio' => 200]);
        ZonaRecoleccion::factory()->create(['nombre' => 'Centro', 'costo' => 0]);

        $ficha = $this->fichaDelNegocio($this->get(route('home'))->getContent());

        $this->assertSame('LocalBusiness', $ficha['@type']);
        $this->assertSame('CalzaClean', $ficha['name']);
        $this->assertSame('https://calzaclean.com/', $ficha['url']);
        $this->assertSame('https://calzaclean.com/img/vista-previa.png', $ficha['image']);
        $this->assertSame('Av. Juárez 12, San Juan del Río, Qro.', $ficha['address']['streetAddress']);
        $this->assertSame('MX', $ficha['address']['addressCountry']);
        $this->assertSame('+524271803585', $ficha['telephone']);
        $this->assertSame(['Mo-Fr 10:00-19:00', 'Sa 10:00-14:00'], $ficha['openingHours']);
        $this->assertSame('$100 - $200', $ficha['priceRange']);
        $this->assertSame([['@type' => 'Place', 'name' => 'Centro']], $ficha['areaServed']);
        $this->assertSame(['https://www.instagram.com/calza_clean_/'], $ficha['sameAs']);
    }

    public function test_el_campo_sin_dato_cargado_no_se_emite(): void
    {
        $ficha = $this->fichaDelNegocio($this->get(route('home'))->getContent());

        foreach (['address', 'telephone', 'openingHours', 'priceRange', 'areaServed', 'sameAs'] as $campo) {
            $this->assertArrayNotHasKey($campo, $ficha, "La ficha emite [{$campo}] sin ningún dato cargado.");
        }
    }

    public function test_sin_zonas_activas_no_se_declara_area_de_recoleccion(): void
    {
        ZonaRecoleccion::factory()->inactiva()->create(['nombre' => 'Centro']);

        $this->assertArrayNotHasKey('areaServed', $this->fichaDelNegocio($this->get(route('home'))->getContent()));
    }

    public function test_el_rango_de_precios_no_contradice_la_lista_publicada(): void
    {
        Servicio::factory()->create(['nombre' => 'Limpieza básica', 'precio' => 120]);
        Servicio::factory()->create(['nombre' => 'Bolsas', 'precio' => 185]);
        Servicio::factory()->inactivo()->create(['nombre' => 'Mochilas', 'precio' => 900]);
        Servicio::factory()->extra()->create(['nombre' => 'Blanqueamiento de suelas', 'precio' => 50]);

        $ficha = $this->fichaDelNegocio($this->get(route('home'))->getContent());

        $this->assertSame('$120 - $185', $ficha['priceRange']);
    }

    public function test_un_horario_que_no_se_entiende_no_se_publica(): void
    {
        Negocio::factory()->create(['horarios' => 'Escríbenos y te decimos si estamos']);

        $this->assertArrayNotHasKey('openingHours', $this->fichaDelNegocio($this->get(route('home'))->getContent()));
    }

    public function test_un_dato_del_panel_no_puede_cerrar_la_etiqueta_del_script(): void
    {
        Negocio::factory()->create(['direccion' => 'Av. Juárez 12 </script><script>alert(1)</script>']);

        $html = $this->get(route('home'))->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('Av. Juárez 12', $this->fichaDelNegocio($html)['address']['streetAddress']);
    }

    public function test_el_canonico_sale_del_dominio_configurado_y_no_del_servidor_que_atendio(): void
    {
        $this->get('http://otro-servidor.test/precios')
            ->assertSee('<link rel="canonical" href="https://calzaclean.com/precios" />', false)
            ->assertSee('<meta property="og:url" content="https://calzaclean.com/precios" />', false);

        $this->get(route('home'))
            ->assertSee('<link rel="canonical" href="https://calzaclean.com/" />', false);
    }

    public function test_cada_pagina_tiene_su_titulo_y_su_descripcion(): void
    {
        $paginas = ['home', 'precios', 'cuidado-de-tenis'];

        $titulos = [];
        $descripciones = [];

        foreach ($paginas as $pagina) {
            $html = $this->get(route($pagina))->getContent();

            $titulos[] = $this->etiqueta('/<title>(.*?)<\/title>/s', $html);
            $descripciones[] = $this->etiqueta('/<meta name="description" content="(.*?)" \/>/s', $html);
        }

        $this->assertSame($titulos, array_unique($titulos), 'Dos páginas comparten el mismo título.');
        $this->assertSame($descripciones, array_unique($descripciones), 'Dos páginas comparten la misma descripción.');
    }

    public function test_la_imagen_de_vista_previa_sale_del_dominio_configurado(): void
    {
        $this->get(route('home'))
            ->assertSee('<meta property="og:image" content="https://calzaclean.com/img/vista-previa.png" />', false);

        $this->assertFileExists(public_path('img/vista-previa.png'));
        $this->assertSame([1200, 630], array_slice((array) getimagesize(public_path('img/vista-previa.png')), 0, 2));
    }

    public function test_el_mapa_del_sitio_lista_las_tres_paginas_publicas(): void
    {
        $respuesta = $this->get('/sitemap.xml');

        $respuesta->assertOk();
        $respuesta->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $respuesta->assertSee('<loc>https://calzaclean.com/</loc>', false);
        $respuesta->assertSee('<loc>https://calzaclean.com/precios</loc>', false);
        $respuesta->assertSee('<loc>https://calzaclean.com/cuidado-de-tenis</loc>', false);
        $respuesta->assertDontSee('/panel', false);
    }

    public function test_robots_deja_el_panel_fuera_y_apunta_al_mapa_del_sitio(): void
    {
        $respuesta = $this->get('/robots.txt');

        $respuesta->assertOk();
        $respuesta->assertSee('Disallow: /panel/', false);
        $respuesta->assertSee('Sitemap: https://calzaclean.com/sitemap.xml', false);

        $this->assertFileDoesNotExist(public_path('robots.txt'), 'Un robots.txt en public/ le gana a la ruta y se queda con el dominio viejo.');
    }

    public function test_los_iconos_son_los_de_calzaclean(): void
    {
        $apple = imagecreatefrompng(public_path('apple-touch-icon.png'));

        $this->assertSame(180, imagesx($apple));
        $this->assertSame('214966', $this->hexDelPixel($apple, 2, 2), 'El apple-touch-icon no arranca con el azul del logo.');

        $svg = (string) file_get_contents(public_path('favicon.svg'));
        $this->assertStringContainsString('aria-label="CalzaClean"', $svg);

        $ico = (string) file_get_contents(public_path('favicon.ico'));
        $cabecera = (array) unpack('vreservado/vtipo/vimagenes', substr($ico, 0, 6));
        $this->assertSame(1, $cabecera['tipo'], 'favicon.ico no es un icono.');
        $this->assertSame(3, $cabecera['imagenes'], 'favicon.ico no trae los tres tamaños.');
    }

    public function test_ninguna_vista_escribe_el_dominio_a_mano(): void
    {
        $vistas = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));

        $revisadas = 0;

        foreach ($vistas as $vista) {
            if (! str_ends_with($vista->getFilename(), '.blade.php')) {
                continue;
            }

            $revisadas++;

            $this->assertStringNotContainsString(
                'calzaclean.com',
                (string) file_get_contents($vista->getPathname()),
                "La vista [{$vista->getPathname()}] escribe el dominio en vez de tomarlo de la configuración.",
            );
        }

        $this->assertGreaterThan(0, $revisadas);
    }

    /**
     * La ficha de datos estructurados que trae la página.
     *
     * @return array<string, mixed>
     */
    private function fichaDelNegocio(string $html): array
    {
        $this->assertSame(
            1,
            preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $bloque),
            'La página no trae datos estructurados.',
        );

        $ficha = json_decode($bloque[1], true);

        $this->assertIsArray($ficha, 'Los datos estructurados no son JSON válido.');

        return $ficha;
    }

    private function etiqueta(string $patron, string $html): string
    {
        $this->assertSame(1, preg_match($patron, $html, $encontrado), "La página no trae [{$patron}].");

        return trim($encontrado[1]);
    }

    private function hexDelPixel(\GdImage $imagen, int $x, int $y): string
    {
        $color = imagecolorat($imagen, $x, $y);

        return sprintf('%02X%02X%02X', ($color >> 16) & 0xFF, ($color >> 8) & 0xFF, $color & 0xFF);
    }
}
