<?php

namespace App\Console\Commands;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * El Panel tiene una sola usuaria y ninguna pantalla de registro: la cuenta de
 * la Dueña se crea desde aquí, con las mismas reglas que valida el perfil.
 */
class CrearDuena extends Command
{
    use PasswordValidationRules, ProfileValidationRules;

    protected $signature = 'calzaclean:crear-duena';

    protected $description = 'Crea la cuenta de la Dueña, la única con acceso al Panel';

    public function handle(): int
    {
        if (User::query()->exists()) {
            $this->components->error('Ya hay una cuenta creada. El Panel admite una sola.');

            return self::FAILURE;
        }

        $nombre = (string) $this->ask('Nombre');
        $correo = (string) $this->ask('Correo');
        $contrasena = (string) $this->secret('Contraseña');
        $confirmacion = (string) $this->secret('Repite la contraseña');

        $validador = Validator::make([
            'name' => $nombre,
            'email' => $correo,
            'password' => $contrasena,
            'password_confirmation' => $confirmacion,
        ], [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ], [
            'name.required' => 'El nombre no puede quedar vacío.',
            'name.max' => 'El nombre no puede pasar de 255 caracteres.',
            'email.required' => 'El correo no puede quedar vacío.',
            'email.email' => 'El correo no tiene forma de correo.',
            'email.max' => 'El correo no puede pasar de 255 caracteres.',
            'password.required' => 'La contraseña no puede quedar vacía.',
            'password.confirmed' => 'Las dos contraseñas no coinciden.',
            'password.min' => 'La contraseña necesita al menos 8 caracteres.',
        ]);

        if ($validador->fails()) {
            foreach ($validador->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $duena = User::query()->create([
            'name' => $nombre,
            'email' => $correo,
            'password' => $contrasena,
        ]);

        $duena->forceFill(['email_verified_at' => now()])->save();

        $this->components->info("Cuenta creada para {$duena->email}. El Panel está en /panel.");

        return self::SUCCESS;
    }
}
