<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
    <title>{{ config('app.name', 'Turnero HUV') }} - Vista Móvil</title>
    
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

        @keyframes pulse-number {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        @keyframes slide-in {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-pulse-number {
            animation: pulse-number 2s ease-in-out infinite;
        }
        
        .animate-slide-in {
            animation: slide-in 0.8s ease-out;
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

        /* Optimizaciones para móvil */
        .mobile-container {
            min-height: 100vh;
            min-height: 100dvh; /* Para navegadores modernos */
            display: flex;
            flex-direction: column;
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
    </style>
</head>
<body class="w-full bg-white overflow-x-hidden">
    <div class="mobile-container bg-white">
        <!-- Header Section - Compacto para móvil -->
        <div class="bg-hospital-blue-light p-3">
            <div class="flex items-center justify-between">
                <!-- Logo y Hospital Info -->
                <div class="flex items-center space-x-2">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo HUV" class="h-8 w-auto">
                    <div>
                        <h2 class="text-sm font-bold text-hospital-blue leading-tight">HOSPITAL UNIVERSITARIO</h2>
                        <h3 class="text-sm font-bold text-hospital-blue leading-tight">DEL VALLE</h3>
                    </div>
                </div>
                
                <!-- Hora -->
                <div class="text-right">
                    <p class="text-lg text-hospital-blue font-semibold" id="current-time">{{ \Carbon\Carbon::now('America/Bogota')->format('M d - H:i') }}</p>
                    <p class="text-xs text-hospital-blue">Turnero HUV</p>
                </div>
            </div>
        </div>

        <!-- Main Content - Stack vertical para móvil -->
        <div class="flex flex-col flex-1">
            <!-- Multimedia Content - Ahora arriba -->
            <div class="bg-hospital-blue-light p-3 flex-1">
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

            <!-- Patient Queue - Justo después de los títulos -->
            <div class="bg-hospital-blue-light p-3">
                <div class="grid grid-cols-2 gap-2" id="patient-queue">
                    <div class="gradient-hospital text-white p-3 mobile-shadow rounded-lg animate-slide-in">
                        <div class="text-center">
                            <div class="text-2xl font-bold animate-pulse-number">U001</div>
                            <div class="text-xs font-semibold">CAJA 1</div>
                        </div>
                    </div>

                    <div class="gradient-hospital text-white p-3 mobile-shadow rounded-lg animate-slide-in" style="animation-delay: 0.1s;">
                        <div class="text-center">
                            <div class="text-2xl font-bold">U002</div>
                            <div class="text-xs font-semibold">CAJA 2</div>
                        </div>
                    </div>

                    <div class="gradient-hospital text-white p-3 mobile-shadow rounded-lg animate-slide-in" style="animation-delay: 0.2s;">
                        <div class="text-center">
                            <div class="text-2xl font-bold">U003</div>
                            <div class="text-xs font-semibold">CAJA 3</div>
                        </div>
                    </div>

                    <div class="gradient-hospital text-white p-3 mobile-shadow rounded-lg animate-slide-in" style="animation-delay: 0.3s;">
                        <div class="text-center">
                            <div class="text-2xl font-bold">U004</div>
                            <div class="text-xs font-semibold">CAJA 4</div>
                        </div>
                    </div>

                    <div class="gradient-hospital text-white p-3 mobile-shadow rounded-lg animate-slide-in" style="animation-delay: 0.4s;">
                        <div class="text-center">
                            <div class="text-2xl font-bold">U005</div>
                            <div class="text-xs font-semibold">CAJA 5</div>
                        </div>
                    </div>

                    <div class="gradient-hospital text-white p-3 mobile-shadow rounded-lg animate-slide-in" style="animation-delay: 0.5s;">
                        <div class="text-center">
                            <div class="text-2xl font-bold">U006</div>
                            <div class="text-xs font-semibold">CAJA 6</div>
                        </div>
                    </div>
                </div>
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

        <!-- Footer - Mínimo -->
        <div class="bg-white border-t border-hospital-blue px-2 py-1 text-center relative">
            <p class="text-xs text-gray-500">
                <span class="font-semibold text-hospital-blue">Turnero HUV</span>
            </p>
            <!-- Indicador de actualización -->
            <div id="updateIndicator" class="absolute top-1 right-1 w-1.5 h-1.5 bg-green-500 rounded-full opacity-0 transition-opacity duration-300"></div>
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

        // Inicializar cuando la página carga
        document.addEventListener('DOMContentLoaded', function() {
            // Hacer una primera verificación de configuración inmediatamente
            updateTvConfig();

            // Cargar multimedia inmediatamente
            loadMultimedia();

            // Configurar actualizaciones periódicas
            setInterval(updateTvConfig, 5000); // Cada 5 segundos
            setInterval(loadMultimedia, 10000); // Cada 10 segundos

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

        // Optimización para dispositivos móviles
        if ('serviceWorker' in navigator) {
            // Registrar service worker para mejor rendimiento (opcional)
            // navigator.serviceWorker.register('/sw.js');
        }
    </script>
</body>
</html>
