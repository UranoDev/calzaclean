@props([
    'titulo',
])

{{-- Envoltura de todo correo que manda el sitio. Los colores van en línea y con
     hex literal: ningún cliente de correo carga la hoja de estilos del sitio. --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo }}</title>
</head>
<body style="margin:0; padding:0; background-color:#F7F9FB;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F7F9FB; padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; background-color:#FFFFFF; border:1px solid #C5DFF1; border-radius:14px; overflow:hidden;">
                    <tr>
                        <td style="background-color:#214966; padding:20px 28px;">
                            <span style="font-family:Arial, Helvetica, sans-serif; font-size:18px; font-weight:bold; color:#FFFFFF;">
                                CalzaClean
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <h1 style="margin:0 0 20px; font-family:Arial, Helvetica, sans-serif; font-size:21px; line-height:1.3; color:#16334A;">
                                {{ $titulo }}
                            </h1>

                            <div style="font-family:Arial, Helvetica, sans-serif; font-size:16px; line-height:1.6; color:#16334A;">
                                {{ $slot }}
                            </div>
                        </td>
                    </tr>
                    @isset($respaldo)
                        <tr>
                            <td style="padding:0 28px 28px;">
                                <div style="border-top:1px solid #C5DFF1; padding-top:16px; font-family:Arial, Helvetica, sans-serif; font-size:13px; line-height:1.6; color:#5A6B79;">
                                    {{ $respaldo }}
                                </div>
                            </td>
                        </tr>
                    @endisset
                </table>

                <p style="max-width:560px; margin:16px auto 0; font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:1.6; color:#5A6B79;">
                    Revive tus tenis, revive tu juego.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
