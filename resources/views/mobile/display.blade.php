<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
    <title>{{ config('app.name', 'Turnero HUV') }} - Vista Móvil</title>
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
            font-size: 0.875rem;
            animation: ticker-glow 3s ease-in-out infinite;
            letter-spacing: 0.5px;
        }

        /* Mejoras visuales para móvil */
        .mobile-shadow {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
        }

        .mobile-border {
            border: 1px solid #064b9e;
            border-radius: 6px;
        }

        /* Transiciones para multimedia - simplificadas */
        .media-transition {
            transition: opacity 0.3s ease;
        }

        .media-fade-in {
            opacity: 1;
        }

        .media-fade-out {
            opacity: 0;
        }

        .media-loading {
            opacity: 0;
        }

        /* Optimizaciones para móvil */
        .mobile-container {
            min-height: 100vh;
            min-height: 100dvh; /* Para navegadores modernos */
            display: flex;
            flex-direction: column;
        }

        /* Asegurar scroll vertical */
        html, body {
            overflow-x: hidden;
            overflow-y: auto !important;
            height: auto !important;
            min-height: 100vh;
            -webkit-overflow-scrolling: touch; /* Para iOS */
        }

        /* Forzar scroll en móviles */
        @media (max-width: 768px) {
            body {
                overflow-y: scroll !important;
                height: auto !important;
                position: relative !important;
            }

            .mobile-container {
                overflow: visible !important;
                height: auto !important;
                min-height: 100vh;
            }
        }

        /* Evitar zoom en inputs */
        input, select, textarea {
            font-size: 16px;
        }

        /* Ocultar scrollbars en móvil */
        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }

        /* Responsive para sección de turnos móvil - Sin restricciones de altura */
        #patient-queue {
            /* Permitir crecimiento natural del contenido */
        }

        /* Ajustes responsive para diferentes tamaños de celular */
        @media (max-height: 667px) {
            /* iPhone SE, iPhone 8 y similares */
            .mobile-container {
                font-size: 14px;
            }

            #patient-queue .text-lg {
                font-size: 1rem !important;
            }

            #patient-queue .text-sm {
                font-size: 0.75rem !important;
            }
        }

        /* Ajustes para pantallas muy pequeñas */
        @media (max-width: 320px) {
            /* iPhone 5/SE y similares */
            .mobile-container {
                font-size: 12px;
            }

            #patient-queue .min-h-\[70px\] {
                min-height: 60px !important;
            }

            #patient-queue .p-4 {
                padding: 0.75rem !important;
            }
        }

        /* Ajustes para pantallas medianas */
        @media (min-width: 375px) and (max-width: 414px) {
            /* iPhone 6/7/8, iPhone X/11 Pro y similares */
            #patient-queue .text-lg {
                font-size: 1.125rem !important;
            }
        }

        /* Ajustes para pantallas grandes de móvil */
        @media (min-width: 415px) {
            /* iPhone Plus, iPhone 11/XR y similares */
            #patient-queue .text-lg {
                font-size: 1.25rem !important;
            }

            #patient-queue .text-sm {
                font-size: 0.875rem !important;
            }
        }
    </style>
