@extends('layouts.admin')

@section('title', 'Dashboard')
@section('content')

                <div class="bg-white rounded-lg shadow-md p-4 md:p-6 max-w-7xl mx-auto space-y-6">
                    <!-- Botón para limpiar sesiones expiradas -->
                    <div class="flex justify-end mb-4">
                        <button
                            onclick="cleanExpiredSessions()"
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors duration-200 flex items-center gap-2"
                            id="cleanSessionsBtn">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Limpiar Sesiones Expiradas
                        </button>
                    </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Advisor Status Table -->
                <div class="bg-white rounded-lg shadow">
                    <div class="bg-hospital-blue text-white p-3 rounded-t-lg">
                        <div class="grid grid-cols-3 gap-4 text-sm font-semibold">
                            <div>ASESOR</div>
                            <div>DISPONIBILIDAD</div>
                            <div>ESTADO</div>
                        </div>
                    </div>
                    <div class="max-h-96 overflow-y-auto">
                        @foreach($advisorData as $advisor)
                        <div class="grid grid-cols-3 gap-4 p-3 text-sm border-b {{ $advisor['availability'] === 'CAJA CERRADA' ? 'bg-blue-50' : 'bg-white' }}">
                            <div>{{ $advisor['name'] }}</div>
                            <div class="{{ $advisor['availability'] === 'DISPONIBLE' ? 'text-hospital-blue' : 'text-blue-800' }}">
                                {{ $advisor['availability'] }}
                            </div>
                            <div class="{{ $advisor['status'] === 'DISPONIBLE' ? 'text-hospital-blue' : 'text-blue-800' }}">
                                {{ $advisor['status'] }}
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Service Summary -->
                <div class="bg-white rounded-lg shadow">
                    <div class="bg-hospital-blue text-white p-3 rounded-t-lg">
                        <div class="grid grid-cols-2 gap-4 text-sm font-semibold">
                            <div>SERVICIO</div>
                            <div>TERMINADOS</div>
                        </div>
                    </div>
                    <div>
                        @foreach($serviceData as $service)
                        <div class="grid grid-cols-2 gap-4 p-3 text-sm border-b bg-white">
                            <div>{{ $service['service'] }}</div>
                            <div class="text-hospital-blue">{{ $service['count'] }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Advisor Terminals -->
                <div class="bg-white rounded-lg shadow">
                    <div class="bg-hospital-blue text-white p-3 rounded-t-lg">
                        <div class="grid grid-cols-2 gap-4 text-sm font-semibold">
                            <div>ASESOR</div>
                            <div>TERMINADOS</div>
                        </div>
                    </div>
                    <div class="max-h-96 overflow-y-auto">
                        @foreach($advisorTerminals as $advisor)
                        <div class="grid grid-cols-2 gap-4 p-3 text-sm border-b bg-white">
                            <div>{{ $advisor['name'] }}</div>
                            <div class="text-hospital-blue">{{ $advisor['terminals'] }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Queue Information -->
                <div class="space-y-4">
                    <div class="bg-white rounded-lg shadow">
                        <div class="bg-hospital-blue text-white p-3 rounded-t-lg">
                            <div class="grid grid-cols-2 gap-4 text-sm font-semibold">
                                <div>SERVICIO</div>
                                <div>TURNOS AUSENTES</div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow">
                        <div class="bg-hospital-blue text-white p-3 rounded-t-lg">
                            <div class="grid grid-cols-2 gap-4 text-sm font-semibold">
                                <div>SERVICIO</div>
                                <div>TURNOS EN COLA</div>
                            </div>
                        </div>
                        <div>
                            @foreach($queueData as $service)
                            <div class="grid grid-cols-2 gap-4 p-3 text-sm border-b bg-white">
                                <div>{{ $service['service'] }}</div>
                                <div class="text-hospital-blue">{{ $service['count'] }}</div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow">
                        <div class="bg-hospital-blue text-white p-3 rounded-t-lg">
                            <div class="grid grid-cols-2 gap-4 text-sm font-semibold">
                                <div>CALIFICACIÓN</div>
                                <div>CONTEO</div>
                            </div>
                        </div>
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
</script>

@endsection
