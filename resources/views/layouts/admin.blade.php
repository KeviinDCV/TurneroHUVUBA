<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- La letra se pide antes que todo lo demás: así ya está cuando se pinta la página (ver @font-face más abajo) --}}
    <link rel="preload" href="{{ asset('fonts/InterVariable.woff2') }}" as="font" type="font/woff2" crossorigin>
    <title>@yield('title', 'Turnero HUV') - Turnero HUV</title>
    @include('components.favicon')
    {{-- Toasts: sileo.js (port sin React de Sileo). Antes que Alpine, para que las vistas ya tengan window.sileo --}}
    <script src="{{ asset('js/sileo.js') }}?v={{ is_file(public_path('js/sileo.js')) ? filemtime(public_path('js/sileo.js')) : 0 }}" defer></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Estilos para el modal -->
    <style>
        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.3);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 50;
            backdrop-filter: blur(3px);
            -webkit-backdrop-filter: blur(3px);
        }

        .modal-content {
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-overlay {
            background-color: rgba(100, 116, 139, 0.25) !important;
            backdrop-filter: blur(2px) !important;
            -webkit-backdrop-filter: blur(2px) !important;
        }
    </style>

    <style>
        :root {
            --hospital-blue: #064b9e;
            --hospital-blue-hover: #053d7a;
            --hospital-blue-light: #e6f0ff;
            --sidebar-collapsed-width: 4.5rem;
        }

        .bg-hospital-blue {
            background-color: var(--hospital-blue);
        }

        .text-hospital-blue {
            color: var(--hospital-blue);
        }

        .border-hospital-blue {
            border-color: var(--hospital-blue);
        }

        .hover\:bg-hospital-blue-hover:hover {
            background-color: var(--hospital-blue-hover);
        }

        .bg-hospital-blue-light {
            background-color: var(--hospital-blue-light);
        }

        /* Animaciones suaves */
        .transition-all {
            transition: all 0.3s ease;
        }

        /* Menú desplazable SIN barra visible (la barra translúcida se veía como
           una línea azul sobre el navy). El contenido sigue desplazándose con rueda/touch. */
        .sidebar-nav,
        .sidebar-full-height {
            scrollbar-width: none;          /* Firefox */
            -ms-overflow-style: none;       /* IE/Edge antiguo */
        }
        .sidebar-nav::-webkit-scrollbar,
        .sidebar-full-height::-webkit-scrollbar {
            width: 0;
            height: 0;
            display: none;                  /* Chrome/Safari/Edge */
        }

        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.5);
        }

        /* Fix para sidebar que cubra toda la altura del viewport */
        .sidebar-full-height {
            height: 100vh !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            overflow-y: auto !important;
        }

        /* Asegurar que el contenedor principal tenga la altura correcta */
        .main-container {
            min-height: 100vh;
        }

        /* Para pantallas grandes, asegurar que el sidebar esté fijo */
        @media (min-width: 768px) {
            .sidebar-full-height {
                height: 100vh !important;
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                overflow-y: auto !important;
                z-index: 30 !important;
            }

        }

        /* Header fijo para todas las pantallas */
        .header-responsive {
            position: fixed !important;
            top: 0 !important;
            right: 0 !important;
            z-index: 20 !important;
        }

        /* ===== RESPONSIVE BREAKPOINTS MEJORADOS ===== */

        /* Móviles (hasta 767px) */
        @media (max-width: 767px) {
            .header-responsive {
                left: 0 !important;
            }
            .sidebar-responsive {
                width: 16rem !important; /* Sidebar más estrecha en móviles */
            }
        }

        /* Tablets y pantallas pequeñas (768px - 1023px) */
        @media (min-width: 768px) and (max-width: 1023px) {
            .sidebar-responsive {
                width: 14rem !important; /* 224px */
            }
            .header-responsive {
                left: 14rem !important;
            }
        }

        /* Resoluciones problemáticas como 1024x666, 1024x768 */
        @media (min-width: 1024px) and (max-width: 1199px) {
            .sidebar-responsive {
                width: 15rem !important; /* 240px */
            }
            .header-responsive {
                left: 15rem !important;
            }
        }

        /* Pantallas medianas (1200px - 1399px) */
        @media (min-width: 1200px) and (max-width: 1399px) {
            .sidebar-responsive {
                width: 16rem !important; /* 256px */
            }
            .header-responsive {
                left: 16rem !important;
            }
        }

        /* Pantallas grandes (1400px+) */
        @media (min-width: 1400px) {
            .sidebar-responsive {
                width: 16rem !important; /* 256px: el menú «Limpio» no necesita más (antes 288px) */
            }
            .header-responsive {
                left: 16rem !important;
            }
        }

        /* ===== MARGENES DEL CONTENIDO PRINCIPAL ===== */

        /* Móviles - sin margen porque sidebar es overlay */
        @media (max-width: 767px) {
            .main-content {
                margin-left: 0 !important;
            }
        }

        /* Tablets y pantallas pequeñas */
        @media (min-width: 768px) and (max-width: 1023px) {
            .main-content {
                margin-left: 14rem !important;
            }
        }

        /* Resoluciones problemáticas como 1024x666 */
        @media (min-width: 1024px) and (max-width: 1199px) {
            .main-content {
                margin-left: 15rem !important;
            }
        }

        /* Pantallas medianas */
        @media (min-width: 1200px) and (max-width: 1399px) {
            .main-content {
                margin-left: 16rem !important;
            }
        }

        /* Pantallas grandes */
        @media (min-width: 1400px) {
            .main-content {
                margin-left: 16rem !important;
            }
        }

        /* ===== MEJORAS ADICIONALES PARA MÓVILES ===== */

        /* Asegurar que en móviles el sidebar sea overlay completo */
        @media (max-width: 767px) {
            .sidebar-full-height {
                width: 16rem !important;
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
            }

            .sidebar-full-height.translate-x-0 {
                transform: translateX(0) !important;
            }

            /* Header en móviles debe ocupar todo el ancho */
            .header-responsive {
                left: 0 !important;
                right: 0 !important;
                width: 100% !important;
            }
        }

        /* ===== MEJORAS PARA RESOLUCIONES ESPECÍFICAS ===== */

        /* Resolución 1024x666 y similares */
        @media (min-width: 1024px) and (max-width: 1199px) and (max-height: 768px) {
            .sidebar-responsive {
                width: 14rem !important;
            }
            .header-responsive {
                left: 14rem !important;
            }
            .main-content {
                margin-left: 14rem !important;
            }

            .header-responsive {
                padding: 0.5rem 1rem !important;
            }
        }

        /* Responsive sidebar */
        @media (max-width: 768px) {
            .sidebar-mobile {
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
            }

            .sidebar-mobile.open {
                transform: translateX(0);
            }
        }

        /* Colapso suave: animar el ancho/margen al alternar el modo compacto */
        @media (min-width: 768px) {
            .sidebar-responsive { transition: width 0.24s cubic-bezier(0.4, 0, 0.2, 1); }
            .header-responsive  { transition: left 0.24s cubic-bezier(0.4, 0, 0.2, 1); }
            .main-content       { transition: margin-left 0.24s cubic-bezier(0.4, 0, 0.2, 1); }
        }

        @media (prefers-reduced-motion: reduce) {
            .sidebar-responsive, .header-responsive, .main-content { transition: none !important; }
        }

        /* Animación suave para el indicador activo */
        .active-indicator {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }

        /* Estilos para tabs */
        .tab-content {
            display: block;
        }

        .tab-content.hidden {
            display: none;
        }

        /* Estilos para multimedia */
        .file-preview {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
        }

        .video-preview {
            max-width: 100px;
            max-height: 100px;
        }

        .sortable-item {
            cursor: move;
        }

        .sortable-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        [x-cloak] {
            display: none !important;
        }

        /* Asegurar que los elementos no se desborden */
        * {
            box-sizing: border-box;
        }

