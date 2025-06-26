<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Turnero HUV') - Turnero HUV</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
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

        /* Mejora del scroll en la sidebar */
        .sidebar-nav::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar-nav::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
        }

        .sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 2px;
        }

        .sidebar-nav::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.5);
        }

        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.5);
        }

        /* Fix para sidebar que cubra toda la altura del contenido */
        .sidebar-full-height {
            min-height: 100vh;
            height: auto;
        }

        /* Asegurar que el contenedor principal tenga la altura correcta */
        .main-container {
            min-height: 100vh;
        }

        /* Para pantallas grandes, la sidebar debe ser sticky */
        @media (min-width: 768px) {
            .sidebar-full-height {
                position: sticky;
                top: 0;
                height: 100vh;
                overflow-y: auto;
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

        @yield('styles')
    </style>
</head>
<body class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
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
        <main class="flex-1 md:ml-0">
            <div class="p-4 md:p-6">
                @yield('content')
            </div>
        </main>
    </div>

    @yield('scripts')
</body>
</html>
