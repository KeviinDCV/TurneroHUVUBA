---
name: Turnero HUV
description: Panel interno del turnero del Hospital Universitario del Valle; claro, minimalista y con la forma del turno impreso.
colors:
  azul: "#064b9e"
  azul-hover: "#053f87"
  azul-activo: "#04356f"
  azul-ocupado: "#2f63a6"
  foco: "rgba(6, 75, 158, 0.18)"
  tinta: "#0f2747"
  tinta-2: "#23344f"
  tinta-suave: "#4a5d78"
  tinta-tenue: "#56698a"
  icono: "#5b6f8c"
  borde: "#7d91ad"
  borde-hover: "#5f7698"
  corte: "#a7b8d0"
  superficie: "#ffffff"
  fondo-alto: "#eef3fa"
  fondo-bajo: "#e6edf6"
  error: "#b42318"
  error-fondo: "#fdf0ef"
  error-borde: "#f1c6c2"
  error-texto: "#8a1c1c"
  info-fondo: "#eaf1fb"
  info-borde: "#c6d7f0"
  info-texto: "#173a6a"
  alerta-fondo: "#fff6e6"
  alerta-borde: "#f0d29b"
  alerta-texto: "#6b4400"
  alerta-tinta: "#7a4b00"
typography:
  display:
    fontFamily: "InterVariable, Segoe UI, system-ui, sans-serif"
    fontSize: "28px"
    fontWeight: 650
    lineHeight: 1.15
    letterSpacing: "-0.02em"
    fontFeature: "'cv11', 'ss01'"
  subtitle:
    fontFamily: "InterVariable, Segoe UI, system-ui, sans-serif"
    fontSize: "14.5px"
    fontWeight: 400
    lineHeight: 1.45
  input:
    fontFamily: "InterVariable, Segoe UI, system-ui, sans-serif"
    fontSize: "16px"
    fontWeight: 400
  button:
    fontFamily: "InterVariable, Segoe UI, system-ui, sans-serif"
    fontSize: "15.5px"
    fontWeight: 600
    letterSpacing: "0.005em"
  body:
    fontFamily: "InterVariable, Segoe UI, system-ui, sans-serif"
    fontSize: "14px"
    fontWeight: 400
    lineHeight: 1.5
  label:
    fontFamily: "InterVariable, Segoe UI, system-ui, sans-serif"
    fontSize: "13.5px"
    fontWeight: 600
  caption:
    fontFamily: "InterVariable, Segoe UI, system-ui, sans-serif"
    fontSize: "12.5px"
    fontWeight: 400
    lineHeight: 1.5
    letterSpacing: "0.02em"
    fontFeature: "'tnum'"
rounded:
  control-inner: "6px"
  control: "10px"
  ticket: "18px"
  muesca: "12px"
spacing:
  etiqueta: "7px"
  control-x: "14px"
  campo: "18px"
  aviso: "20px"
  accion: "26px"
  ticket-x: "34px"
  ticket-x-compacto: "24px"
  columna: "400px"
components:
  button-primary:
    backgroundColor: "{colors.azul}"
    textColor: "{colors.superficie}"
    typography: "{typography.button}"
    rounded: "{rounded.control}"
    height: "48px"
    width: "100%"
  button-primary-hover:
    backgroundColor: "{colors.azul-hover}"
  button-primary-active:
    backgroundColor: "{colors.azul-activo}"
  button-primary-busy:
    backgroundColor: "{colors.azul-ocupado}"
  button-icon:
    textColor: "{colors.icono}"
    rounded: "{rounded.control-inner}"
    size: "40px"
  button-icon-hover:
    backgroundColor: "{colors.fondo-alto}"
    textColor: "{colors.azul}"
  input:
    backgroundColor: "{colors.superficie}"
    textColor: "{colors.tinta}"
    typography: "{typography.input}"
    rounded: "{rounded.control}"
    padding: "0 14px"
    height: "48px"
  notice-error:
    backgroundColor: "{colors.error-fondo}"
    textColor: "{colors.error-texto}"
    typography: "{typography.body}"
    rounded: "{rounded.control}"
    padding: "12px 14px"
  notice-info:
    backgroundColor: "{colors.info-fondo}"
    textColor: "{colors.info-texto}"
    typography: "{typography.body}"
    rounded: "{rounded.control}"
    padding: "12px 14px"
  notice-alert:
    backgroundColor: "{colors.alerta-fondo}"
    textColor: "{colors.alerta-texto}"
    typography: "{typography.body}"
    rounded: "{rounded.control}"
    padding: "12px 14px"
  ticket-stub:
    backgroundColor: "{colors.superficie}"
    rounded: "{rounded.ticket}"
    padding: "30px 34px 22px"
  ticket-body:
    backgroundColor: "{colors.superficie}"
    rounded: "{rounded.ticket}"
    padding: "28px 34px 32px"
