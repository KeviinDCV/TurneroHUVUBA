<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                        <div class="flex justify-between items-center p-6">
                            <select class="bg-white/20 border border-white/30 text-white rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-white/50">
                                <option value="disponible">Disponible</option>
                                <option value="ocupado">Ocupado</option>
                                <option value="descanso">En Descanso</option>
                            </select>
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

        // Función para actualizar la interfaz con el turno actual
        function actualizarInterfazTurno(turno) {
            turnoActual = turno;
            tiempoInicio = new Date();

            turnoActualElement.textContent = turno.codigo_completo;
            servicioActualElement.textContent = turno.servicio;

            btnAtender.style.display = 'block';
            btnAplazar.style.display = 'block';

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

            turnoActualElement.textContent = 'TURNO';
            servicioActualElement.textContent = 'SERVICIO';
            tiempoAtencionElement.textContent = '00:00 de 00:00 min';

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

        botonesLlamarSiguiente.forEach(button => {
            button.addEventListener('click', function() {
                const servicioId = this.dataset.servicioId;

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
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        actualizarInterfazTurno(data.turno);
                        mostrarModal('Turno Llamado', data.message);
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
        });

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
    </script>
</body>
</html>
