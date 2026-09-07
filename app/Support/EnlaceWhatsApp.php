<?php

namespace App\Support;

use App\Models\Negocio;
use App\Models\Pregunta;
use App\Models\Trabajo;
use InvalidArgumentException;

/**
 * El único lugar donde se arma un enlace de `wa.me`. Ninguna vista concatena la
 * dirección a mano: el Sitio pide el enlace acá y el número sale siempre del
 * Negocio, así que cambiarlo en el Panel cambia todas las salidas.
 *
 * Acá viven también los mensajes precargados, uno por punto de salida: quien
 * llega a la conversación desde la galería no escribe lo mismo que quien llega
 * desde la lista de precios.
 */
final class EnlaceWhatsApp
{
    /**
     * El mensaje precargado de cada punto de salida del Sitio.
     *
     * @var array<string, string>
     */
    private const MENSAJES = [
        'contacto' => 'Hola, quiero información sobre la limpieza de mis tenis.',
        'portada' => 'Hola, quiero cotizar la limpieza de mis tenis.',
        'precios' => 'Hola, tengo una duda sobre los precios.',
        'foto' => 'Hola, les mando una foto de mis tenis para saber qué servicio me toca.',
        'recoleccion' => 'Hola, quiero saber si pasan a recoger mis tenis en mi zona.',
    ];

    /**
     * Deja el número en puros dígitos, con lada de país y sin el 1 que México
     * pedía para celulares: es la forma que acepta `wa.me`. Sin dígitos
     * devuelve `null`.
     */
    public static function normalizarNumero(?string $valor): ?string
    {
        $digitos = (string) preg_replace('/\D/', '', (string) $valor);

        // Prefijo internacional escrito como 00 en vez de +.
        if (str_starts_with($digitos, '00')) {
            $digitos = substr($digitos, 2);
        }

        // El 1 de México va después del 52 y wa.me no lo usa.
        if (strlen($digitos) === 13 && str_starts_with($digitos, '521')) {
            $digitos = '52'.substr($digitos, 3);
        }

        // Diez dígitos son un número local: le falta la lada de país.
        if (strlen($digitos) === 10) {
            $digitos = '52'.$digitos;
        }

        return $digitos === '' ? null : $digitos;
    }

    /**
     * El enlace a un número suelto, escrito como venga. Lo usa la vista previa
     * del Panel, que arma el enlace del número que se está tecleando y todavía
     * no se guarda.
     */
    public static function con(?string $numero, ?string $mensaje = null): ?string
    {
        $numero = self::normalizarNumero($numero);

        if ($numero === null) {
            return null;
        }

        return 'https://wa.me/'.$numero
            .(filled($mensaje) ? '?text='.rawurlencode($mensaje) : '');
    }

    /**
     * El enlace de un punto de salida del Sitio, con su mensaje precargado.
     * Sin número cargado en el Negocio no hay conversación a dónde mandar a
     * nadie y devuelve `null`.
     */
    public static function desde(string $origen): ?string
    {
        return self::con(Negocio::actual()->whatsapp, self::mensajeDesde($origen));
    }

    /**
     * El enlace de un punto de salida que habla de algo puntual: el Trabajo que
     * se está mirando en la galería o la Pregunta que quedó sin resolver.
     */
    public static function sobre(Trabajo|Pregunta $asunto): ?string
    {
        return self::con(Negocio::actual()->whatsapp, self::mensajeSobre($asunto));
    }

    /**
     * El texto precargado de un punto de salida.
     *
     * @throws InvalidArgumentException si el origen no está en la lista
     */
    public static function mensajeDesde(string $origen): string
    {
        if (! array_key_exists($origen, self::MENSAJES)) {
            throw new InvalidArgumentException("No hay mensaje de WhatsApp para el origen [{$origen}].");
        }

        return self::MENSAJES[$origen];
    }

    /**
     * El texto precargado de un Trabajo o de una Pregunta. El del Trabajo
     * menciona el Material, que es lo que define qué Servicio le toca al par;
     * el de la Pregunta lleva la pregunta tal como está publicada.
     */
    public static function mensajeSobre(Trabajo|Pregunta $asunto): string
    {
        return match (true) {
            $asunto instanceof Trabajo => 'Hola, vi el par de '
                .mb_strtolower($asunto->material->etiqueta())
                .' en la galería y quiero cotizar la limpieza de los míos.',
            $asunto instanceof Pregunta => 'Hola, tengo una duda sobre: '.$asunto->pregunta,
        };
    }
}
