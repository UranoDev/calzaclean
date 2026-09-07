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
     * Los datos del taller. Solo llenan los campos que estén vacíos: lo que la
     * Dueña haya editado desde el Panel se queda como está.
     *
     * @var array<string, string>
     */
    private const NEGOCIO = [
        'whatsapp' => '+52 427 180 3585',
        'direccion' => 'San Juan del Río, Qro.',
        'instagram' => 'https://www.instagram.com/calza_clean_/',
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

        foreach (self::NEGOCIO as $campo => $valor) {
            if (blank($negocio->{$campo})) {
                $negocio->{$campo} = $valor;
            }
        }

        $negocio->save();
    }
}
