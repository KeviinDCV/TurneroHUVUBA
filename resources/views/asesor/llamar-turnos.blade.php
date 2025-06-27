<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Llamado de Turnos - Hospital Universitario del Valle</title>
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
        /* Evitar scroll innecesario */
        html, body {
            height: 100%;
            overflow: hidden;
        }
        /* Estilos para servicios desplegables */
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
        /* Transición suave para actualización de datos */
        .transition-count {
            transition: background-color 0.5s ease;
        }
        .highlight-update {
            background-color: rgba(59, 130, 246, 0.2); /* Blue highlight */
        }
    </style>
</head>
<body class="bg-gray-100 h-screen">
    <!-- Interfaz de llamado de turnos -->
    <div class="h-screen flex bg-gray-100 overflow-hidden">
        <!-- Panel Izquierdo -->
        <div class="w-1/2 bg-white p-6 shadow-lg overflow-y-auto">
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

                <div id="servicios-container">
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
                        <div class="p-3 border-r border-gray-200 text-center transition-count" data-pendientes="{{ $servicio['pendientes'] }}">
                            {{ $servicio['pendientes'] }}
                        </div>
                        <div class="p-3 border-r border-gray-200 text-center transition-count" data-aplazados="{{ $servicio['aplazados'] }}">
                            {{ $servicio['aplazados'] }}
                        </div>
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
                                <div class="p-3 border-r border-gray-200 text-center transition-count" data-pendientes="{{ $subservicio['pendientes'] }}">{{ $subservicio['pendientes'] }}</div>
                                <div class="p-3 border-r border-gray-200 text-center transition-count" data-aplazados="{{ $subservicio['aplazados'] }}">{{ $subservicio['aplazados'] }}</div>
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
        <div class="w-1/2 text-white relative overflow-hidden" style="background: linear-gradient(135deg, #064b9e 0%, #0a5bb8 100%);">
            <!-- Elementos decorativos -->
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -translate-y-32 translate-x-32"></div>
            <div class="absolute bottom-0 left-0 w-64 h-64 bg-white/10 rounded-full translate-y-32 -translate-x-32"></div>

            <div class="relative z-10 h-full flex flex-col">
                <!-- Header -->
                <div class="flex justify-end items-center p-6">
                    <div class="text-sm" id="tiempo-atencion">00:00 min</div>
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
                        ATENDIDO
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

    <!-- Modal para notificaciones -->
    <div id="notification-modal" class="fixed inset-0 hidden items-center justify-center z-50 backdrop-blur-sm bg-black/30">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 shadow-xl animate-fade-in">
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
        let intervalActualizacion = null;
        let intervalTiempo = null;
        let tiempoTranscurrido = 0;
        let serviciosExpanded = {}; // Almacena el estado de expansión de cada servicio

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
        const serviciosContainer = document.getElementById('servicios-container');

        // Inicializar estado de expansión de servicios
        document.querySelectorAll('.servicio-principal').forEach(servicio => {
            serviciosExpanded[servicio.dataset.servicioId] = false;
        });

        // Funciones para servicios desplegables
        function configurarEventosServiciosDesplegables() {
            document.querySelectorAll('.servicio-principal').forEach(servicioPrincipal => {
                if (servicioPrincipal.dataset.tieneHijos === 'true') {
                    servicioPrincipal.addEventListener('click', function(e) {
                        // Evitar que se ejecute el evento si se hace clic en el botón
                        if (e.target.tagName === 'BUTTON' || e.target.closest('button')) {
                            return;
                        }

                        const servicioId = this.dataset.servicioId;
                        const subservicios = document.querySelectorAll(`.subservicio-row[data-parent-id="${servicioId}"]`);
                        const chevron = this.querySelector('.chevron-icon');

                        // Actualizar el estado de expansión
                        serviciosExpanded[servicioId] = !serviciosExpanded[servicioId];

                        // Alternar visibilidad
                        subservicios.forEach(subservicio => {
                            if (!serviciosExpanded[servicioId]) {
                                subservicio.style.display = 'none';
                                chevron.classList.remove('rotate-chevron');
                            } else {
                                subservicio.style.display = 'grid';
                                chevron.classList.add('rotate-chevron');
                            }
                        });
                    });
                }
            });

            // Re-asignar eventos a los botones de llamar
            document.querySelectorAll('.btn-llamar-siguiente').forEach(button => {
                button.addEventListener('click', handleLlamarSiguiente);
            });
        }

        // Función helper para manejar respuestas de fetch (igual que en dashboard)
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

        // Función para manejar el evento de llamar siguiente turno
        function handleLlamarSiguiente() {
            const servicioId = this.dataset.servicioId;
            console.log('🔍 handleLlamarSiguiente ejecutado con servicioId:', servicioId);

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
                    return response.json();
                }

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                return response.json();
            })
            .then(data => {
                console.log('🔍 Respuesta del servidor en llamar-turnos:', data);

                if (data.success) {
                    actualizarInterfazTurno(data.turno);
                    mostrarModal('Turno Llamado', data.message);
                    // Forzar actualización inmediata
                    actualizarEstadisticasServicios();
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

        // Función para actualizar las estadísticas de servicios
        function actualizarEstadisticasServicios() {
            fetch('{{ route("api.asesor.servicios-estadisticas") }}')
                .then(response => response.json())
                .then(servicios => {
                    actualizarTablaServicios(servicios);
                })
                .catch(error => {
                    console.error('Error al actualizar estadísticas:', error);
                });
        }

        // Función para actualizar la tabla de servicios con los nuevos datos
        function actualizarTablaServicios(servicios) {
            let contenidoHTML = '';

            if (servicios.length === 0) {
                contenidoHTML = '<div class="p-6 text-center text-gray-500">No tienes servicios asignados</div>';
            } else {
                servicios.forEach((servicio, index) => {
                    // Determinar si este servicio estaba expandido
                    const estaExpandido = serviciosExpanded[servicio.id] || false;

                    // Clase para alternar colores
                    const bgClass = index % 2 === 0 ? 'bg-white' : 'bg-gray-50';

                    // Fila del servicio principal
                    contenidoHTML += `
                        <div class="grid grid-cols-4 text-sm ${bgClass} servicio-principal"
                             data-servicio-id="${servicio.id}"
                             data-tiene-hijos="${servicio.tiene_hijos ? 'true' : 'false'}">
                            <div class="p-3 border-r border-gray-200 font-medium servicio-nombre flex items-center">
                                ${servicio.tiene_hijos ? `
                                    <svg class="w-4 h-4 mr-1 chevron-icon text-blue-700 ${estaExpandido ? 'rotate-chevron' : ''}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                ` : ''}
                                ${servicio.nombre}
                            </div>
                            <div class="p-3 border-r border-gray-200 text-center transition-count" data-pendientes="${servicio.pendientes}">
                                ${servicio.pendientes}
                            </div>
                            <div class="p-3 border-r border-gray-200 text-center transition-count" data-aplazados="${servicio.aplazados}">
                                ${servicio.aplazados}
                            </div>
                            <div class="p-3 text-center">
                                <button
                                    class="btn-llamar-siguiente text-xs px-3 py-1 rounded text-white transition-colors duration-200"
                                    data-servicio-id="${servicio.id}"
                                    style="background-color: #064b9e;"
                                    ${servicio.total == 0 ? 'disabled' : ''}
                                >
                                    ${servicio.total > 0 ? 'DISPONIBLE' : 'SIN TURNOS'}
                                </button>
                            </div>
                        </div>
                    `;

                    // Subservicios desplegables
                    if (servicio.tiene_hijos && servicio.subservicios.length > 0) {
                        servicio.subservicios.forEach((subservicio, subindex) => {
                            const displayStyle = estaExpandido ? 'grid' : 'none';
                            const subBgClass = subindex % 2 === 0 ? 'bg-white' : 'bg-gray-50';

                            contenidoHTML += `
                                <div class="grid grid-cols-4 text-sm ${subBgClass} subservicio-row"
                                     data-parent-id="${servicio.id}"
                                     style="display: ${displayStyle}">
                                    <div class="p-3 border-r border-gray-200 font-medium servicio-nombre">
                                        ${subservicio.nombre}
                                    </div>
                                    <div class="p-3 border-r border-gray-200 text-center transition-count" data-pendientes="${subservicio.pendientes}">${subservicio.pendientes}</div>
                                    <div class="p-3 border-r border-gray-200 text-center transition-count" data-aplazados="${subservicio.aplazados}">${subservicio.aplazados}</div>
                                    <div class="p-3 text-center">
                                        <button
                                            class="btn-llamar-siguiente text-xs px-3 py-1 rounded text-white transition-colors duration-200"
                                            data-servicio-id="${subservicio.id}"
                                            style="background-color: #064b9e;"
                                            ${subservicio.total == 0 ? 'disabled' : ''}
                                        >
                                            ${subservicio.total > 0 ? 'DISPONIBLE' : 'SIN TURNOS'}
                                        </button>
                                    </div>
                                </div>
                            `;
                        });
                    }
                });
            }

            // Actualizar el contenido
            serviciosContainer.innerHTML = contenidoHTML;

            // Volver a configurar los eventos desplegables
            configurarEventosServiciosDesplegables();
        }

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

        // Función para actualizar la interfaz con el turno actual
        function actualizarInterfazTurno(turno) {
            turnoActual = turno;
            tiempoInicio = new Date();
            tiempoTranscurrido = 0;

            turnoActualElement.textContent = turno.codigo_completo;
            servicioActualElement.textContent = turno.servicio;

            btnAtender.style.display = 'block';
            btnAplazar.style.display = 'block';

            // Detener contador anterior si existe
            if (intervalTiempo) {
                clearInterval(intervalTiempo);
            }

            // Iniciar contador de tiempo
            actualizarTiempo();
            intervalTiempo = setInterval(actualizarTiempo, 1000);
        }

        function actualizarTiempo() {
            if (tiempoInicio) {
                const ahora = new Date();
                tiempoTranscurrido = Math.floor((ahora - tiempoInicio) / 1000);
                const minutos = Math.floor(tiempoTranscurrido / 60);
                const segundos = tiempoTranscurrido % 60;

                tiempoAtencionElement.textContent =
                    `${minutos.toString().padStart(2, '0')}:${segundos.toString().padStart(2, '0')} min`;
            }
        }

        function limpiarInterfazTurno() {
            turnoActual = null;
            tiempoInicio = null;

            if (intervalTiempo) {
                clearInterval(intervalTiempo);
                intervalTiempo = null;
            }

            turnoActualElement.textContent = 'TURNO';
            servicioActualElement.textContent = 'SERVICIO';
            tiempoAtencionElement.textContent = '00:00 min';

            btnAtender.style.display = 'none';
            btnAplazar.style.display = 'none';
        }

        // Event listeners
        btnLlamarEspecifico.addEventListener('click', function() {
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
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    actualizarInterfazTurno(data.turno);
                    mostrarModal('Turno Llamado', data.message);
                    codigoInput.value = '';
                    numeroInput.value = '';
                } else {
                    mostrarModal('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarModal('Error', 'Error de conexión', 'error');
            });
        });

        // Configurar eventos iniciales
        configurarEventosServiciosDesplegables();

        btnAtender.addEventListener('click', function() {
            if (!turnoActual) return;

            // Detener el contador
            if (intervalTiempo) {
                clearInterval(intervalTiempo);
            }

            fetch('{{ route("asesor.marcar-atendido") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    turno_id: turnoActual.id,
                    duracion: tiempoTranscurrido
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const duracionFormateada = data.duracion ? formatearTiempo(data.duracion) : '00:00';
                    limpiarInterfazTurno();
                    mostrarModal('Turno Atendido', `Turno atendido correctamente. Tiempo de atención: ${duracionFormateada} min`);
                    actualizarEstadisticasServicios(); // Actualizar datos
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
                    actualizarEstadisticasServicios(); // Actualizar datos
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

        // Iniciar actualización periódica
        document.addEventListener('DOMContentLoaded', function() {
            // Iniciar la actualización cada 5 segundos
            intervalActualizacion = setInterval(actualizarEstadisticasServicios, 5000);

            // Configurar eventos iniciales
            configurarEventosServiciosDesplegables();
        });

        // Función para formatear tiempo en segundos a formato MM:SS
        function formatearTiempo(segundos) {
            const minutos = Math.floor(segundos / 60);
            const segs = segundos % 60;
            return `${minutos.toString().padStart(2, '0')}:${segs.toString().padStart(2, '0')}`;
        }
    </script>
</body>
</html>
