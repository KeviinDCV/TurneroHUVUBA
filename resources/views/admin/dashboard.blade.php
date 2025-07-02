@extends('layouts.admin')

@section('title', 'Dashboard')
@section('content')

                <div class="dashboard-container bg-white rounded-lg shadow-md p-4 md:p-6 max-w-7xl mx-auto">
                    <!-- Header del Dashboard -->
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                        <h1 class="dashboard-title text-xl md:text-2xl font-bold text-gray-800">Dashboard Administrativo</h1>
                        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                            <button
                                onclick="showCleanSessionsOptions()"
                                class="dashboard-button bg-hospital-blue hover:bg-hospital-blue-hover text-white px-4 py-2 rounded transition-colors duration-200 flex items-center gap-2 w-full sm:w-auto"
                                id="cleanSessionsBtn">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Limpiar Sesiones
                            </button>
                            <button
                                onclick="showEmergencyTurnosOptions()"
                                class="dashboard-button bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded transition-colors duration-200 flex items-center gap-2 w-full sm:w-auto"
                                id="emergencyTurnosBtn">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                Emergencia Turnos
                            </button>
                        </div>
                    </div>

                    <!-- Usuarios Activos -->
                    <div class="overflow-x-auto">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h2 class="dashboard-title text-lg font-semibold text-gray-800">Usuarios Activos</h2>
                                <p id="last-update-time" class="text-xs text-gray-500 mt-1">Cargando...</p>
                            </div>
                        </div>

                        <table class="dashboard-table w-full divide-y divide-gray-200 border border-gray-200 rounded-lg" id="usuarios-activos-table">
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
                                                <span class="dashboard-badge px-2 py-1 rounded text-sm {{ $usuario['rol'] === 'Administrador' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                                    {{ $usuario['rol'] }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $usuario['availability'] }}
                                            </td>
                                            <td class="py-3 px-4 whitespace-nowrap">
                                                <span class="dashboard-badge px-2 py-1 rounded text-sm
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
                                                <i class="dashboard-icon fas fa-users text-4xl mb-4 text-gray-300"></i>
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
                    <div class="dashboard-section mt-8 overflow-x-auto">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h2 class="dashboard-title text-lg font-semibold text-gray-800">Turnos Atendidos por Servicio (Hoy)</h2>
                                <p id="last-update-turnos" class="text-xs text-gray-500 mt-1">Cargando...</p>
                            </div>
                        </div>

                        <table class="dashboard-table w-full divide-y divide-gray-200 border border-gray-200 rounded-lg" id="turnos-servicio-table">
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
                                                <span class="dashboard-badge px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                                    {{ $turno['terminados'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="2" class="py-12 text-center text-gray-500">
                                            <div class="flex flex-col items-center">
                                                <i class="dashboard-icon fas fa-chart-bar text-4xl mb-4 text-gray-300"></i>
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
                    <div class="dashboard-section mt-8 overflow-x-auto">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h2 class="dashboard-title text-lg font-semibold text-gray-800">Turnos Atendidos por Asesor (Hoy)</h2>
                                <p id="last-update-asesores" class="text-xs text-gray-500 mt-1">Cargando...</p>
                            </div>
                        </div>

                        <table class="dashboard-table w-full divide-y divide-gray-200 border border-gray-200 rounded-lg" id="turnos-asesor-table">
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
                                                <span class="dashboard-badge px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                                    {{ $turno['terminados'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="2" class="py-12 text-center text-gray-500">
                                            <div class="flex flex-col items-center">
                                                <i class="dashboard-icon fas fa-user-tie text-4xl mb-4 text-gray-300"></i>
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
                    <div class="dashboard-section mt-8 overflow-x-auto">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h2 class="dashboard-title text-lg font-semibold text-gray-800">Turnos en Cola por Servicio (Hoy)</h2>
                                <p id="last-update-cola" class="text-xs text-gray-500 mt-1">Cargando...</p>
                            </div>
                        </div>

                        <table class="dashboard-table w-full divide-y divide-gray-200 border border-gray-200 rounded-lg" id="turnos-cola-table">
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
                                                <span class="dashboard-badge px-3 py-1 rounded-full text-sm font-medium bg-orange-100 text-orange-800">
                                                    {{ $turno['en_cola'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="2" class="py-12 text-center text-gray-500">
                                            <div class="flex flex-col items-center">
                                                <i class="dashboard-icon fas fa-clock text-4xl mb-4 text-gray-300"></i>
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

<!-- Modal de opciones para limpiar sesiones -->
<div id="cleanSessionsModal" class="fixed inset-0 modal-overlay hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center mb-6">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Opciones de Limpieza de Sesiones</h3>
                        <p class="text-sm text-gray-500">Selecciona cómo deseas limpiar las sesiones</p>
                    </div>
                </div>

                <!-- Opciones de limpieza -->
                <div class="space-y-4 mb-6">
                    <!-- Opción 1: Limpiar todas las sesiones expiradas -->
                    <div class="border rounded-lg p-4 hover:bg-gray-50 cursor-pointer" onclick="selectCleanOption('expired')">
                        <div class="flex items-center">
                            <input type="radio" name="cleanOption" value="expired" id="cleanExpired" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                            <label for="cleanExpired" class="ml-3 block text-sm font-medium text-gray-700 cursor-pointer">
                                Limpiar solo sesiones expiradas
                            </label>
                        </div>
                        <p class="ml-7 text-sm text-gray-500 mt-1">
                            Elimina únicamente las sesiones que han expirado (más de 15 minutos) o que ya no existen en la base de datos. <strong>También libera las cajas asignadas</strong> a estos usuarios.
                        </p>
                    </div>

                    <!-- Opción 2: Limpiar todas las sesiones -->
                    <div class="border rounded-lg p-4 hover:bg-gray-50 cursor-pointer" onclick="selectCleanOption('all')">
                        <div class="flex items-center">
                            <input type="radio" name="cleanOption" value="all" id="cleanAll" class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300">
                            <label for="cleanAll" class="ml-3 block text-sm font-medium text-gray-700 cursor-pointer">
                                Limpiar todas las sesiones
                            </label>
                        </div>
                        <p class="ml-7 text-sm text-gray-500 mt-1">
                            <span class="text-red-600 font-medium">¡Cuidado!</span> Esto cerrará la sesión de todos los usuarios activos, incluyendo asesores que estén trabajando. <strong>También liberará todas las cajas asignadas</strong>.
                        </p>
                    </div>

                    <!-- Opción 3: Limpiar sesión de usuario específico -->
                    <div class="border rounded-lg p-4 hover:bg-gray-50 cursor-pointer" onclick="selectCleanOption('specific')">
                        <div class="flex items-center">
                            <input type="radio" name="cleanOption" value="specific" id="cleanSpecific" class="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300">
                            <label for="cleanSpecific" class="ml-3 block text-sm font-medium text-gray-700 cursor-pointer">
                                Limpiar sesión de usuario específico
                            </label>
                        </div>
                        <p class="ml-7 text-sm text-gray-500 mt-1">
                            Selecciona un usuario específico para cerrar su sesión y liberar su caja asignada.
                        </p>

                        <!-- Lista de usuarios activos -->
                        <div id="usersList" class="ml-7 mt-3 hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Seleccionar usuario:</label>

                            <!-- Indicador de selección actual -->
                            <div id="selectedUserIndicator" class="hidden mb-3 p-2 bg-blue-50 border border-blue-200 rounded-md">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-sm text-blue-800 font-medium">Usuario seleccionado: <span id="selectedUserName"></span></span>
                                </div>
                            </div>

                            <div class="max-h-40 overflow-y-auto border rounded-md">
                                <div id="usersListContent" class="divide-y divide-gray-200">
                                    <!-- Los usuarios se cargarán aquí dinámicamente -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="flex justify-end space-x-3">
                    <button
                        onclick="closeCleanSessionsModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        Cancelar
                    </button>
                    <button
                        onclick="confirmCleanSessions()"
                        id="confirmCleanBtn"
                        disabled
                        class="px-4 py-2 text-sm font-medium text-white bg-gray-400 border border-transparent rounded-md cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Limpiar Sesiones
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de opciones para emergencia de turnos -->
<div id="emergencyTurnosModal" class="fixed inset-0 modal-overlay hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center mb-6">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Emergencia - Gestión de Turnos</h3>
                        <p class="text-sm text-gray-500">Opciones de emergencia para reestablecer o eliminar turnos del sistema</p>
                    </div>
                </div>

                <!-- Opciones de emergencia -->
                <div class="space-y-4 mb-6">
                    <!-- Opción 1: Eliminar turnos pendientes y aplazados -->
                    <div class="border rounded-lg p-4 hover:bg-gray-50 cursor-pointer" onclick="selectTurnosOption('pending')">
                        <div class="flex items-center">
                            <input type="radio" name="turnosOption" value="pending" id="deletePending" class="h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300">
                            <label for="deletePending" class="ml-3 block text-sm font-medium text-gray-700 cursor-pointer">
                                Eliminar turnos pendientes y aplazados
                            </label>
                        </div>
                        <p class="ml-7 text-sm text-gray-500 mt-1">
                            Elimina únicamente los turnos que están <strong>pendientes</strong> o <strong>aplazados</strong> del día actual. Los turnos atendidos se mantienen para estadísticas.
                        </p>
                    </div>

                    <!-- Opción 2: Eliminar todos los turnos del día -->
                    <div class="border rounded-lg p-4 hover:bg-gray-50 cursor-pointer" onclick="selectTurnosOption('today')">
                        <div class="flex items-center">
                            <input type="radio" name="turnosOption" value="today" id="deleteToday" class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300">
                            <label for="deleteToday" class="ml-3 block text-sm font-medium text-gray-700 cursor-pointer">
                                Eliminar todos los turnos del día actual
                            </label>
                        </div>
                        <p class="ml-7 text-sm text-gray-500 mt-1">
                            <span class="text-red-600 font-medium">¡Cuidado!</span> Esto eliminará <strong>todos los turnos</strong> del día actual, incluyendo los atendidos. Se perderán las estadísticas del día.
                        </p>
                    </div>

                    <!-- Opción 3: Eliminar turnos por servicio específico -->
                    <div class="border rounded-lg p-4 hover:bg-gray-50 cursor-pointer" onclick="selectTurnosOption('service')">
                        <div class="flex items-center">
                            <input type="radio" name="turnosOption" value="service" id="deleteService" class="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300">
                            <label for="deleteService" class="ml-3 block text-sm font-medium text-gray-700 cursor-pointer">
                                Eliminar turnos de un servicio específico
                            </label>
                        </div>
                        <p class="ml-7 text-sm text-gray-500 mt-1">
                            Selecciona un servicio específico para eliminar todos sus turnos del día actual (pendientes, aplazados y atendidos).
                        </p>

                        <!-- Selector de servicio (se muestra cuando se selecciona esta opción) -->
                        <div id="serviceSelector" class="ml-7 mt-3 hidden">
                            <label for="servicioSelect" class="block text-sm font-medium text-gray-700 mb-2">Seleccionar servicio:</label>
                            <select id="servicioSelect" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                                <option value="">Cargando servicios...</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Advertencia -->
                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Advertencia</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <p>Esta es una función de emergencia. Los turnos eliminados <strong>no se pueden recuperar</strong>. Use solo en caso de errores graves del sistema.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="flex justify-end space-x-3">
                    <button
                        onclick="closeEmergencyTurnosModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        Cancelar
                    </button>
                    <button
                        onclick="confirmEmergencyTurnos()"
                        id="confirmTurnosBtn"
                        disabled
                        class="px-4 py-2 text-sm font-medium text-white bg-gray-400 border border-transparent rounded-md cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        Eliminar Turnos
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de resultado -->
<div id="resultModal" class="fixed inset-0 modal-overlay hidden z-50">
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
let selectedCleanOption = null;
let selectedUserId = null;

function showCleanSessionsOptions() {
    document.getElementById('cleanSessionsModal').classList.remove('hidden');
    // Resetear selecciones
    selectedCleanOption = null;
    selectedUserId = null;
    const confirmBtn = document.getElementById('confirmCleanBtn');
    confirmBtn.disabled = true;
    confirmBtn.className = 'px-4 py-2 text-sm font-medium text-white bg-gray-400 border border-transparent rounded-md cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500';
    confirmBtn.textContent = 'Limpiar Sesiones'; // Resetear texto del botón

    // Limpiar selecciones de radio
    document.querySelectorAll('input[name="cleanOption"]').forEach(radio => {
        radio.checked = false;
    });

    // Ocultar lista de usuarios
    document.getElementById('usersList').classList.add('hidden');
}

function selectCleanOption(option) {
    selectedCleanOption = option;
    selectedUserId = null;

    // Marcar el radio button correspondiente
    document.getElementById('clean' + option.charAt(0).toUpperCase() + option.slice(1)).checked = true;

    // Habilitar/deshabilitar botón según la opción
    const confirmBtn = document.getElementById('confirmCleanBtn');

    if (option === 'specific') {
        // Mostrar lista de usuarios y cargarlos
        const usersList = document.getElementById('usersList');
        usersList.classList.remove('hidden');

        // Siempre cargar usuarios para asegurar datos actualizados
        loadActiveUsers();

        // Solo deshabilitar botón si no hay selección previa
        if (!selectedUserId) {
            document.getElementById('selectedUserIndicator').classList.add('hidden');
            confirmBtn.disabled = true;
            confirmBtn.className = 'px-4 py-2 text-sm font-medium text-white bg-gray-400 border border-transparent rounded-md cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500';
        }
    } else {
        // Ocultar lista de usuarios e indicador
        document.getElementById('usersList').classList.add('hidden');
        document.getElementById('selectedUserIndicator').classList.add('hidden');
        confirmBtn.disabled = false;

        // Todos los botones de limpieza son rojos (acciones de eliminación)
        if (option === 'expired') {
            confirmBtn.className = 'px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500';
        } else if (option === 'all') {
            confirmBtn.className = 'px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500';
        }
    }
}

function loadActiveUsers() {
    fetch('{{ route("api.admin.usuarios-activos") }}')
        .then(response => response.json())
        .then(users => {
            const container = document.getElementById('usersListContent');

            if (users.length === 0) {
                container.innerHTML = '<div class="p-3 text-sm text-gray-500 text-center">No hay usuarios activos</div>';
                return;
            }

            // Preservar la selección actual antes de regenerar
            const currentSelection = selectedUserId;

            container.innerHTML = users.map((user, index) => {
                const isSelected = currentSelection && currentSelection == user.id;
                const borderClass = isSelected ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-200' : 'border-gray-200';

                return `
                <label class="block cursor-pointer">
                    <div class="p-3 hover:bg-gray-50 ${borderClass} rounded-md transition-all duration-200 hover:border-blue-300" data-user-id="${user.id}">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <input type="radio" name="selectedUser" value="${user.id}" ${isSelected ? 'checked' : ''} class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">${user.name}</div>
                                    <div class="text-sm text-gray-500">@${user.nombre_usuario} • ${user.rol}</div>
                                    ${user.caja ? `<div class="text-xs text-blue-600">Caja: ${user.caja}</div>` : ''}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-gray-500">${user.tiempo_sesion}</div>
                                <div class="text-xs ${user.status === 'DISPONIBLE' ? 'text-green-600' : user.status === 'OCUPADO' ? 'text-red-600' : 'text-yellow-600'}">${user.status}</div>
                            </div>
                        </div>
                    </div>
                </label>
            `;
            }).join('');

            // Agregar event listeners para los radio buttons
            container.querySelectorAll('input[name="selectedUser"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.checked) {
                        const userId = parseInt(this.value);
                        const userContainer = this.closest('[data-user-id]');
                        const userName = userContainer.querySelector('.text-gray-900').textContent;
                        selectUser(userId, userName, userContainer);
                    }
                });
            });

            // Restaurar el indicador si había una selección
            if (currentSelection) {
                const selectedUser = users.find(u => u.id == currentSelection);
                if (selectedUser) {
                    const indicator = document.getElementById('selectedUserIndicator');
                    const selectedNameSpan = document.getElementById('selectedUserName');
                    selectedNameSpan.textContent = selectedUser.name;
                    indicator.classList.remove('hidden');

                    // Actualizar botón con color rojo
                    const confirmBtn = document.getElementById('confirmCleanBtn');
                    confirmBtn.disabled = false;
                    confirmBtn.className = 'px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500';
                    confirmBtn.textContent = `Limpiar sesión de ${selectedUser.name}`;
                }
            }
        })
        .catch(error => {
            console.error('Error cargando usuarios:', error);
            document.getElementById('usersListContent').innerHTML = '<div class="p-3 text-sm text-red-500 text-center">Error cargando usuarios</div>';
        });
}

