# CONTEXT — CalzaClean (CALZ)

Sitio web de **CalzaClean**, un taller de limpieza y restauración de tenis a mano en
San Juan del Río, Querétaro. Una sola persona atiende el taller y es la única que
edita el sitio.

Este archivo es el **glosario del dominio**: la fuente de la terminología para el
código, la base de datos, la interfaz y los textos. Si un término no está aquí, no
se inventa: se agrega aquí primero.

---

## Qué es el sitio

Una página pública que **termina en WhatsApp**. No cobra, no agenda y no cotiza: su
trabajo es enseñar resultados, decir cuánto cuesta y abrir la conversación. Detrás
tiene un **Panel** de cuatro pantallas para que la Dueña mantenga al día lo que
cambia seguido.

Lo que se decidió a propósito: **editable es lo que cambia seguido**. Los textos que
no cambian —cómo funciona, materiales, la portada— viven en Blade y se cambian por
commit. Abrirlos a edición es cómo se descuadra un sitio.

---

## Glosario

**Servicio** — una línea del catálogo con precio: limpieza básica ($120), limpieza
especializada ($170), limpieza infantil ($100), botas ($150), bolsas ($185),
mochilas ($200). Tiene nombre, a qué materiales aplica, precio y si está activo.

**Extra** — un Servicio que **se suma al precio base y nunca se vende solo**:
blanqueamiento de suelas (+$50) y entrega express (+$100, menos de 24 horas). Es un
Servicio con la bandera `es_extra` levantada, no otra entidad. En pantalla siempre
se escribe con el signo de más por delante.

**Material** — gamuza, ante, piel, cuero, lona, sintético. Es lo que determina qué
Servicio aplica a un par. El diferenciador comercial del taller es que cada material
lleva su técnica y su producto.

**Trabajo** — un par ya limpiado que se publica en la galería, con su foto de
**antes** y su foto de **después**, su Material y el Servicio que se le aplicó. Es la
pieza de contenido que la Dueña sube cada semana y la sección que más vende.

**Aviso** — una franja temporal arriba del sitio (vacaciones, cambio de horario,
promoción). Se enciende y se apaga desde el Panel. **Si no tiene texto, no se
muestra**: nunca hay una franja vacía.

**Negocio** — los datos únicos del taller: WhatsApp, horarios, dirección, colonias de
recolección, redes. Es un **singleton**, un solo renglón, no una tabla con muchos.

**Colonia de recolección** — zona donde se recoge y se entrega a domicilio.

**Pregunta** — una entrada de la sección de preguntas frecuentes: pregunta y
respuesta, en texto plano.

**Testimonio** — la reseña de un Cliente publicada en el sitio: nombre, texto y,
cuando existe, el Trabajo al que corresponde.

**Cliente** — quien lleva sus tenis. **No tiene cuenta.** El sitio no tiene login
para clientes ni los guarda en base de datos.

**Dueña** — la única persona con acceso al Panel. No hay registro público: su usuario
se crea con un comando de artisan.

**Panel** — la parte privada, en `/panel`. Cuatro pantallas: Trabajos, Precios,
Negocio, y Preguntas y testimonios.

**Sitio** — la parte pública. Una sola página con anclas, más `/precios` y
`/cuidado-de-tenis` como páginas propias.

---

## Cinco trampas concretas

1. **Un Extra no es un Servicio suelto.** Express y blanqueamiento no se venden solos
   y no se listan mezclados con el catálogo: van agrupados al final y con `+`. Una
   limpieza básica con entrega el mismo día son $220, no $100.
2. **Trabajo no es Servicio.** Un Trabajo es contenido con fotos; un Servicio es
   catálogo con precio. Se parecen porque un Trabajo menciona un Servicio, pero
   borrar un Servicio no puede borrar Trabajos.
3. **El sitio no cobra ni agenda.** No hay carrito, no hay calendario, no hay estado
   de pedido. Todo camino termina en un enlace de WhatsApp con mensaje precargado.
4. **La Dueña no es un usuario genérico.** No hay pantalla de registro. Si el starter
   kit la trae, se quita.
5. **El Aviso es temporal por definición.** El sitio tiene que verse bien sin ninguno,
   que es como va a estar la mayor parte del año.

---

## Dirección visual

**Paleta A, «Taller»** — extiende el logo sin rediseñarlo. Los tokens viven en
`resources/css/app.css`.

| Token | Hex | Uso |
| --- | --- | --- |
| Azul profundo | `#1F4763` | Texto, estructura, encabezados |
| Azul CalzaClean | `#24506E` | Segundo azul del logo, superficies oscuras |
| Azul claro | `#74AFDB` | **Solo relleno, borde, icono y fondo de sección** |
| Blanco humo | `#F7F9FB` | Superficie |
| Gris pizarra | `#5A6B79` | Texto secundario |
| Verde acción | `#2E9E76` | **Exclusivo del botón de WhatsApp** |

Proporción 60 / 30 / 10: blanco, azul profundo, azul claro.

**La trampa que se pisa sola:** `#74AFDB` no alcanza contraste sobre fondo claro. No
se usa para texto corrido ni para el texto de un botón, nunca. El texto lleva azul
profundo.

El eslogan del logo, **«Revive tus tenis, revive tu juego»**, es firma de marca: va
en el pie. La línea comercial del encabezado es **«Cada material, su técnica.»**

---

## Lo que sigue abierto

Si una tarea depende de algo de esta lista, marca el issue `ready-for-human` y
explícalo en el comentario en vez de inventar el dato.

- **Dominio definitivo.** Todavía no se compra ninguno.
- **Recolección a domicilio**: si se ofrece, con qué costo por zona y qué mínimo de
  pares.
- **Tiempo de entrega estándar.** El sitio dice 72 horas como supuesto; falta que la
  Dueña lo confirme antes de publicarlo como promesa.
- **Paquetes**: tres pares con descuento y plan mensual están propuestos, sin precio.
- **Correo del negocio** para notificaciones y para el alta de la Dueña.
- **Hosting.** Se asume Plesk con MySQL, como los otros proyectos, sin confirmar.
