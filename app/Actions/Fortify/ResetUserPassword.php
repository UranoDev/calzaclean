<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

/**
 * El Panel no tiene registro, pero sí necesita una salida cuando alguien olvida
 * su contraseña. Aquí se valida con las mismas reglas que usan el perfil y el
 * comando de alta, para que una cuenta no termine con una contraseña más débil
 * de la que se le pidió al crearla.
 */
class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * @param  User  $user
     * @param  array<string, string>  $input
     */
    public function reset($user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ], [
            'password.required' => 'La contraseña no puede quedar vacía.',
            'password.confirmed' => 'Las dos contraseñas no coinciden.',
        ])->validate();

        $user->forceFill([
            'password' => $input['password'],
        ])->save();
    }
}