---

# Design System: Turnero HUV

## Overview

**Creative North Star: "El turno impreso"**

El turnero ordena una fila; su interfaz tiene el aplomo del papel que imprime el atril. Fondo claro con un leve tinte azul y una luz suave desde arriba, superficies blancas, texto en azul noche y el azul institucional reservado para la acción principal y el foco. Lo bello sale del oficio (proporción, espacio, tipografía y detalle), no de añadir cosas: no hay ilustraciones, metáforas decorativas ni degradados en los controles.

El sistema nació en el Login y se lleva a todas las vistas internas: el panel del administrador y el del asesor. Esas son pantallas de trabajo (modo Operate), usadas en PC compartidos con pacientes esperando; la densidad puede subir, pero el vocabulario no cambia: mismos colores con los mismos papeles, misma tipografía, mismas esquinas, el mismo foco, la misma sobriedad en movimiento. El logo del HUV está siempre presente y cada pantalla dice en qué unidad se está.

Se rechazan, por decisión del usuario: la plantilla con eslogan y adornos (círculos decorativos, botón con degradado que se eleva, píldoras), la ilustración del edificio, la señalética, la escala gris tipo SaaS y la tarjeta genérica.

**Key Characteristics:**
- Claro y con tinte azul: fondo `fondo-alto` a `fondo-bajo` con luz blanca radial desde arriba.
- Un solo acento: el azul institucional, solo en la acción principal, el foco y el cursor de texto.
- InterVariable propia, sin dependencias externas; cifras tabulares en fechas y horas.
- Esquinas de 10 px en controles; 18 px solo en el ticket. Lo que va dentro de un control es concéntrico: radio interior = radio exterior − separación (el botón del ojo, a 4 px del borde de un campo de 10 px, lleva 6 px).
- Profundidad corta y azulada; una sola pieza elevada por pantalla.
- Un solo movimiento de entrada, y nada con movimiento reducido.

## Colors

Una paleta fría de azul noche sobre blanco, con el azul institucional como única voz de color y tres familias de estado tenues para los avisos.

### Primary
- **Azul institucional** (`azul`): la acción principal de la pantalla (Entrar, Llamar, Guardar), el borde de un campo con foco, el cursor de texto y el hover de botones de icono. Sus pasos `azul-hover` y `azul-activo` son los estados del mismo botón; `azul-ocupado` es el botón mientras trabaja (aria-busy), más claro y sin sombra.
- **Halo de foco** (`foco`): el anillo de 3 px alrededor de un campo enfocado y el color de selección de texto.

