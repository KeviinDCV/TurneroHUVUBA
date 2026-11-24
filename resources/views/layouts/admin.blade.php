@php
    // Cabecera de cada página: el título de la vista y, debajo, dónde y cuándo (la unidad y la fecha de hoy).
    $unidadPanel = config('panel.unidad_nombre', 'Turnero HUV');
    $hoyPanel = now()->locale('es');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- La letra se pide antes que todo lo demás: así ya está cuando se pinta la página (ver @font-face más abajo) --}}
    <link rel="preload" href="{{ asset('fonts/InterVariable.woff2') }}" as="font" type="font/woff2" crossorigin>
    <title>@yield('title', 'Turnero HUV') · {{ $unidadPanel }} · Turnero HUV</title>
    @include('components.favicon')
    {{-- Toasts: sileo.js (port sin React de Sileo). Antes que Alpine, para que las vistas ya tengan window.sileo --}}
    <script src="{{ asset('js/sileo.js') }}?v={{ is_file(public_path('js/sileo.js')) ? filemtime(public_path('js/sileo.js')) : 0 }}" defer></script>
    {{-- Desplegables propios en lugar de la lista nativa del navegador (se montan sobre cada <select>) --}}
    <script src="{{ asset('js/desplegable.js') }}?v={{ is_file(public_path('js/desplegable.js')) ? filemtime(public_path('js/desplegable.js')) : 0 }}" defer></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        /* ================= Base heredada que usan las vistas ================= */
        .modal-backdrop {
            background-color: rgba(15, 39, 71, 0.32); position: fixed; inset: 0; display: flex;
            justify-content: center; align-items: center; z-index: 50;
            backdrop-filter: blur(2px); -webkit-backdrop-filter: blur(2px);
        }
        .modal-content { max-height: 90vh; overflow-y: auto; }
        .modal-overlay { background-color: rgba(15, 39, 71, 0.32) !important; backdrop-filter: blur(2px) !important; -webkit-backdrop-filter: blur(2px) !important; }

        :root { --hospital-blue: #064b9e; --hospital-blue-hover: #053f87; --hospital-blue-light: #eaf1fb; }
        .bg-hospital-blue { background-color: var(--hospital-blue); }
        .text-hospital-blue { color: var(--hospital-blue); }
        .border-hospital-blue { border-color: var(--hospital-blue); }
        .hover\:bg-hospital-blue-hover:hover { background-color: var(--hospital-blue-hover); }
        .bg-hospital-blue-light { background-color: var(--hospital-blue-light); }
        .transition-all { transition: all 0.3s ease; }

        .tab-content { display: block; }
        .tab-content.hidden { display: none; }
        .file-preview { max-width: 100px; max-height: 100px; object-fit: cover; }
        .video-preview { max-width: 100px; max-height: 100px; }
        .sortable-item { cursor: move; }
        .sortable-item:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(15, 39, 71, 0.12); }
        [x-cloak] { display: none !important; }
        * { box-sizing: border-box; }

        /* Enlace para saltar el menú (accesibilidad - WCAG 2.4.1) */
        .skip-link {
            position: absolute; left: -9999px; top: auto; z-index: 60; background: #fff; color: #0f2747;
            padding: .5rem 1rem; border-radius: .625rem; font-weight: 600; box-shadow: 0 2px 8px rgba(15, 39, 71, .2);
        }
        .skip-link:focus { left: 1rem; top: 1rem; }

        /* Estilo sin bordes en cards, modales y botones de Tailwind (ver historia en git): los separadores direccionales,
           los campos y las tablas conservan los suyos. */
        div.border.border-gray-200, div.border.border-gray-100 { border-color: transparent; }
        div.border.border-blue-200, div.border.border-green-200, div.border.border-yellow-200,
        div.border.border-red-200, div.border.border-purple-200 { border-color: transparent; }
        div.overflow-y-auto.border, div.overflow-y-auto.border.border-gray-300 { border-color: transparent; }
        div.border-2.border-gray-200:not(:hover), label.border-2.border-gray-200:not(:hover), label.border.border-gray-200:not(:hover) { border-color: transparent; }
        button.border.border-gray-300:not(.tab-button), button.border.border-gray-200:not(.tab-button),
        button.border.border-hospital-blue:not(.tab-button), a.border.border-gray-300:not(.tab-button) { border-color: transparent; }
        button.border.border-gray-300:not(.tab-button), a.border.border-gray-300:not(.tab-button) { background-color: #eef3fa; }
        button.border.border-gray-300:not(.tab-button):hover, a.border.border-gray-300:not(.tab-button):hover { background-color: #e3ebf7; }

        @yield('styles')
    </style>
    <style>
        /* ================= «La sala» (2026-11): el lenguaje del Login llevado al panel =================
           Ver DESIGN.md. Fondo claro con tinte azul, superficies blancas, azul institucional solo en lo activo y en
           la acción principal, InterVariable propia, cifras tabulares. Sin clases nuevas de Tailwind. */
        :root {
            --azul: #064b9e; --azul-hover: #053f87; --azul-activo: #04356f; --azul-claro: #eaf1fb; --azul-claro-2: #e3ebf7;
            --tinta: #0f2747; --tinta-2: #23344f; --tinta-suave: #4a5d78; --tinta-tenue: #56698a; --icono: #5b6f8c;
            --borde: #7d91ad; --borde-hover: #5f7698; --linea: #e3e9f2; --linea-suave: #edf1f6; --corte: #a7b8d0;
            --foco: rgba(6, 75, 158, .18);
            --ok: #0b5a37; --ok-fondo: #e7f4ec; --info: #173a6a; --info-fondo: #eaf1fb;
            --alerta: #6b4400; --alerta-fondo: #fff4df; --error: #b42318; --error-texto: #8a1c1c; --error-fondo: #fdf0ef;
            --alerta-tinta: #7a4b00;   /* ámbar de texto en línea (sobre blanco); chips y avisos usan --alerta */
            --info-borde: #c6d7f0; --ok-borde: #bcdcc8; --alerta-borde: #f0d29b; --error-borde: #f1c6c2;
            --roce: 0 1px 2px rgba(15, 39, 71, .06);
            --filo: drop-shadow(0 0 .6px rgba(15, 39, 71, .3));
            --riel-ancho: 5.5rem;   /* 88 px */
            --riel-margen: 1rem;
        }

        /* 1. Letra: Inter de verdad (InterVariable propia), con l de cola, a de un piso y cifras abiertas. */
        @font-face {
            font-family: 'Inter';
            src: url('{{ asset('fonts/InterVariable.woff2') }}') format('woff2');
            /* block, no swap: con swap se pintaba primero con Segoe UI y al llegar Inter las letras "crecían" */
            font-weight: 100 900; font-style: normal; font-display: block;
        }
        html { font-feature-settings: 'cv05', 'cv11', 'ss01'; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
        ::selection { background: rgba(6, 75, 158, .18); color: var(--tinta); }

        /* 2. Fondo y página */
        body.panel {
            min-height: 100vh; margin: 0; color: var(--tinta); background-color: #e8eef6;
            background:
                radial-gradient(1400px 700px at 60% -20%, #ffffff 0%, rgba(255, 255, 255, 0) 60%),
                linear-gradient(180deg, #eef3fa 0%, #e6edf6 100%);
            background-attachment: fixed;
        }
        .pagina {
            min-width: 0; min-height: 100vh;
            margin-left: calc(var(--riel-ancho) + 2 * var(--riel-margen));
            padding: 1.5rem 1.75rem 2.5rem .5rem;
        }
        #main-content:focus { outline: none; }

        /* 3. Cabecera de página: el título y, debajo, dónde y cuándo */
        .pagina-cabeza { display: flex; align-items: flex-end; gap: 1rem; flex-wrap: wrap; margin: 0 0 1.25rem; min-height: 3.25rem; }
        .pagina-cabeza__textos { min-width: 0; flex: 1 1 14rem; }
        .pagina-titulo { margin: 0; font-size: 1.75rem; line-height: 1.15; font-weight: 650; letter-spacing: -.02em; color: var(--tinta); text-wrap: balance; }
        .pagina-guia { margin: .35rem 0 0; font-size: .875rem; color: var(--tinta-suave); font-variant-numeric: tabular-nums; }
        .pagina-cabeza__acciones { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
        .pagina-cabeza__menu {
            display: none; width: 2.5rem; height: 2.5rem; place-items: center; border-radius: .625rem; color: var(--tinta-2);
            background: #fff; box-shadow: var(--roce); cursor: pointer; flex: none; align-self: center;
        }
        .pagina-cabeza__menu svg { width: 20px; height: 20px; }

        /* 4. Riel: pieza blanca flotante con íconos y nombres cortos */
        .riel {
            position: fixed; z-index: 40; top: var(--riel-margen); bottom: var(--riel-margen); left: var(--riel-margen);
            width: var(--riel-ancho); display: flex; flex-direction: column; align-items: center;
            padding: .875rem .375rem .75rem; background: #fff; border-radius: 18px; filter: var(--filo) drop-shadow(0 12px 18px rgba(15, 39, 71, .07));
        }
        .riel__marca { display: block; padding: .25rem; border-radius: .75rem; flex: none; }
        .riel__logo { width: 3.5rem; height: auto; display: block; }
        .riel__nav {
            flex: 1; min-height: 0; width: 100%; overflow-y: auto; scrollbar-width: none;
            display: flex; flex-direction: column; align-items: center; gap: .125rem; padding: .625rem 0;
        }
        .riel__nav::-webkit-scrollbar { display: none; }
        .riel__separador { width: 2.25rem; height: 1px; background: var(--linea); margin: .375rem 0; flex: none; }
        .riel__item {
            width: 4.75rem; flex: none; display: flex; flex-direction: column; align-items: center; gap: .25rem;
            padding: .5rem .25rem .4375rem; border-radius: .75rem; color: var(--tinta-suave); text-decoration: none;
            font-size: .6875rem; font-weight: 550; line-height: 1.15; text-align: center;
            transition-property: background-color, color; transition-duration: .15s; transition-timing-function: ease-out;
        }
        .riel__icono { position: relative; display: grid; place-items: center; }
        .riel__icono svg { width: 20px; height: 20px; color: var(--icono); transition: color .15s ease-out; }
        .riel__texto { max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .riel__item:hover { background: #f2f6fc; color: var(--tinta); }
        .riel__item:hover svg { color: var(--tinta-2); }
        .riel__item.is-activo { background: var(--azul-claro); color: var(--azul); font-weight: 650; }
        .riel__item.is-activo svg { color: var(--azul); }
        .riel__cuenta {
            position: absolute; top: -.4375rem; left: calc(100% - .4375rem); min-width: 1.25rem; height: 1.25rem; padding: 0 .3125rem;
            display: grid; place-items: center; border-radius: 999px; background: var(--azul); color: #fff; box-shadow: 0 0 0 2px #fff;
            font-size: .6875rem; font-weight: 700; font-variant-numeric: tabular-nums; line-height: 1;
        }
        .riel__pie { flex: none; display: flex; flex-direction: column; align-items: center; gap: .375rem; padding-top: .5rem; border-top: 1px solid var(--linea); width: 3.5rem; }
        .riel__avatar {
            width: 2rem; height: 2rem; display: grid; place-items: center; border-radius: 999px; background: var(--azul-claro);
            color: var(--azul); font-size: .6875rem; font-weight: 650; letter-spacing: .02em; cursor: default;
        }
        .riel__salir { width: 2.5rem; height: 2.5rem; display: grid; place-items: center; border-radius: .625rem; color: var(--icono); cursor: pointer; }
        .riel__salir svg { width: 20px; height: 20px; }
        .riel__salir:hover { background: #f2f6fc; color: var(--tinta); }
        .riel a:focus-visible, .riel button:focus-visible, .pagina-cabeza__menu:focus-visible { outline: 2px solid var(--azul); outline-offset: 1px; }

        /* Pantallas bajas: el riel se compacta en dos pasos para que quepan todas las opciones sin desplazarse */
        @media (min-width: 768px) and (max-height: 820px) {
            .riel { padding-top: .625rem; }
            .riel__logo { width: 2.75rem; }
            .riel__nav { padding: .375rem 0; }
            .riel__item { padding: .3125rem .25rem .25rem; gap: .1875rem; }
            .riel__separador { margin: .25rem 0; }
        }
        @media (min-width: 768px) and (max-height: 700px) {
            :root { --riel-margen: .75rem; }
            .riel__logo { width: 2.25rem; }
            .riel__item { padding: .25rem; gap: .125rem; font-size: .625rem; }
            .riel__icono svg { width: 18px; height: 18px; }
            .riel__avatar { display: none; }
            .riel__pie { padding-top: .25rem; }
        }
        /* Celular y tableta angosta: el riel se abre encima con el botón de la cabecera */
        .riel-velo { position: fixed; inset: 0; z-index: 35; background: rgba(15, 39, 71, .32); }
        @media (max-width: 767px) {
            .riel { transform: translateX(calc(-100% - 2rem)); transition: transform .24s cubic-bezier(.16, 1, .3, 1); }
            .riel.riel--abierto { transform: none; }
            .pagina { margin-left: 0; padding: 1rem 1rem 2rem; }
            .pagina-cabeza__menu { display: grid; }
            .pagina-titulo { font-size: 1.5rem; }
        }
        @media (prefers-reduced-motion: reduce) {
            .riel, .riel__item, .riel__icono svg { transition: none; }
        }

        /* 5. Esqueletos de carga: la forma del contenido en gris, con un pulso lento, mientras llega del servidor. */
        .esqueleto {
            display: block; height: .75rem; max-width: 100%; border-radius: .25rem; background: #e3e9f2;
            animation: esqueleto-pulso 1.6s ease-in-out infinite;
        }
        .esqueleto--etiqueta { height: 1.25rem; border-radius: .375rem; }
        .esqueleto--bloque { height: 4.25rem; border-radius: .625rem; }
        .esqueleto--bloque-bajo { height: 3.75rem; border-radius: .625rem; }
        @keyframes esqueleto-pulso { 50% { opacity: .45; } }
        @media (prefers-reduced-motion: reduce) { .esqueleto { animation: none; } }

        /* 6. Piezas compartidas de las vistas: superficies, tablas, botones, campos, filtros y modales */
        .barra-vista { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem; }
        .filtros-rapidos { display: flex; flex-wrap: wrap; gap: .375rem; }
        .filtro-rapido {
            display: inline-flex; align-items: center; gap: .4rem; height: 2.5rem; padding: 0 .9rem; border-radius: .625rem;
            background: #ffffff; box-shadow: var(--roce); font-size: .875rem; font-weight: 600; color: var(--tinta-2); cursor: pointer;
            transition-property: background-color, color; transition-duration: .15s; transition-timing-function: ease-out;
        }
        .filtro-rapido:hover { color: var(--azul); }
        /* Elegida: azul claro con contorno azul. El azul relleno queda solo para la acción principal (Una Sola Voz). */
        .filtro-rapido[aria-pressed="true"] { background: var(--azul-claro); color: var(--azul); box-shadow: inset 0 0 0 1.5px var(--azul); }
        .filtro-rapido__n { font-weight: 500; opacity: .75; font-variant-numeric: tabular-nums; }
        .filtro-rapido[aria-pressed="true"] .filtro-rapido__n { opacity: 1; }
        /* Sobre papel blanco (panel, modal): contorno fino en vez de roce; mismo alto */
        .filtros-rapidos--papel .filtro-rapido { box-shadow: inset 0 0 0 1px var(--linea); }
        .filtros-rapidos--papel .filtro-rapido:hover { background: #f5f8fc; }
        .filtros-rapidos--papel .filtro-rapido[aria-pressed="true"] { background: var(--azul-claro); box-shadow: inset 0 0 0 1.5px var(--azul); }
        /* Compactas: en una columna lateral angosta (Soporte, 22rem) tres pastillas con conteos de dos cifras caben en una fila */
        .filtros-rapidos--compacto .filtro-rapido { padding-inline: .625rem; white-space: nowrap; }
        /* Conteo que señala un problema («Sin servicios», «Sin asesor») mientras la pastilla no esté elegida */
        .filtro-rapido.is-alerta:not([aria-pressed="true"]) .filtro-rapido__n { color: var(--error-texto); opacity: 1; font-weight: 650; }
        .buscador { position: relative; flex: 1 1 16rem; max-width: 22rem; margin-left: auto; }
        @media (max-width: 639px) { .buscador { flex-basis: 100%; max-width: none; margin-left: 0; } }
        .buscador__icono { position: absolute; left: .75rem; top: 50%; width: 1rem; height: 1rem; transform: translateY(-50%); color: var(--icono); pointer-events: none; }
        /* Rótulo en línea de un control de la barra y título de un grupo de piezas (fuera de la pieza, como en el Inicio) */
        .rotulo-barra { flex: none; font-size: .8125rem; font-weight: 600; color: var(--tinta-2); white-space: nowrap; }
        .titulo-seccion { margin: 0 0 .75rem; font-size: 1rem; font-weight: 650; color: var(--tinta); }
        .campo {
            width: 100%; height: 2.5rem; padding: 0 .75rem; font-size: .875rem; font-weight: 400; color: var(--tinta); background: #ffffff;
            border: 1px solid var(--borde); border-radius: .625rem; caret-color: var(--azul);
            transition-property: border-color, box-shadow; transition-duration: .15s; transition-timing-function: ease-out;
        }
        .campo:hover { border-color: var(--borde-hover); }
        .buscador .campo { padding-left: 2.25rem; }
        .campo:focus { outline: none; border-color: var(--azul); box-shadow: 0 0 0 3px var(--foco); }
        .campo:disabled { background: #f3f6fa; color: var(--tinta-tenue); }
        .campo[aria-invalid="true"] { border-color: var(--error); }
        .campo[aria-invalid="true"]:focus { box-shadow: 0 0 0 3px rgba(180, 35, 24, .16); }
        .campo--area { height: auto; padding: .5rem .75rem; resize: vertical; }
        .btn-primario, .btn-secundario, .btn-peligro {
            display: inline-flex; align-items: center; justify-content: center; gap: .4rem; height: 2.5rem; padding: 0 1rem;
            border-radius: .625rem; font-size: .875rem; font-weight: 600; cursor: pointer;
            transition-property: background-color, scale; transition-duration: .15s; transition-timing-function: ease-out;
        }
        .btn-primario { background: var(--azul); color: #ffffff; box-shadow: 0 1px 2px rgba(15, 39, 71, .14); }
        .btn-primario:hover { background: var(--azul-hover); }
        .btn-primario:active { background: var(--azul-activo); }
        .btn-secundario { background: #fff; color: var(--tinta-2); box-shadow: var(--roce), inset 0 0 0 1px var(--linea); }
        .btn-secundario:hover { background: #f2f6fc; }
        .btn-peligro { background: #b42318; color: #ffffff; }
        .btn-peligro:hover { background: #931c13; }
        .btn-primario:active:not(:disabled), .btn-secundario:active:not(:disabled), .btn-peligro:active:not(:disabled) { scale: .96; }
        .btn-primario:disabled, .btn-secundario:disabled, .btn-peligro:disabled { opacity: .6; cursor: default; }
        /* Ocupado (aria-busy, texto en gerundio): un tono más claro del mismo color, nunca lavado */
        .btn-primario[aria-busy="true"], .btn-peligro[aria-busy="true"] { opacity: 1; box-shadow: none; cursor: progress; }
        .btn-primario[aria-busy="true"] { background: #2f63a6; }
        .btn-peligro[aria-busy="true"] { background: #c9574d; }
        /* Deshabilitado: gris azulado con contorno, para que no se confunda con «ocupado» */
        .btn-primario:disabled:not([aria-busy="true"]), .btn-peligro:disabled:not([aria-busy="true"]) {
            background: #eef3fa; color: var(--tinta-tenue); box-shadow: inset 0 0 0 1px var(--linea); opacity: 1;
        }
        /* La acción única de la columna lateral elevada (el botón de DESIGN.md): a todo el ancho, 48 px */
        .btn-primario--pieza { width: 100%; height: 3rem; font-size: .96875rem; letter-spacing: .005em; }
        .filtro-rapido:focus-visible, .btn-primario:focus-visible, .btn-secundario:focus-visible, .btn-peligro:focus-visible,
        .accion-icono:focus-visible, .enlace-panel:focus-visible { outline: 2px solid var(--azul); outline-offset: 2px; }
        .enlace-panel { color: var(--azul); font-weight: 600; cursor: pointer; text-underline-offset: 3px; }
        .enlace-panel:hover { text-decoration: underline; }

        .superficie { background: #ffffff; border-radius: .875rem; box-shadow: var(--roce); }
        /* Pieza elevada: como mucho una por vista (la columna lateral o el producto de la vista). El padding lo pone cada pieza. */
        .pieza-elevada { background: #ffffff; border-radius: 18px; box-shadow: none; filter: var(--filo) drop-shadow(0 16px 22px rgba(15, 39, 71, .09)); }
        /* Columna lateral: contenido + una columna fija a la derecha que acompaña al desplazarse; se apila bajo 1100 px */
        .vista-lateral { display: grid; grid-template-columns: minmax(0, 1fr) var(--lateral, 20rem); gap: 1.5rem; align-items: start; }
        .vista-lateral__lado { position: sticky; top: 1.5rem; }
        @media (max-width: 1099px) {
            .vista-lateral { grid-template-columns: minmax(0, 1fr); }
            /* 0,2,0: le gana al max-height local de la columna (Soporte, Reportes) */
            .vista-lateral > .vista-lateral__lado { position: static; max-height: none; }
        }
        .tabla-panel { width: 100%; border-collapse: collapse; font-size: .875rem; font-variant-numeric: tabular-nums; }
        .tabla-panel th {
            background: transparent; color: var(--tinta-tenue); font-size: .78125rem; font-weight: 550;
            text-align: left; padding: .75rem 1rem .5rem; white-space: nowrap; border-bottom: 1px solid var(--linea);
        }
        .tabla-panel td { padding: .625rem 1rem; border-top: 1px solid var(--linea-suave); color: var(--tinta); vertical-align: middle; }
        .tabla-panel tbody tr:first-child > td { border-top: 0; }
        .tabla-panel tbody tr:hover > td { background: #f6f9fd; }
        .accion-icono { width: 2rem; height: 2rem; border-radius: .5rem; display: inline-grid; place-items: center; color: var(--icono); cursor: pointer; }
        .accion-icono:hover { background: #eef3fa; color: var(--azul); }
        .accion-icono--peligro:hover { background: var(--error-fondo); color: var(--error); }
        .texto-mudo { color: var(--tinta-tenue); }
        .texto-alerta { color: var(--alerta-tinta); font-weight: 600; }
        .texto-error { color: var(--error); font-weight: 600; }

        /* Avisos (DESIGN.md · Notices): caja completa con borde fino, nunca barra lateral. Error y alerta con role="alert";
           info y ok con role="status". Ícono de 18 px, trazo 1.75. */
        .aviso {
            display: flex; align-items: flex-start; gap: .625rem; margin: 0; padding: .75rem .875rem; border: 1px solid; border-radius: .625rem;
            font-size: .875rem; font-weight: 400; line-height: 1.5; text-wrap: pretty;
        }
        .aviso > svg { width: 18px; height: 18px; flex: none; margin-top: .125rem; }
        .aviso p { margin: 0; min-width: 0; }
        .aviso b { font-weight: 600; }
        .aviso .enlace-panel { color: inherit; text-decoration: underline; }
        .aviso__cerrar { flex: none; margin: -.375rem -.5rem -.375rem auto; color: inherit; }
        .aviso--info { background: var(--info-fondo); border-color: var(--info-borde); color: var(--info); }
        .aviso--ok { background: var(--ok-fondo); border-color: var(--ok-borde); color: var(--ok); }
        .aviso--alerta { background: var(--alerta-fondo); border-color: var(--alerta-borde); color: var(--alerta); }
        .aviso--error { background: var(--error-fondo); border-color: var(--error-borde); color: var(--error-texto); }

        /* Vacíos: dentro de la superficie donde iría el contenido, con su salida («Quitar filtros» o la acción de crear) */
        .vacio { display: flex; flex-direction: column; align-items: center; gap: .25rem; padding: 2.5rem 1.5rem 2.75rem; text-align: center; }
        .vacio > svg { width: 28px; height: 28px; margin-bottom: .5rem; color: var(--icono); }
        .vacio__titulo { margin: 0; font-size: .9375rem; font-weight: 650; color: var(--tinta); text-wrap: balance; }
        .vacio__texto { margin: 0; max-width: 30rem; font-size: .875rem; line-height: 1.5; color: var(--tinta-suave); text-wrap: pretty; }
        .vacio .enlace-panel { margin-top: .5rem; font-size: .875rem; }

        .modal-panel {
            position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; padding: 1rem;
            background: rgba(15, 39, 71, .32); backdrop-filter: blur(2px);
        }
        .modal-panel__caja {
            width: 100%; max-width: 36rem; max-height: calc(100vh - 2rem); overflow-y: auto; background: #ffffff; border-radius: 18px;
            box-shadow: 0 0 0 1px rgba(15, 39, 71, .06), 0 24px 48px -16px rgba(15, 39, 71, .32);
        }
        /* Una sola entrada para todos los modales (la del Inicio): ninguna vista anima su caja */
        .modal-panel__caja { animation: modal-entra .2s cubic-bezier(.2, 0, 0, 1); }
        @keyframes modal-entra { from { transform: translateY(8px) scale(.985); } }
        @media (prefers-reduced-motion: reduce) { .modal-panel__caja { animation: none; } }
        .modal-panel__caja--angosta { max-width: 28rem; }
        .modal-panel__caja--ancha { max-width: 46rem; }
        .modal-panel__cabeza { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1.25rem 1.5rem .25rem; }
        .modal-panel__cabeza h2 {
            margin: 0; font-size: 1.125rem; line-height: 1.35; font-weight: 650; letter-spacing: -.01em; color: var(--tinta);
            text-wrap: balance; overflow-wrap: anywhere;
        }
        .modal-panel__cabeza .accion-icono { flex: none; margin: -.25rem -.5rem 0 0; }
        .modal-panel__cabeza .accion-icono svg { width: 18px; height: 18px; }
        .form-panel { display: flex; flex-direction: column; gap: .875rem; padding: .75rem 1.5rem 1.5rem; }
        .form-panel__fila { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; }
        @media (max-width: 639px) { .form-panel__fila { grid-template-columns: minmax(0, 1fr); } }
        .form-campo { display: flex; flex-direction: column; gap: .375rem; font-size: .8125rem; font-weight: 600; color: var(--tinta-2); }
        .form-campo small, .form-error { color: var(--error); font-size: .75rem; font-weight: 500; }
        .form-ayuda { color: var(--tinta-tenue); font-size: .75rem; font-weight: 400; line-height: 1.45; text-wrap: pretty; }
        .form-casilla { display: flex; align-items: flex-start; gap: .6rem; font-size: .875rem; color: var(--tinta); cursor: pointer; }
        .form-casilla input { width: 1rem; height: 1rem; margin-top: .15rem; accent-color: var(--azul); flex-shrink: 0; }
        .form-opcional { font-weight: 400; color: var(--tinta-tenue); }
        .form-texto { font-size: .875rem; line-height: 1.5; color: var(--tinta-2); text-wrap: pretty; }
        .modal-panel__pie { display: flex; justify-content: flex-end; gap: .5rem; padding-top: .25rem; }
        /* Solo si el modal se desplaza (nunca en Eliminar): el pie queda pegado abajo con un filete */
        .modal-panel__pie--fijo {
            position: sticky; bottom: 0; z-index: 1; margin: .125rem -1.5rem -1.5rem; padding: .875rem 1.5rem 1.5rem;
            background: #fff; box-shadow: 0 -1px 0 var(--linea-suave);
        }

        /* Casilla (la de Asignación): 18 px, palomita blanca sobre azul; indeterminada con una raya */
        .casilla, .form-casilla input[type="checkbox"] {
            appearance: none; -webkit-appearance: none; flex: none; display: inline-grid; place-content: center;
            width: 1.125rem; height: 1.125rem; margin: 0; border: 1.5px solid var(--borde); border-radius: 6px; background: #fff; cursor: pointer;
            transition-property: background-color, border-color; transition-duration: .15s; transition-timing-function: ease-out;
        }
        .casilla::before, .form-casilla input[type="checkbox"]::before {
            content: ""; width: .625rem; height: .625rem; transform: scale(0); transition: transform .18s cubic-bezier(.16, 1, .3, 1);
            clip-path: polygon(14% 44%, 0 65%, 50% 100%, 100% 16%, 80% 0%, 43% 62%); background: #fff;
        }
        .casilla:hover:not(:disabled), .form-casilla input[type="checkbox"]:hover:not(:disabled) { border-color: var(--azul); }
        .casilla:checked, .casilla:indeterminate,
        .form-casilla input[type="checkbox"]:checked, .form-casilla input[type="checkbox"]:indeterminate { background: var(--azul); border-color: var(--azul); }
        .casilla:checked::before, .form-casilla input[type="checkbox"]:checked::before { transform: scale(1); }
        .casilla:indeterminate::before, .form-casilla input[type="checkbox"]:indeterminate::before { transform: scale(1); clip-path: none; width: .5rem; height: 2px; }
        .casilla:focus-visible, .form-casilla input[type="checkbox"]:focus-visible { outline: 2px solid var(--azul); outline-offset: 2px; }
        .casilla:disabled, .form-casilla input[type="checkbox"]:disabled { opacity: .55; cursor: progress; }
        /* En una fila con texto, la casilla baja hasta el centro de la primera línea (como antes con .15rem) */
        .form-casilla input[type="checkbox"] { margin-top: .125rem; }
        @media (prefers-reduced-motion: reduce) {
            .casilla, .casilla::before, .form-casilla input[type="checkbox"], .form-casilla input[type="checkbox"]::before { transition: none; }
        }

        /* Control segmentado (receta de Usuarios): carril gris azulado; la opción elegida es papel blanco con texto azul */
        .segmentado { display: flex; height: 2.5rem; padding: 3px; gap: 3px; border-radius: .625rem; background: #eef3fa; box-shadow: inset 0 0 0 1px var(--linea); }
        .segmentado > button, .segmentado > label {
            position: relative; flex: 1; min-width: 0; display: inline-flex; align-items: center; justify-content: center; gap: .4375rem;
            padding: 0 .75rem; border-radius: 7px; font-size: .875rem; font-weight: 600; color: var(--tinta-suave); cursor: pointer; white-space: nowrap;
            transition-property: background-color, color, box-shadow; transition-duration: .15s; transition-timing-function: ease-out;
        }
        .segmentado > :hover { color: var(--tinta); }
        .segmentado > [aria-checked="true"], .segmentado > label:has(input:checked) {
            background: #fff; color: var(--azul); box-shadow: 0 1px 2px rgba(15, 39, 71, .12), 0 0 0 1px rgba(15, 39, 71, .06);
        }
        .segmentado label input { position: absolute; inset: 0; width: 100%; height: 100%; margin: 0; opacity: 0; cursor: pointer; }
        .segmentado > button:focus-visible, .segmentado > label:has(input:focus-visible) { outline: 2px solid var(--azul); outline-offset: 1px; }

        /* Estados como texto con punto de color (DESIGN.md: Estado con Palabras) */
        .estado-chip {
            display: inline-flex; align-items: center; gap: .375rem; white-space: nowrap; padding: .1875rem .5625rem; border-radius: 999px;
            font-size: .78125rem; font-weight: 550; line-height: 1.3;
        }
        .estado-chip::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: currentColor; flex: none; }
        .estado-chip--ok { color: var(--ok); background: var(--ok-fondo); }
        .estado-chip--info { color: var(--info); background: var(--info-fondo); }
        .estado-chip--alerta { color: var(--alerta); background: var(--alerta-fondo); }
        .estado-chip--error { color: var(--error-texto); background: var(--error-fondo); }
        .estado-chip--neutro { color: var(--tinta-suave); background: #eef1f6; }
        .estado-chip--chico { font-size: .6875rem; padding: .0625rem .4375rem; }   /* filas compactas */
        /* Punto suelto con los mismos tonos del chip (pastillas, leyendas): nunca hex sueltos */
        .punto { display: inline-block; width: 6px; height: 6px; border-radius: 50%; flex: none; background: var(--tinta-suave); }
        .punto--ok { background: var(--ok); }
        .punto--info { background: var(--info); }
        .punto--alerta { background: var(--alerta); }
        .punto--neutro { background: var(--tinta-suave); }
        .punto--error { background: var(--error-texto); }
        /* Falta de cobertura que lleva a Asignación: etiqueta cuadrada sin punto («Asignar», «Nadie · Asignar») */
        .etiqueta-asignar {
            display: inline-flex; align-items: center; height: 1.5rem; padding: 0 .5rem; border-radius: .5rem;
            background: var(--error-fondo); color: var(--error-texto); font-size: .75rem; font-weight: 650; white-space: nowrap; text-decoration: none;
        }
        .etiqueta-asignar:hover { text-decoration: underline; text-underline-offset: 2px; }
        .etiqueta-asignar:focus-visible { outline: 2px solid var(--azul); outline-offset: 2px; }
        /* Código de servicio como tecla (CI, KP, CIT-URG) */
        .codigo-tecla {
            display: inline-grid; place-items: center; min-width: 1.75rem; height: 1.5rem; padding: 0 .4375rem; border-radius: .375rem;
            background: #f5f8fc; box-shadow: inset 0 0 0 1px var(--linea); font-size: .75rem; font-weight: 650; letter-spacing: .06em;
            color: var(--tinta-2); font-variant-numeric: tabular-nums;
        }

        /* 7. Desplegables propios (public/js/desplegable.js): el <select> real queda oculto debajo y un botón con la
              misma clase lo reemplaza; la lista es un panel del panel, no la del navegador. */
        .desplegable__nativo {
            position: absolute !important; width: 1px !important; height: 1px !important; margin: -1px !important; padding: 0 !important;
            overflow: hidden !important; clip: rect(0, 0, 0, 0) !important; white-space: nowrap !important; border: 0 !important;
            opacity: 0 !important; pointer-events: none !important;
        }
        .desplegable__boton {
            display: flex !important; align-items: center; justify-content: space-between; gap: .5rem; min-width: 0;
            text-align: left; cursor: pointer; background-color: #ffffff; font-weight: 400;
        }
        .desplegable__boton:disabled { cursor: default; background-color: #f3f6fa; color: var(--tinta-tenue); }
        .desplegable__texto { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .desplegable__flecha { width: 1rem; height: 1rem; flex-shrink: 0; color: var(--icono); transition: transform .15s ease; }
        .desplegable__boton--abierto { border-color: var(--azul) !important; box-shadow: 0 0 0 3px rgba(6, 75, 158, .15); }
        .desplegable__boton--abierto .desplegable__flecha { transform: rotate(180deg); color: var(--azul); }
        .desplegable__boton:focus-visible { outline: none; border-color: var(--azul); box-shadow: 0 0 0 3px var(--foco); }
        .desplegable__boton--invalido { border-color: var(--error) !important; }

        .desplegable__panel {
            position: fixed; z-index: 80; display: flex; flex-direction: column; max-height: min(20rem, calc(100vh - 2rem));
            padding: .25rem; background: #ffffff; border: 1px solid #dde6f2; border-radius: .75rem;
            box-shadow: 0 16px 36px -12px rgba(15, 39, 71, .28), 0 2px 6px rgba(15, 39, 71, .06);
            font-size: .875rem; color: var(--tinta-2); animation: desplegable-entra .12s ease-out;
        }
        .desplegable__panel--arriba { animation-name: desplegable-entra-arriba; }
        .desplegable__panel[hidden], .desplegable__buscar[hidden], .desplegable__vacio[hidden] { display: none; }
        @keyframes desplegable-entra { from { opacity: 0; transform: translateY(-4px); } }
        @keyframes desplegable-entra-arriba { from { opacity: 0; transform: translateY(4px); } }
        @media (prefers-reduced-motion: reduce) { .desplegable__panel { animation: none; } .desplegable__flecha { transition: none; } }
        .desplegable__buscar {
            flex-shrink: 0; height: 2.125rem; margin: .125rem .125rem .375rem; padding: 0 .625rem;
            border: 1px solid #dde6f2; border-radius: .5rem; font-size: .8125rem; color: var(--tinta-2); outline: none;
        }
        .desplegable__buscar:focus { border-color: var(--azul); box-shadow: 0 0 0 3px rgba(6, 75, 158, .12); }
        .desplegable__lista { overflow-y: auto; outline: none; overscroll-behavior: contain; }
        .desplegable__opcion {
            display: flex; align-items: center; gap: .5rem; min-height: 2.125rem; padding: .375rem .625rem;
            border-radius: .5rem; cursor: pointer; user-select: none;
        }
        .desplegable__opcion--activa { background: #eef3fa; }
        .desplegable__opcion[aria-selected="true"] { color: var(--azul); font-weight: 600; }
        .desplegable__opcion[aria-disabled] { color: #a0a8b5; cursor: default; }
        .desplegable__opcion-texto { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .desplegable__marca { width: 1rem; height: 1rem; flex-shrink: 0; color: var(--azul); }
        .desplegable__grupo { padding: .5rem .625rem .25rem; font-size: .75rem; font-weight: 600; color: var(--tinta-tenue); }
        .desplegable__vacio { padding: .5rem .625rem; color: var(--tinta-tenue); }
    </style>
    {{-- Estilos propios de cada vista (@push('estilos')): aquí, después de los del layout, para que se apliquen
         desde el primer pintado. Al final del cuerpo, las transiciones de borde se veían como bordes negros al cargar. --}}
    @stack('estilos')
</head>
<body class="panel" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
    <a href="#main-content" class="skip-link">Saltar al contenido</a>

    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="riel-velo md:hidden"></div>
    @include('components.admin.sidebar')

    <main id="main-content" tabindex="-1" class="pagina">
        <header class="pagina-cabeza">
            <button type="button" class="pagina-cabeza__menu" @click="sidebarOpen = true" :aria-expanded="sidebarOpen"
                    aria-controls="admin-sidebar" aria-label="Abrir el menú">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
            <div class="pagina-cabeza__textos">
                <h1 class="pagina-titulo">
                    @hasSection('titulo_pagina') @yield('titulo_pagina') @else @yield('title', 'Panel') @endif
                </h1>
                <p class="pagina-guia">
                    @hasSection('guia_pagina') @yield('guia_pagina') @else {{ $unidadPanel }} · {{ $hoyPanel->isoFormat('dddd D [de] MMMM') }} @endif
                </p>
            </div>
            @hasSection('acciones_pagina')
                <div class="pagina-cabeza__acciones">@yield('acciones_pagina')</div>
            @endif
        </header>
        @yield('content')
    </main>

    @yield('scripts')
</body>
</html>
