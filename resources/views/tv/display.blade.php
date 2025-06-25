<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Turnero HUV') }} - Visualizador TV</title>
    
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
    </style>
</head>
<body class="w-full h-screen bg-white overflow-hidden">
    <div class="w-full h-full bg-white">
        <!-- Header Section -->
        <div class="grid grid-cols-6 h-32">
            <!-- Left Header - UBA y Hora -->
            <div class="bg-hospital-blue-light p-4 flex flex-col justify-center col-span-2">
                <h1 class="text-5xl font-bold text-hospital-blue leading-tight mb-2">UBA</h1>
                <!-- Hora de Colombia (UTC-5) -->
                <p class="text-2xl text-hospital-blue font-semibold" id="current-time">{{ \Carbon\Carbon::now('America/Bogota')->format('M d - H:i') }}</p>
            </div>

            <!-- Center Header - Hospital Info con Logo -->
            <div class="bg-hospital-blue-light p-4 pl-48 pr-2 flex items-center space-x-3 justify-end col-span-2">
                <!-- Logo del Hospital -->
                <div class="flex-shrink-0">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo Hospital Universitario del Valle" class="h-20 w-auto">
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
        <div class="grid grid-cols-6 h-[calc(100%-12rem)]">
            <!-- Left Side - Multimedia Content -->
            <div class="bg-hospital-blue-light p-6 flex flex-col col-span-4">
                <!-- Espacio para videos/fotos - Ahora ocupa todo el espacio disponible -->
                <div class="flex-1 bg-white rounded-lg enhanced-border enhanced-shadow flex items-center justify-center relative overflow-hidden" id="multimedia-container">
                    <!-- Contenido multimedia dinámico -->
                    <div id="multimedia-content" class="w-full h-full flex items-center justify-center">
                        <!-- Placeholder content con mejor diseño -->
                        <div id="multimedia-placeholder" class="text-center text-gray-400 z-10">
                            <div class="text-8xl mb-4 opacity-50">🏥</div>
                            <p class="text-2xl font-semibold text-hospital-blue mb-2">Contenido Multimedia</p>
                            <p class="text-lg text-gray-500">Videos e imágenes del hospital</p>
                        </div>

                        <!-- Decorative background pattern -->
                        <div class="absolute inset-0 opacity-5">
                            <div class="w-full h-full" style="background-image: repeating-linear-gradient(45deg, #064b9e 0px, #064b9e 10px, transparent 10px, transparent 20px);"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side - Patient Queue -->
            <div class="bg-hospital-blue-light p-8 col-span-2">
                <!-- Patient Numbers - Alineados con TURNO y MÓDULO del header -->
                <div class="space-y-3" id="patient-queue">
                    <div class="gradient-hospital text-white p-4 enhanced-shadow rounded-lg animate-slide-in">
                        <div class="grid grid-cols-2 gap-4 items-center">
                            <div class="text-center">
                                <div class="text-6xl font-bold animate-pulse-number">U001</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-semibold">CAJA 1</div>
                            </div>
                        </div>
                    </div>

                    <div class="gradient-hospital text-white p-4 enhanced-shadow rounded-lg animate-slide-in" style="animation-delay: 0.2s;">
                        <div class="grid grid-cols-2 gap-4 items-center">
                            <div class="text-center">
                                <div class="text-6xl font-bold">U002</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-semibold">CAJA 2</div>
                            </div>
                        </div>
                    </div>

                    <div class="gradient-hospital text-white p-4 enhanced-shadow rounded-lg animate-slide-in" style="animation-delay: 0.4s;">
                        <div class="grid grid-cols-2 gap-4 items-center">
                            <div class="text-center">
                                <div class="text-6xl font-bold">U003</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-semibold">CAJA 3</div>
                            </div>
                        </div>
                    </div>

                    <div class="gradient-hospital text-white p-4 enhanced-shadow rounded-lg animate-slide-in" style="animation-delay: 0.6s;">
                        <div class="grid grid-cols-2 gap-4 items-center">
                            <div class="text-center">
                                <div class="text-6xl font-bold">U004</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-semibold">CAJA 4</div>
                            </div>
                        </div>
                    </div>

                    <div class="gradient-hospital text-white p-4 enhanced-shadow rounded-lg animate-slide-in" style="animation-delay: 0.8s;">
                        <div class="grid grid-cols-2 gap-4 items-center">
                            <div class="text-center">
                                <div class="text-6xl font-bold">U005</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-semibold">CAJA 5</div>
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

        <!-- Mensaje Ticker -->
        <div class="ticker-container h-16 flex items-center border-t-2 border-hospital-blue" style="display: {{ $tvConfig->ticker_enabled ? 'flex' : 'none' }};">
            <div class="ticker-content">
                <span class="ticker-text">
                    {{ $tvConfig->ticker_message }}
                </span>
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-white border-t-2 border-hospital-blue p-4 text-center relative">
            <p class="text-gray-600">
                <span class="font-bold text-hospital-blue">Turnero HUV - Innovación y desarrollo</span>
            </p>
            <!-- Indicador de actualización (oculto por defecto) -->
            <div id="updateIndicator" class="absolute top-2 right-2 w-2 h-2 bg-green-500 rounded-full opacity-0 transition-opacity duration-300"></div>
        </div>
    </div>

    <script>
        // Variables globales
        let turnos = []; // Historial de turnos
        let turnosVistos = new Set(); // Conjunto para rastrear turnos ya mostrados
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

                        console.log('Configuración del TV actualizada');
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

        // Actualizar la cola de turnos desde el servidor
        function updateQueue() {
            fetch('/api/turnos-llamados')
                .then(response => response.json())
                .then(data => {
                    // Verificar si hay turnos nuevos
                    const newTurnos = data.turnos || [];
                    
                    // Verificar si hay nuevos turnos que no hayamos visto antes
                    let hayNuevosTurnos = false;
                    
                    // Si tenemos nuevos turnos del API
                    if (newTurnos.length > 0) {
                        console.log('Turnos recibidos:', newTurnos.length);
                        
                        // Buscar el turno más reciente que no hayamos visto
                        let turnoNuevo = null;
                        
                        for (const turno of newTurnos) {
                            // Verificar si este turno es realmente nuevo
                            if (!turnosVistos.has(turno.id)) {
                                turnoNuevo = turno;
                                turnosVistos.add(turno.id); // Marcarlo como visto
                                hayNuevosTurnos = true;
                                
                                // Añadirlo al inicio de nuestra lista de turnos
                                turnos.unshift(turno);
                                
                                // Conservar solo los últimos 5 para mostrar
                                if (turnos.length > 5) {
                                    turnos = turnos.slice(0, 5);
                                }
                                
                                break; // Solo procesamos el más reciente
                            }
                        }
                        
                        // Actualizar la interfaz con todos los turnos
                        renderTurnos(turnos);
                        
                        // Reproducir sonido solo si hay un turno realmente nuevo
                        if (turnoNuevo) {
                            console.log('Nuevo turno detectado! Reproduciendo sonido...', turnoNuevo.codigo_completo);
                            playTurnoSound(turnoNuevo);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error al actualizar cola de turnos:', error);
                });
        }

        // Renderizar los turnos en el contenedor
        function renderTurnos(turnosList) {
            const container = document.getElementById('patient-queue');
            
            // Conservar el contenedor pero limpiar su contenido
            container.innerHTML = '';
            
            // No hay turnos, mostramos placeholders
            if (turnosList.length === 0) {
                for (let i = 0; i < 5; i++) {
                    const placeholderElement = document.createElement('div');
                    placeholderElement.className = 'gradient-hospital text-white p-4 enhanced-shadow rounded-lg opacity-50';
                    
                    placeholderElement.innerHTML = `
                        <div class="grid grid-cols-2 gap-4 items-center">
                            <div class="text-center">
                                <div class="text-6xl font-bold">----</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-semibold">CAJA -</div>
                            </div>
                        </div>
                    `;
                    
                    container.appendChild(placeholderElement);
                }
                return;
            }

            // Mostrar turnos existentes
            for (let i = 0; i < turnosList.length; i++) {
                const turno = turnosList[i];
                
                // Crear elemento del turno
                const turnoElement = document.createElement('div');
                
                // Solo el primer elemento (más reciente) de la primera renderización tendrá una clase especial
                // Revisamos con un atributo data para saber si este turno específico ya se animó
                const yaAnimado = sessionStorage.getItem('turno_animado_' + turno.id);
                
                if (i === 0 && !yaAnimado) {
                    turnoElement.className = 'gradient-hospital text-white p-4 enhanced-shadow rounded-lg new-turn';
                    // Marcar este turno como ya animado para que no se repita
                    sessionStorage.setItem('turno_animado_' + turno.id, 'true');
                } else {
                    turnoElement.className = 'gradient-hospital text-white p-4 enhanced-shadow rounded-lg';
                }
                
                turnoElement.innerHTML = `
                    <div class="grid grid-cols-2 gap-4 items-center">
                        <div class="text-center">
                            <div class="text-6xl font-bold">${turno.codigo_completo}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-semibold">CAJA ${turno.numero_caja || ''}</div>
                        </div>
                    </div>
                `;
                
                container.appendChild(turnoElement);
            }
            
            // Si hay menos de 5 turnos, rellenar con placeholders
            for (let i = turnosList.length; i < 5; i++) {
                const placeholderElement = document.createElement('div');
                placeholderElement.className = 'gradient-hospital text-white p-4 enhanced-shadow rounded-lg opacity-50';
                
                placeholderElement.innerHTML = `
                    <div class="grid grid-cols-2 gap-4 items-center">
                        <div class="text-center">
                            <div class="text-6xl font-bold">----</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-semibold">CAJA -</div>
                        </div>
                    </div>
                `;
                
                container.appendChild(placeholderElement);
            }
        }

        // Función para reproducir sonido y sintetizar voz para un turno
        function playTurnoSound(turno) {
            // Mostrar indicador visual de nuevo turno (opcional)
            document.getElementById('updateIndicator').style.opacity = '1';
            setTimeout(() => {
                document.getElementById('updateIndicator').style.opacity = '0';
            }, 3000);
            
            try {
                // Crear un contexto de audio para generar el sonido de alerta
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                
                // Crear oscilador para generar un tono de alerta suave
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                // Configurar un tono agradable (LA4 - 440Hz)
                oscillator.type = 'sine';
                oscillator.frequency.setValueAtTime(440, audioContext.currentTime);
                
                // Configurar la envolvente del sonido para que sea suave
                gainNode.gain.setValueAtTime(0, audioContext.currentTime);
                gainNode.gain.linearRampToValueAtTime(0.3, audioContext.currentTime + 0.1);
                gainNode.gain.linearRampToValueAtTime(0, audioContext.currentTime + 0.8);
                
                // Conectar nodos
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                // Reproducir el tono
                oscillator.start();
                oscillator.stop(audioContext.currentTime + 0.8);
                
                // Cuando termine el sonido, iniciar la síntesis de voz
                setTimeout(() => {
                    // Mensaje para el turno
                    const mensaje = `Turno ${turno.codigo_completo}, por favor diríjase a la caja ${turno.numero_caja}`;
                    
                    // Crear instancia de síntesis de voz
                    const synth = window.speechSynthesis;
                    
                    // Cancelar cualquier síntesis anterior
                    synth.cancel();
                    
                    const utterance = new SpeechSynthesisUtterance(mensaje);
                    
                    // Configurar voz femenina en español
                    utterance.lang = 'es-ES';
                    utterance.rate = 0.9; // Velocidad ligeramente más lenta para mayor claridad
                    utterance.pitch = 1.0; // Tono normal
                    utterance.volume = 1.0; // Volumen máximo
                    
                    // Seleccionar una voz femenina
                    let voices = synth.getVoices();
                    
                    // Función para seleccionar la mejor voz femenina disponible
                    function selectFemaleVoice() {
                        // Obtener voces actualizadas
                        voices = synth.getVoices();
                        
                        // Prioridad de voces:
                        // 1. Microsoft Helena - voz femenina española de alta calidad
                        // 2. Google español femenina
                        // 3. Cualquier voz femenina en español
                        // 4. Cualquier voz en español
                        
                        // Buscar voces específicas de alta calidad
                        let selectedVoice = voices.find(voice => 
                            voice.name.includes('Helena') && voice.name.includes('Spanish'));
                        
                        if (!selectedVoice) {
                            selectedVoice = voices.find(voice => 
                                voice.name.includes('Google') && 
                                voice.name.toLowerCase().includes('español') && 
                                voice.name.toLowerCase().includes('female'));
                        }
                        
                        if (!selectedVoice) {
                            // Buscar cualquier voz femenina en español
                            selectedVoice = voices.find(voice => 
                                voice.lang.includes('es') && 
                                (voice.name.toLowerCase().includes('female') || 
                                voice.name.toLowerCase().includes('mujer') || 
                                voice.name.toLowerCase().includes('fem')));
                        }
                        
                        if (!selectedVoice) {
                            // Como último recurso, cualquier voz en español
                            selectedVoice = voices.find(voice => voice.lang.includes('es'));
                        }
                        
                        console.log('Voz seleccionada:', selectedVoice ? selectedVoice.name : 'ninguna');
                        return selectedVoice;
                    }
                    
                    // Si las voces ya están cargadas
                    if (voices.length > 0) {
                        utterance.voice = selectFemaleVoice();
                        synth.speak(utterance);
                    } else {
                        // Si las voces no están cargadas, esperar el evento voiceschanged
                        speechSynthesis.onvoiceschanged = function() {
                            utterance.voice = selectFemaleVoice();
                            synth.speak(utterance);
                        };
                    }
                    
                    // Registrar en consola para depuración
                    console.log('Reproduciendo mensaje de voz:', mensaje);
                }, 900); // Esperar 900ms para que termine el tono antes de iniciar la voz
                
            } catch (error) {
                console.error('Error al reproducir sonido:', error);
                
                // Plan B: intentar reproducción directa de voz sin efectos de sonido
                const synth = window.speechSynthesis;
                const mensaje = `Turno ${turno.codigo_completo}, por favor diríjase a la caja ${turno.numero_caja}`;
                const utterance = new SpeechSynthesisUtterance(mensaje);
                utterance.lang = 'es-ES';
                utterance.volume = 1.0;
                synth.speak(utterance);
            }
        }

        // Escuchar eventos de Pusher para turnos en tiempo real
        function setupRealTimeListeners() {
            // En modo polling, no intentamos conectar a WebSockets
            console.log('Modo polling activado para actualizaciones de turnos');
            
            // Aumentamos la frecuencia de polling para compensar la falta de WebSockets
            setInterval(updateQueue, 2000); // Actualizar cada 2 segundos en lugar de 5
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

        // Inicializar cuando la página carga
        document.addEventListener('DOMContentLoaded', function() {
            // Actualizar la hora inmediatamente y cada minuto
            updateTime();
            setInterval(updateTime, 60000);
            
            initializeTicker();

            // Inicializar funcionalidad en tiempo real
            setupRealTimeListeners();

            // Cargar datos iniciales inmediatamente
            updateTvConfig();
            loadMultimedia();
            updateQueue();

            // Establecer intervalos para actualizaciones periódicas adicionales
            setInterval(updateTvConfig, 5000);
            setInterval(loadMultimedia, 5000);
            // La actualización de turnos ahora se maneja en setupRealTimeListeners con intervalo más frecuente
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
