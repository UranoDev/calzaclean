<?php

namespace Tests\Feature\Sitio;

use App\Enums\Material;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialesTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_seis_materiales_aparecen_con_su_etiqueta(): void
    {
        $html = $this->get(route('home'))->getContent();

        foreach (Material::cases() as $material) {
            $this->assertStringContainsString(
                '>'.$material->etiqueta().'</h3>',
                $html,
                "Falta la ficha del material {$material->value}.",
            );
        }
    }

    public function test_cada_material_dice_que_se_hace_y_que_se_usa(): void
    {
        $html = $this->get(route('home'))->getContent();

        $this->assertSame(count(Material::cases()), substr_count($html, 'Qué se hace'));
        $this->assertSame(count(Material::cases()), substr_count($html, 'Qué se usa'));
    }

    public function test_la_seccion_lleva_a_la_lista_de_precios(): void
    {
        $this->get(route('home'))->assertSee('Ver los precios por servicio', false);
    }
}