</head>
<body class="w-full bg-white overflow-y-auto">
    <div class="mobile-container bg-white min-h-screen">
        <!-- Header Section - Compacto para móvil -->
        <div class="bg-hospital-blue-light p-3">
            <div class="flex items-center justify-between">
                <!-- Logo y Hospital Info -->
                <div class="flex items-center space-x-3">
                    <img src="{{ asset('images/logoacreditacion.png') }}" alt="Logo HUV" class="h-10 w-auto max-w-none" style="mix-blend-mode: multiply; filter: contrast(1.2);">
                    <div>
                        <h2 class="text-sm font-bold text-hospital-blue leading-tight">HOSPITAL UNIVERSITARIO</h2>
                        <h3 class="text-sm font-bold text-hospital-blue leading-tight">DEL VALLE</h3>
                    </div>
                </div>

                <!-- Hora -->
                <div class="text-right">
                    <p class="text-lg text-hospital-blue font-semibold" id="current-time">{{ \Carbon\Carbon::now('America/Bogota')->format('M d - H:i') }}</p>
                </div>
            </div>
        </div>

        @if(isset($turnoInfo) && $turnoInfo)
        <!-- Información del Turno Personal -->
        <div id="turno-personal-container" class="gradient-hospital text-white p-3 shadow-lg">
            <script>console.log('🎯 TurnoInfo encontrado:', @json($turnoInfo));</script>



            <div class="text-center">
                <div class="flex items-center justify-center mb-2">
                    <svg class="w-6 h-6 mr-2 text-white drop-shadow-lg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h2 class="text-lg font-bold text-white drop-shadow-lg">Su Turno</h2>
                </div>

                <div class="bg-white rounded-lg p-2 mb-2 shadow-lg border border-hospital-blue border-opacity-30">
                    <div class="text-2xl font-bold mb-1 text-hospital-blue">{{ $turnoInfo['turno']->codigo_completo }}</div>
                    <div class="text-sm text-hospital-blue font-medium">{{ $turnoInfo['turno']->servicio->nombre }}</div>
                </div>

                <div id="turno-estado-container">
                @if($turnoInfo['turno']->estado === 'atendido')
                    <div class="bg-green-500 bg-opacity-80 rounded-lg p-3 shadow-lg border border-green-300">
                        <div class="text-sm font-medium text-white drop-shadow-md">✅ Su turno ya fue atendido</div>
                    </div>
                @elseif($turnoInfo['turno']->estado === 'llamado')
                    <div class="bg-yellow-500 bg-opacity-80 rounded-lg p-3 animate-pulse shadow-lg border border-yellow-300">
                        <div class="text-sm font-medium text-white drop-shadow-md">🔔 Su turno está siendo llamado</div>
                        <div class="text-xs mt-1 text-white drop-shadow-md">Diríjase a la caja indicada</div>
                    </div>
                @else
                    <div class="bg-blue-600 bg-opacity-90 rounded-lg p-3 shadow-lg border border-blue-500">
                        @if($turnoInfo['turnos_adelante'] > 0)
                            <div class="text-sm font-medium text-white drop-shadow-md">Faltan {{ $turnoInfo['turnos_adelante'] }} turnos</div>
                            <div class="text-xs mt-1 text-white drop-shadow-md">Tiempo estimado: {{ $turnoInfo['tiempo_estimado'] }} minutos</div>
                        @else
                            <div class="text-sm font-medium text-white drop-shadow-md">Su turno será llamado próximamente</div>
                        @endif
                    </div>
                @endif
                </div>
            </div>
        </div>
        @else
        <script>
            console.log('❌ No hay turnoInfo disponible');
            console.log('🔍 URL actual:', window.location.href);
            console.log('🔍 Parámetros URL:', new URLSearchParams(window.location.search).get('turno'));
            console.log('🔍 Variables disponibles:', {
                turnoInfo: @json($turnoInfo ?? 'undefined'),
                tvConfig: @json(isset($tvConfig) ? 'exists' : 'missing')
            });
        </script>
        @endif

        <!-- Main Content - Stack vertical para móvil -->
        <div class="flex flex-col">
            <!-- Multimedia Content - Ahora arriba -->
            <div class="bg-hospital-blue-light p-3">
                <div class="bg-white rounded-lg mobile-border mobile-shadow aspect-video flex items-center justify-center relative overflow-hidden" id="multimedia-container">
                    <!-- Contenido multimedia dinámico -->
                    <div id="multimedia-content" class="w-full h-full flex items-center justify-center">
                        <!-- Placeholder content -->
                        <div id="multimedia-placeholder" class="text-center text-gray-400 z-10">
                            <div class="text-4xl mb-2 opacity-50">🏥</div>
                            <p class="text-sm font-semibold text-hospital-blue mb-1">Contenido Multimedia</p>
                            <p class="text-xs text-gray-500">Videos e imágenes del hospital</p>
                        </div>

                        <!-- Decorative background pattern -->
                        <div class="absolute inset-0 opacity-5">
                            <div class="w-full h-full" style="background-image: repeating-linear-gradient(45deg, #064b9e 0px, #064b9e 5px, transparent 5px, transparent 10px);"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Turnos Header - Ahora abajo -->
            <div class="gradient-hospital flex">
                <div class="flex-1 bg-hospital-blue flex items-center justify-center py-2">
                    <h1 class="text-lg font-bold text-white">TURNO</h1>
                </div>
                <div class="flex-1 gradient-hospital-light flex items-center justify-center py-2">
                    <h1 class="text-lg font-bold text-white">MÓDULO</h1>
                </div>
            </div>

            <!-- Patient Queue - Dinámico desde el servidor -->
            <div class="bg-hospital-blue-light p-4 pb-16">
                <div class="space-y-3" id="patient-queue">
                    <!-- Placeholders iniciales para móvil (4 turnos) -->
                    <div class="gradient-hospital text-white p-4 mobile-shadow rounded-lg opacity-50 min-h-[70px] flex items-center">
                        <div class="grid grid-cols-2 gap-4 items-center w-full">
                            <div class="text-left pl-2">
                                <div class="text-lg font-bold text-gray-300">----</div>
                            </div>
                            <div class="text-right pr-4">
                                <div class="text-sm font-semibold text-gray-300">CAJA -</div>
                            </div>
                        </div>
                    </div>
                    <div class="gradient-hospital text-white p-4 mobile-shadow rounded-lg opacity-50 min-h-[70px] flex items-center">
                        <div class="grid grid-cols-2 gap-4 items-center w-full">
                            <div class="text-left pl-2">
                                <div class="text-lg font-bold text-gray-300">----</div>
                            </div>
                            <div class="text-right pr-4">
                                <div class="text-sm font-semibold text-gray-300">CAJA -</div>
                            </div>
                        </div>
                    </div>
                    <div class="gradient-hospital text-white p-4 mobile-shadow rounded-lg opacity-50 min-h-[70px] flex items-center">
                        <div class="grid grid-cols-2 gap-4 items-center w-full">
                            <div class="text-left pl-2">
                                <div class="text-lg font-bold text-gray-300">----</div>
                            </div>
                            <div class="text-right pr-4">
                                <div class="text-sm font-semibold text-gray-300">CAJA -</div>
                            </div>
                        </div>
                    </div>
                    <div class="gradient-hospital text-white p-4 mobile-shadow rounded-lg opacity-50 min-h-[70px] flex items-center">
                        <div class="grid grid-cols-2 gap-4 items-center w-full">
                            <div class="text-left pl-2">
                                <div class="text-lg font-bold text-gray-300">----</div>
                            </div>
                            <div class="text-right pr-4">
                                <div class="text-sm font-semibold text-gray-300">CAJA -</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Espacio adicional para scroll -->
                <div class="h-20 bg-hospital-blue-light"></div>
            </div>
        </div>

        <!-- Mensaje Ticker - Más pequeño para móvil -->
        <div class="ticker-container h-10 flex items-center border-t border-hospital-blue" style="display: {{ $tvConfig->ticker_enabled ? 'flex' : 'none' }};">
            <div class="ticker-content">
                <span class="ticker-text">
                    {{ $tvConfig->ticker_message }}
                </span>
            </div>
        </div>


    </div>

    <script>
        // Actualizar la hora cada minuto (Zona horaria de Colombia)
        function updateTime() {
            const now = new Date();
            const colombiaTime = new Date(now.toLocaleString("en-US", {timeZone: "America/Bogota"}));

            const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            const month = months[colombiaTime.getMonth()];
            const day = colombiaTime.getDate().toString().padStart(2, '0');
            const hours = colombiaTime.getHours().toString().padStart(2, '0');
            const minutes = colombiaTime.getMinutes().toString().padStart(2, '0');

            document.getElementById('current-time').textContent = `${month} ${day} - ${hours}:${minutes}`;
        }

        // Actualizar inmediatamente y luego cada minuto
        updateTime();
        setInterval(updateTime, 60000);

        // Variables globales para la configuración del TV
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

        // Variables globales para turnos
        let turnos = []; // Historial de turnos
        let turnosVistos = new Set(); // Conjunto para rastrear turnos ya mostrados
        let ultimoTurnoId = null; // ID del último turno para detectar nuevos
        let sincronizacionActiva = true; // Control de sincronización

        // Sistema de cola de audio
        let colaAudio = []; // Cola de turnos pendientes de reproducir
        let reproduciendoAudio = false; // Estado de reproducción actual
        let colaProtegida = false; // Protección contra limpieza de cola durante reproducción
        let sessionId = null; // ID único de sesión para evitar duplicados

        // Variables para seguimiento de turno personal
        @if($turnoInfo)
        let turnoPersonalId = {{ $turnoInfo['turno']->id }};
        let turnoPersonalCodigo = '{{ $turnoInfo['turno']->codigo_completo }}';
        @else
        let turnoPersonalId = null;
        let turnoPersonalCodigo = null;
        @endif

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
            const updateIndicator = document.getElementById('updateIndicator');

            // Mostrar indicador de actualización
            if (updateIndicator) {
                updateIndicator.style.opacity = '1';
                setTimeout(() => {
                    updateIndicator.style.opacity = '0';
                }, 2000);
            }

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
            } else {
                // Ocultar ticker si está deshabilitado
                if (tickerContainer) {
                    tickerContainer.style.display = 'none';
                }
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
                    <div class="text-4xl mb-2 opacity-50">🏥</div>
                    <p class="text-sm font-semibold text-hospital-blue mb-1">Contenido Multimedia</p>
                    <p class="text-xs text-gray-500">Videos e imágenes del hospital</p>
                </div>
                <div class="absolute inset-0 opacity-5">
                    <div class="w-full h-full" style="background-image: repeating-linear-gradient(45deg, #064b9e 0px, #064b9e 5px, transparent 5px, transparent 10px);"></div>
                </div>
            `;

            container.appendChild(placeholderDiv);

            // Aplicar transición de entrada
            setTimeout(() => {
                placeholderDiv.classList.remove('media-loading');
                placeholderDiv.classList.add('media-fade-in');
            }, 50);
        }

        // Iniciar reproducción de multimedia
        function startMediaPlayback() {
            if (multimediaList.length === 0) {
                showPlaceholder();
                return;
            }

            currentMediaIndex = 0;
            isMediaPlaying = true;
            showCurrentMedia();
        }

        // Mostrar el archivo multimedia actual
        function showCurrentMedia() {
            if (multimediaList.length === 0) {
                showPlaceholder();
                return;
            }

            const media = multimediaList[currentMediaIndex];
            if (!media) {
                showPlaceholder();
                return;
            }

            const container = document.getElementById('multimedia-content');

            // Aplicar transición de salida al contenido actual
            const currentContent = container.children[0];
            if (currentContent && !currentContent.id.includes('placeholder')) {
                currentContent.classList.add('media-transition', 'media-fade-out');

                setTimeout(() => {
                    loadCurrentMedia(container, media);
                }, 400);
            } else {
                loadCurrentMedia(container, media);
            }
        }

        // Cargar el archivo multimedia actual
        function loadCurrentMedia(container, media) {
            // Limpiar contenido actual
            container.innerHTML = '';

            let mediaElement;

            if (media.tipo === 'imagen') {
                // Crear elemento de imagen
                mediaElement = document.createElement('img');
                mediaElement.src = media.url;
                mediaElement.alt = media.nombre;
                mediaElement.className = 'w-full h-full object-contain media-transition media-loading';

                mediaElement.onload = function() {
                    // Aplicar transición de entrada
                    setTimeout(() => {
                        mediaElement.classList.remove('media-loading');
                        mediaElement.classList.add('media-fade-in');
                    }, 50);

                    // Programar siguiente archivo
                    scheduleNextMedia(media.duracion * 1000);
                };

                mediaElement.onerror = function() {
                    console.error('Error al cargar imagen:', media.url);
                    nextMedia();
                };

            } else if (media.tipo === 'video') {
                // Crear elemento de video
                mediaElement = document.createElement('video');
                mediaElement.src = media.url;
                mediaElement.className = 'w-full h-full object-contain media-transition media-loading';
                mediaElement.muted = true;
                mediaElement.autoplay = true;
                mediaElement.loop = false;

                mediaElement.onloadeddata = function() {
                    // Aplicar transición de entrada
                    setTimeout(() => {
                        mediaElement.classList.remove('media-loading');
                        mediaElement.classList.add('media-fade-in');
                    }, 50);
                };

                mediaElement.onended = function() {
                    nextMedia();
                };

                mediaElement.onerror = function() {
                    console.error('Error al cargar video:', media.url);
                    nextMedia();
                };
            }

            if (mediaElement) {
                container.appendChild(mediaElement);
            }
        }

        // Programar siguiente archivo multimedia
        function scheduleNextMedia(delay) {
            if (mediaTimer) {
                clearTimeout(mediaTimer);
            }

            mediaTimer = setTimeout(() => {
                nextMedia();
            }, delay);
        }

        // Avanzar al siguiente archivo multimedia
        function nextMedia() {
            if (multimediaList.length === 0) {
                showPlaceholder();
                return;
            }

            currentMediaIndex = (currentMediaIndex + 1) % multimediaList.length;
            showCurrentMedia();
        }

        // Reiniciar ticker con nueva velocidad
        function restartTicker(speed) {
            const tickerContent = document.querySelector('.ticker-content');
            if (tickerContent) {
                // Remover animación actual
                tickerContent.style.animation = 'none';

                // Forzar reflow
                tickerContent.offsetHeight;

                // Aplicar nueva animación
                tickerContent.style.animation = `ticker-scroll ${speed}s linear infinite`;
            }
        }

        // ========== SISTEMA DE TURNOS Y AUDIO ==========

        // Generar ID único de sesión
        function generarSessionId() {
            return 'mobile_session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
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

        // Marcar turno como reproducido
        function marcarTurnoReproducido(turnoId) {
            try {
                const turnosReproducidos = getTurnosReproducidos();
                turnosReproducidos.add(turnoId);
                localStorage.setItem('turnos_reproducidos_' + sessionId, JSON.stringify([...turnosReproducidos]));
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
                    if (key.startsWith('turnos_reproducidos_mobile_session_')) {
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

            // Audio iniciado (sin indicador visual para usuarios)

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

        // Funciones de debugging simplificadas (solo para desarrollo)
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            // Solo disponible en desarrollo
            window.mostrarEstadoCola = function() {
                console.log('📊 Estado actual de la cola de audio:', {
                    reproduciendoAudio: reproduciendoAudio,
                    colaProtegida: colaProtegida,
                    colaLength: colaAudio.length,
                    turnos: colaAudio.map(t => t.codigo_completo)
                });
            };

            // Función de prueba para archivos específicos
            window.probarAudio = function(archivo) {
                const audio = new Audio(archivo);
                audio.volume = 1.0;

                audio.addEventListener('loadstart', () => console.log('🔄 Iniciando carga:', archivo));
                audio.addEventListener('canplay', () => console.log('✅ Puede reproducir:', archivo));
                audio.addEventListener('error', (e) => console.error('❌ Error:', archivo, e));

                audio.play().then(() => {
                    console.log('🔊 Reproduciendo:', archivo);
                }).catch(error => {
                    console.error('❌ Error al reproducir:', archivo, error);
                });
            };
        }

        // Ejecutar verificación de estado cada 5 segundos
        setInterval(verificarEstadoColaAudio, 5000);

        // Variable global para almacenar el último turno llamado (solo para logging)
        let ultimoTurnoLlamado = null;

        // Variables para mantener la página activa y audio
        let audioContext;

        // Función principal para reproducir mensaje de voz
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
                // Tomar todo excepto la última parte como código de servicio
                codigoServicio = partes.slice(0, -1).join('-');
                // La última parte es el número - convertir a entero para eliminar ceros a la izquierda
                const numeroCompleto = partes[partes.length - 1];
                numeroTurno = parseInt(numeroCompleto, 10).toString(); // Convierte "001" a "1"
            } else {
                // Si no hay guión, asumir que todo es código de servicio
                codigoServicio = codigoCompleto;
            }

            // Convertir código de servicio a array de letras
            const letrasServicio = codigoServicio.split('').filter(char => /[A-Za-z]/.test(char));

            return {
                original: codigoCompleto,
                codigoServicio: codigoServicio,
                letrasServicio: letrasServicio,
                numeroTurno: numeroTurno
            };
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

            // Para archivos de voz, usar volumen máximo del navegador
            // (Evitamos Web Audio API para prevenir superposición)
            if (gainValue > 1.0) {
                audio.volume = 1.0; // Volumen máximo del navegador
            }

            let audioCompleted = false;

            // Timeout de seguridad para archivos individuales (10 segundos máximo)
            const timeoutId = setTimeout(() => {
                if (!audioCompleted) {
                    console.warn('⚠️ Timeout en archivo de audio:', audioFile.split('/').pop());
                    audioCompleted = true;

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

                    // Pequeña pausa entre archivos para que suene más natural
                    setTimeout(() => {
                        playAudioSequence(audioFiles, index + 1, onComplete);
                    }, 200);
                }
            };

            audio.onerror = function(e) {
                if (!audioCompleted) {
                    audioCompleted = true;
                    clearTimeout(timeoutId);

                    console.error('❌ Error en audio:', audioFile.split('/').pop(), 'Error:', e, 'NetworkState:', audio.networkState, 'ReadyState:', audio.readyState);

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

                        // Intentar continuar con el siguiente archivo
                        setTimeout(() => {
                            playAudioSequence(audioFiles, index + 1, onComplete);
                        }, 200);
                    }
                });
            }
        }

        // Función para actualizar la cola de turnos con sincronización completa (COPIADO EXACTAMENTE DE LA VISTA TV)
        function updateQueue() {
            if (!sincronizacionActiva) return;

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

                    // Renderizar los turnos actualizados
                    console.log('📱 Llamando renderTurnos desde updateQueue con:', newTurnos.length, 'turnos');
                    renderTurnos(newTurnos);

                    // Log de estado para debugging (solo cuando hay cambios)
                    const llamandoCount = newTurnos.filter(t => t.estado === 'llamado').length;
                    const atendidoCount = newTurnos.filter(t => t.estado === 'atendido').length;
                    const estadisticasActuales = `${llamandoCount}-${atendidoCount}`;
                    if (window.lastEstadisticas !== estadisticasActuales) {
                        console.log(`📊 Turnos: ${llamandoCount} llamando, ${atendidoCount} atendidos`);
                        window.lastEstadisticas = estadisticasActuales;
                    }
                })
                .catch(error => {
                    console.error('❌ Error de sincronización:', error);
                });
        }

        // Renderizar los turnos en el contenedor (COPIADO EXACTAMENTE DE LA VISTA TV)
        function renderTurnos(turnosList) {
            console.log('📱 Renderizando turnos en móvil:', turnosList);
            const container = document.getElementById('patient-queue');

            if (!container) {
                console.error('❌ Contenedor patient-queue no encontrado');
                return;
            }

            // Conservar el contenedor pero limpiar su contenido
            container.innerHTML = '';

            // Verificar si hay turnos para mostrar
            if (!turnosList || turnosList.length === 0) {
                console.log('📱 No hay turnos para mostrar, mostrando placeholders');
                // Mostrar 4 placeholders para móvil (optimizado para pantallas pequeñas)
                for (let i = 0; i < 4; i++) {
                    const placeholder = document.createElement('div');
                    placeholder.className = 'gradient-hospital text-white p-4 mobile-shadow rounded-lg opacity-50 min-h-[70px] flex items-center';
                    placeholder.innerHTML = `
                        <div class="grid grid-cols-2 gap-4 items-center w-full">
                            <div class="text-left pl-2">
                                <div class="text-lg font-bold text-gray-300">----</div>
                            </div>
                            <div class="text-right pr-4">
                                <div class="text-sm font-semibold text-gray-300">CAJA -</div>
                            </div>
                        </div>
                    `;
                    container.appendChild(placeholder);
                }
                console.log('📱 Renderizado completado. Elementos en contenedor:', container.children.length);
                return;
            }

            // Limitar a los últimos 4 turnos para vista móvil (optimizado para celulares)
            const turnosParaMostrar = turnosList.slice(-4);

            // Mostrar turnos existentes
            for (let i = 0; i < turnosParaMostrar.length; i++) {
                const turno = turnosParaMostrar[i];

                // Crear elemento del turno
                const turnoElement = document.createElement('div');

                // Determinar estilo según el estado
                const esAtendido = turno.estado === 'atendido';

                // Diseño optimizado para móvil
                let clases = 'gradient-hospital text-white p-4 mobile-shadow rounded-lg min-h-[70px] flex items-center';

                turnoElement.className = clases;

                turnoElement.innerHTML = `
                    <div class="relative w-full">
                        <div class="grid grid-cols-2 gap-4 items-center w-full">
                            <div class="text-left pl-2">
                                <div class="text-lg font-bold">${turno.codigo_completo}</div>
                            </div>
                            <div class="text-right pr-4 relative">
                                ${esAtendido ? '<div class="absolute right-0" style="top: -24px;"><span class="bg-green-500 text-white px-1.5 py-0.5 rounded-full text-xs font-bold shadow-lg">✓</span></div>' : ''}
                                <div class="text-sm font-semibold">CAJA ${turno.numero_caja || ''}</div>
                            </div>
                        </div>
                    </div>
                `;

                container.appendChild(turnoElement);
            }

            // Si hay menos de 4 turnos, llenar con placeholders
            while (container.children.length < 4) {
                const placeholder = document.createElement('div');
                placeholder.className = 'gradient-hospital text-white p-4 mobile-shadow rounded-lg opacity-50 min-h-[70px] flex items-center';
                placeholder.innerHTML = `
                    <div class="grid grid-cols-2 gap-4 items-center w-full">
                        <div class="text-left pl-2">
                            <div class="text-lg font-bold text-gray-300">----</div>
                        </div>
                        <div class="text-right pr-4">
                            <div class="text-sm font-semibold text-gray-300">CAJA -</div>
                        </div>
                    </div>
                `;
                container.appendChild(placeholder);
            }

            console.log('📱 Renderizado completado. Elementos en contenedor:', container.children.length);
        }

        // Función para actualizar información del turno personal
        function actualizarTurnoPersonal() {
            if (!turnoPersonalId) return;

            fetch(`/api/turno-status/${turnoPersonalId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.turno) {
                        const turno = data.turno;
                        const infoContainer = document.getElementById('turno-personal-container');

                        if (infoContainer) {
                            // Actualizar el estado visual según el estado del turno
                            let estadoHtml = '';
                            let containerClass = 'gradient-hospital';

                            if (turno.estado === 'atendido') {
                                containerClass = 'gradient-hospital';
                                estadoHtml = `
                                    <div class="bg-green-500 bg-opacity-80 rounded-lg p-3 shadow-lg border border-green-300">
                                        <div class="text-sm font-medium text-white drop-shadow-md">✅ Su turno ya fue atendido</div>
                                    </div>
                                `;
                            } else if (turno.estado === 'llamado') {
                                containerClass = 'gradient-hospital';
                                estadoHtml = `
                                    <div class="bg-yellow-500 bg-opacity-80 rounded-lg p-3 animate-pulse shadow-lg border border-yellow-300">
                                        <div class="text-sm font-medium text-white drop-shadow-md">🔔 Su turno está siendo llamado</div>
                                        <div class="text-xs mt-1 text-white drop-shadow-md">Diríjase a la caja ${turno.numero_caja || 'indicada'}</div>
                                    </div>
                                `;
                            } else {
                                // Pendiente o aplazado
                                const turnosAdelante = data.turnos_adelante || 0;
                                const tiempoEstimado = turnosAdelante * 3;

                                estadoHtml = `
                                    <div class="bg-blue-600 bg-opacity-90 rounded-lg p-3 shadow-lg border border-blue-500">
                                        ${turnosAdelante > 0 ?
                                            `<div class="text-sm font-medium text-white drop-shadow-md">Faltan ${turnosAdelante} turnos</div>
                                             <div class="text-xs mt-1 text-white drop-shadow-md">Tiempo estimado: ${tiempoEstimado} minutos</div>` :
                                            `<div class="text-sm font-medium text-white drop-shadow-md">Su turno será llamado próximamente</div>`
                                        }
                                    </div>
                                `;
                            }

                            // Actualizar el contenedor
                            infoContainer.className = `${containerClass} text-white p-4 shadow-lg`;

                            // Actualizar la información del turno (código y servicio)
                            const codigoDiv = infoContainer.querySelector('.text-2xl.font-bold');
                            const servicioDiv = infoContainer.querySelector('.text-sm.font-medium');

                            if (codigoDiv) {
                                codigoDiv.textContent = turno.codigo_completo;
                            }
                            if (servicioDiv && turno.servicio) {
                                servicioDiv.textContent = turno.servicio;
                            }

                            // Actualizar el contenido del estado
                            const estadoContainer = document.getElementById('turno-estado-container');
                            if (estadoContainer) {
                                estadoContainer.innerHTML = estadoHtml;
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Error actualizando turno personal:', error);
                });
        }

        // Función para sincronización inicial
        function sincronizacionInicial() {
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

                    console.log('✅ Sincronización inicial completada - solo los turnos nuevos sonarán a partir de ahora');
                })
                .catch(error => {
                    console.error('Error en sincronización inicial:', error);
                    // Fallback: hacer sincronización normal
                    updateQueue();
                });
        }

        // Escuchar eventos para turnos en tiempo real (COPIADO EXACTAMENTE DE LA VISTA TV)
        function setupRealTimeListeners() {
            // Sincronización inicial
            sincronizacionInicial();

            // Aumentamos la frecuencia de polling para actualizaciones en tiempo real
            setInterval(updateQueue, 1000); // Actualizar cada 1 segundo para mejor tiempo real
        }

        // Función para habilitar audio después de interacción del usuario
        function habilitarAudio() {
            try {
                // Crear contexto de audio si no existe
                if (!window.audioContext) {
                    window.audioContext = new (window.AudioContext || window.webkitAudioContext)();
                }

                // Reanudar contexto si está suspendido
                if (window.audioContext.state === 'suspended') {
                    window.audioContext.resume().then(() => {
                        console.log('✅ Audio habilitado correctamente');
                    }).catch(error => {
                        console.warn('⚠️ Error al habilitar audio:', error);
                    });
                } else {
                    console.log('✅ Audio ya habilitado');
                }
            } catch (error) {
                console.warn('⚠️ Error al crear contexto de audio:', error);
            }
        }

        // Función para mantener la página activa (evitar que se suspenda)
        function mantenerPaginaActiva() {
            try {
                // 1. Crear AudioContext para mantener el audio activo
                if (!audioContext) {
                    audioContext = new (window.AudioContext || window.webkitAudioContext)();
                }

                // Crear un oscilador silencioso que mantenga el contexto activo
                if (audioContext.state === 'suspended') {
                    audioContext.resume();
                }

                // Intentar obtener wake lock para mantener pantalla activa
                if ('wakeLock' in navigator) {
                    navigator.wakeLock.request('screen').then(wakeLock => {
                        console.log('🔒 Wake Lock activado - pantalla se mantendrá activa');

                        // Manejar cuando se libera el wake lock
                        wakeLock.addEventListener('release', () => {
                            console.log('🔓 Wake Lock liberado');
                        });
                    }).catch(err => {
                        console.warn('⚠️ No se pudo activar Wake Lock:', err);
                    });
                }

                // Crear contexto de audio para evitar que se suspenda
                if (!window.audioContext) {
                    try {
                        window.audioContext = new (window.AudioContext || window.webkitAudioContext)();
                        console.log('🎵 Contexto de audio creado para mantener página activa');
                    } catch (e) {
                        console.warn('⚠️ No se pudo crear contexto de audio:', e);
                    }
                }
            } catch (error) {
                if (!window.audioContextWarningShown) {
                    console.warn('⚠️ Error en mantenerPaginaActiva:', error);
                    window.audioContextWarningShown = true;
                }
            }
        }

        // Función para habilitar audio con interacción del usuario (COPIADO EXACTAMENTE DE LA VISTA TV)
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

        // Función para detectar si necesitamos interacción del usuario (COPIADO EXACTAMENTE DE LA VISTA TV)
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

        // Inicializar cuando la página carga
        document.addEventListener('DOMContentLoaded', function() {
            // Verificar si necesitamos interacción del usuario para el audio
            verificarNecesidadInteraccion();

            // Mantener página activa
            mantenerPaginaActiva();

            // Hacer una primera verificación de configuración inmediatamente
            updateTvConfig();

            // Cargar multimedia inmediatamente
            loadMultimedia();

            // Inicializar sistema de turnos
            setupRealTimeListeners();

            // Configurar actualizaciones periódicas
            setInterval(updateTvConfig, 5000); // Cada 5 segundos
            setInterval(loadMultimedia, 10000); // Cada 10 segundos

            // Actualizar turno personal si existe
            if (turnoPersonalId) {
                setInterval(actualizarTurnoPersonal, 3000); // Cada 3 segundos
                // Actualización inicial
                setTimeout(actualizarTurnoPersonal, 1000);
            }

            // Asegurar que el ticker esté funcionando si está habilitado
            setTimeout(() => {
                if (currentConfig.ticker_enabled) {
                    const tickerContent = document.querySelector('.ticker-content');
                    if (tickerContent && !tickerContent.style.animation) {
                        restartTicker(currentConfig.ticker_speed);
                    }
                }
            }, 100);
        });

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

        // Prevenir interacciones no deseadas en móvil
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
        });

        // Prevenir zoom con doble tap
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function (event) {
            const now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);

        // Prevenir selección de texto
        document.addEventListener('selectstart', function(e) {
            e.preventDefault();
        });

        // Limpiar recursos cuando la página se cierre
        window.addEventListener('beforeunload', function() {
            // Cerrar AudioContext
            if (audioContext) {
                audioContext.close();
            }
        });

        // Optimización para dispositivos móviles
        if ('serviceWorker' in navigator) {
            // Registrar service worker para mejor rendimiento (opcional)
            // navigator.serviceWorker.register('/sw.js');
        }
    </script>
</body>
</html>