### Neutral
- **Azul noche** (`tinta`): texto principal, títulos y valores escritos en los campos.
- **Azul noche medio** (`tinta-2`): etiquetas de campo.
- **Pizarra azul** (`tinta-suave`): texto secundario (subtítulo, ayuda).
- **Pizarra tenue** (`tinta-tenue`): metadatos y pie: fecha, hora, nombre oficial. Mantiene 4.5:1 sobre blanco y sobre el fondo.
- **Pizarra de icono** (`icono`): iconos de acción en reposo.
- **Borde azul grisáceo** (`borde`, `borde-hover`): el contorno fino de los campos (3:1 sobre blanco, el mínimo de un control) y su hover.
- **Línea de corte** (`corte`): la línea punteada del ticket; decorativa, nunca para separar contenido que haya que leer.
- **Papel** (`superficie`): toda superficie de trabajo: ticket, campos, tarjetas del panel.
- **Fondo con tinte** (`fondo-alto` arriba, `fondo-bajo` abajo): el lienzo de la página, en degradado vertical bajo una luz radial blanca. `fondo-alto` también es el relleno de hover de un botón de icono.

### Estados
- **Error** (`error`): borde de un campo inválido (aria-invalid) y base de su halo. Aviso de error: `error-fondo`, `error-borde`, `error-texto`.
- **Información** (`info-fondo`, `info-borde`, `info-texto`): avisos neutros (página caducada, sesión cerrada).
- **Alerta** (`alerta-fondo`, `alerta-borde`, `alerta-texto`): problemas que piden ayuda de sistemas. `alerta-tinta` es el texto de una advertencia en línea sin caja (Bloq Mayús).

### Named Rules
**La Regla de Una Sola Voz.** El azul institucional marca una sola acción principal por vista y el foco. Las acciones secundarias van en azul noche o como botón de icono; nunca dos botones azules rellenos compitiendo.

**La Regla del Estado con Palabras.** Un aviso nunca es solo color: lleva texto que nombra el problema y la salida, y role="alert" o role="status".

## Typography

**Fuente:** InterVariable (alojada en `public/fonts`, con Segoe UI y system-ui de respaldo), con los rasgos `cv11` y `ss01` activos en todo el documento.

**Character:** Una sola familia variable, con el peso haciendo la jerarquía. Seminegritas (600–650) para lo que se nombra, regular para lo que se lee.

### Hierarchy
- **Display** (650, 28px, 1.15, -0.02em): el nombre de la unidad o el título de la vista. Uno por pantalla, con `text-wrap: balance`.
- **Subtitle** (400, 14.5px, 1.45): la línea que acompaña al título.
- **Input** (400, 16px): el texto escrito en los campos; 16 px evita el zoom del navegador.
- **Button** (600, 15.5px, 0.005em): el texto de la acción principal.
- **Body** (400, 14px, 1.5): avisos y texto corrido; `text-wrap: pretty`.
- **Label** (600, 13.5px): etiquetas de campo, siempre visibles encima del campo. La ayuda bajo el formulario usa 13.5px regular con 1.55.
- **Caption** (400, 12.5px, 0.02em, cifras tabulares): fecha, hora, pie y la nota técnica de un aviso.

### Named Rules
**La Regla de la Etiqueta Visible.** Todo campo lleva su etiqueta escrita encima; el placeholder nunca la sustituye.

**La Regla de la Cifra Quieta.** Horas, fechas, números de turno y conteos usan cifras tabulares, para que no bailen al cambiar.

## Layout

El Login es una columna centrada de 400 px (`columna`), un poco por encima del centro (−3vh), con 40 px de aire arriba y 20 px a los lados. Debajo de 700 px de alto la columna se ancla arriba y el logo baja de 70 a 54 px; debajo de 420 px de ancho el ticket pasa de 34 px de margen interno lateral (`ticket-x`) a 24 px (`ticket-x-compacto`).

El ritmo vertical dentro de una superficie de trabajo: 7 px de etiqueta a campo (`etiqueta`), 18 px entre campos (`campo`), 20 px bajo un aviso (`aviso`) y 26 px antes de la acción principal (`accion`), que se separa del resto del formulario. Los controles tienen 48 px de alto y 14 px de margen interno (`control-x`). El pie va en el flujo del documento, nunca fijo, y une con espacios de no separación las piezas que no deben partirse al ajustar la línea («E.S.E.&nbsp;·», «Innovación&nbsp;y&nbsp;Desarrollo»).

