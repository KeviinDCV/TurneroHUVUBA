<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard Asesor - Hospital Universitario del Valle</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <style>
        .animate-fade-in {
            animation: fadeIn 0.8s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Estilos mínimos para la funcionalidad desplegable */
        .servicio-principal {
            cursor: pointer;
        }

        .subservicio-row {
            display: none;
        }

        .subservicio-row .servicio-nombre {
            padding-left: 25px;
        }

        .chevron-icon {
            transition: transform 0.3s;
        }

        .rotate-chevron {
            transform: rotate(90deg);
        }

        /* Estilos para botones deshabilitados */
        button:disabled {
            cursor: not-allowed !important;
            opacity: 0.5 !important;
            pointer-events: none !important;
        }

        button:disabled:hover {
            opacity: 0.5 !important;
        }

        /* Estilo específico para botones bloqueados por turno en proceso */
        .btn-bloqueado {
            background-color: #6b7280 !important;
            cursor: not-allowed !important;
            opacity: 0.6 !important;
            pointer-events: none !important;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <header class="shadow-sm" style="background-color: #064b9e;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <!-- Logo y título -->
                <div class="flex items-center space-x-4">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo HUV" class="h-10 w-auto">
                    <div>
                        <h1 class="text-xl font-bold text-white">Turnero HUV</h1>
                        <p class="text-blue-200 text-sm">Panel de Asesor</p>
                    </div>
                </div>

                <!-- Información del usuario y caja -->
                <div class="flex items-center space-x-6">
                    <!-- Información de la caja -->
                    <div class="text-right">
                        <p class="text-white font-medium">{{ $caja->nombre }}</p>
                        <p class="text-blue-200 text-sm">Caja {{ $caja->numero_caja }}</p>
                    </div>

                    <!-- Información del usuario -->
                    <div class="flex items-center space-x-3">
                        <div class="text-right">
                            <p class="text-white font-medium">{{ $user->nombre_completo }}</p>
                            <p class="text-blue-200 text-sm">{{ $user->rol }}</p>
                        </div>
                        <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center border border-white/30">
                            <span class="text-white font-medium">{{ substr($user->nombre_completo, 0, 1) }}</span>
                        </div>
                    </div>

                    <!-- Menú de opciones -->
                    <div class="flex items-center space-x-2">
                        <!-- Botón para repetir audio del último turno -->
                        <button id="repetir-audio-btn"
                                class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors duration-200 text-sm flex items-center gap-2"
                                title="Repetir audio del último turno llamado">
                            <span>🔊</span>
                            <span>Repetir Audio</span>
                        </button>

                        <a href="{{ route('asesor.cambiar-caja') }}"
                           class="px-4 py-2 bg-white/20 text-white rounded-lg hover:bg-white/30 transition-colors duration-200 text-sm">
                            Cambiar Caja
                        </a>
                        <form action="{{ route('logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit"
                                    class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors duration-200 text-sm">
                                Cerrar Sesión
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="animate-fade-in">
            <!-- Interfaz de llamado de turnos -->
            <div class="min-h-screen flex bg-gray-100">
                <!-- Panel Izquierdo -->
                <div class="w-1/2 bg-white p-6 shadow-lg">
                    <div class="mb-6">
                        <h2 class="text-lg font-medium text-gray-700 mb-4">Llamar Turno Específico</h2>

                        <div class="flex items-center gap-2 mb-4">
                            <input
                                type="text"
                                id="codigo-turno"
                                placeholder="CÓDIGO"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-center focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                maxlength="3"
                                style="text-transform: uppercase;"
                            />
                            <span class="text-gray-500 font-bold text-xl">—</span>
                            <input
                                type="number"
                                id="numero-turno"
                                placeholder="NÚMERO"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-center focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                min="1"
                            />
                        </div>

                        <div class="flex gap-3 mb-6">
                            <button
                                id="btn-llamar-especifico"
                                class="px-6 py-2 text-white rounded-md transition-colors duration-200 hover:opacity-90"
                                style="background-color: #064b9e;"
                            >
                                Llamar
                            </button>
                            <button
                                id="btn-calificar"
                                class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-md transition-colors duration-200"
                            >
                                Calificar
                            </button>
                        </div>
                    </div>

                    <!-- Tabla de servicios -->
                    <div class="border border-gray-300 rounded overflow-hidden">
                        <div class="text-white grid grid-cols-4 text-sm font-medium" style="background-color: #064b9e;">
                            <div class="p-3 border-r border-blue-400">SERVICIO</div>
                            <div class="p-3 border-r border-blue-400 text-center">CANTIDAD</div>
                            <div class="p-3 border-r border-blue-400 text-center">APLAZADOS</div>
                            <div class="p-3 text-center">OPCIÓN</div>
                        </div>

                        <div class="servicios-container">
                            @forelse($serviciosEstructurados as $servicio)
                                <!-- Fila del servicio principal -->
                                <div class="grid grid-cols-4 text-sm {{ $loop->even ? 'bg-gray-50' : 'bg-white' }} servicio-principal"
                                     data-servicio-id="{{ $servicio['id'] }}"
                                     data-tiene-hijos="{{ $servicio['tiene_hijos'] ? 'true' : 'false' }}">
                                    <div class="p-3 border-r border-gray-200 font-medium servicio-nombre flex items-center">
                                        @if($servicio['tiene_hijos'])
                                            <svg class="w-4 h-4 mr-1 chevron-icon text-blue-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        @endif
                                        {{ $servicio['nombre'] }}
                                    </div>
                                    <div class="p-3 border-r border-gray-200 text-center">{{ $servicio['pendientes'] }}</div>
                                    <div class="p-3 border-r border-gray-200 text-center">{{ $servicio['aplazados'] }}</div>
                                    <div class="p-3 text-center">
                                        <button
                                            class="btn-llamar-siguiente text-xs px-3 py-1 rounded text-white transition-colors duration-200"
                                            data-servicio-id="{{ $servicio['id'] }}"
                                            style="background-color: #064b9e;"
                                            {{ $servicio['total'] == 0 ? 'disabled' : '' }}
                                        >
                                            {{ $servicio['total'] > 0 ? 'DISPONIBLE' : 'SIN TURNOS' }}
                                        </button>
                                    </div>
                                </div>

                                <!-- Subservicios desplegables -->
                                @if($servicio['tiene_hijos'] && count($servicio['subservicios']) > 0)
                                    @foreach($servicio['subservicios'] as $subservicio)
                                        <div class="grid grid-cols-4 text-sm {{ $loop->even ? 'bg-gray-50' : 'bg-white' }} subservicio-row" data-parent-id="{{ $servicio['id'] }}">
                                            <div class="p-3 border-r border-gray-200 font-medium servicio-nombre">
                                                {{ $subservicio['nombre'] }}
                                            </div>
                                            <div class="p-3 border-r border-gray-200 text-center">{{ $subservicio['pendientes'] }}</div>
                                            <div class="p-3 border-r border-gray-200 text-center">{{ $subservicio['aplazados'] }}</div>
                                            <div class="p-3 text-center">
                                                <button
                                                    class="btn-llamar-siguiente text-xs px-3 py-1 rounded text-white transition-colors duration-200"
                                                    data-servicio-id="{{ $subservicio['id'] }}"
                                                    style="background-color: #064b9e;"
                                                    {{ $subservicio['total'] == 0 ? 'disabled' : '' }}
                                                >
                                                    {{ $subservicio['total'] > 0 ? 'DISPONIBLE' : 'SIN TURNOS' }}
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            @empty
                                <div class="p-6 text-center text-gray-500">
                                    No tienes servicios asignados
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Panel Derecho -->
                <div class="w-1/2 text-white relative" style="background: linear-gradient(135deg, #064b9e 0%, #0a5bb8 100%);">
                    <!-- Elementos decorativos -->
                    <div class="absolute top-0 right-0 w-80 h-80 bg-white/10 rounded-full -translate-y-40 translate-x-40"></div>
                    <div class="absolute bottom-0 left-0 w-80 h-80 bg-white/10 rounded-full translate-y-40 -translate-x-40"></div>

                    <div class="relative z-10 h-full flex flex-col">
                        <!-- Header -->
                        <div class="flex justify-end items-center p-6">
                            <div class="text-sm" id="tiempo-atencion">00:00 de 00:00 min</div>
                        </div>

                        <!-- Centro -->
                        <div class="flex-1 flex flex-col justify-center items-center">
                            <div class="text-6xl font-light mb-8 tracking-wider" id="turno-actual">TURNO</div>
                            <div class="text-4xl font-light tracking-wider" id="servicio-actual">SERVICIO</div>
                        </div>

                        <!-- Botones de acción -->
                        <div class="flex justify-center gap-4 mb-8">
                            <button
                                id="btn-atender"
                                class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-full text-lg transition-colors duration-200"
                                style="display: none;"
                            >
                                ATENDER
                            </button>
                            <button
                                id="btn-aplazar"
                                class="bg-yellow-600 hover:bg-yellow-700 text-white px-8 py-3 rounded-full text-lg transition-colors duration-200"
                                style="display: none;"
                            >
                                APLAZAR
                            </button>
                        </div>

                        <!-- Footer -->
                        <div class="p-6">
                            <div class="flex items-center mb-4 opacity-80 cursor-pointer" onclick="window.location.href='{{ route('asesor.cambiar-caja') }}'">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                <span class="text-sm">Cambiar Caja</span>
                            </div>

                            <div class="flex justify-between items-end">
                                <div class="text-sm opacity-70">
                                    <div>{{ $user->nombre_completo }}</div>
                                    <div class="text-xs">{{ $caja->nombre }}</div>
                                </div>
                                <div class="text-xl font-bold">
                                    Turnero<span class="text-blue-200">HUV</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal para notificaciones -->
    <div id="notification-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <div class="text-center">
                <div id="modal-icon" class="mx-auto mb-4 w-12 h-12 rounded-full flex items-center justify-center">
                    <svg id="success-icon" class="w-6 h-6 text-green-600 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <svg id="error-icon" class="w-6 h-6 text-red-600 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <h3 id="modal-title" class="text-lg font-medium text-gray-900 mb-2"></h3>
                <p id="modal-message" class="text-gray-600 mb-4"></p>
                <button id="modal-close" class="px-4 py-2 text-white rounded-md transition-colors duration-200" style="background-color: #064b9e;">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <script>
        let turnoActual = null;
        let tiempoInicio = null;
        let turnoEnProceso = false; // Estado para controlar si hay un turno en proceso

        // Elementos del DOM
        const codigoInput = document.getElementById('codigo-turno');
        const numeroInput = document.getElementById('numero-turno');
        const btnLlamarEspecifico = document.getElementById('btn-llamar-especifico');
        const btnCalificar = document.getElementById('btn-calificar');
        const btnAtender = document.getElementById('btn-atender');
        const btnAplazar = document.getElementById('btn-aplazar');
        const turnoActualElement = document.getElementById('turno-actual');
        const servicioActualElement = document.getElementById('servicio-actual');
        const tiempoAtencionElement = document.getElementById('tiempo-atencion');
        const botonesLlamarSiguiente = document.querySelectorAll('.btn-llamar-siguiente');
        const serviciosPrincipales = document.querySelectorAll('.servicio-principal');

        // Funciones para servicios desplegables
        serviciosPrincipales.forEach(servicioPrincipal => {
            if (servicioPrincipal.dataset.tieneHijos === 'true') {
                servicioPrincipal.addEventListener('click', function(e) {
                    // Evitar que se ejecute el evento si se hace clic en el botón
                    if (e.target.tagName === 'BUTTON' || e.target.closest('button')) {
                        return;
                    }

                    const servicioId = this.dataset.servicioId;
                    const subservicios = document.querySelectorAll(`.subservicio-row[data-parent-id="${servicioId}"]`);
                    const chevron = this.querySelector('.chevron-icon');

                    // Alternar visibilidad
                    subservicios.forEach(subservicio => {
                        if (subservicio.style.display === 'none' || subservicio.style.display === '') {
                            subservicio.style.display = 'grid';
                            chevron.classList.add('rotate-chevron');
                        } else {
                            subservicio.style.display = 'none';
                            chevron.classList.remove('rotate-chevron');
                        }
                    });
                });
            }
        });

        // Modal
        const modal = document.getElementById('notification-modal');
        const modalTitle = document.getElementById('modal-title');
        const modalMessage = document.getElementById('modal-message');
        const modalIcon = document.getElementById('modal-icon');
        const successIcon = document.getElementById('success-icon');
        const errorIcon = document.getElementById('error-icon');
        const modalClose = document.getElementById('modal-close');

        // Funciones del modal
        function mostrarModal(titulo, mensaje, tipo = 'success') {
            modalTitle.textContent = titulo;
            modalMessage.textContent = mensaje;

            if (tipo === 'success') {
                modalIcon.className = 'mx-auto mb-4 w-12 h-12 rounded-full flex items-center justify-center bg-green-100';
                successIcon.classList.remove('hidden');
                errorIcon.classList.add('hidden');
            } else {
                modalIcon.className = 'mx-auto mb-4 w-12 h-12 rounded-full flex items-center justify-center bg-red-100';
                successIcon.classList.add('hidden');
                errorIcon.classList.remove('hidden');
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function cerrarModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        modalClose.addEventListener('click', cerrarModal);
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                cerrarModal();
            }
        });

        // Funciones para habilitar/deshabilitar botones de llamar turno
        function deshabilitarBotonesLlamar() {
            console.log('🔒 Deshabilitando TODOS los botones de llamar turno');

            // Deshabilitar botón de llamar específico
            if (btnLlamarEspecifico) {
                btnLlamarEspecifico.disabled = true;
                btnLlamarEspecifico.classList.add('btn-bloqueado', 'opacity-50', 'cursor-not-allowed');
                btnLlamarEspecifico.classList.remove('hover:opacity-90');
                console.log('🔒 Botón llamar específico deshabilitado');
            }

            // Deshabilitar TODOS los botones de llamar siguiente (incluyendo los de la tabla)
            const todosLosBotonesLlamar = document.querySelectorAll('.btn-llamar-siguiente');
            console.log('🔍 Encontrados', todosLosBotonesLlamar.length, 'botones de llamar siguiente');

            todosLosBotonesLlamar.forEach((button, index) => {
                if (!button.disabled) { // Solo si no estaba ya deshabilitado por falta de turnos
                    button.disabled = true;
                    button.classList.add('btn-bloqueado', 'opacity-50', 'cursor-not-allowed');
                    button.style.pointerEvents = 'none'; // BLOQUEO TOTAL DE EVENTOS
                    button.setAttribute('data-was-enabled', 'true');
                    console.log(`🔒 Botón ${index + 1} COMPLETAMENTE deshabilitado:`, button.textContent.trim());
                } else {
                    // Incluso si ya estaba deshabilitado, aplicar bloqueo total
                    button.style.pointerEvents = 'none';
                    console.log(`⚠️ Botón ${index + 1} ya estaba deshabilitado - aplicando bloqueo total:`, button.textContent.trim());
                }
            });
        }

        function habilitarBotonesLlamar() {
            console.log('🔓 Habilitando TODOS los botones de llamar turno');

            // Habilitar botón de llamar específico
            if (btnLlamarEspecifico) {
                btnLlamarEspecifico.disabled = false;
                btnLlamarEspecifico.classList.remove('btn-bloqueado', 'opacity-50', 'cursor-not-allowed');
                btnLlamarEspecifico.classList.add('hover:opacity-90');
                console.log('🔓 Botón llamar específico habilitado');
            }

            // Habilitar TODOS los botones de llamar siguiente que estaban habilitados antes
            const todosLosBotonesLlamar = document.querySelectorAll('.btn-llamar-siguiente');
            console.log('🔍 Encontrados', todosLosBotonesLlamar.length, 'botones de llamar siguiente para habilitar');

            todosLosBotonesLlamar.forEach((button, index) => {
                if (button.getAttribute('data-was-enabled') === 'true') {
                    button.disabled = false;
                    button.classList.remove('btn-bloqueado', 'opacity-50', 'cursor-not-allowed');
                    button.style.pointerEvents = 'auto'; // RESTAURAR EVENTOS
                    button.removeAttribute('data-was-enabled');
                    console.log(`🔓 Botón ${index + 1} COMPLETAMENTE habilitado:`, button.textContent.trim());
                } else if (!button.disabled) {
                    // Si el botón no estaba marcado como deshabilitado por nosotros,
                    // pero tampoco está deshabilitado, asegurar que esté habilitado
                    button.classList.remove('btn-bloqueado');
                    button.style.pointerEvents = 'auto'; // RESTAURAR EVENTOS
                    console.log(`✅ Botón ${index + 1} ya estaba habilitado - restaurando eventos:`, button.textContent.trim());
                } else {
                    console.log(`⚠️ Botón ${index + 1} permanece deshabilitado (sin turnos):`, button.textContent.trim());
                }
            });
        }

        function mostrarModalAdvertencia() {
            mostrarModal(
                'Turno en Proceso',
                'Debe marcar el turno actual como "Atendido" antes de llamar un nuevo turno.',
                'error'
            );
        }

        // Función helper para manejar respuestas de fetch
        async function handleFetchResponse(response) {
            const contentType = response.headers.get('content-type');

            // Verificar si la respuesta es JSON
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Respuesta no JSON recibida:', text.substring(0, 200));
                throw new Error('El servidor devolvió una respuesta inválida. Posible problema de autenticación.');
            }

            // Para respuestas 400 (Bad Request) que contienen JSON válido,
            // no lanzar error sino devolver el JSON para manejo específico
            if (response.status === 400) {
                return response.json();
            }

            // Para otros errores HTTP, lanzar error
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return response.json();
        }

        // Función para actualizar la interfaz con el turno actual
        function actualizarInterfazTurno(turno) {
            console.log('🔄 Actualizando interfaz con turno:', turno.codigo_completo);

            turnoActual = turno;
            tiempoInicio = new Date();
            turnoEnProceso = true; // Marcar que hay un turno en proceso

            turnoActualElement.textContent = turno.codigo_completo;
            servicioActualElement.textContent = turno.servicio;

            btnAtender.style.display = 'block';
            btnAplazar.style.display = 'block';

            // Deshabilitar botones de llamar turno INMEDIATAMENTE
            deshabilitarBotonesLlamar();

            console.log('🔒 Turno en proceso - Estado actualizado:', {
                turnoEnProceso: turnoEnProceso,
                turnoActual: turnoActual?.codigo_completo,
                botonesAtenderVisible: btnAtender.style.display === 'block'
            });

            // Iniciar contador de tiempo
            actualizarTiempo();
            setInterval(actualizarTiempo, 1000);
        }

        function actualizarTiempo() {
            if (tiempoInicio) {
                const ahora = new Date();
                const diferencia = Math.floor((ahora - tiempoInicio) / 1000);
                const minutos = Math.floor(diferencia / 60);
                const segundos = diferencia % 60;

                tiempoAtencionElement.textContent =
                    `${minutos.toString().padStart(2, '0')}:${segundos.toString().padStart(2, '0')} de 00:00 min`;
            }
        }

        function limpiarInterfazTurno() {
            turnoActual = null;
            tiempoInicio = null;
            turnoEnProceso = false; // Marcar que no hay turno en proceso

            turnoActualElement.textContent = 'TURNO';
            servicioActualElement.textContent = 'SERVICIO';
            tiempoAtencionElement.textContent = '00:00 de 00:00 min';

            btnAtender.style.display = 'none';
            btnAplazar.style.display = 'none';

            // Habilitar botones de llamar turno
            habilitarBotonesLlamar();

            console.log('🔄 Interfaz limpiada - turnoEnProceso:', turnoEnProceso);
        }

        // Event listeners
        btnLlamarEspecifico.addEventListener('click', function() {
            // Verificar si hay un turno en proceso
            if (turnoEnProceso) {
                console.log('❌ Intento de llamar turno específico bloqueado - turno en proceso');
                mostrarModalAdvertencia();
                return;
            }

            // Verificar si el botón está deshabilitado
            if (this.disabled) {
                console.log('❌ Intento de llamar turno específico en botón deshabilitado');
                mostrarModalAdvertencia();
                return;
            }

            console.log('✅ Llamando turno específico - no hay turno en proceso');

            const codigo = codigoInput.value.trim().toUpperCase();
            const numero = numeroInput.value.trim();

            if (!codigo || !numero) {
                mostrarModal('Error', 'Por favor ingresa el código y número del turno', 'error');
                return;
            }

            fetch('{{ route("asesor.llamar-turno-especifico") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    codigo: codigo,
                    numero: parseInt(numero)
                })
            })
            .then(handleFetchResponse)
            .then(data => {
                if (data.success) {
                    actualizarInterfazTurno(data.turno);
                    mostrarModal('Turno Llamado', data.message);
                    codigoInput.value = '';
                    numeroInput.value = '';
                } else {
                    // Si hay un turno en proceso, mostrar modal específico
                    if (data.turno_en_proceso) {
                        mostrarModal('Turno en Proceso',
                            `Ya tiene el turno ${data.turno_en_proceso} en proceso. Debe marcarlo como "Atendido" antes de llamar un nuevo turno.`,
                            'error');
                    } else {
                        mostrarModal('Error', data.message, 'error');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarModal('Error', error.message || 'Error de conexión', 'error');
            });
        });

        // Event delegation para interceptar TODOS los clics en botones de llamar
        function agregarEventListenersBotonesLlamar() {
            console.log('🔧 Configurando event delegation para botones de llamar');

            // Remover event listener anterior si existe
            document.removeEventListener('click', interceptarClicksBotonesLlamar, true);

            // Agregar event listener con captura (se ejecuta ANTES que otros)
            document.addEventListener('click', interceptarClicksBotonesLlamar, true);

            console.log('✅ Event delegation configurado');
        }

        function interceptarClicksBotonesLlamar(event) {
            // Verificar si el clic fue en un botón de llamar siguiente
            if (event.target.classList.contains('btn-llamar-siguiente')) {
                console.log('🔍 Clic interceptado en botón de llamar siguiente');
                console.log('📊 Estado actual:', {
                    turnoEnProceso: turnoEnProceso,
                    buttonDisabled: event.target.disabled,
                    buttonText: event.target.textContent.trim()
                });

                // VERIFICACIÓN CRÍTICA: Si hay turno en proceso, bloquear COMPLETAMENTE
                if (turnoEnProceso) {
                    console.log('❌ BLOQUEADO - Hay turno en proceso');
                    event.stopImmediatePropagation();
                    event.preventDefault();
                    mostrarModalAdvertencia();
                    return false;
                }

                // VERIFICACIÓN: Si el botón está deshabilitado
                if (event.target.disabled) {
                    console.log('❌ BLOQUEADO - Botón deshabilitado');
                    event.stopImmediatePropagation();
                    event.preventDefault();
                    mostrarModalAdvertencia();
                    return false;
                }

                console.log('✅ PERMITIDO - Ejecutando función de llamar turno');

                // DETENER la propagación para evitar otros listeners
                event.stopImmediatePropagation();
                event.preventDefault();

                // Ejecutar nuestra función con validaciones
                manejarClickLlamarTurno(event);

                return false;
            }
        }

        function manejarClickLlamarTurno(event) {
            console.log('🔍 manejarClickLlamarTurno ejecutado');
            console.log('📊 Estado actual:', {
                turnoEnProceso: turnoEnProceso,
                buttonDisabled: event.target.disabled,
                buttonText: event.target.textContent.trim(),
                turnoActual: turnoActual?.codigo_completo || 'ninguno'
            });

            // Verificar si hay un turno en proceso
            if (turnoEnProceso) {
                console.log('❌ Intento de llamar turno bloqueado - turno en proceso');
                console.log('📊 Estado actual - turnoActual:', turnoActual);
                console.log('📊 Botón atender visible:', btnAtender.style.display === 'block');
                mostrarModalAdvertencia();
                return;
            }

            // Verificar si el botón está deshabilitado
            if (event.target.disabled) {
                console.log('❌ Intento de llamar turno en botón deshabilitado');
                mostrarModalAdvertencia();
                return;
            }

            console.log('✅ Llamando turno - no hay turno en proceso');

            const servicioId = event.target.dataset.servicioId;
            console.log('🔍 Datos de la petición:', {
                servicioId: servicioId,
                servicioIdParsed: parseInt(servicioId),
                url: '{{ route("asesor.llamar-siguiente-turno") }}'
            });

            fetch('{{ route("asesor.llamar-siguiente-turno") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    servicio_id: parseInt(servicioId)
                })
            })
            .then(async (response) => {
                // Manejo especial para evitar que aparezca "400 Bad Request" en consola
                const contentType = response.headers.get('content-type');

                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Respuesta no JSON recibida:', text.substring(0, 200));
                    throw new Error('El servidor devolvió una respuesta inválida.');
                }

                // Para 400 y otros códigos, simplemente devolver el JSON sin mostrar error
                if (response.status === 400 || response.status === 403) {
                    console.log('🔍 Respuesta del servidor (400/403):', response.status);
                    return response.json();
                }

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                return response.json();
            })
            .then(data => {
                console.log('🔍 Respuesta del servidor:', data);

                if (data.success) {
                    actualizarInterfazTurno(data.turno);
                    mostrarModal('Turno Llamado', data.message);
                    // NO recargar la página - mantener el estado de bloqueo
                    console.log('✅ Turno llamado, botones bloqueados hasta marcar como atendido');
                } else {
                    console.log('❌ Error en respuesta del servidor:', data);
                    // Si hay un turno en proceso, mostrar modal específico
                    if (data.turno_en_proceso) {
                        console.log('🚫 Turno en proceso detectado:', data.turno_en_proceso);
                        mostrarModal('Turno en Proceso',
                            `Ya tiene el turno ${data.turno_en_proceso} en proceso. Debe marcarlo como "Atendido" antes de llamar un nuevo turno.`,
                            'error');
                    } else {
                        mostrarModal('Error', data.message, 'error');
                    }
                }
            })
            .catch(error => {
                console.error('❌ Error en catch:', error);
                mostrarModal('Error', error.message || 'Error de conexión', 'error');
            });
        }

        btnAtender.addEventListener('click', function() {
            if (!turnoActual) return;

            fetch('{{ route("asesor.marcar-atendido") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    turno_id: turnoActual.id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('✅ Turno marcado como atendido - habilitando botones');
                    limpiarInterfazTurno();
                    mostrarModal('Turno Atendido', data.message);
                } else {
                    mostrarModal('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarModal('Error', 'Error de conexión', 'error');
            });
        });

        btnAplazar.addEventListener('click', function() {
            if (!turnoActual) return;

            fetch('{{ route("asesor.aplazar-turno") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    turno_id: turnoActual.id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    limpiarInterfazTurno();
                    mostrarModal('Turno Aplazado', data.message);
                    // Recargar la página para actualizar las estadísticas
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    mostrarModal('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarModal('Error', 'Error de conexión', 'error');
            });
        });

        // Convertir código a mayúsculas automáticamente
        codigoInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });

        // Esta función se eliminó para evitar duplicación con la inicialización principal



        // Función para verificar el estado del turno en el servidor
        function verificarEstadoTurnoEnServidor() {
            fetch('{{ route("asesor.verificar-turno-en-proceso") }}', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.turno_en_proceso) {
                    console.log('🔒 Turno en proceso detectado en servidor:', data.turno.codigo_completo);
                    actualizarInterfazTurno(data.turno);
                } else {
                    console.log('🔓 No hay turno en proceso en servidor');
                    limpiarInterfazTurno();
                }
            })
            .catch(error => {
                console.error('Error verificando estado del turno:', error);
                // En caso de error, usar la lógica visual como fallback
                if (btnAtender.style.display === 'block' || btnAplazar.style.display === 'block') {
                    turnoEnProceso = true;
                    deshabilitarBotonesLlamar();
                } else {
                    turnoEnProceso = false;
                    habilitarBotonesLlamar();
                }
            });
        }

        // Interceptar peticiones HTTP para bloquear llamadas duplicadas
        function interceptarPeticionesHTTP() {
            // Guardar la función fetch original
            const fetchOriginal = window.fetch;

            // Sobrescribir fetch para interceptar peticiones
            window.fetch = function(...args) {
                const url = args[0];

                // Si es una petición para llamar turno y hay un turno en proceso
                if (typeof url === 'string' && url.includes('llamar-siguiente-turno') && turnoEnProceso) {
                    console.log('🚫 Petición HTTP bloqueada - turno en proceso');

                    // Retornar una promesa rechazada
                    return Promise.reject(new Error('Petición bloqueada: hay un turno en proceso'));
                }

                // Si no hay conflicto, ejecutar fetch normal
                return fetchOriginal.apply(this, args);
            };
        }

        // Inicialización al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Dashboard del asesor cargado');

            // Interceptar peticiones HTTP
            interceptarPeticionesHTTP();

            // Agregar event listeners a todos los botones de llamar
            agregarEventListenersBotonesLlamar();

            // Agregar event listener al botón de repetir audio
            agregarEventListenerRepetirAudio();

            // Verificar estado del turno en el servidor
            verificarEstadoTurnoEnServidor();
        });

        // Función para manejar el botón de repetir audio
        function agregarEventListenerRepetirAudio() {
            const btnRepetirAudio = document.getElementById('repetir-audio-btn');

            if (btnRepetirAudio) {
                btnRepetirAudio.addEventListener('click', function() {
                    repetirAudioUltimoTurno();
                });
            }
        }

        // Función para solicitar repetición del audio
        function repetirAudioUltimoTurno() {
            const btnRepetirAudio = document.getElementById('repetir-audio-btn');

            // Deshabilitar botón temporalmente
            btnRepetirAudio.disabled = true;
            btnRepetirAudio.innerHTML = '<span>🔄</span><span>Enviando...</span>';

            // Hacer petición al servidor
            fetch('/api/repetir-audio-turno', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('✅ Solicitud de repetición enviada');

                    // Cambiar botón temporalmente para mostrar éxito
                    btnRepetirAudio.innerHTML = '<span>✅</span><span>Enviado</span>';
                    btnRepetirAudio.className = 'px-4 py-2 bg-green-500 text-white rounded-lg transition-colors duration-200 text-sm flex items-center gap-2';

                    // Usar localStorage para comunicación entre pestañas
                    localStorage.setItem('repetir-audio-turno', Date.now().toString());

                } else {
                    console.error('❌ Error en solicitud:', data.message);
                    alert('Error al solicitar repetición: ' + data.message);
                }
            })
            .catch(error => {
                console.error('❌ Error de red:', error);
                alert('Error de conexión al solicitar repetición');
            })
            .finally(() => {
                // Restaurar botón después de 2 segundos
                setTimeout(() => {
                    btnRepetirAudio.disabled = false;
                    btnRepetirAudio.innerHTML = '<span>🔊</span><span>Repetir Audio</span>';
                    btnRepetirAudio.className = 'px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors duration-200 text-sm flex items-center gap-2';
                }, 2000);
            });
        }
    </script>
</body>
</html>
