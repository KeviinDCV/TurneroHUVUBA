@extends('layouts.admin')

@section('title', 'Soporte Técnico')

@section('content')
<div class="bg-white rounded-lg shadow-md p-4 md:p-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-gray-800">Soporte Técnico</h1>
            <p class="text-sm text-gray-600 mt-1">Reportar errores o solicitar mejoras en el aplicativo</p>
        </div>
        <div class="flex items-center text-sm text-gray-500 bg-blue-50 px-3 py-2 rounded-lg border border-blue-200">
            <svg class="w-4 h-4 mr-2 text-hospital-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Complete todos los campos para una mejor atención
        </div>
    </div>

    <!-- Mensajes de éxito/error -->
    @if(session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6">
            <div class="font-bold">Por favor corrija los siguientes errores:</div>
            <ul class="list-disc ml-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Formulario de Soporte -->
    <form method="POST" action="{{ route('admin.soporte.store') }}" class="space-y-6">
        @csrf

        <!-- Información del Usuario (Solo lectura) -->
        <div class="bg-white border border-gray-200 shadow-sm">
            <div class="bg-hospital-blue text-white px-6 py-4">
                <h3 class="text-lg font-semibold flex items-center">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    Información del Solicitante
                </h3>
                <p class="text-blue-100 text-sm mt-1">Datos del usuario que reporta la solicitud</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <svg class="w-4 h-4 inline mr-1 text-hospital-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            Nombre Completo
                        </label>
                        <input type="text" value="{{ $user->nombre_completo }}" readonly
                               class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <svg class="w-4 h-4 inline mr-1 text-hospital-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                            </svg>
                            Correo Electrónico
                        </label>
                        <input type="email" value="{{ $user->correo_electronico }}" readonly
                               class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-600">
                    </div>
                </div>
            </div>
        </div>

        <!-- Detalles de la Solicitud -->
        <div class="bg-white border border-gray-200 shadow-sm">
            <div class="bg-hospital-blue text-white px-6 py-4">
                <h3 class="text-lg font-semibold flex items-center">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Detalles de la Solicitud
                </h3>
                <p class="text-blue-100 text-sm mt-1">Información específica sobre el reporte o solicitud</p>
            </div>
            <div class="p-6 space-y-6">
                <!-- Tipo de Solicitud y Prioridad -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="tipo_solicitud" class="block text-sm font-medium text-gray-700 mb-2">
                            <svg class="w-4 h-4 inline mr-1 text-hospital-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a1.994 1.994 0 01-1.414.586H7a4 4 0 01-4-4V7a4 4 0 014-4z"></path>
                            </svg>
                            Tipo de Solicitud *
                        </label>
                        <select id="tipo_solicitud" name="tipo_solicitud" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent">
                            <option value="">Seleccionar tipo</option>
                            <option value="error" {{ old('tipo_solicitud') == 'error' ? 'selected' : '' }}>Reporte de Error</option>
                            <option value="mejora" {{ old('tipo_solicitud') == 'mejora' ? 'selected' : '' }}>Mejora de Funcionalidad</option>
                            <option value="nueva_funcionalidad" {{ old('tipo_solicitud') == 'nueva_funcionalidad' ? 'selected' : '' }}>Nueva Funcionalidad</option>
                            <option value="otro" {{ old('tipo_solicitud') == 'otro' ? 'selected' : '' }}>Otro</option>
                        </select>
                    </div>

                    <div>
                        <label for="prioridad" class="block text-sm font-medium text-gray-700 mb-2">
                            <svg class="w-4 h-4 inline mr-1 text-hospital-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            Prioridad *
                        </label>
                        <select id="prioridad" name="prioridad" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent">
                            <option value="">Seleccionar prioridad</option>
                            <option value="baja" {{ old('prioridad') == 'baja' ? 'selected' : '' }}>Baja</option>
                            <option value="media" {{ old('prioridad') == 'media' ? 'selected' : '' }}>Media</option>
                            <option value="alta" {{ old('prioridad') == 'alta' ? 'selected' : '' }}>Alta</option>
                            <option value="critica" {{ old('prioridad') == 'critica' ? 'selected' : '' }}>Crítica</option>
                        </select>
                    </div>
                </div>

                <!-- Asunto -->
                <div>
                    <label for="asunto" class="block text-sm font-medium text-gray-700 mb-2">
                        <svg class="w-4 h-4 inline mr-1 text-hospital-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                        </svg>
                        Asunto *
                    </label>
                    <input type="text" id="asunto" name="asunto" required maxlength="255"
                           value="{{ old('asunto') }}"
                           placeholder="Resumen breve del problema o solicitud"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent">
                </div>

                <!-- Descripción -->
                <div>
                    <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-2">
                        <svg class="w-4 h-4 inline mr-1 text-hospital-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
                        </svg>
                        Descripción Detallada *
                    </label>
                    <textarea id="descripcion" name="descripcion" required maxlength="2000" rows="5"
                              placeholder="Describa detalladamente el problema, error o funcionalidad solicitada..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent">{{ old('descripcion') }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">Máximo 2000 caracteres</p>
                </div>
            </div>
        </div>

        <!-- Campos adicionales para errores -->
        <div id="campos-error" style="display: none;">
            <div class="bg-white border border-gray-200 shadow-sm">
                <div class="bg-red-600 text-white px-6 py-4">
                    <h3 class="text-lg font-semibold flex items-center">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        Información Adicional del Error
                    </h3>
                    <p class="text-red-100 text-sm mt-1">Detalles específicos para reportes de errores</p>
                </div>
                <div class="p-6 space-y-6">
                    <!-- Pasos para Reproducir -->
                    <div>
                        <label for="pasos_reproducir" class="block text-sm font-medium text-gray-700 mb-2">
                            <svg class="w-4 h-4 inline mr-1 text-hospital-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                            Pasos para Reproducir el Error
                        </label>
                        <textarea id="pasos_reproducir" name="pasos_reproducir" maxlength="1000" rows="4"
                                  placeholder="1. Ir a la página...&#10;2. Hacer clic en...&#10;3. El error aparece cuando..."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent">{{ old('pasos_reproducir') }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Máximo 1000 caracteres</p>
                    </div>

                    <!-- Comportamiento Esperado -->
                    <div>
                        <label for="comportamiento_esperado" class="block text-sm font-medium text-gray-700 mb-2">
                            <svg class="w-4 h-4 inline mr-1 text-hospital-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Comportamiento Esperado
                        </label>
                        <textarea id="comportamiento_esperado" name="comportamiento_esperado" maxlength="1000" rows="3"
                                  placeholder="Describa qué debería suceder en lugar del error..."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent">{{ old('comportamiento_esperado') }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Máximo 1000 caracteres</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones -->
        <div class="flex justify-end space-x-3 mt-6">
            <button type="button" onclick="limpiarFormulario()"
                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition-colors">
                Limpiar Formulario
            </button>
            <button type="submit"
                    class="px-4 py-2 bg-hospital-blue text-white rounded hover:bg-hospital-blue-hover transition-colors">
                Enviar Solicitud
            </button>
        </div>
    </form>
</div>

<script>
// Mostrar/ocultar campos adicionales según el tipo de solicitud
document.getElementById('tipo_solicitud').addEventListener('change', function() {
    const camposError = document.getElementById('campos-error');
    if (this.value === 'error') {
        camposError.style.display = 'block';
    } else {
        camposError.style.display = 'none';
    }
});

// Limpiar formulario
function limpiarFormulario() {
    if (confirm('¿Está seguro de que desea limpiar el formulario?')) {
        document.querySelector('form').reset();
        document.getElementById('campos-error').style.display = 'none';
    }
}

// Mostrar campos de error si ya estaba seleccionado (para mantener estado después de validación)
document.addEventListener('DOMContentLoaded', function() {
    const tipoSolicitud = document.getElementById('tipo_solicitud').value;
    if (tipoSolicitud === 'error') {
        document.getElementById('campos-error').style.display = 'block';
    }
});
</script>
@endsection
