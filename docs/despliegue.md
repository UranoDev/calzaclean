# Despliegue en Plesk

Sitio: `calzaclean.com` · Servidor: Plesk con MySQL · Repo:
`https://github.com/UranoDev/calzaclean.git`

Los datos de negocio ya decididos están en `CONTEXT.md` § Decisiones cerradas. Este
documento es solo el procedimiento.

---

## Antes de empezar

- **PHP 8.3 o superior** con las extensiones habituales de Laravel más **GD**, que es
  la que procesa las fotos de los Trabajos. Sin GD, subir un par falla.
- **MySQL** 8 o MariaDB 10.6+.
- **Composer** en el servidor.
- **Node 22**, la versión que fija `.node-version` en la raíz del repo. Si el servidor
  usa **nodenv** —Plesk suele hacerlo—, `npm` responde `command not found` mientras no
  haya una versión elegida para la carpeta. Se arregla una sola vez:

  ```bash
  cd ~/httpdocs && nodenv local 22
  ```

  Elegir la 22 y no la 25: la 22 es LTS y es la misma línea que se usa en desarrollo.
  Si no hay Node en el servidor, está la alternativa de la sección *Compilar los
  assets*.
- El DNS de `calzaclean.com` ya apunta a `74.208.127.180`. **Confirma que esa es la IP
  del servidor de Plesk donde va a vivir el sitio** antes de seguir: si el hosting
  está en otro lado, primero se corrige el registro A.

---

## 1. El dominio en Plesk

1. Crear el dominio `calzaclean.com`.
2. **La raíz del documento tiene que ser `httpdocs/public`**, no `httpdocs`. Es el
   error más común y el más caro: apuntarla a la raíz del proyecto deja `.env`, el
   código y las dependencias accesibles desde el navegador.
3. Elegir la versión de PHP y comprobar que GD esté activa.
4. Emitir el certificado Let's Encrypt para `calzaclean.com` y `www`, y **activar la
   redirección permanente a HTTPS**.

## 2. La base de datos

En *Bases de datos*, crear una base y un usuario propio con acceso solo a ella.
Guardar nombre, usuario y contraseña para el `.env`. No reutilizar el usuario de
administración de MySQL.

## 3. El código

Se trae con la extensión **Git** de Plesk. Así, actualizar el sitio es traer los
cambios, no volver a subir archivos por FTP.

### 3.1 Dar de alta el repositorio

En *Sitios web y dominios → calzaclean.com → **Git** → Añadir repositorio*:

| Campo | Valor |
| --- | --- |
| Tipo | **Repositorio Git remoto** |
| URL | `https://github.com/UranoDev/calzaclean.git` |
| Rama | `master` |
| Ruta de despliegue | `httpdocs` |

Dos cosas de esa tabla:

- **El repositorio es público**, así que la URL `https://` basta y no hacen falta
  llaves. Si algún día se vuelve privado, Plesk muestra una llave pública SSH que hay
  que registrar en GitHub como *deploy key* con permiso de solo lectura, y la URL pasa
  a la forma `git@github.com:UranoDev/calzaclean.git`.
- **La ruta de despliegue es `httpdocs`, no `httpdocs/public`.** El proyecto entero
  vive en `httpdocs`, y la raíz del documento —que se configuró en el paso 1— apunta
  a la subcarpeta `public`. Confundir estas dos rutas es lo que expone el `.env` a
  internet.

### 3.1-bis La alternativa: git por SSH, sin la extensión

La extensión no es obligatoria. Si prefieres la terminal, se clona a mano y se usa
`git pull` como en cualquier servidor.

Primero hay que habilitar el acceso: *Sitios web y dominios → Acceso al hosting web →
**Acceso al servidor por SSH*** y elegir `/bin/bash`. Plesk lo deja en `/bin/false` por
omisión, así que sin este paso la conexión se cierra al conectar.

`httpdocs` no está vacío —Plesk deja su `index.html` de bienvenida—, así que no se puede
clonar encima. Se inicializa y se trae:

```bash
ssh usuario@calzaclean.com
cd ~/httpdocs
rm -f index.html favicon.ico
git init
git remote add origin https://github.com/UranoDev/calzaclean.git
git fetch origin master
git checkout -f -B master origin/master
```

Para actualizar después, `git pull` y los comandos de la sección 3.3.

**Una cosa a favor de este camino que conviene entender:** el directorio `.git` queda en
`httpdocs`, un nivel arriba de la raíz del documento, que es `httpdocs/public`. Desde
internet no se alcanza. Pero si alguien mueve la raíz del documento a `httpdocs` —el
error del paso 1— `.git` se vuelve descargable, y con él **el código completo y todo el
historial**. Es la segunda razón para no equivocarse en esa ruta.

**Cuál elegir.** La extensión conviene si quieres un botón de desplegar y que las
acciones posteriores corran solas cada vez. Git por SSH conviene si prefieres el control
directo, pero entonces los comandos de la sección 3.3 hay que acordarse de correrlos —
lo cual se resuelve dejando un `deploy.sh` en el servidor:

