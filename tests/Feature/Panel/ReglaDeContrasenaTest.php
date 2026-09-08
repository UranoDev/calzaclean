<?php

namespace Tests\Feature\Panel;

use App\Support\ReglaDeContrasena;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Tests\TestCase;

/**
 * El mensaje de la contraseña estuvo diciendo «al menos 8 caracteres» mientras
 * producción exigía doce, porque el número estaba escrito a mano en cuatro
 * archivos. Estas pruebas afirman que el texto y la regla salgan del mismo
 * lugar, que es lo único que impide que vuelvan a desfasarse.
 */
class ReglaDeContrasenaTest extends TestCase
{
    public function test_el_mensaje_nombra_el_minimo_que_la_regla_exige(): void
    {
        $minimo = ReglaDeContrasena::minimo();

        $validador = Validator::make(
            ['password' => 'abc', 'password_confirmation' => 'abc'],
            ['password' => ['required', 'string', Password::default(), 'confirmed']],
        );

        $this->assertTrue($validador->fails());
        $this->assertStringContainsString(
            (string) $minimo,
            $validador->errors()->first('password'),
            'El mensaje de la contraseña tiene que nombrar el mínimo real, no uno escrito a mano.',
        );
    }

    public function test_una_contrasena_del_largo_minimo_pasa_el_requisito_de_longitud(): void
    {
        $minimo = ReglaDeContrasena::minimo();
        $contrasena = 'Aa1!'.str_repeat('x', max(0, $minimo - 4));

        $validador = Validator::make(
            ['password' => $contrasena, 'password_confirmation' => $contrasena],
            ['password' => ['required', 'string', Password::default(), 'confirmed']],
        );

        $validador->fails();

        $this->assertStringNotContainsString(
            'caracteres',
            $validador->errors()->first('password'),
            "Una contraseña de {$minimo} caracteres no debería fallar por longitud.",
        );
    }

    public function test_el_texto_de_ayuda_menciona_el_minimo(): void
    {
        $this->assertStringContainsString(
            (string) ReglaDeContrasena::minimo(),
            ReglaDeContrasena::enPalabras(),
        );
    }
}
