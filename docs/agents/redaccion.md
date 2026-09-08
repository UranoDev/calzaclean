# Redacción de la interfaz

Hay dos públicos y ninguno construyó el sistema: quien busca a quién dejarle sus
tenis, y el Dueño del taller editando desde el celular. Estas tres reglas son las que
más veces se rompen.

## 1. Sin aforismos

El texto de una pantalla explica **qué hace esa pantalla**. No argumenta, no convence
y no resume una filosofía.

Mal: «Un par sin foto de antes es una promesa, no una prueba.»
Bien: «Sube la foto de antes y la de después. Las dos aparecen en el sitio.»

El razonamiento detrás de una decisión tiene su lugar: el ADR que la tomó y el issue
que la construyó. En la interfaz estorba.

Vale igual para las frases con estructura de sentencia («X no es Y, es Z»), las
comparaciones con lo que se descartó y las moralejas.

## 2. No prometer lo que no está encendido

Una pantalla no describe en presente algo que depende de una configuración que quizá
nadie hizo.

Mal: «Recogemos tus tenis en tu casa» —cuando no hay ninguna zona de recolección
cargada.
Bien: la sección de recolección no se muestra si no hay zonas, y el Panel dice qué
falta para que aparezca.

Lo mismo con el **Aviso**: sin texto no hay franja. El sitio se ve bien sin ninguno,
que es como va a estar la mayor parte del año.

## 3. Se relee antes de comitear

Ninguna prueba lee la prosa. Una frase a la que le falta una palabra pasa todos los
tests y llega al cliente.

Dos cosas que hay que releer siempre porque son dinero: los **precios** —los Extras
se escriben con `+` por delante, siempre— y el **tiempo de entrega**, que mientras no
lo confirme el Dueño no se promete en presente.
