<?php

namespace Tests\Support;

/**
 * Lee los tokens de color de `resources/css/app.css` y mide el contraste entre
 * dos de ellos.
 *
 * Los hexadecimales no se copian acá: se leen del archivo donde viven, así que
 * cambiar un token mueve la medición y la prueba lo dice. Los derivados están
 * escritos como `color-mix(in oklab, …)`, que es lo que resuelve el navegador,
 * así que la mezcla se hace en oklab y no en sRGB: en sRGB da otro color y la
 * medición sería de un color que nadie ve.
 */
final class Paleta
{
    /** @var array<string, string>|null */
    private static ?array $tokens = null;

    /**
     * El hexadecimal de un token, con los `color-mix` ya resueltos.
     */
    public static function token(string $nombre): string
    {
        self::$tokens ??= self::leerDelCss();

        if (! isset(self::$tokens[$nombre])) {
            throw new \InvalidArgumentException("No hay ningún token [--color-{$nombre}] en resources/css/app.css.");
        }

        return self::resolver(self::$tokens[$nombre]);
    }

    /**
     * La razón de contraste entre dos colores, redondeada a dos decimales, tal
     * como la define WCAG 2.1. Cada argumento es un nombre de token o un
     * hexadecimal.
     */
    public static function contraste(string $uno, string $otro): float
    {
        $a = self::luminancia(self::hex($uno));
        $b = self::luminancia(self::hex($otro));

        return round((max($a, $b) + 0.05) / (min($a, $b) + 0.05), 2);
    }

    /**
     * Un color blanco a media tinta sobre un fondo, que es lo que dibuja
     * `text-white/80`: el contraste se mide contra el color que queda, no
     * contra el blanco.
     */
    public static function sobre(string $color, float $opacidad, string $fondo): string
    {
        [$rf, $gf, $bf] = self::componentes(self::hex($color));
        [$rb, $gb, $bb] = self::componentes(self::hex($fondo));

        return sprintf(
            '#%02x%02x%02x',
            (int) round($rf * $opacidad + $rb * (1 - $opacidad)),
            (int) round($gf * $opacidad + $gb * (1 - $opacidad)),
            (int) round($bf * $opacidad + $bb * (1 - $opacidad)),
        );
    }

    private static function hex(string $valor): string
    {
        return str_starts_with($valor, '#') ? $valor : self::token($valor);
    }

    /**
     * @return array<string, string>
     */
    private static function leerDelCss(): array
    {
        $css = file_get_contents(base_path('resources/css/app.css'));

        preg_match_all('/--color-([a-z0-9-]+):\s*([^;]+);/i', $css, $encontrados, PREG_SET_ORDER);

        $tokens = [];

        foreach ($encontrados as [, $nombre, $valor]) {
            $tokens[$nombre] = trim($valor);
        }

        return $tokens;
    }

    /**
     * Resuelve un valor de token: un hexadecimal se devuelve tal cual, un
     * `color-mix(in oklab, var(--color-x) N%, y)` se mezcla.
     */
    private static function resolver(string $valor): string
    {
        if (preg_match('/^#[0-9a-f]{6}$/i', $valor)) {
            return strtolower($valor);
        }

        if (preg_match('/color-mix\(\s*in\s+oklab\s*,\s*var\(--color-([a-z0-9-]+)\)\s*([\d.]+)%\s*,\s*([a-z#0-9]+)\s*\)/i', $valor, $partes)) {
            return self::mezclarEnOklab(
                self::token($partes[1]),
                self::nombrado($partes[3]),
                (float) $partes[2] / 100,
            );
        }

        throw new \RuntimeException("No sé resolver el valor de color [{$valor}].");
    }

    private static function nombrado(string $color): string
    {
        return match (strtolower($color)) {
            'white' => '#ffffff',
            'black' => '#000000',
            default => $color,
        };
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private static function componentes(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    private static function luminancia(string $hex): float
    {
        [$r, $g, $b] = array_map(self::aLineal(...), self::componentes($hex));

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    private static function aLineal(int $canal): float
    {
        $c = $canal / 255;

        return $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
    }

    private static function aSrgb(float $canal): int
    {
        $v = $canal <= 0.0031308 ? $canal * 12.92 : 1.055 * $canal ** (1 / 2.4) - 0.055;

        return (int) max(0, min(255, round($v * 255)));
    }

    /**
     * `$parte` es cuánto pesa el primer color, de 0 a 1.
     */
    private static function mezclarEnOklab(string $uno, string $otro, float $parte): string
    {
        $a = self::aOklab($uno);
        $b = self::aOklab($otro);

        $mezcla = [];

        foreach ([0, 1, 2] as $eje) {
            $mezcla[$eje] = $a[$eje] * $parte + $b[$eje] * (1 - $parte);
        }

        [$r, $g, $azul] = self::deOklab($mezcla);

        return sprintf('#%02x%02x%02x', self::aSrgb($r), self::aSrgb($g), self::aSrgb($azul));
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private static function aOklab(string $hex): array
    {
        [$r, $g, $b] = array_map(self::aLineal(...), self::componentes($hex));

        $l = self::raizCubica(0.4122214708 * $r + 0.5363325363 * $g + 0.0514459929 * $b);
        $m = self::raizCubica(0.2119034982 * $r + 0.6806995451 * $g + 0.1073969566 * $b);
        $s = self::raizCubica(0.0883024619 * $r + 0.2817188376 * $g + 0.6299787005 * $b);

        return [
            0.2104542553 * $l + 0.7936177850 * $m - 0.0040720468 * $s,
            1.9779984951 * $l - 2.4285922050 * $m + 0.4505937099 * $s,
            0.0259040371 * $l + 0.7827717662 * $m - 0.8086757660 * $s,
        ];
    }

    /**
     * @param  array{0: float, 1: float, 2: float}  $lab
     * @return array{0: float, 1: float, 2: float}
     */
    private static function deOklab(array $lab): array
    {
        [$L, $a, $b] = $lab;

        $l = ($L + 0.3963377774 * $a + 0.2158037573 * $b) ** 3;
        $m = ($L - 0.1055613458 * $a - 0.0638541728 * $b) ** 3;
        $s = ($L - 0.0894841775 * $a - 1.2914855480 * $b) ** 3;

        return [
            4.0767416621 * $l - 3.3077115913 * $m + 0.2309699292 * $s,
            -1.2684380046 * $l + 2.6097574011 * $m - 0.3413193965 * $s,
            -0.0041960863 * $l - 0.7034186147 * $m + 1.7076147010 * $s,
        ];
    }

    private static function raizCubica(float $valor): float
    {
        return $valor < 0 ? -((-$valor) ** (1 / 3)) : $valor ** (1 / 3);
    }
}
