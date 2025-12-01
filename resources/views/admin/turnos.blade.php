@extends('layouts.admin')

@section('title', 'Gestión de Turnos')

@section('content')
<div class="bg-white rounded-lg shadow-md p-4 md:p-6 max-w-7xl mx-auto">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-gray-800">Turnos del Día</h1>
            <p class="text-sm text-gray-500">{{ \Carbon\Carbon::now()->format('d/m/Y') }}</p>
        </div>
        <div class="flex items-center gap-2 text-sm text-gray-500">
            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
            Actualización automática
        </div>
    </div>

    <!-- Estadísticas Rápidas -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        <div class="bg-gray-50 rounded-lg p-3 text-center border border-gray-200">
            <p class="text-2xl font-bold text-gray-800" id="stat-total">{{ $estadisticas['total'] }}</p>
            <p class="text-xs text-gray-500">Total</p>
        </div>
        <div class="bg-yellow-50 rounded-lg p-3 text-center border border-yellow-200">
            <p class="text-2xl font-bold text-yellow-600" id="stat-pendientes">{{ $estadisticas['pendientes'] }}</p>
            <p class="text-xs text-gray-500">Pendientes</p>
        </div>
        <div class="bg-blue-50 rounded-lg p-3 text-center border border-blue-200">
            <p class="text-2xl font-bold text-blue-600" id="stat-llamados">{{ $estadisticas['llamados'] }}</p>
            <p class="text-xs text-gray-500">Llamados</p>
        </div>
        <div class="bg-green-50 rounded-lg p-3 text-center border border-green-200">
            <p class="text-2xl font-bold text-green-600" id="stat-atendidos">{{ $estadisticas['atendidos'] }}</p>
            <p class="text-xs text-gray-500">Atendidos</p>
        </div>
        <div class="bg-orange-50 rounded-lg p-3 text-center border border-orange-200">
            <p class="text-2xl font-bold text-orange-600" id="stat-aplazados">{{ $estadisticas['aplazados'] }}</p>
            <p class="text-xs text-gray-500">Aplazados</p>
        </div>
        <div class="bg-red-50 rounded-lg p-3 text-center border border-red-200">
            <p class="text-2xl font-bold text-red-600" id="stat-cancelados">{{ $estadisticas['cancelados'] }}</p>
            <p class="text-xs text-gray-500">Cancelados</p>
        </div>
    </div>

    <!-- Filtros -->
    <form method="GET" action="{{ route('admin.turnos') }}" class="mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- Búsqueda -->
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Buscar</label>
                <input type="text" name="search" value="{{ $search }}" placeholder="Código, número..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent">
            </div>

            <!-- Estado -->
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Estado</label>
                <select name="estado" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent">
                    <option value="">Todos</option>
                    <option value="pendiente" {{ $estado === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                    <option value="llamado" {{ $estado === 'llamado' ? 'selected' : '' }}>Llamado</option>
                    <option value="atendido" {{ $estado === 'atendido' ? 'selected' : '' }}>Atendido</option>
                    <option value="aplazado" {{ $estado === 'aplazado' ? 'selected' : '' }}>Aplazado</option>
                    <option value="cancelado" {{ $estado === 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                </select>
            </div>

            <!-- Servicio -->
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Servicio</label>
                <select name="servicio" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent">
                    <option value="">Todos</option>
                    @foreach($servicios as $s)
                        <option value="{{ $s->id }}" {{ $servicio == $s->id ? 'selected' : '' }}>{{ $s->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Asesor -->
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Asesor</label>
                <select name="asesor" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent">
                    <option value="">Todos</option>
                    @foreach($asesores as $a)
                        <option value="{{ $a->id }}" {{ $asesor == $a->id ? 'selected' : '' }}>{{ $a->nombre_completo }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Botones -->
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 bg-hospital-blue text-white px-4 py-2 rounded-md hover:bg-hospital-blue-hover transition-colors text-sm">
                    Filtrar
                </button>
                <a href="{{ route('admin.turnos') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors text-sm">
                    Limpiar
                </a>
            </div>
        </div>
    </form>

    <!-- Tabla de Turnos -->
    <div class="overflow-x-auto">
        <table class="w-full divide-y divide-gray-200 border border-gray-200 rounded-lg text-sm">
            <thead>
                <tr class="bg-hospital-blue text-white">
                    <th class="py-3 px-3 text-left font-semibold">TURNO</th>
                    <th class="py-3 px-3 text-left font-semibold">SERVICIO</th>
                    <th class="py-3 px-3 text-left font-semibold">PRIORIDAD</th>
                    <th class="py-3 px-3 text-left font-semibold">ESTADO</th>
                    <th class="py-3 px-3 text-left font-semibold">ASESOR</th>
                    <th class="py-3 px-3 text-left font-semibold">CAJA</th>
                    <th class="py-3 px-3 text-left font-semibold">CREADO</th>
                    <th class="py-3 px-3 text-left font-semibold">LLAMADO</th>
                    <th class="py-3 px-3 text-left font-semibold">ATENDIDO</th>
                    <th class="py-3 px-3 text-left font-semibold">DURACIÓN</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse($turnos as $turno)
                <tr class="hover:bg-gray-50">
                    <td class="py-3 px-3 whitespace-nowrap">
                        <span class="font-bold text-gray-900">{{ $turno->codigo_completo }}</span>
                    </td>
                    <td class="py-3 px-3 whitespace-nowrap">
                        <span class="text-gray-700">{{ $turno->servicio->nombre ?? 'N/A' }}</span>
                    </td>
                    <td class="py-3 px-3 whitespace-nowrap">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-white text-xs font-bold" 
                              style="background-color: {{ $turno->prioridad_color }}">
                            {{ $turno->prioridad_letra }}
                        </span>
                    </td>
                    <td class="py-3 px-3 whitespace-nowrap">
                        @switch($turno->estado)
                            @case('pendiente')
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pendiente</span>
                                @break
                            @case('llamado')
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Llamado</span>
                                @break
                            @case('atendido')
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Atendido</span>
                                @break
                            @case('aplazado')
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">Aplazado</span>
                                @break
                            @case('cancelado')
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">Cancelado</span>
                                @break
                            @default
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ $turno->estado }}</span>
                        @endswitch
                    </td>
                    <td class="py-3 px-3 whitespace-nowrap">
                        @if($turno->asesor)
                            <div class="flex items-center">
                                <div class="w-6 h-6 rounded-full bg-hospital-blue text-white flex items-center justify-center text-xs font-medium mr-2">
                                    {{ substr($turno->asesor->nombre_completo, 0, 1) }}
                                </div>
                                <span class="text-gray-700 text-xs">{{ $turno->asesor->nombre_completo }}</span>
                            </div>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="py-3 px-3 whitespace-nowrap">
                        @if($turno->caja)
                            <span class="text-gray-700">Caja {{ $turno->caja->numero_caja }}</span>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="py-3 px-3 whitespace-nowrap">
                        <span class="text-gray-600">{{ $turno->fecha_creacion ? $turno->fecha_creacion->format('H:i:s') : '-' }}</span>
                    </td>
                    <td class="py-3 px-3 whitespace-nowrap">
                        <span class="text-gray-600">{{ $turno->fecha_llamado ? $turno->fecha_llamado->format('H:i:s') : '-' }}</span>
                    </td>
                    <td class="py-3 px-3 whitespace-nowrap">
                        <span class="text-gray-600">{{ $turno->fecha_atencion ? $turno->fecha_atencion->format('H:i:s') : '-' }}</span>
                    </td>
                    <td class="py-3 px-3 whitespace-nowrap">
                        @if($turno->duracion_atencion)
                            <span class="text-gray-700 font-medium">{{ gmdate('i:s', $turno->duracion_atencion) }}</span>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="py-8 text-center text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                        </svg>
                        <p>No se encontraron turnos para hoy</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div class="mt-4">
        {{ $turnos->withQueryString()->links() }}
    </div>
</div>

<script>
let autoUpdateInterval = null;

function getEstadoBadge(estado) {
    const badges = {
        'pendiente': '<span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pendiente</span>',
        'llamado': '<span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Llamado</span>',
        'atendido': '<span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Atendido</span>',
        'aplazado': '<span class="px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">Aplazado</span>',
        'cancelado': '<span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">Cancelado</span>'
    };
    return badges[estado] || `<span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">${estado}</span>`;
}

function renderTurnoRow(turno) {
    const asesorHtml = turno.asesor 
        ? `<div class="flex items-center">
               <div class="w-6 h-6 rounded-full bg-hospital-blue text-white flex items-center justify-center text-xs font-medium mr-2">
                   ${turno.asesor.nombre.charAt(0)}
               </div>
               <span class="text-gray-700 text-xs">${turno.asesor.nombre}</span>
           </div>`
        : '<span class="text-gray-400">-</span>';
    
    const cajaHtml = turno.caja 
        ? `<span class="text-gray-700">Caja ${turno.caja.numero}</span>`
        : '<span class="text-gray-400">-</span>';
    
    const duracionHtml = turno.duracion_formateada 
        ? `<span class="text-gray-700 font-medium">${turno.duracion_formateada}</span>`
        : '<span class="text-gray-400">-</span>';

    return `
        <tr class="hover:bg-gray-50">
            <td class="py-3 px-3 whitespace-nowrap">
                <span class="font-bold text-gray-900">${turno.codigo_completo}</span>
            </td>
            <td class="py-3 px-3 whitespace-nowrap">
                <span class="text-gray-700">${turno.servicio?.nombre || 'N/A'}</span>
            </td>
            <td class="py-3 px-3 whitespace-nowrap">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-white text-xs font-bold" 
                      style="background-color: ${turno.prioridad_color}">
                    ${turno.prioridad_letra.charAt(0)}
                </span>
            </td>
            <td class="py-3 px-3 whitespace-nowrap">${getEstadoBadge(turno.estado)}</td>
            <td class="py-3 px-3 whitespace-nowrap">${asesorHtml}</td>
            <td class="py-3 px-3 whitespace-nowrap">${cajaHtml}</td>
            <td class="py-3 px-3 whitespace-nowrap">
                <span class="text-gray-600">${turno.fecha_creacion || '-'}</span>
            </td>
            <td class="py-3 px-3 whitespace-nowrap">
                <span class="text-gray-600">${turno.fecha_llamado || '-'}</span>
            </td>
            <td class="py-3 px-3 whitespace-nowrap">
                <span class="text-gray-600">${turno.fecha_atencion || '-'}</span>
            </td>
            <td class="py-3 px-3 whitespace-nowrap">${duracionHtml}</td>
        </tr>
    `;
}

function actualizarTurnos() {
    const params = new URLSearchParams(window.location.search);
    
    fetch(`{{ route('api.admin.turnos-hoy') }}?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            // Actualizar estadísticas
            document.getElementById('stat-total').textContent = data.estadisticas.total;
            document.getElementById('stat-pendientes').textContent = data.estadisticas.pendientes;
            document.getElementById('stat-llamados').textContent = data.estadisticas.llamados;
            document.getElementById('stat-atendidos').textContent = data.estadisticas.atendidos;
            document.getElementById('stat-aplazados').textContent = data.estadisticas.aplazados;
            document.getElementById('stat-cancelados').textContent = data.estadisticas.cancelados;
            
            // Actualizar tabla de turnos
            const tbody = document.querySelector('table tbody');
            if (data.turnos && data.turnos.length > 0) {
                tbody.innerHTML = data.turnos.map(turno => renderTurnoRow(turno)).join('');
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="10" class="py-8 text-center text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                            </svg>
                            <p>No se encontraron turnos para hoy</p>
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => console.error('Error actualizando turnos:', error));
}

// Actualización automática cada 5 segundos
function startAutoUpdate() {
    if (autoUpdateInterval) clearInterval(autoUpdateInterval);
    autoUpdateInterval = setInterval(actualizarTurnos, 5000);
}

// Iniciar actualización automática
startAutoUpdate();

// Actualizar inmediatamente al cargar
actualizarTurnos();
</script>
@endsection
