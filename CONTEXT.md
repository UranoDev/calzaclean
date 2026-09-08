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
blanqueamiento de suelas (+$50) y entrega express (+$100, en 24 horas). Es un
Servicio con la bandera `es_extra` levantada, no otra entidad. En pantalla siempre
se escribe con el signo de más por delante.

**Material** — gamuza, ante, piel, cuero, lona, sintético. Es lo que determina qué
Servicio aplica a un par. El diferenciador comercial del taller es que cada material
lleva su técnica y su producto.

**Trabajo** — un par ya limpiado que se publica en la galería, con su foto de
**antes** y su foto de **después**, su Material y el Servicio que se le aplicó. Es la
pieza de contenido que la Dueña sube cada semana y la sección que más vende. La lista
lleva un orden que la Dueña acomoda con las flechas del Panel, y **el primero de esa
lista que esté publicado es el que enseña la portada**: uno solo, y elegido a mano.
Sin ninguno publicado la portada usa su imagen de respaldo. Un Trabajo nuevo entra en
primera posición, así que por omisión la portada muestra el último que se subió.

**Aviso** — una franja temporal arriba del sitio (vacaciones, cambio de horario,
promoción). Se enciende y se apaga desde el Panel. **Si no tiene texto, no se
muestra**: nunca hay una franja vacía.

**Negocio** — los datos únicos del taller: WhatsApp, horarios, dirección, zonas de
recolección y **redes**. Es un **singleton**, un solo renglón, no una tabla con
muchos.

**Redes** — Instagram, Facebook, X y TikTok. Son cuatro campos del Negocio, cada uno
con la URL completa. **Una red sin URL no se dibuja**: el pie del Sitio solo muestra
las que están cargadas, nunca un icono muerto. Hoy hay Instagram y Facebook; X y
TikTok todavía no existen y así se quedan hasta que la Dueña los cargue.

**Zona de recolección** — un área donde se recoge y se entrega a domicilio, con su
costo. Hoy hay dos: **Centro**, sin costo, y **Fuera del centro**, **+$50**. Una zona
sin costo se escribe «sin costo», nunca «$0». El costo se suma al precio de la
limpieza, así que se escribe con el mismo signo de más que un Extra. **No hay mínimo
de pares.** El Sitio solo muestra el bloque cuando hay al menos una zona activa. La
entidad se llamó Colonia de recolección hasta CALZ-20, que le agregó el costo y la
renombró.

**Pregunta** — una entrada de la sección de preguntas frecuentes: pregunta y
respuesta, en texto plano.

**Testimonio** — la reseña de un Cliente publicada en el sitio: nombre, texto y,
cuando existe, el Trabajo al que corresponde.

**Tiempo de entrega** — **72 horas**. Confirmado por la Dueña el 6 de septiembre de
2026: se puede escribir como promesa en presente. La **entrega express** es el Extra
que lo baja a 24 horas por +$100.

**Cliente** — quien lleva sus tenis. **No tiene cuenta.** El sitio no tiene login
para clientes ni los guarda en base de datos.

**Dueña** — quien atiende el taller y edita el contenido. Es el público para el que
se diseña el Panel.

**Cuenta del Panel** — un acceso al Panel. **No hay registro público**: las cuentas se
crean con un comando de artisan. Hoy hay dos, la Dueña y quien mantiene el sitio, y
**las dos tienen el mismo acceso**: el Panel no distingue permisos ni roles. Si algún
día hiciera falta distinguirlos, eso es una decisión nueva, no algo que se cuele en
una tarea.

**Panel** — la parte privada, en `/panel`. Cuatro entradas: **Trabajos**, **Precios**,
**Ajustes** y **Preguntas y testimonios**. Ajustes se abre en tres pantallas:
**Contacto** (WhatsApp y Redes), **Negocio** (horarios, dirección y zonas de
recolección) y **Aviso**.

**Sitio** — la parte pública. Una sola página con anclas, más `/precios`,
`/resultados` y `/cuidado-de-tenis` como páginas propias. `/resultados` es la
galería completa: la portada enseña cuatro pares y el botón «Ver más resultados»
va agregando de a cuatro, y sin JavaScript ese botón es el enlace a esa página.

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
| Azul profundo | `#16334A` | Texto y encabezados. Un paso más oscuro que el del logo |
| Azul CalzaClean | `#214966` | **El azul del logo, medido del archivo.** Superficies oscuras y pie |
| Azul claro | `#6FAFDE` | **El azul claro del logo, medido.** Solo relleno, borde, icono y fondo de sección |
| Blanco humo | `#F7F9FB` | Superficie |
| Gris pizarra | `#5A6B79` | Texto secundario |
| Verde acción | `#24805F` | **Exclusivo del botón de WhatsApp, en el Sitio** |

### Color semántico del Panel

Va aparte de la paleta de marca porque no acentúa: dice qué pasó. Los tres pasan 4.5:1
sobre blanco como texto, medido con `Tests\Support\Paleta`.

| Token | Hex | Uso |
| --- | --- | --- |
| Éxito | `#1C7A55` | Confirmaciones: «quedó guardado» |
| Alerta | `#9A5314` | Advertencias que no impiden seguir |
| Error | `#B3261E` | Validaciones, fallos y **acciones destructivas** |

**Los botones tienen tres pesos, y el peso es la advertencia.** El **primario** es el
único relleno sólido de la pantalla. El **secundario** lleva relleno tenue, no borde: un
borde claro sobre blanco no se lee como botón. El **destructivo** usa el rojo de error —
tenue en el botón que abre la confirmación, sólido en el que borra de verdad. Un `Borrar`
que se ve igual que un `Agregar` es un accidente esperando a ocurrir.