En el panel interno (solo PC), conservar estos pasos para formularios y avisos; las vistas de trabajo pueden ser más anchas y densas, pero los controles no bajan de 40 px de alto.

## Elevation & Depth

Sistema plano sobre un lienzo con luz, con sombras cortas y teñidas de azul noche (nunca negras). Hay tres alturas: la pieza principal flota con un filo fino que sigue su forma más una sombra doble (contacto + ambiente), la acción principal se apoya con una sombra de contacto de 2 px, y los campos un roce de 1 px. Nada brilla: no hay resplandor azul bajo los botones. El foco no es sombra de profundidad sino un halo plano.

### Shadow Vocabulary
- **Pieza elevada** (`filter: drop-shadow(0 0 0.6px rgba(15, 39, 71, 0.35)) drop-shadow(0 1px 1px rgba(15, 39, 71, 0.07)) drop-shadow(0 22px 28px rgba(15, 39, 71, 0.13))`): el ticket. La primera capa es el filo: un contorno de medio píxel que sigue la forma recortada, muescas incluidas, y separa el papel del fondo claro. Se aplica con `drop-shadow` en el contenedor, nunca con `border` o `box-shadow`, para que siga las muescas.
- **Acción** (`box-shadow: 0 1px 2px rgba(15, 39, 71, 0.14)`): el botón principal, solo contacto; no cambia al pasar el cursor ni al pulsar, y ocupado no tiene sombra.
- **Roce** (`box-shadow: 0 1px 2px rgba(15, 39, 71, 0.06)`): campos de texto.
- **Halo de foco** (`box-shadow: 0 0 0 3px` con `foco`): campo enfocado; con error, el halo usa el rojo al 16 %.

### Named Rules
**La Regla de la Pieza Única.** Una sola superficie por vista lleva la sombra elevada. Las tarjetas y filas del panel son planas o con roce; si todo flota, nada flota.

## Shapes

Esquinas suaves y uniformes: 10 px (`control`) en campos, botones y avisos; 6 px (`control-inner`) en un botón de icono anidado dentro de un campo (concéntrico: 10 px del campo − 4 px de separación); 18 px (`ticket`) solo en el contorno exterior del ticket. Las muescas del corte del ticket son medios círculos de 12 px (`muesca`) recortados con máscara. Bordes de 1 px; nada de píldoras ni formas totalmente redondas.

## Components

### Buttons
Sobrios y firmes: rellenos planos, sin degradado ni elevación al pasar el cursor.
- **Shape:** esquinas suaves (10px), 48px de alto, a todo el ancho en formularios de una columna.
- **Primary:** `azul` con texto blanco, tipografía Button, sombra de contacto.
- **Hover / Active:** el fondo baja a `azul-hover` y `azul-activo`; transición de 0.15s solo en el color de fondo. Sin desplazamiento ni cambio de sombra.
- **Focus:** contorno de 3px en azul al 35 % separado 3px del botón (`:focus-visible`).
- **Ocupado:** `aria-busy="true"`, fondo `azul-ocupado` sin sombra, texto en gerundio («Entrando…») y cursor de progreso; dentro del ticket, la línea de corte corre. No hay indicador giratorio: el estado lo dicen el color, la palabra y el corte. Un segundo envío se bloquea. Si la red no responde en 20 s, el botón vuelve a servir.
- **Icono:** 40px cuadrado, transparente, icono SVG de trazo 1.8 en `icono`; hover con fondo `fondo-alto` y icono azul; foco con contorno de 2px azul. Si alterna un estado, lleva `aria-pressed` y una etiqueta que cambia.