```bash
#!/usr/bin/env bash
set -e
cd ~/httpdocs
git pull
/opt/plesk/php/8.3/bin/php ~/bin/composer install --no-dev --optimize-autoloader
npm ci && npm run build
/opt/plesk/php/8.3/bin/php artisan migrate --force
/opt/plesk/php/8.3/bin/php artisan config:cache
/opt/plesk/php/8.3/bin/php artisan route:cache
/opt/plesk/php/8.3/bin/php artisan view:cache
echo "Listo."
```

El `set -e` es lo que importa: si `migrate` falla, el script se detiene ahí en vez de
seguir cacheando configuración sobre una base a medio migrar.

### 3.2 Modo de despliegue: manual

Plesk ofrece **automático**, que despliega en cada push a GitHub, y **manual**, con un
botón *Desplegar*. **Elige manual.**

Un push que trae una migración no puede aplicarse solo mientras alguien usa el panel: el
código nuevo quedaría corriendo contra un esquema viejo durante los segundos que tarda
`migrate`. Con despliegue manual, tú eliges el momento.

### 3.3 Acciones adicionales de despliegue

En la misma pantalla, Plesk deja definir comandos que corren después de traer los
archivos. Ahí va todo lo que de otro modo habría que teclear a mano:

```bash
/opt/plesk/php/8.3/bin/php ~/bin/composer install --no-dev --optimize-autoloader
npm ci
npm run build
/opt/plesk/php/8.3/bin/php artisan migrate --force
/opt/plesk/php/8.3/bin/php artisan config:cache
/opt/plesk/php/8.3/bin/php artisan route:cache
/opt/plesk/php/8.3/bin/php artisan view:cache
```

**La ruta completa de PHP no es un capricho.** El `php` que encuentra la shell suele ser
la versión del sistema, casi siempre más vieja que la del dominio, y `composer install`
resolvería dependencias para la versión equivocada. Ajusta `8.3` a la versión elegida en
el paso 1.

#### Dónde está Composer

**No hay una ruta fija.** Depende de la versión de Plesk y de si la extensión *PHP
Composer* está instalada. Búscalo antes de escribir las acciones:

```bash
which composer
find /usr/lib/plesk-9.0 /usr/local/psa /opt/plesk -maxdepth 4 -name "composer*" 2>/dev/null | head
```

Si no aparece, conviene **instalarlo para el usuario** y dejar de depender de Plesk:

```bash
cd ~
/opt/plesk/php/8.3/bin/php -r "copy('https://getcomposer.org/installer','composer-setup.php');"
/opt/plesk/php/8.3/bin/php -r "if (hash_file('sha384','composer-setup.php') === trim(file_get_contents('https://composer.github.io/installer.sig'))) { echo 'instalador verificado'.PHP_EOL; } else { unlink('composer-setup.php'); exit('ALTO: no coincide con la firma'.PHP_EOL); }"
mkdir -p ~/bin
/opt/plesk/php/8.3/bin/php composer-setup.php --install-dir=$HOME/bin --filename=composer
rm composer-setup.php
```

La segunda línea **no se salta**: compara el instalador contra la firma oficial antes de
ejecutarlo. Se está por correr un archivo descargado con los permisos del usuario del
hosting; si no coincide, se borra solo.

Estas acciones corren en **cada** despliegue. Lo que no va aquí: `db:seed`,
`storage:link` y la creación de cuentas, que son de la primera vez y están en los pasos
6, 7 y 8.

### 3.4 Lo que el despliegue no toca

`.env`, `storage/app/public` —donde viven las fotos de los Trabajos— y la base de
datos. Están fuera del repo, así que sobreviven a cada despliegue. Es la razón por la
que el contenido que cargue la Dueña no se pierde al actualizar el sitio.

### 3.5 Si no hay más remedio que FTP

**Sube un `.tar` y no un `.zip`.** Varias vistas de Livewire llevan un `⚡` en el nombre
del archivo, y algunos clientes de FTP y descompresores de Windows lo destruyen; el
resultado son pantallas del panel que no cargan, con un error que no menciona el
nombre del archivo. Con git no pasa.

## 4. El archivo `.env`

**No está en el repo y nunca debe estarlo.** Se crea a mano en el servidor:

```bash
APP_NAME=CalzaClean
APP_ENV=production
APP_DEBUG=false
APP_URL=https://calzaclean.com
APP_TIMEZONE=America/Mexico_City

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=<la base creada en el paso 2>
DB_USERNAME=<el usuario creado en el paso 2>
DB_PASSWORD=<la contraseña>

MAIL_MAILER=sendmail
MAIL_FROM_ADDRESS="eli@calzaclean.com"
MAIL_FROM_NAME="CalzaClean"
```

Tres cosas que se pasan por alto:

