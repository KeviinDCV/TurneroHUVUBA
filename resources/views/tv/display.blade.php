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
    </style>
</head>
<body class="w-full h-screen bg-white overflow-hidden {{ $tvConfig->ticker_enabled ? 'ticker-enabled' : '' }}">
    <div class="w-full h-full bg-white">
        <!-- Header Section -->
        <div class="grid grid-cols-6 responsive-header responsive-container">
            <!-- Left Header - Apoyo Diagnostico y Hora -->
            <div class="bg-hospital-blue-light p-4 flex flex-col justify-center items-end col-span-2" style="overflow: visible !important; padding-right: 2rem;">
                <h1 class="text-5xl font-bold text-hospital-blue leading-tight mb-1" style="text-align: right;">UBA</h1>
                <!-- Hora de Colombia (UTC-5) -->
                <p class="text-2xl text-hospital-blue font-semibold" id="current-time" style="white-space: nowrap; overflow: visible; text-align: right;">{{ \Carbon\Carbon::now('America/Bogota')->format('M d - H:i') }}</p>
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
            <div class="bg-hospital-blue-light p-3 flex flex-col col-span-4 responsive-multimedia-section responsive-container">
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
            <div class="bg-hospital-blue-light p-2 col-span-2 responsive-queue-section responsive-container">
                <!-- Patient Numbers - Alineados con TURNO y MÓDULO del header -->
                <div class="space-y-3 overflow-hidden" id="patient-queue">
                    <div class="gradient-hospital text-white pl-3 pt-3 pb-3 pr-0 enhanced-shadow rounded-lg animate-slide-in flex items-center h-full">
                        <div class="grid grid-cols-2 gap-1 items-center w-full">
                            <div class="text-left flex items-center">
                                <div class="turno-numero font-bold animate-pulse-number">U001</div>
                            </div>
                            <div class="text-right flex items-center justify-end">
                                <div class="turno-caja font-semibold">CAJA 1</div>
                            </div>
                        </div>
                    </div>

                    <div class="gradient-hospital text-white pl-3 pt-3 pb-3 pr-0 enhanced-shadow rounded-lg animate-slide-in flex items-center h-full" style="animation-delay: 0.2s;">
                        <div class="grid grid-cols-2 gap-1 items-center w-full">
                            <div class="text-left flex items-center">
                                <div class="turno-numero font-bold">U002</div>
                            </div>
                            <div class="text-right flex items-center justify-end">
                                <div class="turno-caja font-semibold">CAJA 2</div>
                            </div>
                        </div>
                    </div>

                    <div class="gradient-hospital text-white pl-3 pt-3 pb-3 pr-0 enhanced-shadow rounded-lg animate-slide-in flex items-center h-full" style="animation-delay: 0.4s;">
                        <div class="grid grid-cols-2 gap-1 items-center w-full">
                            <div class="text-left flex items-center">
                                <div class="turno-numero font-bold">U003</div>
                            </div>
                            <div class="text-right flex items-center justify-end">
                                <div class="turno-caja font-semibold">CAJA 3</div>
                            </div>
                        </div>
                    </div>

                    <div class="gradient-hospital text-white pl-3 pt-3 pb-3 pr-0 enhanced-shadow rounded-lg animate-slide-in flex items-center h-full" style="animation-delay: 0.6s;">
                        <div class="grid grid-cols-2 gap-1 items-center w-full">
                            <div class="text-left flex items-center">
                                <div class="turno-numero font-bold">U004</div>
                            </div>
                            <div class="text-right flex items-center justify-end">
                                <div class="turno-caja font-semibold">CAJA 4</div>
                            </div>
                        </div>
                    </div>

                    <div class="gradient-hospital text-white pl-3 pt-3 pb-3 pr-0 enhanced-shadow rounded-lg animate-slide-in flex items-center h-full" style="animation-delay: 0.8s;">
                        <div class="grid grid-cols-2 gap-1 items-center w-full">
                            <div class="text-left flex items-center">
                                <div class="turno-numero font-bold">U005</div>
                            </div>
                            <div class="text-right flex items-center justify-end">
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

    <!-- Modal de Notificación de Nuevo Turno -->
    <div id="turnoNotificationModal" class="fixed inset-0 hidden" style="z-index: 9999;">
        <!-- Overlay con fondo blanco -->
        <div class="absolute inset-0 bg-white bg-opacity-95"></div>
        
        <!-- Contenido del Modal -->
        <div class="relative flex items-center justify-center h-full p-8" style="z-index: 10000;">
            <div class="hospital-building text-white rounded-lg enhanced-shadow p-20 max-w-6xl w-full mx-auto opacity-0 transform scale-90 transition-all duration-300" id="modalContent">
                <!-- Información del Turno - Solo turno y caja -->
                <div class="text-center">
                    <div class="mb-10">
                        <div class="font-bold tracking-wider mb-4" style="font-size: clamp(6rem, 12vw, 14rem);" id="modalTurnoNumero">A001</div>
                    </div>
                    <div class="border-t-2 border-white border-opacity-30 pt-10">
                        <div class="font-bold" style="font-size: clamp(4rem, 8vw, 10rem);" id="modalTurnoCaja">CAJA 1</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
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
            turnoCaja.textContent = `CAJA ${turno.numero_caja}`;
            
            // Mostrar modal
            modalVisible = true;
            modal.classList.remove('hidden');
            
            // Animar entrada - usar requestAnimationFrame para mejor compatibilidad
            requestAnimationFrame(() => {
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
            ticker_message: '{{ addslashes($tvConfig->ticker_message) }}',
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
            const month = months[colombiaTime.getMonth()];
            const day = colombiaTime.getDate().toString().padStart(2, '0');
            const hours = colombiaTime.getHours().toString().padStart(2, '0');
            const minutes = colombiaTime.getMinutes().toString().padStart(2, '0');

            document.getElementById('current-time').textContent = `${month} ${day} - ${hours}:${minutes}`;
            
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
                ultimoContenidoTurnos = '';
                
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

        // Renderizar los turnos en el contenedor
        function renderTurnos(turnosList) {
            const container = document.getElementById('patient-queue');

            // Crear un hash del contenido para detectar cambios reales
            const contenidoActual = turnosList.map(t => `${t.codigo_completo}-${t.numero_caja}`).join('|');
            const contenidoCambio = contenidoActual !== ultimoContenidoTurnos;
            ultimoContenidoTurnos = contenidoActual;

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
                    placeholderElement.className = 'gradient-hospital text-white pl-3 pt-3 pb-3 pr-0 enhanced-shadow rounded-lg opacity-50 flex items-center h-full';

                    placeholderElement.innerHTML = `
                    <div class="turno-container">
                        <div class="turno-numero font-bold">----</div>
                        <div class="turno-caja font-semibold">CAJA -</div>
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
                let clases = 'gradient-hospital text-white pl-3 pt-3 pb-3 pr-0 enhanced-shadow rounded-lg flex items-center h-full';

                // Animación solo para turnos nuevos llamados
                if (i === 0 && !yaAnimado && !esAtendido) {
                    clases += ' new-turn';
                    sessionStorage.setItem('turno_animado_' + turno.id, 'true');
                    
                    // NOTA: El modal ahora se muestra después de completar el audio del turno
                    // Ver función playVoiceMessage para la implementación
                }

                turnoElement.className = clases;

                // Badge de estado dentro de la tarjeta, en la esquina superior derecha
                // Ajustado con top negativo para compensar el padding del contenedor padre (pt-3) y quedar al borde
                const estadoBadge = esAtendido ?
                    '<div style="position: absolute; top: -8px; right: 8px; z-index: 10;"><span class="bg-green-500 text-white px-2 py-0.5 rounded-b text-[9px] font-bold shadow-sm tracking-wider uppercase border-x border-b border-white/20">ATENDIDO</span></div>' :
                    '';

                turnoElement.innerHTML = `
                    <div style="position: relative; width: 100%; height: 100%; display: flex; align-items: center;">
                        <div class="turno-container" style="width: 100%;">
                            <div class="turno-numero font-bold">${turno.codigo_completo}</div>
                            <div class="turno-caja font-semibold">CAJA ${turno.numero_caja || ''}</div>
                        </div>
                        ${estadoBadge}
                    </div>
                `;

                container.appendChild(turnoElement);
            }



            // Si hay menos de 5 turnos, rellenar con placeholders
            for (let i = turnosLimitados.length; i < 5; i++) {
                const placeholderElement = document.createElement('div');
                placeholderElement.className = 'gradient-hospital text-white pl-3 pt-3 pb-3 pr-0 enhanced-shadow rounded-lg opacity-50 flex items-center h-full';

                placeholderElement.innerHTML = `
                    <div class="relative">
                        <div class="turno-container">
                            <div class="turno-numero font-bold">----</div>
                            <div class="turno-caja font-semibold">CAJA -</div>
                        </div>
                    </div>
                `;

                container.appendChild(placeholderElement);
            }



            // Ajustar tamaño de fuente solo si el contenido cambió
            if (contenidoCambio) {
                // Usar requestAnimationFrame para mejor rendimiento
                requestAnimationFrame(() => {
                    const turnoElements = container.querySelectorAll('div:not(.opacity-50)');
                    turnoElements.forEach(turnoElement => {
                        ajustarTamanoFuenteFila(turnoElement);
                    });


                });
            }
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
                    intentosCargaMedia = 0; // Resetear contador al cargar exitosamente

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
                    // Intentar siguiente media después de un breve delay
                    setTimeout(() => {
                        nextMedia();
                    }, 500);
                };

            } else if (media.tipo === 'video') {
                // Mostrar video
                const video = document.createElement('video');
                video.src = media.url;
                video.className = 'max-w-full max-h-full object-contain media-transition media-loading';
                video.autoplay = true;
                video.muted = true;
                video.loop = false;
                video.playsInline = true; // Importante para rendimiento en móviles y algunos navegadores
                video.preload = 'auto'; // Preargar metadatos y buffer
                
                // Manejo de errores para evitar bucles infinitos
                let errorHandled = false;

                video.onloadeddata = () => {
                    container.appendChild(video);
                    intentosCargaMedia = 0; // Resetear contador al cargar exitosamente

                    // Aplicar transición de entrada
                    setTimeout(() => {
                        video.classList.remove('media-loading');
                        video.classList.add('media-fade-in', 'media-enter');
                    }, 50);
                };

                video.onended = () => {
                    // Limpiar video para liberar memoria antes de pasar al siguiente
                    video.src = "";
                    video.load();
                    nextMedia();
                };

                video.onerror = () => {
                    if (errorHandled) return;
                    errorHandled = true;
                    console.error('Error al cargar video:', media.url);
                    // Intentar siguiente media después de un breve delay
                    setTimeout(() => {
                        nextMedia();
                    }, 1000); // Aumentar delay para evitar sobrecarga si falla repetidamente
                };
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
            setInterval(updateTime, 60000);

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
