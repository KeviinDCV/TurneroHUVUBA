@extends('layouts.admin')

@section('title', 'Dashboard')
@section('content')

                <div class="bg-white rounded-lg shadow-md p-4 md:p-6 max-w-7xl mx-auto">
                    <!-- Header del Dashboard -->
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                        <h1 class="text-xl md:text-2xl font-bold text-gray-800">Dashboard Administrativo</h1>
                        <button
                            onclick="cleanExpiredSessions()"
                            class="bg-hospital-blue hover:bg-hospital-blue-hover text-white px-4 py-2 rounded transition-colors duration-200 flex items-center gap-2 w-full sm:w-auto"
                            id="cleanSessionsBtn">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Limpiar Sesiones
                        </button>
                    </div>

                    <!-- Usuarios Activos -->
                    <div class="overflow-x-auto">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-800">Usuarios Activos</h2>
                                <p id="last-update-time" class="text-xs text-gray-500 mt-1">Cargando...</p>
                            </div>
                        </div>

                        <table class="w-full divide-y divide-gray-200 border border-gray-200 rounded-lg" id="usuarios-activos-table">
                            <thead>
                                <tr class="bg-hospital-blue text-white">
                                    <th class="py-3 px-4 text-left font-semibold">USUARIO</th>
                                    <th class="py-3 px-4 text-left font-semibold">ROL</th>
                                    <th class="py-3 px-4 text-left font-semibold">DISPONIBILIDAD</th>
                                    <th class="py-3 px-4 text-left font-semibold">ESTADO</th>
                                </tr>
                            </thead>
                            <tbody id="usuarios-activos-container" class="divide-y divide-gray-200 bg-white">
                                @if($usuariosActivos->count() > 0)
                                    @foreach($usuariosActivos as $usuario)
                                        <tr class="hover:bg-gray-50">
                                            <td class="py-3 px-4 whitespace-nowrap">
                                                <div class="flex flex-col">
                                                    <span class="text-sm font-medium text-gray-900">{{ $usuario['name'] }}</span>
                                                    <span class="text-xs text-gray-500">Sesión activa: {{ $usuario['tiempo_sesion'] }}</span>
                                                </div>
                                            </td>
                                            <td class="py-3 px-4 whitespace-nowrap">
                                                <span class="px-2 py-1 rounded text-sm {{ $usuario['rol'] === 'Administrador' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                                    {{ $usuario['rol'] }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $usuario['availability'] }}
                                            </td>
                                            <td class="py-3 px-4 whitespace-nowrap">
                                                <span class="px-2 py-1 rounded text-sm
                                                    @if($usuario['status'] === 'DISPONIBLE') bg-green-100 text-green-800
                                                    @elseif($usuario['status'] === 'OCUPADO') bg-yellow-100 text-yellow-800
                                                    @elseif($usuario['status'] === 'EN DESCANSO') bg-blue-100 text-blue-800
                                                    @else bg-red-100 text-red-800
                                                    @endif">
                                                    {{ $usuario['status'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="4" class="py-12 text-center text-gray-500">
                                            <div class="flex flex-col items-center">
                                                <i class="fas fa-users text-4xl mb-4 text-gray-300"></i>
                                                <p class="text-lg font-medium">No hay usuarios activos en este momento</p>
                                                <p class="text-sm">Los usuarios aparecerán aquí cuando inicien sesión</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <!-- Turnos por Servicio -->
                    <div class="mt-8 overflow-x-auto">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-800">Turnos Atendidos por Servicio (Hoy)</h2>
                                <p id="last-update-turnos" class="text-xs text-gray-500 mt-1">Cargando...</p>
                            </div>
                        </div>

                        <table class="w-full divide-y divide-gray-200 border border-gray-200 rounded-lg" id="turnos-servicio-table">
                            <thead>
                                <tr class="bg-hospital-blue text-white">
                                    <th class="py-3 px-4 text-left font-semibold">SERVICIO</th>
                                    <th class="py-3 px-4 text-left font-semibold">TERMINADOS</th>
                                </tr>
                            </thead>
                            <tbody id="turnos-servicio-container" class="divide-y divide-gray-200 bg-white">
                                @if($turnosPorServicio->count() > 0)
                                    @foreach($turnosPorServicio as $turno)
                                        <tr class="hover:bg-gray-50">
                                            <td class="py-3 px-4 whitespace-nowrap">
                                                <span class="text-sm font-medium text-gray-900">{{ $turno['servicio'] }}</span>
                                            </td>
                                            <td class="py-3 px-4 whitespace-nowrap">
                                                <span class="px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                                    {{ $turno['terminados'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="2" class="py-12 text-center text-gray-500">
                                            <div class="flex flex-col items-center">
                                                <i class="fas fa-chart-bar text-4xl mb-4 text-gray-300"></i>
                                                <p class="text-lg font-medium">No hay turnos atendidos hoy</p>
                                                <p class="text-sm">Los turnos aparecerán aquí cuando sean atendidos</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <!-- Turnos por Asesor -->
                    <div class="mt-8 overflow-x-auto">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-800">Turnos Atendidos por Asesor (Hoy)</h2>
                                <p id="last-update-asesores" class="text-xs text-gray-500 mt-1">Cargando...</p>
                            </div>
                        </div>

                        <table class="w-full divide-y divide-gray-200 border border-gray-200 rounded-lg" id="turnos-asesor-table">
                            <thead>
                                <tr class="bg-hospital-blue text-white">
                                    <th class="py-3 px-4 text-left font-semibold">ASESOR</th>
                                    <th class="py-3 px-4 text-left font-semibold">TERMINADOS</th>
                                </tr>
                            </thead>
                            <tbody id="turnos-asesor-container" class="divide-y divide-gray-200 bg-white">
                                @if($turnosPorAsesor->count() > 0)
                                    @foreach($turnosPorAsesor as $turno)
                                        <tr class="hover:bg-gray-50">
                                            <td class="py-3 px-4 whitespace-nowrap">
                                                <span class="text-sm font-medium text-gray-900">{{ $turno['asesor'] }}</span>
                                            </td>
                                            <td class="py-3 px-4 whitespace-nowrap">
                                                <span class="px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                                    {{ $turno['terminados'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="2" class="py-12 text-center text-gray-500">
                                            <div class="flex flex-col items-center">
                                                <i class="fas fa-user-tie text-4xl mb-4 text-gray-300"></i>
                                                <p class="text-lg font-medium">No hay turnos atendidos por asesores hoy</p>
                                                <p class="text-sm">Los turnos aparecerán aquí cuando sean atendidos por asesores</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <!-- Turnos en Cola por Servicio -->
                    <div class="mt-8 overflow-x-auto">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-800">Turnos en Cola por Servicio (Hoy)</h2>
                                <p id="last-update-cola" class="text-xs text-gray-500 mt-1">Cargando...</p>
                            </div>
                        </div>

                        <table class="w-full divide-y divide-gray-200 border border-gray-200 rounded-lg" id="turnos-cola-table">
                            <thead>
                                <tr class="bg-hospital-blue text-white">
                                    <th class="py-3 px-4 text-left font-semibold">SERVICIO</th>
                                    <th class="py-3 px-4 text-left font-semibold">TURNOS EN COLA</th>
                                </tr>
                            </thead>
                            <tbody id="turnos-cola-container" class="divide-y divide-gray-200 bg-white">
                                @if($turnosEnCola->count() > 0)
                                    @foreach($turnosEnCola as $turno)
                                        <tr class="hover:bg-gray-50">
                                            <td class="py-3 px-4 whitespace-nowrap">
                                                <span class="text-sm font-medium text-gray-900">{{ $turno['servicio'] }}</span>
                                            </td>
                                            <td class="py-3 px-4 whitespace-nowrap">
                                                <span class="px-3 py-1 rounded-full text-sm font-medium bg-orange-100 text-orange-800">
                                                    {{ $turno['en_cola'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="2" class="py-12 text-center text-gray-500">
                                            <div class="flex flex-col items-center">
                                                <i class="fas fa-clock text-4xl mb-4 text-gray-300"></i>
                                                <p class="text-lg font-medium">No hay turnos en cola</p>
                                                <p class="text-sm">Los turnos pendientes aparecerán aquí</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    </div>
                </div>

<!-- Modal de confirmación para limpiar sesiones -->
<div id="cleanSessionsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Limpiar Sesiones Expiradas</h3>
                        <p class="text-sm text-gray-500">¿Estás seguro de que deseas limpiar todas las sesiones expiradas?</p>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mb-6">
                    Esta acción eliminará las sesiones que ya no existen o han expirado, liberando las cajas ocupadas por usuarios desconectados.
                </p>
                <div class="flex justify-end space-x-3">
                    <button
                        onclick="closeCleanSessionsModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        Cancelar
                    </button>
                    <button
                        onclick="confirmCleanSessions()"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        Limpiar Sesiones
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de resultado -->
<div id="resultModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div id="resultIcon" class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center">
                        <!-- Icon will be set by JavaScript -->
                    </div>
                    <div class="ml-4">
                        <h3 id="resultTitle" class="text-lg font-medium text-gray-900"></h3>
                        <p id="resultMessage" class="text-sm text-gray-500"></p>
                    </div>
                </div>
                <div id="resultDetails" class="text-sm text-gray-600 mb-6 hidden">
                    <!-- Details will be populated by JavaScript -->
                </div>
                <div class="flex justify-end">
                    <button
                        onclick="closeResultModal()"
                        class="px-4 py-2 text-sm font-medium text-white bg-hospital-blue border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function cleanExpiredSessions() {
    document.getElementById('cleanSessionsModal').classList.remove('hidden');
}

function closeCleanSessionsModal() {
    document.getElementById('cleanSessionsModal').classList.add('hidden');
}

function closeResultModal() {
    document.getElementById('resultModal').classList.add('hidden');
}

function confirmCleanSessions() {
    const btn = document.getElementById('cleanSessionsBtn');
    const originalText = btn.innerHTML;

    // Deshabilitar botón y mostrar loading
    btn.disabled = true;
    btn.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Limpiando...';

    // Cerrar modal de confirmación
    closeCleanSessionsModal();

    fetch('{{ route("admin.clean-sessions") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        // Restaurar botón
        btn.disabled = false;
        btn.innerHTML = originalText;

        // Mostrar resultado
        showResult(data);
    })
    .catch(error => {
        // Restaurar botón
        btn.disabled = false;
        btn.innerHTML = originalText;

        // Mostrar error
        showResult({
            success: false,
            message: 'Error de conexión: ' + error.message
        });
    });
}

function showResult(data) {
    const modal = document.getElementById('resultModal');
    const icon = document.getElementById('resultIcon');
    const title = document.getElementById('resultTitle');
    const message = document.getElementById('resultMessage');
    const details = document.getElementById('resultDetails');

    if (data.success) {
        icon.className = 'flex-shrink-0 w-10 h-10 rounded-full bg-green-100 flex items-center justify-center';
        icon.innerHTML = '<svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
        title.textContent = 'Limpieza Completada';
        message.textContent = data.message;

        if (data.data) {
            details.innerHTML = `
                <ul class="list-disc list-inside space-y-1">
                    <li>Usuarios limpiados: ${data.data.usuarios_limpiados}</li>
                    <li>Cajas liberadas: ${data.data.cajas_liberadas}</li>
                    <li>Sesiones expiradas eliminadas: ${data.data.sesiones_expiradas_eliminadas}</li>
                </ul>
            `;
            details.classList.remove('hidden');
        }
    } else {
        icon.className = 'flex-shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center';
        icon.innerHTML = '<svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
        title.textContent = 'Error';
        message.textContent = data.message;
        details.classList.add('hidden');
    }

    modal.classList.remove('hidden');
}

// Variables para controlar la actualización automática
let autoUpdateInterval;
let lastUpdateTime = new Date();

// Función para actualizar usuarios activos
function actualizarUsuariosActivos() {
    const container = document.getElementById('usuarios-activos-container');
    const lastUpdateElement = document.getElementById('last-update-time');

    fetch('{{ route('api.admin.usuarios-activos') }}')
        .then(response => response.json())
        .then(usuarios => {
            let html = '';

            if (usuarios.length > 0) {
                usuarios.forEach(usuario => {
                    let statusClass = '';
                    if (usuario.status === 'DISPONIBLE') {
                        statusClass = 'bg-green-100 text-green-800';
                    } else if (usuario.status === 'OCUPADO') {
                        statusClass = 'bg-yellow-100 text-yellow-800';
                    } else if (usuario.status === 'EN DESCANSO') {
                        statusClass = 'bg-blue-100 text-blue-800';
                    } else {
                        statusClass = 'bg-red-100 text-red-800';
                    }

                    let rolClass = usuario.rol === 'Administrador' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800';

                    html += `
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-gray-900">${usuario.name}</span>
                                    <span class="text-xs text-gray-500">Sesión activa: ${usuario.tiempo_sesion}</span>
                                </div>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="px-2 py-1 rounded text-sm ${rolClass}">
                                    ${usuario.rol}
                                </span>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap text-sm text-gray-900">
                                ${usuario.availability}
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="px-2 py-1 rounded text-sm ${statusClass}">
                                    ${usuario.status}
                                </span>
                            </td>
                        </tr>
                    `;
                });
            } else {
                html = `
                    <tr>
                        <td colspan="4" class="py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-users text-4xl mb-4 text-gray-300"></i>
                                <p class="text-lg font-medium">No hay usuarios activos en este momento</p>
                                <p class="text-sm">Los usuarios aparecerán aquí cuando inicien sesión</p>
                            </div>
                        </td>
                    </tr>
                `;
            }

            container.innerHTML = html;

            // Actualizar timestamp de última actualización
            lastUpdateTime = new Date();
            if (lastUpdateElement) {
                lastUpdateElement.textContent = `Última actualización: ${lastUpdateTime.toLocaleTimeString()}`;
            }
        })
        .catch(error => {
            console.error('Error al actualizar usuarios:', error);
            // No mostrar errores en actualizaciones automáticas para no ser intrusivo
        });
}

// Función para actualizar turnos por servicio
function actualizarTurnosPorServicio() {
    const container = document.getElementById('turnos-servicio-container');
    const lastUpdateElement = document.getElementById('last-update-turnos');

    fetch('{{ route('api.admin.turnos-por-servicio') }}')
        .then(response => response.json())
        .then(turnos => {
            let html = '';

            if (turnos.length > 0) {
                turnos.forEach(turno => {
                    html += `
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900">${turno.servicio}</span>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    ${turno.terminados}
                                </span>
                            </td>
                        </tr>
                    `;
                });
            } else {
                html = `
                    <tr>
                        <td colspan="2" class="py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-chart-bar text-4xl mb-4 text-gray-300"></i>
                                <p class="text-lg font-medium">No hay turnos atendidos hoy</p>
                                <p class="text-sm">Los turnos aparecerán aquí cuando sean atendidos</p>
                            </div>
                        </td>
                    </tr>
                `;
            }

            container.innerHTML = html;

            // Actualizar timestamp de última actualización
            if (lastUpdateElement) {
                lastUpdateElement.textContent = `Última actualización: ${new Date().toLocaleTimeString()}`;
            }
        })
        .catch(error => {
            console.error('Error al actualizar turnos por servicio:', error);
            // No mostrar errores en actualizaciones automáticas para no ser intrusivo
        });
}

// Función para actualizar turnos por asesor
function actualizarTurnosPorAsesor() {
    const container = document.getElementById('turnos-asesor-container');
    const lastUpdateElement = document.getElementById('last-update-asesores');

    fetch('{{ route('api.admin.turnos-por-asesor') }}')
        .then(response => response.json())
        .then(turnos => {
            let html = '';

            if (turnos.length > 0) {
                turnos.forEach(turno => {
                    html += `
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900">${turno.asesor}</span>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                    ${turno.terminados}
                                </span>
                            </td>
                        </tr>
                    `;
                });
            } else {
                html = `
                    <tr>
                        <td colspan="2" class="py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-user-tie text-4xl mb-4 text-gray-300"></i>
                                <p class="text-lg font-medium">No hay turnos atendidos por asesores hoy</p>
                                <p class="text-sm">Los turnos aparecerán aquí cuando sean atendidos por asesores</p>
                            </div>
                        </td>
                    </tr>
                `;
            }

            container.innerHTML = html;

            // Actualizar timestamp de última actualización
            if (lastUpdateElement) {
                lastUpdateElement.textContent = `Última actualización: ${new Date().toLocaleTimeString()}`;
            }
        })
        .catch(error => {
            console.error('Error al actualizar turnos por asesor:', error);
            // No mostrar errores en actualizaciones automáticas para no ser intrusivo
        });
}

// Función para actualizar turnos en cola por servicio
function actualizarTurnosEnCola() {
    const container = document.getElementById('turnos-cola-container');
    const lastUpdateElement = document.getElementById('last-update-cola');

    fetch('{{ route('api.admin.turnos-en-cola') }}')
        .then(response => response.json())
        .then(turnos => {
            let html = '';

            if (turnos.length > 0) {
                turnos.forEach(turno => {
                    html += `
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900">${turno.servicio}</span>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="px-3 py-1 rounded-full text-sm font-medium bg-orange-100 text-orange-800">
                                    ${turno.en_cola}
                                </span>
                            </td>
                        </tr>
                    `;
                });
            } else {
                html = `
                    <tr>
                        <td colspan="2" class="py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-clock text-4xl mb-4 text-gray-300"></i>
                                <p class="text-lg font-medium">No hay turnos en cola</p>
                                <p class="text-sm">Los turnos pendientes aparecerán aquí</p>
                            </div>
                        </td>
                    </tr>
                `;
            }

            container.innerHTML = html;

            // Actualizar timestamp de última actualización
            if (lastUpdateElement) {
                lastUpdateElement.textContent = `Última actualización: ${new Date().toLocaleTimeString()}`;
            }
        })
        .catch(error => {
            console.error('Error al actualizar turnos en cola:', error);
            // No mostrar errores en actualizaciones automáticas para no ser intrusivo
        });
}

// Función para iniciar la actualización automática
function startAutoUpdate() {
    autoUpdateInterval = setInterval(() => {
        actualizarUsuariosActivos();
        actualizarTurnosPorServicio();
        actualizarTurnosPorAsesor();
        actualizarTurnosEnCola();
    }, 15000); // Actualizar cada 15 segundos
}

// Iniciar actualización automática
startAutoUpdate();

// Actualización inicial
actualizarUsuariosActivos();
actualizarTurnosPorServicio();
actualizarTurnosPorAsesor();
actualizarTurnosEnCola();
</script>

@endsection
