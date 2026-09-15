<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preload" href="{{ asset('fonts/InterVariable.woff2') }}" as="font" type="font/woff2" crossorigin>
    <title>Generar turno · {{ config('app.name', 'Turnero HUV') }}</title>
    @include('components.favicon')

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @font-face {
            font-family: 'Inter';
            src: url('{{ asset('fonts/InterVariable.woff2') }}') format('woff2');
            font-weight: 100 900; font-style: normal; font-display: block;
        }
        :root { --azul: #064b9e; --azul-hover: #053d7a; --tinta: #0f1f3d; --mudo: #5b6b82; --linea: #d9e1ec; --fondo: #f3f6fb; }
        * { -webkit-tap-highlight-color: transparent; }
        html, body { height: 100%; }
        body { font-family: 'Inter', 'Segoe UI', system-ui, sans-serif; font-feature-settings: 'cv05'; color: var(--tinta); background: var(--fondo); overflow: hidden; user-select: none; -webkit-user-select: none; }

        /* Kiosco del asesor: barra con la identidad y la hora; toda la pantalla lleva al menú de servicios */
        .kiosco { height: 100vh; height: 100dvh; display: grid; grid-template-rows: auto minmax(0, 1fr); color: inherit; text-decoration: none; cursor: pointer; }
        .barra { display: flex; align-items: center; gap: clamp(.8rem, 1.6vw, 1.6rem); padding: clamp(.7rem, 1.6vh, 1.2rem) clamp(1.25rem, 3vw, 3rem); background: #fff; box-shadow: inset 0 -1px 0 var(--linea); }
        .barra img { height: clamp(46px, 7vh, 80px); width: auto; flex: none; }
        .barra-nombre { font-size: clamp(1rem, 1.6vw, 1.5rem); font-weight: 700; line-height: 1.15; color: var(--azul); }
        .barra-unidad { margin-top: .15rem; font-size: clamp(.8rem, 1.1vw, 1.05rem); color: var(--mudo); }
        .barra-reloj { margin-left: auto; text-align: right; }
        .barra-hora { font-size: clamp(1.6rem, 3vw, 2.8rem); font-weight: 700; line-height: 1; letter-spacing: -.02em; font-variant-numeric: tabular-nums; }
        .barra-fecha { margin-top: .25rem; font-size: clamp(.8rem, 1.05vw, 1.05rem); color: var(--mudo); }

        .bienvenida { min-height: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 4vh 6vw; text-align: center; }
        .antetitulo { font-size: clamp(.9rem, 1.3vw, 1.2rem); font-weight: 700; letter-spacing: .18em; text-transform: uppercase; color: var(--azul); }
        .bienvenida h1 { margin-top: 1.4vh; font-size: clamp(2.6rem, 6.4vw, 6rem); font-weight: 800; line-height: 1.02; letter-spacing: -.03em; }
        .bienvenida .sub { margin-top: 2vh; font-size: clamp(1.2rem, 2.2vw, 2rem); color: var(--mudo); }
        .comenzar { position: relative; margin-top: 5vh; display: inline-flex; align-items: center; gap: 1rem; height: clamp(84px, 11vh, 124px); padding: 0 clamp(2.5rem, 4.5vw, 4.5rem);
                    border-radius: 999px; background: var(--azul); color: #fff; font-size: clamp(1.4rem, 2.5vw, 2.3rem); font-weight: 700; box-shadow: 0 18px 40px -18px rgba(6, 75, 158, .6); }
        .comenzar svg { width: 1.1em; height: 1.1em; }
        .kiosco:active .comenzar { background: var(--azul-hover); }

        .firma { position: fixed; right: 1rem; bottom: .75rem; font-size: .75rem; color: #9aa6b8; }

        @media (orientation: portrait) {
            .bienvenida h1 { font-size: clamp(2.6rem, 9vw, 6rem); }
            .bienvenida .sub { font-size: clamp(1.2rem, 3.4vw, 2rem); }
        }
    </style>
</head>
<body>
    @php
        $ahoraKiosco = \Carbon\Carbon::now('America/Bogota');
        $fechaKiosco = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'][$ahoraKiosco->dayOfWeek] . ' ' . $ahoraKiosco->day . ' de '
            . ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'][$ahoraKiosco->month - 1];
    @endphp
    <a href="{{ route('turnos.menu') }}" class="kiosco" aria-label="Toque la pantalla para elegir el servicio">
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

        <main class="bienvenida">
            <div class="antetitulo">Turnero</div>
            <h1>Generar turno</h1>
            <p class="sub">Toque la pantalla para elegir el servicio e imprimir el turno.</p>
            <span class="comenzar">
                Elegir servicio
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 5l7 7-7 7M4 12h15"/></svg>
            </span>
        </main>
    </a>
    <p class="firma">Turnero HUV · Innovación y desarrollo</p>

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

        // Vibración corta al tocar (en equipos que la tienen)
        document.addEventListener('click', function () {
            if (navigator.vibrate) navigator.vibrate(50);
        });

        // Prevenir zoom en pantallas táctiles
        document.addEventListener('touchstart', function (event) {
            if (event.touches.length > 1) event.preventDefault();
        });
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function (event) {
            const now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) event.preventDefault();
            lastTouchEnd = now;
        }, false);
    </script>
</body>
</html>
