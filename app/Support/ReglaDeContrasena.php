<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

/**
 * El único lugar donde se decide qué exige una contraseña.
 *
 * Antes el mínimo estaba escrito a mano en cuatro archivos y la regla en un
 * quinto. Cuando producción subió a doce caracteres, los cuatro mensajes
 * siguieron diciendo ocho: quien intentaba crear una cuenta probaba
 * contraseñas de nueve y diez sin entender por qué las rechazaba.
 */
final class ReglaDeContrasena
{
    /**
     * Producción pide una contraseña seria porque el Panel está expuesto a
     * internet. En desarrollo alcanza con el mínimo de Laravel.
     */
    public static function minimo(): int
    {
        return app()->isProduction() ? 12 : 8;
    }

    /**
     * `null` deja el valor por omisión de Laravel, que es `min(8)`.
     */
    public static function regla(): ?Password
    {
        if (! app()->isProduction()) {
            return null;
        }

        return Password::min(self::minimo())
            ->mixedCase()
            ->letters()
            ->numbers()
            ->symbols()
            ->uncompromised();
    }

    /**
     * La exigencia dicha en una línea, para ponerla en pantalla o en la ayuda
     * de un comando. Se arma a partir del mismo lugar que la regla, así que no
     * puede quedar desfasada.
     */
    public static function enPalabras(): string
    {
        $minimo = self::minimo();

        if (! app()->isProduction()) {
            return "Al menos {$minimo} caracteres.";
        }

        return "Al menos {$minimo} caracteres, con mayúsculas y minúsculas, un número y un símbolo.";
    }
}
