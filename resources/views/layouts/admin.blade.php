<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Turnero HUV') - Turnero HUV</title>
    @include('components.favicon')
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
            --sidebar-collapsed-width: 5.25rem;
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

        /* ===== Sidebar "Marca profunda" (azul profundo + pestaña conectada) ===== */
        .sidebar-shell {
            background: #072449;
        }

        .sidebar-section-title {
            color: #7e9bc4;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            line-height: 1.4;
        }

        /* Sin brillo de barrido en este diseño */
        .sidebar-item::before { display: none !important; }

        /* Ítems: pestaña que llega al borde derecho y se "conecta" con el contenido */
        .sidebar-item {
            color: #bccce4;
            margin-left: 12px;
            border-radius: 10px 0 0 10px;
        }
        .sidebar-item:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
        }
        .sidebar-item-active,
        .sidebar-item-active:hover {
            background: #ffffff;
            color: #072449;
        }

        .sidebar-logout { color: #bccce4; border-radius: 10px; }
        .sidebar-logout:hover { background: rgba(255, 255, 255, 0.08); color: #ffffff; }

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
                width: 18rem !important; /* 288px - valor original */
            }
            .header-responsive {
                left: 18rem !important;
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
                margin-left: 18rem !important;
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

            /* Hacer elementos más compactos */
            .sidebar-header {
                padding: 0.75rem !important;
            }
            .sidebar-nav {
                padding: 0.5rem !important;
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

        /* Estilos adicionales para la sidebar */
        .sidebar-item {
            position: relative;
            overflow: hidden;
        }

        .sidebar-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s;
        }

        .sidebar-item:hover::before {
            left: 100%;
        }

        /* ===================== PULIDO PROFESIONAL DEL SIDEBAR ===================== */
        /* Colapso suave: animar el ancho/margen al alternar el modo compacto */
        @media (min-width: 768px) {
            .sidebar-responsive { transition: width 0.24s cubic-bezier(0.4, 0, 0.2, 1); }
            .header-responsive  { transition: left 0.24s cubic-bezier(0.4, 0, 0.2, 1); }
            .main-content       { transition: margin-left 0.24s cubic-bezier(0.4, 0, 0.2, 1); }
        }

        /* Micro-interacción: desplazamiento sutil al pasar el cursor (excepto el activo) */
        .sidebar-item:not(.sidebar-item-active):hover {
            transform: translateX(2px);
        }

        /* Ícono del ítem: leve realce al pasar el cursor y sombra en el activo */
        .sidebar-icon { transition: transform 0.2s ease, background-color 0.2s ease, color 0.2s ease; }
        .sidebar-item:hover .sidebar-icon { transform: scale(1.06); }
        .sidebar-item-active .sidebar-icon { box-shadow: 0 4px 10px -3px rgba(0, 0, 0, 0.28); }

        /* Botón de colapsar: realce al pasar el cursor */
        body.sidebar-is-collapsed .sidebar-header .flex.items-center.min-w-0 { justify-content: center; }

        @media (prefers-reduced-motion: reduce) {
            .sidebar-responsive, .header-responsive, .main-content,
            .sidebar-item, .sidebar-icon, .sidebar-item::before { transition: none !important; }
        }

        body.sidebar-is-collapsed .sidebar-label {
            pointer-events: none;
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

        /* ===== RESPONSIVE DESIGN IMPROVEMENTS ===== */

        /* Mejoras para resoluciones pequeñas */
        @media (max-height: 800px) {
            /* Reducir padding general */
            .dashboard-container {
                padding: 0.75rem !important;
            }

            /* Reducir espaciado entre secciones */
            .dashboard-section {
                margin-top: 1.5rem !important;
            }

            /* Tablas más compactas */
            .dashboard-table th,
            .dashboard-table td {
                padding: 0.5rem !important;
                font-size: 0.875rem !important;
            }

            /* Títulos más pequeños */
            .dashboard-title {
                font-size: 1rem !important;
                margin-bottom: 0.75rem !important;
            }

            /* Botones más compactos */
            .dashboard-button {
                padding: 0.5rem 1rem !important;
                font-size: 0.875rem !important;
            }

            /* Badges más pequeños */
            .dashboard-badge {
                padding: 0.25rem 0.5rem !important;
                font-size: 0.75rem !important;
            }
        }

        @media (max-height: 700px) {
            /* Para pantallas muy pequeñas */
            .dashboard-container {
                padding: 0.5rem !important;
            }

            .dashboard-section {
                margin-top: 1rem !important;
            }

            .dashboard-table th,
            .dashboard-table td {
                padding: 0.375rem !important;
                font-size: 0.8rem !important;
            }

            .dashboard-title {
                font-size: 0.9rem !important;
                margin-bottom: 0.5rem !important;
            }

            /* Iconos más pequeños */
            .dashboard-icon {
                width: 2.5rem !important;
                height: 2.5rem !important;
            }

            .dashboard-icon svg {
                width: 1.25rem !important;
                height: 1.25rem !important;
            }
        }

        /* Reglas de tabla responsive - sin conflicto con sidebar */
        @media (max-width: 1400px) {
            .dashboard-table th,
            .dashboard-table td {
                font-size: 0.875rem !important;
                padding: 0.5rem !important;
            }
        }

        @media (max-width: 1200px) {
            .dashboard-table th,
            .dashboard-table td {
                font-size: 0.8rem !important;
                padding: 0.375rem !important;
            }
        }

        /* Mejoras específicas para resolución 1366x768 */
        @media (max-width: 1366px) and (max-height: 768px) {
            .dashboard-container {
                padding: 0.75rem !important;
            }

            .dashboard-section {
                margin-top: 1.25rem !important;
            }

            .dashboard-table th,
            .dashboard-table td {
                font-size: 0.8rem !important;
                padding: 0.375rem !important;
            }

            .dashboard-title {
                font-size: 1rem !important;
                margin-bottom: 0.75rem !important;
            }

            .dashboard-button {
                padding: 0.5rem 0.875rem !important;
                font-size: 0.8rem !important;
            }

            .dashboard-badge {
                padding: 0.25rem 0.5rem !important;
                font-size: 0.7rem !important;
            }

            /* Header más compacto */
            .header-responsive {
                padding: 0.5rem 1rem !important;
            }

            .header-title {
                font-size: 1.25rem !important;
            }

            /* Sidebar más compacta - ancho controlado por reglas principales */

            .sidebar-header {
                padding: 1rem !important;
            }

            .sidebar-user {
                padding: 0.75rem !important;
            }

            .sidebar-nav {
                padding: 0.75rem !important;
            }

            .sidebar-item {
                padding: 0.5rem 0.75rem !important;
                font-size: 0.875rem !important;
            }
        }

        /* Asegurar que los elementos no se desborden */
        * {
            box-sizing: border-box;
        }

        /* Mejorar legibilidad en pantallas muy pequeñas */
        @media (max-height: 600px) {
            .dashboard-container {
                padding: 0.5rem !important;
            }

            .dashboard-section {
                margin-top: 0.75rem !important;
            }

            .dashboard-table th,
            .dashboard-table td {
                font-size: 0.75rem !important;
                padding: 0.25rem !important;
            }
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

        @yield('styles')
    </style>
</head>
<body class="min-h-screen bg-gray-100"
      x-data="{ sidebarOpen: false, sidebarCollapsed: window.innerWidth >= 768 && localStorage.getItem('huvSidebarCollapsed') === '1' }"
      :class="sidebarCollapsed ? 'sidebar-is-collapsed' : ''">
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
        <main class="flex-1 main-content pt-16">
            <div class="p-4 md:p-6">
                @yield('content')
            </div>
        </main>
    </div>

    @yield('scripts')
</body>
</html>
