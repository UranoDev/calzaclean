## Agent skills

### Contexto del dominio

`CONTEXT.md` es el glosario: **Servicio**, **Extra**, **Material**, **Trabajo**,
**Aviso**, **Negocio**, **Zona de recolección**, **Pregunta**, **Testimonio**,
**Cliente**, **Dueña**, **Panel** y **Sitio**. Se lee antes de tocar código — ahí
están las cinco trampas del dominio y la sección **Lo que sigue abierto**, que es lo
que no se inventa.

### Issue tracker

YouTrack, proyecto CALZ (`uranodev.youtrack.cloud`), vía las herramientas
`mcp__youtrack__*` — configuradas a scope de usuario, sin setup por repo. Ver
`docs/agents/issue-tracker.md`.

Dos convenciones fáciles de pasar por alto: las subtareas ordenadas necesitan enlaces
formales **`depends on`** (esta instancia no tiene `blocked by`), y todo issue cuelga
de una épica `Type: Epic` vía `parentIssue` / `subtask of`.

### Triage

Cinco valores canónicos en un campo `Triage` propio de CALZ; la categoría
(bug/enhancement) usa el campo nativo `Type`. `State` queda reservado al flujo de
ingeniería. Ver `docs/agents/triage-labels.md`.

### Dirección visual

**Paleta A, «Taller»** — tokens en `resources/css/app.css`, tabla completa en
`CONTEXT.md`. La regla que se rompe sola: el azul claro `#6FAFDE` **no** se usa para
texto sobre fondo claro, solo para relleno, borde, icono y fondo de sección. El verde
`#24805F` es exclusivo del botón de WhatsApp **en el Sitio**; el Panel tiene su propio
color semántico —éxito, alerta y error— documentado en `CONTEXT.md`.

### Redacción de la interfaz

El texto de una pantalla dice qué hace esa pantalla: **sin aforismos**, sin argumentar
la decisión de diseño, y **sin prometer en presente lo que depende de una
configuración que quizá nadie hizo**. Ninguna prueba lee la prosa, así que se relee
antes de comitear. Ver `docs/agents/redaccion.md`.

### Quién lee el Panel

Una sola persona, dueña de un taller, entrando casi siempre desde el celular con el
par recién terminado en la mano. El Panel se prueba angosto antes que ancho.
