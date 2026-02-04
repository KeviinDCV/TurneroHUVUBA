<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
        /* Asegurar que la tabla de historial sea visible */
        .historial-tabla {
            z-index: 1000;
        }
        /* Animación para el spinner */
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
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

        /* Responsive Design - Mejoras para resoluciones pequeñas */
        @media (max-height: 800px) {
            /* Reducir padding general */
            .panel-left {
                padding: 1rem !important;
            }
            .panel-right {
                padding: 1rem !important;
            }

            /* Reducir tamaños de texto en panel derecho */
            .turno-display {
                font-size: 3rem !important;
                margin-bottom: 1rem !important;
            }
            .servicio-display {
                font-size: 2rem !important;
            }

            /* Reducir padding de botones */
            .action-buttons button {
                padding: 0.5rem 1.5rem !important;
                font-size: 0.875rem !important;
            }

            /* Reducir espaciado en secciones */
            .section-spacing {
                margin-bottom: 1rem !important;
            }

            /* Ajustar tabla de servicios */
            .services-table .grid-item {
                padding: 0.5rem !important;
                font-size: 0.75rem !important;
            }

            /* Ajustar tabla de historial */
            .historial-table .grid-item {
                padding: 0.375rem !important;
                font-size: 0.75rem !important;
            }
        }

        @media (max-height: 700px) {
            /* Para pantallas muy pequeñas */
            .turno-display {
                font-size: 2.5rem !important;
                margin-bottom: 0.5rem !important;
            }
            .servicio-display {
                font-size: 1.5rem !important;
            }

            .panel-left, .panel-right {
                padding: 0.75rem !important;
            }

            .action-buttons {
                margin-bottom: 1rem !important;
            }
        }

        @media (max-width: 1400px) {
            /* Ajustes para anchos menores */
            .services-table .grid-item,
            .historial-table .grid-item {
                font-size: 0.75rem !important;
                padding: 0.5rem !important;
            }

            /* Reducir padding de inputs */
            .input-group input {
                padding: 0.5rem !important;
                font-size: 0.875rem !important;
            }
        }

        @media (max-width: 1200px) {
            /* Para pantallas más pequeñas */
            .services-table .grid-item,
            .historial-table .grid-item {
                font-size: 0.7rem !important;
                padding: 0.375rem !important;
            }

            .turno-display {
                font-size: 2rem !important;
            }
            .servicio-display {
                font-size: 1.25rem !important;
            }
        }

        /* Mejoras específicas para resolución 1366x768 */
        @media (max-width: 1366px) and (max-height: 768px) {
            .panel-left, .panel-right {
                padding: 0.75rem !important;
            }

            .turno-display {
                font-size: 2.5rem !important;
                margin-bottom: 0.75rem !important;
            }

            .servicio-display {
                font-size: 1.5rem !important;
            }

            .action-buttons button {
                padding: 0.5rem 1.25rem !important;
                font-size: 0.875rem !important;
            }

            .services-table .grid-item,
            .historial-table .grid-item {
                font-size: 0.7rem !important;
                padding: 0.375rem !important;
            }

            .section-spacing {
                margin-bottom: 0.75rem !important;
            }

            /* Mejorar botones pequeños */
            .btn-llamar-siguiente {
                font-size: 0.65rem !important;
                padding: 0.25rem 0.5rem !important;
            }

            /* Ajustar títulos */
            h2 {
                font-size: 1rem !important;
                margin-bottom: 0.75rem !important;
            }
        }

        /* ===== RESPONSIVE MEJORADO PARA BOTONES DE ACCIONES ===== */

        /* Resoluciones muy bajas (hasta 1023px) */
        @media (max-width: 1023px) {
            .historial-table .grid-item {
                font-size: 0.7rem !important;
                padding: 0.25rem !important;
            }

            /* Botones de acciones más compactos */
            .historial-action-btn {
                padding: 0.125rem 0.25rem !important;
                font-size: 0.6rem !important;
                margin: 0.125rem !important;
            }
        }

        /* Resoluciones problemáticas como 1024x666 */
        @media (min-width: 1024px) and (max-width: 1199px) {
            .historial-table .grid-item {
                font-size: 0.75rem !important;
                padding: 0.375rem !important;
            }

            /* Botones de acciones compactos */
            .historial-action-btn {
                padding: 0.25rem 0.375rem !important;
                font-size: 0.65rem !important;
                margin: 0.125rem !important;
            }
        }

        /* Pantallas muy pequeñas en altura */
        @media (max-height: 600px) {
            .historial-table .grid-item {
                font-size: 0.65rem !important;
                padding: 0.25rem !important;
            }

            .historial-action-btn {
                padding: 0.125rem 0.25rem !important;
                font-size: 0.55rem !important;
                margin: 0.0625rem !important;
            }
        }

        /* Mejoras para móviles */
        @media (max-width: 767px) {
            .historial-table {
                overflow-x: auto;
            }

            .historial-table .grid-item {
                min-width: 60px;
                font-size: 0.6rem !important;
                padding: 0.25rem !important;
            }

            .historial-action-btn {
                padding: 0.125rem 0.25rem !important;
                font-size: 0.55rem !important;
                margin: 0.0625rem !important;
                display: block !important;
                width: 100% !important;
                margin-bottom: 0.125rem !important;
            }

            /* Hacer paneles apilables en móviles */
            .h-screen.flex {
                flex-direction: column !important;
            }

            .w-1\/2 {
                width: 100% !important;
                height: auto !important;
            }
        }

        /* Mejoras adicionales para la columna de acciones */
        .historial-action-btn {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Asegurar que la tabla sea responsive */
        .historial-table {
            min-width: 100%;
            table-layout: fixed;
        }

        /* Grid personalizado para dar más espacio a la columna de acciones */
        .historial-grid {
            display: grid;
            grid-template-columns: 1fr 2fr 1fr 1fr 1fr 1.5fr; /* Más espacio para ACCIONES */
        }

        /* Responsive para el grid personalizado */
        @media (max-width: 1199px) {
            .historial-grid {
                grid-template-columns: 0.8fr 1.5fr 0.8fr 0.8fr 0.8fr 1.8fr; /* Aún más espacio para acciones en pantallas pequeñas */
            }
        }

        @media (max-width: 1023px) {
            .historial-grid {
                grid-template-columns: 0.7fr 1.2fr 0.7fr 0.7fr 0.7fr 2fr; /* Máximo espacio para acciones */
            }
        }

        @media (max-width: 767px) {
            .historial-grid {
                grid-template-columns: 0.6fr 1fr 0.6fr 0.6fr 0.6fr 2.2fr; /* Priorizar acciones en móviles */
            }
        }

        /* Asegurar que los elementos no se desborden */
        * {
            box-sizing: border-box;
        }

        /* Mejorar legibilidad en pantallas pequeñas */
        @media (max-height: 600px) {
            .turno-display {
                font-size: 2rem !important;
                margin-bottom: 0.5rem !important;
            }

            .servicio-display {
                font-size: 1.25rem !important;
            }

            .action-buttons {
                margin-bottom: 0.5rem !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100 h-screen">
    <!-- Interfaz de llamado de turnos -->
    <div class="h-screen flex bg-gray-100 overflow-hidden">
        <!-- Panel Izquierdo -->
        <div class="w-1/2 bg-white panel-left p-6 shadow-lg overflow-y-auto">
            <div class="section-spacing mb-6">
                <h2 class="text-lg font-medium text-gray-700 mb-4">Llamar Turno Específico</h2>

                <div class="input-group flex items-center gap-2 mb-4">
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
                </div>
            </div>

            <!-- Tabla de servicios -->
            <div class="services-table border border-gray-300 rounded overflow-hidden">
                <div class="text-white grid grid-cols-4 text-sm font-medium" style="background-color: #064b9e;">
                    <div class="grid-item p-3 border-r border-blue-400">SERVICIO</div>
                    <div class="grid-item p-3 border-r border-blue-400 text-center">CANTIDAD</div>
                    <div class="grid-item p-3 border-r border-blue-400 text-center">APLAZADOS</div>
                    <div class="grid-item p-3 text-center">OPCIÓN</div>
                </div>

                <div id="servicios-container">
                    @forelse($serviciosEstructurados as $servicio)
                    <!-- Fila del servicio principal -->
                    <div class="grid grid-cols-4 text-sm {{ $loop->even ? 'bg-gray-50' : 'bg-white' }} servicio-principal"
                         data-servicio-id="{{ $servicio['id'] }}"
                         data-tiene-hijos="{{ $servicio['tiene_hijos'] ? 'true' : 'false' }}">
                        <div class="grid-item p-3 border-r border-gray-200 font-medium servicio-nombre flex items-center">
                            @if($servicio['tiene_hijos'])
                                <svg class="w-4 h-4 mr-1 chevron-icon text-blue-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                                </svg>
                            @endif
                            {{ $servicio['nombre'] }}
                        </div>
                        <div class="grid-item p-3 border-r border-gray-200 text-center transition-count" data-pendientes="{{ $servicio['pendientes'] }}">
                            {{ $servicio['pendientes'] }}
                        </div>
                            <div class="grid-item p-3 border-r border-gray-200 text-center transition-count cursor-pointer hover:bg-blue-50" 
                                 data-aplazados="{{ $servicio['aplazados'] }}"
                                 data-servicio-id="{{ $servicio['id'] }}"
                                 onclick="abrirModalAplazados({{ $servicio['id'] }}, '{{ $servicio['nombre'] }}', {{ $servicio['aplazados'] }})"
                                 style="{{ $servicio['aplazados'] > 0 ? 'cursor: pointer; font-weight: bold; color: #f97316;' : '' }}">
                                {{ $servicio['aplazados'] }}
                            </div>
                        <div class="grid-item p-3 text-center">
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
                                <div class="grid-item p-3 border-r border-gray-200 font-medium servicio-nombre">
                                    {{ $subservicio['nombre'] }}
                                </div>
                                <div class="grid-item p-3 border-r border-gray-200 text-center transition-count" data-pendientes="{{ $subservicio['pendientes'] }}">{{ $subservicio['pendientes'] }}</div>
                                <div class="grid-item p-3 border-r border-gray-200 text-center transition-count cursor-pointer hover:bg-blue-50" 
                                     data-aplazados="{{ $subservicio['aplazados'] }}"
                                     data-servicio-id="{{ $subservicio['id'] }}"
                                     onclick="abrirModalAplazados({{ $subservicio['id'] }}, '{{ $subservicio['nombre'] }}', {{ $subservicio['aplazados'] }})"
                                     style="{{ $subservicio['aplazados'] > 0 ? 'cursor: pointer; font-weight: bold; color: #f97316;' : '' }}">
                                    {{ $subservicio['aplazados'] }}
                                </div>
                                <div class="grid-item p-3 text-center">
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

            <!-- HISTORIAL DE TURNOS LLAMADOS -->
            <div class="historial-table mt-8 border border-gray-300 rounded overflow-hidden">
                <div class="text-white historial-grid text-sm font-medium" style="background-color: #064b9e;">
                    <div class="grid-item p-3 border-r border-blue-400">TURNO</div>
                    <div class="grid-item p-3 border-r border-blue-400">SERVICIO</div>
                    <div class="grid-item p-3 border-r border-blue-400">ESTADO</div>
                    <div class="grid-item p-3 border-r border-blue-400">HORA</div>
                    <div class="grid-item p-3 border-r border-blue-400">TIEMPO</div>
                    <div class="grid-item p-3 text-center">ACCIONES</div>
                </div>

                <div id="historial-turnos-tbody" class="bg-white">
                    <div class="historial-grid text-sm bg-white p-6 text-center text-gray-500">
                        <div class="col-span-6 flex items-center justify-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 0 1 4 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-sm">Cargando historial...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel Derecho -->
        <div class="w-1/2 text-white relative overflow-hidden panel-right" style="background: linear-gradient(135deg, #064b9e 0%, #0a5bb8 100%);">
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
                    <div class="turno-display text-6xl font-light mb-8 tracking-wider" id="turno-actual">TURNO</div>
                    <div class="servicio-display text-4xl font-light tracking-wider" id="servicio-actual">SERVICIO</div>
                </div>

                <!-- Botones de acción -->
                <div class="action-buttons flex justify-center gap-4 mb-8 flex-wrap">
                    <button
                        id="btn-rellamar"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-full text-lg transition-colors duration-200"
                        style="display: none;"
                        title="Volver a llamar el turno en el televisor"
                    >
                        VOLVER A LLAMAR
                    </button>
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
                    <button
                        id="btn-transferir"
                        class="bg-purple-600 hover:bg-purple-700 text-white px-8 py-3 rounded-full text-lg transition-colors duration-200"
                        style="display: none;"
                    >
                        TRANSFERIR
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



    <!-- Modal para Turnos Aplazados -->
    <div id="modal-aplazados" class="fixed inset-0 hidden items-center justify-center z-50 backdrop-blur-sm bg-black/30">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-2xl mx-4 animate-fade-in max-h-[80vh] flex flex-col">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900">Turnos Aplazados</h3>
                    <button id="btn-cerrar-modal-aplazados" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <p id="modal-aplazados-servicio" class="text-sm text-gray-600 mt-2"></p>
            </div>

            <div class="flex-1 overflow-y-auto p-6">
                <div id="modal-aplazados-lista" class="space-y-2">
                    <!-- Los turnos se cargarán aquí -->
                    <div class="text-center text-gray-500 py-8">
                        <svg class="animate-spin mx-auto h-8 w-8 text-blue-500 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 0 1 4 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Cargando turnos aplazados...</span>
                    </div>
                </div>
            </div>

            <div class="p-6 border-t border-gray-200">
                <div class="flex justify-end gap-3">
                    <button 
                        id="btn-cerrar-modal-aplazados-footer"
                        class="px-6 py-2 bg-gray-300 text-gray-700 rounded-md font-medium hover:bg-gray-400 transition-colors"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Transferir Turno -->
    <div id="modal-transferir" class="fixed inset-0 hidden items-center justify-center z-50 backdrop-blur-sm bg-black/30">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-lg mx-4 animate-fade-in">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900">Transferir Turno</h3>
                    <button id="btn-cerrar-modal-transferir" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <p id="modal-transferir-turno" class="text-sm text-gray-600 mt-2"></p>
            </div>

            <div class="p-6">
                <div class="mb-4">
                    <label for="select-servicio-destino" class="block text-sm font-medium text-gray-700 mb-2">
                        Seleccionar servicio destino:
                    </label>
                    <select id="select-servicio-destino" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="">-- Seleccione un servicio --</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Posición en la cola:
                    </label>
                    <div class="flex gap-4">
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" name="posicion-cola" value="primero" class="w-4 h-4 text-purple-600 border-gray-300 focus:ring-purple-500">
                            <span class="ml-2 text-sm text-gray-700">De primero (siguiente en llamar)</span>
                        </label>
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" name="posicion-cola" value="ultimo" checked class="w-4 h-4 text-purple-600 border-gray-300 focus:ring-purple-500">
                            <span class="ml-2 text-sm text-gray-700">De último</span>
                        </label>
                    </div>
                </div>

                <div class="bg-purple-50 border border-purple-200 rounded-md p-3 mb-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-purple-600 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-sm text-purple-700">
                            El turno será transferido al servicio seleccionado y quedará disponible para el asesor de ese servicio.
                        </p>
                    </div>
                </div>
            </div>

            <div class="p-6 border-t border-gray-200">
                <div class="flex justify-end gap-3">
                    <button 
                        id="btn-cancelar-transferir"
                        class="px-6 py-2 bg-gray-300 text-gray-700 rounded-md font-medium hover:bg-gray-400 transition-colors"
                    >
                        Cancelar
                    </button>
                    <button 
                        id="btn-confirmar-transferir"
                        class="px-6 py-2 bg-purple-600 text-white rounded-md font-medium hover:bg-purple-700 transition-colors"
                    >
                        Transferir
                    </button>
                </div>
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
        const btnRellamar = document.getElementById('btn-rellamar');
        const btnAtender = document.getElementById('btn-atender');
        const btnAplazar = document.getElementById('btn-aplazar');
        const btnTransferir = document.getElementById('btn-transferir');
        const turnoActualElement = document.getElementById('turno-actual');
        const servicioActualElement = document.getElementById('servicio-actual');
        const tiempoAtencionElement = document.getElementById('tiempo-atencion');
        const serviciosContainer = document.getElementById('servicios-container');
        
        // Elementos del modal de transferencia
        const modalTransferir = document.getElementById('modal-transferir');
        const btnCerrarModalTransferir = document.getElementById('btn-cerrar-modal-transferir');
        const btnCancelarTransferir = document.getElementById('btn-cancelar-transferir');
        const btnConfirmarTransferir = document.getElementById('btn-confirmar-transferir');
        const selectServicioDestino = document.getElementById('select-servicio-destino');
        const modalTransferirTurno = document.getElementById('modal-transferir-turno');
        let turnoATransferir = null; // Almacena el código del turno a transferir
        
        // Variables para transferencia programada (se ejecuta al marcar como atendido)
        let transferenciaProgramada = null; // { servicioDestinoId, servicioDestinoNombre, posicion }

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
                            <div class="grid-item p-3 border-r border-gray-200 font-medium servicio-nombre flex items-center">
                                ${servicio.tiene_hijos ? `
                                    <svg class="w-4 h-4 mr-1 chevron-icon text-blue-700 ${estaExpandido ? 'rotate-chevron' : ''}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                ` : ''}
                                ${servicio.nombre}
                            </div>
                            <div class="grid-item p-3 border-r border-gray-200 text-center transition-count" data-pendientes="${servicio.pendientes}">
                                ${servicio.pendientes}
                            </div>
                            <div class="grid-item p-3 border-r border-gray-200 text-center transition-count cursor-pointer hover:bg-blue-50" 
                                 data-aplazados="${servicio.aplazados}"
                                 data-servicio-id="${servicio.id}"
                                 onclick="abrirModalAplazados(${servicio.id}, '${servicio.nombre.replace(/'/g, "\\'")}', ${servicio.aplazados})"
                                 style="${servicio.aplazados > 0 ? 'cursor: pointer; font-weight: bold; color: #f97316;' : ''}">
                                ${servicio.aplazados}
                            </div>
                            <div class="grid-item p-3 text-center">
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
                                    <div class="grid-item p-3 border-r border-gray-200 font-medium servicio-nombre">
                                        ${subservicio.nombre}
                                    </div>
                                    <div class="grid-item p-3 border-r border-gray-200 text-center transition-count" data-pendientes="${subservicio.pendientes}">${subservicio.pendientes}</div>
                                    <div class="grid-item p-3 border-r border-gray-200 text-center transition-count cursor-pointer hover:bg-blue-50" 
                                         data-aplazados="${subservicio.aplazados}"
                                         data-servicio-id="${subservicio.id}"
                                         onclick="abrirModalAplazados(${subservicio.id}, '${subservicio.nombre.replace(/'/g, "\\'")}', ${subservicio.aplazados})"
                                         style="${subservicio.aplazados > 0 ? 'cursor: pointer; font-weight: bold; color: #f97316;' : ''}">
                                        ${subservicio.aplazados}
                                    </div>
                                    <div class="grid-item p-3 text-center">
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

        // Función para mostrar notificaciones
        function showNotification(title, message, type = 'success') {
            modalTitle.textContent = title;
            modalMessage.textContent = message;
            
            // Ocultar todos los iconos primero
            successIcon.classList.add('hidden');
            errorIcon.classList.add('hidden');
            
            // Mostrar el icono apropiado
            if (type === 'success') {
                successIcon.classList.remove('hidden');
                modalIcon.classList.remove('bg-red-100');
                modalIcon.classList.add('bg-green-100');
            } else {
                errorIcon.classList.remove('hidden');
                modalIcon.classList.remove('bg-green-100');
                modalIcon.classList.add('bg-red-100');
            }
            
            // Mostrar modal
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

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

            btnRellamar.style.display = 'block';
            btnAtender.style.display = 'block';
            btnAplazar.style.display = 'block';
            btnTransferir.style.display = 'block';

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

            btnRellamar.style.display = 'none';
            btnAtender.style.display = 'none';
            btnAplazar.style.display = 'none';
            btnTransferir.style.display = 'none';
            
            // Limpiar transferencia programada
            transferenciaProgramada = null;
            btnTransferir.textContent = 'TRANSFERIR';
            btnTransferir.classList.remove('bg-purple-800');
            btnTransferir.classList.add('bg-purple-600');
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

        // Event listener para el botón "Volver a llamar"
        btnRellamar.addEventListener('click', async function() {
            if (!turnoActual) return;

            // Deshabilitar botón temporalmente para evitar múltiples clics
            btnRellamar.disabled = true;
            btnRellamar.textContent = 'LLAMANDO...';

            try {
                const response = await fetch('{{ route("asesor.rellamar-turno") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        codigo_completo: turnoActual.codigo_completo
                    })
                });

                const data = await response.json();

                if (data.success) {
                    mostrarModal('Éxito', data.message || 'Turno llamado nuevamente en el televisor', 'success');
                } else {
                    mostrarModal('Error', data.message || 'No se pudo volver a llamar el turno', 'error');
                }
            } catch (error) {
                console.error('Error al rellamar turno:', error);
                mostrarModal('Error', 'Error de conexión al intentar rellamar el turno', 'error');
            } finally {
                // Rehabilitar botón
                btnRellamar.disabled = false;
                btnRellamar.textContent = 'VOLVER A LLAMAR';
            }
        });

        btnAtender.addEventListener('click', async function() {
            if (!turnoActual) return;

            // Detener el contador
            if (intervalTiempo) {
                clearInterval(intervalTiempo);
            }

            try {
                // Primero marcar como atendido
                const responseAtender = await fetch('{{ route("asesor.marcar-atendido") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        turno_id: turnoActual.id,
                        duracion: tiempoTranscurrido
                    })
                });
                
                const dataAtender = await responseAtender.json();
                
                if (!dataAtender.success) {
                    mostrarModal('Error', dataAtender.message, 'error');
                    return;
                }
                
                const duracionFormateada = dataAtender.duracion ? formatearTiempo(dataAtender.duracion) : '00:00';
                
                // Sin transferencia, solo marcar como atendido
                limpiarInterfazTurno();
                mostrarModal('Turno Atendido', `Turno atendido correctamente. Tiempo de atención: ${duracionFormateada} min`);
                
                actualizarEstadisticasServicios();
                cargarHistorialTurnos();
                
            } catch (error) {
                console.error('Error:', error);
                mostrarModal('Error', 'Error de conexión', 'error');
            }
        });

        btnAplazar.addEventListener('click', function() {
            if (!turnoActual) return;

            // Detener el contador
            if (intervalTiempo) {
                clearInterval(intervalTiempo);
            }

            fetch('{{ route("asesor.aplazar-turno") }}', {
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

        // ===== FUNCIONES PARA HISTORIAL DE TURNOS =====

        // Cargar historial de turnos llamados
        function cargarHistorialTurnos() {
            console.log('🔄 Cargando historial de turnos...');

            fetch('/asesor/historial-turnos')
                .then(response => response.json())
                .then(data => {
                    console.log('📋 Historial recibido:', data);

                    if (data.success) {
                        actualizarTablaHistorial(data.turnos);
                    } else {
                        console.error('❌ Error al cargar historial:', data.message);
                        mostrarErrorHistorial('Error al cargar el historial');
                    }
                })
                .catch(error => {
                    console.error('❌ Error en petición de historial:', error);
                    mostrarErrorHistorial('Error de conexión');
                });
        }

        // Actualizar tabla de historial
        function actualizarTablaHistorial(turnos) {
            const tbody = document.getElementById('historial-turnos-tbody');

            if (!turnos || turnos.length === 0) {
                tbody.innerHTML = `
                    <div class="historial-grid text-sm bg-white p-6 text-center text-gray-500">
                        <div class="col-span-6">
                            <span class="text-sm">No hay turnos llamados hoy</span>
                        </div>
                    </div>
                `;
                return;
            }

            tbody.innerHTML = turnos.map(turno => {
                const estadoColor = {
                    'llamado': 'bg-yellow-100 text-yellow-800',
                    'atendido': 'bg-green-100 text-green-800',
                    'aplazado': 'bg-orange-100 text-orange-800'
                }[turno.estado] || 'bg-gray-100 text-gray-800';

                const tiempoTranscurrido = turno.tiempo_transcurrido || '00:00';
                const horaLlamado = new Date(turno.fecha_llamado).toLocaleTimeString('es-ES', {
                    hour: '2-digit',
                    minute: '2-digit'
                });

                let acciones = '';
                if (turno.estado === 'llamado') {
                    acciones = `
                        <div class="grid grid-cols-2 gap-1 w-full">
                            <button onclick="rellamarTurnoDesdeHistorial('${turno.codigo_completo}')"
                                    class="historial-action-btn px-1 py-1 bg-blue-500 text-white text-[10px] rounded hover:bg-blue-600 w-full flex items-center justify-center transition-colors" title="Volver a llamar">
                                Rellamar
                            </button>
                            <button onclick="marcarAtendidoDesdeHistorial('${turno.codigo_completo}')"
                                    class="historial-action-btn px-1 py-1 bg-green-500 text-white text-[10px] rounded hover:bg-green-600 w-full flex items-center justify-center transition-colors" title="Marcar como atendido">
                                Atender
                            </button>
                            <button onclick="aplazarTurnoDesdeHistorial('${turno.codigo_completo}')"
                                    class="historial-action-btn px-1 py-1 bg-orange-500 text-white text-[10px] rounded hover:bg-orange-600 w-full flex items-center justify-center transition-colors" title="Aplazar turno">
                                Aplazar
                            </button>
                            <button onclick="abrirModalTransferir('${turno.codigo_completo}')"
                                    class="historial-action-btn px-1 py-1 bg-purple-500 text-white text-[10px] rounded hover:bg-purple-600 w-full flex items-center justify-center transition-colors" title="Transferir turno">
                                Transferir
                            </button>
                        </div>
                    `;
                } else {
                    acciones = `
                        <div class="flex justify-center items-center h-full">
                            <span class="text-xs text-gray-500">-</span>
                        </div>
                    `;
                }

                return `
                    <div class="historial-grid text-sm bg-white border-b border-gray-200 hover:bg-gray-50">
                        <div class="grid-item p-3 border-r border-gray-200 font-medium flex items-center">${turno.codigo_completo}</div>
                        <div class="grid-item p-3 border-r border-gray-200 flex items-center">${turno.servicio?.nombre || 'N/A'}</div>
                        <div class="grid-item p-3 border-r border-gray-200 flex items-center">
                            <span class="px-2 py-1 rounded-full text-xs font-medium ${estadoColor}">
                                ${turno.estado.toUpperCase()}
                            </span>
                        </div>
                        <div class="grid-item p-3 border-r border-gray-200 flex items-center">${horaLlamado}</div>
                        <div class="grid-item p-3 border-r border-gray-200 font-mono flex items-center">${tiempoTranscurrido}</div>
                        <div class="grid-item p-2 flex items-center justify-center">
                            ${acciones}
                        </div>
                    </div>
                `;
            }).join('');
        }

        // Mostrar error en historial
        function mostrarErrorHistorial(mensaje) {
            const tbody = document.getElementById('historial-turnos-tbody');
            tbody.innerHTML = `
                <div class="historial-grid text-sm bg-white p-6 text-center text-red-500">
                    <div class="col-span-6">
                        <span class="text-sm">❌ ${mensaje}</span>
                    </div>
                </div>
            `;
        }

        // Rellamar turno desde historial (volver a emitir sonido en TV)
        function rellamarTurnoDesdeHistorial(codigoCompleto) {
            console.log('🔊 Rellamando turno desde historial:', codigoCompleto);

            fetch('/asesor/rellamar-turno', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    codigo_completo: codigoCompleto
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('🔊 Turno rellamado exitosamente');
                    mostrarModal('Éxito', data.message || 'Turno llamado nuevamente en el televisor', 'success');
                } else {
                    console.error('❌ Error al rellamar turno:', data.message);
                    mostrarModal('Error', data.message || 'No se pudo volver a llamar el turno', 'error');
                }
            })
            .catch(error => {
                console.error('❌ Error en petición:', error);
                mostrarModal('Error', 'Error de conexión al intentar rellamar el turno', 'error');
            });
        }

        // Marcar turno como atendido desde historial
        function marcarAtendidoDesdeHistorial(codigoCompleto) {
            console.log('✅ Marcando turno como atendido desde historial:', codigoCompleto);

            fetch('/asesor/marcar-atendido', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    codigo_completo: codigoCompleto
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('🔍 Respuesta completa del servidor:', data);
                if (data.success) {
                    console.log('✅ Turno marcado como atendido');
                    console.log('⏱️ Duración retornada:', data.duracion, 'segundos');

                    // Verificar si el turno marcado como atendido es el turno actual
                    if (turnoActual && turnoActual.codigo_completo === codigoCompleto) {
                        console.log('🔄 El turno atendido es el turno actual, limpiando interfaz...');
                        limpiarInterfazTurno();
                    }

                    cargarHistorialTurnos(); // Recargar historial
                    actualizarEstadisticasServicios(); // Actualizar estadísticas
                } else {
                    console.error('❌ Error al marcar como atendido:', data.message);
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('❌ Error en petición:', error);
                alert('Error de conexión');
            });
        }

        // Aplazar turno desde historial
        function aplazarTurnoDesdeHistorial(codigoCompleto) {
            console.log('⏸️ Aplazando turno desde historial:', codigoCompleto);

            fetch('/asesor/aplazar-turno', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    codigo_completo: codigoCompleto
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('⏸️ Turno aplazado');

                    // Verificar si el turno aplazado es el turno actual
                    if (turnoActual && turnoActual.codigo_completo === codigoCompleto) {
                        console.log('🔄 El turno aplazado es el turno actual, limpiando interfaz...');
                        limpiarInterfazTurno();
                    }

                    cargarHistorialTurnos(); // Recargar historial
                    actualizarEstadisticasServicios(); // Actualizar estadísticas
                } else {
                    console.error('❌ Error al aplazar:', data.message);
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('❌ Error en petición:', error);
                alert('Error de conexión');
            });
        }

        // Volver a llamar turno
        function volverLlamarTurno(codigoCompleto) {
            console.log('🔊 Volviendo a llamar turno:', codigoCompleto);

            fetch('/asesor/volver-llamar-turno', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    codigo_completo: codigoCompleto
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('🔊 Turno vuelto a llamar');
                    // No necesitamos recargar el historial, solo confirmar
                    alert('Turno vuelto a llamar correctamente');
                } else {
                    console.error('❌ Error al volver a llamar:', data.message);
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('❌ Error en petición:', error);
                alert('Error de conexión');
            });
        }

        // ===== FUNCIONES PARA TRANSFERIR TURNO =====

        // Cargar servicios disponibles para transferir
        async function cargarServiciosParaTransferir() {
            try {
                const response = await fetch('/api/asesor/servicios-activos');
                const data = await response.json();
                
                selectServicioDestino.innerHTML = '<option value="">-- Seleccione un servicio --</option>';
                
                if (data.success && data.servicios) {
                    data.servicios.forEach(servicio => {
                        const option = document.createElement('option');
                        option.value = servicio.id;
                        option.textContent = `${servicio.codigo} - ${servicio.nombre}`;
                        selectServicioDestino.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error al cargar servicios:', error);
                selectServicioDestino.innerHTML = '<option value="">Error al cargar servicios</option>';
            }
        }

        // Abrir modal de transferencia
        function abrirModalTransferir(codigoCompleto) {
            turnoATransferir = codigoCompleto;
            modalTransferirTurno.textContent = `Turno a transferir: ${codigoCompleto}`;
            
            // Resetear formulario
            selectServicioDestino.value = '';
            document.querySelector('input[name="posicion-cola"][value="ultimo"]').checked = true;
            
            // Cargar servicios
            cargarServiciosParaTransferir();
            
            // Mostrar modal
            modalTransferir.classList.remove('hidden');
            modalTransferir.classList.add('flex');
        }

        // Cerrar modal de transferencia
        function cerrarModalTransferir() {
            modalTransferir.classList.remove('flex');
            modalTransferir.classList.add('hidden');
            turnoATransferir = null;
        }

        // Ejecutar transferencia inmediatamente
        async function programarTransferencia() {
            const servicioDestinoId = selectServicioDestino.value;
            const posicion = document.querySelector('input[name="posicion-cola"]:checked').value;
            
            if (!servicioDestinoId) {
                mostrarModal('Error', 'Debe seleccionar un servicio destino', 'error');
                return;
            }
            
            if (!turnoATransferir) {
                mostrarModal('Error', 'No se ha seleccionado un turno para transferir', 'error');
                return;
            }
            
            // Obtener el nombre del servicio seleccionado
            const servicioOption = selectServicioDestino.options[selectServicioDestino.selectedIndex];
            const servicioDestinoNombre = servicioOption.textContent;
            
            // Guardar código en variable local antes de cerrar el modal (que limpia turnoATransferir)
            const codigoTurnoParaTransferir = turnoATransferir;

            // Cerrar modal
            cerrarModalTransferir();
            
            try {
                // Hacer la petición de transferencia directa
                console.log('Enviando transferencia:', {
                    codigo_completo: codigoTurnoParaTransferir,
                    servicio_destino_id: servicioDestinoId,
                    posicion: posicion
                });

                const response = await fetch('/asesor/transferir-turno', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        codigo_completo: codigoTurnoParaTransferir,
                        servicio_destino_id: servicioDestinoId,
                        posicion: posicion
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Si el turno transferido era el actual, limpiar la interfaz
                    if (turnoActual && turnoActual.codigo_completo === codigoTurnoParaTransferir) {
                        limpiarInterfazTurno();
                    }
                    
                    mostrarModal(
                        'Turno Transferido', 
                        `Turno transferido exitosamente a ${servicioDestinoNombre}.`,
                        'success'
                    );
                    
                    actualizarEstadisticasServicios();
                    cargarHistorialTurnos();
                } else {
                    console.error('Error del servidor:', data);
                    let errorMessage = data.message;
                    
                    // Si hay errores de validación específicos
                    if (data.errors) {
                        const errorDetails = Object.values(data.errors).flat().join('\n');
                        errorMessage += '\n' + errorDetails;
                    }
                    
                    mostrarModal('Error', errorMessage, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarModal('Error', 'Error de conexión al transferir', 'error');
            }
        }
        
        // Cancelar transferencia programada
        function cancelarTransferenciaProgramada() {
            transferenciaProgramada = null;
            btnTransferir.textContent = 'TRANSFERIR';
            btnTransferir.classList.remove('bg-purple-800');
            btnTransferir.classList.add('bg-purple-600');
            mostrarModal('Información', 'Transferencia cancelada', 'success');
        }

        // Event listeners para modal de transferencia
        btnCerrarModalTransferir.addEventListener('click', cerrarModalTransferir);
        btnCancelarTransferir.addEventListener('click', cerrarModalTransferir);
        btnConfirmarTransferir.addEventListener('click', programarTransferencia);
        
        // Event listener para el botón principal de transferir
        btnTransferir.addEventListener('click', function() {
            // Si ya hay una transferencia programada, preguntar si quiere cancelarla
            if (transferenciaProgramada) {
                if (confirm('Ya hay una transferencia programada a "' + transferenciaProgramada.servicioDestinoNombre + '". ¿Desea cancelarla o cambiarla?')) {
                    if (turnoActual && turnoActual.codigo_completo) {
                        abrirModalTransferir(turnoActual.codigo_completo);
                    }
                }
            } else if (turnoActual && turnoActual.codigo_completo) {
                abrirModalTransferir(turnoActual.codigo_completo);
            } else {
                mostrarModal('Error', 'No hay turno seleccionado para transferir', 'error');
            }
        });

        // ===== FUNCIONES PARA MODAL DE TURNOS APLAZADOS =====

        const modalAplazados = document.getElementById('modal-aplazados');
        const btnCerrarModalAplazados = document.getElementById('btn-cerrar-modal-aplazados');
        const btnCerrarModalAplazadosFooter = document.getElementById('btn-cerrar-modal-aplazados-footer');
        const modalAplazadosLista = document.getElementById('modal-aplazados-lista');
        const modalAplazadosServicio = document.getElementById('modal-aplazados-servicio');
        let servicioIdActual = null;

        function abrirModalAplazados(servicioId, nombreServicio, cantidadAplazados) {
            if (cantidadAplazados === 0) {
                mostrarModal('Sin turnos aplazados', 'No hay turnos aplazados para este servicio', 'error');
                return;
            }

            servicioIdActual = servicioId;
            modalAplazadosServicio.textContent = `Servicio: ${nombreServicio} (${cantidadAplazados} turno${cantidadAplazados > 1 ? 's' : ''} aplazado${cantidadAplazados > 1 ? 's' : ''})`;
            
            // Mostrar modal
            modalAplazados.classList.remove('hidden');
            modalAplazados.classList.add('flex');

            // Cargar turnos aplazados
            cargarTurnosAplazados(servicioId);
        }

        function cerrarModalAplazados() {
            modalAplazados.classList.remove('flex');
            modalAplazados.classList.add('hidden');
            servicioIdActual = null;
        }

        function cargarTurnosAplazados(servicioId) {
            // Mostrar loading
            modalAplazadosLista.innerHTML = `
                <div class="text-center text-gray-500 py-8">
                    <svg class="animate-spin mx-auto h-8 w-8 text-blue-500 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 0 1 4 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Cargando turnos aplazados...</span>
                </div>
            `;

            fetch(`{{ route('asesor.turnos-aplazados') }}?servicio_id=${servicioId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mostrarTurnosAplazados(data.turnos);
                    } else {
                        modalAplazadosLista.innerHTML = `
                            <div class="text-center text-red-500 py-8">
                                <p>Error al cargar turnos aplazados: ${data.message}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error al cargar turnos aplazados:', error);
                    modalAplazadosLista.innerHTML = `
                        <div class="text-center text-red-500 py-8">
                            <p>Error de conexión al cargar turnos aplazados</p>
                        </div>
                    `;
                });
        }

        function mostrarTurnosAplazados(turnos) {
            if (!turnos || turnos.length === 0) {
                modalAplazadosLista.innerHTML = `
                    <div class="text-center text-gray-500 py-8">
                        <p>No hay turnos aplazados para este servicio</p>
                    </div>
                `;
                return;
            }

            const coloresPrioridad = {
                5: 'bg-red-100 text-red-800 border-red-300',
                4: 'bg-orange-100 text-orange-800 border-orange-300',
                3: 'bg-yellow-100 text-yellow-800 border-yellow-300',
                2: 'bg-blue-100 text-blue-800 border-blue-300',
                1: 'bg-green-100 text-green-800 border-green-300'
            };

            modalAplazadosLista.innerHTML = turnos.map(turno => {
                const colorPrioridad = coloresPrioridad[turno.prioridad] || 'bg-gray-100 text-gray-800 border-gray-300';
                
                return `
                    <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <span class="text-xl font-bold text-gray-900">${turno.codigo_completo}</span>
                                <span class="px-2 py-1 text-xs font-medium rounded border ${colorPrioridad}">
                                    Prioridad ${turno.prioridad_letra}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 mt-1">${turno.servicio.nombre}</p>
                        </div>
                        <button 
                            onclick="volverLlamarTurnoAplazado(${turno.id}, '${turno.codigo_completo}')"
                            class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded hover:bg-blue-700 transition-colors ml-4 ${turnoActual ? 'opacity-50 cursor-not-allowed' : ''}"
                            ${turnoActual ? 'disabled' : ''}
                        >
                            Llamar
                        </button>
                    </div>
                `;
            }).join('');
        }

        function volverLlamarTurnoAplazado(turnoId, codigoCompleto) {
            // Verificar si ya hay un turno en proceso
            if (turnoActual) {
                mostrarModal('Turno en Proceso', 
                    `Ya tiene el turno ${turnoActual.codigo_completo} en proceso. Debe marcarlo como "Atendido" antes de llamar otro turno.`,
                    'error');
                return;
            }

            // Deshabilitar botón mientras se procesa
            const botones = document.querySelectorAll(`button[onclick*="${turnoId}"]`);
            botones.forEach(btn => {
                btn.disabled = true;
                btn.classList.add('opacity-50', 'cursor-not-allowed');
            });

            fetch('{{ route("asesor.volver-llamar-turno") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    turno_id: turnoId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Actualizar interfaz con el turno llamado
                    actualizarInterfazTurno(data.turno);
                    
                    // Cerrar modal
                    cerrarModalAplazados();
                    
                    // Mostrar mensaje de éxito
                    mostrarModal('Turno Llamado', data.message);
                    
                    // Actualizar estadísticas y historial
                    actualizarEstadisticasServicios();
                    cargarHistorialTurnos();
                } else {
                    mostrarModal('Error', data.message, 'error');
                    
                    // Rehabilitar botones
                    botones.forEach(btn => {
                        btn.disabled = false;
                        btn.classList.remove('opacity-50', 'cursor-not-allowed');
                    });
                }
            })
            .catch(error => {
                console.error('Error al volver a llamar turno:', error);
                mostrarModal('Error', 'Error de conexión', 'error');
                
                // Rehabilitar botones
                botones.forEach(btn => {
                    btn.disabled = false;
                    btn.classList.remove('opacity-50', 'cursor-not-allowed');
                });
            });
        }

        // Event listeners para el modal de aplazados
        btnCerrarModalAplazados.addEventListener('click', cerrarModalAplazados);
        btnCerrarModalAplazadosFooter.addEventListener('click', cerrarModalAplazados);
        modalAplazados.addEventListener('click', function(e) {
            if (e.target === modalAplazados) {
                cerrarModalAplazados();
            }
        });

        // Función para verificar si hay un turno en proceso al cargar la página
        function verificarTurnoEnProcesoInicial() {
            fetch('{{ route("asesor.verificar-turno-en-proceso") }}')
                .then(response => response.json())
                .then(data => {
                    if (data.turno_en_proceso && data.turno) {
                        console.log('📌 Turno en proceso encontrado:', data.turno);
                        actualizarInterfazTurno(data.turno);
                    }
                })
                .catch(error => {
                    console.error('Error verificando turno en proceso:', error);
                });
        }

        // Inicializar historial al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Inicializando historial de turnos...');

            // Verificar si hay un turno en proceso (importante al recargar la página)
            verificarTurnoEnProcesoInicial();

            // Cargar historial inicial
            cargarHistorialTurnos();

            // Recargar historial cada 5 segundos para ver el tiempo en tiempo real
            setInterval(cargarHistorialTurnos, 5000);
        });
    </script>
</body>
</html>
