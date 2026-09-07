<?php

namespace Tests\Feature\Sitio;

use App\Support\Horarios;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HorariosTest extends TestCase
{
    /**
     * @param  list<string>  $esperado
     */
    #[DataProvider('horariosEscritosEnElPanel')]
    public function test_los_horarios_del_panel_se_traducen_para_los_buscadores(?string $escrito, array $esperado): void
    {
        $this->assertSame($esperado, Horarios::interpretar($escrito));
    }

    /**
     * @return array<string, array{?string, list<string>}>
     */
    public static function horariosEscritosEnElPanel(): array
    {
        return [
            'un rango de días' => ['Lunes a viernes de 10:00 a 19:00', ['Mo-Fr 10:00-19:00']],
            'sin acentos' => ['Lunes a sabado de 9 a 18', ['Mo-Sa 09:00-18:00']],
            'días sueltos' => ['Lunes, miércoles y viernes de 10:00 a 14:00', ['Mo,We,Fr 10:00-14:00']],
            'un día solo, en plural' => ['Sábados de 10:00 a 14:00', ['Sa 10:00-14:00']],
            'dos renglones' => [
                "Lunes a viernes de 10:00 a 19:00\nSábados de 10:00 a 14:00",
                ['Mo-Fr 10:00-19:00', 'Sa 10:00-14:00'],
            ],
            'con guion en vez de «a»' => ['Martes a jueves 11:30 - 20:00', ['Tu-Th 11:30-20:00']],
            'el mismo renglón dos veces' => [
                "Lunes a viernes de 10 a 19\nLunes a viernes de 10 a 19",
                ['Mo-Fr 10:00-19:00'],
            ],
            'sin horas' => ['Lunes a viernes', []],
            'sin días' => ['De 10:00 a 19:00', []],
            'una hora que no existe' => ['Lunes a viernes de 10:00 a 25:00', []],
            'cierra antes de abrir' => ['Domingos de 10 a 7', []],
            'un aviso, no un horario' => ['Escríbenos y te decimos si estamos', []],
            'vacío' => ['', []],
            'sin cargar' => [null, []],
        ];
    }
}
