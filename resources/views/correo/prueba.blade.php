<x-correo.mensaje titulo="Prueba de envío de CalzaClean">
    <p style="margin:0 0 16px;">
        Este mensaje salió de <strong>{{ config('app.name') }}</strong> con el comando
        <code>calzaclean:probar-correo</code>. Si llegó, el envío está bien configurado
        y la recuperación de contraseña del Panel puede salir por el mismo camino.
    </p>

    <p style="margin:0 0 16px;">
        Remitente: {{ $remitente }}<br>
        Envío por: {{ $transporte }}
    </p>
</x-correo.mensaje>
