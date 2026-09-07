<?php

namespace Tests\Feature\Sitio;

use App\Models\Negocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Paleta;
use Tests\TestCase;

/**
 * El repaso de CALZ-17, hecho prueba: el contraste de cada par de la paleta que
 * termina en pantalla, el anillo de foco, el texto alternativo y el trato del
 * movimiento reducido.
 *
 * Los números salen de `Tests\Support\Paleta`, que lee los tokens de
 * `resources/css/app.css` y mide como pide WCAG 2.1. No hay hexadecimales
 * copiados acá: cambiar un token mueve la medición.
 */
class AccesibilidadTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Los pares que existen en pantalla, en el Sitio y en el Panel. El mínimo
     * es 4.5:1; el texto grande —el título de sección, el precio— llega igual,
     * así que no se le afloja a 3:1.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function paresDeTexto(): array
    {
        return [
            'texto sobre blanco' => ['azul-profundo', '#ffffff'],
            'texto sobre blanco humo' => ['azul-profundo', 'blanco-humo'],
            'texto sobre el fondo de sección' => ['azul-profundo', 'azul-claro-tenue'],
            'texto secundario sobre blanco' => ['gris-pizarra', '#ffffff'],
            'texto secundario sobre blanco humo' => ['gris-pizarra', 'blanco-humo'],
            'texto secundario sobre el fondo de sección' => ['gris-pizarra', 'azul-claro-tenue'],
            'texto del pie' => ['#ffffff', 'azul-calzaclean'],
            'texto del botón oscuro' => ['#ffffff', 'azul-profundo'],
            'texto del botón oscuro con el puntero encima' => ['#ffffff', 'azul-profundo-hover'],
            'texto del botón de WhatsApp' => ['#ffffff', 'verde-accion'],
            'texto del botón de WhatsApp con el puntero encima' => ['#ffffff', 'verde-accion-hover'],
        ];
    }

    #[DataProvider('paresDeTexto')]
    public function test_todo_texto_alcanza_el_minimo_contra_su_fondo(string $texto, string $fondo): void
    {
        $medida = Paleta::contraste($texto, $fondo);

        $this->assertGreaterThanOrEqual(
            4.5,
            $medida,
            "El texto queda en {$medida}:1 contra su fondo, debajo del mínimo de 4.5:1.",
        );
    }

    /**
     * El pie escribe dos renglones en blanco a media tinta. El contraste se
     * mide contra el color que queda encima del azul, no contra el blanco.
     */
    public function test_los_renglones_a_media_tinta_del_pie_alcanzan_el_minimo(): void
    {
        foreach ([0.8, 0.7] as $opacidad) {
            $color = Paleta::sobre('#ffffff', $opacidad, 'azul-calzaclean');
            $medida = Paleta::contraste($color, 'azul-calzaclean');

            $this->assertGreaterThanOrEqual(
                4.5,
                $medida,
                "El blanco al {$opacidad} del pie queda en {$medida}:1.",
            );
        }
    }

    /**
     * Un indicador de foco no es texto: le toca 3:1 contra lo que tiene al
     * lado. El anillo del pie es blanco justamente porque ninguno de los
     * colores oscuros llega sobre el azul.
     */
    public function test_el_anillo_de_foco_se_distingue_de_su_superficie(): void
    {
        $anillos = [
            'el anillo azul sobre blanco' => ['azul-profundo', '#ffffff'],
            'el anillo azul sobre blanco humo' => ['azul-profundo', 'blanco-humo'],
            'el anillo azul sobre el fondo de sección' => ['azul-profundo', 'azul-claro-tenue'],
            'el anillo del botón de WhatsApp sobre blanco' => ['verde-accion', '#ffffff'],
            'el anillo del botón de WhatsApp sobre blanco humo' => ['verde-accion', 'blanco-humo'],
            'el anillo claro sobre el pie' => ['#ffffff', 'azul-calzaclean'],
        ];

        foreach ($anillos as $donde => [$anillo, $superficie]) {
            $medida = Paleta::contraste($anillo, $superficie);

            $this->assertGreaterThanOrEqual(3.0, $medida, "{$donde} queda en {$medida}:1.");
        }
    }

    /**
     * El azul claro da 2.4:1 contra blanco. PaletaTest ya lo cuida en las
     * vistas del Sitio, por dirección visual; acá se mide el Panel, que se
     * había quedado con un icono pintado de ese color.
     */
    public function test_ninguna_vista_propia_usa_el_azul_claro_como_color_de_texto(): void
    {
        foreach ($this->vistasPropias() as $vista) {
            $this->assertDoesNotMatchRegularExpression(
                '/\btext-azul-claro\b/',
                file_get_contents($vista),
                "La vista [{$vista}] usa el azul claro como color de texto.",
            );
        }
    }

    /**
     * El salto suave vive en el CSS y no como clase en el `html`: la capa de
     * utilidades va después de la base, así que ahí le ganaba al bloque de
     * movimiento reducido y el salto seguía siendo suave.
     */
    public function test_el_salto_suave_cede_ante_el_movimiento_reducido(): void
    {
        $css = file_get_contents(base_path('resources/css/app.css'));

        $this->assertStringContainsString('@media (prefers-reduced-motion: no-preference)', $css);
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $css);
        $this->assertMatchesRegularExpression('/transition-duration:\s*0\.01ms\s*!important/', $css);

        foreach ($this->vistasPropias() as $vista) {
            $this->assertStringNotContainsString(
                'scroll-smooth',
                file_get_contents($vista),
                "La vista [{$vista}] pide el salto suave como clase, y así le gana al bloque de movimiento reducido.",
            );
        }
    }

    /**
     * Ninguna imagen entra sin texto alternativo, y ninguno dice «imagen» ni el
     * nombre del archivo.
     */
    public function test_toda_imagen_lleva_texto_alternativo(): void
    {
        foreach ($this->vistasPropias() as $vista) {
            $contenido = file_get_contents($vista);

            preg_match_all('/<img\b(?:\{\{.*?\}\}|[^>])*>/is', $contenido, $etiquetas);

            foreach ($etiquetas[0] as $etiqueta) {
                $this->assertMatchesRegularExpression(
                    '/\balt=/',
                    $etiqueta,
                    "Una imagen de [{$vista}] entra sin texto alternativo.",
                );

                $this->assertDoesNotMatchRegularExpression(
                    '/alt="[^"]*(imagen|foto de archivo|\.jpe?g|\.png|\.webp)/i',
                    $etiqueta,
                    "El texto alternativo de una imagen de [{$vista}] describe el archivo, no lo que se ve.",
                );
            }
        }
    }

    /**
     * El recorrido con teclado arranca en el salto al contenido y termina en el
     * botón de WhatsApp del pie. Lo que se puede comprobar del lado del
     * servidor es que el salto exista, que apunte a un ancla que está, y que en
     * el pie —la única superficie oscura— nada quede con el anillo azul.
     */
    public function test_el_recorrido_con_teclado_empieza_en_el_salto_al_contenido(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('href="#contenido"', $html);
        $this->assertStringContainsString('id="contenido"', $html);
        $this->assertLessThan(
            strpos($html, '<header'),
            strpos($html, 'href="#contenido"'),
            'El salto al contenido no es lo primero que recibe el foco.',
        );
    }

    public function test_todo_lo_enfocable_del_pie_dibuja_su_anillo_en_blanco(): void
    {
        Negocio::factory()->create();

        $html = $this->get(route('home'))->assertOk()->getContent();

        $desde = strpos($html, '<footer');
        $hasta = strpos($html, '</footer>');

        $this->assertNotFalse($desde, 'El Sitio no dibujó el pie.');

        $pie = substr($html, $desde, $hasta - $desde);

        preg_match_all('/<a\b(?:\{\{.*?\}\}|[^>])*>/is', $pie, $enlaces);

        $this->assertNotEmpty($enlaces[0], 'El pie no dibujó ningún enlace.');

        foreach ($enlaces[0] as $enlace) {
            $this->assertStringContainsString(
                'focus-visible:outline-white',
                $enlace,
                'Un enlace del pie deja el anillo de foco en un color oscuro, que sobre el azul del pie no se ve.',
            );
        }
    }

    /**
     * Las vistas que se escribieron para este sitio. Las del starter kit —el
     * acceso, los ajustes de la cuenta y los iconos de Flux— quedan afuera: no
     * las dibuja el Sitio ni el Panel.
     *
     * @return list<string>
     */
    private function vistasPropias(): array
    {
        $carpetas = [
            'views/sitio/*.blade.php',
            'views/panel/*.blade.php',
            'views/panel/ajustes/*.blade.php',
            'views/components/*.blade.php',
            'views/components/layouts/*.blade.php',
            'views/livewire/panel/*.blade.php',
            'views/livewire/panel/ajustes/*.blade.php',
        ];

        $vistas = [];

        foreach ($carpetas as $patron) {
            $vistas = array_merge($vistas, glob(resource_path($patron)));
        }

        // Del starter kit quedan piezas sueltas en `components/` que no dibuja
        // ninguna pantalla viva; se revisan cuando alguien las use.
        return array_values(array_filter(
            $vistas,
            fn (string $vista): bool => ! str_starts_with(basename($vista), 'app-logo')
                && ! in_array(basename($vista), [
                    'auth-header.blade.php',
                    'auth-session-status.blade.php',
                    'desktop-user-menu.blade.php',
                    'passkey-registration.blade.php',
                    'passkey-verify.blade.php',
                    'placeholder-pattern.blade.php',
                ], true),
        ));
    }
}
