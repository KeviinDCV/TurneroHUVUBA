@php
    // Lo que la pantalla dice de sí misma: la unidad (PANEL_UNIDAD_NOMBRE) y a quién acudir (PANEL_SOPORTE_CONTACTO, opcional).
    $unidad = config('panel.unidad_nombre', 'Turnero');
    $contactoSoporte = config('panel.soporte.contacto');

    // Volvió por un 419 (bootstrap/app.php). Si aun así el navegador no trae la cookie de sesión, no la está guardando:
    // casi siempre es una cookie «Secure» de una visita por https:// que bloquea la de http://.
    $paginaCaducada = request()->boolean('caducada');
    $sinCookie = $paginaCaducada && !request()->cookies->has(config('session.cookie'));

    $hayError = $errors->any();
    // Solo un usuario o contraseña equivocados (o vacíos) marcan los campos; cuenta desactivada o demasiados intentos, no.
    $camposInvalidos = $errors->hasAny(['usuario', 'password']);
    // Con el usuario ya escrito (un error, la página caducada) se sigue en la contraseña.
    $focoEnClave = filled(old('usuario'));

    // El talón del ticket lleva la fecha y la hora del servidor, como el ticket que imprime el atril.
    $ahora = now()->locale('es');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión · Turnero de {{ $unidad }} · HUV</title>
    @include('components.favicon')
    <link rel="preload" href="{{ asset('fonts/InterVariable.woff2') }}" as="font" type="font/woff2" crossorigin>

    <style>
        @font-face {
            font-family: 'InterVariable';
            font-style: normal;
            font-weight: 100 900;
            font-display: swap;
            src: url('{{ asset('fonts/InterVariable.woff2') }}') format('woff2');
        }

        :root {
            --azul: #064b9e;
            --azul-hover: #053f87;
            --azul-activo: #04356f;
            --tinta: #0f2747;
            --tinta-2: #23344f;
            --tinta-suave: #4a5d78;
            --tinta-tenue: #56698a;
            --borde: #7d91ad;
            --borde-hover: #5f7698;
            --corte: #c9d5e6;
            --foco: rgba(6, 75, 158, 0.18);
            --error: #b42318;
            --muesca: 12px;   /* radio de las muescas del corte del ticket */
        }

        *, *::before, *::after { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }
        body {
            margin: 0;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            font-family: 'InterVariable', 'Segoe UI', system-ui, sans-serif;
            font-feature-settings: 'cv11', 'ss01';
            color: var(--tinta);
            -webkit-font-smoothing: antialiased;
            background:
                radial-gradient(1200px 620px at 50% -14%, #ffffff 0%, rgba(255, 255, 255, 0) 62%),
                linear-gradient(180deg, #eef3fa 0%, #e6edf6 100%);
            background-attachment: fixed;
        }
        ::selection { background: rgba(6, 75, 158, 0.18); color: var(--tinta); }

        .acceso {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px 20px;
        }
        .acceso__bloque {
            width: 100%;
            max-width: 400px;
            margin-top: -3vh;   /* algo por encima del centro: ahí cae la vista */
        }

        /* ===================== El ticket =====================
           Dos piezas blancas (talón y cuerpo) con media muesca en las esquinas donde se tocan: juntas forman el corte del
           ticket. La sombra va en el contenedor con drop-shadow, para que siga la forma recortada. */
        .ticket {
            filter: drop-shadow(0 1px 1px rgba(15, 39, 71, 0.07)) drop-shadow(0 22px 28px rgba(15, 39, 71, 0.13));
            animation: imprimir 0.55s cubic-bezier(0.16, 1, 0.3, 1);
        }
        /* Solo un leve ascenso, como el ticket que sale del atril; el contenido nunca depende de la animación para verse. */
        @keyframes imprimir { from { transform: translateY(8px); } }

        .ticket__talon,
        .ticket__cuerpo { position: relative; background: #fff; }
        .ticket__talon {
            padding: 30px 34px 22px;
            border-radius: 18px 18px 0 0;
            text-align: center;
            -webkit-mask:
                radial-gradient(circle var(--muesca) at 0 100%, #0000 98%, #000) left / 51% 100% no-repeat,
                radial-gradient(circle var(--muesca) at 100% 100%, #0000 98%, #000) right / 51% 100% no-repeat;
            mask:
                radial-gradient(circle var(--muesca) at 0 100%, #0000 98%, #000) left / 51% 100% no-repeat,
                radial-gradient(circle var(--muesca) at 100% 100%, #0000 98%, #000) right / 51% 100% no-repeat;
        }
        .ticket__cuerpo {
            padding: 28px 34px 32px;
            border-radius: 0 0 18px 18px;
            -webkit-mask:
                radial-gradient(circle var(--muesca) at 0 0, #0000 98%, #000) left / 51% 100% no-repeat,
                radial-gradient(circle var(--muesca) at 100% 0, #0000 98%, #000) right / 51% 100% no-repeat;
            mask:
                radial-gradient(circle var(--muesca) at 0 0, #0000 98%, #000) left / 51% 100% no-repeat,
                radial-gradient(circle var(--muesca) at 100% 0, #0000 98%, #000) right / 51% 100% no-repeat;
        }
        /* La línea de corte: trazos que corren mientras se entra, como un ticket que se desprende. */
        .ticket__corte {
            position: absolute;
            top: -1px;
            left: calc(var(--muesca) + 10px);
            width: calc(100% - 2 * (var(--muesca) + 10px));
            height: 2px;
            overflow: visible;
            stroke: var(--corte);
            stroke-width: 2;
            stroke-dasharray: 7 6;
        }
        .ticket[data-entrando] .ticket__corte { animation: rasgar 0.5s linear infinite; }
        @keyframes rasgar { to { stroke-dashoffset: -13; } }

        .ticket__logo { display: block; height: 70px; width: auto; margin: 0 auto 14px; }
        .ticket__unidad {
            margin: 0;
            font-size: 28px;
            line-height: 1.15;
            font-weight: 650;
            letter-spacing: -0.02em;
            color: var(--tinta);
            text-wrap: balance;
        }
        .ticket__turnero {
            margin: 6px 0 0;
            font-size: 14.5px;
            line-height: 1.45;
            color: var(--tinta-suave);
            text-wrap: balance;
        }
        .ticket__sello {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-top: 18px;
            font-size: 12.5px;
            color: var(--tinta-tenue);
            font-variant-numeric: tabular-nums;
            letter-spacing: 0.02em;
        }

        /* ===================== Avisos ===================== */
        .aviso {
            margin: 0 0 20px;
            padding: 12px 14px;
            border-radius: 10px;
            border: 1px solid;
            font-size: 14px;
            line-height: 1.5;
            text-wrap: pretty;
        }
        .aviso strong { display: block; font-weight: 600; }
        .aviso p { margin: 2px 0 0; }
        .aviso--error { background: #fdf0ef; border-color: #f1c6c2; color: #8a1c1c; }
        .aviso--info { background: #eaf1fb; border-color: #c6d7f0; color: #173a6a; }
        .aviso--alerta { background: #fff6e6; border-color: #f0d29b; color: #6b4400; }
        .aviso__tecnico { margin-top: 8px !important; font-size: 12.5px; opacity: 0.9; }
        .aviso__tecnico a { color: inherit; text-underline-offset: 2px; }

        /* ===================== Campos ===================== */
        .campo { margin-bottom: 18px; }
        .campo__etiqueta {
            display: block;
            margin-bottom: 7px;
            font-size: 13.5px;
            font-weight: 600;
            color: var(--tinta-2);
        }
        .campo__caja { position: relative; }
        .campo__entrada {
            display: block;
            width: 100%;
            height: 48px;
            padding: 0 14px;
            border: 1px solid var(--borde);
            border-radius: 10px;
            background: #fff;
            font: inherit;
            font-size: 16px;
            color: var(--tinta);
            caret-color: var(--azul);
            box-shadow: 0 1px 2px rgba(15, 39, 71, 0.06);
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .campo__entrada--clave { padding-right: 52px; }
        .campo__entrada:hover { border-color: var(--borde-hover); }
        .campo__entrada:focus { outline: none; border-color: var(--azul); box-shadow: 0 0 0 3px var(--foco); }
        .campo__entrada:-webkit-autofill,
        .campo__entrada:-webkit-autofill:hover {
            -webkit-text-fill-color: var(--tinta);
            box-shadow: inset 0 0 0 40px #fff, 0 1px 2px rgba(15, 39, 71, 0.06);
            transition: background-color 9999s ease 0s;
        }
        .campo__entrada:-webkit-autofill:focus { box-shadow: inset 0 0 0 40px #fff, 0 0 0 3px var(--foco); }
        .campo__entrada[aria-invalid="true"] { border-color: var(--error); }
        .campo__entrada[aria-invalid="true"]:focus { box-shadow: 0 0 0 3px rgba(180, 35, 24, 0.16); }

        .campo__ver {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 40px;
            height: 40px;
            display: grid;
            place-items: center;
            padding: 0;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: #5b6f8c;
            cursor: pointer;
            transition: color 0.15s ease, background-color 0.15s ease;
        }
        .campo__ver:hover { color: var(--azul); background: #eef3fa; }
        .campo__ver:focus-visible { outline: 2px solid var(--azul); outline-offset: 0; color: var(--azul); }
        .campo__ver svg { width: 20px; height: 20px; }
        .campo__ver svg[hidden] { display: none; }

        .campo__mayus {
            display: flex;
            align-items: center;
            gap: 6px;
            margin: 8px 0 0;
            font-size: 13px;
            font-weight: 500;
            color: #7a4b00;
        }
        .campo__mayus[hidden] { display: none; }
        .campo__mayus svg { width: 15px; height: 15px; flex: none; }

        /* ===================== Botón ===================== */
        .entrar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            height: 48px;
            margin-top: 26px;
            border: 0;
            border-radius: 10px;
            background: var(--azul);
            color: #fff;
            font: inherit;
            font-size: 15.5px;
            font-weight: 600;
            letter-spacing: 0.005em;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(15, 39, 71, 0.14), 0 8px 18px -10px rgba(6, 75, 158, 0.55);
            transition: background-color 0.15s ease, box-shadow 0.15s ease;
        }
        .entrar:hover { background: var(--azul-hover); }
        .entrar:active { background: var(--azul-activo); box-shadow: 0 1px 2px rgba(15, 39, 71, 0.14); }
        .entrar:focus-visible { outline: 3px solid rgba(6, 75, 158, 0.35); outline-offset: 3px; }
        .entrar[aria-busy="true"] { background: #2f63a6; cursor: progress; box-shadow: none; }
        .entrar__giro {
            display: none;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.35);
            border-top-color: #fff;
            border-radius: 50%;
            animation: girar 0.8s linear infinite;
        }
        .entrar[aria-busy="true"] .entrar__giro { display: block; }
        @keyframes girar { to { transform: rotate(360deg); } }

        /* ===================== Ayuda y pie ===================== */
        .ayuda { margin-top: 24px; font-size: 13.5px; line-height: 1.55; color: var(--tinta-suave); text-align: center; }
        .ayuda p { margin: 0; text-wrap: balance; }

        .pie {
            padding: 16px 20px 24px;
            text-align: center;
            font-size: 12.5px;
            line-height: 1.5;
            color: var(--tinta-tenue);
        }

        @media (max-height: 700px) {
            .acceso { align-items: flex-start; padding-top: 24px; }
            .acceso__bloque { margin-top: 0; }
            .ticket__logo { height: 54px; margin-bottom: 10px; }
            .ticket__talon { padding-top: 22px; }
        }
        @media (max-width: 420px) {
            .ticket__talon { padding-left: 24px; padding-right: 24px; }
            .ticket__cuerpo { padding-left: 24px; padding-right: 24px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .ticket, .ticket[data-entrando] .ticket__corte { animation: none; }
            .entrar__giro { animation-duration: 2.4s; }
            .campo__entrada, .campo__ver, .entrar { transition: none; }
        }
    </style>
</head>
<body>
    <main class="acceso">
        <div class="acceso__bloque">
            <div class="ticket" id="ticket">
                <header class="ticket__talon">
                    <img src="{{ asset('images/logo.png') }}" alt="Hospital Universitario del Valle" class="ticket__logo" width="107" height="70">
                    <h1 class="ticket__unidad">{{ $unidad }}</h1>
                    <p class="ticket__turnero">Turnero del Hospital Universitario del Valle</p>
                    <p class="ticket__sello">
                        <span>{{ ucfirst($ahora->isoFormat('dddd D [de] MMMM')) }}</span>
                        <time id="horaTicket" datetime="{{ $ahora->toIso8601String() }}">{{ $ahora->format('g:i') }} {{ $ahora->format('A') === 'AM' ? 'a. m.' : 'p. m.' }}</time>
                    </p>
                </header>

                <div class="ticket__cuerpo">
                    <svg class="ticket__corte" aria-hidden="true" focusable="false"><line x1="0" y1="1" x2="100%" y2="1"/></svg>

                    <form method="POST" action="{{ route('admin.login.post') }}" id="formLogin">
                        @csrf

                        @if ($sinCookie)
                            <div role="alert" class="aviso aviso--alerta" id="avisoLogin">
                                <strong>Este navegador no está guardando la sesión</strong>
                                <p>Por eso no puede entrar. Pida ayuda a sistemas{{ $contactoSoporte ? ' (' . $contactoSoporte . ')' : '' }} para borrar los datos de este sitio en el navegador.</p>
                                <p class="aviso__tecnico">Para sistemas: una cookie «Secure» de una visita por https bloquea la de http. Entre una vez por
                                    <a href="https://{{ request()->getHost() }}/login">https://{{ request()->getHost() }}/login</a> o borre las cookies de este sitio.</p>
                            </div>
                        @elseif ($hayError)
                            <div role="alert" class="aviso aviso--error" id="avisoLogin">{{ $errors->first() }}</div>
                        @elseif ($paginaCaducada)
                            <div role="status" class="aviso aviso--info" id="avisoLogin">La página estuvo abierta mucho tiempo y caducó. Escriba de nuevo su contraseña para entrar.</div>
                        @elseif (session('info'))
                            <div role="status" class="aviso aviso--info" id="avisoLogin">{{ session('info') }}</div>
                        @endif

                        <div class="campo">
                            <label for="usuario" class="campo__etiqueta">Usuario</label>
                            <input type="text" name="usuario" id="usuario" class="campo__entrada"
                                   value="{{ old('usuario') }}" autocomplete="username" autocapitalize="none" autocorrect="off" spellcheck="false"
                                   required @if (!$focoEnClave) autofocus @endif
                                   @if ($hayError) aria-describedby="avisoLogin" @endif @if ($camposInvalidos) aria-invalid="true" @endif>
                        </div>

                        <div class="campo">
                            <label for="password" class="campo__etiqueta">Contraseña</label>
                            <div class="campo__caja">
                                <input type="password" name="password" id="password" class="campo__entrada campo__entrada--clave"
                                       autocomplete="current-password" required @if ($focoEnClave) autofocus @endif
                                       aria-describedby="avisoMayus{{ $hayError ? ' avisoLogin' : '' }}" @if ($camposInvalidos) aria-invalid="true" @endif>
                                <button type="button" class="campo__ver" id="verClave" aria-label="Mostrar contraseña" aria-pressed="false">
                                    <svg data-icono="ver" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    <svg data-icono="ocultar" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" hidden>
                                        <path d="M10.6 5.6A9.6 9.6 0 0 1 12 5.5C18 5.5 21.5 12 21.5 12a16.5 16.5 0 0 1-2.9 3.7M6.3 6.9C3.9 8.6 2.5 12 2.5 12S6 18.5 12 18.5c1.6 0 3-.4 4.2-1.1M9.9 9.9a3 3 0 0 0 4.2 4.2M3 3l18 18"/>
                                    </svg>
                                </button>
                            </div>
                            <p class="campo__mayus" id="avisoMayus" aria-live="polite" hidden>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4 4 12h4v4h8v-4h4L12 4Z"/><path d="M8 20h8"/></svg>
                                Bloq&nbsp;Mayús está activado
                            </p>
                        </div>

                        <button type="submit" class="entrar" id="botonEntrar">
                            <span class="entrar__giro" aria-hidden="true"></span>
                            <span class="entrar__texto">Entrar</span>
                        </button>
                    </form>
                </div>
            </div>

            <div class="ayuda">
                <p>¿No puede entrar? Pida a un administrador que restablezca su contraseña{{ $contactoSoporte ? ', o escriba a soporte: ' . $contactoSoporte : '' }}.</p>
            </div>
        </div>
    </main>

    <footer class="pie">Hospital Universitario del Valle «Evaristo García» E.S.E. · Innovación y Desarrollo</footer>

    <script>
        (function () {
            var ticket = document.getElementById('ticket');
            var form = document.getElementById('formLogin');
            var boton = document.getElementById('botonEntrar');
            var texto = boton.querySelector('.entrar__texto');
            var clave = document.getElementById('password');
            var ver = document.getElementById('verClave');
            var mayus = document.getElementById('avisoMayus');
            var hora = document.getElementById('horaTicket');

            // Mostrar u ocultar la contraseña, con el estado anunciado.
            ver.addEventListener('click', function () {
                var visible = clave.type === 'password';
                clave.type = visible ? 'text' : 'password';
                ver.setAttribute('aria-pressed', String(visible));
                ver.setAttribute('aria-label', visible ? 'Ocultar contraseña' : 'Mostrar contraseña');
                ver.querySelector('[data-icono="ver"]').toggleAttribute('hidden', visible);
                ver.querySelector('[data-icono="ocultar"]').toggleAttribute('hidden', !visible);
                clave.focus();
            });

            // Bloq Mayús: la causa más común de «contraseña incorrecta».
            function revisarMayus(e) {
                if (e.getModifierState) mayus.hidden = !e.getModifierState('CapsLock');
            }
            clave.addEventListener('keydown', revisarMayus);
            clave.addEventListener('keyup', revisarMayus);
            clave.addEventListener('blur', function () { mayus.hidden = true; });

            // La hora del talón sigue la del servidor (el reloj del equipo puede estar mal), sumando lo que pasa en la página.
            var base = Date.parse(hora.getAttribute('datetime'));
            var inicio = performance.now();
            function ponerHora() {
                var d = new Date(base + (performance.now() - inicio));
                var bogota = new Date(d.getTime() + (d.getTimezoneOffset() - 300) * 60000);   // Colombia: UTC−5, sin horario de verano
                var h = bogota.getHours(), m = bogota.getMinutes();
                hora.textContent = ((h % 12) || 12) + ':' + (m < 10 ? '0' : '') + m + (h < 12 ? ' a. m.' : ' p. m.');
            }
            if (!isNaN(base)) setInterval(ponerHora, 15000);

            // Un solo envío: con el servidor lento, un segundo clic o Enter mandaría el formulario dos veces.
            var reactivar = null;
            form.addEventListener('submit', function (e) {
                if (boton.getAttribute('aria-busy') === 'true') { e.preventDefault(); return; }
                boton.setAttribute('aria-busy', 'true');
                texto.textContent = 'Entrando…';
                ticket.setAttribute('data-entrando', '');
                // Si la red se cae y la página nunca cambia, el botón vuelve a servir.
                reactivar = setTimeout(restaurar, 20000);
            });
            function restaurar() {
                clearTimeout(reactivar);
                boton.removeAttribute('aria-busy');
                texto.textContent = 'Entrar';
                ticket.removeAttribute('data-entrando');
            }

            // Al volver con «Atrás» desde la caché del navegador, se recarga para tener un token nuevo.
            window.addEventListener('pageshow', function (event) {
                if (event.persisted) window.location.reload();
            });

            // El aviso de página caducada se muestra una vez: al recargar ya no debe aparecer.
            if (/[?&]caducada=/.test(window.location.search)) {
                window.history.replaceState(null, '', window.location.pathname);
            }

            // Si en otra pestaña ya se inició sesión, al volver a esta se entra directo.
            document.addEventListener('visibilitychange', function () {
                if (document.hidden) return;
                fetch('/api/auth-check', { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) { if (data.authenticated) window.location.href = data.redirect_url; })
                    .catch(function () {});
            });
        })();
    </script>
</body>
</html>
