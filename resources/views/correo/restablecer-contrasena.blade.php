<x-correo.mensaje titulo="Cambia la contraseña de tu cuenta del Panel">
    <p style="margin:0 0 16px;">Hola, {{ $nombre }}:</p>

    <p style="margin:0 0 16px;">
        Alguien pidió una contraseña nueva para tu cuenta del Panel de CalzaClean.
        Este botón la cambia:
    </p>

    <x-correo.boton :enlace="$enlace">Poner una contraseña nueva</x-correo.boton>

    <p style="margin:0 0 16px;">
        El enlace sirve por {{ $minutos }} minutos. Después hay que pedir otro desde
        «Olvidé mi contraseña».
    </p>

    <p style="margin:0 0 16px;">
        Si no lo pediste, no tienes que hacer nada: tu contraseña sigue siendo la de
        siempre.
    </p>

    <x-slot:respaldo>
        Si el botón no abre, copia esta dirección en tu navegador:<br>
        <a href="{{ $enlace }}" style="color:#214966; word-break:break-all;">{{ $enlace }}</a>
    </x-slot:respaldo>
</x-correo.mensaje>
