# Skills disponibles

Estas skills vienen de plugins instalados **a nivel usuario**, no del repo: están en
cualquier sesión de esta máquina sin instalar nada. Se invocan por nombre
(`/<skill>` o la tool `Skill`).

Este archivo **no lo genera `claude-tools`**. Es del proyecto, y `bootstrap.ps1` no lo
toca al reinstalar herramientas.

Abajo solo están las que aplican a CalzaClean, con el gancho concreto de este
dominio. Que exista una skill no obliga a usarla: son para cuando el trabajo cae en
su terreno, no un checklist a recorrer.

## Dominio y diseño de código

| Skill | Cuándo |
| --- | --- |
| `mattpocock-skills:domain-modeling` | Cuando haya que afinar `CONTEXT.md`, fijar un término nuevo o registrar un ADR. Es la dueña del lenguaje ubicuo: si una palabra del glosario se empieza a usar con dos sentidos, se arregla aquí y no en el código. |
| `mattpocock-skills:codebase-design` | Al decidir dónde va un seam o cómo se corta un módulo. El caso vivo es la resolución de un Toque a su Destino: qué sabe el Tag, qué sabe el servidor, y por qué cambiar un Destino no debe implicar reprogramar el chip. |
| `mattpocock-skills:grilling` | Antes de comprometerse con un plan grande. Las tres decisiones abiertas de `CONTEXT.md` —*review gating*, qué es un «producto», y si se atribuyen Reseñas o solo Toques— son exactamente el material que conviene estresar a preguntas antes de escribir código. |

## Producto y UX

El terreno de estas es **la pantalla que ve el Cliente después del Toque**. Es la
superficie más delicada del producto: dura segundos, ocurre de pie en un mostrador, en
un teléfono ajeno que nadie eligió, y cualquier fricción ahí borra la Reseña.

| Skill | Cuándo |
| --- | --- |
| `design:design-critique` | Revisar una pantalla ya hecha. La pregunta que importa no es si se ve bien, sino si el Cliente entiende en un vistazo qué va a pasar si sigue. |
| `design:ux-copy` | Redactar lo poco que se le dice al Cliente. Debe usar los términos de `CONTEXT.md` y **nunca** condicionar el paso a Google a cómo le fue —eso es *review gating*, y está prohibido. |
| `design:accessibility-review` | Auditar contra WCAG 2.1 AA. No es un trámite aquí: el Cliente puede ser una persona mayor, con el teléfono a media luz y una mano ocupada. Contraste y tamaño de área táctil son requisitos, no pulido. |
| `design:user-research` | Antes de dar por buena una hipótesis sobre por qué el Cliente no reseña. Toda la premisa del producto es una: que el costo, y no la desidia, es lo que frena. |
| `design:research-synthesis` | Convertir lo que salga de esa investigación —o de hablar con los Negocios— en algo accionable. |

## Sistema visual

| Skill | Cuándo |
| --- | --- |
| `frontend-design:frontend-design` | Dirección visual al construir UI nueva, para que no salga con cara de plantilla. Aplica tanto a la pantalla del Cliente como al panel donde el Negocio administra sus Tarjetas. |
| `design:design-system` | Cuando aparezcan valores hardcodeados o nombres inconsistentes entre componentes. |
| `design:design-handoff` | Al pasar un diseño a implementación: tokens, estados, breakpoints y casos borde. |
| `design` | Canvas de artboards para mockups —la pantalla del Cliente, el panel del Negocio— cuando convenga verlo antes de escribirlo. También sirve para el arte de la Tarjeta y la Etiqueta. |

## Andamiaje del proyecto

`youtrack-new-project` ya corrió sobre este repo: creó el proyecto `CALZ`, sus campos
`Triage`/`Cost` y `.youtrack-project.ps1`. No hay que volver a correrla salvo que se
rehaga el proyecto en el tracker.
