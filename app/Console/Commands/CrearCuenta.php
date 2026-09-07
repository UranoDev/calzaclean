<?php

namespace App\Console\Commands;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * El Panel no tiene pantalla de registro: las cuentas se crean desde aquí, con
 * las mismas reglas que valida el perfil. Todas las cuentas tienen el mismo
 * acceso.
 */
class CrearCuenta extends Command
{
    use PasswordValidationRules, ProfileValidationRules;

    protected $signature = 'calzaclean:crear-cuenta
        {--nombre= : Nombre de quien va a entrar al Panel}
        {--correo= : Correo con el que entra}
        {--contrasena= : Contraseña, de ocho caracteres para arriba}';

    protected $description = 'Crea una cuenta con acceso al Panel';

    public function handle(): int
    {
        $nombre = $this->opcion('nombre') ?? (string) $this->ask('Nombre');
        $correo = $this->opcion('correo') ?? (string) $this->ask('Correo');

        $contrasena = $this->opcion('contrasena');
        $confirmacion = $contrasena;

        if ($contrasena === null) {
            $contrasena = (string) $this->secret('Contraseña');
            $confirmacion = (string) $this->secret('Repite la contraseña');
        }

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
            'email.unique' => "Ya hay una cuenta con el correo {$correo}.",
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

        $cuenta = User::query()->create([
            'name' => $nombre,
            'email' => $correo,
            'password' => $contrasena,
        ]);

        $cuenta->forceFill(['email_verified_at' => now()])->save();

        $this->components->info("Cuenta creada para {$cuenta->email}. El Panel está en /panel.");

        return self::SUCCESS;
    }

    /**
     * El dato que llegó por opción, o null si no se pasó la opción. Una opción
     * vacía se toma como dada para que la validación la rechace en vez de
     * quedarse esperando una respuesta que nadie va a escribir.
     */
    private function opcion(string $nombre): ?string
    {
        $valor = $this->option($nombre);

        return is_string($valor) ? $valor : null;
    }
}
