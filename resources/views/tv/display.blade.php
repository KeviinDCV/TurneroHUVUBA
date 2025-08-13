<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Turnero HUV') }} - Visualizador TV</title>
    @include('components.favicon')

    <!-- Fonts - Optimized loading -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Fuente de respaldo para evitar problemas de carga */
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        /* Nuevas animaciones más sutiles */
        @keyframes highlight {
            0% { box-shadow: 0 0 0 0 rgba(6, 75, 158, 0.4); }
            50% { box-shadow: 0 0 0 10px rgba(6, 75, 158, 0); }
            100% { box-shadow: 0 0 0 0 rgba(6, 75, 158, 0); }
        }

        @keyframes simple-fade-in {
            0% { opacity: 0.7; transform: translateY(5px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        /* Clase para mostrar un nuevo turno */
        .new-turn {
            animation: simple-fade-in 0.5s ease forwards, highlight 1.5s ease 0.5s forwards;
            animation-iteration-count: 1; /* Solo una vez */
        }

        /* Eliminar las animaciones anteriores que podrían estar causando problemas */
        .animate-pulse-number, .animate-slide-in {
            animation: none !important;
        }

        .hospital-building {
            background-color: #064b9e;
            border-color: #053a7a;
        }

        .hospital-building-inner {
            border-color: #053a7a;
        }

        .hospital-building-window {
            border-color: #053a7a;
            background-color: rgba(6, 75, 158, 0.1);
        }

        .bg-hospital-blue {
            background-color: #064b9e;
        }

        .bg-hospital-blue-light {
            background-color: rgba(6, 75, 158, 0.1);
        }

        .text-hospital-blue {
            color: #064b9e;
        }

        .border-hospital-blue {
            border-color: #064b9e;
        }

        .gradient-hospital {
            background: linear-gradient(135deg, #064b9e 0%, #0a5fb4 100%);
        }

        .gradient-hospital-light {
            background: linear-gradient(135deg, #0a5fb4 0%, #1e7dd8 100%);
        }

        /* Animación para el mensaje ticker */
        @keyframes ticker-scroll {
            0% { transform: translateX(100%); }
            15% { transform: translateX(0%); }
            100% { transform: translateX(-100%); }
        }

        @keyframes ticker-glow {
            0%, 100% { text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3), 0 0 10px rgba(255, 255, 255, 0.1); }
            50% { text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3), 0 0 20px rgba(255, 255, 255, 0.2); }
        }

        .ticker-container {
            overflow: hidden;
            white-space: nowrap;
            position: relative;
            background: linear-gradient(135deg, #064b9e 0%, #0a5fb4 100%);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .ticker-content {
            display: inline-block;
            animation: ticker-scroll {{ $tvConfig->ticker_speed }}s linear infinite;
            white-space: nowrap;
            padding-left: 100%;
        }

        .ticker-text {
            color: white;
            font-weight: 600;
            font-size: 1.25rem;
            animation: ticker-glow 3s ease-in-out infinite;
            letter-spacing: 0.5px;
        }

        /* Mejoras visuales adicionales */
        .enhanced-shadow {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .enhanced-border {
            border: 2px solid #064b9e;
            border-radius: 8px;
        }

        /* Transiciones para multimedia */
        .media-transition {
            transition: opacity 0.8s ease-in-out, transform 0.8s ease-in-out;
        }

        .media-fade-in {
            opacity: 1;
            transform: scale(1);
        }

        .media-fade-out {
            opacity: 0;
            transform: scale(0.95);
        }

        .media-loading {
            opacity: 0;
            transform: scale(1.05);
        }

        /* Animación de entrada suave */
        @keyframes mediaEnter {
            0% {
                opacity: 0;
                transform: scale(0.95) translateY(10px);
            }
            100% {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .media-enter {
            animation: mediaEnter 1s ease-out forwards;
        }

        /* ===== RESPONSIVE DESIGN ===== */

        /* Variables CSS para escalado dinámico */
        :root {
            --scale-factor: 1;
            --header-height: 8rem;
            --ticker-height: 4rem;
        }

        /* Pantallas muy pequeñas (móviles en landscape, tablets pequeñas) */
        @media (max-width: 768px) {
            :root {
                --scale-factor: 0.6;
                --header-height: 6rem;
                --ticker-height: 3rem;
            }

            .text-5xl { font-size: 2rem !important; }
            .text-4xl { font-size: 1.5rem !important; }
            .text-3xl { font-size: 1.25rem !important; }
            .text-2xl { font-size: 1rem !important; }
            .text-xl { font-size: 0.875rem !important; }
            .text-6xl { font-size: 2.5rem !important; }
            .text-8xl { font-size: 3rem !important; }

            .ticker-text { font-size: 0.875rem !important; }

            .p-8 { padding: 1rem !important; }
            .p-6 { padding: 0.75rem !important; }
            .p-4 { padding: 0.5rem !important; }

            .space-y-3 > * + * { margin-top: 0.5rem !important; }

            /* Layout vertical para pantallas pequeñas */
            .responsive-main {
                display: flex !important;
                flex-direction: column !important;
            }

            .responsive-main > div:first-child {
                flex: 2 !important;
                min-height: 60% !important;
            }

            .responsive-main > div:last-child {
                flex: 1 !important;
                min-height: 40% !important;
            }

            /* Ajustar header para pantallas pequeñas */
            .responsive-header {
                grid-template-columns: 1fr 2fr 1fr !important;
            }

            .responsive-header > div:first-child h1 {
                font-size: 1.5rem !important;
            }

            .responsive-header > div:first-child p {
                font-size: 0.875rem !important;
            }

            /* Ajustes específicos para turnos en pantallas pequeñas */
            .turno-numero {
                font-size: clamp(1.5rem, 4vw, 3rem) !important;
            }

            .turno-caja {
                font-size: clamp(1rem, 2.25vw, 1.5rem) !important;
            }

            /* Reducir padding en turnos para pantallas pequeñas */
            .responsive-queue-section .p-3 {
                padding: 0.5rem !important;
            }

            /* Ajustar espaciado entre turnos */
            .responsive-queue-section #patient-queue {
                gap: 0.25rem !important;
            }
        }

        /* Pantallas medianas (tablets, laptops pequeños) */
        @media (min-width: 769px) and (max-width: 1024px) {
            :root {
                --scale-factor: 0.8;
                --header-height: 7rem;
                --ticker-height: 3.5rem;
            }

            .text-5xl { font-size: 2.5rem !important; }
            .text-4xl { font-size: 2rem !important; }
            .text-3xl { font-size: 1.5rem !important; }
            .text-2xl { font-size: 1.25rem !important; }
            .text-6xl { font-size: 3rem !important; }
            .text-8xl { font-size: 4rem !important; }

            .ticker-text { font-size: 1rem !important; }

            /* Ajustes para turnos en pantallas medianas */
            .turno-numero {
                font-size: clamp(1.75rem, 3.5vw, 3.5rem) !important;
            }

            .turno-caja {
                font-size: clamp(1.125rem, 2vw, 1.5rem) !important;
            }
        }

        /* Pantallas grandes (laptops, monitores estándar) */
        @media (min-width: 1025px) and (max-width: 1440px) {
            :root {
                --scale-factor: 1;
                --header-height: 8rem;
                --ticker-height: 4rem;
            }
        }

        /* Pantallas muy grandes (monitores 4K, TVs) */
        @media (min-width: 1441px) {
            :root {
                --scale-factor: 1.3;
                --header-height: 10rem;
                --ticker-height: 5rem;
            }

            .text-5xl { font-size: 4rem !important; }
            .text-4xl { font-size: 3rem !important; }
            .text-3xl { font-size: 2rem !important; }
            .text-2xl { font-size: 1.5rem !important; }
            .text-xl { font-size: 1.25rem !important; }
            .text-6xl { font-size: 5rem !important; }
            .text-8xl { font-size: 8rem !important; }

            .ticker-text { font-size: 1.5rem !important; }

            .p-8 { padding: 3rem !important; }
            .p-6 { padding: 2rem !important; }
            .p-4 { padding: 1.5rem !important; }

            .space-y-3 > * + * { margin-top: 1rem !important; }
        }

        /* Pantallas ultra anchas (monitores ultrawide) */
        @media (min-width: 1921px) {
            :root {
                --scale-factor: 1.5;
                --header-height: 12rem;
                --ticker-height: 6rem;
            }

            .text-5xl { font-size: 5rem !important; }
            .text-4xl { font-size: 4rem !important; }
            .text-3xl { font-size: 2.5rem !important; }
            .text-2xl { font-size: 2rem !important; }
            .text-xl { font-size: 1.5rem !important; }
            .text-6xl { font-size: 6rem !important; }
            .text-8xl { font-size: 10rem !important; }

            .ticker-text { font-size: 2rem !important; }

            .p-8 { padding: 4rem !important; }
            .p-6 { padding: 3rem !important; }
            .p-4 { padding: 2rem !important; }
        }

        /* Ajustes específicos para orientación landscape en móviles */
        @media (max-height: 500px) and (orientation: landscape) {
            :root {
                --header-height: 4rem;
                --ticker-height: 2rem;
            }

            .text-5xl { font-size: 1.5rem !important; }
            .text-4xl { font-size: 1.25rem !important; }
            .text-3xl { font-size: 1rem !important; }
            .text-6xl { font-size: 2rem !important; }
            .text-8xl { font-size: 2.5rem !important; }

            .p-8 { padding: 0.5rem !important; }
            .p-6 { padding: 0.5rem !important; }
            .p-4 { padding: 0.25rem !important; }

            /* Ajustes extremos para turnos en landscape móvil */
            .turno-numero {
                font-size: clamp(1rem, 2.5vw, 2rem) !important;
            }

            .turno-caja {
                font-size: clamp(0.75rem, 1.5vw, 1.125rem) !important;
            }

            /* Reducir espaciado al mínimo */
            .responsive-queue-section #patient-queue {
                gap: 0.125rem !important;
            }
        }

        /* Clases responsive dinámicas */
        .responsive-header {
            height: var(--header-height);
        }

        .responsive-ticker {
            height: var(--ticker-height);
        }

        .responsive-main {
            height: calc(100vh - var(--header-height));
        }

        /* Cuando el ticker está habilitado, ajustar la altura */
        .ticker-enabled .responsive-main {
            height: calc(100vh - var(--header-height) - var(--ticker-height));
        }

        /* Ajustes para el contenido multimedia */
        @media (max-width: 768px) {
            .multimedia-placeholder-icon { font-size: 3rem !important; }
            .multimedia-placeholder-title { font-size: 1.25rem !important; }
            .multimedia-placeholder-subtitle { font-size: 1rem !important; }

            /* Ajustar cola de turnos para pantallas pequeñas */
            .responsive-queue-section .space-y-3 > * + * { margin-top: 0.25rem !important; }
            .responsive-queue-section .text-6xl { font-size: 1.5rem !important; }
            .responsive-queue-section .text-3xl { font-size: 1rem !important; }
            .responsive-queue-section .p-4 { padding: 0.5rem !important; }

            /* Asegurar que se vean los 5 turnos */
            .responsive-queue-section {
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
            }

            .responsive-queue-section .space-y-3 {
                display: flex !important;
                flex-direction: column !important;
                height: 100% !important;
                justify-content: space-between !important;
            }
        }

        @media (min-width: 1441px) {
            .multimedia-placeholder-icon { font-size: 8rem !important; }
            .multimedia-placeholder-title { font-size: 2.5rem !important; }
            .multimedia-placeholder-subtitle { font-size: 1.5rem !important; }

            /* Mejorar espaciado en pantallas grandes */
            .responsive-queue-section .space-y-3 > * + * { margin-top: 1.5rem !important; }
            .responsive-queue-section .p-4 { padding: 2rem !important; }
        }

        /* Ajustes específicos para diferentes aspectos de pantalla */
        @media (max-aspect-ratio: 4/3) {
            /* Pantallas más altas que anchas (tablets en portrait) */
            .responsive-main {
                grid-template-columns: 1fr !important;
                grid-template-rows: 1fr auto !important;
            }

            .responsive-multimedia-section {
                grid-column: 1 !important;
                grid-row: 1 !important;
            }

            .responsive-queue-section {
                grid-column: 1 !important;
                grid-row: 2 !important;
                max-height: 40vh !important;
                overflow-y: auto !important;
            }
        }

        @media (min-aspect-ratio: 21/9) {
            /* Pantallas ultra anchas */
            .responsive-main {
                grid-template-columns: 2fr 1fr !important;
            }

            .responsive-multimedia-section {
                grid-column: 1 !important;
            }

            .responsive-queue-section {
                grid-column: 2 !important;
            }
        }

        /* Mejoras para la legibilidad en diferentes tamaños */
        .responsive-text-scale {
            font-size: calc(1rem * var(--scale-factor));
        }

        /* Asegurar que el contenido siempre sea visible */
        .responsive-container {
            min-height: 0;
            overflow: hidden;
        }

        /* Optimización para la cola de turnos - asegurar que se vean los 5 turnos */
        .responsive-queue-section {
            display: flex;
            flex-direction: column;
        }

        .responsive-queue-section #patient-queue {
            display: flex;
            flex-direction: column;
            height: 100%;
            justify-content: space-between;
            gap: 0;
            padding: 0.5rem 0;
        }

        .responsive-queue-section #patient-queue > div {
            flex: 1;
            height: calc(20% - 0.4rem);
            min-height: 60px;
            max-height: none;
            overflow: hidden;
            box-sizing: border-box;
            margin-bottom: 0.5rem;
        }

        .responsive-queue-section #patient-queue > div:last-child {
            margin-bottom: 0;
        }

        /* Contenedor interno de cada turno */
        .turno-content {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Texto de turnos con límites estrictos y mejor escalado */
        .turno-numero {
            font-size: clamp(1.75rem, 4.5vw, 4rem);
            line-height: 1;
            max-height: 100%;
            overflow: hidden;
            white-space: nowrap;
        }

        .turno-caja {
            font-size: clamp(1.125rem, 2.25vw, 1.75rem);
            line-height: 1.2;
            max-height: 100%;
            overflow: hidden;
            white-space: nowrap;
        }

        /* Ajustes específicos para diferentes alturas de pantalla */
        @media (max-height: 800px) {
            .turno-numero {
                font-size: clamp(1.5rem, 3.5vw, 3rem) !important;
            }

            .turno-caja {
                font-size: clamp(1rem, 2vw, 1.5rem) !important;
            }

            .responsive-queue-section .p-4 {
                padding: 0.75rem !important;
            }
        }

        @media (max-height: 600px) {
            .turno-numero {
                font-size: clamp(1.25rem, 3vw, 2.5rem) !important;
            }

            .turno-caja {
                font-size: clamp(0.875rem, 1.75vw, 1.25rem) !important;
            }

            .responsive-queue-section .p-4 {
                padding: 0.5rem !important;
            }

            .responsive-queue-section #patient-queue {
                gap: 0.25rem !important;
            }
        }

        @media (max-height: 400px) {
            .turno-numero {
                font-size: clamp(1rem, 2.5vw, 2rem) !important;
            }

            .turno-caja {
                font-size: clamp(0.75rem, 1.5vw, 1rem) !important;
            }

            .responsive-queue-section .p-4 {
                padding: 0.25rem !important;
            }

            .responsive-queue-section #patient-queue {
                gap: 0.125rem !important;
            }
        }

        /* Prevenir desbordamiento en resoluciones altas */
        .responsive-queue-section .text-6xl {
            max-height: 100%;
            line-height: 1;
            overflow: hidden;
            display: flex;
            align-items: center;
        }

        .responsive-queue-section .text-3xl {
            max-height: 100%;
            line-height: 1.2;
            overflow: hidden;
            display: flex;
            align-items: center;
        }

        /* Limitar el tamaño máximo de fuente para evitar desbordamiento */
        @media (min-width: 1441px) {
            .responsive-queue-section .text-6xl {
                font-size: min(4rem, 8vh) !important;
                max-height: 8vh;
            }
            .responsive-queue-section .text-3xl {
                font-size: min(1.5rem, 4vh) !important;
                max-height: 4vh;
            }
        }

        @media (min-width: 1921px) {
            .responsive-queue-section .text-6xl {
                font-size: min(5rem, 10vh) !important;
                max-height: 10vh;
            }
            .responsive-queue-section .text-3xl {
                font-size: min(2rem, 5vh) !important;
                max-height: 5vh;
            }
        }

        /* Asegurar que cada turno no exceda su espacio asignado */
        .responsive-queue-section #patient-queue > div {
            max-height: calc(20vh - 1rem);
            overflow: hidden;
        }

        /* Ajustes para el logo del hospital */
        @media (max-width: 768px) {
            .responsive-header img {
                height: 3rem !important;
                max-height: 3rem !important;
            }
        }

        @media (min-width: 1441px) {
            .responsive-header img {
                height: 6rem !important;
                max-height: 6rem !important;
            }
        }
    </style>
