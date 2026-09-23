@extends('layouts.admin')

@section('title', 'Inicio')
@section('content')
@php
    // Todo sale de App\Services\TableroService (el mismo cálculo que refresca GET /api/admin/tablero).
    $r = $tablero['resumen'];
    $esperaMax = $r['espera_max'];
@endphp

                <div class="dashboard-container max-w-7xl mx-auto space-y-5">
                    <h1 class="sr-only">Inicio</h1>

                    <!-- Cifras del momento -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                        <div class="metric-card bg-white border border-gray-200 rounded-xl shadow-sm p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">En espera</p>
                                    <div id="metric-en-espera" class="metric-value text-3xl font-bold mt-2">{{ $r['en_espera'] }}</div>
                                </div>
                                <div class="metric-icon w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                                </div>
                            </div>
                            <p id="metric-en-espera-detalle" class="text-xs text-gray-500 mt-1.5"><span class="font-semibold text-gray-700">{{ $r['prioritarios'] }}</span> prioritarios · <span class="font-semibold text-gray-700">{{ $r['aplazados'] }}</span> aplazados</p>
                        </div>
                        <div class="metric-card bg-white border border-gray-200 rounded-xl shadow-sm p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Espera más larga</p>
                                    <div id="metric-espera-max" class="metric-value text-3xl font-bold mt-2 {{ ($esperaMax['supera_umbral'] ?? false) ? 'is-alerta' : '' }}">@if($esperaMax){{ $esperaMax['minutos'] }}<span class="metric-unit">min</span>@else — @endif</div>
                                </div>
                                <div class="metric-icon w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                            </div>
                            <p id="metric-espera-detalle" class="text-xs text-gray-500 mt-1.5">@if($esperaMax)<span class="font-semibold text-gray-700">{{ $esperaMax['turno'] }}</span>{{ $esperaMax['prioritario'] ? ' prioritario' : '' }} · {{ $esperaMax['servicio'] }}@else Nadie en espera @endif</p>
                        </div>
                        <div class="metric-card bg-white border border-gray-200 rounded-xl shadow-sm p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Atendidos hoy</p>
                                    <div id="metric-atendidos" class="metric-value text-3xl font-bold mt-2">{{ $r['atendidos_hoy'] }}</div>
                                </div>
                                <div class="metric-icon w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                            </div>
                            <p id="metric-atendidos-detalle" class="text-xs text-gray-500 mt-1.5"><span class="font-semibold text-gray-700">{{ $r['transferidos_hoy'] }}</span> transferidos</p>
                        </div>
                        <div class="metric-card bg-white border border-gray-200 rounded-xl shadow-sm p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Asesores conectados</p>
                                    <div id="metric-asesores" class="metric-value text-3xl font-bold mt-2">{{ $r['asesores']['conectados'] }}</div>
                                </div>
                                <div class="metric-icon w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                </div>
                            </div>
                            <p id="metric-asesores-detalle" class="text-xs text-gray-500 mt-1.5"><span class="font-semibold text-gray-700">{{ $r['asesores']['atendiendo'] }}</span> atendiendo · <span class="font-semibold text-gray-700">{{ $r['asesores']['libres'] }}</span> {{ $r['asesores']['libres'] === 1 ? 'libre' : 'libres' }} · <span class="font-semibold text-gray-700">{{ $r['asesores']['descanso'] }}</span> en descanso</p>
                        </div>
                    </div>

                    <div class="inicio-cuerpo">
                    <!-- Turnos en cola por servicio: es lo primero que se mira -->
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 overflow-x-auto">
                        <div class="panel-cabeza flex justify-between items-center mb-4">
                            <h2 class="dashboard-title text-lg font-semibold text-gray-800">Cola por servicio</h2>
                        </div>
                        <table class="dashboard-table tabla-cifras w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600">
                                    <th class="py-3 px-4 text-left text-xs font-semibold uppercase tracking-wide">Servicio</th><th class="py-3 px-4 text-left text-xs font-semibold uppercase tracking-wide">En cola</th><th class="py-3 px-4 text-left text-xs font-semibold uppercase tracking-wide">Espera máx.</th><th class="py-3 px-4 text-left text-xs font-semibold uppercase tracking-wide">Cubren</th>
                                </tr>
                            </thead>
                            <tbody id="turnos-cola-container" class="divide-y divide-gray-200 bg-white"></tbody>
                        </table>
                    </div>

                    <!-- Asesores conectados (sin administradores) -->
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 overflow-x-auto">
                        <div class="panel-cabeza flex justify-between items-center mb-4">
                            <h2 class="dashboard-title text-lg font-semibold text-gray-800">Asesores conectados</h2>
                        </div>
                        <table class="dashboard-table tabla-cifras w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600">
                                    <th class="py-3 px-4 text-left text-xs font-semibold uppercase tracking-wide">Mód.</th><th class="py-3 px-4 text-left text-xs font-semibold uppercase tracking-wide">Asesor</th><th class="py-3 px-4 text-left text-xs font-semibold uppercase tracking-wide">Estado</th><th class="py-3 px-4 text-left text-xs font-semibold uppercase tracking-wide">Turno</th><th class="py-3 px-4 text-left text-xs font-semibold uppercase tracking-wide">Atend.</th>
                                </tr>
                            </thead>
                            <tbody id="usuarios-activos-container" class="divide-y divide-gray-200 bg-white"></tbody>
                        </table>
                    </div>

                    </div>

                    <!-- Herramientas del sistema: al final y en estilo secundario; el rojo vive solo en la confirmación -->
                    <div class="herramientas flex flex-wrap items-center gap-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-600">Herramientas del sistema</p>
                        <button type="button" onclick="showCleanSessionsOptions()" id="cleanSessionsBtn"
                            class="herramienta-btn text-gray-700 text-sm font-medium px-3 py-2 rounded-lg flex items-center gap-2">
                            <svg class="w-4 h-4 text-hospital-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            Limpiar sesiones
                        </button>
                        <button type="button" onclick="showEmergencyTurnosOptions()" id="emergencyTurnosBtn"
                            class="herramienta-btn text-gray-700 text-sm font-medium px-3 py-2 rounded-lg flex items-center gap-2">
                            <svg class="w-4 h-4 text-hospital-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>
                            Emergencia de turnos
                        </button>
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
                            <span class="text-red-600 font-medium">¡Cuidado!</span> Esto cerrará la sesión de todos los usuarios activos, incluyendo asesores que estén trabajando (la tuya se mantiene). <strong>También liberará todas las cajas asignadas</strong>.
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
                                    <!-- Los usuarios se cargan aquí; mientras tanto, la forma de la lista -->
                                    @foreach ([[45, 30], [38, 26], [52, 34]] as [$a, $b])
                                        <div class="usuario-esqueleto" aria-hidden="true"><span class="esqueleto" style="width: {{ $a }}%"></span><span class="esqueleto" style="width: {{ $b }}%"></span></div>
                                    @endforeach
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

