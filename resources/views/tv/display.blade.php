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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

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

        /* Indicador de reconexión */
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
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
            /* PERF: sombra estática. La animación ticker-glow repintaba el texto en cada
               frame (60fps) las 24h porque text-shadow no la acelera el compositor. */
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
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

        /* Tablets en portrait y resoluciones similares (768x1024, 800x1280, etc.) */
        @media (min-width: 769px) and (max-width: 900px) {
            :root {
                --scale-factor: 0.75;
                --header-height: 6.8rem;
                --ticker-height: 3.2rem;
            }

            .turno-numero {
                font-size: clamp(1.3rem, 3.5vw, 2.8rem) !important;
                line-height: 1.05 !important;
            }

            .turno-caja {
                font-size: clamp(1.3rem, 3.5vw, 2.8rem) !important;
                line-height: 1.05 !important;
            }

            .responsive-queue-section > div > div {
                height: calc(19% - 0.2rem) !important;
                min-height: 50px !important;
            }

            .responsive-queue-section #patient-queue {
                gap: 0.15rem !important;
            }

            .responsive-queue-section .p-3 {
                padding: 0.5rem !important;
            }
        }

        /* Tablets en portrait con mucha altura (768x1024, 800x1280, etc.) */
        @media (min-width: 769px) and (max-width: 900px) and (min-height: 1000px) {
            :root {
                --scale-factor: 0.78;
                --header-height: 7.2rem;
                --ticker-height: 3.4rem;
            }

            .turno-numero {
                font-size: clamp(1.4rem, 3.8vw, 3rem) !important;
                line-height: 1.08 !important;
            }

            .turno-caja {
                font-size: clamp(1.4rem, 3.8vw, 3rem) !important;
                line-height: 1.08 !important;
            }

            .responsive-queue-section > div > div {
                height: calc(18.5% - 0.25rem) !important;
                min-height: 55px !important;
            }

            .responsive-queue-section #patient-queue {
                gap: 0.2rem !important;
            }

            .responsive-queue-section .p-3 {
                padding: 0.6rem !important;
            }
        }

        /* Resoluciones muy pequeñas pero no móviles (netbooks, tablets pequeñas en landscape) */
        @media (min-width: 769px) and (max-height: 650px) {
            :root {
                --scale-factor: 0.7;
                --header-height: 6rem;
                --ticker-height: 2.8rem;
            }

            .turno-numero {
                font-size: clamp(1.1rem, 3.2vw, 2.4rem) !important;
                line-height: 1.02 !important;
            }

            .turno-caja {
                font-size: clamp(1.1rem, 3.2vw, 2.4rem) !important;
                line-height: 1.02 !important;
            }

            .responsive-queue-section > div > div {
                height: calc(19.8% - 0.15rem) !important;
                min-height: 42px !important;
            }

            .responsive-queue-section #patient-queue {
                gap: 0.1rem !important;
            }

            .responsive-queue-section .p-3 {
                padding: 0.4rem !important;
            }

            .responsive-queue-section .p-4 {
                padding: 0.6rem !important;
            }
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

            .responsive-header > div:first-child {
                overflow: visible !important;
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
                font-size: clamp(1.5rem, 4vw, 3rem) !important;
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

        /* Pantallas medianas (tablets grandes, laptops pequeños) */
        @media (min-width: 901px) and (max-width: 1024px) {
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
                font-size: clamp(1.75rem, 3.5vw, 3.5rem) !important;
            }
        }

        /* Media query de seguridad para resoluciones intermedias no cubiertas */
        @media (min-width: 769px) and (max-width: 1024px) and (max-height: 800px) {
            :root {
                --scale-factor: 0.76;
                --header-height: 6.5rem;
                --ticker-height: 3rem;
            }

            .turno-numero {
                font-size: clamp(1.2rem, 3.3vw, 2.6rem) !important;
                line-height: 1.04 !important;
            }

            .turno-caja {
                font-size: clamp(1.2rem, 3.3vw, 2.6rem) !important;
                line-height: 1.04 !important;
            }

            .responsive-queue-section > div > div {
                height: calc(19.3% - 0.18rem) !important;
                min-height: 47px !important;
            }

            .responsive-queue-section #patient-queue {
                gap: 0.12rem !important;
            }

            .responsive-queue-section .p-3 {
                padding: 0.45rem !important;
            }
        }

        /* Pantallas intermedias (1025px - 1440px) - Resoluciones como 1440x900 */
        @media (min-width: 1025px) and (max-width: 1440px) {
            :root {
                --scale-factor: 0.9;
                --header-height: 7.5rem;
                --ticker-height: 3.5rem;
            }

            /* Ajustes específicos para turnos en resoluciones intermedias */
            .turno-numero {
                font-size: clamp(1.5rem, 3.8vw, 3.2rem) !important;
            }

            .turno-caja {
                font-size: clamp(1.5rem, 3.8vw, 3.2rem) !important;
            }

            /* Reducir padding para aprovechar mejor el espacio */
            .responsive-queue-section .p-3 {
                padding: 0.6rem !important;
            }

            /* Ajustar altura de turnos para pantallas intermedias */
            .responsive-queue-section > div > div {
                height: calc(18% - 0.3rem) !important;
                min-height: 55px !important;
            }
        }

        /* Resoluciones específicas problemáticas (1366x768, 1280x720, etc.) */
        @media (max-width: 1366px) and (max-height: 900px) {
            :root {
                --scale-factor: 0.85;
                --header-height: 7rem;
                --ticker-height: 3.2rem;
            }

            /* Ajustes más agresivos para turnos */
            .turno-numero {
                font-size: clamp(1.3rem, 3.5vw, 2.8rem) !important;
                line-height: 1.1 !important;
            }

            .turno-caja {
                font-size: clamp(1.3rem, 3.5vw, 2.8rem) !important;
                line-height: 1.1 !important;
            }

            /* Reducir espaciado entre turnos */
            .responsive-queue-section #patient-queue {
                gap: 0.2rem !important;
            }

            /* Ajustar altura de turnos para pantallas pequeñas */
            .responsive-queue-section > div > div {
                height: calc(19% - 0.2rem) !important;
                min-height: 50px !important;
            }

            /* Reducir padding general */
            .responsive-queue-section .p-3 {
                padding: 0.5rem !important;
            }

            .responsive-queue-section .p-4 {
                padding: 0.75rem !important;
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
                font-size: clamp(1rem, 2.5vw, 2rem) !important;
            }

            /* Reducir espaciado al mínimo */
            .responsive-queue-section #patient-queue {
                gap: 0.125rem !important;
            }
        }

        /* Clases responsive dinámicas */
        .responsive-header {
            height: var(--header-height);
            overflow: visible !important;
        }

        .responsive-header > div:first-child {
            overflow: visible !important;
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
            min-height: 80px;
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

        /* LAYOUT FLEXBOX - DOS COLUMNAS */
        .turno-container {
            display: flex !important;
            width: 100% !important;
            align-items: center !important;
            justify-content: space-between !important;
        }

        /* Columna izquierda - Código del turno (toma el espacio disponible) */
        .turno-numero {
            font-size: clamp(2.5rem, 5vw, 5.5rem);
            flex: 1 1 auto !important;
            min-width: 0 !important;
            text-align: left !important;
            padding-left: 0.75rem !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
            line-height: 1;
            max-height: 100%;
        }

        /* Columna derecha - CAJA (siempre muestra completo, nunca se corta) */
        .turno-caja {
            font-size: clamp(2.5rem, 5vw, 5.5rem);
            flex: 0 0 auto !important;
            white-space: nowrap !important;
            text-align: right !important;
            padding-right: 1rem !important;
            padding-left: 0.5rem !important;
            overflow: visible !important;
            line-height: 1;
            max-height: 100%;
        }

        /* Ajustes específicos para diferentes alturas de pantalla */
        @media (max-height: 900px) {
            .turno-numero {
                font-size: clamp(1.4rem, 3.2vw, 2.8rem) !important;
                line-height: 1.1 !important;
            }

            .turno-caja {
                font-size: clamp(1.4rem, 3.2vw, 2.8rem) !important;
                line-height: 1.1 !important;
            }

            .responsive-queue-section .p-4 {
                padding: 0.6rem !important;
            }

            /* Ajustar altura de cada turno */
            .responsive-queue-section > div > div {
                height: calc(19% - 0.25rem) !important;
                min-height: 48px !important;
            }
        }

        @media (max-height: 800px) {
            .turno-numero {
                font-size: clamp(1.3rem, 3vw, 2.5rem) !important;
                line-height: 1.05 !important;
            }

            .turno-caja {
                font-size: clamp(1.3rem, 3vw, 2.5rem) !important;
                line-height: 1.05 !important;
            }

            .responsive-queue-section .p-4 {
                padding: 0.5rem !important;
            }

            /* Reducir gap entre turnos */
            .responsive-queue-section #patient-queue {
                gap: 0.15rem !important;
            }

            /* Ajustar altura de cada turno */
            .responsive-queue-section > div > div {
                height: calc(19.5% - 0.2rem) !important;
                min-height: 45px !important;
            }
        }

        /* Media query específico para 1440x900 y resoluciones similares */
        @media (min-width: 1400px) and (max-width: 1500px) and (max-height: 950px) {
            :root {
                --scale-factor: 0.88;
                --header-height: 7.2rem;
                --ticker-height: 3.3rem;
            }

            .turno-numero {
                font-size: clamp(1.4rem, 3.3vw, 2.9rem) !important;
                line-height: 1.08 !important;
            }

            .turno-caja {
                font-size: clamp(1.4rem, 3.3vw, 2.9rem) !important;
                line-height: 1.08 !important;
            }

            /* Optimizar espacio vertical */
            .responsive-queue-section > div > div {
                height: calc(18.8% - 0.22rem) !important;
                min-height: 52px !important;
            }

            .responsive-queue-section #patient-queue {
                gap: 0.18rem !important;
            }

            .responsive-queue-section .p-3 {
                padding: 0.55rem !important;
            }
        }

        @media (max-height: 600px) {
            .turno-numero {
                font-size: clamp(1.25rem, 3vw, 2.5rem) !important;
            }

            .turno-caja {
                font-size: clamp(1.25rem, 3vw, 2.5rem) !important;
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
                font-size: clamp(1rem, 2.5vw, 2rem) !important;
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
            overflow: visible;
            width: 100%;
            box-sizing: border-box;
        }

        /* Contenedores de texto dentro de cada turno - respetar layout flexbox */
        .responsive-queue-section .turno-numero {
            flex: 1 1 auto !important;
            min-width: 0 !important;
            box-sizing: border-box;
        }

        .responsive-queue-section .turno-caja {
            flex: 0 0 auto !important;
            box-sizing: border-box;
        }

        /* Asegurar que los grids dentro de turnos no se desborden */
        .responsive-queue-section .grid {
            width: 100%;
            max-width: 100%;
            overflow: hidden;
        }

        /* Media queries específicos para resoluciones problemáticas comunes */

        /* 1366x768 - Resolución muy común en laptops */
        @media (min-width: 1300px) and (max-width: 1400px) and (max-height: 800px) {
            :root {
                --scale-factor: 0.82;
                --header-height: 6.8rem;
                --ticker-height: 3rem;
            }

            .turno-numero {
                font-size: clamp(1.2rem, 3.2vw, 2.6rem) !important;
                line-height: 1.05 !important;
            }

            .turno-caja {
                font-size: clamp(1.2rem, 3.2vw, 2.6rem) !important;
                line-height: 1.05 !important;
            }

            .responsive-queue-section > div > div {
                height: calc(19.2% - 0.18rem) !important;
                min-height: 48px !important;
            }

            .responsive-queue-section #patient-queue {
                gap: 0.15rem !important;
            }
        }

        /* 1280x720 - Otra resolución común */
        @media (min-width: 1200px) and (max-width: 1320px) and (max-height: 750px) {
            :root {
                --scale-factor: 0.78;
                --header-height: 6.5rem;
                --ticker-height: 2.8rem;
            }

            .turno-numero {
                font-size: clamp(1.1rem, 3vw, 2.4rem) !important;
                line-height: 1.02 !important;
            }

            .turno-caja {
                font-size: clamp(1.1rem, 3vw, 2.4rem) !important;
                line-height: 1.02 !important;
            }

            .responsive-queue-section > div > div {
                height: calc(19.5% - 0.15rem) !important;
                min-height: 45px !important;
            }

            .responsive-queue-section #patient-queue {
                gap: 0.12rem !important;
            }

            .responsive-queue-section .p-3 {
                padding: 0.4rem !important;
            }
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

        /* ===================================================================
           REDISEÑO 2026 — override aditivo (NO borra el sistema responsive).
           1) Layout a prueba de resolución: grid de 3 filas que impide que
              cualquier card invada el header (bug del TV).
           2) Cola: distribución uniforme por flex, nunca desborda.
           3) Jerarquía de estado: "en atención" (sin badge) = azul vivo;
              "atendido" (ya terminó) = atenuado en segundo plano.
           Selectores con id-specificity + !important para ganar al sistema viejo.
           =================================================================== */
        .tv-root {
            height: 100% !important;
            display: grid !important;
            grid-template-rows: auto minmax(0, 1fr) auto !important;
            overflow: hidden !important;
        }
        .tv-root > .responsive-header { height: auto !important; }
        .tv-root > .responsive-main {
            height: auto !important;
            min-height: 0 !important;
            overflow: hidden !important;
        }
        .tv-root #patient-queue {
            height: 100% !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 0.7rem !important;
            overflow: hidden !important;
        }
        .tv-root #patient-queue > div {
            flex: 1 1 0 !important;
            min-height: 0 !important;
            height: auto !important;
            max-height: none !important;
            margin: 0 !important;
        }
        /* Turno atendido (ya terminó) = atenuado, pasa a segundo plano */
        .tv-root #patient-queue > div.is-atendido {
            background: #3f6a9e !important;
        }
        .tv-root #patient-queue > div.is-atendido .turno-numero,
        .tv-root #patient-queue > div.is-atendido .turno-caja {
            color: rgba(255, 255, 255, 0.6) !important;
        }
        /* Estado ATENDIDO: tag pequeño ENCIMA de la CAJA. El badge es position:absolute,
           NO aporta ancho a la celda → la celda mide exactamente lo que "CAJA", y el
           número del turno conserva TODO su espacio (igual que un turno normal) y nunca
           se recorta. El margin-top de la CAJA deja hueco para el badge (sin overlap). */
        .caja-cell {
            flex: 0 0 auto;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: center;
        }
        .caja-cell .turno-caja {
            margin-top: 1.6rem !important;
        }
        .badge-atendido {
            position: absolute;
            top: 0;
            right: 1rem;
            background: #16a34a;
            color: #ffffff;
            font-weight: 700;
            padding: 0.12rem 0.55rem;
            border-radius: 0.35rem;
            font-size: clamp(0.65rem, 0.9vw, 0.95rem);
            letter-spacing: 0.05em;
            line-height: 1;
            white-space: nowrap;
        }
        /* Placeholder multimedia con marca (reemplaza el emoji 🏥) */
        .mm-brand-tile {
            width: clamp(3.5rem, 7vw, 7rem);
            height: clamp(3.5rem, 7vw, 7rem);
            background: #064b9e;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: #ffffff;
            font-weight: 700;
            font-size: clamp(1.2rem, 2.5vw, 2.5rem);
            letter-spacing: 0.04em;
        }

        /* ===================================================================
           REDISEÑO TV 2026-09 — mismo esqueleto y mismas medidas (cabecera, 4/6 contenido,
           2/6 turnos, --ticker-height); cambia solo lo de adentro. Los tamaños de la columna
           de turnos salen de su propio alto y ancho (unidades cqw/cqh), así se adaptan a
           cualquier resolución igual que hoy.
           =================================================================== */
        :root {
            --tv-azul: #064b9e;
            --tv-azul-hondo: #053d82;
            --tv-marino: #072449;
            --tv-linea: #d6e0ed;
            --tv-mudo: #5b6f8e;
            --tv-tenue: #8193b0;
        }

        /* Cabecera: logo y unidad a la izquierda, hora grande a la derecha */
        .tv-cabecera { background: #fff; box-shadow: inset 0 -1px 0 var(--tv-linea); } /* sombra, no borde: no suma altura */
        .tv-marca { display: flex; align-items: center; min-width: 0; }
        .responsive-header img.tv-logo { flex: none; height: min(calc(var(--header-height) * .7), 121px) !important; max-height: none !important; width: auto; max-width: none; filter: contrast(1.15); }
        .tv-separador { flex: none; width: 1px; height: calc(var(--header-height) * .46); margin: 0 clamp(.9rem, 1.7vw, 2.2rem); background: var(--tv-linea); }
        .tv-unidad { display: flex; flex-direction: column; justify-content: center; min-width: 0; }
        .tv-unidad h1 { color: var(--tv-azul); letter-spacing: -.01em; white-space: nowrap; }
        .tv-unidad-nombre { font-size: .95rem; line-height: 1.1; margin-bottom: .35rem; color: var(--tv-mudo); white-space: nowrap; }
        .tv-fecha { color: var(--tv-marino); white-space: nowrap; }
        .tv-reloj { display: flex; align-items: center; justify-content: flex-end; }
        .tv-hora { font-size: calc(var(--header-height) * .55); font-weight: 700; line-height: 1; letter-spacing: -.02em; color: var(--tv-marino); font-variant-numeric: tabular-nums; white-space: nowrap; }

        /* Contenido institucional: recuadro oscuro. Cada pieza lo llena (recortando los bordes) o, si es muy
           distinta al recuadro, se ve completa sobre su propia imagen difuminada. */
        #multimedia-container { background: var(--tv-marino); border: 0; border-radius: .9rem; box-shadow: 0 1px 3px rgba(7, 36, 73, .1); }
        #multimedia-relleno { position: absolute; inset: 0; background-position: center; background-size: cover; opacity: .34; pointer-events: none; }
        #multimedia-content { position: relative; z-index: 1; }
        #multimedia-content .pieza-tv { position: absolute; inset: 0; width: 100%; height: 100%; max-width: none; max-height: none; object-fit: contain; }
        #multimedia-content .pieza-tv.pieza-llena { object-fit: cover; }
        #multimedia-avance { position: absolute; left: 0; right: 0; bottom: 0; z-index: 2; height: max(3px, .45vh); background: rgba(255, 255, 255, .14); opacity: 0; transition: opacity .4s; pointer-events: none; }
        #multimedia-avance.activo { opacity: 1; }
        #multimedia-avance i { display: block; height: 100%; background: rgba(255, 255, 255, .6); transform-origin: 0 50%; transform: scaleX(0); }
        @keyframes tv-avance { from { transform: scaleX(0); } to { transform: scaleX(1); } }
        .tv-sin-medios-capa { position: absolute; inset: 0; }
        .tv-sin-medios { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1.2vh; padding: 4%; background: #f5f8fc; text-align: center; }
        .tv-sin-medios img { width: min(32%, 40vh); height: auto; margin-bottom: 1vh; }
        .tv-sin-medios p { font-size: clamp(1rem, 1.7vw, 2.2rem); font-weight: 600; color: var(--tv-azul); }
        .tv-sin-medios small { font-size: clamp(.8rem, 1.05vw, 1.35rem); color: var(--tv-mudo); }

        /* Turnos: el último llamado en grande y los anteriores en lista */
        .tv-llamados { flex: 1 1 auto; min-height: 0; height: 100%; display: flex; flex-direction: column; container-type: size; }
        .tv-actual { flex: none; position: relative; overflow: hidden; border-radius: .9rem; color: #fff; background: linear-gradient(150deg, var(--tv-azul) 0%, var(--tv-azul-hondo) 100%); box-shadow: 0 2px 6px rgba(7, 36, 73, .12); }
        .tv-actual-rotulo { padding: 3.2cqh 6cqw 0; font-size: min(3.1cqw, 2.3cqh); font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: rgba(255, 255, 255, .74); }
        .tv-actual-codigo { padding: .4cqh 6cqw 0; font-size: calc(min(26cqw, 18.5cqh) * var(--escala, 1)); font-weight: 800; line-height: 1; letter-spacing: -.025em; white-space: nowrap; overflow: hidden; }
        .tv-actual-servicio { min-height: calc(min(4.1cqw, 3cqh) * 1.3 + 3.8cqh); padding: 1.2cqh 6cqw 2.6cqh; font-size: min(4.1cqw, 3cqh); font-weight: 500; line-height: 1.3; color: rgba(255, 255, 255, .84); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .tv-actual-destino { display: flex; align-items: baseline; gap: 3.4cqw; padding: 2.4cqh 6cqw 2.8cqh; background: #fff; color: var(--tv-azul); white-space: nowrap; overflow: hidden; }
        .tv-actual-destino span { flex: none; font-size: min(4.1cqw, 3cqh); font-weight: 600; color: var(--tv-mudo); }
        .tv-actual-destino b { flex: none; font-size: calc(min(13cqw, 9.4cqh) * var(--escala, 1)); font-weight: 800; line-height: 1; letter-spacing: -.02em; }
        .tv-actual.vacio .tv-actual-codigo { opacity: .45; }
        .tv-actual.vacio .tv-actual-destino b { color: #b8c5d8; }
        .tv-actual.entra { animation: tv-entra .7s cubic-bezier(.2, .8, .2, 1); }
        @keyframes tv-entra { from { transform: translateY(-12px); opacity: 0; } to { transform: none; opacity: 1; } }

        .tv-anteriores { flex: 1 1 auto; min-height: 0; margin-top: 2.4cqh; display: flex; flex-direction: column; overflow: hidden; border-radius: .9rem; background: #fff; border: 1px solid var(--tv-linea); }
        .tv-anteriores-titulo { flex: none; padding: 2.2cqh 5.4cqw 1.2cqh; font-size: min(2.9cqw, 2.1cqh); font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: var(--tv-mudo); }
        .tv-anteriores-filas { flex: 1 1 auto; min-height: 0; display: flex; flex-direction: column; }
        .tv-fila { flex: 1 1 0; min-height: 0; display: flex; align-items: center; gap: 3cqw; padding: 0 5.4cqw; border-top: 1px solid #e6edf5; overflow: hidden; white-space: nowrap; }
        .tv-fila-codigo { flex: none; font-size: calc(min(11cqw, 7.4cqh) * var(--escala, 1)); font-weight: 800; line-height: 1; letter-spacing: -.015em; color: var(--tv-marino); }
        .tv-fila-lugar { flex: none; margin-left: auto; display: flex; align-items: baseline; gap: 2.4cqw; }
        .tv-fila-lugar b { font-size: calc(min(7.6cqw, 5.3cqh) * var(--escala, 1)); font-weight: 700; line-height: 1; color: var(--tv-azul); }
        .tv-fila-estado { font-size: min(2.7cqw, 1.9cqh); font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--tv-tenue); }
        .tv-fila.es-atendido .tv-fila-codigo, .tv-fila.es-atendido .tv-fila-lugar b { color: var(--tv-tenue); }
        .tv-fila.vacia .tv-fila-codigo { color: #c5d1e2; }
        /* Pantallas 4:3 o verticales: la columna de turnos pasa abajo con alto automático; darle alto propio */
        @media (max-aspect-ratio: 4/3) { .tv-llamados { height: 40vh; } }

        /* Cinta de avisos: rótulo fijo y el mensaje repetido sin hueco */
        .ticker-container.tv-cinta { align-items: stretch; background: var(--tv-azul); box-shadow: none; border-top-color: var(--tv-azul-hondo); }
        .tv-cinta-etiqueta { flex: none; display: flex; align-items: center; padding: 0 clamp(1rem, 1.7vw, 2.6rem); background: var(--tv-marino); color: #fff; font-size: calc(var(--ticker-height) * .25); font-weight: 800; letter-spacing: .18em; text-transform: uppercase; }
        .tv-cinta-pista { position: relative; flex: 1 1 auto; min-width: 0; overflow: hidden; display: flex; align-items: center; }
        .tv-cinta-pista::before, .tv-cinta-pista::after { content: ''; position: absolute; top: 0; bottom: 0; z-index: 1; width: 3vw; pointer-events: none; }
        .tv-cinta-pista::before { left: 0; background: linear-gradient(90deg, var(--tv-azul), rgba(6, 75, 158, 0)); }
        .tv-cinta-pista::after { right: 0; background: linear-gradient(270deg, var(--tv-azul), rgba(6, 75, 158, 0)); }
        .tv-cinta .ticker-text { font-size: calc(var(--ticker-height) * .4) !important; font-weight: 500; letter-spacing: 0; text-shadow: none; }
        .tv-cinta-vuelta { display: inline-flex; align-items: center; }
        .tv-cinta-punto { flex: none; display: inline-block; width: calc(var(--ticker-height) * .11); height: calc(var(--ticker-height) * .11); margin: 0 calc(var(--ticker-height) * .55); border-radius: 50%; background: #7fb0ec; }

        /* Llamado de turno: cubre todo bajo la cabecera durante 8 s (el JS pone el "top") */
        #turnoNotificationModal .tv-llamado-fondo { position: absolute; inset: 0; background: radial-gradient(70vw 70vh at 18% 12%, rgba(255, 255, 255, .09), transparent 60%), linear-gradient(140deg, var(--tv-azul) 0%, var(--tv-azul-hondo) 100%); }
        #turnoNotificationModal .tv-llamado { position: relative; z-index: 1; height: 100%; display: flex; align-items: center; justify-content: center; padding: 3vh 4vw 5vh; }
        .tv-llamado-contenido { display: flex; flex-direction: column; align-items: center; max-width: 100%; color: #fff; opacity: 0; transform: scale(.9); transition: opacity .3s ease, transform .3s ease; }
        .tv-llamado-rotulo { font-size: 3.4vh; font-weight: 700; letter-spacing: .22em; text-transform: uppercase; color: rgba(255, 255, 255, .74); }
        .tv-llamado-codigo { max-width: 92vw; margin-top: .6vh; font-size: calc(min(30vh, 17vw) * var(--escala, 1)); font-weight: 800; line-height: .95; letter-spacing: -.03em; white-space: nowrap; overflow: hidden; }
        .tv-llamado-destino { max-width: 92vw; margin-top: 4.4vh; display: flex; align-items: baseline; gap: 2.2vw; padding: 2.4vh 3.4vw 2.8vh; border-radius: 1.6rem; background: #fff; color: var(--tv-azul); white-space: nowrap; overflow: hidden; }
        .tv-llamado-destino span { flex: none; font-size: 4.2vh; font-weight: 600; color: var(--tv-mudo); }
        .tv-llamado-destino b { flex: none; font-size: calc(min(12.6vh, 8vw) * var(--escala, 1)); font-weight: 800; line-height: 1; letter-spacing: -.02em; }
        .tv-llamado-servicio { max-width: 90vw; min-height: 1.3em; margin-top: 3.2vh; font-size: 3.2vh; font-weight: 500; line-height: 1.3; color: rgba(255, 255, 255, .84); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        #turnoNotificationModal .tv-llamado-tiempo { position: absolute; left: 0; right: 0; bottom: 0; z-index: 1; height: .75vh; background: rgba(255, 255, 255, .14); }
        #turnoNotificationModal .tv-llamado-tiempo i { display: block; height: 100%; background: rgba(255, 255, 255, .5); transform-origin: 0 50%; animation: tv-llamado-tiempo 8s linear forwards; }
        @keyframes tv-llamado-tiempo { from { transform: scaleX(1); } to { transform: scaleX(0); } }
    </style>
</head>
<body class="w-full h-screen bg-white overflow-hidden {{ $tvConfig->ticker_enabled ? 'ticker-enabled' : '' }}">
    <div class="w-full h-full bg-white tv-root">
        <!-- Cabecera: logo y unidad a la izquierda, hora a la derecha. El bloque de la unidad conserva sus líneas
             (sigla, nombre, fecha) para que la cabecera mida lo mismo que antes. -->
        @php
            $ahoraTv = \Carbon\Carbon::now('America/Bogota');
            $fechaTv = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'][$ahoraTv->dayOfWeek] . ' ' . $ahoraTv->day . ' de '
                . ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'][$ahoraTv->month - 1];
        @endphp
        <div class="grid grid-cols-6 responsive-header responsive-container tv-cabecera">
            <div class="p-4 col-span-2 tv-marca">
                <img src="{{ asset('images/logoacreditacion.png') }}" alt="Hospital Universitario del Valle, acreditado en salud" class="tv-logo">
                <span class="tv-separador" aria-hidden="true"></span>
                <div class="tv-unidad">
                    <h1 class="text-5xl font-bold text-hospital-blue leading-tight">UBA</h1>
                    <p class="tv-unidad-nombre">Unidad Básica de Atención</p>
                    <p class="text-2xl font-semibold tv-fecha" id="current-date">{{ $fechaTv }}</p>
                </div>
            </div>
            <div class="col-span-2" aria-hidden="true"></div>
            <div class="p-4 col-span-2 tv-reloj">
                <p class="tv-hora" id="current-time">{{ $ahoraTv->format('H:i') }}</p>
            </div>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-6 responsive-main responsive-container">
            <!-- Contenido institucional -->
            <div class="bg-hospital-blue-light p-3 flex flex-col col-span-4 responsive-multimedia-section responsive-container">
                <div class="flex-1 flex items-center justify-center relative overflow-hidden" id="multimedia-container">
                    <div id="multimedia-relleno" aria-hidden="true"></div>
                    <div id="multimedia-content" class="w-full h-full flex items-center justify-center">
                        <div id="multimedia-placeholder" class="tv-sin-medios">
                            <img src="{{ asset('images/logo.png') }}" alt="">
                            <p>Hospital Universitario del Valle</p>
                            <small>“Evaristo García” E.S.E.</small>
                        </div>
                    </div>
                    <div id="multimedia-avance" aria-hidden="true"><i></i></div>
                </div>
            </div>

            <!-- Turnos: el último llamado en grande y los 4 anteriores -->
            <div class="bg-hospital-blue-light p-2 col-span-2 responsive-queue-section responsive-container">
                <div class="tv-llamados" id="tv-llamados">
                    <section class="tv-actual vacio" id="tv-actual" aria-live="polite">
                        <div class="tv-actual-rotulo" id="tv-actual-rotulo">Turno</div>
                        <div class="tv-actual-codigo" id="tv-actual-codigo">– – –</div>
                        <div class="tv-actual-servicio" id="tv-actual-servicio"></div>
                        <div class="tv-actual-destino"><span>Diríjase a</span><b id="tv-actual-destino">—</b></div>
                    </section>
                    <section class="tv-anteriores">
                        <div class="tv-anteriores-titulo">Llamados anteriores</div>
                        <div class="tv-anteriores-filas" id="tv-anteriores">
                            @for ($i = 0; $i < 4; $i++)
                                <div class="tv-fila vacia"><span class="tv-fila-codigo">—</span><span class="tv-fila-lugar"><b></b></span></div>
                            @endfor
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <!-- Mensaje Ticker - En la parte inferior de la página -->
        <div class="ticker-container responsive-ticker flex items-center border-t-2 border-hospital-blue tv-cinta" style="display: {{ $tvConfig->ticker_enabled ? 'flex' : 'none' }};">
            <div class="tv-cinta-etiqueta">Avisos</div>
            <div class="tv-cinta-pista">
                <div class="ticker-content">
                    <span class="ticker-text">{{ $tvConfig->ticker_message }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Llamado de turno: cubre todo bajo la cabecera durante 8 s (mismos id que antes: los usa la cola de modales) -->
    <div id="turnoNotificationModal" class="fixed inset-0 hidden" style="z-index: 9999;">
        <div class="tv-llamado-fondo"></div>
        <div class="tv-llamado">
            <div class="tv-llamado-contenido" id="modalContent">
                <div class="tv-llamado-rotulo">Turno</div>
                <div class="tv-llamado-codigo" id="modalTurnoNumero">A001</div>
                <div class="tv-llamado-destino"><span>Diríjase a</span><b id="modalTurnoCaja">Caja 1</b></div>
                <div class="tv-llamado-servicio" id="modalTurnoServicio"></div>
            </div>
        </div>
        <div class="tv-llamado-tiempo" aria-hidden="true"><i></i></div>
    </div>

    <script>
        // ===== Modo producción: silenciar logs verbosos (perf en hot-path de 3s y, sobre
        // todo, evitar que con DevTools abierto se retengan objetos logeados -> deriva de
        // memoria en 24/7). Se CONSERVAN console.warn (watchdog/red) y console.error (fallos).
        // Poner DEBUG = true para depurar en sitio. =====
        const DEBUG = false;
        if (!DEBUG) { console.log = function () {}; console.info = function () {}; }

        // ===================================================================
        // REDISEÑO TV 2026-09 — piezas de la vista (reloj, turnos, llamado, contenido, cinta).
        // No toca la voz, el sondeo ni la cola de llamados.
        // ===================================================================
        const DIAS_TV = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        const MESES_TV = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        // Una pieza (imagen o video) llena el recuadro si para eso se recorta a lo sumo este tanto de ella;
        // si es muy distinta (afiches verticales, cuadrados), se ve completa sobre su propia imagen difuminada.
        const RECORTE_MAXIMO = 0.2;
        const LOGO_HUV_TV = @json(asset('images/logo.png'));
        const PLANTILLA_SIN_MEDIOS = '<div id="multimedia-placeholder" class="tv-sin-medios"><img src="' + LOGO_HUV_TV + '" alt="">'
            + '<p>Hospital Universitario del Valle</p><small>“Evaristo García” E.S.E.</small></div>';
        let ultimoTurnoActualId = null;
        let cintaAnimacion = null;
        let cintaVelocidad = Number(@json($tvConfig->ticker_speed)) || 30;
        let temporizadorTv = null;

        function pintarReloj(fecha, horas, minutos) {
            const hora = document.getElementById('current-time');
            if (hora) hora.textContent = horas + ':' + minutos;
            const dia = document.getElementById('current-date');
            if (dia) dia.textContent = DIAS_TV[fecha.getDay()] + ' ' + fecha.getDate() + ' de ' + MESES_TV[fecha.getMonth()];
        }

        // Lo mismo que dice la voz ("diríjase a caja número N")
        function destinoTurno(turno) {
            if (turno && turno.numero_caja) return 'Caja ' + turno.numero_caja;
            return (turno && turno.caja) ? turno.caja : '—';
        }

        // Achica la letra (variable --escala) hasta que el texto quepa en su caja; si ya cabe, no cambia nada.
        function encajar(el, caja) {
            if (!el) return;
            const limite = caja || el;
            let escala = 1;
            el.style.setProperty('--escala', '1');
            while (limite.scrollWidth > limite.clientWidth + 1 && escala > 0.55) {
                escala -= 0.05;
                el.style.setProperty('--escala', escala.toFixed(2));
            }
        }

        function ajustarLlamados() {
            encajar(document.getElementById('tv-actual-codigo'));
            encajar(document.querySelector('.tv-actual-destino'));
            document.querySelectorAll('#tv-anteriores .tv-fila').forEach(fila => encajar(fila));
        }

        function prepararLlamado(modal, turno) {
            const servicio = document.getElementById('modalTurnoServicio');
            if (servicio) servicio.textContent = turno.servicio || '';
            // Cubre todo bajo la cabecera: el logo y la hora siguen a la vista.
            const cabecera = document.querySelector('.tv-cabecera');
            modal.style.top = cabecera ? Math.round(cabecera.getBoundingClientRect().bottom) + 'px' : '0px';
        }

        function encajarLlamado() {
            encajar(document.getElementById('modalTurnoNumero'));
            encajar(document.querySelector('.tv-llamado-destino'));
        }

        // ── Contenido institucional: todas las piezas ocupan el recuadro ──
        function prepararPieza(el, evento) {
            el.classList.add('pieza-tv');
            el.addEventListener(evento, () => ajustarPieza(el), { once: true });
        }

        function ajustarPieza(el) {
            const caja = document.getElementById('multimedia-container');
            const ancho = el.naturalWidth || el.videoWidth;
            const alto = el.naturalHeight || el.videoHeight;
            if (!caja || !ancho || !alto || !caja.clientWidth || !caja.clientHeight) return;
            const proporcionPieza = ancho / alto;
            const proporcionCaja = caja.clientWidth / caja.clientHeight;
            const recorte = 1 - Math.min(proporcionPieza / proporcionCaja, proporcionCaja / proporcionPieza);
            const llenar = recorte <= RECORTE_MAXIMO;
            el.classList.toggle('pieza-llena', llenar);
            pintarRelleno(llenar ? null : el);
        }

        // Fondo difuminado barato: la pieza reducida a 48×27 px, difuminada ahí y ampliada por CSS (una vez por pieza).
        function pintarRelleno(el) {
            const fondo = document.getElementById('multimedia-relleno');
            if (!fondo) return;
            if (!el) { fondo.style.backgroundImage = ''; return; }
            try {
                const lienzo = document.createElement('canvas');
                lienzo.width = 48;
                lienzo.height = 27;
                const ctx = lienzo.getContext('2d');
                ctx.filter = 'blur(4px)';   // se difumina una sola vez aquí, no con un filtro CSS que se repinte
                ctx.drawImage(el, -8, -8, 64, 43);
                fondo.style.backgroundImage = 'url(' + lienzo.toDataURL('image/png') + ')';
            } catch (e) {
                fondo.style.backgroundImage = '';
            }
        }

        // Barra fina con el tiempo que lleva la pieza en pantalla
        function iniciarAvance(segundos) {
            const barra = document.getElementById('multimedia-avance');
            const relleno = barra && barra.firstElementChild;
            if (!relleno) return;
            relleno.style.animation = 'none';
            if (!(segundos > 0)) { barra.classList.remove('activo'); return; }
            void relleno.offsetWidth;
            relleno.style.animation = 'tv-avance ' + segundos + 's linear forwards';
            barra.classList.add('activo');
        }

        // ── Cinta: el mensaje se repite sin hueco y pasa a la misma velocidad que antes
        //    (antes recorría ancho + largo del texto en el 85 % de ticker_speed) ──
        function pintarCinta() {
            const pista = document.querySelector('.tv-cinta-pista');
            const contenido = pista && pista.querySelector('.ticker-content');
            if (!contenido || typeof contenido.animate !== 'function') return;
            const fuente = contenido.querySelector('.ticker-text');
            const mensaje = fuente ? fuente.textContent.trim() : '';
            if (cintaAnimacion) { cintaAnimacion.cancel(); cintaAnimacion = null; }
            const ancho = pista.clientWidth;
            if (!ancho || !mensaje) return;
            contenido.style.animation = 'none';
            contenido.style.paddingLeft = '0';
            contenido.textContent = '';
            const vuelta = document.createElement('span');
            vuelta.className = 'tv-cinta-vuelta';
            const texto = document.createElement('span');
            texto.className = 'ticker-text';
            texto.textContent = mensaje;
            const punto = document.createElement('span');
            punto.className = 'tv-cinta-punto';
            vuelta.append(texto, punto);
            contenido.appendChild(vuelta);
            const largo = texto.getBoundingClientRect().width;
            const paso = vuelta.getBoundingClientRect().width;
            if (!paso) return;
            for (let i = Math.ceil(ancho / paso); i > 0; i--) {
                const copia = vuelta.cloneNode(true);
                copia.setAttribute('aria-hidden', 'true');
                contenido.appendChild(copia);
            }
            const pxPorSegundo = (ancho + largo) / (0.85 * cintaVelocidad);
            cintaAnimacion = contenido.animate(
                [{ transform: 'translateX(0)' }, { transform: 'translateX(' + (-paso) + 'px)' }],
                { duration: Math.max(paso / pxPorSegundo, 1) * 1000, iterations: Infinity, easing: 'linear' }
            );
        }

        function repintarTv() {
            pintarCinta();
            ajustarLlamados();
            const pieza = document.querySelector('#multimedia-content .pieza-tv');
            if (pieza) ajustarPieza(pieza);
        }

        function repintarTvLuego() {
            clearTimeout(temporizadorTv);
            temporizadorTv = setTimeout(repintarTv, 300);
        }

        // La letra se reajusta en el acto (es barato) y lo demás (cinta, pieza) cuando termina el cambio de tamaño
        window.addEventListener('resize', function () { ajustarLlamados(); repintarTvLuego(); });
        document.addEventListener('DOMContentLoaded', function () {
            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(repintarTv);
                if (document.fonts.addEventListener) document.fonts.addEventListener('loadingdone', repintarTvLuego);
            } else {
                repintarTv();
            }
        });

        // ===== SISTEMA DE MODAL DE NOTIFICACIÓN CON COLA =====
        let modalVisible = false;
        let modalTimeout = null;
        let colaModales = []; // Cola de turnos pendientes de mostrar
        let procesandoCola = false;

        function mostrarModalTurno(turno) {
            // Agregar turno a la cola
            colaModales.push(turno);
            console.log('🎯 Turno agregado a cola de modales:', turno.codigo_completo, '- Cola:', colaModales.length);
            
            // Procesar cola si no se está procesando ya
            if (!procesandoCola) {
                procesarColaModales();
            }
        }

        function procesarColaModales() {
            if (procesandoCola) {
                console.log('🎯 Cola de modales ocupada, esperando...');
                return;
            }
            if (colaModales.length === 0) {
                console.log('🎯 Cola de modales vacía');
                return;
            }
            
            procesandoCola = true;
            const turno = colaModales.shift(); // Tomar el primer turno de la cola
            
            console.log('🎯 Procesando modal para turno:', turno.codigo_completo, '- Quedan en cola:', colaModales.length);
            console.log('🎯 Estado de la cola:', {procesandoCola, colaLength: colaModales.length, modalVisible});
            
            mostrarModalDirecto(turno);
        }

        function mostrarModalDirecto(turno) {
            if (modalVisible) {
                console.warn('🚨 Modal ya visible, reintentando...');
                setTimeout(() => mostrarModalDirecto(turno), 100);
                return;
            }
            
            const modal = document.getElementById('turnoNotificationModal');
            const modalContent = document.getElementById('modalContent');
            const turnoNumero = document.getElementById('modalTurnoNumero');
            const turnoCaja = document.getElementById('modalTurnoCaja');
            
            if (!modal || !modalContent || !turnoNumero || !turnoCaja) {
                console.error('🚨 Elementos del modal no encontrados');
                return;
            }
            
            // Actualizar información del turno
            turnoNumero.textContent = turno.codigo_completo;
            turnoCaja.textContent = destinoTurno(turno);
            prepararLlamado(modal, turno);
            
            // Mostrar modal
            modalVisible = true;
            modal.classList.remove('hidden');
            
            // Animar entrada - usar requestAnimationFrame para mejor compatibilidad
            requestAnimationFrame(() => {
                encajarLlamado();
                modalContent.style.opacity = '1';
                modalContent.style.transform = 'scale(1)';
            });
            
            // Auto-cerrar después de 8 segundos - solo para captar atención
            modalTimeout = setTimeout(() => {
                cerrarModalTurno();
            }, 8000);
            
            console.log('🎯 Modal de turno mostrado:', turno.codigo_completo);
        }

        function cerrarModalTurno() {
            if (!modalVisible) return;
            
            const modal = document.getElementById('turnoNotificationModal');
            const modalContent = document.getElementById('modalContent');
            
            if (!modal || !modalContent) {
                console.error('🚨 Elementos del modal no encontrados para cerrar');
                procesandoCola = false; // Liberar la cola en caso de error
                return;
            }
            
            // Animar salida
            modalContent.style.opacity = '0';
            modalContent.style.transform = 'scale(0.9)';
            
            // Ocultar modal después de la animación
            setTimeout(() => {
                modal.classList.add('hidden');
                modalVisible = false;
                // Resetear estilos para próxima vez
                modalContent.style.opacity = '0';
                modalContent.style.transform = 'scale(0.9)';
                
                // Marcar que terminamos de procesar este modal
                procesandoCola = false;
                
                // Procesar siguiente modal en la cola si existe
                if (colaModales.length > 0) {
                    console.log('🎯 Continuando con siguiente modal en cola...');
                    setTimeout(() => {
                        procesarColaModales();
                    }, 2000); // Pausa más larga entre modales para mejor separación visual
                }
            }, 300);
            
            // Limpiar timeout si existe
            if (modalTimeout) {
                clearTimeout(modalTimeout);
                modalTimeout = null;
            }
            
            console.log('🎯 Modal de turno cerrado');
        }

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

        // ============================================
        // SISTEMA DE RESILIENCIA DE RED 24/7
        // ============================================
        let networkOnline = navigator.onLine; // Estado actual de la red
        let erroresConsecutivosRed = 0; // Contador de errores consecutivos de fetch
        let pollingTimerId = null; // ID del setTimeout de polling (para cancelar/reiniciar)
        const POLLING_BASE_MS = 3000; // Intervalo base de polling (3 segundos)
        const POLLING_MAX_MS = 30000; // Máximo intervalo de polling cuando hay errores (30 segs)
        let pollingActualMs = POLLING_BASE_MS; // Intervalo actual de polling

        // Fetch con timeout para evitar requests colgados
        function fetchConTimeout(url, opciones = {}, timeoutMs = 10000) {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), timeoutMs);

            return fetch(url, {
                ...opciones,
                signal: controller.signal,
                cache: 'no-cache'
            }).finally(() => clearTimeout(timeoutId));
        }

        // Calcular intervalo de polling con backoff exponencial
        function calcularIntervaloPolling() {
            if (erroresConsecutivosRed === 0) return POLLING_BASE_MS;
            // 1s → 2s → 4s → 8s → 16s → 30s (máx)
            const backoff = Math.min(POLLING_BASE_MS * Math.pow(2, erroresConsecutivosRed), POLLING_MAX_MS);
            return backoff;
        }

        // Registrar éxito de red (resetear backoff)
        function registrarExitoRed() {
            const teniaErrores = erroresConsecutivosRed > 0;
            erroresConsecutivosRed = 0;
            pollingActualMs = POLLING_BASE_MS;
            networkOnline = true;
            actualizarIndicadorConexion(true);
            if (teniaErrores) {
                console.log('✅ Conexión restaurada - polling cada', pollingActualMs, 'ms');
            }
        }

        // Registrar error de red (aplicar backoff)
        function registrarErrorRed() {
            erroresConsecutivosRed++;
            pollingActualMs = calcularIntervaloPolling();
            console.warn(`⚠️ Error de red #${erroresConsecutivosRed} - próximo intento en ${pollingActualMs}ms`);
            if (erroresConsecutivosRed >= 3) {
                actualizarIndicadorConexion(false);
            }
        }

        // Indicador visual de conexión (pequeño, no intrusivo)
        function actualizarIndicadorConexion(conectado) {
            let indicador = document.getElementById('indicador-conexion');
            if (!indicador) {
                indicador = document.createElement('div');
                indicador.id = 'indicador-conexion';
                indicador.style.cssText = 'position:fixed;bottom:8px;right:8px;z-index:9999;padding:4px 10px;border-radius:12px;font-size:11px;font-weight:600;display:flex;align-items:center;gap:5px;transition:all 0.5s ease;pointer-events:none;';
                document.body.appendChild(indicador);
            }
            if (conectado) {
                indicador.style.background = 'rgba(34,197,94,0.15)';
                indicador.style.color = '#16a34a';
                indicador.innerHTML = '<span style="width:8px;height:8px;border-radius:50%;background:#22c55e;display:inline-block;"></span>';
                // Ocultar después de 5 segundos si está conectado
                setTimeout(() => { indicador.style.opacity = '0'; }, 5000);
            } else {
                indicador.style.background = 'rgba(239,68,68,0.15)';
                indicador.style.color = '#dc2626';
                indicador.style.opacity = '1';
                indicador.innerHTML = '<span style="width:8px;height:8px;border-radius:50%;background:#ef4444;display:inline-block;animation:pulse 1.5s infinite;"></span> Reconectando...';
            }
        }

        // Detectar cambios de estado de la red del navegador
        window.addEventListener('online', function() {
            console.log('🌐 Navegador reporta: ONLINE');
            networkOnline = true;
            // Sincronización inmediata al recuperar red
            erroresConsecutivosRed = 0;
            pollingActualMs = POLLING_BASE_MS;
            actualizarIndicadorConexion(true);
            // Forzar un ciclo de polling inmediato
            if (pollingTimerId) clearTimeout(pollingTimerId);
            if (window.cicloPolling) window.cicloPolling();
        });

        window.addEventListener('offline', function() {
            console.log('🌐 Navegador reporta: OFFLINE');
            networkOnline = false;
            actualizarIndicadorConexion(false);
        });

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
        // Usa una clave compuesta de turnoId_fecha_llamado para detectar turnos recién llamados
        function getTurnosReproducidos() {
            try {
                const stored = localStorage.getItem('turnos_reproducidos_' + sessionId);
                return stored ? new Set(JSON.parse(stored)) : new Set();
            } catch (e) {
                return new Set();
            }
        }

        // Generar clave única para un turno basada en ID y fecha_llamado
        function getTurnoKey(turno) {
            const fechaLlamado = turno.fecha_llamado || turno.fecha_llamado_original || '';
            return `${turno.id}_${fechaLlamado}`;
        }

        // Guardar turno como reproducido en localStorage usando clave compuesta
        function marcarTurnoReproducido(turnoId, fechaLlamado = null) {
            try {
                const reproducidos = getTurnosReproducidos();
                // Si se proporciona fecha_llamado, usar clave compuesta
                const key = fechaLlamado ? `${turnoId}_${fechaLlamado}` : turnoId;
                reproducidos.add(key);
                localStorage.setItem('turnos_reproducidos_' + sessionId, JSON.stringify([...reproducidos]));
            } catch (e) {
                console.warn('No se pudo guardar en localStorage');
            }
        }

        // Limpiar turnos reproducidos antiguos (más de 1 hora) - NO limpiar la sesión actual
        function limpiarTurnosAntiguos() {
            try {
                const keys = Object.keys(localStorage);
                const ahora = Date.now();
                const unaHora = 60 * 60 * 1000;
                const claveActual = sessionId ? ('turnos_reproducidos_' + sessionId) : null;

                keys.forEach(key => {
                    if (key.startsWith('turnos_reproducidos_tv_session_')) {
                        // NUNCA limpiar la sesión actual
                        if (claveActual && key === claveActual) return;

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

            // 🎯 MOSTRAR MODAL CUANDO COMIENZA EL LLAMADO REAL DEL TURNO
            console.log('🎯 Mostrando modal al iniciar llamado de:', siguienteTurno.codigo_completo);
            mostrarModalTurno(siguienteTurno);

            // Marcar como reproducido antes de empezar (solo para turnos reales, no repeticiones)
            // Usar clave compuesta de ID y fecha_llamado para detectar turnos recién llamados
            if (!siguienteTurno.id.toString().startsWith('repetir_')) {
                const fechaLlamado = siguienteTurno.fecha_llamado || siguienteTurno.fecha_llamado_original || '';
                marcarTurnoReproducido(siguienteTurno.id, fechaLlamado);
            }

            // Reproducir audio con callback al terminar
            playVoiceMessage(siguienteTurno, () => {
                console.log('🎯 [DEBUG] Callback de procesarColaAudio ejecutado para:', siguienteTurno.codigo_completo);
                
                reproduciendoAudio = false;
                window.ultimoInicioReproduccion = null; // Limpiar timestamp

                // Solo desactivar protección si no hay más turnos en cola
                if (colaAudio.length === 0) {
                    colaProtegida = false;
                    console.log('✅ Audio completado:', siguienteTurno.codigo_completo, '- Cola vacía, protección desactivada');
                } else {
                    console.log('✅ Audio completado:', siguienteTurno.codigo_completo, '(Turnos restantes en cola:', colaAudio.length, ') - Manteniendo protección');
                }

                // Procesar siguiente audio en la cola después de una pausa
                setTimeout(() => {
                    console.log('⏰ Timeout completado, procesando siguiente turno...');
                    procesarColaAudio();
                }, 400); // Pausa breve entre turnos de audio
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
            ticker_message: @json($tvConfig->ticker_message ?? ''),
            ticker_speed: {{ $tvConfig->ticker_speed }},
            ticker_enabled: {{ $tvConfig->ticker_enabled ? 'true' : 'false' }}
        };

        // Variables globales para multimedia
        let multimediaList = [];
        let currentMediaIndex = 0;
        let mediaTimer = null;
        let isMediaPlaying = false;

        // Variable para rastrear el último día procesado
        let ultimoDiaProcesado = null;
        
        // Actualizar la hora cada minuto (Zona horaria de Colombia)
        function updateTime() {
            // Crear fecha con zona horaria de Colombia (UTC-5)
            const now = new Date();
            const colombiaTime = new Date(now.toLocaleString("en-US", {timeZone: "America/Bogota"}));

            const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            const weekdays = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
            const month = months[colombiaTime.getMonth()];
            const weekday = weekdays[colombiaTime.getDay()];
            const day = colombiaTime.getDate().toString().padStart(2, '0');
            const hours = colombiaTime.getHours().toString().padStart(2, '0');
            const minutes = colombiaTime.getMinutes().toString().padStart(2, '0');

            pintarReloj(colombiaTime, hours, minutes);
            
            // Verificar si es medianoche (12:00 AM) para limpiar turnos del día anterior
            const fechaActual = colombiaTime.toDateString();
            const esMedianoche = hours === '00' && minutes === '00';
            
            // Solo limpiar una vez cuando cambia el día a las 12:00 AM
            if (esMedianoche && ultimoDiaProcesado !== fechaActual) {
                console.log('🕛 Medianoche detectada - Limpiando turnos del día anterior (solo de la vista, NO de BD)');
                ultimoDiaProcesado = fechaActual;
                
                // Limpiar turnos de la vista local (NO eliminar de BD)
                turnos = [];
                turnosVistos.clear();
                ultimoTurnoId = null;
                // ultimoContenidoTurnos NO se resetea aquí a propósito: así renderTurnos([])
                // detecta el cambio "con turnos" -> vacío y limpia la vista (necesario ahora
                // que renderTurnos hace early-return cuando el contenido no cambió).

                // Limpiar cola de audio
                limpiarColaAudio(true);
                
                // Limpiar localStorage de turnos reproducidos antiguos
                limpiarTurnosAntiguos();
                
                // Actualizar la vista
                renderTurnos([]);
                
                console.log('✅ Turnos limpiados de la vista (mantenidos en BD)');
            } else if (!esMedianoche && ultimoDiaProcesado === null) {
                // Inicializar el día actual si no está establecido
                ultimoDiaProcesado = fechaActual;
            }
        }

        // Actualizar configuración del TV desde el servidor
        function updateTvConfig() {
            fetchConTimeout('/api/tv-config')
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);
                    return response.json();
                })
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

        // Cargar multimedia desde el servidor con mejor manejo de errores
        let multimediaErrorCount = 0;
        const MAX_MULTIMEDIA_ERRORS = 3;
        
        function loadMultimedia() {
            fetchConTimeout('/api/multimedia')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    multimediaErrorCount = 0; // Resetear contador de errores en éxito
                    const newMultimediaList = data.multimedia || [];

                    // Comparar si la lista ha cambiado
                    const hasChanged = !arraysEqual(multimediaList, newMultimediaList);

                    if (hasChanged) {
                        console.log('Lista de multimedia actualizada');
                        multimediaList = newMultimediaList;

                        if (multimediaList.length > 0) {
                            // Resetear contador de intentos cuando hay nueva lista válida
                            intentosCargaMedia = 0;
                            
                            // Si hay multimedia y no se está reproduciendo, iniciar
                            if (!isMediaPlaying) {
                                startMediaPlayback();
                            } else {
                                // Si se está reproduciendo, verificar si el archivo actual sigue activo
                                const currentMedia = multimediaList[currentMediaIndex];
                                if (!currentMedia) {
                                    // El archivo actual ya no existe, reiniciar desde el principio
                                    currentMediaIndex = 0;
                                    intentosCargaMedia = 0; // Resetear contador
                                    showCurrentMedia();
                                }
                            }
                        } else {
                            // Solo mostrar placeholder si realmente no hay multimedia
                            // No ocultar si hay multimedia cargado previamente
                            if (multimediaList.length === 0) {
                                showPlaceholder();
                            }
                        }
                    }
                })
                .catch(error => {
                    multimediaErrorCount++;
                    console.error('Error al cargar multimedia:', error, `(Intento ${multimediaErrorCount}/${MAX_MULTIMEDIA_ERRORS})`);
                    
                    // Solo mostrar placeholder si hay múltiples errores consecutivos
                    // Esto evita que el multimedia desaparezca por errores temporales de red
                    if (multimediaErrorCount >= MAX_MULTIMEDIA_ERRORS && multimediaList.length === 0) {
                        console.warn('Múltiples errores al cargar multimedia, mostrando placeholder');
                        showPlaceholder();
                    }
                    // Si ya hay multimedia cargado, mantenerlo visible aunque haya error temporal
                });
        }



        // Función para actualizar la cola de turnos con sincronización completa
        function updateQueue() {
            if (!sincronizacionActiva) return;

            actualizarIndicadorSync('sincronizando');

            fetchConTimeout('/api/turnos-llamados')
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    // ✅ Éxito de red - resetear backoff
                    registrarExitoRed();

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
                    // Usar clave compuesta de ID y fecha_llamado para detectar turnos recién llamados
                    turnosLlamando.forEach(turno => {
                        const turnoKey = getTurnoKey(turno);
                        if (!turnosReproducidos.has(turnoKey)) {
                            turnosNuevos.push(turno);
                            console.log('🔊 Nuevo turno para reproducir:', turno.codigo_completo, 'fecha_llamado:', turno.fecha_llamado);
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
                                
                                // El modal se mostrará cuando comience el llamado real, no al entrar a la cola
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
                    // ❌ Error de red - aplicar backoff
                    registrarErrorRed();
                    // No loguear AbortError (timeout) en exceso
                    if (error.name !== 'AbortError') {
                        console.error('❌ Error de sincronización:', error);
                    }
                    actualizarIndicadorSync('error');
                });
        }

        // Función para ajustar el tamaño de fuente de toda la fila de turno
        function ajustarTamanoFuenteFila(turnoElement) {
            const numeroElement = turnoElement.querySelector('.turno-numero');
            const cajaElement = turnoElement.querySelector('.turno-caja');
            const container = turnoElement.querySelector('.turno-container');

            if (!numeroElement || !cajaElement || !container) return;

            // Con flexbox: el contenedor tiene un ancho fijo.
            // CAJA siempre se muestra completo (flex-shrink: 0).
            // El NUMERO toma el espacio restante y se ajusta si es necesario.
            const containerWidth = container.clientWidth;

            // Resetear tamaño para obtener medidas frescas
            numeroElement.style.fontSize = ''; 
            cajaElement.style.fontSize = '';
            
            // Obtener tamaño de fuente base
            let baseFontSize = parseFloat(window.getComputedStyle(numeroElement).fontSize);
            const minFontSize = 14;
            
            // Calcular cuánto espacio ocupa CAJA (siempre se respeta su tamaño)
            const cajaWidth = cajaElement.scrollWidth;
            
            // Espacio disponible para el número = contenedor - caja - padding
            const maxNumeroWidth = containerWidth - cajaWidth - 15;
            
            // Ajustar NUMERO si excede su espacio disponible
            let fontSize = baseFontSize;
            while (numeroElement.scrollWidth > maxNumeroWidth && fontSize > minFontSize) {
                fontSize -= 0.5;
                numeroElement.style.fontSize = fontSize + 'px';
            }
            
            // Igualar tamaño de CAJA al NUMERO para apariencia consistente
            if (fontSize < baseFontSize) {
                cajaElement.style.fontSize = fontSize + 'px';
            }
        }

        // Variable para evitar ajustes innecesarios
        let ultimoContenidoTurnos = '';

        // Renderizar los turnos: el más reciente en grande ("Turno … Diríjase a Caja N") y los 4 anteriores en lista.
        // Si nada cambió no se toca el DOM (el sondeo corre cada pocos segundos y casi siempre trae lo mismo).
        function renderTurnos(turnosList) {
            const lista = (turnosList || []).slice(0, 5);
            const contenidoActual = lista.length
                ? lista.map(t => `${t.id}-${t.codigo_completo}-${t.numero_caja}-${t.estado}-${t.servicio || ''}`).join('|')
                : 'sin-turnos';
            if (contenidoActual === ultimoContenidoTurnos) return;
            ultimoContenidoTurnos = contenidoActual;

            const actual = document.getElementById('tv-actual');
            const filas = document.getElementById('tv-anteriores');
            if (!actual || !filas) return;

            const primero = lista[0];
            actual.classList.toggle('vacio', !primero);
            document.getElementById('tv-actual-rotulo').textContent = primero && primero.estado === 'atendido' ? 'Último llamado' : 'Turno';
            document.getElementById('tv-actual-codigo').textContent = primero ? primero.codigo_completo : '– – –';
            document.getElementById('tv-actual-servicio').textContent = primero ? (primero.servicio || '') : 'Aún no se han llamado turnos hoy';
            document.getElementById('tv-actual-destino').textContent = primero ? destinoTurno(primero) : '—';
            if (primero && ultimoTurnoActualId !== null && primero.id !== ultimoTurnoActualId && primero.estado !== 'atendido') {
                actual.classList.remove('entra');
                void actual.offsetWidth;
                actual.classList.add('entra');
            }
            ultimoTurnoActualId = primero ? primero.id : null;

            filas.textContent = '';
            for (let i = 1; i <= 4; i++) {
                const turno = lista[i];
                const fila = document.createElement('div');
                fila.className = 'tv-fila' + (!turno ? ' vacia' : (turno.estado === 'atendido' ? ' es-atendido' : ''));
                const codigo = document.createElement('span');
                codigo.className = 'tv-fila-codigo';
                codigo.textContent = turno ? turno.codigo_completo : '—';
                const lugar = document.createElement('span');
                lugar.className = 'tv-fila-lugar';
                if (turno && turno.estado === 'atendido') {
                    const estado = document.createElement('span');
                    estado.className = 'tv-fila-estado';
                    estado.textContent = 'Atendido';
                    lugar.appendChild(estado);
                }
                const destino = document.createElement('b');
                destino.textContent = turno ? destinoTurno(turno) : '';
                lugar.appendChild(destino);
                fila.append(codigo, lugar);
                filas.appendChild(fila);
            }
            ajustarLlamados();   // directo (no en requestAnimationFrame): la letra queda ajustada antes de pintar
        }

        // Función para reproducir el mensaje de voz usando archivos pre-generados (DINÁMICO)
        function playVoiceMessage(turno, onComplete = null) {
            const codigoCompleto = turno.codigo_completo;
            const numeroCaja = turno.numero_caja;

            // Almacenar como último turno llamado para repetición manual
            ultimoTurnoLlamado = turno;

            console.log('🔊 [DEBUG] playVoiceMessage iniciada para:', codigoCompleto);
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

            // Agregar los archivos de audio para el número del turno
            // Soporta números infinitos descomponiéndolos en partes
            if (partes.numeroTurno) {
                const archivosNumero = descomponerNumeroEnAudios(parseInt(partes.numeroTurno));
                archivosNumero.forEach(archivo => audioSequence.push(archivo));
            }

            // Agregar frase de dirección y número de caja
            audioSequence.push('/audio/turnero/voice/frases/dirigirse-caja-numero.mp3');
            // El número de caja también puede ser > 99
            const archivosCaja = descomponerNumeroEnAudios(parseInt(numeroCaja));
            archivosCaja.forEach(archivo => audioSequence.push(archivo));

            console.log('🔊 Secuencia de audio generada:', audioSequence.map(file => file.split('/').pop()));

            // Indicador de audio deshabilitado para mantener la vista del TV limpia

            // Reproducir la secuencia 2 veces automáticamente
            console.log('🔊 Iniciando playAudioSequenceWithRepeat para:', codigoCompleto);
            playAudioSequenceWithRepeat(audioSequence, 2, turno, () => {
                console.log('🔊 playVoiceMessage completado para:', codigoCompleto);
                
                // El modal se muestra entre repeticiones en playAudioSequenceWithRepeat
                
                if (onComplete) {
                    onComplete();
                }
            });
        }

        /**
         * Descomponer un número en archivos de audio existentes.
         * Soporta números infinitos (1, 2, 3... hasta miles).
         * 
         * Estrategia:
         * - 1-999: archivo directo /numeros/{N}.mp3 (si existe, generado por script)
         * - 1000+: descompone dígito por dígito usando archivos 1-9
         * - Fallback: si un archivo no existe, descompone en partes menores
         * 
         * Archivos disponibles: 1-999 (generados), más algunos especiales
         */
        function descomponerNumeroEnAudios(numero) {
            const basePath = '/audio/turnero/voice/numeros';
            
            if (!numero || numero <= 0) {
                return [];
            }

            // Números 1-999: usar archivo directo
            if (numero <= 999) {
                return [`${basePath}/${numero}.mp3`];
            }

            // Números 1000+: descomponer en partes pronunciables
            // Ej: 1234 → "1" + "mil" + "234"
            // Ej: 2500 → "2" + "mil" + "500"
            // Como no tenemos audio de "mil", usamos dígito por dígito para miles
            // y archivo directo para las centenas
            
            const archivos = [];
            const numStr = numero.toString();
            
            if (numero >= 1000 && numero <= 9999) {
                // Miles: descomponer en [miles][centenas]
                const miles = Math.floor(numero / 1000);
                const resto = numero % 1000;
                
                // Dígito de miles
                archivos.push(`${basePath}/${miles}.mp3`);
                
                // Si el resto es > 0, agregar el archivo de centenas
                if (resto > 0 && resto <= 999) {
                    archivos.push(`${basePath}/${resto}.mp3`);
                }
            } else {
                // Para números muy grandes (10000+), pronunciar dígito por dígito
                for (const digito of numStr) {
                    const d = parseInt(digito);
                    if (d > 0) {
                        archivos.push(`${basePath}/${d}.mp3`);
                    }
                }
            }
            
            console.log(`🔢 Número ${numero} descompuesto en:`, archivos.map(f => f.split('/').pop()));
            return archivos;
        }

        // Función para separar dinámicamente el código del servicio y número del turno
        function separarCodigoTurno(codigoCompleto) {
            // Formato: CODIGO-NUMERO (ej: "CIT-001", "COPAGOS-123")
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
            fetchConTimeout('/api/turnos-llamados')
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    const turnosExistentes = data.turnos || [];

                    // SOLUCIÓN: Marcar todos los turnos existentes como ya reproducidos
                    // para evitar que suenen cuando alguien ingresa por primera vez a la página
                    // Usar clave compuesta de ID y fecha_llamado para detectar turnos recién llamados
                    const turnosLlamandoExistentes = turnosExistentes.filter(t => t.estado === 'llamado');
                    turnosLlamandoExistentes.forEach(turno => {
                        const fechaLlamado = turno.fecha_llamado || turno.fecha_llamado_original || '';
                        marcarTurnoReproducido(turno.id, fechaLlamado);
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

            // Polling auto-regenerativo: usa setTimeout recursivo en vez de setInterval
            // Esto evita que se acumulen requests si uno tarda mucho,
            // y aplica backoff exponencial cuando hay errores de red
            function cicloPolling() {
                updateQueue();
                pollingTimerId = setTimeout(cicloPolling, pollingActualMs);
            }
            // Hacer cicloPolling accesible para el listener de 'online'
            window.cicloPolling = cicloPolling;
            cicloPolling();
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
            limpiarVideoTimers();
            if (mediaTimer) {
                clearTimeout(mediaTimer);
                mediaTimer = null;
            }
        }

        // Cargar placeholder con transición
        function loadPlaceholder(container) {
            // Limpiar contenido actual (liberando el decoder del video saliente)
            limpiarMultimediaContainer(container);

            // Crear placeholder con transición
            const placeholderDiv = document.createElement('div');
            placeholderDiv.className = 'media-transition media-loading tv-sin-medios-capa';
            placeholderDiv.innerHTML = PLANTILLA_SIN_MEDIOS;
            pintarRelleno(null);
            iniciarAvance(0);

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

        // Mostrar el archivo multimedia actual ("carga primero, cambia después")
        function showCurrentMedia() {
            if (multimediaList.length === 0) {
                showPlaceholder();
                return;
            }
            const media = multimediaList[currentMediaIndex];
            const container = document.getElementById('multimedia-content');
            loadNewMedia(media, container);
        }

        // ============================================================
        // FIX 2026: reproductor robusto de multimedia.
        //  - Sin fuga de decoders: el <video> saliente se apaga (pause +
        //    removeAttribute('src') + load) antes de quitarlo del DOM.
        //  - "Carga primero, cambia después": el medio nuevo se carga mientras
        //    el actual sigue visible; el cambio es instantáneo -> SIN blanco.
        //  - Watchdogs: timeout de carga + estancamiento + seguridad por
        //    duración real -> no se queda pegado.
        // ============================================================
        let videoWatchdogTimer = null; // timeout de seguridad por clip (en reproducción)
        let videoStallTimer = null;    // timeout ante stall/waiting
        let videoGen = 0;              // generación de carga; invalida timers/handlers viejos

        function limpiarVideoWatchdog() {
            if (videoWatchdogTimer) { clearTimeout(videoWatchdogTimer); videoWatchdogTimer = null; }
        }
        function limpiarVideoTimers() {
            limpiarVideoWatchdog();
            if (videoStallTimer) { clearTimeout(videoStallTimer); videoStallTimer = null; }
        }
        // Apaga un <video> liberando su decoder en Chrome (clave contra la fuga)
        function destruirVideo(video) {
            if (!video || video.tagName !== 'VIDEO') return;
            try {
                video.onloadedmetadata = null;
                video.oncanplay = null;
                video.onloadeddata = null;
                video.onended = null;
                video.onerror = null;
                video.onstalled = null;
                video.onwaiting = null;
                video.onplaying = null;
                video.pause();
                video.removeAttribute('src');
                video.load(); // fuerza a Chrome a soltar el pipeline/decoder
            } catch (e) { /* el elemento puede estar ya detached */ }
        }
        // Vacía el contenedor liberando primero el <video> activo y sus timers
        function limpiarMultimediaContainer(container) {
            limpiarVideoTimers();
            const v = container.querySelector('video');
            if (v) destruirVideo(v);
            container.innerHTML = '';
        }

        // Cargar el medio nuevo SIN borrar el anterior; cambiar solo cuando esté listo
        // (elimina el "blanco" entre medios) y con timeout de carga (no se queda pegado).
        function loadNewMedia(media, container) {
            if (!media) return;
            if (mediaTimer) { clearTimeout(mediaTimer); mediaTimer = null; }
            limpiarVideoTimers();
            const gen = ++videoGen;        // esta carga; invalida timers/handlers de cargas previas
            let avanzado = false;
            let loadTimer = null;

            const avanzarUnaVez = (motivo) => {
                if (avanzado || gen !== videoGen) return;
                avanzado = true;
                if (loadTimer) { clearTimeout(loadTimer); loadTimer = null; }
                limpiarVideoTimers();
                if (motivo) console.warn('▶ Avance forzado:', motivo, media && media.url);
                nextMedia();
            };

            // Cambio INSTANTÁNEO: el contenido nuevo ya está cargado; apaga/quita el
            // anterior y muestra el nuevo en el mismo paso síncrono -> no hay frame en blanco.
            const swap = (nuevoEl) => {
                if (gen !== videoGen) return;
                limpiarMultimediaContainer(container); // teardown del video saliente + innerHTML=''
                container.appendChild(nuevoEl);
                intentosCargaMedia = 0; // Resetear contador al mostrar exitosamente
            };

            // Si el medio nuevo no llega a estar listo en 12s, saltar (cubre "pegado al cargar")
            loadTimer = setTimeout(() => avanzarUnaVez('timeout de carga (medio no cargó)'), 12000);

            if (media.tipo === 'imagen') {
                const img = document.createElement('img');
                img.className = 'max-w-full max-h-full object-contain';
                prepararPieza(img, 'load');
                img.alt = media.nombre || '';
                img.onload = () => {
                    if (gen !== videoGen) return;
                    clearTimeout(loadTimer); loadTimer = null;
                    swap(img);
                    const dur = (media.duracion && media.duracion > 0) ? media.duracion : 10;
                    iniciarAvance(dur);
                    mediaTimer = setTimeout(() => avanzarUnaVez(), dur * 1000);
                };
                img.onerror = () => avanzarUnaVez('error de imagen');
                img.src = media.url;

            } else if (media.tipo === 'video') {
                const video = document.createElement('video');
                video.className = 'max-w-full max-h-full object-contain';
                prepararPieza(video, 'canplay');
                video.muted = true;
                video.loop = false;
                video.playsInline = true;
                video.preload = 'auto';

                let mostrado = false;
                const stall = () => { if (videoStallTimer) return; videoStallTimer = setTimeout(() => avanzarUnaVez('estancamiento (stall/waiting)'), 8000); };
                const cancelarStall = () => { if (videoStallTimer) { clearTimeout(videoStallTimer); videoStallTimer = null; } };

                // 'canplay' = ya puede arrancar; aquí recién hacemos el swap y reproducimos.
                video.oncanplay = () => {
                    if (mostrado || gen !== videoGen) return;
                    mostrado = true;
                    clearTimeout(loadTimer); loadTimer = null;
                    swap(video);
                    video.play().catch(() => {});
                    // Watchdog de seguridad = duración REAL del clip + 10s (no corta clips largos)
                    const dur = (isFinite(video.duration) && video.duration > 0)
                        ? video.duration
                        : (media.duracion && media.duracion > 0 ? media.duracion : 60);
                    limpiarVideoWatchdog();
                    videoWatchdogTimer = setTimeout(() => avanzarUnaVez('timeout de seguridad'), (dur + 10) * 1000);
                    iniciarAvance(dur);
                };
                video.onplaying = cancelarStall;
                video.onstalled = stall;
                video.onwaiting = stall;
                video.onended = () => { cancelarStall(); avanzarUnaVez(); };
                video.onerror = () => { cancelarStall(); avanzarUnaVez('error de video'); };

                video.src = media.url;
                video.load();
            }
        }

        // Avanzar al siguiente archivo multimedia con transición
        let intentosCargaMedia = 0;
        const MAX_INTENTOS_MEDIA = 3;
        
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

            // Incrementar índice y asegurar que esté dentro del rango
            currentMediaIndex = (currentMediaIndex + 1) % multimediaList.length;
            
            // Si hemos intentado cargar todos los archivos sin éxito, mantener el último que funcionó
            if (intentosCargaMedia >= MAX_INTENTOS_MEDIA * multimediaList.length) {
                console.warn('⚠️ Múltiples errores al cargar multimedia, manteniendo último contenido válido');
                intentosCargaMedia = 0; // Resetear contador después de un tiempo
                return; // No intentar más, mantener lo que está visible
            }
            
            intentosCargaMedia++;
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
            cintaVelocidad = Number(speed) || cintaVelocidad;
            if (typeof Element.prototype.animate === 'function' && document.querySelector('.tv-cinta-pista')) {
                requestAnimationFrame(pintarCinta);
                return;
            }
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
                    // Forzar re-sync inmediato
                    if (pollingTimerId) clearTimeout(pollingTimerId);
                    erroresConsecutivosRed = 0;
                    pollingActualMs = POLLING_BASE_MS;
                    updateQueue();
                    if (window.cicloPolling) {
                        pollingTimerId = setTimeout(window.cicloPolling, pollingActualMs);
                    }
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
                    // Resetear errores y forzar re-sync
                    erroresConsecutivosRed = 0;
                    pollingActualMs = POLLING_BASE_MS;
                    updateQueue();
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
                fetchConTimeout('/api/tv-config', {}, 5000).catch(() => {
                    // Ignorar errores, es solo para mantener activa la conexión
                });

                // Asegurar que el AudioContext siga activo
                if (audioContext && audioContext.state === 'suspended') {
                    audioContext.resume();
                }
            }, 30000); // Cada 30 segundos
        }

        // Función para reproducir secuencia de audio con repeticiones automáticas
        function playAudioSequenceWithRepeat(audioSequence, repeticiones = 2, turnoData = null, onComplete = null) {
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
                        // Pausa breve entre repeticiones
                        console.log(`⏰ Pausa antes de repetición ${repeticionActual + 1} para ${turnoId}`);
                        
                        // Ya no manejamos el modal aquí - se maneja al detectar el turno
                        
                        setTimeout(() => {
                            reproducirConRepeticion();
                        }, 500);
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
                targetVolume = 0.2;  // Volumen reducido para el pito
                gainValue = 1.0;
            } else {
                // Aumentar volumen para archivos de voz
                targetVolume = 1.0;  // Volumen máximo del navegador
                gainValue = 3.0;     // Amplificación adicional con Web Audio API
            }

            audio.volume = targetVolume;
            
            // Aumentar la velocidad de reproducción para que sea más fluida
            audio.playbackRate = 1.35;

            // Log para debugging del volumen
            console.log(`🔊 Reproduciendo: ${audioFile.split('/').pop()} - Volumen: ${targetVolume}, Ganancia: ${gainValue}x, Velocidad: ${audio.playbackRate}x`);

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
                        }, 80);
                    }
                }, 10000);

                audio.onended = function() {
                    if (!audioCompleted) {
                        audioCompleted = true;
                        clearTimeout(timeoutId);

                        // Registrar éxito en reproducción
                        if (window.registrarExitoAudio) {
                            window.registrarExitoAudio();
                        }

                        // Limpiar conexiones de Web Audio API
                        if (audioSource && gainNode) {
                            try {
                                audioSource.disconnect();
                                gainNode.disconnect();
                            } catch (e) {
                                // Ignorar errores de desconexión
                            }
                        }

                        // Pausa mínima entre archivos de audio
                        setTimeout(() => {
                            playAudioSequence(audioFiles, index + 1, onComplete);
                        }, 80);
                    }
                };

                audio.onerror = function() {
                    if (!audioCompleted) {
                        audioCompleted = true;
                        clearTimeout(timeoutId);

                        console.error('❌ Error al reproducir audio:', audioFiles[index]);
                        
                        // Registrar error en reproducción
                        if (window.registrarErrorAudio) {
                            window.registrarErrorAudio();
                        }
                        
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
                            
                            // Registrar error en reproducción
                            if (window.registrarErrorAudio) {
                                window.registrarErrorAudio();
                            }
                            
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
            setInterval(updateTime, 15000); // la hora se ve en grande: que no se atrase casi un minuto

            initializeTicker();

            // Inicializar funcionalidad en tiempo real
            setupRealTimeListeners();

            // Cargar datos iniciales inmediatamente
            updateTvConfig();
            loadMultimedia();

            // Establecer intervalos para actualizaciones periódicas adicionales
            setInterval(updateTvConfig, 30000);
            setInterval(loadMultimedia, 30000);
            // La actualización de turnos ahora se maneja en setupRealTimeListeners con intervalo más frecuente

            // Ajustar tamaño de fuente de elementos estáticos inmediatamente
            setTimeout(() => {
                const turnoElementsEstaticos = document.querySelectorAll('#patient-queue > div:not(.opacity-50)');
                turnoElementsEstaticos.forEach(turnoElement => {
                    ajustarTamanoFuenteFila(turnoElement);
                });
            }, 100);
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

        // ============================================
        // SISTEMA DE MONITOREO Y AUTO-RECARGA 24/7
        // ============================================
        
        let erroresAudioConsecutivos = 0;
        const MAX_ERRORES_ANTES_RECARGA = 5;
        let ultimaReproduccionExitosa = Date.now();
        let audioContextRecreado = 0;
        
        // Función para registrar éxito en reproducción de audio
        window.registrarExitoAudio = function() {
            erroresAudioConsecutivos = 0;
            ultimaReproduccionExitosa = Date.now();
        };
        
        // Función para registrar error en reproducción de audio
        window.registrarErrorAudio = function() {
            erroresAudioConsecutivos++;
            console.warn(`⚠️ Error de audio consecutivo #${erroresAudioConsecutivos}`);
            
            // Si hay muchos errores consecutivos, intentar recrear AudioContext
            if (erroresAudioConsecutivos >= 3 && audioContextRecreado < 2) {
                console.warn('🔧 Intentando recrear AudioContext...');
                recrearAudioContext();
            }
            
            // Si hay demasiados errores, recargar la página
            if (erroresAudioConsecutivos >= MAX_ERRORES_ANTES_RECARGA) {
                console.error('❌ Demasiados errores de audio consecutivos. Recargando página...');
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }
        };
        
        // Función para recrear el AudioContext
        function recrearAudioContext() {
            audioContextRecreado++;
            console.log('🔄 Recreando AudioContext (intento ' + audioContextRecreado + ')...');
            
            try {
                if (audioContext) {
                    audioContext.close().catch(() => {});
                }
                
                audioContext = new (window.AudioContext || window.webkitAudioContext)();
                console.log('✅ AudioContext recreado exitosamente');
                
                // Reiniciar contador de errores
                erroresAudioConsecutivos = 0;
            } catch (e) {
                console.error('❌ Error al recrear AudioContext:', e);
            }
        }
        
        // Monitoreo periódico del estado del audio (cada 2 minutos)
        setInterval(function() {
            const tiempoSinExito = Date.now() - ultimaReproduccionExitosa;
            
            // Si el AudioContext está suspendido, intentar reanudarlo
            if (audioContext && audioContext.state === 'suspended') {
                console.log('🔧 AudioContext suspendido, intentando reanudar...');
                audioContext.resume().then(() => {
                    console.log('✅ AudioContext reanudado');
                }).catch(e => {
                    console.error('❌ Error al reanudar AudioContext:', e);
                });
            }
            
            // Si han pasado más de 30 minutos sin reproducción exitosa y hay cola de audio,
            // probablemente hay un problema
            if (tiempoSinExito > 1800000 && colaAudio.length > 0) {
                console.warn('⚠️ Más de 30 minutos sin reproducción exitosa con cola activa');
                recrearAudioContext();
            }
            
        }, 120000); // Cada 2 minutos
        
        // Auto-recarga preventiva cada 4 horas para limpiar memoria
        // Esto evita problemas acumulativos del navegador en uso 24/7
        const HORAS_PARA_RECARGA = 4;
        const tiempoRecargaMs = HORAS_PARA_RECARGA * 60 * 60 * 1000;
        const tiempoInicioSistema = Date.now();
        
        console.log(`⏰ Auto-recarga programada cada ${HORAS_PARA_RECARGA} horas`);
        
        // Verificar periódicamente si ya pasaron las 4 horas (más robusto que un solo setTimeout)
        setInterval(function() {
            const tiempoTranscurrido = Date.now() - tiempoInicioSistema;
            if (tiempoTranscurrido >= tiempoRecargaMs) {
                console.log('🔄 Ejecutando auto-recarga preventiva (4 horas)...');
                
                // Solo recargar si no hay audio reproduciéndose
                if (!reproduciendoAudio && colaAudio.length === 0) {
                    window.location.reload();
                } else {
                    // Si hay audio en proceso, reintentar en 1 minuto
                    console.log('⏳ Audio en proceso, reintentando recarga en 1 minuto...');
                }
            }
        }, 60000); // Verificar cada minuto
        
        // Log de inicio del sistema de monitoreo
        console.log('🔊 Sistema de monitoreo de audio 24/7 iniciado');
        console.log('   - Max errores antes de recarga:', MAX_ERRORES_ANTES_RECARGA);
        console.log('   - Auto-recarga preventiva cada:', HORAS_PARA_RECARGA, 'horas');
        console.log('   - Polling resiliente con backoff exponencial activado');
        console.log('   - Fetch con timeout de 10s activado');
        console.log('   - Detección de estado de red (online/offline) activada');

        // ============================================
        // WATCHDOG: Detecta si el polling murió y lo reinicia
        // ============================================
        let ultimoPollingExitoso = Date.now();
        
        // El updateQueue original actualiza este timestamp en cada éxito
        const _registrarExitoRedOriginal = registrarExitoRed;
        registrarExitoRed = function() {
            ultimoPollingExitoso = Date.now();
            _registrarExitoRedOriginal();
        };

        setInterval(function() {
            const tiempoSinPolling = Date.now() - ultimoPollingExitoso;
            
            // Si han pasado más de 2 minutos sin un polling exitoso (y hay red), algo murió
            if (tiempoSinPolling > 120000 && navigator.onLine) {
                console.warn('🐕 WATCHDOG: Polling muerto detectado (' + Math.round(tiempoSinPolling/1000) + 's sin éxito). Reiniciando...');
                erroresConsecutivosRed = 0;
                pollingActualMs = POLLING_BASE_MS;
                if (pollingTimerId) clearTimeout(pollingTimerId);
                if (window.cicloPolling) window.cicloPolling();
            }
            
            // Si han pasado más de 10 minutos sin polling exitoso, forzar recarga
            if (tiempoSinPolling > 600000 && navigator.onLine) {
                console.error('🐕 WATCHDOG: Sin conexión por más de 10 minutos con red disponible. Recargando...');
                if (!reproduciendoAudio) {
                    window.location.reload();
                }
            }
        }, 30000); // Cada 30 segundos
    </script>
</body>
</html>
