@props([
    'enlace',
])

{{-- Azul profundo con texto blanco: el azul claro de la paleta no alcanza
     contraste para el texto de un botón. --}}
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 20px;">
    <tr>
        <td style="background-color:#16334A; border-radius:8px;">
            <a href="{{ $enlace }}" style="display:inline-block; padding:12px 22px; font-family:Arial, Helvetica, sans-serif; font-size:16px; font-weight:bold; color:#FFFFFF; text-decoration:none;">
                {{ $slot }}
            </a>
        </td>
    </tr>
</table>