- **`APP_DEBUG=false`.** Con `true`, cualquier error muestra el contenido del `.env`
  —incluida la contraseña de la base— a quien esté mirando.
- **`MAIL_MAILER=sendmail`**, no `smtp`. Laravel corre en el mismo servidor que
  Postfix, así que le entrega el mensaje directo: sin TLS, sin contraseñas y sin el
  problema del certificado del correo. La configuración de Mailpit que hay en local
  **no se copia**.
- **`APP_TIMEZONE`.** Sin ella, el Panel muestra el día siguiente a partir de las seis
  de la tarde.

Después:

```bash
php artisan key:generate
```

## 5. Compilar los assets

`/public/build` está en `.gitignore`, así que **no viaja con el repo**. Hay que
generarlo en el servidor:

```bash
npm ci
npm run build
```

Las dependencias de Linux ya están declaradas como opcionales en `package.json`, así
que `npm ci` las resuelve solo.

Si el servidor no tiene Node, la alternativa es compilar en la máquina de desarrollo y
subir `public/build` por FTP. Funciona, pero hay que acordarse de repetirlo en cada
despliegue que toque CSS o JavaScript. **Si el sitio se ve sin estilos, esto es lo que
falta.**

## 6. Base de datos y contenido

```bash
php artisan migrate --force
php artisan db:seed --force
```

El seeder carga los ocho Servicios con sus precios, las dos Zonas de recolección y la
ficha del Negocio. **Es idempotente**: correrlo dos veces no duplica nada. No crea
ninguna cuenta, a propósito.

## 7. Las cuentas del Panel

No hay registro público. Cada cuenta se crea con:

```bash
php artisan calzaclean:crear-cuenta
```

Sin opciones pregunta los datos por consola. Con `--nombre`, `--correo` y
`--contrasena` corre sin interacción. Para ver quién tiene acceso:

```bash
php artisan calzaclean:cuentas
```

Son dos: la Dueña y quien mantiene el sitio. **Las contraseñas temporales que se usaron
en desarrollo no se reutilizan aquí.**

## 8. El enlace de almacenamiento

```bash
php artisan storage:link
```

**Sin esto las fotos de los Trabajos no se ven.** El Panel las sube sin error y la
galería queda con imágenes rotas, que es un síntoma que despista.

## 9. Permisos

`storage/` y `bootstrap/cache/` tienen que ser escribibles por el usuario de PHP.
Plesk normalmente lo resuelve solo; si aparece un error de permisos al subir una foto o
al escribir el log, es esto.

## 10. Cachés

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Se puede cachear la configuración sin riesgo: no hay ninguna llamada a `env()` fuera de
`config/`, que es lo que rompe este paso en otros proyectos.

**Cada vez que cambie el `.env` hay que repetir `config:cache`**, o el cambio no surte
efecto.

---

## Comprobar que quedó bien

Recorrer esta lista en el sitio publicado, no en local:

- [ ] `https://calzaclean.com` responde y **redirige desde `http://`**.
- [ ] La página se ve con estilos. Si no, falta el paso 5.
- [ ] Los precios aparecen: ocho servicios, con los dos Extras al final y su `+`.
- [ ] El bloque de recolección muestra las dos zonas con su costo.
- [ ] Los enlaces de WhatsApp abren una conversación con el mensaje precargado.
- [ ] `/panel` pide contraseña, y las dos cuentas entran.
- [ ] Subir un Trabajo de prueba desde el Panel, **desde un celular**, y comprobar que
      las dos fotos se ven en la galería. Luego borrarlo.
- [ ] `php artisan calzaclean:probar-correo eli@calzaclean.com` llega.
- [ ] Pedir un restablecimiento de contraseña y comprobar que el correo llega.
- [ ] Provocar un error a propósito y confirmar que **no** se ve una traza de Laravel:
      eso significa que `APP_DEBUG` quedó en `true`.

---

## Actualizar el sitio después

```bash
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

El seeder **no** se vuelve a correr en cada actualización: los precios y la ficha del
Negocio ya viven en la base y se editan desde el Panel. Correrlo no rompe nada, pero
tampoco hace falta.

---

## Lo que queda fuera de este documento

- **El certificado del servicio de correo.** Plesk sirve su certificado autofirmado en
  los puertos 465 y 993. No afecta al sitio, que usa `sendmail`, pero le sale una
  advertencia de seguridad a quien configure el buzón en su teléfono. Se arregla
  asignando el Let's Encrypt del dominio al servicio de correo y reiniciando Postfix y
  Dovecot.
- **Colas y tareas programadas.** El proyecto no tiene ninguna, así que no hace falta
  ni un worker ni una entrada de cron.
- **El contenido real.** Las fotos de los Trabajos, las Preguntas y los Testimonios los
  carga la Dueña desde el Panel. El sitio publicado sin ellos oculta esas secciones en
  vez de mostrarlas vacías, así que no rompe nada — pero le falta justo lo que más
  vende.
