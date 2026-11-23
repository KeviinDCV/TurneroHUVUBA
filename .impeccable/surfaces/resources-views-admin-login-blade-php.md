---
version: 1
slug: "resources-views-admin-login-blade-php"
primary_target: "resources/views/admin/login.blade.php"
related_targets: []
---

# Login

Mode: Operate. Asesores y administradores entran desde un PC del hospital, a veces compartido, casi siempre al empezar la jornada. Tarea: entrar al turnero de su unidad sin dudas; si algo falla, saber qué hacer y a quién acudir. Igual en los dos turneros (Fisiatría y UBA); el nombre de la unidad sale de `PANEL_UNIDAD_NOMBRE`, el contacto de soporte de `PANEL_SOPORTE_CONTACTO`.

Estados: normal, entrando, contraseña incorrecta, cuenta desactivada, demasiados intentos, Bloq Mayús, página caducada (aviso informativo), navegador sin cookie, sesión cerrada por seguridad.

No se toca: el logo, el flujo de autenticación y sus rutas, la limpieza de cookies duplicadas del manejador del 419.

Decisiones abiertas: ninguna. Este lenguaje se llevará después a las vistas internas.

## Direction contract

THESIS: El Login de siempre, depurado hasta lo esencial y hecho ticket: la entrada al turnero tiene la forma del turno que imprime el atril, y dice en qué unidad se está. Rechaza la plantilla con eslogan y adornos (círculos decorativos, botón con degradado que se eleva, píldoras, nombre del hospital en cuatro líneas).

OWN-WORLD: Fondo claro con tinte azul y una luz suave desde arriba; superficies blancas con borde azul grisáceo fino; texto en azul noche; el azul institucional #064b9e solo en la acción principal y en el foco; InterVariable propia; esquinas de 10 px en campos y botones, y de 18 px en el ticket (la pieza de papel es más blanda que sus controles); el ticket lleva un filo fino que sigue su forma, muescas incluidas; sombras cortas con desplazamiento y desenfoque suave; ninguna ilustración.

STORY: La persona ve el logo y el nombre de su unidad, escribe usuario y contraseña con etiquetas visibles, pulsa Entrar y ve «Entrando…». Si falla, el aviso nombra el problema y la salida (Bloq Mayús, administrador, soporte).

FIRST VIEWPORT: Una columna centrada de 400 px, algo por encima del centro, con el formulario como el ticket que imprime el atril. Talón arriba (logo de 70 px, nombre de la unidad a 28 px seminegrita azul noche, «Turnero del Hospital Universitario del Valle», y el sello con fecha y hora del servidor); un corte con muescas y línea punteada; cuerpo abajo con los avisos, dos campos de 48 px y Entrar a todo el ancho. Una línea de ayuda debajo del ticket (sin aviso de PC compartido: lo retiró el usuario); pie con el nombre oficial en el flujo. Interacción propia: al enviar, el botón dice «Entrando…» y la línea de corte corre como un ticket que se desprende; bloquea el doble envío. Movimiento: el ticket sale con un leve ascenso, salida exponencial; nada con movimiento reducido. Variante elegida por el usuario entre tres maquetas (A · El ticket).

FORM: Refinamiento del Login actual, fijado por el usuario. seed: none (pinned) — «quiero algo hermoso y minimalista pues como puedes ver como está ahora en este momento».

FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance
