<?php

namespace Tests\Feature\Sitio;

use App\Enums\Material;
use App\Models\Negocio;
use App\Models\Pregunta;
use App\Models\Trabajo;
use App\Support\EnlaceWhatsApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EnlaceWhatsappTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, string}>
     */
    public static function numerosEscritos(): array
    {
        return [
            'con espacios' => ['52 427 180 3585', '524271803585'],
            'con el signo de más' => ['+52 427 180 3585', '524271803585'],
            'con el 1 de México' => ['+52 1 427 180 3585', '524271803585'],
            'sin lada de país' => ['427 180 3585', '524271803585'],
            'con guiones y paréntesis' => ['(427) 180-3585', '524271803585'],
            'con el 00 internacional' => ['0052 427 180 3585', '524271803585'],
        ];
    }

    #[DataProvider('numerosEscritos')]
    public function test_el_numero_queda_en_puros_digitos(string $escrito, string $esperado): void
    {
        $this->assertSame(
            'https://wa.me/'.$esperado,
            EnlaceWhatsApp::con($escrito),
            "La forma [{$escrito}] no quedó normalizada.",
        );
    }

    public function test_un_numero_vacio_no_arma_enlace(): void
    {
        $this->assertNull(EnlaceWhatsApp::con('   '));
        $this->assertNull(EnlaceWhatsApp::con(null));
    }

    public function test_el_mensaje_viaja_codificado(): void
    {
        // Acentos, comas y signos de interrogación: los tres se rompen si el
        // mensaje se pega crudo en la dirección.
        $enlace = EnlaceWhatsApp::con('+52 427 180 3585', '¿Cuánto tarda, más o menos?');

        $this->assertSame(
            'https://wa.me/524271803585?text=%C2%BFCu%C3%A1nto%20tarda%2C%20m%C3%A1s%20o%20menos%3F',
            $enlace,
        );

        // Y del otro lado llega legible, que es lo que se lee en el celular.
        parse_str((string) parse_url($enlace, PHP_URL_QUERY), $parametros);

        $this->assertSame('¿Cuánto tarda, más o menos?', $parametros['text']);
    }

    public function test_sin_mensaje_el_enlace_no_lleva_query(): void
    {
        $this->assertSame('https://wa.me/524271803585', EnlaceWhatsApp::con('+52 427 180 3585'));
    }

    public function test_cada_punto_de_salida_precarga_su_propio_mensaje(): void
    {
        Negocio::factory()->create();

        $mensajes = array_map(
            fn (string $origen): string => EnlaceWhatsApp::mensajeDesde($origen),
            ['contacto', 'portada', 'precios', 'foto', 'recoleccion'],
        );

        $this->assertSame($mensajes, array_unique($mensajes));
        $this->assertStringContainsString('cotizar', EnlaceWhatsApp::mensajeDesde('portada'));
        $this->assertStringContainsString('precios', EnlaceWhatsApp::mensajeDesde('precios'));
    }

    public function test_un_origen_que_no_existe_no_pasa_callado(): void
    {
        $this->expectException(InvalidArgumentException::class);

        EnlaceWhatsApp::mensajeDesde('inventado');
    }

    public function test_el_mensaje_de_un_trabajo_menciona_su_material(): void
    {
        $trabajo = Trabajo::factory()->make(['material' => Material::Gamuza]);

        $this->assertStringContainsString('gamuza', EnlaceWhatsApp::mensajeSobre($trabajo));
    }

    public function test_el_mensaje_de_una_pregunta_lleva_la_pregunta(): void
    {
        $pregunta = Pregunta::factory()->make(['pregunta' => '¿Recogen a domicilio?']);

        $this->assertStringContainsString('¿Recogen a domicilio?', EnlaceWhatsApp::mensajeSobre($pregunta));
    }

    public function test_sin_numero_cargado_no_hay_enlace_en_ningun_punto_de_salida(): void
    {
        Negocio::factory()->sinWhatsapp()->create();

        $this->assertNull(EnlaceWhatsApp::desde('portada'));
        $this->assertNull(EnlaceWhatsApp::sobre(Trabajo::factory()->make()));
    }

    public function test_cambiar_el_whatsapp_cambia_todos_los_enlaces_del_sitio(): void
    {
        $negocio = Negocio::factory()->create(['whatsapp' => '+52 427 180 3585']);
        Trabajo::factory()->create(['material' => Material::Gamuza]);
        Pregunta::factory()->create(['pregunta' => '¿Recogen a domicilio?']);

        foreach ([route('home'), route('precios')] as $direccion) {
            $this->get($direccion)->assertSee('https://wa.me/524271803585', false);
        }

        $negocio->update(['whatsapp' => '+52 1 442 123 4567']);

        foreach ([route('home'), route('precios')] as $direccion) {
            $respuesta = $this->get($direccion);

            $respuesta->assertSee('https://wa.me/524421234567', false);
            $respuesta->assertDontSee('https://wa.me/524271803585', false);
        }
    }

    public function test_la_galeria_y_las_preguntas_salen_con_su_propio_mensaje(): void
    {
        Negocio::factory()->create(['whatsapp' => '+52 427 180 3585']);
        Trabajo::factory()->create(['material' => Material::Gamuza]);
        Pregunta::factory()->create(['pregunta' => '¿Recogen a domicilio?']);

        $respuesta = $this->get(route('home'));

        $respuesta->assertSee(rawurlencode(EnlaceWhatsApp::mensajeDesde('portada')), false);
        $respuesta->assertSee(rawurlencode('Hola, vi el par de gamuza'), false);
        $respuesta->assertSee(rawurlencode('Hola, tengo una duda sobre: ¿Recogen a domicilio?'), false);
    }

    public function test_el_boton_flotante_espera_al_pie_y_al_comparador(): void
    {
        Negocio::factory()->create();
        Trabajo::factory()->create();

        $respuesta = $this->get(route('home'));

        $respuesta->assertSee('data-flotante', false);

        // El pie y el comparador son los dos que lo esconden al entrar en
        // pantalla: sin la marca en los dos, el botón los tapa.
        $respuesta->assertSee('<footer data-sin-flotante', false);
        $respuesta->assertSee('data-comparador data-sin-flotante', false);
    }

    public function test_sin_numero_cargado_no_se_dibuja_el_boton_flotante(): void
    {
        Negocio::factory()->sinWhatsapp()->create();

        $respuesta = $this->get(route('home'));

        $respuesta->assertOk();
        $respuesta->assertDontSee('wa.me', false);
    }
}