function selectUser(userId, userName, element) {
    selectedUserId = userId;

    // Limpiar selecciones previas
    document.querySelectorAll('#usersListContent [data-user-id]').forEach(div => {
        div.classList.remove('bg-blue-50', 'border-blue-500', 'ring-2', 'ring-blue-200');
        div.classList.add('border-gray-200');
    });

    // Marcar el elemento seleccionado con estilo más visible
    element.classList.remove('border-gray-200');
    element.classList.add('bg-blue-50', 'border-blue-500', 'ring-2', 'ring-blue-200');

    // Habilitar botón de confirmación con color rojo (acción de eliminación)
    const confirmBtn = document.getElementById('confirmCleanBtn');
    confirmBtn.disabled = false;
    confirmBtn.className = 'px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500';

    // Actualizar texto del botón para mostrar qué usuario se va a limpiar
    confirmBtn.textContent = `Limpiar sesión de ${userName}`;

    // Mostrar indicador de selección
    const indicator = document.getElementById('selectedUserIndicator');
    const selectedNameSpan = document.getElementById('selectedUserName');
    selectedNameSpan.textContent = userName;
    indicator.classList.remove('hidden');
}

function closeCleanSessionsModal() {
    document.getElementById('cleanSessionsModal').classList.add('hidden');
}