No se usan en el Sitio. Y **no se confunden con `verde-accion`**: ese sigue queriendo
decir «WhatsApp», y si además significara «guardado» dejaría de significar nada.

Los dos azules **no son aproximaciones**: salen de muestrear `public/img/logo-original.jpeg`
el 6 de septiembre de 2026. `#214966` da 9.5:1 contra blanco, así que sirve como color
de texto sin ayuda.

Proporción 60 / 30 / 10: blanco, azul profundo, azul claro.

**La trampa que se pisa sola:** `#6FAFDE` da 2.4:1 contra blanco, muy por debajo del
mínimo. No se usa para texto corrido ni para el texto de un botón, nunca, y en iconos
solo cuando son decorativos. El texto lleva azul profundo.

El verde era `#2E9E76` hasta CALZ-17. Con blanco encima daba 3.35:1 y el texto del
botón de WhatsApp no llegaba al mínimo, justo en el botón del que cuelga todo el
sitio. `#24805F` es el mismo verde un paso más oscuro —la mezcla en oklab que ya usaba
el estado de hover— y da 4.85:1. El anillo de foco sale de ese mismo verde, que sobre
blanco alcanza los 3:1 que pide un indicador; sobre el azul del pie no, así que ahí el
botón lleva `foco="claro"` y el anillo se dibuja blanco.

El eslogan del logo, **«Revive tus tenis, revive tu juego»**, es firma de marca: va
en el pie. La línea comercial del encabezado es **«Cada material, su técnica.»**

### El logo

`public/img/logo-original.jpeg` es el archivo que entregó la Dueña: el lockup completo
—logotipo, eslogan y los dos tenis— sobre el azul del logo. De ahí sale
`public/img/logo-calzaclean.png`, el logotipo recortado con fondo transparente, que es
el que usa `<x-logo-calzaclean>` en el encabezado y en el pie. El logotipo trae la
pastilla blanca, así que se lee tanto sobre blanco como sobre el azul del pie.

**No hay vectorial.** Mientras no aparezca el original en `.ai`, `.svg` o `.eps`, el
logotipo es un mapa de bits y no conviene usarlo a más de 360 px de ancho. Si llega el
vectorial, se reemplaza el PNG y el componente no cambia. El lockup completo sí sirve
tal cual para la imagen de vista previa al compartir, donde el fondo azul y el eslogan
juegan a favor.

**Los iconos y la vista previa se generan del logo, no se dibujan.** Dos scripts que se
corren a mano cuando cambia el logo, y cuyo resultado se comitea:

- `php scripts/generar-iconos.php` recorta la **C** de «Calza» del logotipo y la pone
  sobre el azul del logo. De ahí salen `public/favicon.ico` (16, 32 y 48 px),
  `public/favicon.svg` y `public/apple-touch-icon.png`. A 32 px el logotipo completo no
  se lee; una letra sí.
- `php scripts/generar-vista-previa.php` recorta el lockup a `public/img/vista-previa.png`,
  de 1200 × 630, que es lo que se ve al pegar el enlace del Sitio en una conversación.
  `/precios` tiene la suya, de `scripts/generar-vista-previa-precios.php`.

---

## Decisiones cerradas

- **Dominio: `calzaclean.com`.** Confirmado el 6 de septiembre de 2026. Es el
  canónico del Sitio y el que va en los datos estructurados y el sitemap. Vive en
  `APP_URL` y en ningún otro lado: `App\Support\EnlaceCanonico` es el único que arma
  direcciones absolutas, y `robots.txt` y `sitemap.xml` se sirven desde una ruta —no
  desde `public/`— para que salgan de ahí.
- **Sí hay recolección a domicilio.** Confirmado el 6 de septiembre de 2026, después
  de haberse dicho lo contrario ese mismo día: la entidad se queda y CALZ-19, que la
  iba a retirar, quedó en `wontfix`. **El costo depende de la zona**: Centro sin
  costo, Fuera del centro +$50, y **no hay mínimo de pares** — se recoge aunque sea
  uno solo. Lo construyó CALZ-20, que renombró la entidad a Zona de recolección.
- **Zona horaria: `America/Mexico_City`.** El taller está en un solo lugar y las fechas
  las lee la Dueña, no un sistema. Con la zona en UTC, el Panel mostraba el día
  siguiente a partir de las seis de la tarde.
- **El seeder no crea cuentas.** Las Cuentas del Panel se crean con
  `calzaclean:crear-cuenta`. Sembrar una con correo y contraseña conocidos deja una
  puerta abierta en cuanto alguien corre `db:seed` en el servidor.
- **Hosting: Plesk con MySQL.** Confirmado el 6 de septiembre de 2026.
- **Correo del negocio: `eli@calzaclean.com`.** Confirmado el 6 de septiembre de 2026.
  Es el remitente de todo lo que mande el sitio.
- **Correo saliente encendido.** Las credenciales SMTP del buzón
  `eli@calzaclean.com` se cargaron el 6 de septiembre de 2026 y viven solo en `.env`.
  Con eso la recuperación de contraseña del Panel quedó prendida (CALZ-21). El envío
  se comprueba con `php artisan calzaclean:probar-correo <destino>`.
- **No hay paquetes.** Los tres pares con descuento y el plan mensual quedaron
  propuestos y sin definir. El Sitio **no los menciona**, ni siquiera como «próximamente»:
  se agregan cuando existan, con su precio.

## Lo que sigue abierto

Si una tarea depende de algo de esta lista, marca el issue `ready-for-human` y
explícalo en el comentario en vez de inventar el dato.

Hoy no hay ningún dato pendiente.