@media (min-width: 768px) {
            body.sidebar-is-collapsed .sidebar-responsive {
                width: var(--sidebar-collapsed-width) !important;
            }

            body.sidebar-is-collapsed .header-responsive {
                left: var(--sidebar-collapsed-width) !important;
            }

            body.sidebar-is-collapsed .main-content {
                margin-left: var(--sidebar-collapsed-width) !important;
            }
        }

        @media (max-width: 767px) {
            body.sidebar-is-collapsed .sidebar-responsive {
                width: 16rem !important;
            }
        }

        /* Enlace para saltar la cabecera y el menú (accesibilidad - WCAG 2.4.1) */
        .skip-link {
            position: absolute;
            left: -9999px;
            top: auto;
            z-index: 60;
            background: #fff;
            color: #072449;
            padding: .5rem 1rem;
            border-radius: .375rem;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(0,0,0,.2);
        }
        .skip-link:focus {
            left: 1rem;
            top: 1rem;
        }

        /* ================= ESTILO MINIMALISTA: SIN BORDES =================
           Quita los bordes de CARDS, MODALES y BOTONES en todas las vistas.

           Por que se hace aqui y no editando cada vista: son ~1.200 usos de
           "border" repartidos en 20 archivos. Centralizarlo es reversible
           (basta borrar este bloque) y no se escapa ninguna vista.

           Clave: se usa el selector COMPUESTO ".border.border-gray-200", que
           solo alcanza a los elementos con borde COMPLETO (clase `border`).
           Los separadores direccionales (`border-b`, `border-t`, `border-l-4`)
           NO llevan la clase `border`, asi que se conservan intactos.

           Se usa `border-color: transparent` en vez de `border: none` para que
           NO haya corrimiento de layout: el ancho del borde se mantiene.

           NO se tocan a proposito: inputs/selects/textarea (border-gray-300),
           separadores de tabla, acentos laterales de color (border-l-4) ni el
           spinner de carga (border-b-2 border-white). */

        /* --- Cards y paneles ---
           El ancla `div.` es DELIBERADA: hay <table> que tambien usan
           "border border-gray-200" (cajas, turnos, users) y su borde SI debe
           conservarse, porque una tabla no es una card. */
        div.border.border-gray-200,
        div.border.border-gray-100 {
            border-color: transparent;
        }

        /* Tiles de metrica y avisos de color (turnos, reportes): conservan su
           fondo de color, que ya comunica el estado sin necesidad del borde. */
        div.border.border-blue-200,
        div.border.border-green-200,
        div.border.border-yellow-200,
        div.border.border-red-200,
        div.border.border-purple-200 {
            border-color: transparent;
        }

        /* Listas de seleccion con scroll (Usuarios/Servicios de Reportes y
           similares). Se anclan por `overflow-y-auto` porque algunas llevan
           `border` sin clase de color. */
        div.overflow-y-auto.border,
        div.overflow-y-auto.border.border-gray-300 {
            border-color: transparent;
        }

        /* Tiles de opcion seleccionables dentro de modales. Usan `border-2`,
           no `border`. El `:not(:hover)` es IMPRESCINDIBLE: conserva el
           resaltado azul de `hover:border-hospital-blue`, que es la unica
           senal de que la opcion es clicable. */
        div.border-2.border-gray-200:not(:hover),
        label.border-2.border-gray-200:not(:hover),
        label.border.border-gray-200:not(:hover) {
            border-color: transparent;
        }

        /* --- Botones ---
           :not(.tab-button) protege el indicador de pestana activa, que se
           dibuja con borde y que el JS reescribe en caliente. */
        button.border.border-gray-300:not(.tab-button),
        button.border.border-gray-200:not(.tab-button),
        button.border.border-hospital-blue:not(.tab-button),
        a.border.border-gray-300:not(.tab-button) {
            border-color: transparent;
        }

        /* Los secundarios blancos se quedarian invisibles sobre la card blanca:
           se les da un gris muy suave para que sigan leyendose como boton. */
        button.border.border-gray-300:not(.tab-button),
        a.border.border-gray-300:not(.tab-button) {
            background-color: #eef1f6;
        }
        button.border.border-gray-300:not(.tab-button):hover,
        a.border.border-gray-300:not(.tab-button):hover {
            background-color: #e2e8f2;
        }

        @yield('styles')
    </style>
    <style>
        /* ================= EVOLUCIÓN 2026-09: menú lateral y barra =================
           Una sola medida para la barra y el contenido, densidad del menú según la ALTURA de la
           pantalla (en 1366x768 la pestaña activa seguía conectada y las 3 secciones caben), y
           hover/foco coherentes. Sin clases nuevas de Tailwind: no requiere npm run build. */
        /* 1. Barra superior y contenido comparten UNA medida.
              Antes: la barra medía 52 px (44 px en 1366x768) y el <main> tenía
              pt-16 (64 px) fijo -> franja gris vacía de 12-20 px bajo la barra. */
        :root { --admin-header-h: 3.25rem; }                       /* 52 px */
        .header-responsive { height: var(--admin-header-h); padding-top: 0; padding-bottom: 0; }
        .main-content { padding-top: var(--admin-header-h); min-width: 0; }  /* min-width: 0 -> una tabla ancha ya no estira el <main> fuera de la pantalla */

        /* 2. Alturas medianas (720-799 px útiles: 1536x864, 1600x900…): la barra superior baja a 44 px. */
        @media (min-width: 768px) and (max-height: 799px) {
            :root { --admin-header-h: 2.75rem; }                   /* 44 px */
        }

        /* 3. Foco de teclado en la barra superior (el del menú está en el bloque 8) */
        .header-responsive button:focus-visible { outline: 2px solid #064b9e; outline-offset: 2px; }

        /* 4. La letra Inter de verdad (el tema la declaraba y nunca se cargaba; se veía Segoe UI). */
        @font-face {
            font-family: 'Inter';
            src: url('{{ asset('fonts/InterVariable.woff2') }}') format('woff2');
            /* block, no swap: con swap se pintaba primero con Segoe UI y al llegar Inter las letras "crecían" */
            font-weight: 100 900; font-style: normal; font-display: block;
        }
        html { font-feature-settings: 'cv05' 1; }   /* l minúscula con cola: "lcruz" ya no se lee "Icruz" */

        /* 5. Esqueletos de carga: la forma del contenido en gris, con un pulso lento, mientras llega del servidor. */
        .esqueleto {
            display: block; height: .75rem; max-width: 100%; border-radius: .25rem; background: #e6ebf2;
            animation: esqueleto-pulso 1.6s ease-in-out infinite;
        }
        .esqueleto--etiqueta { height: 1.25rem; border-radius: .375rem; }
        .esqueleto--bloque { height: 4.25rem; border-radius: .5rem; }
        .esqueleto--bloque-bajo { height: 3.75rem; border-radius: .5rem; }
        @keyframes esqueleto-pulso { 50% { opacity: .45; } }
        @media (prefers-reduced-motion: reduce) { .esqueleto { animation: none; } }

        /* 7. Piezas compartidas de las vistas (Servicios, Asignación…): el mismo material de Módulos y del Inicio. */
        .barra-vista { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem; }
        .filtros-rapidos { display: flex; flex-wrap: wrap; gap: .375rem; }
        .filtro-rapido {
            display: inline-flex; align-items: center; gap: .4rem; height: 2.5rem; padding: 0 .9rem; border-radius: .5rem;
            background: #ffffff; box-shadow: 0 1px 2px rgba(16, 24, 40, .06);
            font-size: .875rem; font-weight: 600; color: #374151; cursor: pointer;
        }
        .filtro-rapido:hover { color: #064b9e; }
        .filtro-rapido[aria-pressed="true"] { background: #064b9e; color: #ffffff; }
        .filtro-rapido__n { font-weight: 500; opacity: .75; }
        .buscador { position: relative; flex: 1 1 16rem; max-width: 22rem; margin-left: auto; }
        .buscador__icono { position: absolute; left: .75rem; top: 50%; width: 1rem; height: 1rem; transform: translateY(-50%); color: #6b7280; pointer-events: none; }
        .campo {
            width: 100%; height: 2.5rem; padding: 0 .75rem; font-size: .875rem; color: #111827; background: #ffffff;
            border: 1px solid #d1d5db; border-radius: .5rem;
        }
        .buscador .campo { padding-left: 2.25rem; }
        .campo:focus { outline: none; border-color: #064b9e; box-shadow: 0 0 0 3px rgba(6, 75, 158, .18); }
        .campo:disabled { background: #f3f5f9; color: #6b7280; }
        .campo--area { height: auto; padding: .5rem .75rem; resize: vertical; }
        .campo--mono { text-transform: uppercase; letter-spacing: .08em; font-weight: 600; font-variant-numeric: tabular-nums; }
        .btn-primario, .btn-secundario, .btn-peligro {
            display: inline-flex; align-items: center; justify-content: center; gap: .4rem; height: 2.5rem; padding: 0 1rem;
            border-radius: .5rem; font-size: .875rem; font-weight: 600; cursor: pointer; transition: background-color .15s ease;
        }
        .btn-primario { background: #064b9e; color: #ffffff; }
        .btn-primario:hover { background: #053d7a; }
        .btn-secundario { background: #eef1f6; color: #374151; }
        .btn-secundario:hover { background: #e2e8f2; }
        .btn-peligro { background: #b7191c; color: #ffffff; }
        .btn-peligro:hover { background: #9a1518; }
        .btn-primario:disabled, .btn-secundario:disabled, .btn-peligro:disabled { opacity: .6; cursor: default; }
        .filtro-rapido:focus-visible, .btn-primario:focus-visible, .btn-secundario:focus-visible, .btn-peligro:focus-visible,
        .accion-icono:focus-visible, .enlace-panel:focus-visible { outline: 2px solid #064b9e; outline-offset: 2px; }
        .enlace-panel { color: #064b9e; font-weight: 600; cursor: pointer; }
        .enlace-panel:hover { text-decoration: underline; }

        .superficie { background: #ffffff; border-radius: .75rem; box-shadow: 0 1px 2px rgba(16, 24, 40, .06); }
        .tabla-panel { width: 100%; border-collapse: collapse; font-size: .875rem; font-variant-numeric: tabular-nums; }
        .tabla-panel th {
            background: #f6f8fc; color: #6b7280; font-size: .75rem; font-weight: 600; text-transform: uppercase;
            letter-spacing: .04em; text-align: left; padding: .6rem 1rem; white-space: nowrap;
        }
        .tabla-panel td { padding: .5rem 1rem; border-top: 1px solid #eef1f6; color: #111827; vertical-align: middle; }
        .tabla-panel tbody tr:hover > td { background: #f8fafd; }
        .accion-icono { width: 1.75rem; height: 1.75rem; border-radius: .375rem; display: inline-grid; place-items: center; color: #6b7280; cursor: pointer; }
        .accion-icono:hover { background: #eef1f6; color: #064b9e; }
        .accion-icono--peligro:hover { background: #fdecec; color: #b7191c; }
        .texto-mudo { color: #6b7280; }
        .texto-alerta { color: #b45309; font-weight: 600; }
        .texto-error { color: #b7191c; font-weight: 600; }

        .modal-panel {
            position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; padding: 1rem;
            background: rgba(15, 23, 42, .35); backdrop-filter: blur(2px);
        }
        .modal-panel__caja { width: 100%; max-width: 36rem; max-height: calc(100vh - 2rem); overflow-y: auto; background: #ffffff; border-radius: .875rem; box-shadow: 0 24px 48px -12px rgba(16, 24, 40, .28); }
        .modal-panel__caja--angosta { max-width: 28rem; }
        .modal-panel__cabeza { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem .25rem; }
        .modal-panel__cabeza h2 { font-size: 1.05rem; font-weight: 700; color: #0f2547; }
        .form-panel { display: flex; flex-direction: column; gap: .8rem; padding: .75rem 1.25rem 1.25rem; }
        .form-panel__fila { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; }
        @media (max-width: 639px) { .form-panel__fila { grid-template-columns: minmax(0, 1fr); } }
        .form-campo { display: flex; flex-direction: column; gap: .3rem; font-size: .75rem; font-weight: 600; color: #374151; }
        .form-campo small, .form-error { color: #b7191c; font-size: .75rem; font-weight: 500; }
        .form-ayuda { color: #6b7280; font-size: .75rem; font-weight: 400; line-height: 1.4; }
        .form-casilla { display: flex; align-items: flex-start; gap: .6rem; font-size: .875rem; color: #111827; cursor: pointer; }
        .form-casilla input { width: 1rem; height: 1rem; margin-top: .15rem; accent-color: #064b9e; flex-shrink: 0; }
        .form-texto { font-size: .875rem; line-height: 1.5; color: #374151; }
        .modal-panel__pie { display: flex; justify-content: flex-end; gap: .5rem; padding-top: .25rem; }

        /* 8. Menú lateral «Limpio · Toque» (2026-10, referencia Linear/Notion) y barra superior blanca.
              Claro y liviano: el azul institucional va en la opción activa; arriba, el logo del hospital. */
        .menu-lateral {
            --menu-fondo: #f2f6fc; --menu-borde: #dde6f2; --menu-texto: #2b3d59; --menu-fuerte: #0f2547; --menu-mudo: #6b7f9e;
            --menu-icono: #6b82a6; --menu-seccion: #7a8fae; --menu-hover: #e3ebf7; --menu-activo: #064b9e;
            background: var(--menu-fondo); box-shadow: inset -1px 0 0 var(--menu-borde); color: var(--menu-texto);
            font-size: .84375rem; overflow-x: hidden;
        }
        .menu-lateral svg { width: 16px; height: 16px; flex-shrink: 0; }
        .menu-cabeza { display: flex; align-items: flex-start; gap: .25rem; padding: .75rem .625rem .5rem; flex-shrink: 0; }
        /* El logo del hospital va en su propio renglón, a buen tamaño; el nombre y la unidad, debajo y completos */
        .menu-espacio { flex: 1; min-width: 0; display: flex; flex-direction: column; align-items: flex-start; gap: .5rem; padding: .5rem; border-radius: .375rem; }
        .menu-espacio:hover { background: var(--menu-hover); }
        /* Logo del hospital (dibujo de la fachada, azul sobre transparente): sin recuadro, sobre el fondo del menú */
        .menu-logo { width: 4.5rem; height: auto; flex-shrink: 0; }
        .menu-espacio__texto { min-width: 0; line-height: 1.25; }
        .menu-espacio__nombre { display: block; font-size: .875rem; font-weight: 600; color: var(--menu-fuerte); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .menu-espacio__unidad { display: block; font-size: .75rem; color: var(--menu-mudo); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .menu-boton { width: 1.75rem; height: 1.75rem; flex-shrink: 0; display: grid; place-items: center; border-radius: .375rem; color: var(--menu-mudo); }
        .menu-boton:hover { background: var(--menu-hover); color: var(--menu-fuerte); }
        .menu-boton--cerrar { display: none; }

        .menu-nav { padding: .25rem .625rem 1rem; }
        .menu-seccion { padding: 0 .5rem; margin: 1.1rem 0 .25rem; font-size: .75rem; font-weight: 500; color: var(--menu-seccion); }
        .menu-item { display: flex; align-items: center; gap: .625rem; height: 30px; padding: 0 .5rem; border-radius: .375rem; font-weight: 500; color: var(--menu-texto); }
        .menu-item + .menu-item { margin-top: 1px; }
        .menu-item svg { color: var(--menu-icono); }
        .menu-item:hover { background: var(--menu-hover); color: var(--menu-fuerte); }
        .menu-item:hover svg { color: var(--menu-texto); }
        .menu-item.is-activo,
        .menu-item.is-activo:hover { background: var(--menu-activo); color: #ffffff; font-weight: 600; }
        .menu-item.is-activo svg { color: #ffffff; }
        .menu-item__texto { min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .menu-pie { margin-top: auto; padding: .5rem .625rem .75rem; flex-shrink: 0; }
        .menu-usuario { display: flex; align-items: center; gap: .625rem; padding: .25rem .25rem .25rem .5rem; }
        .menu-avatar {
            width: 1.375rem; height: 1.375rem; flex-shrink: 0; display: grid; place-items: center; border-radius: 9999px;
            background: #d6e2f3; color: var(--menu-texto); font-size: .6875rem; font-weight: 650;
        }
        .menu-usuario__nombre { flex: 1; min-width: 0; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .menu-firma { padding: .25rem .5rem 0; font-size: .6875rem; line-height: 1.4; color: #93a4bd; }
        .menu-lateral a:focus-visible,
        .menu-lateral button:focus-visible { outline: 2px solid var(--menu-activo); outline-offset: -1px; }
        .menu-item.is-activo:focus-visible { outline-color: var(--menu-fuerte); outline-offset: 1px; }

        /* Pantallas bajas (hasta 719 px útiles; 1366x768 real ≈ 650): menos aire entre grupos */
        @media (min-width: 768px) and (max-height: 719px) {
            .menu-cabeza { padding-top: .5rem; }
            .menu-logo { width: 3.75rem; }
            .menu-seccion { margin-top: .75rem; }
        }
        /* Celular: el menú se abre encima y se cierra con la X; plegar no aplica */
        @media (max-width: 767px) {
            .menu-boton--plegar { display: none; }
            .menu-boton--cerrar { display: grid; }
        }
        /* Compacto (72 px): solo íconos, centrados; el título de cada grupo se vuelve una raya fina */
        @media (min-width: 768px) {
            body.sidebar-is-collapsed .menu-espacio__texto,
            body.sidebar-is-collapsed .menu-item__texto,
            body.sidebar-is-collapsed .menu-usuario__nombre,
            body.sidebar-is-collapsed .menu-avatar,
            body.sidebar-is-collapsed .menu-firma { display: none; }
            body.sidebar-is-collapsed .menu-cabeza { flex-direction: column; align-items: center; gap: .5rem; padding: .75rem 0 .5rem; }
            body.sidebar-is-collapsed .menu-espacio { flex: none; padding: .25rem; }
            body.sidebar-is-collapsed .menu-logo { width: 2.5rem; }
            body.sidebar-is-collapsed .menu-nav { padding: .25rem .875rem 1rem; }
            body.sidebar-is-collapsed .menu-seccion { height: 1px; margin: .625rem .25rem; padding: 0; overflow: hidden; color: transparent; background: var(--menu-borde); }
            body.sidebar-is-collapsed .menu-item { justify-content: center; padding: 0; }
            body.sidebar-is-collapsed .menu-usuario { justify-content: center; padding: .25rem 0; }
        }

        /* Barra superior: blanca, delgada y en voz baja */
        .barra-superior { background: #ffffff; box-shadow: inset 0 -1px 0 #e3e8f0; }
        .barra-superior__titulo { font-size: .9375rem; font-weight: 600; color: #0f2547; }
        .barra-superior__hospital { font-size: .75rem; color: #7a8fae; }
        .barra-superior__menu { margin-left: -.5rem; padding: .5rem; border-radius: .375rem; color: #2b3d59; }
        .barra-superior__menu:hover { background: #eef3fa; }
    </style>
    {{-- Estilos propios de cada vista (@push('estilos')): aquí, después de los del layout, para que se apliquen
         desde el primer pintado. Al final del cuerpo, las transiciones de borde se veían como bordes negros al cargar. --}}
    @stack('estilos')
</head>
<body class="min-h-screen bg-gray-100"
      x-data="{ sidebarOpen: false, sidebarCollapsed: window.innerWidth >= 768 && localStorage.getItem('huvSidebarCollapsed') === '1' }"
      :class="{ 'sidebar-is-collapsed': sidebarCollapsed }">
    <script>
        // El menú plegado se aplica antes del primer pintado; Alpine llega después y solo lo mantiene.
        try { if (window.innerWidth >= 768 && localStorage.getItem('huvSidebarCollapsed') === '1') document.body.classList.add('sidebar-is-collapsed'); } catch (e) {}
    </script>
    <a href="#main-content" class="skip-link">Saltar al contenido</a>
    @include('components.admin.header')

    <div class="flex main-container">
        <!-- Overlay para móviles -->
        <div x-show="sidebarOpen"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false"
             class="fixed inset-0 bg-gray-600 bg-opacity-75 z-20 md:hidden"
             style="display: none;"></div>

        @include('components.admin.sidebar')

        <!-- Main Content -->
        <main id="main-content" tabindex="-1" class="flex-1 main-content">
            <div class="p-4 md:p-6">
                @yield('content')
            </div>
        </main>
    </div>

    @yield('scripts')
</body>
</html>
