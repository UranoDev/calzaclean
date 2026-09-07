<?php

namespace App\Support;

/**
 * El único lugar donde se decide cómo se escribe un monto en pantalla. Lo usan
 * el accesor de precio del Servicio y el de costo de la Zona de recolección:
 * el signo de más de un Extra y el de una zona con costo salen de la misma
 * regla, no de dos.
 */
final class Precio
{
    /**
     * El monto como se lee en pantalla. `$seSuma` levantado escribe el signo de
     * más por delante, que es lo que distingue un monto que se agrega a un
     * precio base de uno que se cobra solo.
     */
    public static function formatear(int $monto, bool $seSuma = false): string
    {
        return ($seSuma ? '+' : '').'$'.number_format($monto);
    }
}
