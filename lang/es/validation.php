<?php

/*
 * Solo las claves que este proyecto usa de verdad. Las de contraseña están
 * aquí a propósito: escritas a mano en cada pantalla se desfasaban de la regla
 * —el mensaje decía ocho cuando producción ya pedía doce— y quien intentaba
 * crear una cuenta no entendía por qué la rechazaban. Con `:min` el número lo
 * pone la regla, no quien redacta.
 */

return [
    'required' => 'Este campo no puede quedar vacío.',
    'string' => 'Este campo tiene que ser texto.',
    'email' => 'Esto no tiene forma de correo.',
    'url' => 'Esto no tiene forma de dirección web.',
    'boolean' => 'Este campo solo acepta sí o no.',
    'integer' => 'Este campo tiene que ser un número entero.',
    'numeric' => 'Este campo tiene que ser un número.',
    'confirmed' => 'Las dos veces que lo escribiste no coinciden.',
    'unique' => 'Este valor ya está registrado.',
    'in' => 'El valor elegido no es válido.',
    'exists' => 'El valor elegido no existe.',

    'min' => [
        'string' => 'Necesita al menos :min caracteres.',
        'numeric' => 'No puede ser menor que :min.',
        'array' => 'Necesita al menos :min elementos.',
        'file' => 'El archivo no puede pesar menos de :min kilobytes.',
    ],

    'max' => [
        'string' => 'No puede pasar de :max caracteres.',
        'numeric' => 'No puede ser mayor que :max.',
        'array' => 'No puede tener más de :max elementos.',
        'file' => 'El archivo no puede pasar de :max kilobytes.',
    ],

    'password' => [
        'letters' => 'La contraseña necesita al menos una letra.',
        'mixed' => 'La contraseña necesita al menos una mayúscula y una minúscula.',
        'numbers' => 'La contraseña necesita al menos un número.',
        'symbols' => 'La contraseña necesita al menos un símbolo.',
        'uncompromised' => 'Esa contraseña apareció en una filtración de datos. Elige otra.',
    ],

    'image' => 'El archivo tiene que ser una imagen.',
    'mimes' => 'El archivo tiene que ser de tipo: :values.',
    'mimetypes' => 'El archivo tiene que ser de tipo: :values.',

    // La contraseña se nombra a sí misma: sus errores salen en la consola y en
    // el correo de restablecimiento, donde no hay una etiqueta al lado que
    // diga de qué campo se habla.
    'custom' => [
        'password' => [
            'min' => [
                'string' => 'La contraseña necesita al menos :min caracteres.',
            ],
            'required' => 'La contraseña no puede quedar vacía.',
            'confirmed' => 'Las dos contraseñas no coinciden.',
        ],
    ],

    'attributes' => [
        'name' => 'nombre',
        'email' => 'correo',
        'password' => 'contraseña',
    ],
];
