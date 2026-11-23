# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

- **Asesores** de ventanilla o casilla. Al empezar el turno entran desde un PC del hospital (a menudo compartido con asesores de otros turnos), eligen su casilla y llaman, atienden, aplazan o transfieren turnos mientras hay pacientes esperando. Entran solo desde PC, nunca desde el celular.
- **Administradores** de cada unidad. Configuran servicios y secciones, usuarios, casillas, la pantalla de TV, la base de pacientes del atril (UBA), y sacan los informes para la jefatura y control interno. También entran solo desde PC.
- **Pacientes**, que no inician sesión. Sacan turno en el atril y siguen el llamado en el TV.

Cada persona tiene el enlace de su propia unidad: nadie trabaja en las dos ni entra por error a la otra.

## Product Purpose

Ordenar la atención presencial de dos unidades del Hospital Universitario del Valle.
- El paciente saca su turno en el atril.
- El asesor lo llama desde su casilla.
- El TV lo anuncia con voz.
- Los informes miden la atención con cifras que cuadran con la base de datos.

Éxito: la fila avanza sin fricción, cada asesor sabe qué hacer, y la jefatura y control interno confían en los números.

## Positioning

Es un sistema hecho a la medida del HUV, no un producto genérico de filas. Tiene dos instalaciones gemelas con el mismo código:

| Instalación | Unidad | Lo propio de la unidad |
|---|---|---|
| turnero.huv.gov.co | **Fisiatría** | canales no presenciales |
| turnerouba.huv.gov.co | **Unidad Básica de Atención (UBA)** | transferencias entre servicios, y cédula en el atril con base de pacientes |

Las dos comparten:
- servicios y secciones propios;
- TV con videos y voz;
- métricas de atención validadas con datos reales.

Corre completo en el cPanel del hospital.

## Operating Context

- **Producción:** corre en el cPanel del hospital, casi siempre por http en la red interna. El https usa un certificado autofirmado, y AutoSSL está pendiente.
- **Equipos:** PC con Chrome. El TV corre en modo kiosco por http. Las descargas van por URL, porque los gestores de descarga (Chrono, IDM) las toman.
- **Ritmo de uso:** jornadas con pacientes esperando desde temprano, y PC compartidos entre turnos.
- **Desarrollo:** el equipo local solo sirve para editar; el código se sube al cPanel. Hay dos repositorios gemelos, turnero-huv y turnero-huvuba, y los cambios de interfaz se replican en ambos. Los estilos van con Tailwind v4 compilado (`npm run build`, que sirve desde `public/build`).

## Capabilities and Constraints

- **Roles:** Administrador y Asesor. El inicio de sesión es con usuario y contraseña.
- **Contraseñas:** no hay recuperación por correo; un administrador asigna o cambia la contraseña desde Usuarios.
- **Soporte:** hay un módulo de soporte dentro del panel. El contacto para urgencias se configura con `PANEL_SOPORTE_CONTACTO` en el `.env`, y el nombre de la unidad con `PANEL_UNIDAD_NOMBRE`.
- **Sin dependencias externas en el navegador:** la red interna puede no tener salida a internet y hay un CSP pendiente de activar. Fuentes, scripts e imágenes deben ser propios.
- **Descargas:** nunca con Blob; siempre por URL.
- **Idioma:** español de Colombia.
- **Tratamiento (tú o usted):** sin decidir; hoy se mezclan.

## Brand Commitments

- Nombre oficial: Hospital Universitario del Valle «Evaristo García» E.S.E.
- El logo del HUV (`public/images/logo.svg`, `logo.png`) siempre presente; nunca se quita.
- Azul institucional `#064b9e`.
- Estilo (confirmado el 2026-11-23): **minimalista y claro, como el Login actual**. Fondo claro con un leve tinte azul, el logo arriba al centro, superficies blancas y el azul institucional en la acción principal. Nada de ilustraciones, metáforas ni recargos: lo bello sale del oficio (proporción, espacio, tipografía, detalle), no de añadir cosas. El usuario rechazó direcciones con ilustración del edificio, señalética, escala gris tipo SaaS y la tarjeta genérica.
- El Login fija este lenguaje y luego se lleva a todas las vistas internas.

## Evidence on Hand

- **Manuales de usuario en PDF**, uno por turnero, en `Documents/turnero-rediseno/manual`.
- **Logos:** `public/images/logo.svg`, `logo.png` y `logoacreditacion.png`.
- **Tipografía:** InterVariable alojada en `public/fonts`.
- **Lo que no hay:** testimonios, cifras públicas ni casos de éxito. No inventarlos.

## Product Principles

1. **La atención no espera.** Lo que usa un asesor con pacientes delante tiene que ser inmediato y nunca fallar en silencio.
2. **Cada pantalla dice dónde estás y qué está pasando.** La unidad, el estado del sistema y a quién acudir cuando algo falla.
3. **Los datos de los pacientes se protegen (Ley 1581).** Nunca a la vista de quien no debe verlos.
4. **Funciona en la infraestructura real del hospital.** cPanel, http, PC compartidos y sin salida a internet garantizada.
5. **Las cifras cuadran.** Lo que muestra un informe tiene que coincidir con la base de datos.

## Accessibility & Inclusion

- No hay un estándar formal exigido.
- El panel ya pasó una auditoría de accesibilidad: foco visible, etiquetas y contraste. Ese nivel se mantiene en el rediseño.
- Los PC son compartidos, así que el cuidado de la sesión es parte de la experiencia: no guardar la contraseña y cerrar sesión al terminar.
