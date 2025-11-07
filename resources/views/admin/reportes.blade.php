@extends('layouts.admin')

@section('title', 'Reportes')
@section('content')

<div class="bg-white rounded-lg shadow-md p-4 md:p-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <h1 class="text-xl md:text-2xl font-bold text-gray-800">Reportes del Sistema</h1>
    </div>

    <!-- Formulario de Reportes -->
    <form id="reporteForm" action="{{ route('admin.reportes.generar') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Selección de Fechas -->
        <div class="bg-white border border-gray-200 shadow-sm">
            <div class="bg-hospital-blue text-white px-6 py-4">
                <h3 class="text-lg font-semibold flex items-center">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Rango de Fechas
                </h3>
                <p class="text-blue-100 text-sm mt-1">Seleccione el período para el reporte</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="fecha_inicio" class="block text-sm font-medium text-gray-700 mb-2">
                            <svg class="w-4 h-4 inline mr-1 text-hospital-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Fecha de Inicio
                        </label>
                        <input type="date"
                               id="fecha_inicio"
                               name="fecha_inicio"
                               class="w-full border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-hospital-blue transition-colors"
                               value="{{ date('Y-m-d', strtotime('-7 days')) }}"
                               required>
                    </div>

                    <div>
                        <label for="fecha_fin" class="block text-sm font-medium text-gray-700 mb-2">
                            <svg class="w-4 h-4 inline mr-1 text-hospital-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Fecha Final
                        </label>
                        <input type="date"
                               id="fecha_fin"
                               name="fecha_fin"
                               class="w-full border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-hospital-blue transition-colors"
                               value="{{ date('Y-m-d') }}"
                               required>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros Opcionales -->
        <div class="bg-white border border-gray-200 shadow-sm">
            <div class="bg-hospital-blue text-white px-6 py-4">
                <h3 class="text-lg font-semibold flex items-center">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.414A1 1 0 013 6.707V4z"></path>
                    </svg>
                    Filtros Opcionales
                </h3>
                <p class="text-blue-100 text-sm mt-1">Filtre por usuarios y servicios específicos</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Usuarios -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            <svg class="w-4 h-4 inline mr-1 text-hospital-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                            Usuarios
                        </label>
                        <div class="max-h-48 overflow-y-auto border border-gray-300 bg-gray-50">
                            <div class="p-4 space-y-3">
                                @foreach($usuarios as $usuario)
                                    <label class="flex items-center p-2 hover:bg-white transition-colors cursor-pointer">
                                        <input type="checkbox"
                                               name="usuarios[]"
                                               value="{{ $usuario->id }}"
                                               class="border-gray-300 text-hospital-blue focus:ring-hospital-blue focus:ring-offset-0">
                                        <div class="ml-3">
                                            <span class="text-sm font-medium text-gray-900">{{ $usuario->nombre_completo }}</span>
                                            <span class="text-xs text-gray-500 block">({{ $usuario->nombre_usuario }})</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2 flex items-center">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Deje vacío para incluir todos los usuarios
                        </p>
                    </div>

                    <!-- Servicios -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            <svg class="w-4 h-4 inline mr-1 text-hospital-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                            Servicios
                        </label>
                        <div class="max-h-48 overflow-y-auto border border-gray-300 bg-gray-50">
                            <div class="p-4 space-y-3">
                                @foreach($servicios as $servicio)
                                    <label class="flex items-center p-2 hover:bg-white transition-colors cursor-pointer">
                                        <input type="checkbox"
                                               name="servicios[]"
                                               value="{{ $servicio->id }}"
                                               class="border-gray-300 text-hospital-blue focus:ring-hospital-blue focus:ring-offset-0">
                                        <span class="ml-3 text-sm font-medium text-gray-900">{{ $servicio->nombre }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2 flex items-center">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Deje vacío para incluir todos los servicios
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reportes Adicionales -->
        <div class="bg-white border border-gray-200 shadow-sm">
            <div class="bg-hospital-blue text-white px-6 py-4">
                <h3 class="text-lg font-semibold flex items-center">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                    </svg>
                    Opciones Avanzadas
                </h3>
                <p class="text-blue-100 text-sm mt-1">Incluya análisis adicionales en su reporte</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <label class="flex items-start p-4 border border-gray-200 hover:border-hospital-blue hover:bg-hospital-blue-light transition-all cursor-pointer group">
                        <input type="checkbox"
                               name="incluir_calificaciones"
                               value="1"
                               class="mt-1 border-gray-300 text-hospital-blue focus:ring-hospital-blue focus:ring-offset-0">
                        <div class="ml-3">
                            <span class="text-sm font-medium text-gray-900 group-hover:text-hospital-blue">Reportes de Calificaciones</span>
                            <p class="text-xs text-gray-500 mt-1">Incluye análisis de satisfacción del usuario</p>
                        </div>
                    </label>

                    <label class="flex items-start p-4 border border-gray-200 hover:border-hospital-blue hover:bg-hospital-blue-light transition-all cursor-pointer group">
                        <input type="checkbox"
                               name="incluir_tiempos_detallados"
                               value="1"
                               class="mt-1 border-gray-300 text-hospital-blue focus:ring-hospital-blue focus:ring-offset-0">
                        <div class="ml-3">
                            <span class="text-sm font-medium text-gray-900 group-hover:text-hospital-blue">Análisis de Tiempos</span>
                            <p class="text-xs text-gray-500 mt-1">Tiempos detallados de espera y atención</p>
                        </div>
                    </label>

                    <label class="flex items-start p-4 border border-gray-200 hover:border-hospital-blue hover:bg-hospital-blue-light transition-all cursor-pointer group">
                        <input type="checkbox"
                               name="incluir_estadisticas_avanzadas"
                               value="1"
                               class="mt-1 border-gray-300 text-hospital-blue focus:ring-hospital-blue focus:ring-offset-0">
                        <div class="ml-3">
                            <span class="text-sm font-medium text-gray-900 group-hover:text-hospital-blue">Estadísticas Avanzadas</span>
                            <p class="text-xs text-gray-500 mt-1">Métricas adicionales y comparativas</p>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Formato de Exportación -->
        <div class="bg-white border border-gray-200 shadow-sm">
            <div class="bg-hospital-blue text-white px-6 py-4">
                <h3 class="text-lg font-semibold flex items-center">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Formato de Exportación
                </h3>
                <p class="text-blue-100 text-sm mt-1">Seleccione el formato de salida del reporte</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <label class="relative flex items-start p-6 border-2 border-gray-200 cursor-pointer hover:border-hospital-blue hover:shadow-md transition-all group">
                        <input type="radio"
                               name="formato"
                               value="excel"
                               class="mt-1 text-hospital-blue focus:ring-hospital-blue focus:ring-offset-0"
                               checked>
                        <div class="ml-4">
                            <div class="flex items-center mb-2">
                                <div class="w-8 h-8 bg-green-100 flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                                    </svg>
                                </div>
                                <span class="font-semibold text-gray-900 group-hover:text-hospital-blue">Excel (.xlsx)</span>
                            </div>
                            <p class="text-sm text-gray-600">Ideal para análisis de datos, cálculos y manipulación de información</p>
                            <div class="mt-2 flex items-center text-xs text-green-600">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Recomendado para análisis
                            </div>
                        </div>
                    </label>

                    <label class="relative flex items-start p-6 border-2 border-gray-200 cursor-pointer hover:border-hospital-blue hover:shadow-md transition-all group">
                        <input type="radio"
                               name="formato"
                               value="pdf"
                               class="mt-1 text-hospital-blue focus:ring-hospital-blue focus:ring-offset-0">
                        <div class="ml-4">
                            <div class="flex items-center mb-2">
                                <div class="w-8 h-8 bg-red-100 flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                                    </svg>
                                </div>
                                <span class="font-semibold text-gray-900 group-hover:text-hospital-blue">PDF (.pdf)</span>
                            </div>
                            <p class="text-sm text-gray-600">Perfecto para presentaciones, informes ejecutivos y documentación</p>
                            <div class="mt-2 flex items-center text-xs text-red-600">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Ideal para presentaciones
                            </div>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="bg-white border border-gray-200 shadow-sm p-6">
            <div class="flex flex-col sm:flex-row gap-4">
                <button type="submit"
                        class="flex-1 bg-hospital-blue hover:bg-hospital-blue-hover text-white font-semibold py-4 px-8 transition-all duration-200 flex items-center justify-center gap-3 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span>Generar Reporte</span>
                </button>

                <button type="button"
                        onclick="limpiarFormulario()"
                        class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-semibold py-4 px-8 transition-all duration-200 flex items-center justify-center gap-3 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span>Limpiar Formulario</span>
                </button>
            </div>

            <div class="mt-4 p-4 bg-blue-50 border border-blue-200">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="text-sm text-blue-800">
                        <p class="font-medium mb-1">Información importante:</p>
                        <ul class="list-disc list-inside space-y-1 text-blue-700">
                            <li>Los reportes pueden tardar unos minutos en generarse dependiendo del rango de fechas</li>
                            <li>Los archivos Excel incluyen múltiples hojas con diferentes análisis</li>
                            <li>Los reportes PDF están optimizados para impresión y presentación</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function limpiarFormulario() {
    // Resetear fechas a valores por defecto
    document.getElementById('fecha_inicio').value = '{{ date('Y-m-d', strtotime('-7 days')) }}';
    document.getElementById('fecha_fin').value = '{{ date('Y-m-d') }}';

    // Desmarcar todos los checkboxes
    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.checked = false;
    });

    // Seleccionar Excel por defecto
    document.querySelector('input[name="formato"][value="excel"]').checked = true;

    // Mostrar mensaje de confirmación
    mostrarNotificacion('Formulario limpiado correctamente', 'success');
}

