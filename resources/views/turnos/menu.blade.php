<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Turnero HUV') }} - Menú de Servicios</title>
    @include('components.favicon')

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @keyframes slide-in {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
        }

        .animate-slide-in {
            animation: slide-in 0.3s ease-out;
        }

        .animate-float {
            animation: float 3s ease-in-out infinite;
        }

        .btn-service {
            background: linear-gradient(135deg, #064b9e 0%, #0a5fb4 100%);
            border: 2px solid #053a7a;
            transition: all 0.15s ease;
        }

        .btn-service:hover {
            background: linear-gradient(135deg, #053a7a 0%, #064b9e 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(6, 75, 158, 0.3);
        }

        .btn-service:active {
            transform: translateY(0px);
            box-shadow: 0 5px 15px rgba(6, 75, 158, 0.2);
        }

        .btn-volver {
            background-color: #064b9e;
            border: 2px solid #053a7a;
        }

        .btn-volver:hover {
            background-color: #053a7a;
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(6, 75, 158, 0.3);
        }

        /* Estilos para botones de prioridad */
        .btn-prioridad-normal {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }
        .btn-prioridad-normal:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        }

        .btn-prioridad-alta {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        .btn-prioridad-alta:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        }

        /* Estilos para modal overlay */
        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }
    </style>