</head>
<body class="w-full h-screen bg-white overflow-hidden {{ $tvConfig->ticker_enabled ? 'ticker-enabled' : '' }}">
    <div class="w-full h-full bg-white">
        <!-- Header Section -->
        <div class="grid grid-cols-6 responsive-header responsive-container">
            <!-- Left Header - UBA y Hora -->
            <div class="bg-hospital-blue-light p-4 flex flex-col justify-center col-span-2">
                <h1 class="text-5xl font-bold text-hospital-blue leading-tight mb-2">UBA</h1>
                <!-- Hora de Colombia (UTC-5) -->
                <p class="text-2xl text-hospital-blue font-semibold" id="current-time">{{ \Carbon\Carbon::now('America/Bogota')->format('M d - H:i') }}</p>
            </div>

            <!-- Center Header - Hospital Info con Logo -->
            <div class="bg-hospital-blue-light p-4 pl-32 pr-2 flex items-center space-x-4 justify-end col-span-2">
                <!-- Logo del Hospital -->
                <div class="flex-shrink-0">
                    <img src="{{ asset('images/logoacreditacion.png') }}" alt="Logo Hospital Universitario del Valle" class="h-24 w-auto max-w-none responsive-header" style="mix-blend-mode: multiply; filter: contrast(1.2);">
                </div>

                <!-- Información del Hospital -->
                <div class="flex-shrink-0">
                    <h2 class="text-xl font-bold text-hospital-blue leading-tight">HOSPITAL UNIVERSITARIO</h2>
                    <h3 class="text-xl font-bold text-hospital-blue leading-tight">DEL VALLE</h3>
                    <p class="text-sm text-hospital-blue italic">"Evaristo García" E.S.E</p>
                </div>
            </div>

            <!-- Right Header - Turno y Módulo -->
            <div class="gradient-hospital flex col-span-2">
                <div class="flex-1 bg-hospital-blue flex items-center justify-center">
                    <h1 class="text-4xl font-bold text-white">TURNO</h1>
                </div>
                <div class="flex-1 gradient-hospital-light flex items-center justify-center">
                    <h1 class="text-4xl font-bold text-white">MÓDULO</h1>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-6 responsive-main responsive-container">
            <!-- Left Side - Multimedia Content -->
            <div class="bg-hospital-blue-light p-6 flex flex-col col-span-4 responsive-multimedia-section responsive-container">
                <!-- Espacio para videos/fotos - Ahora ocupa todo el espacio disponible -->
                <div class="flex-1 bg-white rounded-lg enhanced-border enhanced-shadow flex items-center justify-center relative overflow-hidden" id="multimedia-container">
                    <!-- Contenido multimedia dinámico -->
                    <div id="multimedia-content" class="w-full h-full flex items-center justify-center">
                        <!-- Placeholder content con mejor diseño -->
                        <div id="multimedia-placeholder" class="text-center text-gray-400 z-10">
                            <div class="text-8xl mb-4 opacity-50 multimedia-placeholder-icon">🏥</div>
                            <p class="text-2xl font-semibold text-hospital-blue mb-2 multimedia-placeholder-title">Contenido Multimedia</p>
                            <p class="text-lg text-gray-500 multimedia-placeholder-subtitle">Videos e imágenes del hospital</p>
                        </div>

                        <!-- Decorative background pattern -->
                        <div class="absolute inset-0 opacity-5">
                            <div class="w-full h-full" style="background-image: repeating-linear-gradient(45deg, #064b9e 0px, #064b9e 10px, transparent 10px, transparent 20px);"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side - Patient Queue -->
            <div class="bg-hospital-blue-light p-8 col-span-2 responsive-queue-section responsive-container">
                <!-- Patient Numbers - Alineados con TURNO y MÓDULO del header -->
                <div class="space-y-3 overflow-hidden" id="patient-queue">
                    <div class="gradient-hospital text-white p-3 enhanced-shadow rounded-lg animate-slide-in flex items-center h-full">
                        <div class="grid grid-cols-2 gap-4 items-center w-full">
                            <div class="text-left flex items-center">
                                <div class="turno-numero font-bold animate-pulse-number">U001</div>
                            </div>
                            <div class="text-right flex items-center justify-end pr-2">
                                <div class="turno-caja font-semibold">CAJA 1</div>
                            </div>
                        </div>
                    </div>

                    <div class="gradient-hospital text-white p-3 enhanced-shadow rounded-lg animate-slide-in flex items-center h-full" style="animation-delay: 0.2s;">
                        <div class="grid grid-cols-2 gap-4 items-center w-full">
                            <div class="text-left flex items-center">
                                <div class="turno-numero font-bold">U002</div>
                            </div>
                            <div class="text-right flex items-center justify-end pr-2">
                                <div class="turno-caja font-semibold">CAJA 2</div>
                            </div>
                        </div>
                    </div>

                    <div class="gradient-hospital text-white p-3 enhanced-shadow rounded-lg animate-slide-in flex items-center h-full" style="animation-delay: 0.4s;">
                        <div class="grid grid-cols-2 gap-4 items-center w-full">
                            <div class="text-left flex items-center">
                                <div class="turno-numero font-bold">U003</div>
                            </div>
                            <div class="text-right flex items-center justify-end pr-2">
                                <div class="turno-caja font-semibold">CAJA 3</div>
                            </div>
                        </div>
                    </div>

                    <div class="gradient-hospital text-white p-3 enhanced-shadow rounded-lg animate-slide-in flex items-center h-full" style="animation-delay: 0.6s;">
                        <div class="grid grid-cols-2 gap-4 items-center w-full">
                            <div class="text-left flex items-center">
                                <div class="turno-numero font-bold">U004</div>
                            </div>
                            <div class="text-right flex items-center justify-end pr-2">
                                <div class="turno-caja font-semibold">CAJA 4</div>
                            </div>
                        </div>
                    </div>

                    <div class="gradient-hospital text-white p-3 enhanced-shadow rounded-lg animate-slide-in flex items-center h-full" style="animation-delay: 0.8s;">
                        <div class="grid grid-cols-2 gap-4 items-center w-full">
                            <div class="text-left flex items-center">
                                <div class="turno-numero font-bold">U005</div>
                            </div>
                            <div class="text-right flex items-center justify-end pr-2">
                                <div class="turno-caja font-semibold">CAJA 5</div>
                            </div>
                        </div>
                    </div>

                    <div class="gradient-hospital text-white p-4 enhanced-shadow rounded-lg animate-slide-in" style="animation-delay: 1.0s;">
                        <div class="grid grid-cols-2 gap-4 items-center">
                            <div class="text-center">
                                <div class="text-6xl font-bold">U006</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-semibold">CAJA 6</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mensaje Ticker - En la parte inferior de la página -->
        <div class="ticker-container responsive-ticker flex items-center border-t-2 border-hospital-blue" style="display: {{ $tvConfig->ticker_enabled ? 'flex' : 'none' }};">
            <div class="ticker-content">
                <span class="ticker-text">
                    {{ $tvConfig->ticker_message }}
                </span>
            </div>
        </div>
    </div>

    <script>
        // ===== SISTEMA RESPONSIVE DINÁMICO =====
        function initializeResponsiveSystem() {
            // Función para ajustar el escalado dinámico basado en la resolución
            function updateScaleFactor() {
                const width = window.innerWidth;
                const height = window.innerHeight;
                const aspectRatio = width / height;

                let scaleFactor = 1;

                // Calcular factor de escala basado en resolución
                if (width <= 768) {
                    scaleFactor = 0.6;
                } else if (width <= 1024) {
                    scaleFactor = 0.8;
                } else if (width <= 1440) {
                    scaleFactor = 1;
                } else if (width <= 1920) {
                    scaleFactor = 1.3;
                } else {
                    scaleFactor = 1.5;
                }

                // Ajustar por aspect ratio
                if (aspectRatio < 1.2) { // Pantallas muy altas
                    scaleFactor *= 0.8;
                } else if (aspectRatio > 2.5) { // Pantallas ultra anchas
                    scaleFactor *= 1.1;
                }

                // Aplicar el factor de escala
                document.documentElement.style.setProperty('--scale-factor', scaleFactor);

                console.log(`📱 Resolución: ${width}x${height}, Aspect: ${aspectRatio.toFixed(2)}, Scale: ${scaleFactor}`);
            }

            // Función para optimizar el layout según el tamaño de pantalla
            function optimizeLayout() {
                const width = window.innerWidth;
                const height = window.innerHeight;

                // SIEMPRE mostrar exactamente 5 turnos - este es el diseño requerido
                const queueContainer = document.getElementById('patient-queue');
                if (queueContainer) {
                    const turnos = queueContainer.children;
                    const maxVisible = 5; // FIJO: siempre 5 turnos

                    // Mostrar exactamente 5 turnos, ocultar el resto
                    for (let i = 0; i < turnos.length; i++) {
                        if (i < maxVisible) {
                            turnos[i].style.display = 'block';
                        } else {
                            turnos[i].style.display = 'none';
                        }
                    }

                    // Asegurar que el contenedor use todo el espacio disponible
                    queueContainer.style.height = '100%';
                    queueContainer.style.display = 'flex';
                    queueContainer.style.flexDirection = 'column';
                    queueContainer.style.justifyContent = 'space-evenly';
                }
            }

            // Ejecutar al cargar y al cambiar tamaño
            updateScaleFactor();
            optimizeLayout();

            // Escuchar cambios de tamaño de ventana
            let resizeTimeout;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(function() {
                    updateScaleFactor();
                    optimizeLayout();
                }, 250);
            });

            // Escuchar cambios de orientación
            window.addEventListener('orientationchange', function() {
                setTimeout(function() {
                    updateScaleFactor();
                    optimizeLayout();
                }, 500);
            });
        }

        // Variables globales
        let turnos = []; // Historial de turnos
        let turnosVistos = new Set(); // Conjunto para rastrear turnos ya mostrados
        let ultimoTurnoId = null; // ID del último turno para detectar nuevos
        let sincronizacionActiva = true; // Control de sincronización

        // Sistema de cola de audio
        let colaAudio = []; // Cola de turnos pendientes de reproducir
        let reproduciendoAudio = false; // Estado de reproducción actual
        let colaProtegida = false; // Protección contra limpieza de cola durante reproducción
        let sessionId = null; // ID único de sesión para evitar duplicados

        // Generar ID único de sesión
        function generarSessionId() {
            return 'tv_session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        }

        // Obtener turnos ya reproducidos en esta sesión desde localStorage
        function getTurnosReproducidos() {
            try {
                const stored = localStorage.getItem('turnos_reproducidos_' + sessionId);
                return stored ? new Set(JSON.parse(stored)) : new Set();
            } catch (e) {
                return new Set();
            }
        }

        // Guardar turno como reproducido en localStorage
        function marcarTurnoReproducido(turnoId) {
            try {
                const reproducidos = getTurnosReproducidos();
                reproducidos.add(turnoId);
                localStorage.setItem('turnos_reproducidos_' + sessionId, JSON.stringify([...reproducidos]));
            } catch (e) {
                console.warn('No se pudo guardar en localStorage');
            }
        }

        // Limpiar turnos reproducidos antiguos (más de 1 hora)
        function limpiarTurnosAntiguos() {
            try {
                const keys = Object.keys(localStorage);
                const ahora = Date.now();
                const unaHora = 60 * 60 * 1000;

                keys.forEach(key => {
                    if (key.startsWith('turnos_reproducidos_tv_session_')) {
                        const timestamp = parseInt(key.split('_')[3]);
                        if (ahora - timestamp > unaHora) {
                            localStorage.removeItem(key);
                        }
                    }
                });
            } catch (e) {
                console.warn('No se pudo limpiar localStorage');
            }
        }

        // Agregar turno a la cola de audio (solo para repeticiones manuales)
        function agregarAColaAudio(turno) {
            // Verificar si ya está en la cola (permitir repeticiones con ID único)
            const yaEnCola = colaAudio.some(t => t.id === turno.id);
            if (!yaEnCola) {
                colaAudio.push(turno);
                console.log('🎵 Turno agregado a cola de audio (manual):', turno.codigo_completo, '(Cola actual:', colaAudio.length, 'turnos)');

                // Solo procesar la cola si no hay audio reproduciéndose
                if (!reproduciendoAudio) {
                    procesarColaAudio();
                }
            } else {
                console.log('⚠️ Turno ya está en cola de audio:', turno.codigo_completo);
            }
        }

        // Procesar cola de audio (reproducir siguiente si no está ocupado)
        function procesarColaAudio() {
            console.log('🔄 procesarColaAudio() llamado - Estado:', {
                reproduciendoAudio: reproduciendoAudio,
                colaLength: colaAudio.length,
                cola: colaAudio.map(t => t.codigo_completo)
            });

            // Verificar si ya hay audio reproduciéndose o no hay turnos en cola
            if (reproduciendoAudio) {
                console.log('⏸️ Audio ya reproduciéndose, esperando...');
                return;
            }

            if (colaAudio.length === 0) {
                console.log('📭 Cola de audio vacía');
                return;
            }

            const siguienteTurno = colaAudio.shift();
            reproduciendoAudio = true;
            colaProtegida = true; // Activar protección de cola
            window.ultimoInicioReproduccion = Date.now(); // Timestamp para verificación de bloqueos

            console.log('🔊 Iniciando reproducción de audio:', siguienteTurno.codigo_completo, '(Turnos restantes en cola:', colaAudio.length, ') 🛡️ Cola protegida');

            // Marcar como reproducido antes de empezar (solo para turnos reales, no repeticiones)
            if (!siguienteTurno.id.toString().startsWith('repetir_')) {
                marcarTurnoReproducido(siguienteTurno.id);
            }

            // Reproducir audio con callback al terminar
            playVoiceMessage(siguienteTurno, () => {
                reproduciendoAudio = false;
                window.ultimoInicioReproduccion = null; // Limpiar timestamp

                // Solo desactivar protección si no hay más turnos en cola
                if (colaAudio.length === 0) {
                    colaProtegida = false;
                    console.log('✅ Audio completado:', siguienteTurno.codigo_completo, '- Cola vacía, protección desactivada');
                } else {
                    console.log('✅ Audio completado:', siguienteTurno.codigo_completo, '(Turnos restantes en cola:', colaAudio.length, ') - Manteniendo protección');
                }

                // Procesar siguiente en la cola después de una pausa
                setTimeout(() => {
                    console.log('⏰ Timeout completado, procesando siguiente turno...');
                    procesarColaAudio();
                }, 1000); // Pausa de 1 segundo entre turnos
            });
        }

        // Limpiar cola de audio (con protección)
        function limpiarColaAudio(forzar = false) {
            if (colaProtegida && !forzar) {
                console.log('🛡️ Cola de audio protegida - limpieza bloqueada');
                return false;
            }

            colaAudio = [];
            reproduciendoAudio = false;
            colaProtegida = false;
            console.log('🧹 Cola de audio limpiada' + (forzar ? ' (forzada)' : ''));
            return true;
        }

        // Función de seguridad para detectar y resolver bloqueos en la cola de audio
        function verificarEstadoColaAudio() {
            const ahora = Date.now();

            // Si hay turnos en cola pero no se está reproduciendo nada, intentar procesar
            if (colaAudio.length > 0 && !reproduciendoAudio) {
                console.log('🔧 Detectado posible bloqueo en cola de audio, reactivando procesamiento...', {
                    colaLength: colaAudio.length,
                    reproduciendoAudio: reproduciendoAudio,
                    turnos: colaAudio.map(t => t.codigo_completo)
                });
                procesarColaAudio();
            }

            // Verificar si el estado reproduciendoAudio está bloqueado por mucho tiempo
            if (reproduciendoAudio && window.ultimoInicioReproduccion) {
                const tiempoTranscurrido = ahora - window.ultimoInicioReproduccion;
                if (tiempoTranscurrido > 60000) { // 1 minuto máximo
                    console.warn('⚠️ Estado reproduciendoAudio bloqueado por más de 1 minuto, reseteando...');
                    reproduciendoAudio = false;
                    colaProtegida = false; // Desactivar protección en caso de bloqueo
                    if (colaAudio.length > 0) {
                        procesarColaAudio();
                    }
                }
            }
        }

        // Función para mostrar estado actual de la cola (para debugging)
        function mostrarEstadoCola() {
            console.log('📊 Estado actual de la cola de audio:', {
                reproduciendoAudio: reproduciendoAudio,
                colaProtegida: colaProtegida,
                colaLength: colaAudio.length,
                turnos: colaAudio.map(t => t.codigo_completo),
                ultimoInicioReproduccion: window.ultimoInicioReproduccion ? new Date(window.ultimoInicioReproduccion).toLocaleTimeString() : null
            });
        }

        // Función de emergencia para limpiar cola forzadamente
        function limpiarColaForzado() {
            const resultado = limpiarColaAudio(true);
            console.log('🚨 Limpieza forzada de cola:', resultado ? 'exitosa' : 'falló');
            return resultado;
        }

        // Hacer las funciones disponibles globalmente para debugging
        window.mostrarEstadoCola = mostrarEstadoCola;
        window.limpiarColaForzado = limpiarColaForzado;

        // Ejecutar verificación de estado cada 5 segundos
        setInterval(verificarEstadoColaAudio, 5000);
        let currentConfig = {
            ticker_message: '{{ addslashes($tvConfig->ticker_message) }}',
            ticker_speed: {{ $tvConfig->ticker_speed }},
            ticker_enabled: {{ $tvConfig->ticker_enabled ? 'true' : 'false' }}
        };

        // Variables globales para multimedia
        let multimediaList = [];
        let currentMediaIndex = 0;
        let mediaTimer = null;
        let isMediaPlaying = false;

        // Actualizar la hora cada minuto (Zona horaria de Colombia)
        function updateTime() {
            // Crear fecha con zona horaria de Colombia (UTC-5)
            const now = new Date();
            const colombiaTime = new Date(now.toLocaleString("en-US", {timeZone: "America/Bogota"}));

            const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            const month = months[colombiaTime.getMonth()];
            const day = colombiaTime.getDate().toString().padStart(2, '0');
            const hours = colombiaTime.getHours().toString().padStart(2, '0');
            const minutes = colombiaTime.getMinutes().toString().padStart(2, '0');

            document.getElementById('current-time').textContent = `${month} ${day} - ${hours}:${minutes}`;
        }

        // Actualizar configuración del TV desde el servidor
        function updateTvConfig() {
            fetch('/api/tv-config')
                .then(response => response.json())
                .then(data => {
                    // Verificar si la configuración ha cambiado
                    if (data.ticker_message !== currentConfig.ticker_message ||
                        data.ticker_speed !== currentConfig.ticker_speed ||
                        data.ticker_enabled !== currentConfig.ticker_enabled) {

                        currentConfig = data;
                        applyTvConfig(data);
                    }
                })
                .catch(error => {
                    console.error('Error al obtener configuración del TV:', error);
                });
        }

        // Aplicar nueva configuración al TV
        function applyTvConfig(config) {
            const tickerContainer = document.querySelector('.ticker-container');
            const tickerContent = document.querySelector('.ticker-content');
            const tickerText = document.querySelector('.ticker-text');
            // Indicador de actualización deshabilitado para mantener la vista del TV limpia

            if (config.ticker_enabled) {
                // Mostrar ticker si está habilitado
                if (tickerContainer) {
                    tickerContainer.style.display = 'flex';

                    // Actualizar mensaje
                    if (tickerText) {
                        tickerText.textContent = config.ticker_message;
                    }

                    // Reiniciar el ticker con la nueva velocidad
                    restartTicker(config.ticker_speed);
                }

                // Agregar clase al body para ajustar el layout
                document.body.classList.add('ticker-enabled');
            } else {
                // Ocultar ticker si está deshabilitado
                if (tickerContainer) {
                    tickerContainer.style.display = 'none';
                }

                // Remover clase del body
                document.body.classList.remove('ticker-enabled');
            }
        }

        // Cargar multimedia desde el servidor
        function loadMultimedia() {
            fetch('/api/multimedia')
                .then(response => response.json())
                .then(data => {
                    const newMultimediaList = data.multimedia || [];

                    // Comparar si la lista ha cambiado
                    const hasChanged = !arraysEqual(multimediaList, newMultimediaList);

                    if (hasChanged) {
                        console.log('Lista de multimedia actualizada');
                        multimediaList = newMultimediaList;

                        if (multimediaList.length > 0) {
                            // Si hay multimedia y no se está reproduciendo, iniciar
                            if (!isMediaPlaying) {
                                startMediaPlayback();
                            } else {
                                // Si se está reproduciendo, verificar si el archivo actual sigue activo
                                const currentMedia = multimediaList[currentMediaIndex];
                                if (!currentMedia) {
                                    // El archivo actual ya no existe, reiniciar desde el principio
                                    currentMediaIndex = 0;
                                    showCurrentMedia();
                                }
                            }
                        } else {
                            // No hay multimedia, mostrar placeholder
                            showPlaceholder();
                        }
                    }
                })
                .catch(error => {
                    console.error('Error al cargar multimedia:', error);
                    showPlaceholder();
                });
        }

        // Función para actualizar la cola de turnos con sincronización completa
        function updateQueue() {
            if (!sincronizacionActiva) return;

            actualizarIndicadorSync('sincronizando');

            fetch('/api/turnos-llamados')
                .then(response => response.json())
                .then(data => {
                    const newTurnos = data.turnos || [];

                    // SINCRONIZACIÓN COMPLETA: Reemplazar completamente la lista local
                    const turnosAnteriores = [...turnos];
                    turnos = [...newTurnos]; // Copiar exactamente lo que viene del servidor

                    // Obtener turnos ya reproducidos en esta sesión
                    const turnosReproducidos = getTurnosReproducidos();

                    // Detectar turnos nuevos que no han sido reproducidos
                    const turnosNuevos = [];

                    // Separar turnos por estado
                    const turnosLlamando = newTurnos.filter(t => t.estado === 'llamado');
                    const turnosAtendidos = newTurnos.filter(t => t.estado === 'atendido');

                    // Solo reproducir audio para turnos nuevos en estado "llamado"
                    turnosLlamando.forEach(turno => {
                        if (!turnosReproducidos.has(turno.id)) {
                            turnosNuevos.push(turno);
                            console.log('🔊 Nuevo turno para reproducir:', turno.codigo_completo);
                        }
                    });

                    // SIEMPRE actualizar la interfaz para mantener sincronización
                    renderTurnos(turnos);

                    // Agregar turnos nuevos a la cola de audio (en orden inverso para mantener cronología)
                    if (turnosNuevos.length > 0) {
                        console.log('🔊 Nuevos turnos detectados para audio:', turnosNuevos.length, 'Estado actual cola:', colaAudio.length, 'Reproduciendo:', reproduciendoAudio);

                        // Agregar en orden cronológico (más antiguos primero)
                        const turnosOrdenados = turnosNuevos.reverse();
                        console.log('🔊 Turnos a agregar en orden:', turnosOrdenados.map(t => t.codigo_completo));

                        // Agregar todos los turnos a la cola primero
                        turnosOrdenados.forEach(turno => {
                            const yaEnCola = colaAudio.some(t => t.id === turno.id);
                            if (!yaEnCola) {
                                colaAudio.push(turno);
                                console.log('🎵 Turno agregado a cola de audio:', turno.codigo_completo, '(Cola actual:', colaAudio.length, 'turnos)');
                            } else {
                                console.log('⚠️ Turno ya está en cola de audio:', turno.codigo_completo);
                            }
                        });

                        // Procesar la cola solo una vez después de agregar todos los turnos
                        if (!reproduciendoAudio && colaAudio.length > 0) {
                            console.log('🔊 Iniciando procesamiento de cola con', colaAudio.length, 'turnos');
                            procesarColaAudio();
                        }
                    }

                    // Log de estado para debugging (solo cuando hay cambios)
                    const llamandoCount = newTurnos.filter(t => t.estado === 'llamado').length;
                    const atendidoCount = newTurnos.filter(t => t.estado === 'atendido').length;
                    const estadisticasActuales = `${llamandoCount}-${atendidoCount}`;
                    if (window.lastEstadisticas !== estadisticasActuales) {
                        console.log(`📊 Turnos: ${llamandoCount} llamando, ${atendidoCount} atendidos`);
                        window.lastEstadisticas = estadisticasActuales;
                    }

                    // Actualizar indicador de éxito
                    actualizarIndicadorSync('sincronizado');
                })
                .catch(error => {
                    console.error('❌ Error de sincronización:', error);
                    actualizarIndicadorSync('error');
                });
        }

        // Renderizar los turnos en el contenedor
        function renderTurnos(turnosList) {
            const container = document.getElementById('patient-queue');

            // Conservar el contenedor pero limpiar su contenido
            container.innerHTML = '';

            // Limitar a máximo 5 turnos para evitar desbordamiento visual
            // Los turnos vienen ordenados por fecha_llamado DESC (más recientes primero)
            // Esto implementa un comportamiento FIFO: nuevo turno entra al principio, el más antiguo sale
            const turnosLimitados = turnosList.slice(0, 5);

            // No hay turnos, mostramos placeholders
            if (turnosLimitados.length === 0) {
                for (let i = 0; i < 5; i++) {
                    const placeholderElement = document.createElement('div');
                    placeholderElement.className = 'gradient-hospital text-white p-3 enhanced-shadow rounded-lg opacity-50 flex items-center h-full';

                    placeholderElement.innerHTML = `
                        <div class="grid grid-cols-2 gap-4 items-center w-full">
                            <div class="text-left flex items-center">
                                <div class="turno-numero font-bold">----</div>
                            </div>
                            <div class="text-right flex items-center justify-end pr-2">
                                <div class="turno-caja font-semibold">CAJA -</div>
                            </div>
                        </div>
                    `;

                    container.appendChild(placeholderElement);
                }
                return;
            }

            // Mostrar turnos existentes (máximo 5)
            for (let i = 0; i < turnosLimitados.length; i++) {
                const turno = turnosLimitados[i];

                // Crear elemento del turno
                const turnoElement = document.createElement('div');

                // Determinar estilo según el estado
                const esAtendido = turno.estado === 'atendido';
                const yaAnimado = sessionStorage.getItem('turno_animado_' + turno.id);

                // MANTENER EL DISEÑO ORIGINAL - Solo cambiar el badge
                let clases = 'gradient-hospital text-white p-3 enhanced-shadow rounded-lg flex items-center h-full';

                // Animación solo para turnos nuevos llamados
                if (i === 0 && !yaAnimado && !esAtendido) {
                    clases += ' new-turn';
                    sessionStorage.setItem('turno_animado_' + turno.id, 'true');
                }

                turnoElement.className = clases;

                // Badge de estado en la esquina superior derecha
                const estadoBadge = esAtendido ?
                    '<div class="absolute -top-3 right-2"><span class="bg-green-500 text-white px-2 py-1 rounded-full text-xs font-bold">✓ ATENDIDO</span></div>' :
                    '';

                turnoElement.innerHTML = `
                    <div class="relative">
                        ${estadoBadge}
                        <div class="grid grid-cols-2 gap-4 items-center w-full">
                            <div class="text-left flex items-center">
                                <div class="turno-numero font-bold">${turno.codigo_completo}</div>
                            </div>
                            <div class="text-right flex items-center justify-end pr-2">
                                <div class="turno-caja font-semibold">CAJA ${turno.numero_caja || ''}</div>
                            </div>
                        </div>
                    </div>
                `;

                container.appendChild(turnoElement);
            }

            // Si hay menos de 5 turnos, rellenar con placeholders
            for (let i = turnosLimitados.length; i < 5; i++) {
                const placeholderElement = document.createElement('div');
                placeholderElement.className = 'gradient-hospital text-white p-3 enhanced-shadow rounded-lg opacity-50 flex items-center h-full';

                placeholderElement.innerHTML = `
                    <div class="grid grid-cols-2 gap-4 items-center w-full">
                        <div class="text-left flex items-center">
                            <div class="turno-numero font-bold">----</div>
                        </div>
                        <div class="text-right flex items-center justify-end pr-2">
                            <div class="turno-caja font-semibold">CAJA -</div>
                        </div>
                    </div>
                `;

                container.appendChild(placeholderElement);
            }
        }



        // Función para reproducir el mensaje de voz usando archivos pre-generados (DINÁMICO)
        function playVoiceMessage(turno, onComplete = null) {
            const codigoCompleto = turno.codigo_completo;
            const numeroCaja = turno.numero_caja;

            // Almacenar como último turno llamado para repetición manual
            ultimoTurnoLlamado = turno;

            console.log('🔊 Procesando turno:', turno);

            // Separar el código del servicio y el número del turno
            const partes = separarCodigoTurno(codigoCompleto);

            // Crear secuencia de archivos de audio dinámicamente
            const audioSequence = [
                '/audio/turnero/turno.mp3',                                 // Sonido de alerta/pito
                '/audio/turnero/voice/frases/turno.mp3'                     // "Turno"
            ];

            // Agregar todas las letras del código del servicio dinámicamente
            partes.letrasServicio.forEach(letra => {
                audioSequence.push(`/audio/turnero/voice/letras/${letra}.mp3`);
            });

            // Agregar el número del turno si existe
            if (partes.numeroTurno) {
                audioSequence.push(`/audio/turnero/voice/numeros/${partes.numeroTurno}.mp3`);
            }

            // Agregar frase de dirección y número de caja
            audioSequence.push('/audio/turnero/voice/frases/dirigirse-caja-numero.mp3');
            audioSequence.push(`/audio/turnero/voice/numeros/${numeroCaja}.mp3`);

            console.log('🔊 Secuencia de audio generada:', audioSequence.map(file => file.split('/').pop()));

            // Indicador de audio deshabilitado para mantener la vista del TV limpia

            // Reproducir la secuencia 2 veces automáticamente
            console.log('🔊 Iniciando playAudioSequenceWithRepeat para:', codigoCompleto);
            playAudioSequenceWithRepeat(audioSequence, 2, () => {
                console.log('🔊 playVoiceMessage completado para:', codigoCompleto);
                if (onComplete) {
                    onComplete();
                }
            });
        }

        // Función para separar dinámicamente el código del servicio y número del turno
        function separarCodigoTurno(codigoCompleto) {
            // El formato es: CODIGO-NUMERO (ej: "CIT-001", "COPAGOS-123")
            const partes = codigoCompleto.split('-');

            let codigoServicio = '';
            let numeroTurno = '';

            if (partes.length >= 2) {
                // Hay guión, separar código y número
                codigoServicio = partes[0].trim().toUpperCase();
                numeroTurno = parseInt(partes[1], 10).toString(); // Eliminar ceros a la izquierda
            } else {
                // No hay guión, intentar separar letras y números
                const match = codigoCompleto.match(/^([A-Za-z]+)(\d+)$/);
                if (match) {
                    codigoServicio = match[1].toUpperCase();
                    numeroTurno = parseInt(match[2], 10).toString();
                } else {
                    // Fallback: todo como código de servicio
                    codigoServicio = codigoCompleto.toUpperCase();
                }
            }

            // Convertir el código del servicio en letras individuales
            const letrasServicio = codigoServicio.split('');

            console.log('📝 Código separado:', {
                original: codigoCompleto,
                codigoServicio: codigoServicio,
                letrasServicio: letrasServicio,
                numeroTurno: numeroTurno
            });

            return {
                codigoServicio: codigoServicio,
                letrasServicio: letrasServicio,
                numeroTurno: numeroTurno
            };
        }





        // Función para actualizar indicador de sincronización (deshabilitada para TV)
        function actualizarIndicadorSync(estado) {
            // Función deshabilitada para mantener la vista del TV limpia
            // Solo se mantiene para compatibilidad con el código existente
            return;
        }

        // Función para sincronización inicial
        function sincronizacionInicial() {
            actualizarIndicadorSync('sincronizando');

            // Generar nuevo ID de sesión si no existe
            if (!sessionId) {
                sessionId = generarSessionId();
                limpiarTurnosAntiguos(); // Limpiar datos antiguos
            }

            // Limpiar estado local
            turnos = [];
            turnosVistos.clear();
            ultimoTurnoId = null;

            // Intentar limpiar cola de audio (respetando protección)
            const limpiado = limpiarColaAudio();
            if (!limpiado) {
                console.log('⚠️ Sincronización inicial - cola protegida, manteniendo estado actual');
            }

            // Hacer primera sincronización y marcar turnos existentes como ya reproducidos
            fetch('/api/turnos-llamados')
                .then(response => response.json())
                .then(data => {
                    const turnosExistentes = data.turnos || [];

                    // SOLUCIÓN: Marcar todos los turnos existentes como ya reproducidos
                    // para evitar que suenen cuando alguien ingresa por primera vez a la página
                    const turnosLlamandoExistentes = turnosExistentes.filter(t => t.estado === 'llamado');
                    turnosLlamandoExistentes.forEach(turno => {
                        marcarTurnoReproducido(turno.id);
                    });

                    if (turnosLlamandoExistentes.length > 0) {
                        console.log(`🔇 ${turnosLlamandoExistentes.length} turnos existentes marcados como ya reproducidos:`,
                                   turnosLlamandoExistentes.map(t => t.codigo_completo));
                    } else {
                        console.log('ℹ️ No hay turnos existentes en estado "llamado" al cargar la página');
                    }

                    // Actualizar la lista local y renderizar
                    turnos = [...turnosExistentes];
                    renderTurnos(turnos);

                    actualizarIndicadorSync('sincronizado');
                    console.log('✅ Sincronización inicial completada - solo los turnos nuevos sonarán a partir de ahora');
                })
                .catch(error => {
                    console.error('Error en sincronización inicial:', error);
                    actualizarIndicadorSync('error');
                    // Fallback: hacer sincronización normal
                    updateQueue();
                });
        }

        // Escuchar eventos de Pusher para turnos en tiempo real
        function setupRealTimeListeners() {
            // Sincronización inicial
            sincronizacionInicial();

            // Aumentamos la frecuencia de polling para actualizaciones en tiempo real
            setInterval(updateQueue, 1000); // Actualizar cada 1 segundo para mejor tiempo real
        }

        // Función auxiliar para comparar arrays de multimedia
        function arraysEqual(arr1, arr2) {
            if (arr1.length !== arr2.length) return false;

            for (let i = 0; i < arr1.length; i++) {
                if (arr1[i].id !== arr2[i].id ||
                    arr1[i].activo !== arr2[i].activo ||
                    arr1[i].orden !== arr2[i].orden) {
                    return false;
                }
            }
            return true;
        }

        // Mostrar placeholder cuando no hay multimedia con transición
        function showPlaceholder() {
            const container = document.getElementById('multimedia-content');

            // Aplicar transición de salida al contenido actual si existe
            const currentContent = container.children[0];
            if (currentContent && !currentContent.id.includes('placeholder')) {
                currentContent.classList.add('media-transition', 'media-fade-out');

                setTimeout(() => {
                    loadPlaceholder(container);
                }, 400);
            } else {
                loadPlaceholder(container);
            }

            isMediaPlaying = false;
            if (mediaTimer) {
                clearTimeout(mediaTimer);
                mediaTimer = null;
            }
        }

        // Cargar placeholder con transición
        function loadPlaceholder(container) {
            // Limpiar contenido actual
            container.innerHTML = '';

            // Crear placeholder con transición
            const placeholderDiv = document.createElement('div');
            placeholderDiv.className = 'media-transition media-loading';
            placeholderDiv.innerHTML = `
                <div id="multimedia-placeholder" class="text-center text-gray-400 z-10">
                    <div class="text-8xl mb-4 opacity-50">🏥</div>
                    <p class="text-2xl font-semibold text-hospital-blue mb-2">Contenido Multimedia</p>
                    <p class="text-lg text-gray-500">Videos e imágenes del hospital</p>
                </div>
                <div class="absolute inset-0 opacity-5">
                    <div class="w-full h-full" style="background-image: repeating-linear-gradient(45deg, #064b9e 0px, #064b9e 10px, transparent 10px, transparent 20px);"></div>
                </div>
            `;

            container.appendChild(placeholderDiv);

            // Aplicar transición de entrada
            setTimeout(() => {
                placeholderDiv.classList.remove('media-loading');
                placeholderDiv.classList.add('media-fade-in', 'media-enter');
            }, 50);
        }

        // Iniciar reproducción de multimedia
        function startMediaPlayback() {
            if (multimediaList.length === 0) {
                showPlaceholder();
                return;
            }

            isMediaPlaying = true;
            currentMediaIndex = 0;
            showCurrentMedia();
        }

        // Mostrar el archivo multimedia actual con transiciones
        function showCurrentMedia() {
            if (multimediaList.length === 0) {
                showPlaceholder();
                return;
            }

            const media = multimediaList[currentMediaIndex];
            const container = document.getElementById('multimedia-content');

            // Aplicar transición de salida al contenido actual
            const currentContent = container.children[0];
            if (currentContent && !currentContent.id.includes('placeholder')) {
                currentContent.classList.add('media-transition', 'media-fade-out');

                // Esperar a que termine la transición de salida antes de mostrar el nuevo contenido
                setTimeout(() => {
                    loadNewMedia(media, container);
                }, 400); // Mitad de la duración de la transición
            } else {
                // No hay contenido previo, mostrar inmediatamente
                loadNewMedia(media, container);
            }
        }

        // Cargar nuevo archivo multimedia
        function loadNewMedia(media, container) {
            // Limpiar contenido anterior
            container.innerHTML = '';

            if (media.tipo === 'imagen') {
                // Mostrar imagen
                const img = document.createElement('img');
                img.src = media.url;
                img.className = 'max-w-full max-h-full object-contain media-transition media-loading';
                img.alt = media.nombre;

                img.onload = () => {
                    container.appendChild(img);

                    // Aplicar transición de entrada
                    setTimeout(() => {
                        img.classList.remove('media-loading');
                        img.classList.add('media-fade-in', 'media-enter');
                    }, 50);

                    // Programar siguiente media después de la duración especificada
                    mediaTimer = setTimeout(() => {
                        nextMedia();
                    }, media.duracion * 1000);
                };

                img.onerror = () => {
                    console.error('Error al cargar imagen:', media.url);
                    nextMedia();
                };

            } else if (media.tipo === 'video') {
                // Mostrar video
                const video = document.createElement('video');
                video.src = media.url;
                video.className = 'max-w-full max-h-full object-contain media-transition media-loading';
                video.autoplay = true;
                video.muted = true;
                video.loop = false;

                video.onloadeddata = () => {
                    container.appendChild(video);

                    // Aplicar transición de entrada
                    setTimeout(() => {
                        video.classList.remove('media-loading');
                        video.classList.add('media-fade-in', 'media-enter');
                    }, 50);
                };

                video.onended = () => {
                    nextMedia();
                };

                video.onerror = () => {
                    console.error('Error al cargar video:', media.url);
                    nextMedia();
                };
            }
        }

        // Avanzar al siguiente archivo multimedia con transición
        function nextMedia() {
            if (mediaTimer) {
                clearTimeout(mediaTimer);
                mediaTimer = null;
            }

            // Verificar si hay más multimedia disponible
            if (multimediaList.length === 0) {
                showPlaceholder();
                return;
            }

            currentMediaIndex = (currentMediaIndex + 1) % multimediaList.length;
            showCurrentMedia();
        }

        // Funcionalidad del ticker
        function initializeTicker() {
            const tickerContent = document.querySelector('.ticker-content');
            const tickerContainer = document.querySelector('.ticker-container');

            if (tickerContainer && tickerContent) {
                // Pausar animación al hacer hover (útil para debugging)
                tickerContainer.addEventListener('mouseenter', function() {
                    tickerContent.style.animationPlayState = 'paused';
                });

                tickerContainer.addEventListener('mouseleave', function() {
                    tickerContent.style.animationPlayState = 'running';
                });
            }
        }

        // Función específica para reiniciar el ticker
        function restartTicker(speed) {
            const tickerContent = document.querySelector('.ticker-content');

            if (tickerContent) {
                // Detener completamente la animación
                tickerContent.style.animation = 'none';

                // Forzar reflow para asegurar que el navegador procese el cambio
                void tickerContent.offsetWidth;

                // Usar requestAnimationFrame para asegurar que la animación se aplique correctamente
                requestAnimationFrame(() => {
                    tickerContent.style.animation = `ticker-scroll ${speed}s linear infinite`;
                });
            }
        }

        // Detectar cuando la ventana vuelve a estar activa para re-sincronizar
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden && sincronizacionActiva) {
                // Solo hacer sincronización suave si no hay audio reproduciéndose
                if (!reproduciendoAudio) {
                    console.log('👁️ Página visible - sincronización suave');
                    updateQueue(); // Solo actualizar datos, no limpiar cola
                } else {
                    console.log('👁️ Página visible - audio en curso, omitiendo sincronización');
                }
            } else if (document.hidden) {
                console.log('📱 Página oculta - manteniendo activa para audio');
            }
        });

        // Detectar cuando la ventana obtiene el foco para re-sincronizar
        window.addEventListener('focus', function() {
            if (sincronizacionActiva && !reproduciendoAudio) {
                console.log('🎯 Página enfocada - sincronización suave');
                setTimeout(() => {
                    updateQueue(); // Solo actualizar datos, no limpiar cola
                }, 500);
            } else if (reproduciendoAudio) {
                console.log('🎯 Página enfocada - audio en curso, omitiendo sincronización');
            }
        });

        // Variables para mantener la página activa
        let keepAliveInterval;
        let audioContext;
        let wakeLockSentinel = null;

        // Función para mantener la página activa en segundo plano
        function mantenerPaginaActiva() {
            // 1. Crear AudioContext para mantener el audio activo
            try {
                if (!audioContext) {
                    audioContext = new (window.AudioContext || window.webkitAudioContext)();
                }

                // Crear un oscilador silencioso que mantenga el contexto activo
                if (audioContext.state === 'suspended') {
                    audioContext.resume();
                }
            } catch (e) {
                if (!window.audioContextWarningShown) {
                    console.warn('No se pudo crear AudioContext:', e);
                    window.audioContextWarningShown = true;
                }
            }

            // 2. Usar Page Visibility API para detectar cuando la página se oculta
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    console.log('📱 Página oculta - manteniendo activa para audio');
                    // Forzar que el audio siga funcionando
                    if (audioContext && audioContext.state === 'suspended') {
                        audioContext.resume();
                    }
                } else {
                    console.log('📱 Página visible nuevamente');
                }
            });

            // 3. Usar Wake Lock API para mantener la pantalla activa (si está disponible)
            if ('wakeLock' in navigator) {
                navigator.wakeLock.request('screen').then(function(sentinel) {
                    wakeLockSentinel = sentinel;
                    console.log('🔒 Wake Lock activado - pantalla se mantendrá activa');
                }).catch(function(err) {
                    console.warn('No se pudo activar Wake Lock:', err);
                });
            }

            // 4. Heartbeat para mantener la conexión activa
            keepAliveInterval = setInterval(function() {
                // Enviar una pequeña petición para mantener la conexión activa
                fetch('/api/tv-config', {
                    method: 'GET',
                    cache: 'no-cache'
                }).catch(() => {
                    // Ignorar errores, es solo para mantener activa la conexión
                });

                // Asegurar que el AudioContext siga activo
                if (audioContext && audioContext.state === 'suspended') {
                    audioContext.resume();
                }
            }, 30000); // Cada 30 segundos
        }

        // Función para reproducir secuencia de audio con repeticiones automáticas
        function playAudioSequenceWithRepeat(audioSequence, repeticiones = 2, onComplete = null) {
            let repeticionActual = 0;
            let timeoutId = null;
            const turnoId = audioSequence.length > 0 ? audioSequence[0].split('/').pop() : 'desconocido';

            console.log(`🔊 Iniciando playAudioSequenceWithRepeat para turno ${turnoId} - ${repeticiones} repeticiones`);

            function reproducirConRepeticion() {
                repeticionActual++;
                console.log(`🔊 Reproduciendo secuencia ${turnoId} - Repetición ${repeticionActual} de ${repeticiones}`);

                // Timeout de seguridad para evitar bloqueos
                timeoutId = setTimeout(() => {
                    console.warn(`⚠️ Timeout en reproducción de audio ${turnoId}, forzando finalización`);
                    if (onComplete) {
                        console.log(`🔊 Ejecutando callback por timeout para ${turnoId}`);
                        onComplete();
                    }
                }, 30000); // 30 segundos máximo por secuencia

                playAudioSequence(audioSequence, 0, function() {
                    // Limpiar timeout de seguridad
                    if (timeoutId) {
                        clearTimeout(timeoutId);
                        timeoutId = null;
                    }

                    console.log(`✅ Repetición ${repeticionActual} completada para ${turnoId}`);

                    if (repeticionActual < repeticiones) {
                        // Pausa de 1 segundo entre repeticiones
                        console.log(`⏰ Pausa de 1 segundo antes de repetición ${repeticionActual + 1} para ${turnoId}`);
                        setTimeout(() => {
                            reproducirConRepeticion();
                        }, 1000);
                    } else {
                        // Todas las repeticiones completadas
                        console.log(`🎉 Todas las repeticiones completadas para ${turnoId}`);
                        if (onComplete) {
                            console.log(`🔊 Ejecutando callback final para ${turnoId}`);
                            onComplete();
                        }
                    }
                });
            }

            // Iniciar reproducción con manejo de errores
            try {
                reproducirConRepeticion();
            } catch (error) {
                console.error(`❌ Error en playAudioSequenceWithRepeat para ${turnoId}:`, error);
                if (timeoutId) {
                    clearTimeout(timeoutId);
                }
                if (onComplete) {
                    console.log(`🔊 Ejecutando callback por error para ${turnoId}`);
                    onComplete();
                }
            }
        }

        // Variable global para almacenar el último turno llamado (para repetir)
        let ultimoTurnoLlamado = null;

        // Función para repetir manualmente el último turno llamado
        function repetirUltimoTurno() {
            if (ultimoTurnoLlamado) {
                console.log('🔊 Repitiendo manualmente el turno:', ultimoTurnoLlamado.codigo_completo);

                // Usar el sistema de cola para evitar reproducciones simultáneas
                // Crear una copia del turno para la repetición
                const turnoParaRepetir = {
                    ...ultimoTurnoLlamado,
                    id: 'repetir_' + ultimoTurnoLlamado.id + '_' + Date.now() // ID único para evitar duplicados
                };

                agregarAColaAudio(turnoParaRepetir);
            } else {
                console.warn('⚠️ No hay turno para repetir');
            }
        }

        // Hacer la función disponible globalmente para el dashboard del asesor
        window.repetirUltimoTurno = repetirUltimoTurno;

        // Función mejorada para reproducir audio que funciona en segundo plano
        function playAudioSequence(audioFiles, index = 0, onComplete = null) {
            if (index >= audioFiles.length) {
                console.log('🎵 Secuencia de audio completada');
                if (onComplete) onComplete();
                return;
            }

            const audioFile = audioFiles[index];
            console.log(`🎵 Reproduciendo archivo ${index + 1}/${audioFiles.length}:`, audioFile.split('/').pop());

            const audio = new Audio(audioFile);

            // Configurar el audio para que funcione en segundo plano
            audio.preload = 'auto';

            // Determinar el volumen según el tipo de archivo
            let targetVolume = 1.0;
            let gainValue = 1.0;

            // El pito inicial mantiene su volumen original
            if (audioFile.includes('turno.mp3') && !audioFile.includes('voice/')) {
                targetVolume = 1.0;  // Volumen normal para el pito
                gainValue = 1.0;
            } else {
                // Aumentar volumen para archivos de voz
                targetVolume = 1.0;  // Volumen máximo del navegador
                gainValue = 3.0;     // Amplificación adicional con Web Audio API
            }

            audio.volume = targetVolume;

            // Log para debugging del volumen
            console.log(`🔊 Reproduciendo: ${audioFile.split('/').pop()} - Volumen: ${targetVolume}, Ganancia: ${gainValue}x`);

            // Usar Web Audio API para amplificar el volumen de los archivos de voz
            let audioSource = null;
            let gainNode = null;

            try {
                if (audioContext && gainValue > 1.0) {
                    audioSource = audioContext.createMediaElementSource(audio);
                    gainNode = audioContext.createGain();
                    gainNode.gain.value = gainValue;
                    audioSource.connect(gainNode);
                    gainNode.connect(audioContext.destination);
                }
            } catch (e) {
                // Si Web Audio API falla, usar volumen estándar
                console.warn('Web Audio API no disponible para amplificación:', e);
            }

            // Asegurar que el AudioContext esté activo antes de reproducir
            if (audioContext && audioContext.state === 'suspended') {
                audioContext.resume().then(() => {
                    reproducirAudio();
                });
            } else {
                reproducirAudio();
            }

            function reproducirAudio() {
                let audioCompleted = false;

                // Timeout de seguridad para archivos individuales (10 segundos máximo)
                const timeoutId = setTimeout(() => {
                    if (!audioCompleted) {
                        console.warn('⚠️ Timeout en archivo de audio:', audioFile.split('/').pop());
                        audioCompleted = true;

                        // Limpiar conexiones
                        if (audioSource && gainNode) {
                            try {
                                audioSource.disconnect();
                                gainNode.disconnect();
                            } catch (e) {
                                // Ignorar errores de desconexión
                            }
                        }

                        // Continuar con el siguiente archivo
                        setTimeout(() => {
                            playAudioSequence(audioFiles, index + 1, onComplete);
                        }, 200);
                    }
                }, 10000);

                audio.onended = function() {
                    if (!audioCompleted) {
                        audioCompleted = true;
                        clearTimeout(timeoutId);

                        // Limpiar conexiones de Web Audio API
                        if (audioSource && gainNode) {
                            try {
                                audioSource.disconnect();
                                gainNode.disconnect();
                            } catch (e) {
                                // Ignorar errores de desconexión
                            }
                        }

                        // Pequeña pausa entre archivos para que suene más natural
                        setTimeout(() => {
                            playAudioSequence(audioFiles, index + 1, onComplete);
                        }, 200);
                    }
                };

                audio.onerror = function() {
                    if (!audioCompleted) {
                        audioCompleted = true;
                        clearTimeout(timeoutId);

                        console.error('❌ Error al reproducir audio:', audioFiles[index]);
                        // Limpiar conexiones en caso de error
                        if (audioSource && gainNode) {
                            try {
                                audioSource.disconnect();
                                gainNode.disconnect();
                            } catch (e) {
                                // Ignorar errores de desconexión
                            }
                        }

                        // Continuar con el siguiente archivo aunque haya error
                        setTimeout(() => {
                            playAudioSequence(audioFiles, index + 1, onComplete);
                        }, 200);
                    }
                };

                // Reproducir con manejo de errores
                const playPromise = audio.play();
                if (playPromise !== undefined) {
                    playPromise.catch(error => {
                        if (!audioCompleted) {
                            audioCompleted = true;
                            clearTimeout(timeoutId);

                            console.error('❌ Error al iniciar reproducción:', error);
                            // Limpiar conexiones en caso de error
                            if (audioSource && gainNode) {
                                try {
                                    audioSource.disconnect();
                                    gainNode.disconnect();
                                } catch (e) {
                                    // Ignorar errores de desconexión
                                }
                            }

                            // Intentar continuar con el siguiente archivo
                            setTimeout(() => {
                                playAudioSequence(audioFiles, index + 1, onComplete);
                            }, 200);
                        }
                    });
                }
            }
        }

        // Función para habilitar audio con interacción del usuario
        function habilitarAudioConInteraccion() {
            // Crear un overlay invisible que capture el primer clic/toque
            const overlay = document.createElement('div');
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.8);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                color: white;
                font-size: 24px;
                text-align: center;
                cursor: pointer;
            `;
            overlay.innerHTML = `
                <div>
                    <div style="font-size: 48px; margin-bottom: 20px;">🔊</div>
                    <div>Toque la pantalla para habilitar el audio</div>
                    <div style="font-size: 16px; margin-top: 10px; opacity: 0.7;">
                        (Requerido por el navegador para reproducir sonidos)
                    </div>
                </div>
            `;

            // Función para habilitar audio
            function enableAudio() {
                try {
                    // Crear y activar AudioContext
                    if (!audioContext) {
                        audioContext = new (window.AudioContext || window.webkitAudioContext)();
                    }

                    if (audioContext.state === 'suspended') {
                        audioContext.resume();
                    }

                    // Reproducir un sonido silencioso para "despertar" el audio
                    const oscillator = audioContext.createOscillator();
                    const gainNode = audioContext.createGain();
                    gainNode.gain.value = 0; // Volumen 0 (silencioso)
                    oscillator.connect(gainNode);
                    gainNode.connect(audioContext.destination);
                    oscillator.start();
                    oscillator.stop(audioContext.currentTime + 0.1);

                    // Remover overlay
                    document.body.removeChild(overlay);

                    console.log('✅ Audio habilitado correctamente');
                } catch (e) {
                    console.error('Error al habilitar audio:', e);
                    // Remover overlay aunque haya error
                    document.body.removeChild(overlay);
                }
            }

            // Agregar event listeners
            overlay.addEventListener('click', enableAudio);
            overlay.addEventListener('touchstart', enableAudio);

            // Agregar overlay al DOM
            document.body.appendChild(overlay);

            // Auto-remover después de 10 segundos si no hay interacción
            setTimeout(() => {
                if (document.body.contains(overlay)) {
                    enableAudio();
                }
            }, 10000);
        }

        // Función para detectar si necesitamos interacción del usuario
        function verificarNecesidadInteraccion() {
            // En navegadores modernos, el audio requiere interacción del usuario
            // Mostrar overlay solo si es necesario
            try {
                const testAudio = new Audio();
                const playPromise = testAudio.play();

                if (playPromise !== undefined) {
                    playPromise.catch(() => {
                        // El audio requiere interacción del usuario
                        habilitarAudioConInteraccion();
                    });
                }
            } catch (e) {
                // Asumir que necesitamos interacción
                habilitarAudioConInteraccion();
            }
        }

        // Listener para comunicación entre pestañas (repetir audio)
        function configurarComunicacionEntrePestanas() {
            // Escuchar cambios en localStorage para repetir audio
            window.addEventListener('storage', function(e) {
                if (e.key === 'repetir-audio-turno' && e.newValue) {
                    console.log('📨 Solicitud de repetición recibida desde dashboard');
                    repetirUltimoTurno();

                    // Limpiar el localStorage después de procesar
                    setTimeout(() => {
                        localStorage.removeItem('repetir-audio-turno');
                    }, 1000);
                }
            });
        }

        // Inicializar cuando la página carga
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar sistema responsive PRIMERO
            initializeResponsiveSystem();

            // Verificar si necesitamos interacción del usuario para el audio
            verificarNecesidadInteraccion();

            // Activar funciones para mantener la página activa
            mantenerPaginaActiva();

            // Configurar comunicación entre pestañas
            configurarComunicacionEntrePestanas();

            // Actualizar la hora inmediatamente y cada minuto
            updateTime();
            setInterval(updateTime, 60000);

            initializeTicker();

            // Inicializar funcionalidad en tiempo real
            setupRealTimeListeners();

            // Cargar datos iniciales inmediatamente
            updateTvConfig();
            loadMultimedia();

            // Establecer intervalos para actualizaciones periódicas adicionales
            setInterval(updateTvConfig, 5000);
            setInterval(loadMultimedia, 5000);
            // La actualización de turnos ahora se maneja en setupRealTimeListeners con intervalo más frecuente
        });

        // Limpiar recursos cuando la página se cierre
        window.addEventListener('beforeunload', function() {
            // Limpiar intervalos
            if (keepAliveInterval) {
                clearInterval(keepAliveInterval);
            }

            // Liberar Wake Lock
            if (wakeLockSentinel) {
                wakeLockSentinel.release();
            }

            // Cerrar AudioContext
            if (audioContext) {
                audioContext.close();
            }
        });

        // Prevenir interacciones no deseadas en el TV
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
        });

        document.addEventListener('keydown', function(e) {
            // Permitir solo F11 para fullscreen
            if (e.key !== 'F11') {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
