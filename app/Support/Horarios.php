<?php

namespace App\Support;

/**
 * Traduce los horarios del Negocio —texto libre que el Dueño escribe en el
 * Panel— a la forma que leen los buscadores: `Mo-Fr 10:00-19:00`.
 *
 * Un renglón que no se entiende se descarta. Los datos estructurados prefieren
 * quedarse sin horarios a publicar uno adivinado.
 */
final class Horarios
{
    /**
     * Los días como los escribe la gente y como los espera schema.org.
     *
     * @var array<string, string>
     */
    private const DIAS = [
        'lunes' => 'Mo',
        'martes' => 'Tu',
        'miercoles' => 'We',
        'jueves' => 'Th',
        'viernes' => 'Fr',
        'sabado' => 'Sa',
        'domingo' => 'Su',
    ];

    /**
     * El orden de la semana, para armar un rango de días.
     *
     * @var list<string>
     */
    private const SEMANA = ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'];

    /**
     * Un renglón por franja: «Lunes a viernes de 10:00 a 19:00» sale como
     * `Mo-Fr 10:00-19:00`.
     *
     * @return list<string>
     */
    public static function interpretar(?string $texto): array
    {
        if ($texto === null || trim($texto) === '') {
            return [];
        }

        $franjas = [];

        foreach (preg_split('/[\r\n;]+/', self::normalizar($texto)) ?: [] as $renglon) {
            $franja = self::renglon($renglon);

            if ($franja !== null && ! in_array($franja, $franjas, true)) {
                $franjas[] = $franja;
            }
        }

        return $franjas;
    }

    /**
     * Un renglón necesita las dos cosas para valer: días y una hora de
     * apertura con su hora de cierre.
     */
    private static function renglon(string $renglon): ?string
    {
        $dias = self::dias($renglon);
        $horas = self::horas($renglon);

        if ($dias === null || $horas === null) {
            return null;
        }

        return $dias.' '.$horas;
    }

    /**
     * Los días del renglón. «Lunes a viernes» es un rango; «lunes, miércoles y
     * viernes» es una lista.
     */
    private static function dias(string $renglon): ?string
    {
        $nombres = implode('|', array_keys(self::DIAS));

        if (preg_match('/\b('.$nombres.')s?\b\s*(?:a|-|hasta)\s*\b('.$nombres.')s?\b/', $renglon, $rango) === 1) {
            $desde = self::DIAS[$rango[1]];
            $hasta = self::DIAS[$rango[2]];

            if (array_search($desde, self::SEMANA, true) < array_search($hasta, self::SEMANA, true)) {
                return $desde.'-'.$hasta;
            }

            return null;
        }

        if (preg_match_all('/\b('.$nombres.')s?\b/', $renglon, $sueltos) === 0) {
            return null;
        }

        $dias = [];

        foreach ($sueltos[1] as $nombre) {
            $dia = self::DIAS[$nombre];

            if (! in_array($dia, $dias, true)) {
                $dias[] = $dia;
            }
        }

        usort($dias, fn (string $uno, string $otro): int => array_search($uno, self::SEMANA, true) <=> array_search($otro, self::SEMANA, true));

        return implode(',', $dias);
    }

    /**
     * La franja de horas. Se descarta la que no cierra después de abrir: una
     * hora suelta o un «de 10 a 7» no dicen si el cierre es de la mañana o de
     * la tarde.
     */
    private static function horas(string $renglon): ?string
    {
        if (preg_match('/(\d{1,2})(?::(\d{2}))?\s*(?:a|-|hasta)\s*(\d{1,2})(?::(\d{2}))?/', $renglon, $horas) !== 1) {
            return null;
        }

        $abre = self::hora((int) $horas[1], (int) $horas[2]);
        $cierra = self::hora((int) $horas[3], (int) ($horas[4] ?? 0));

        if ($abre === null || $cierra === null || $cierra <= $abre) {
            return null;
        }

        return sprintf('%02d:%02d-%02d:%02d', intdiv($abre, 60), $abre % 60, intdiv($cierra, 60), $cierra % 60);
    }

    /**
     * La hora en minutos desde la medianoche, o nada si no es una hora del día.
     */
    private static function hora(int $horas, int $minutos): ?int
    {
        if ($horas > 23 || $minutos > 59) {
            return null;
        }

        return $horas * 60 + $minutos;
    }

    /**
     * Minúsculas y sin acentos: «Miércoles» y «miercoles» son el mismo día.
     */
    private static function normalizar(string $texto): string
    {
        return strtr(mb_strtolower($texto), [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u',
            '–' => '-', '—' => '-',
        ]);
    }
}