</head>
<body class="min-h-screen bg-gray-100 p-4">
    <!-- Elementos decorativos de fondo -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-20 -right-20 w-40 h-40 rounded-full opacity-5 animate-float" style="background-color: #064b9e;"></div>
        <div class="absolute -bottom-16 -left-16 w-32 h-32 rounded-full opacity-5 animate-float" style="background-color: #064b9e; animation-delay: 1s;"></div>
        <div class="absolute top-1/4 left-1/4 w-3 h-3 rounded-full opacity-10 animate-pulse" style="background-color: #064b9e; animation-delay: 2s;"></div>
        <div class="absolute top-3/4 right-1/4 w-2 h-2 rounded-full opacity-10 animate-pulse" style="background-color: #064b9e; animation-delay: 3s;"></div>
    </div>

    <!-- Header with Volver button and logo -->
    <div class="flex justify-between items-start mb-8 animate-slide-in">
        <button
            @if(isset($mostrandoSubservicios) && $mostrandoSubservicios)
                onclick="window.location.href='{{ route('turnos.menu') }}'"
            @else
                onclick="window.location.href='{{ route('turnos.inicio') }}'"
            @endif
            class="btn-volver text-white px-6 py-2 rounded-md font-medium flex items-center transition-all duration-150"
        >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Volver
        </button>

        <!-- Logo/watermark del hospital -->
        <div class="text-gray-300 text-sm opacity-50 font-light text-right">
            <div class="text-lg font-semibold" style="color: #064b9e; opacity: 0.3;">HUV</div>
            <div class="text-xs">TURNERO</div>
        </div>
    </div>

    <!-- Main navigation buttons -->
    <div class="flex flex-col items-center justify-center space-y-6 max-w-2xl mx-auto mt-16">
        <!-- Título del menú -->
        <div class="text-center mb-8 animate-slide-in" style="animation-delay: 0.05s; animation-fill-mode: both;">
            @if(isset($mostrandoSubservicios) && $mostrandoSubservicios)
                <h1 class="text-3xl font-bold mb-2" style="color: #064b9e;">{{ $servicioSeleccionado->nombre }}</h1>
                <p class="text-lg text-gray-600 mb-4">Seleccione el subservicio</p>
            @else
                <h1 class="text-3xl font-bold mb-2" style="color: #064b9e;">Seleccione el Servicio</h1>
            @endif
            <div class="h-1 w-24 mx-auto rounded-full" style="background-color: #064b9e;"></div>
        </div>

        <!-- Botones dinámicos de servicios o subservicios -->
        @if(isset($mostrandoSubservicios) && $mostrandoSubservicios)
            <!-- Mostrar subservicios -->
            @forelse($subservicios as $index => $subservicio)
                <button
                    class="btn-service w-full max-w-lg h-16 text-white text-xl font-medium rounded-lg shadow-lg animate-slide-in"
                    style="animation-delay: {{ 0.1 + ($index * 0.05) }}s; animation-fill-mode: both;"
                    onclick="seleccionarSubservicio({{ $subservicio->id }}, '{{ addslashes($subservicio->nombre) }}')"
                >
                    {{ strtoupper($subservicio->nombre) }}
                </button>
            @empty
                <button
                    class="btn-service w-full max-w-lg h-16 text-white text-xl font-medium rounded-lg shadow-lg animate-slide-in"
                    style="animation-delay: 0.1s; animation-fill-mode: both;"
                    onclick="seleccionarServicio({{ $servicioSeleccionado->id }}, '{{ addslashes($servicioSeleccionado->nombre) }}')"
                >
                    {{ strtoupper($servicioSeleccionado->nombre) }}
                </button>
            @endforelse
        @else
            <!-- Mostrar servicios principales -->
            @forelse($servicios as $index => $servicio)
                @php
                    $tieneSubservicios = $servicio->subservicios()->where('estado', 'activo')->count() > 0;
                @endphp
                <button
                    class="btn-service w-full max-w-lg h-16 text-white text-xl font-medium rounded-lg shadow-lg animate-slide-in"
                    style="animation-delay: {{ 0.1 + ($index * 0.05) }}s; animation-fill-mode: both;"
                    @if($tieneSubservicios)
                        onclick="navegarASubservicios({{ $servicio->id }})"
                    @else
                        onclick="seleccionarServicio({{ $servicio->id }}, '{{ addslashes($servicio->nombre) }}')"
                    @endif
                >
                    {{ strtoupper($servicio->nombre) }}
                </button>
            @empty
                <div class="text-center text-gray-600 py-8">
                    <p class="text-lg">No hay servicios disponibles en este momento</p>
                </div>
            @endforelse
        @endif
    </div>

    <!-- Instrucciones -->
    <div class="absolute bottom-16 left-0 right-0 text-center animate-slide-in" style="animation-delay: 0.3s; animation-fill-mode: both;">
        <p class="text-lg text-gray-600">
            Toque el servicio que necesita
        </p>
    </div>

    <!-- Firma -->
    <div class="absolute bottom-4 right-4">
        <p class="text-xs text-gray-400">
            Turnero HUV - Innovación y desarrollo
        </p>
    </div>

    <!-- Modal de selección de prioridad -->
    <div id="prioridadModal" class="fixed inset-0 modal-overlay z-50 flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-md overflow-y-auto max-h-[90vh] animate-slide-in">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Seleccione el Tipo de Turno</h3>
                    <button onclick="cerrarPrioridadModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <p id="prioridadServicioNombre" class="text-sm text-gray-600 mb-6 text-center"></p>
                
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <button onclick="seleccionarPrioridad('normal')" class="btn-prioridad-normal h-32 rounded-lg font-bold text-white shadow-lg hover:shadow-xl transition-all transform hover:scale-105 flex flex-col items-center justify-center">
                        <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <div class="text-xl">Normal</div>
                    </button>
                    <button onclick="seleccionarPrioridad('alta')" class="btn-prioridad-alta h-32 rounded-lg font-bold text-white shadow-lg hover:shadow-xl transition-all transform hover:scale-105 flex flex-col items-center justify-center">
                        <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div class="text-xl">Prioritario</div>
                        <div class="text-xs font-normal opacity-80">Adulto mayor, embarazada, discapacidad</div>
                    </button>
                </div>
                
                <div class="flex justify-center mt-4">
                    <button onclick="cerrarPrioridadModal()" class="px-6 py-2 bg-gray-300 text-gray-700 rounded-md font-medium hover:bg-gray-400 transition-colors">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación personalizado -->
    <div id="confirmModal" class="fixed inset-0 modal-overlay z-50 flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-lg shadow-2xl p-6 max-w-md w-full mx-4 animate-slide-in">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Turno Solicitado</h3>
                <p id="confirmMessage" class="text-sm text-gray-500 mb-4"></p>
                <button onclick="cerrarModal()" class="btn-service px-4 py-2 text-white rounded-md hover:opacity-90 transition-opacity">
                    Aceptar
                </button>
            </div>
        </div>
    </div>

    <script>
        // Configurar CSRF token para peticiones AJAX
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        // Variables para almacenar datos del servicio seleccionado
        let servicioSeleccionadoId = null;
        let servicioSeleccionadoNombre = '';

        // Función para navegar a subservicios
        function navegarASubservicios(servicioId) {
            if (navigator.vibrate) navigator.vibrate(30);
            window.location.href = `{{ route('turnos.menu') }}?servicio_id=${servicioId}`;
        }

        // Función para seleccionar un servicio principal (sin subservicios)
        function seleccionarServicio(servicioId, nombreServicio) {
            if (navigator.vibrate) navigator.vibrate(30);

            fetch('{{ route('turnos.seleccionar') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    servicio_id: servicioId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Verificar si requiere priorización
                    if (data.requiere_priorizacion) {
                        servicioSeleccionadoId = data.servicio_id;
                        servicioSeleccionadoNombre = data.servicio_nombre;
                        mostrarPrioridadModal(data.servicio_nombre);
                    } else {
                        // Redirigir al ticket del turno
                        if (data.redirect_url) {
                            window.location.href = data.redirect_url;
                        } else {
                            mostrarModal(data.message);
                        }
                    }
                } else {
                    mostrarModal(data.message || 'Error al procesar la solicitud');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarModal('Error de conexión');
            });
        }

        // Función para seleccionar un subservicio
        function seleccionarSubservicio(subservicioId, nombreSubservicio) {
            if (navigator.vibrate) navigator.vibrate(30);

            fetch('{{ route('turnos.seleccionar') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    subservicio_id: subservicioId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Verificar si requiere priorización
                    if (data.requiere_priorizacion) {
                        servicioSeleccionadoId = data.servicio_id;
                        servicioSeleccionadoNombre = data.servicio_nombre;
                        mostrarPrioridadModal(data.servicio_nombre);
                    } else {
                        // Redirigir al ticket del turno
                        if (data.redirect_url) {
                            window.location.href = data.redirect_url;
                        } else {
                            mostrarModal(data.message);
                        }
                    }
                } else {
                    mostrarModal(data.message || 'Error al procesar la solicitud');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarModal('Error de conexión');
            });
        }

        // Mostrar modal de selección de prioridad
        function mostrarPrioridadModal(nombreServicio) {
            document.getElementById('prioridadServicioNombre').textContent = `Servicio: ${nombreServicio}`;
            document.getElementById('prioridadModal').style.display = 'flex';
        }

        // Cerrar modal de prioridad
        function cerrarPrioridadModal() {
            document.getElementById('prioridadModal').style.display = 'none';
            servicioSeleccionadoId = null;
            servicioSeleccionadoNombre = '';
        }

        // Seleccionar prioridad y crear turno
        function seleccionarPrioridad(prioridad) {
            if (navigator.vibrate) navigator.vibrate(30);
            
            if (!servicioSeleccionadoId) {
                mostrarModal('Error: No hay servicio seleccionado');
                return;
            }

            fetch('{{ route('turnos.crear-con-prioridad') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    servicio_id: servicioSeleccionadoId,
                    prioridad: prioridad
                })
            })
            .then(response => response.json())
            .then(data => {
                cerrarPrioridadModal();
                
                if (data.success) {
                    // Redirigir al ticket del turno
                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                    } else {
                        mostrarModal(data.message);
                    }
                } else {
                    mostrarModal(data.message || 'Error al generar el turno');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                cerrarPrioridadModal();
                mostrarModal('Error de conexión');
            });
        }

        // Función para mostrar el modal personalizado
        function mostrarModal(mensaje) {
            document.getElementById('confirmMessage').textContent = mensaje;
            document.getElementById('confirmModal').style.display = 'flex';
        }

        // Función para cerrar el modal
        function cerrarModal() {
            document.getElementById('confirmModal').style.display = 'none';
        }

        // Agregar efecto de vibración en dispositivos móviles al tocar botones
        document.querySelectorAll('button').forEach(button => {
            button.addEventListener('click', function() {
                if (navigator.vibrate) {
                    navigator.vibrate(30);
                }
            });
        });

        // Prevenir zoom en dispositivos táctiles
        document.addEventListener('touchstart', function(event) {
            if (event.touches.length > 1) {
                event.preventDefault();
            }
        });

        let lastTouchEnd = 0;
        document.addEventListener('touchend', function(event) {
            const now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
    </script>
</body>
</html>