function mostrarNotificacion(mensaje, tipo = 'info') {
    // Crear elemento de notificación
    const notificacion = document.createElement('div');
    notificacion.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full`;

    if (tipo === 'success') {
        notificacion.className += ' bg-green-500 text-white';
    } else if (tipo === 'error') {
        notificacion.className += ' bg-red-500 text-white';
    } else {
        notificacion.className += ' bg-blue-500 text-white';
    }

    notificacion.innerHTML = `
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            ${mensaje}
        </div>
    `;

    document.body.appendChild(notificacion);

    // Animar entrada
    setTimeout(() => {
        notificacion.classList.remove('translate-x-full');
    }, 100);

    // Remover después de 3 segundos
    setTimeout(() => {
        notificacion.classList.add('translate-x-full');
        setTimeout(() => {
            document.body.removeChild(notificacion);
        }, 300);
    }, 3000);
}

// Validación de fechas mejorada
document.getElementById('fecha_inicio').addEventListener('change', function() {
    const fechaInicio = new Date(this.value);
    const fechaFin = new Date(document.getElementById('fecha_fin').value);

    if (fechaInicio > fechaFin) {
        document.getElementById('fecha_fin').value = this.value;
        mostrarNotificacion('Fecha final ajustada automáticamente', 'info');
    }

    // Validar que no sea una fecha futura
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
    if (fechaInicio > hoy) {
        this.value = hoy.toISOString().split('T')[0];
        mostrarNotificacion('No se pueden seleccionar fechas futuras', 'error');
    }
});

document.getElementById('fecha_fin').addEventListener('change', function() {
    const fechaInicio = new Date(document.getElementById('fecha_inicio').value);
    const fechaFin = new Date(this.value);

    if (fechaFin < fechaInicio) {
        document.getElementById('fecha_inicio').value = this.value;
        mostrarNotificacion('Fecha inicial ajustada automáticamente', 'info');
    }

    // Validar que no sea una fecha futura
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
    if (fechaFin > hoy) {
        this.value = hoy.toISOString().split('T')[0];
        mostrarNotificacion('No se pueden seleccionar fechas futuras', 'error');
    }
});

// Mostrar loading al enviar formulario
document.getElementById('reporteForm').addEventListener('submit', function(e) {
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalContent = submitBtn.innerHTML;

    // Validar que al menos un rango de fechas esté seleccionado
    const fechaInicio = document.getElementById('fecha_inicio').value;
    const fechaFin = document.getElementById('fecha_fin').value;

    if (!fechaInicio || !fechaFin) {
        e.preventDefault();
        mostrarNotificacion('Por favor seleccione un rango de fechas válido', 'error');
        return;
    }

    // VALIDAR que al menos un usuario O servicio esté seleccionado
    const usuariosSeleccionados = this.querySelectorAll('input[name="usuarios[]"]:checked').length;
    const serviciosSeleccionados = this.querySelectorAll('input[name="servicios[]"]:checked').length;

    if (usuariosSeleccionados === 0 && serviciosSeleccionados === 0) {
        e.preventDefault();
        mostrarNotificacion('Debe seleccionar al menos un usuario o un servicio para generar el reporte', 'error');
        return;
    }

    // Mostrar estado de carga
    submitBtn.innerHTML = `
        <svg class="animate-spin w-5 h-5 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>Generando Reporte...</span>
    `;
    submitBtn.disabled = true;

    // Restaurar botón después de 30 segundos (timeout de seguridad)
    setTimeout(() => {
        submitBtn.innerHTML = originalContent;
        submitBtn.disabled = false;
    }, 30000);
});

// Inicializar estado del formulario
document.addEventListener('DOMContentLoaded', function() {
    // El formulario está listo para usar
    console.log('Formulario de reportes inicializado');
});
</script>

@endsection
