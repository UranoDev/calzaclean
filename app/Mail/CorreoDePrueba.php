<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * El mensaje que manda `calzaclean:probar-correo`. Sirve para ver si el buzón
 * del negocio está bien configurado sin tener que provocar un restablecimiento
 * de contraseña de verdad.
 */
class CorreoDePrueba extends Mailable
{
    use Queueable, SerializesModels;

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Prueba de envío de CalzaClean',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'correo.prueba',
            with: [
                'remitente' => (string) config('mail.from.address'),
                'transporte' => (string) config('mail.default'),
            ],
        );
    }
}