### Inputs / Fields
- **Style:** blanco, borde de 1px `borde`, esquinas de 10px, 48px de alto, roce de 1px, texto de 16px y cursor azul.
- **Hover:** el borde pasa a `borde-hover`.
- **Focus:** borde `azul` y halo de 3px `foco`; sin contorno del navegador.
- **Error:** `aria-invalid="true"` pone el borde en `error` y el halo en rojo; el campo apunta al aviso con `aria-describedby`. Solo los errores del propio dato marcan el campo.
- **Autorrelleno:** se neutraliza el amarillo del navegador; el campo se ve igual relleno a mano o por el navegador.
- **Advertencia en línea:** debajo del campo, 13px peso 500 en `alerta-tinta`, con un icono SVG de 15px y `aria-live="polite"` (Bloq Mayús).

### Notices (avisos)
- **Style:** caja con borde de 1px, esquinas de 10px, 12px por 14px de margen interno, texto Body. Una primera línea en negrita (600) nombra el problema cuando hace falta; debajo, qué hacer y a quién acudir.
- **Variantes:** error, información y alerta (ver Colors). Error y alerta llevan `role="alert"`; información, `role="status"`.
- **Detalle técnico:** una nota en Caption al final, para sistemas, con enlaces subrayados en el color del aviso.

### El ticket (signature component)
La pieza firma del sistema: la forma del turno que imprime el atril. Dos superficies blancas, un talón arriba y un cuerpo abajo, unidas por un corte con muescas laterales (máscaras radiales de 12px) y una línea punteada `corte` (trazo 7, hueco 6). Un filo fino (la primera capa de la sombra de pieza elevada) dibuja su contorno, muescas incluidas. El talón lleva la identidad (logo, nombre de la unidad en Display, subtítulo y un sello con fecha y hora del servidor en Caption); el cuerpo lleva el contenido o el formulario. Sombra de pieza elevada aplicada al conjunto.

- **Movimiento:** al aparecer sube 8px en 0.55s con salida exponencial (`cubic-bezier(0.16, 1, 0.3, 1)`), sin opacidad: el contenido nunca depende de la animación para verse. Mientras la acción trabaja, la línea de corte corre (0.5s lineal) como un ticket que se desprende. Con movimiento reducido, ninguna de las dos.
- **Cuándo sí:** donde la pantalla representa un turno o la entrada al turnero: el Login, el turno que el asesor está atendiendo o acaba de llamar, la vista previa del ticket que imprime el atril, una confirmación de turno.
- **Cuándo no:** nunca como tarjeta general del panel, ni para tablas, filtros, formularios de configuración, listas de turnos en espera ni informes. Una vista lleva como mucho un ticket; si hay varios turnos en una lista, son filas planas.

## Do's and Don'ts

### Do:
- **Do** reservar `azul` para una sola acción principal por vista y para el foco.
- **Do** usar InterVariable propia con `cv11` y `ss01`, y cifras tabulares en horas, fechas y números de turno.
- **Do** mantener esquinas de 10px en controles y avisos, y 48px de alto en los controles de un formulario.
- **Do** dar a todo campo una etiqueta visible en Label (600, 13.5px) y foco con borde azul y halo de 3px.
- **Do** escribir los avisos con el problema y la salida, con `role="alert"` o `role="status"`.
- **Do** limitar el movimiento a una entrada corta con salida exponencial y transiciones de estado de 0.15s, y quitarlo todo con `prefers-reduced-motion`.
- **Do** mostrar el logo del HUV y el nombre de la unidad en cada pantalla.

### Don't:
- **Don't** usar ilustraciones, círculos decorativos, metáforas visuales, señalética o fotos del edificio.
- **Don't** poner degradados en botones ni hacer que se eleven al pasar el cursor.
- **Don't** usar píldoras ni formas totalmente redondas en botones, etiquetas o avisos.
- **Don't** escribir el nombre del hospital en varias líneas apiladas como adorno; el nombre oficial va una vez, en el pie.
- **Don't** convertir el ticket en la tarjeta de todo el panel; es para un turno o para la entrada.
- **Don't** usar sombras negras ni más de una pieza elevada por vista.
- **Don't** cargar fuentes, scripts ni imágenes de fuera del servidor.
