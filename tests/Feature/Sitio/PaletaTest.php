<?php

namespace Tests\Feature\Sitio;

use Tests\TestCase;

class PaletaTest extends TestCase
{
    /**
     * Las vistas del Sitio consumen los tokens de la Paleta A; los hexadecimales
     * viven en resources/css/app.css y en el logo, en ningún otro lado.
     */
    public function test_ninguna_vista_del_sitio_escribe_un_color_literal(): void
    {
        foreach ($this->vistasDelSitio() as $vista) {
            $this->assertDoesNotMatchRegularExpression(
                '/#[0-9a-fA-F]{3,8}\b/',
                $this->sinMetaTheme(file_get_contents($vista)),
                "La vista [{$vista}] escribe un color literal en vez de consumir un token.",
            );
        }
    }

    public function test_el_azul_claro_no_se_usa_como_color_de_texto(): void
    {
        foreach ($this->vistasDelSitio() as $vista) {
            $this->assertDoesNotMatchRegularExpression(
                '/\btext-azul-claro\b/',
                file_get_contents($vista),
                "La vista [{$vista}] usa el azul claro como color de texto.",
            );
        }
    }

    public function test_el_verde_de_accion_solo_aparece_en_el_boton_de_whatsapp(): void
    {
        foreach ($this->vistasDelSitio() as $vista) {
            if (basename($vista) === 'boton-whatsapp.blade.php') {
                continue;
            }

            $this->assertStringNotContainsString(
                'verde-accion',
                file_get_contents($vista),
                "La vista [{$vista}] reparte el verde de acción fuera del botón de WhatsApp.",
            );
        }
    }

    /**
     * @return list<string>
     */
    private function vistasDelSitio(): array
    {
        return array_merge(
            glob(resource_path('views/sitio/*.blade.php')),
            glob(resource_path('views/components/*.blade.php')),
            glob(resource_path('views/components/layouts/publico.blade.php')),
        );
    }

    /**
     * El color de la barra del navegador es un dato del documento, no un estilo.
     */
    private function sinMetaTheme(string $contenido): string
    {
        return preg_replace('/<meta name="theme-color"[^>]*>/', '', $contenido);
    }
}