<!-- Modal de Estadísticas de Usuario (Alpine.js) -->
<div
    x-data="estadisticasUsuarioModal()"
    x-cloak
    @keydown.escape.window="isOpen = false"
>
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 modal-overlay z-50 flex items-center justify-center p-4"
        style="display: none;"
    >
        <div
            @click.away="isOpen = false"
            class="bg-white rounded-lg shadow-2xl w-full max-w-2xl overflow-y-auto max-h-[90vh]"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95"
        >
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-800">Estadísticas del Usuario</h2>
                    <button @click="isOpen = false" class="text-gray-500 hover:text-gray-700 cursor-pointer">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Nombre del usuario -->
                <p class="text-sm text-gray-500 mb-6" x-text="'Usuario: ' + usuarioNombre"></p>

                <!-- Filtros de Fecha -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Inicio</label>
                        <input
                            type="date"
                            x-model="fechaInicio"
                            class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-hospital-blue"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Fin</label>
                        <input
                            type="date"
                            x-model="fechaFin"
                            class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-hospital-blue"
                        >
                    </div>
                </div>

                <!-- Atajos de fecha -->
                <div class="flex flex-wrap gap-2 mb-6">
                    <button @click="setFechaRapida('hoy')" class="px-3 py-1 text-xs border border-gray-300 rounded hover:bg-gray-100 transition-colors cursor-pointer">Hoy</button>
                    <button @click="setFechaRapida('ayer')" class="px-3 py-1 text-xs border border-gray-300 rounded hover:bg-gray-100 transition-colors cursor-pointer">Ayer</button>
                    <button @click="setFechaRapida('semana')" class="px-3 py-1 text-xs border border-gray-300 rounded hover:bg-gray-100 transition-colors cursor-pointer">Última Semana</button>
                    <button @click="setFechaRapida('mes')" class="px-3 py-1 text-xs border border-gray-300 rounded hover:bg-gray-100 transition-colors cursor-pointer">Último Mes</button>
                </div>

                <!-- Mientras cargan: la forma del resumen, los tiempos y la tabla de turnos -->
                <div x-show="loading" class="est-esqueleto" role="status">
                    <span class="sr-only">Cargando estadísticas…</span>
                    <div class="est-esqueleto__fila est-esqueleto__fila--4" aria-hidden="true">
                        <span class="esqueleto esqueleto--bloque"></span><span class="esqueleto esqueleto--bloque"></span>
                        <span class="esqueleto esqueleto--bloque"></span><span class="esqueleto esqueleto--bloque"></span>
                    </div>
                    <div class="est-esqueleto__fila est-esqueleto__fila--3" aria-hidden="true">
                        <span class="esqueleto esqueleto--bloque-bajo"></span><span class="esqueleto esqueleto--bloque-bajo"></span><span class="esqueleto esqueleto--bloque-bajo"></span>
                    </div>
                    <span class="esqueleto" style="width: 10rem" aria-hidden="true"></span>
                    <div class="est-esqueleto__tabla" aria-hidden="true">
                        <span class="esqueleto"></span><span class="esqueleto"></span><span class="esqueleto"></span><span class="esqueleto"></span>
                    </div>
                </div>

                <!-- Contenido de Estadísticas -->
                <div x-show="!loading" id="estadisticas-contenido" class="max-h-80 overflow-y-auto">
                    <!-- El contenido se carga dinámicamente -->
                </div>

                <div class="mt-8 flex justify-end space-x-3">
                    <button type="button" @click="isOpen = false" class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300 transition-colors cursor-pointer">
                        Cerrar
                    </button>
                    <button type="button" @click="cargarEstadisticas()" class="bg-hospital-blue text-white px-6 py-2 rounded hover:bg-hospital-blue-hover transition-colors cursor-pointer">
                        Aplicar Filtro
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let selectedCleanOption = null;
let selectedUserId = null;

