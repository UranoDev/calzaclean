<?php

namespace App\Console\Commands;

use App\Mail\CorreoDePrueba;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Sin este comando, diagnosticar el envío contra el buzón de Plesk es a ciegas:
 * un restablecimiento que no llega no distingue entre credenciales malas,
 * puerto cerrado y correo que cayó en spam. Aquí el error del servidor se ve.
 */
class ProbarCorreo extends Command
{
    protected $signature = 'calzaclean:probar-correo
        {destino : Correo al que se manda el mensaje de prueba}';

    protected $description = 'Manda un correo de prueba para revisar la configuración de envío';

    public function handle(): int
    {
        $destino = (string) $this->argument('destino');

        $validador = Validator::make(['destino' => $destino], [
            'destino' => ['required', 'email'],
        ], [
            'destino.required' => 'Falta el correo al que se manda la prueba.',
            'destino.email' => "«{$destino}» no tiene forma de correo.",
        ]);

        if ($validador->fails()) {
            foreach ($validador->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $transporte = (string) config('mail.default');
        $remitente = (string) config('mail.from.address');

        $this->components->info("Mandando a {$destino} desde {$remitente} por {$transporte}.");

        try {
            Mail::to($destino)->send(new CorreoDePrueba);
        } catch (Throwable $falla) {
            $this->components->error('El correo no salió. Esto contestó el servidor:');
            $this->line('  '.$falla->getMessage());

            return self::FAILURE;
        }

        if ($transporte === 'log') {
            $this->components->warn('Con MAIL_MAILER=log el mensaje no sale a internet: quedó escrito en storage/logs. Carga las credenciales SMTP en .env para que salga de verdad.');

            return self::SUCCESS;
        }

        $this->components->info('El correo salió sin error. Revisa la bandeja de '.$destino.', y también la de spam.');

        return self::SUCCESS;
    }
}