function closeResultModal() {
    document.getElementById('resultModal').classList.add('hidden');
}

function confirmCleanSessions() {
    if (!selectedCleanOption) {
        alert('Por favor selecciona una opción de limpieza');
        return;
    }

    if (selectedCleanOption === 'specific' && !selectedUserId) {
        alert('Por favor selecciona un usuario');
        return;
    }

    const btn = document.getElementById('cleanSessionsBtn');
    const originalText = btn.innerHTML;

    // Deshabilitar botón y mostrar loading
    btn.disabled = true;
    btn.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Limpiando...';

    // Cerrar modal de confirmación
    closeCleanSessionsModal();

    // Preparar datos para enviar
    const requestData = {
        option: selectedCleanOption
    };

    if (selectedCleanOption === 'specific') {
        requestData.user_id = selectedUserId;
    }

    // Determinar la ruta según la opción
    let route = '{{ route("admin.clean-sessions") }}';
    if (selectedCleanOption === 'all') {
        route = '{{ route("admin.clean-all-sessions") }}';
    } else if (selectedCleanOption === 'specific') {
        route = '{{ route("admin.clean-user-session") }}';
    }

    fetch(route, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(requestData)
    })
    .then(response => response.json())
    .then(data => {
        // Restaurar botón
        btn.disabled = false;
        btn.innerHTML = originalText;

        // Mostrar resultado
        showResult(data);

        // Actualizar la tabla de usuarios activos
        actualizarUsuariosActivos();
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
            let detailsHtml = '<ul class="list-disc list-inside space-y-1">';

            // Mostrar diferentes campos según el tipo de limpieza
            if (data.data.usuarios_limpiados !== undefined) {
                detailsHtml += `<li>Usuarios limpiados: ${data.data.usuarios_limpiados}</li>`;
            }
            if (data.data.usuario_limpiado !== undefined) {
                detailsHtml += `<li>Usuario: ${data.data.usuario_limpiado}</li>`;
            }
            if (data.data.cajas_liberadas !== undefined) {
                detailsHtml += `<li>Cajas liberadas: ${data.data.cajas_liberadas}</li>`;
            }
            if (data.data.sesiones_expiradas_eliminadas !== undefined) {
                detailsHtml += `<li>Sesiones expiradas eliminadas: ${data.data.sesiones_expiradas_eliminadas}</li>`;
            }
            if (data.data.sesiones_eliminadas !== undefined) {
                detailsHtml += `<li>Sesiones eliminadas: ${data.data.sesiones_eliminadas}</li>`;
            }
            if (data.data.sesion_eliminada !== undefined) {
                detailsHtml += `<li>Sesión eliminada: ${data.data.sesion_eliminada ? 'Sí' : 'No'}</li>`;
            }

            detailsHtml += '</ul>';
            details.innerHTML = detailsHtml;
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
                                <span class="dashboard-badge px-2 py-1 rounded text-sm ${rolClass}">
                                    ${usuario.rol}
                                </span>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap text-sm text-gray-900">
                                ${usuario.availability}
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="dashboard-badge px-2 py-1 rounded text-sm ${statusClass}">
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
                                <i class="dashboard-icon fas fa-users text-4xl mb-4 text-gray-300"></i>
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
                                <span class="dashboard-badge px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
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
                                <i class="dashboard-icon fas fa-chart-bar text-4xl mb-4 text-gray-300"></i>
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
                                <span class="dashboard-badge px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
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
                                <i class="dashboard-icon fas fa-user-tie text-4xl mb-4 text-gray-300"></i>
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
                                <span class="dashboard-badge px-3 py-1 rounded-full text-sm font-medium bg-orange-100 text-orange-800">
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
                                <i class="dashboard-icon fas fa-clock text-4xl mb-4 text-gray-300"></i>
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

// Variables para el modal de emergencia de turnos
let selectedTurnosOption = null;
let selectedServiceId = null;

// Funciones para el modal de emergencia de turnos
function showEmergencyTurnosOptions() {
    document.getElementById('emergencyTurnosModal').classList.remove('hidden');
    // Resetear selecciones
    selectedTurnosOption = null;
    selectedServiceId = null;
    const confirmBtn = document.getElementById('confirmTurnosBtn');
    confirmBtn.disabled = true;
    confirmBtn.className = 'px-4 py-2 text-sm font-medium text-white bg-gray-400 border border-transparent rounded-md cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500';
    confirmBtn.textContent = 'Eliminar Turnos';

    // Limpiar selecciones de radio
    document.querySelectorAll('input[name="turnosOption"]').forEach(radio => {
        radio.checked = false;
    });

    // Ocultar selector de servicio
    document.getElementById('serviceSelector').classList.add('hidden');

    // Cargar servicios
    loadServiciosForEmergency();
}

function closeEmergencyTurnosModal() {
    document.getElementById('emergencyTurnosModal').classList.add('hidden');
}

function selectTurnosOption(option) {
    selectedTurnosOption = option;

    // Marcar el radio button correspondiente
    document.getElementById('delete' + option.charAt(0).toUpperCase() + option.slice(1)).checked = true;

    // Mostrar/ocultar selector de servicio
    const serviceSelector = document.getElementById('serviceSelector');
    if (option === 'service') {
        serviceSelector.classList.remove('hidden');
    } else {
        serviceSelector.classList.add('hidden');
        selectedServiceId = null;
    }

    updateTurnosConfirmButton();
}

function updateTurnosConfirmButton() {
    const confirmBtn = document.getElementById('confirmTurnosBtn');

    // Verificar si se puede habilitar el botón
    const canConfirm = selectedTurnosOption && (selectedTurnosOption !== 'service' || selectedServiceId);

    if (canConfirm) {
        confirmBtn.disabled = false;
        confirmBtn.className = 'px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500';

        // Actualizar texto del botón según la opción
        switch(selectedTurnosOption) {
            case 'pending':
                confirmBtn.textContent = 'Eliminar Turnos Pendientes/Aplazados';
                break;
            case 'today':
                confirmBtn.textContent = 'Eliminar Todos los Turnos del Día';
                break;
            case 'service':
                confirmBtn.textContent = 'Eliminar Turnos del Servicio';
                break;
        }
    } else {
        confirmBtn.disabled = true;
        confirmBtn.className = 'px-4 py-2 text-sm font-medium text-white bg-gray-400 border border-transparent rounded-md cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500';
        confirmBtn.textContent = 'Eliminar Turnos';
    }
}

function loadServiciosForEmergency() {
    fetch('/api/servicios-activos')
        .then(response => response.json())
        .then(servicios => {
            const select = document.getElementById('servicioSelect');
            select.innerHTML = '<option value="">Seleccionar servicio...</option>';

            servicios.forEach(servicio => {
                const option = document.createElement('option');
                option.value = servicio.id;
                option.textContent = servicio.nombre;
                select.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error cargando servicios:', error);
            const select = document.getElementById('servicioSelect');
            select.innerHTML = '<option value="">Error cargando servicios</option>';
        });
}

// Event listener para el selector de servicio
document.addEventListener('DOMContentLoaded', function() {
    const servicioSelect = document.getElementById('servicioSelect');
    if (servicioSelect) {
        servicioSelect.addEventListener('change', function() {
            selectedServiceId = this.value;
            updateTurnosConfirmButton();
        });
    }
});

function confirmEmergencyTurnos() {
    if (!selectedTurnosOption) return;

    const confirmBtn = document.getElementById('confirmTurnosBtn');
    const originalText = confirmBtn.textContent;

    // Deshabilitar botón y mostrar loading
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Procesando...';

    // Preparar datos de la petición
    const requestData = {
        option: selectedTurnosOption
    };

    if (selectedTurnosOption === 'service' && selectedServiceId) {
        requestData.service_id = selectedServiceId;
    }

    // Determinar la ruta
    const route = '/admin/emergency-turnos';

    fetch(route, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(requestData)
    })
    .then(response => response.json())
    .then(data => {
        // Restaurar botón
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalText;

        // Cerrar modal
        closeEmergencyTurnosModal();

        // Mostrar resultado
        showResult(data);

        // Actualizar las estadísticas
        actualizarTurnosPorServicio();
        actualizarTurnosPorAsesor();
        actualizarTurnosEnCola();
    })
    .catch(error => {
        console.error('Error:', error);

        // Restaurar botón
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalText;

        // Mostrar error
        showResult({
            success: false,
            message: 'Error de conexión. Inténtalo de nuevo.'
        });
    });
}

// Iniciar actualización automática
startAutoUpdate();

// Actualización inicial
actualizarUsuariosActivos();
actualizarTurnosPorServicio();
actualizarTurnosPorAsesor();
actualizarTurnosEnCola();
</script>

<style>
/* Eliminar outline/borde de focus en botones y elementos interactivos */
button:focus,
button:active,
input:focus,
input:active,
select:focus,
select:active,
textarea:focus,
textarea:active,
.btn:focus,
.btn:active,
[role="button"]:focus,
[role="button"]:active {
    outline: none !important;
    box-shadow: none !important;
}

/* Eliminar outline en radio buttons y checkboxes */
input[type="radio"]:focus,
input[type="checkbox"]:focus {
    outline: none !important;
    box-shadow: none !important;
}

/* Eliminar outline en elementos clickeables */
.cursor-pointer:focus,
.cursor-pointer:active,
label:focus,
label:active {
    outline: none !important;
    box-shadow: none !important;
}

/* Mantener accesibilidad con un sutil efecto hover en lugar del outline */
button:hover,
.btn:hover,
[role="button"]:hover {
    transform: translateY(-1px);
    transition: transform 0.1s ease;
}
</style>

@endsection
