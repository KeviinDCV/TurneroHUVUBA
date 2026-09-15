<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preload" href="{{ asset('fonts/InterVariable.woff2') }}" as="font" type="font/woff2" crossorigin>
    <title>Generar turno · {{ config('app.name', 'Turnero HUV') }}</title>
    @include('components.favicon')

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* KIOSCO 2026-09 — lo usa el asesor: fichas grandes para el dedo y lectura rápida */
        @font-face {
            font-family: 'Inter';
            src: url('{{ asset('fonts/InterVariable.woff2') }}') format('woff2');
            font-weight: 100 900; font-style: normal; font-display: block;
        }
        :root { --azul: #064b9e; --azul-hover: #053d7a; --tinta: #0f1f3d; --mudo: #5b6b82; --linea: #d9e1ec; --fondo: #f3f6fb; --rojo: #b42318; }
        * { -webkit-tap-highlight-color: transparent; }
        html, body { height: 100%; }
        body { font-family: 'Inter', 'Segoe UI', system-ui, sans-serif; font-feature-settings: 'cv05'; color: var(--tinta); background: var(--fondo); overflow: hidden; user-select: none; -webkit-user-select: none; }
        button { cursor: pointer; }

        .kiosco { height: 100vh; height: 100dvh; display: grid; grid-template-rows: auto minmax(0, 1fr) auto; }
        .barra { display: flex; align-items: center; gap: clamp(.8rem, 1.6vw, 1.6rem); padding: clamp(.7rem, 1.6vh, 1.2rem) clamp(1.25rem, 3vw, 3rem); background: #fff; box-shadow: inset 0 -1px 0 var(--linea); }
        .barra img { height: clamp(46px, 7vh, 80px); width: auto; flex: none; }
        .barra-nombre { font-size: clamp(1rem, 1.6vw, 1.5rem); font-weight: 700; line-height: 1.15; color: var(--azul); }
        .barra-unidad { margin-top: .15rem; font-size: clamp(.8rem, 1.1vw, 1.05rem); color: var(--mudo); }
        .barra-reloj { margin-left: auto; text-align: right; }
        .barra-hora { font-size: clamp(1.6rem, 3vw, 2.8rem); font-weight: 700; line-height: 1; letter-spacing: -.02em; font-variant-numeric: tabular-nums; }
        .barra-fecha { margin-top: .25rem; font-size: clamp(.8rem, 1.05vw, 1.05rem); color: var(--mudo); }

        .contenido { min-height: 0; overflow-y: auto; padding: clamp(1.5rem, 4.5vh, 3.75rem) clamp(1.25rem, 5vw, 6rem); }
        .antetitulo { font-size: clamp(.9rem, 1.3vw, 1.2rem); font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: var(--azul); }
        .encabezado h1 { margin-top: .6vh; font-size: clamp(2rem, 3.8vw, 3.6rem); font-weight: 800; line-height: 1.08; letter-spacing: -.025em; }
        .encabezado .sub { margin-top: 1vh; font-size: clamp(1.05rem, 1.7vw, 1.55rem); color: var(--mudo); }
        .opciones { margin-top: clamp(1.5rem, 4.5vh, 3.25rem); display: grid; grid-template-columns: repeat(var(--columnas, 2), minmax(0, 1fr)); gap: clamp(.9rem, 2.2vh, 1.6rem); }
        @media (max-width: 900px) and (orientation: landscape) { .opciones { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        .opcion { display: flex; align-items: center; gap: 1.2rem; min-height: clamp(90px, 13vh, 150px); padding: 1.2rem clamp(1.4rem, 2.4vw, 2.4rem); border: 0; border-radius: 22px;
                  background: var(--azul); color: #fff; text-align: left; box-shadow: 0 16px 32px -20px rgba(6, 75, 158, .75); transition: background .15s, transform .1s; }
        .opcion:active { transform: scale(.985); background: var(--azul-hover); }
        .opcion:focus-visible { outline: 4px solid rgba(6, 75, 158, .35); outline-offset: 4px; }
        .opcion-texto { flex: 1; min-width: 0; }
        .opcion-nombre { display: block; font-size: clamp(1.35rem, 2.3vw, 2.3rem); font-weight: 750; line-height: 1.15; letter-spacing: .01em; overflow-wrap: anywhere; }
        .opcion-nota { display: block; margin-top: .45rem; font-size: clamp(.95rem, 1.25vw, 1.2rem); font-weight: 500; color: #bfdbfe; }
        .opcion-flecha { flex: none; width: clamp(50px, 5vw, 66px); height: clamp(50px, 5vw, 66px); display: grid; place-items: center; border-radius: 50%; background: rgba(255, 255, 255, .15); }
        .opcion-flecha svg { width: 46%; height: 46%; }
        .vacio { padding: 3rem 1rem; text-align: center; font-size: clamp(1.1rem, 1.6vw, 1.5rem); color: var(--mudo); }

        .pie { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: clamp(.8rem, 2vh, 1.4rem) clamp(1.25rem, 3vw, 3rem); background: #fff; box-shadow: inset 0 1px 0 var(--linea); }
        .volver { height: clamp(58px, 7.5vh, 78px); padding: 0 clamp(1.4rem, 2vw, 2.2rem); display: inline-flex; align-items: center; gap: .6rem; border-radius: 16px; border: 2px solid #c9d5e6;
                  background: #fff; color: var(--azul); font-size: clamp(1.1rem, 1.6vw, 1.45rem); font-weight: 700; }
        .volver:active { background: #eef3fb; }
        .volver svg { width: 1.2em; height: 1.2em; }
        .firma { font-size: .75rem; color: #9aa6b8; }

        /* Ventanas */
        .velo { position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; padding: 3vh 4vw; background: rgba(7, 20, 40, .55); }
        .dialogo { width: 100%; max-width: 40rem; max-height: 94vh; overflow-y: auto; border-radius: 26px; background: #fff; padding: clamp(1.6rem, 4.5vh, 3rem) clamp(1.5rem, 3.2vw, 3rem);
                   text-align: center; box-shadow: 0 30px 80px -30px rgba(7, 20, 40, .6); animation: aparecer .18s ease-out; }
        .dialogo--ancho { max-width: 60rem; }
        @keyframes aparecer { from { opacity: 0; transform: translateY(10px) scale(.985); } }
        .dialogo h2 { font-size: clamp(1.6rem, 2.9vw, 2.7rem); font-weight: 800; line-height: 1.12; letter-spacing: -.02em; }
        .dialogo .sub { margin-top: .7rem; font-size: clamp(1.05rem, 1.6vw, 1.45rem); line-height: 1.45; color: var(--mudo); }
        .icono-dialogo { width: clamp(64px, 7vw, 84px); height: clamp(64px, 7vw, 84px); margin: 0 auto 1.2rem; display: grid; place-items: center; border-radius: 50%; background: #e3ecf9; color: var(--azul); }
        .icono-dialogo--alerta { background: #fdecec; color: var(--rojo); }
        .icono-dialogo svg { width: 48%; height: 48%; }
        .tipos { margin-top: clamp(1.4rem, 3.5vh, 2.6rem); display: grid; grid-template-columns: 1fr 1fr; gap: clamp(.9rem, 1.6vw, 1.4rem); }
        .tipo { display: flex; flex-direction: column; align-items: center; justify-content: flex-start; gap: .75rem; min-height: clamp(190px, 27vh, 310px); padding: clamp(1.6rem, 4vh, 2.6rem) 1.4rem 1.4rem;
                border: 0; border-radius: 22px; color: #fff; transition: transform .1s, filter .15s; }
        .tipo:active { transform: scale(.985); filter: brightness(.92); }
        .tipo svg { width: clamp(46px, 5vw, 66px); height: clamp(46px, 5vw, 66px); }
        .tipo-nombre { font-size: clamp(1.4rem, 2.4vw, 2.2rem); font-weight: 800; }
        .tipo-nota { max-width: 22rem; font-size: clamp(.98rem, 1.35vw, 1.25rem); line-height: 1.35; opacity: .92; }
        .btn-prioridad-normal { background: var(--azul); }
        .btn-prioridad-alta { background: var(--rojo); }
        .botones { display: flex; justify-content: center; flex-wrap: wrap; gap: .9rem; margin-top: clamp(1.3rem, 3vh, 2.2rem); }
        .primario, .secundario { min-width: 11rem; height: clamp(58px, 7vh, 72px); padding: 0 2rem; border-radius: 16px; font-size: clamp(1.1rem, 1.6vw, 1.4rem); font-weight: 700; }
        .primario { border: 0; background: var(--azul); color: #fff; }
        .primario:active { background: var(--azul-hover); }
        .secundario { border: 2px solid #c9d5e6; background: #fff; color: #33415c; }
        .secundario:active { background: #eef3fb; }

        .cargando { position: fixed; inset: 0; z-index: 60; flex-direction: column; align-items: center; justify-content: center; gap: 1.3rem; padding: 6vw; background: var(--fondo); text-align: center; }
        .giro { width: clamp(70px, 8vw, 100px); height: clamp(70px, 8vw, 100px); border-radius: 50%; border: 7px solid #d6e2f3; border-top-color: var(--azul); animation: giro .8s linear infinite; }
        @keyframes giro { to { transform: rotate(360deg); } }
        #loadingMessage { font-size: clamp(1.7rem, 3.2vw, 3rem); font-weight: 800; letter-spacing: -.02em; color: var(--azul); }
        #loadingSubMessage { font-size: clamp(1.1rem, 1.7vw, 1.55rem); color: var(--mudo); }
        #errorOverlay { z-index: 61; }

        @media (orientation: portrait) {
            .opciones { grid-template-columns: 1fr; }
            .opcion { min-height: clamp(110px, 10.5vh, 190px); }
            .opcion-nombre { font-size: clamp(1.5rem, 4.4vw, 2.6rem); }
            .encabezado h1 { font-size: clamp(2.2rem, 6.6vw, 4rem); }
            .tipos { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    @php
        $ahoraKiosco = \Carbon\Carbon::now('America/Bogota');
        $fechaKiosco = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'][$ahoraKiosco->dayOfWeek] . ' ' . $ahoraKiosco->day . ' de '
            . ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'][$ahoraKiosco->month - 1];
        $enSubservicios = isset($mostrandoSubservicios) && $mostrandoSubservicios;
    @endphp
    <div class="kiosco">
        <header class="barra">
            <img src="{{ asset('images/logo.png') }}" alt="">
            <div>
                <div class="barra-nombre">Hospital Universitario del Valle</div>
                <div class="barra-unidad">{{ config('panel.unidad_nombre') }} · “Evaristo García” E.S.E.</div>
            </div>
            <div class="barra-reloj" aria-hidden="true">
                <div class="barra-hora" id="reloj-hora">{{ $ahoraKiosco->format('H:i') }}</div>
                <div class="barra-fecha" id="reloj-fecha">{{ $fechaKiosco }}</div>
            </div>
        </header>

        <main class="contenido">
            <div class="encabezado">
                @if($enSubservicios)
                    <div class="antetitulo">{{ mb_strtoupper($servicioSeleccionado->nombre) }}</div>
                    <h1>Elija la opción</h1>
                    <p class="sub">Al tocarla se imprime el turno para entregarlo al paciente.</p>
                @else
                    <div class="antetitulo">Generar turno</div>
                    <h1>Elija el servicio</h1>
                    <p class="sub">Al tocarlo se imprime el turno para entregarlo al paciente; si tiene opciones, primero se elige la opción.</p>
                @endif
            </div>

            @php
                $cantidadFichas = $enSubservicios ? max($subservicios->count(), 1) : $servicios->count();
            @endphp
            <div class="opciones" style="--columnas: {{ $cantidadFichas <= 4 ? 2 : 3 }}">
                @if($enSubservicios)
                    @forelse($subservicios as $subservicio)
                        <button type="button" class="opcion btn-service" onclick="seleccionarSubservicio({{ $subservicio->id }}, @js($subservicio->nombre))">
                            <span class="opcion-texto"><span class="opcion-nombre">{{ mb_strtoupper($subservicio->nombre) }}</span><span class="opcion-nota">Imprimir turno</span></span>
                            <span class="opcion-flecha" aria-hidden="true"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.6" d="M9 5l7 7-7 7"/></svg></span>
                        </button>
                    @empty
                        <button type="button" class="opcion btn-service" onclick="seleccionarServicio({{ $servicioSeleccionado->id }}, @js($servicioSeleccionado->nombre))">
                            <span class="opcion-texto"><span class="opcion-nombre">{{ mb_strtoupper($servicioSeleccionado->nombre) }}</span><span class="opcion-nota">Imprimir turno</span></span>
                            <span class="opcion-flecha" aria-hidden="true"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.6" d="M9 5l7 7-7 7"/></svg></span>
                        </button>
                    @endforelse
                @else
                    @forelse($servicios as $servicio)
                        @php
                            $opcionesActivas = $servicio->subservicios()->where('estado', 'activo')->count();
                        @endphp
                        @if($opcionesActivas > 0)
                            <button type="button" class="opcion btn-service" onclick="navegarASubservicios({{ $servicio->id }})">
                            <span class="opcion-texto"><span class="opcion-nombre">{{ mb_strtoupper($servicio->nombre) }}</span><span class="opcion-nota">{{ $opcionesActivas }} {{ $opcionesActivas === 1 ? 'opción' : 'opciones' }}</span></span>
                            <span class="opcion-flecha" aria-hidden="true"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.6" d="M9 5l7 7-7 7"/></svg></span>
                        </button>
                        @else
                            <button type="button" class="opcion btn-service" onclick="seleccionarServicio({{ $servicio->id }}, @js($servicio->nombre))">
                            <span class="opcion-texto"><span class="opcion-nombre">{{ mb_strtoupper($servicio->nombre) }}</span><span class="opcion-nota">Imprimir turno</span></span>
                            <span class="opcion-flecha" aria-hidden="true"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.6" d="M9 5l7 7-7 7"/></svg></span>
                        </button>
                        @endif
                    @empty
                        <p class="vacio">No hay servicios disponibles en este momento.</p>
                    @endforelse
                @endif
            </div>
        </main>

        <footer class="pie">
            <button type="button" class="volver" onclick="window.location.href='{{ $enSubservicios ? route('turnos.menu') : route('turnos.inicio') }}'">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.6" d="M15 19l-7-7 7-7"/></svg>
                Volver
            </button>
            <span class="firma">Turnero HUV · Innovación y desarrollo</span>
        </footer>
    </div>

    <!-- Tipo de turno (servicios con prioridad) -->
    <div id="prioridadModal" class="velo" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="prioridadModalTitle">
        <div class="dialogo dialogo--ancho">
            <h2 id="prioridadModalTitle">Tipo de turno</h2>
            <p id="prioridadServicioNombre" class="sub"></p>
            <div class="tipos">
                <button type="button" onclick="seleccionarPrioridad('normal')" class="tipo btn-prioridad-normal">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span class="tipo-nombre">General</span>
                    <span class="tipo-nota">Atención en orden de llegada</span>
                </button>
                <button type="button" onclick="seleccionarPrioridad('alta')" class="tipo btn-prioridad-alta">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.48 3.5a.56.56 0 011.04 0l2.13 4.9 5.32.46c.5.04.7.66.32.98l-4.03 3.5 1.2 5.2c.11.49-.42.87-.85.61L12 16.4l-4.61 2.75c-.43.26-.96-.12-.85-.61l1.2-5.2-4.03-3.5c-.38-.32-.18-.94.32-.98l5.32-.46 2.13-4.9z"/></svg>
                    <span class="tipo-nombre">Prioritario</span>
                    <span class="tipo-nota">Adultos mayores, mujeres embarazadas y personas con discapacidad</span>
                </button>
            </div>
            <div class="botones">
                <button type="button" class="secundario" onclick="cerrarPrioridadModal()">Cancelar</button>
            </div>
        </div>
    </div>

    <!-- Aviso -->
    <div id="confirmModal" class="velo" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="confirmModalTitle">
        <div class="dialogo">
            <div class="icono-dialogo"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
            <h2 id="confirmModalTitle">Aviso</h2>
            <p id="confirmMessage" class="sub"></p>
            <div class="botones"><button type="button" class="primario" onclick="cerrarModal()">Aceptar</button></div>
        </div>
    </div>

    <!-- Generando / imprimiendo el turno -->
    <div id="loadingOverlay" class="cargando" style="display: none;" role="status" aria-live="polite">
        <div class="giro" aria-hidden="true"></div>
        <p id="loadingMessage">Generando turno...</p>
        <p id="loadingSubMessage">Por favor espere</p>
    </div>

    <!-- El servidor tarda: reintentar o cancelar -->
    <div id="errorOverlay" class="velo" style="display: none;" role="alertdialog" aria-modal="true" aria-labelledby="errorTitulo">
        <div class="dialogo">
            <div class="icono-dialogo icono-dialogo--alerta"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg></div>
            <h2 id="errorTitulo">El servidor está tardando</h2>
            <p id="errorMessage" class="sub">La solicitud tardó demasiado. ¿Desea intentar de nuevo?</p>
            <div class="botones">
                <button type="button" class="primario" onclick="reintentarSolicitud()">Reintentar</button>
                <button type="button" class="secundario" onclick="cancelarSolicitud()">Cancelar</button>
            </div>
        </div>
    </div>

    <!-- Iframe oculto para impresión directa -->
    <iframe id="printFrame" style="position:absolute;width:0;height:0;border:0;"></iframe>

    <script>
        // Hora del kiosco (Colombia)
        (function () {
            const dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
            const meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
            function pintar() {
                const d = new Date(new Date().toLocaleString('en-US', { timeZone: 'America/Bogota' }));
                document.getElementById('reloj-hora').textContent = String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
                document.getElementById('reloj-fecha').textContent = dias[d.getDay()] + ' ' + d.getDate() + ' de ' + meses[d.getMonth()];
            }
            pintar();
            setInterval(pintar, 15000);
        })();
    </script>

    <script>
        // ============================================
        // CONFIGURACIÓN Y VARIABLES GLOBALES
        // ============================================
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        let currentCsrfToken = csrfToken;
        let servicioSeleccionadoId = null;
        let servicioSeleccionadoNombre = '';
        let procesandoSolicitud = false;
        let ultimaSolicitud = null; // Para reintentos

        // URL base para el enlace móvil del QR (se construye client-side)
        const baseUrl = window.location.origin;

        // ============================================
        // FETCH CON TIMEOUT Y AUTO-REINTENTO
        // ============================================
        let reintentoAutomatico = false; // Flag para saber si ya se reintentó automáticamente

        function fetchConTimeout(url, opciones = {}, timeoutMs = 25000) {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), timeoutMs);
            return fetch(url, { ...opciones, signal: controller.signal })
                .finally(() => clearTimeout(timeoutId));
        }

        // ============================================
        // RENOVACIÓN PERIÓDICA DEL CSRF TOKEN
        // ============================================
        // Previene errores 419 en kioscos que pasan horas sin recargar
        setInterval(function() {
            fetch('/turnos/menu', { method: 'GET', headers: { 'Accept': 'text/html' } })
                .then(response => response.text())
                .then(html => {
                    const match = html.match(/meta name="csrf-token" content="([^"]+)"/);
                    if (match && match[1]) {
                        currentCsrfToken = match[1];
                        const metaTag = document.querySelector('meta[name="csrf-token"]');
                        if (metaTag) metaTag.setAttribute('content', match[1]);
                        console.log('🔑 CSRF token renovado');
                    }
                })
                .catch(() => { /* silenciouso - se reintentará en el próximo ciclo */ });
        }, 30 * 60 * 1000); // Cada 30 minutos

        // ============================================
        // LOADING / ERROR OVERLAYS
        // ============================================
        function mostrarLoading(mensaje = 'Generando turno...', submensaje = 'Por favor espere') {
            document.getElementById('loadingMessage').textContent = mensaje;
            document.getElementById('loadingSubMessage').textContent = submensaje;
            document.getElementById('loadingOverlay').style.display = 'flex';
        }

        function ocultarLoading() {
            document.getElementById('loadingOverlay').style.display = 'none';
        }

        function mostrarError(mensaje = 'La solicitud tardó demasiado. ¿Desea intentar de nuevo?') {
            ocultarLoading();
            document.getElementById('errorMessage').textContent = mensaje;
            document.getElementById('errorOverlay').style.display = 'flex';
        }

        function ocultarError() {
            document.getElementById('errorOverlay').style.display = 'none';
        }

        function reintentarSolicitud() {
            ocultarError();
            if (ultimaSolicitud) {
                ultimaSolicitud();
            }
        }

        function cancelarSolicitud() {
            ocultarError();
            desbloquearBotones();
            ultimaSolicitud = null;
        }

        // ============================================
        // BLOQUEO/DESBLOQUEO DE BOTONES
        // ============================================
        function bloquearBotones() {
            procesandoSolicitud = true;
            document.querySelectorAll('.btn-service, .btn-prioridad-normal, .btn-prioridad-alta').forEach(btn => {
                btn.disabled = true;
                btn.style.opacity = '0.6';
                btn.style.pointerEvents = 'none';
            });
        }

        function desbloquearBotones() {
            procesandoSolicitud = false;
            document.querySelectorAll('.btn-service, .btn-prioridad-normal, .btn-prioridad-alta').forEach(btn => {
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.style.pointerEvents = 'auto';
            });
        }

        // ============================================
        // IMPRESIÓN DIRECTA DESDE IFRAME (sin navegar)
        // ============================================
        function imprimirTicket(turnoData) {
            const iframe = document.getElementById('printFrame');
            const doc = iframe.contentWindow || iframe.contentDocument;
            const iframeDoc = doc.document || doc;

            const fechaCreacion = new Date();
            const fecha = fechaCreacion.toLocaleDateString('es-CO', { day: '2-digit', month: '2-digit', year: 'numeric' });
            const hora = fechaCreacion.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });

            const ticketHTML = `<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Ticket ${turnoData.codigo_completo}</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{background:white;font-family:'Courier New',monospace;display:flex;justify-content:center;align-items:center;}
.ticket{width:80mm;padding:5mm;text-align:center;line-height:1.3;}
.ancho{display:inline-block;transform:scaleX(1.18);}
.logo{height:36px;margin-bottom:6px;}
.label{font-size:11px;color:#444;font-weight:bold;margin-bottom:1px;}
.turno{font-size:38px;font-weight:bold;color:#064b9e;letter-spacing:2px;margin:3px 0;line-height:1.15;}
.servicio{font-size:13px;font-weight:bold;color:#000;padding:0 2px;line-height:1.2;}
.sep{border-top:1px dashed #888;margin:6px 0;}
.info{font-size:12px;color:#222;}
.info div{margin:1px 0;}
.guia{border:2px solid #000;padding:3px 4px 4px;margin:6px 0;text-align:center;}
.guia-titulo{font-size:9px;font-weight:bold;letter-spacing:1px;color:#000;line-height:1.1;}
.guia-sitio{font-size:13px;font-weight:bold;color:#000;line-height:1.2;margin-top:1px;}
.guia-rango{font-size:21px;font-weight:bold;color:#000;line-height:1.1;white-space:nowrap;}
@media print{@page{size:auto;margin:0;}body{min-height:auto;}.ticket{width:80mm;padding:4mm 5mm;}.ticket,.guia,.info{page-break-inside:avoid;break-inside:avoid;}}
</style>
</head>
<body>
<div class="ticket">
<img src="${baseUrl}/images/logo.png" alt="Logo" class="logo">
<div class="label"><span class="ancho">TURNO</span></div>
<div class="turno"><span class="ancho">${turnoData.codigo_completo}</span></div>
<div class="label"><span class="ancho">SERVICIO</span></div>
<div class="servicio">${turnoData.servicio.toUpperCase()}</div>
<div class="sep"></div>
${turnoData.ubicacion ? `<div class="guia">
<div class="guia-titulo"><span class="ancho">¿DÓNDE DEBO UBICARME?</span></div>
<div class="guia-sitio"><span class="ancho">${turnoData.ubicacion.sitio}</span></div>
<div class="guia-rango"><span class="ancho">${turnoData.ubicacion.rango}</span></div>
</div>` : ''}
<div class="info">
<div><span class="ancho">FECHA: ${fecha}</span></div>
<div><span class="ancho">HORA: ${hora}</span></div>
</div>
${turnoData.prioridad && turnoData.prioridad === 'Prioritario' ? '<div style="margin-top:4px;font-size:11px;font-weight:bold;color:#dc2626;">★ PRIORITARIO</div>' : ''}
</div>
</body>
</html>`;

            iframeDoc.open();
            iframeDoc.write(ticketHTML);
            iframeDoc.close();

            // Esperar a que el logo cargue antes de imprimir
            const logoImg = iframeDoc.querySelector('.logo');
            let intentosImpresion = 0;
            const MAX_INTENTOS = 3;

            function ejecutarImpresion() {
                intentosImpresion++;
                mostrarLoading('Imprimiendo...', `Enviando a impresora`);

                try {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                } catch (e) {
                    console.error('Error al imprimir:', e);
                }

                // Después de imprimir (o intentar), volver al estado normal
                setTimeout(() => {
                    ocultarLoading();
                    desbloquearBotones();
                    ultimaSolicitud = null;
                }, 1500);
            }

            if (logoImg && !logoImg.complete) {
                logoImg.onload = ejecutarImpresion;
                logoImg.onerror = ejecutarImpresion; // Imprimir aunque falle el logo
                // Timeout si la imagen no carga en 3 segundos
                setTimeout(() => {
                    if (!logoImg.complete) ejecutarImpresion();
                }, 3000);
            } else {
                // Logo ya cargado o no existe, imprimir inmediatamente
                setTimeout(ejecutarImpresion, 100);
            }
        }

        // ============================================
        // PROCESAMIENTO DE RESPUESTA DEL SERVIDOR
        // ============================================
        function procesarRespuestaTurno(data) {
            reintentoAutomatico = false; // Reset flag en respuesta exitosa
            if (data.success) {
                if (data.duplicado) {
                    ocultarLoading();
                    desbloquearBotones();
                    mostrarModal(data.message);
                    return;
                }
                if (data.requiere_priorizacion) {
                    ocultarLoading();
                    servicioSeleccionadoId = data.servicio_id;
                    servicioSeleccionadoNombre = data.servicio_nombre;
                    desbloquearBotones();
                    mostrarPrioridadModal(data.servicio_nombre);
                    return;
                }
                // Turno creado exitosamente → imprimir directamente
                if (data.turno) {
                    mostrarLoading('Turno generado: ' + data.turno.codigo_completo, 'Enviando a impresora...');
                    imprimirTicket(data.turno);
                } else {
                    ocultarLoading();
                    desbloquearBotones();
                    mostrarModal(data.message);
                }
            } else {
                ocultarLoading();
                desbloquearBotones();
                mostrarModal(data.message || 'Error al procesar la solicitud');
            }
        }

        function manejarErrorFetch(error) {
            if (error.name === 'AbortError' || error.message === 'Failed to fetch') {
                // Si hay una solicitud guardada y aún no se reintentó, reintentar automáticamente una vez
                if (!reintentoAutomatico && ultimaSolicitud) {
                    reintentoAutomatico = true;
                    console.log('⏳ Timeout del servidor, reintentando automáticamente...');
                    mostrarLoading('Reintentando...', 'El servidor está tardando, un momento por favor');
                    // Desbloquear para que el reintento funcione
                    procesandoSolicitud = false;
                    setTimeout(() => {
                        ultimaSolicitud();
                    }, 500);
                    return;
                }
                // Ya se reintentó una vez, mostrar error al usuario
                reintentoAutomatico = false;
                ocultarLoading();
                mostrarError('El servidor está tardando demasiado. ¿Desea intentar de nuevo?');
            } else {
                reintentoAutomatico = false;
                ocultarLoading();
                mostrarError('Error de conexión con el servidor. ¿Desea intentar de nuevo?');
            }
        }

        // ============================================
        // FUNCIONES DE SELECCIÓN DE SERVICIO
        // ============================================
        function navegarASubservicios(servicioId) {
            if (navigator.vibrate) navigator.vibrate(30);
            window.location.href = `{{ route('turnos.menu') }}?servicio_id=${servicioId}`;
        }

        function seleccionarServicio(servicioId, nombreServicio) {
            if (procesandoSolicitud) return;
            bloquearBotones();
            mostrarLoading('Generando turno...', nombreServicio);
            if (navigator.vibrate) navigator.vibrate(30);

            // Guardar para reintentos
            ultimaSolicitud = () => seleccionarServicio(servicioId, nombreServicio);

            fetchConTimeout('{{ route('turnos.seleccionar') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': currentCsrfToken
                },
                body: JSON.stringify({ servicio_id: servicioId })
            })
            .then(response => {
                if (response.status === 419) {
                    // CSRF expirado - renovar y reintentar
                    throw new Error('CSRF_EXPIRED');
                }
                return response.json();
            })
            .then(data => procesarRespuestaTurno(data))
            .catch(error => {
                if (error.message === 'CSRF_EXPIRED') {
                    ocultarLoading();
                    mostrarError('La sesión expiró. Reintente por favor.');
                    // Intentar renovar CSRF para el siguiente intento
                    fetch('/turnos/menu', { method: 'GET', headers: { 'Accept': 'text/html' } })
                        .then(r => r.text())
                        .then(html => {
                            const m = html.match(/meta name="csrf-token" content="([^"]+)"/);
                            if (m && m[1]) currentCsrfToken = m[1];
                        }).catch(() => {});
                    return;
                }
                manejarErrorFetch(error);
            });
        }

        function seleccionarSubservicio(subservicioId, nombreSubservicio) {
            if (procesandoSolicitud) return;
            bloquearBotones();
            mostrarLoading('Generando turno...', nombreSubservicio);
            if (navigator.vibrate) navigator.vibrate(30);

            ultimaSolicitud = () => seleccionarSubservicio(subservicioId, nombreSubservicio);

            fetchConTimeout('{{ route('turnos.seleccionar') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': currentCsrfToken
                },
                body: JSON.stringify({ subservicio_id: subservicioId })
            })
            .then(response => {
                if (response.status === 419) throw new Error('CSRF_EXPIRED');
                return response.json();
            })
            .then(data => procesarRespuestaTurno(data))
            .catch(error => {
                if (error.message === 'CSRF_EXPIRED') {
                    ocultarLoading();
                    mostrarError('La sesión expiró. Reintente por favor.');
                    fetch('/turnos/menu', { method: 'GET', headers: { 'Accept': 'text/html' } })
                        .then(r => r.text())
                        .then(html => {
                            const m = html.match(/meta name="csrf-token" content="([^"]+)"/);
                            if (m && m[1]) currentCsrfToken = m[1];
                        }).catch(() => {});
                    return;
                }
                manejarErrorFetch(error);
            });
        }

        // ============================================
        // PRIORIDAD
        // ============================================
        function mostrarPrioridadModal(nombreServicio) {
            document.getElementById('prioridadServicioNombre').textContent = `Servicio: ${nombreServicio}`;
            document.getElementById('prioridadModal').style.display = 'flex';
        }

        function cerrarPrioridadModal() {
            document.getElementById('prioridadModal').style.display = 'none';
            servicioSeleccionadoId = null;
            servicioSeleccionadoNombre = '';
        }

        function seleccionarPrioridad(prioridad) {
            if (procesandoSolicitud) return;
            bloquearBotones();
            cerrarPrioridadModal();
            mostrarLoading('Generando turno...', servicioSeleccionadoNombre);
            if (navigator.vibrate) navigator.vibrate(30);
            
            if (!servicioSeleccionadoId) {
                ocultarLoading();
                desbloquearBotones();
                mostrarModal('Error: No hay servicio seleccionado');
                return;
            }

            const sId = servicioSeleccionadoId;
            const sNombre = servicioSeleccionadoNombre;
            ultimaSolicitud = () => {
                servicioSeleccionadoId = sId;
                servicioSeleccionadoNombre = sNombre;
                seleccionarPrioridad(prioridad);
            };

            fetchConTimeout('{{ route('turnos.crear-con-prioridad') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': currentCsrfToken
                },
                body: JSON.stringify({
                    servicio_id: sId,
                    prioridad: prioridad
                })
            })
            .then(response => {
                if (response.status === 419) throw new Error('CSRF_EXPIRED');
                return response.json();
            })
            .then(data => procesarRespuestaTurno(data))
            .catch(error => {
                if (error.message === 'CSRF_EXPIRED') {
                    ocultarLoading();
                    mostrarError('La sesión expiró. Reintente por favor.');
                    fetch('/turnos/menu', { method: 'GET', headers: { 'Accept': 'text/html' } })
                        .then(r => r.text())
                        .then(html => {
                            const m = html.match(/meta name="csrf-token" content="([^"]+)"/);
                            if (m && m[1]) currentCsrfToken = m[1];
                        }).catch(() => {});
                    return;
                }
                manejarErrorFetch(error);
            });
        }

        // ============================================
        // MODALES
        // ============================================
        function mostrarModal(mensaje) {
            document.getElementById('confirmMessage').textContent = mensaje;
            document.getElementById('confirmModal').style.display = 'flex';
        }

        function cerrarModal() {
            document.getElementById('confirmModal').style.display = 'none';
        }

        // ============================================
        // EVENTOS DE INTERFAZ
        // ============================================
        document.querySelectorAll('button').forEach(button => {
            button.addEventListener('click', function() {
                if (navigator.vibrate) navigator.vibrate(30);
            });
        });

        // Prevenir zoom en dispositivos táctiles
        document.addEventListener('touchstart', function(event) {
            if (event.touches.length > 1) {
                event.preventDefault();
            }
        });

        let lastTouchEnd = 0;
        document.addEventListener('touchend', function(event) {
            const now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);

        // ============================================
        // AUTO-RETORNO A INICIO POR INACTIVIDAD (3 min)
        // ============================================
        let inactividadTimer = null;
        function resetearInactividad() {
            if (inactividadTimer) clearTimeout(inactividadTimer);
            inactividadTimer = setTimeout(function() {
                // Solo redirigir si no hay proceso en curso
                if (!procesandoSolicitud) {
                    window.location.href = '{{ route('turnos.inicio') }}';
                }
            }, 3 * 60 * 1000); // 3 minutos
        }
        document.addEventListener('touchstart', resetearInactividad);
        document.addEventListener('click', resetearInactividad);
        resetearInactividad();
    </script>
</body>
</html>
