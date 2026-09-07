<?php

namespace Database\Seeders;

use App\Models\Negocio;
use App\Models\Servicio;
use Illuminate\Database\Seeder;

/**
 * Deja cargado el contenido que no se inventa: la lista de precios del taller y
 * los datos del Negocio. Se puede correr las veces que haga falta.
 */
class ContenidoSeeder extends Seeder
{
    /**
     * El catálogo con precio, y al final los dos Extras.
     *
     * @var list<array<string, mixed>>
     */
    private const SERVICIOS = [
        ['nombre' => 'Limpieza básica', 'aplica_a' => 'Cuero, piel y tela', 'precio' => 120, 'es_extra' => false],
        ['nombre' => 'Limpieza especializada', 'aplica_a' => 'Gamuza, ante y delicados', 'precio' => 170, 'es_extra' => false],
        ['nombre' => 'Limpieza infantil', 'aplica_a' => 'Hasta talla 20 de niño', 'precio' => 100, 'es_extra' => false],
        ['nombre' => 'Botas', 'aplica_a' => null, 'precio' => 150, 'es_extra' => false],
        ['nombre' => 'Bolsas', 'aplica_a' => null, 'precio' => 185, 'es_extra' => false],
        ['nombre' => 'Mochilas', 'aplica_a' => null, 'precio' => 200, 'es_extra' => false],
        ['nombre' => 'Blanqueamiento de suelas', 'aplica_a' => null, 'precio' => 50, 'es_extra' => true],
        ['nombre' => 'Entrega express', 'aplica_a' => null, 'precio' => 100, 'es_extra' => true],
    ];

    /**
     * Los datos del taller que no salen de la semilla de `config/sitio.php`.
     *
     * @var array<string, string>
     */
    private const NEGOCIO = [
        'direccion' => 'San Juan del Río, Qro.',
    ];

    public function run(): void
    {
        foreach (self::SERVICIOS as $orden => $servicio) {
            Servicio::query()->updateOrCreate(
                ['nombre' => $servicio['nombre']],
                [
                    'aplica_a' => $servicio['aplica_a'],
                    'precio' => $servicio['precio'],
                    'es_extra' => $servicio['es_extra'],
                    'orden' => $orden + 1,
                    'activo' => true,
                ],
            );
        }

        $negocio = Negocio::actual();

        // La semilla de contacto vive en config/sitio.php; el resto, acá
        // arriba. En los dos casos solo se llena el campo que esté vacío: lo
        // que la Dueña haya guardado desde el Panel se queda como está.
        $semilla = self::NEGOCIO + [
            'whatsapp' => config('sitio.whatsapp'),
            ...config('sitio.redes', []),
        ];

        foreach ($semilla as $campo => $valor) {
            if (blank($negocio->{$campo}) && filled($valor)) {
                $negocio->{$campo} = $valor;
            }
        }

        $negocio->save();
    }
}