// Peticiones de Inicio: piden JSON y reconocen la sesión cerrada (HTTP 419/401 o redirección al acceso).
// Antes se leía como JSON la página HTML de "sesión expirada" y salía "Unexpected token '<'".
const CSRF_INICIO = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
const USUARIO_ACTUAL_ID = @json(auth()->id());
class SesionCerrada extends Error {}
function pedirJson(url, opciones = {}) {
    const cabeceras = Object.assign({ 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, opciones.headers);
    if ((opciones.method || 'GET') !== 'GET') cabeceras['X-CSRF-TOKEN'] = CSRF_INICIO;
    return fetch(url, Object.assign({}, opciones, { headers: cabeceras })).then(r => {
        if (r.status === 401 || r.status === 419) throw new SesionCerrada();
        if (!(r.headers.get('content-type') || '').includes('application/json')) {
            if (r.redirected) throw new SesionCerrada();
            throw new Error('el servidor respondió HTTP ' + r.status);
        }
        if (opciones.exigirOk && !r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
    });
}
// Con la sesión cerrada se detiene la actualización y se ofrece recargar (la página lleva al acceso).
function mostrarSesionCerrada() {
    if (autoUpdateInterval) { clearInterval(autoUpdateInterval); autoUpdateInterval = null; }
    // Se queda abierto (sin irse solo) hasta que recarguen: la página ya no puede actualizarse.
    const unDia = 24 * 60 * 60 * 1000;
    sileo.error({
        title: 'Sesión cerrada',
        description: 'Tu sesión se cerró (por inactividad o porque se limpiaron las sesiones). Recarga la página para volver a entrar.',
        button: { title: 'Recargar página', onClick: () => location.reload() },
        duration: unDia, autopilot: { expand: 150, collapse: unDia },
    });
}

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
    pedirJson('{{ route("api.admin.usuarios-activos") }}', { exigirOk: true })
        .then(todos => {
            // La sesión propia no se limpia desde aquí (dejaría esta página sin sesión)
            const users = todos.filter(u => Number(u.id) !== Number(USUARIO_ACTUAL_ID));
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
            if (error instanceof SesionCerrada) { closeCleanSessionsModal(); mostrarSesionCerrada(); return; }
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

function confirmCleanSessions() {
    if (!selectedCleanOption) {
        sileo.warning({ title: 'Elige una opción de limpieza' });
        return;
    }

    if (selectedCleanOption === 'specific' && !selectedUserId) {
        sileo.warning({ title: 'Elige un usuario' });
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

    pedirJson(route, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(requestData)
    })
    .then(data => {
        // Restaurar botón
        btn.disabled = false;
        btn.innerHTML = originalText;

        // Mostrar resultado
        showResult(data, { ok: 'Sesiones limpiadas', error: 'No se limpiaron las sesiones' });

        // Actualizar la tabla de usuarios activos
        actualizarUsuariosActivos();
    })
    .catch(error => {
        // Restaurar botón
        btn.disabled = false;
        btn.innerHTML = originalText;

        if (error instanceof SesionCerrada) { mostrarSesionCerrada(); return; }
        showResult({
            success: false,
            message: 'No se pudo completar la limpieza (' + error.message + '). Inténtalo de nuevo.'
        }, { ok: 'Sesiones limpiadas', error: 'No se limpiaron las sesiones' });
    });
}

// Resultado de "Limpiar sesiones" o de "Emergencia de turnos": toast (sileo.js) con el mensaje y el detalle.
function showResult(data, titulos) {
    const d = data.data || {};
    const detalle = [];
    if (d.usuarios_limpiados !== undefined) detalle.push('Usuarios limpiados: ' + d.usuarios_limpiados);
    if (d.usuario_limpiado !== undefined) detalle.push('Usuario: ' + d.usuario_limpiado);
    if (d.cajas_liberadas !== undefined) detalle.push('Cajas liberadas: ' + d.cajas_liberadas);
    if (d.sesiones_expiradas_eliminadas !== undefined) detalle.push('Sesiones expiradas eliminadas: ' + d.sesiones_expiradas_eliminadas);
    if (d.sesiones_eliminadas !== undefined) detalle.push('Sesiones eliminadas: ' + d.sesiones_eliminadas);
    if (d.sesion_eliminada !== undefined) detalle.push('Sesión eliminada: ' + (d.sesion_eliminada ? 'sí' : 'no'));
    const description = [data.message, detalle.join(' · ')].filter(Boolean).join('\n');
    if (data.success) sileo.success({ title: titulos.ok, description, duration: 8000 });
    else sileo.error({ title: titulos.error, description, duration: 8000 });
}

// ===== Inicio: cifras, cola por servicio y asesores conectados =====
// Un solo origen de datos: App\Services\TableroService. La primera pintura usa los datos que ya trae
// la página y cada 15 s se piden de nuevo a /api/admin/tablero (en pausa con la pestaña oculta).
const TABLERO_URL = @json(route('api.admin.tablero'));
const ASIGNACION_URL = @json(route('admin.asignacion-servicios'));
let autoUpdateInterval = null;

function escHtml(t) {
    return String(t ?? '').replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
}
function cifra(n) { return '<span class="font-semibold text-gray-700">' + escHtml(n) + '</span>'; }
function ponerTexto(id, v) { const el = document.getElementById(id); if (el) el.textContent = v; }
function ponerHtml(id, v) { const el = document.getElementById(id); if (el) el.innerHTML = v; }

function pintarCifras(t) {
    const r = t.resumen;
    ponerTexto('metric-en-espera', r.en_espera);
    ponerHtml('metric-en-espera-detalle', cifra(r.prioritarios) + ' prioritarios · ' + cifra(r.aplazados) + ' aplazados');

    const em = r.espera_max;
    const esperaEl = document.getElementById('metric-espera-max');
    if (esperaEl) {
        if (em) {
            esperaEl.innerHTML = escHtml(em.minutos) + '<span class="metric-unit">min</span>';
            esperaEl.classList.toggle('is-alerta', !!em.supera_umbral);
            esperaEl.title = em.supera_umbral
                ? 'Pasó el límite de ' + (em.prioritario ? t.umbrales.prioritario : t.umbrales.espera) + ' min'
                : '';
            ponerHtml('metric-espera-detalle', cifra(em.turno) + (em.prioritario ? ' prioritario' : '') + ' · ' + escHtml(em.servicio));
        } else {
            esperaEl.textContent = '—';
            esperaEl.classList.remove('is-alerta');
            esperaEl.title = '';
            ponerHtml('metric-espera-detalle', 'Nadie en espera');
        }
    }

    ponerTexto('metric-atendidos', r.atendidos_hoy);
    ponerHtml('metric-atendidos-detalle', cifra(r.transferidos_hoy) + ' transferidos');

    const a = r.asesores;
    ponerTexto('metric-asesores', a.conectados);
    const partes = [cifra(a.atendiendo) + ' atendiendo', cifra(a.libres) + (a.libres === 1 ? ' libre' : ' libres'), cifra(a.descanso) + ' en descanso'];
    if (a.canal) partes.push(cifra(a.canal) + ' en canal');
    if (a.sin_modulo) partes.push(cifra(a.sin_modulo) + ' sin módulo');
    ponerHtml('metric-asesores-detalle', partes.join(' · '));
}

function pintarCola(t) {
    const cuerpo = document.getElementById('turnos-cola-container');
    if (!cuerpo) return;
    if (!t.servicios.length) {
        cuerpo.innerHTML = '<tr><td colspan="4" class="py-8 px-4 text-center text-sm text-gray-500">Hoy no hay turnos en cola ni atendidos.</td></tr>';
        return;
    }
    const td = 'py-3 px-4 whitespace-nowrap text-sm';
    cuerpo.innerHTML = t.servicios.map(s => {
        const enCola = (s.en_cola > 0
            ? '<span class="dashboard-badge px-3 py-1 rounded-full text-sm font-medium bg-orange-100 text-orange-800">' + escHtml(s.en_cola) + '</span>'
            : '<span class="text-gray-500">0</span>')
            + (s.prioritarios > 0 ? ' <span class="prio-mini" title="Prioritarios en espera">' + escHtml(s.prioritarios) + ' prior.</span>' : '');
        const espera = s.espera_max_min === null || s.espera_max_min === undefined
            ? '<span class="text-gray-500">—</span>'
            : (s.alerta
                ? '<span class="cifra-alerta" title="' + escHtml(s.alerta_motivo) + '">' + escHtml(s.espera_max_min) + ' min</span>'
                : escHtml(s.espera_max_min) + ' min');
        const cubren = s.sin_cobertura
            ? '<a href="' + ASIGNACION_URL + '?servicio=' + encodeURIComponent(s.id) + '" class="sin-cobertura" title="Ningún asesor conectado puede atender este servicio">Nadie · Asignar</a>'
            : escHtml(s.cubren);
        return '<tr class="' + (s.sin_cobertura ? 'fila-sin-cobertura' : 'hover:bg-gray-50') + '">'
            + '<td class="py-3 px-4"><span class="celda-nombre text-sm font-medium text-gray-900" title="' + escHtml(s.nombre) + '">' + escHtml(s.nombre) + '</span></td>'
            + '<td class="py-3 px-4 whitespace-nowrap">' + enCola + '</td>'
            + '<td class="' + td + ' text-gray-900">' + espera + '</td>'
            + '<td class="' + td + ' text-gray-900">' + cubren + '</td>'
            + '</tr>';
    }).join('');
}

const ESTADOS_ASESOR = {
    atendiendo: ['Atendiendo', 'bg-yellow-100 text-yellow-800'],
    libre: ['Libre', 'bg-green-100 text-green-800'],
    descanso: ['En descanso', 'bg-blue-100 text-blue-800'],
    canal: ['Canal no presencial', 'bg-orange-100 text-orange-800'],
    sin_modulo: ['Sin módulo', 'bg-gray-100 text-gray-700'],
};

function pintarAsesores(t) {
    const cuerpo = document.getElementById('usuarios-activos-container');
    if (!cuerpo) return;
    if (!t.asesores.length) {
        cuerpo.innerHTML = '<tr><td colspan="5" class="py-8 px-4 text-center text-sm text-gray-500">No hay asesores conectados.</td></tr>';
        return;
    }
    const td = 'py-3 px-4 whitespace-nowrap text-sm text-gray-900';
    cuerpo.innerHTML = t.asesores.map(a => {
        const [texto, colores] = ESTADOS_ASESOR[a.estado] || [a.estado, 'bg-gray-100 text-gray-700'];
        let enCurso = '<span class="text-gray-500">—</span>';
        if (a.turno) {
            enCurso = '<span class="font-semibold">' + escHtml(a.turno.codigo) + '</span>'
                + (a.turno.minutos !== null ? ' <span class="text-gray-500">· ' + escHtml(a.turno.minutos) + ' min</span>' : '');
        } else if (a.canal) {
            enCurso = escHtml(a.canal.actividad || 'Canal no presencial')
                + (a.canal.minutos !== null ? ' <span class="text-gray-500">· ' + escHtml(a.canal.minutos) + ' min</span>' : '');
        }
        return '<tr class="hover:bg-blue-50/70 cursor-pointer transition-colors" data-asesor-id="' + escHtml(a.id) + '" data-asesor-nombre="' + escHtml(a.nombre) + '" title="Ver estadísticas de ' + escHtml(a.nombre) + '">'
            + '<td class="' + td + ' font-semibold">' + (a.modulo !== null ? escHtml(a.modulo) : '<span class="text-gray-500">—</span>') + '</td>'
            + '<td class="py-3 px-4"><button type="button" class="asesor-nombre celda-nombre text-sm font-medium text-gray-900">' + escHtml(a.nombre) + '</button></td>'
            + '<td class="py-3 px-4 whitespace-nowrap"><span class="dashboard-badge px-2 py-1 rounded-md text-xs font-medium ' + colores + '">' + texto + '</span></td>'
            + '<td class="' + td + '">' + enCurso + '</td>'
            + '<td class="' + td + '">' + escHtml(a.atendidos_hoy) + '</td>'
            + '</tr>';
    }).join('');
}

// Clic en la fila o en el nombre: estadísticas del asesor. Delegado y con data-*, sin interpolar el
// nombre dentro de JavaScript en línea (un apóstrofo en el nombre ya no rompe nada).
document.getElementById('usuarios-activos-container')?.addEventListener('click', e => {
    const fila = e.target.closest('tr[data-asesor-id]');
    if (fila) abrirModalEstadisticas(parseInt(fila.dataset.asesorId, 10), fila.dataset.asesorNombre);
});

function pintarTablero(t) {
    pintarCifras(t);
    pintarCola(t);
    pintarAsesores(t);
}

function actualizarTablero() {
    return pedirJson(TABLERO_URL, { cache: 'no-store', exigirOk: true })
        .then(pintarTablero)
        .catch(err => {
            // Sin sesión, las cifras en pantalla dejan de ser reales: se avisa una vez en lugar de seguir mostrándolas
            if (err instanceof SesionCerrada) { if (autoUpdateInterval) mostrarSesionCerrada(); return; }
            console.warn('No se pudo actualizar el Inicio:', err);
        });
}

// Los modales (limpiar sesiones, emergencia) siguen llamando a estos nombres después de actuar.
const actualizarUsuariosActivos = actualizarTablero;
const actualizarTurnosPorServicio = actualizarTablero;
const actualizarTurnosPorAsesor = actualizarTablero;
const actualizarTurnosEnCola = actualizarTablero;

function startAutoUpdate() {
    if (autoUpdateInterval) clearInterval(autoUpdateInterval);
    autoUpdateInterval = setInterval(() => { if (!document.hidden) actualizarTablero(); }, 15000);
}
document.addEventListener('visibilitychange', () => { if (!document.hidden) actualizarTablero(); });

pintarTablero(@js($tablero));

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
    pedirJson('/api/servicios-activos', { exigirOk: true })
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
            if (error instanceof SesionCerrada) { closeEmergencyTurnosModal(); mostrarSesionCerrada(); return; }
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

    pedirJson(route, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(requestData)
    })
    .then(data => {
        // Restaurar botón
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalText;

        // Cerrar modal
        closeEmergencyTurnosModal();

        // Mostrar resultado
        showResult(data, { ok: 'Turnos eliminados', error: 'No se eliminaron los turnos' });

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

        if (error instanceof SesionCerrada) { closeEmergencyTurnosModal(); mostrarSesionCerrada(); return; }
        showResult({
            success: false,
            message: 'No se pudo completar la acción (' + error.message + '). Inténtalo de nuevo.'
        }, { ok: 'Turnos eliminados', error: 'No se eliminaron los turnos' });
    });
}

// Iniciar actualización automática
startAutoUpdate();

// ===== MODAL DE ESTADÍSTICAS DE USUARIO (Alpine.js) =====
function abrirModalEstadisticas(userId, nombreUsuario) {
    window.dispatchEvent(new CustomEvent('open-estadisticas-modal', {
        detail: { userId, nombreUsuario }
    }));
}

// Componente Alpine.js para el modal de estadísticas
function estadisticasUsuarioModal() {
    return {
        isOpen: false,
        loading: false,
        usuarioId: null,
        usuarioNombre: '',
        fechaInicio: (() => { const d = new Date(); return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0'); })(),
        fechaFin: (() => { const d = new Date(); return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0'); })(),

        init() {
            window.addEventListener('open-estadisticas-modal', (event) => {
                const { userId, nombreUsuario } = event.detail;
                this.usuarioId = userId;
                this.usuarioNombre = nombreUsuario;
                // Usar fecha LOCAL (no UTC) para evitar desfase de zona horaria
                const hoy = new Date();
                const fechaLocal = hoy.getFullYear() + '-' + String(hoy.getMonth()+1).padStart(2,'0') + '-' + String(hoy.getDate()).padStart(2,'0');
                this.fechaInicio = fechaLocal;
                this.fechaFin = fechaLocal;
                this.isOpen = true;
                this.cargarEstadisticas();
            });
        },

        setFechaRapida(tipo) {
            const hoy = new Date();
            let fechaInicio, fechaFin;
            
            switch(tipo) {
                case 'hoy':
                    fechaInicio = fechaFin = hoy;
                    break;
                case 'ayer':
                    const ayer = new Date(hoy);
                    ayer.setDate(ayer.getDate() - 1);
                    fechaInicio = fechaFin = ayer;
                    break;
                case 'semana':
                    fechaFin = hoy;
                    fechaInicio = new Date(hoy);
                    fechaInicio.setDate(fechaInicio.getDate() - 7);
                    break;
                case 'mes':
                    fechaFin = hoy;
                    fechaInicio = new Date(hoy);
                    fechaInicio.setMonth(fechaInicio.getMonth() - 1);
                    break;
            }
            
            // Usar fecha LOCAL (no UTC) para evitar desfase de zona horaria
            this.fechaInicio = fechaInicio.getFullYear() + '-' + String(fechaInicio.getMonth()+1).padStart(2,'0') + '-' + String(fechaInicio.getDate()).padStart(2,'0');
            this.fechaFin = fechaFin.getFullYear() + '-' + String(fechaFin.getMonth()+1).padStart(2,'0') + '-' + String(fechaFin.getDate()).padStart(2,'0');
            this.cargarEstadisticas();
        },

        cargarEstadisticas() {
            if (!this.usuarioId) return;
            
            this.loading = true;
            const contenedor = document.getElementById('estadisticas-contenido');
            
            let url = `/api/admin/usuario/${this.usuarioId}/estadisticas`;
            if (this.fechaInicio && this.fechaFin) {
                url += `?fecha_inicio=${this.fechaInicio}&fecha_fin=${this.fechaFin}`;
            }
            
            pedirJson(url, { exigirOk: true })
                .then(data => {
                    this.loading = false;
                    this.renderizarEstadisticas(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.loading = false;
                    contenedor.innerHTML = `
                        <div class="text-center py-12 text-red-600">
                            <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p>${error instanceof SesionCerrada ? 'Tu sesión se cerró. Recarga la página para volver a entrar.' : 'Error al cargar las estadísticas'}</p>
                        </div>
                    `;
                });
        },

        renderizarEstadisticas(data) {
            const contenedor = document.getElementById('estadisticas-contenido');
            const est = data.estadisticas;
            const canal = data.canal_no_presencial;
            
            let html = `
                <!-- Resumen General -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                    <div class="bg-blue-50 p-3 rounded-lg text-center">
                        <p class="text-2xl font-bold text-blue-600">${est.total_turnos}</p>
                        <p class="text-xs text-gray-600">Total Turnos</p>
                    </div>
                    <div class="bg-green-50 p-3 rounded-lg text-center">
                        <p class="text-2xl font-bold text-green-600">${est.turnos_atendidos}</p>
                        <p class="text-xs text-gray-600">Atendidos</p>
                    </div>
                    <div class="bg-yellow-50 p-3 rounded-lg text-center">
                        <p class="text-2xl font-bold text-yellow-600">${est.turnos_pendientes}</p>
                        <p class="text-xs text-gray-600">Pendientes</p>
                    </div>
                    <div class="bg-red-50 p-3 rounded-lg text-center">
                        <p class="text-2xl font-bold text-red-600">${est.turnos_cancelados}</p>
                        <p class="text-xs text-gray-600">Cancelados</p>
                    </div>
                </div>
                
                <!-- Tiempos -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-hospital-blue mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <p class="text-lg font-semibold text-gray-800">${est.tiempo_promedio_atencion}</p>
                                <p class="text-xs text-gray-500">Tiempo Prom. Atención</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-hospital-blue mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            <div>
                                <p class="text-lg font-semibold text-gray-800">${est.tiempo_total_atencion}</p>
                                <p class="text-xs text-gray-500">Tiempo Total</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-hospital-blue mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            <div>
                                <p class="text-lg font-semibold text-gray-800">${est.tiempo_promedio_entre_turnos}</p>
                                <p class="text-xs text-gray-500">Tiempo Entre Turnos</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Canal No Presencial -->
                ${canal.cantidad_actividades > 0 ? `
                <div class="mb-6">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                        Canal No Presencial
                    </h4>
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div class="bg-orange-50 p-3 rounded-lg text-center">
                            <p class="text-xl font-bold text-orange-600">${canal.cantidad_actividades}</p>
                            <p class="text-xs text-gray-600">Actividades</p>
                        </div>
                        <div class="bg-orange-50 p-3 rounded-lg text-center">
                            <p class="text-xl font-bold text-orange-600">${canal.tiempo_total_horas} hrs</p>
                            <p class="text-xs text-gray-600">Tiempo Total</p>
                        </div>
                    </div>
                    ${canal.detalle.length > 0 ? `
                    <div class="max-h-32 overflow-y-auto border rounded-lg">
                        <table class="w-full text-xs">
                            <thead class="bg-orange-100 sticky top-0">
                                <tr>
                                    <th class="py-2 px-2 text-left">Actividad</th>
                                    <th class="py-2 px-2 text-left">Inicio</th>
                                    <th class="py-2 px-2 text-left">Duración</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                ${canal.detalle.map(a => `
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-2 px-2">${a.actividad}</td>
                                        <td class="py-2 px-2">${a.inicio}</td>
                                        <td class="py-2 px-2">${a.duracion_minutos} min</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                    ` : ''}
                </div>
                ` : ''}
                
                <!-- Turnos por Servicio -->
                ${Object.keys(data.turnos_por_servicio).length > 0 ? `
                <div class="mb-6">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-hospital-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        Turnos por Servicio
                    </h4>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                        ${Object.entries(data.turnos_por_servicio).map(([servicio, datos]) => `
                            <div class="bg-gray-50 p-2 rounded-lg">
                                <p class="text-xs font-medium text-gray-700 truncate" title="${servicio}">${servicio}</p>
                                <p class="text-sm font-bold text-hospital-blue">${datos.atendidos}/${datos.total}</p>
                            </div>
                        `).join('')}
                    </div>
                </div>
                ` : ''}
                
                <!-- Detalle de Turnos -->
                ${data.turnos_detalle.length > 0 ? `
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-hospital-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        Últimos Turnos Atendidos
                    </h4>
                    <div class="max-h-48 overflow-y-auto border rounded-lg">
                        <table class="w-full text-xs">
                            <thead class="bg-hospital-blue text-white sticky top-0">
                                <tr>
                                    <th class="py-2 px-2 text-left">Código</th>
                                    <th class="py-2 px-2 text-left">Servicio</th>
                                    <th class="py-2 px-2 text-left">Hora</th>
                                    <th class="py-2 px-2 text-left">Duración</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y bg-white">
                                ${data.turnos_detalle.map(t => `
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-2 px-2 font-medium">${t.codigo}</td>
                                        <td class="py-2 px-2">${t.servicio}</td>
                                        <td class="py-2 px-2">${t.fecha_atencion}</td>
                                        <td class="py-2 px-2">${t.duracion_minutos}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
                ` : `
                <div class="text-center py-8 text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <p>No hay turnos atendidos en este período</p>
                </div>
                `}
            `;
            
            contenedor.innerHTML = html;
        }
    }
}

</script>

@push('estilos')
<style>
/* A11y (WCAG 2.4.7): quitar el outline SOLO para foco de mouse/touch (no feo al hacer
   click), pero CONSERVAR un indicador claro para foco de teclado vía :focus-visible. */
button:focus:not(:focus-visible),
input:focus:not(:focus-visible),
select:focus:not(:focus-visible),
textarea:focus:not(:focus-visible),
.btn:focus:not(:focus-visible),
[role="button"]:focus:not(:focus-visible),
.cursor-pointer:focus:not(:focus-visible),
label:focus:not(:focus-visible),
input[type="radio"]:focus:not(:focus-visible),
input[type="checkbox"]:focus:not(:focus-visible) {
    outline: none;
    box-shadow: none;
}

/* Indicador visible de foco para navegación por teclado */
button:focus-visible,
input:focus-visible,
select:focus-visible,
textarea:focus-visible,
.btn:focus-visible,
[role="button"]:focus-visible,
.cursor-pointer:focus-visible,
label:focus-visible,
input[type="radio"]:focus-visible,
input[type="checkbox"]:focus-visible {
    outline: 2px solid #064b9e;
    outline-offset: 2px;
}

/* ===== Refresco visual del dashboard (se conserva tal cual) ===== */
/* Chips de ícono de las métricas: azul institucional (todas iguales) */
.metric-icon { background: #e6f1fb; color: #064b9e; }
.metric-value { color: #0f2547; }
.metric-card { transition: box-shadow .2s ease, border-color .2s ease; }
.metric-card:hover { border-color: #cdd9ec; box-shadow: 0 6px 18px -10px rgba(16, 24, 40, .18); }
/* Encabezado de tablas: tinte sobrio en lugar de gris plano */
.dashboard-table thead tr { background: #f6f8fc; }
.dashboard-table th { color: #5f6b80; }

/* ===== EVOLUCIÓN 2026-09 ===== */
/* Cifra en rojo SOLO cuando pasa el límite (prioritario > 15 min). */
.metric-value.is-alerta { color: var(--color-red-700, #c10007); }
.metric-unit { font-size: 1rem; font-weight: 600; margin-left: .25rem; }

/* Números alineados por columna (los dígitos no "bailan" al refrescar cada 5 s). */
.tabla-cifras td { font-variant-numeric: tabular-nums; }

/* Servicio que nadie puede atender (0 asesores lo cubren): la fila se tiñe. */
.fila-sin-cobertura { background: var(--color-red-50, #fef2f2); }
.cifra-alerta { color: var(--color-red-700, #c10007); font-weight: 600; }
.enlace-accion { color: #064b9e; font-weight: 500; }
.enlace-accion:hover { text-decoration: underline; }
.enlace-accion:focus-visible { outline: 2px solid #064b9e; outline-offset: 2px; border-radius: 4px; }

/* Nombre del asesor: botón (se llega con teclado) que se ve igual que el texto de antes. */
.asesor-nombre { cursor: pointer; text-align: left; border-radius: 4px; }

/* Herramientas del sistema: botón blanco sobre el fondo gris (mismo material que
   las tarjetas). El gris #eef1f6 de los secundarios desaparecería sobre gray-100. */
.herramientas { padding-top: .25rem; }
.herramienta-btn { background: #ffffff; box-shadow: 0 1px 2px rgba(16, 24, 40, .08); transition: background-color .15s ease; }
.herramienta-btn:hover { background: #f6f8fc; color: #111827; }

/* Densidad por altura (reemplaza las reglas .dashboard-* con !important que había
   en layouts/admin.blade.php: solo las usaba esta vista). */
@media (min-width: 768px) and (max-height: 799px) {
    .dashboard-container > :not(:last-child) { margin-block-end: 1rem; }
    .dashboard-table th,
    .dashboard-table td { padding-top: .5rem; padding-bottom: .5rem; }
    .panel-cabeza { margin-bottom: .75rem; }
}

/* ===== Una sola vista (2026-09-14): cola y asesores lado a lado, todo más compacto ===== */
.inicio-cuerpo { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 1.25rem; align-items: start; }
@media (max-width: 1279px) { .inicio-cuerpo { grid-template-columns: minmax(0, 1fr); } }
.inicio-cuerpo .dashboard-table th,
.inicio-cuerpo .dashboard-table td { padding: .5rem .45rem; }
.inicio-cuerpo .dashboard-table th:first-child,
.inicio-cuerpo .dashboard-table td:first-child { padding-left: .75rem; }
#turnos-cola-container .celda-nombre { max-width: 10.5rem; }
#usuarios-activos-container .celda-nombre { max-width: 8.25rem; }
.inicio-cuerpo .dashboard-table td { font-size: .8125rem; }
.inicio-cuerpo .panel-cabeza { margin-bottom: .75rem; }
.inicio-cuerpo .dashboard-title { font-size: 1rem; }
.inicio-cuerpo .dashboard-badge.rounded-full { padding: .125rem .6rem; font-size: .8125rem; }
.metric-card { padding: .85rem 1rem; }
.metric-card .metric-value { font-size: 1.75rem; line-height: 2rem; margin-top: .35rem; }
.metric-card .metric-icon { width: 2.25rem; height: 2.25rem; }
.dashboard-container > :not(:last-child) { margin-block-end: 1rem; }   /* en vez del space-y-5 (1.25rem) */
.celda-nombre { display: block; max-width: 13rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.inicio-cuerpo td .celda-nombre { max-width: 12rem; }
.prio-mini { margin-left: .35rem; font-size: .75rem; font-weight: 600; color: var(--color-red-700, #c10007); white-space: nowrap; }
.sin-cobertura { display: inline-block; padding: .2rem .5rem; border-radius: .375rem; font-size: .75rem; font-weight: 600;
                 background: var(--color-red-100, #ffe2e2); color: var(--color-red-800, #9f0712); white-space: nowrap; }
.sin-cobertura:hover { text-decoration: underline; }
.sin-cobertura:focus-visible { outline: 2px solid #064b9e; outline-offset: 2px; }

/* Esqueletos: modal de estadísticas del asesor y lista de sesiones del modal de limpieza */
.est-esqueleto { display: flex; flex-direction: column; gap: 1rem; padding-block: .25rem; }
.est-esqueleto__fila { display: grid; gap: .75rem; }
.est-esqueleto__fila--4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
.est-esqueleto__fila--3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
@media (max-width: 767px) {
    .est-esqueleto__fila--4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .est-esqueleto__fila--3 { grid-template-columns: minmax(0, 1fr); }
}
.est-esqueleto__tabla { display: flex; flex-direction: column; gap: .8rem; padding: .85rem .75rem; border: 1px solid #eef1f6; border-radius: .5rem; }
.est-esqueleto__tabla .esqueleto:nth-child(odd) { width: 92%; }
.est-esqueleto__tabla .esqueleto:nth-child(even) { width: 74%; }
.usuario-esqueleto { display: flex; flex-direction: column; gap: .5rem; padding: .9rem .75rem; }
</style>
@endpush

@endsection
