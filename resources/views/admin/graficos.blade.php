@extends('layouts.admin')

@section('title', 'Gráficos y Estadísticas')
@section('content')

<div class="bg-white rounded-lg shadow-md p-4 md:p-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <h1 class="text-xl md:text-2xl font-bold text-gray-800">Gráficos y Estadísticas</h1>
        <div class="flex flex-col sm:flex-row gap-2">
            <!-- Selector de Fechas -->
            <div class="flex flex-col sm:flex-row gap-2 items-center">
                <label class="text-sm font-medium text-gray-700 whitespace-nowrap">Fecha:</label>
                <input type="date" id="fechaSelector" class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent" value="{{ date('Y-m-d') }}">

                <label class="text-sm font-medium text-gray-700 whitespace-nowrap">Rango:</label>
                <select id="rangoSelector" class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent">
                    <option value="dia">Día</option>
                    <option value="semana" selected>Semana</option>
                    <option value="mes">Mes</option>
                    <option value="personalizado">Personalizado</option>
                </select>

                <!-- Campos para rango personalizado -->
                <div id="rangoPersonalizado" class="hidden flex gap-2">
                    <input type="date" id="fechaInicio" class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent">
                    <span class="text-sm text-gray-500 self-center">a</span>
                    <input type="date" id="fechaFin" class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent">
                </div>
            </div>

            <button onclick="actualizarTodosLosGraficos()" class="bg-hospital-blue hover:bg-hospital-blue-hover text-white px-4 py-2 rounded transition-colors duration-200 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Actualizar
            </button>
        </div>
    </div>

    <!-- Estadísticas Generales -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8" id="estadisticas-generales">
        <!-- Las estadísticas se cargarán dinámicamente -->
    </div>

    <!-- Grid de Gráficos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Turnos por Estado (Hoy) -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Turnos por Estado (Hoy)</h3>
            <div class="relative h-64">
                <canvas id="turnosPorEstadoChart"></canvas>
            </div>
        </div>

        <!-- Distribución de Prioridades -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Distribución de Prioridades (Hoy)</h3>
            <div class="relative h-64">
                <canvas id="distribucionPrioridadesChart"></canvas>
            </div>
        </div>

        <!-- Turnos por Hora del Día -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm lg:col-span-2">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Turnos por Hora del Día (Hoy)</h3>
            <div class="relative h-64">
                <canvas id="turnosPorHoraChart"></canvas>
            </div>
        </div>

        <!-- Turnos por Servicio (Última Semana) -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm lg:col-span-2">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Turnos por Servicio (Últimos 7 días)</h3>
            <div class="relative h-64">
                <canvas id="turnosPorServicioChart"></canvas>
            </div>
        </div>

        <!-- Rendimiento de Asesores -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm lg:col-span-2">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Top 10 Asesores (Últimos 7 días)</h3>
            <div class="relative h-64">
                <canvas id="rendimientoAsesoresChart"></canvas>
            </div>
        </div>

        <!-- Tiempo de Atención por Servicio -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm lg:col-span-2">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Tiempo Promedio de Atención por Servicio (min)</h3>
            <div class="relative h-64">
                <canvas id="tiempoAtencionChart"></canvas>
            </div>
        </div>

        <!-- Turnos por Día (Últimos 30 días) -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm lg:col-span-2">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Evolución de Turnos (Últimos 30 días)</h3>
            <div class="relative h-64">
                <canvas id="turnosPorDiaChart"></canvas>
            </div>
        </div>

    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Variables globales para los gráficos
let charts = {};

// Colores para los gráficos
const colors = {
    primary: '#064b9e',
    secondary: '#3b82f6',
    success: '#10b981',
    warning: '#f59e0b',
    danger: '#ef4444',
    info: '#06b6d4',
    purple: '#8b5cf6',
    pink: '#ec4899',
    indigo: '#6366f1',
    orange: '#f97316'
};

// Función para obtener parámetros de fecha
function obtenerParametrosFecha() {
    const fechaSelector = document.getElementById('fechaSelector').value;
    const rangoSelector = document.getElementById('rangoSelector').value;

    let params = {};

    if (rangoSelector === 'dia') {
        params.fecha = fechaSelector;
    } else if (rangoSelector === 'semana') {
        const fecha = new Date(fechaSelector);
        const fechaInicio = new Date(fecha);
        fechaInicio.setDate(fecha.getDate() - 6);

        params.fecha_inicio = fechaInicio.toISOString().split('T')[0];
        params.fecha_fin = fechaSelector;
    } else if (rangoSelector === 'mes') {
        const fecha = new Date(fechaSelector);
        const fechaInicio = new Date(fecha.getFullYear(), fecha.getMonth(), 1);
        const fechaFin = new Date(fecha.getFullYear(), fecha.getMonth() + 1, 0);

        params.fecha_inicio = fechaInicio.toISOString().split('T')[0];
        params.fecha_fin = fechaFin.toISOString().split('T')[0];
    } else if (rangoSelector === 'personalizado') {
        params.fecha_inicio = document.getElementById('fechaInicio').value;
        params.fecha_fin = document.getElementById('fechaFin').value;
    }

    return params;
}

// Función para construir URL con parámetros
function construirURL(baseURL, params) {
    const url = new URL(baseURL, window.location.origin);
    Object.keys(params).forEach(key => {
        if (params[key]) {
            url.searchParams.append(key, params[key]);
        }
    });
    return url.toString();
}

// Función para crear gráfico de dona
function crearGraficoDona(ctx, data, titulo) {
    return new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.data,
                backgroundColor: [
                    colors.success,
                    colors.warning,
                    colors.danger,
                    colors.info,
                    colors.purple
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// Función para crear gráfico de barras
function crearGraficoBarras(ctx, data, titulo, color = colors.primary) {
    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: titulo,
                data: data.data,
                backgroundColor: color + '80',
                borderColor: color,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Función para crear gráfico de líneas
function crearGraficoLineas(ctx, data, titulo) {
    return new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [{
                label: titulo,
                data: data.data,
                borderColor: colors.primary,
                backgroundColor: colors.primary + '20',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Función para cargar estadísticas generales
function cargarEstadisticasGenerales() {
    const params = obtenerParametrosFecha();
    const url = construirURL('{{ route('api.graficos.estadisticas-generales') }}', params);

    fetch(url)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('estadisticas-generales');
            container.innerHTML = `
                <div class="bg-hospital-blue rounded-lg p-4 text-white shadow-md">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium opacity-90">Turnos Hoy</p>
                            <p class="text-2xl font-bold">${data.turnos_hoy || 0}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-hospital-blue rounded-lg p-4 text-white shadow-md">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium opacity-90">Atendidos</p>
                            <p class="text-2xl font-bold">${data.turnos_atendidos_hoy || 0}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-hospital-blue rounded-lg p-4 text-white shadow-md">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium opacity-90">En Cola</p>
                            <p class="text-2xl font-bold">${data.turnos_pendientes || 0}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-hospital-blue rounded-lg p-4 text-white shadow-md">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium opacity-90">Asesores Activos</p>
                            <p class="text-2xl font-bold">${data.asesores_activos || 0}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-hospital-blue rounded-lg p-4 text-white shadow-md">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium opacity-90">Tiempo Promedio</p>
                            <p class="text-2xl font-bold">${data.tiempo_promedio_atencion ? data.tiempo_promedio_atencion.toFixed(1) + 'm' : '0m'}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-hospital-blue rounded-lg p-4 text-white shadow-md">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium opacity-90">Servicios Activos</p>
                            <p class="text-2xl font-bold">${data.servicios_activos || 0}</p>
                        </div>
                    </div>
                </div>
            `;
        })
        .catch(error => console.error('Error al cargar estadísticas:', error));
}

// Funciones para cargar cada gráfico
function cargarTurnosPorEstado() {
    const params = obtenerParametrosFecha();
    const url = construirURL('{{ route('api.graficos.turnos-por-estado') }}', params);

    fetch(url)
        .then(response => response.json())
        .then(data => {
            const ctx = document.getElementById('turnosPorEstadoChart').getContext('2d');
            if (charts.turnosPorEstado) {
                charts.turnosPorEstado.destroy();
            }
            charts.turnosPorEstado = crearGraficoDona(ctx, data, 'Turnos por Estado');
        })
        .catch(error => console.error('Error al cargar turnos por estado:', error));
}

function cargarDistribucionPrioridades() {
    const params = obtenerParametrosFecha();
    const url = construirURL('{{ route('api.graficos.distribucion-prioridades') }}', params);

    fetch(url)
        .then(response => response.json())
        .then(data => {
            const ctx = document.getElementById('distribucionPrioridadesChart').getContext('2d');
            if (charts.distribucionPrioridades) {
                charts.distribucionPrioridades.destroy();
            }
            charts.distribucionPrioridades = crearGraficoDona(ctx, data, 'Distribución de Prioridades');
        })
        .catch(error => console.error('Error al cargar distribución de prioridades:', error));
}

function cargarTurnosPorHora() {
    const params = obtenerParametrosFecha();
    const url = construirURL('{{ route('api.graficos.turnos-por-hora') }}', params);

    fetch(url)
        .then(response => response.json())
        .then(data => {
            const ctx = document.getElementById('turnosPorHoraChart').getContext('2d');
            if (charts.turnosPorHora) {
                charts.turnosPorHora.destroy();
            }
            charts.turnosPorHora = crearGraficoBarras(ctx, data, 'Turnos por Hora', colors.info);
        })
        .catch(error => console.error('Error al cargar turnos por hora:', error));
}

function cargarTurnosPorServicio() {
    const params = obtenerParametrosFecha();
    const url = construirURL('{{ route('api.graficos.turnos-por-servicio-semana') }}', params);

    fetch(url)
        .then(response => response.json())
        .then(data => {
            const ctx = document.getElementById('turnosPorServicioChart').getContext('2d');
            if (charts.turnosPorServicio) {
                charts.turnosPorServicio.destroy();
            }
            charts.turnosPorServicio = crearGraficoBarras(ctx, data, 'Turnos por Servicio', colors.success);
        })
        .catch(error => console.error('Error al cargar turnos por servicio:', error));
}

function cargarRendimientoAsesores() {
    const params = obtenerParametrosFecha();
    const url = construirURL('{{ route('api.graficos.rendimiento-asesores') }}', params);

    fetch(url)
        .then(response => response.json())
        .then(data => {
            const ctx = document.getElementById('rendimientoAsesoresChart').getContext('2d');
            if (charts.rendimientoAsesores) {
                charts.rendimientoAsesores.destroy();
            }
            charts.rendimientoAsesores = crearGraficoBarras(ctx, data, 'Turnos Atendidos', colors.purple);
        })
        .catch(error => console.error('Error al cargar rendimiento de asesores:', error));
}

function cargarTiempoAtencion() {
    const params = obtenerParametrosFecha();
    const url = construirURL('{{ route('api.graficos.tiempo-atencion-por-servicio') }}', params);

    fetch(url)
        .then(response => response.json())
        .then(data => {
            const ctx = document.getElementById('tiempoAtencionChart').getContext('2d');
            if (charts.tiempoAtencion) {
                charts.tiempoAtencion.destroy();
            }
            charts.tiempoAtencion = crearGraficoBarras(ctx, data, 'Tiempo Promedio (min)', colors.warning);
        })
        .catch(error => console.error('Error al cargar tiempo de atención:', error));
}

function cargarTurnosPorDia() {
    const params = obtenerParametrosFecha();
    const url = construirURL('{{ route('api.graficos.turnos-por-dia') }}', params);

    fetch(url)
        .then(response => response.json())
        .then(data => {
            const ctx = document.getElementById('turnosPorDiaChart').getContext('2d');
            if (charts.turnosPorDia) {
                charts.turnosPorDia.destroy();
            }
            charts.turnosPorDia = crearGraficoLineas(ctx, data, 'Turnos por Día');
        })
        .catch(error => console.error('Error al cargar turnos por día:', error));
}

// Función para actualizar todos los gráficos
function actualizarTodosLosGraficos() {
    cargarEstadisticasGenerales();
    cargarTurnosPorEstado();
    cargarDistribucionPrioridades();
    cargarTurnosPorHora();
    cargarTurnosPorServicio();
    cargarRendimientoAsesores();
    cargarTiempoAtencion();
    cargarTurnosPorDia();
}

// Cargar todos los gráficos al inicializar la página
document.addEventListener('DOMContentLoaded', function() {
    // Configurar event listeners para los selectores
    const fechaSelector = document.getElementById('fechaSelector');
    const rangoSelector = document.getElementById('rangoSelector');
    const fechaInicio = document.getElementById('fechaInicio');
    const fechaFin = document.getElementById('fechaFin');

    // Event listener para cambio de rango
    rangoSelector.addEventListener('change', function() {
        const rangoPersonalizado = document.getElementById('rangoPersonalizado');
        if (this.value === 'personalizado') {
            rangoPersonalizado.classList.remove('hidden');
            // Establecer fechas por defecto
            const hoy = new Date().toISOString().split('T')[0];
            const haceUnaSemana = new Date();
            haceUnaSemana.setDate(haceUnaSemana.getDate() - 7);
            fechaInicio.value = haceUnaSemana.toISOString().split('T')[0];
            fechaFin.value = hoy;
        } else {
            rangoPersonalizado.classList.add('hidden');
        }
        actualizarTodosLosGraficos();
    });

    // Event listeners para cambios de fecha
    fechaSelector.addEventListener('change', actualizarTodosLosGraficos);
    fechaInicio.addEventListener('change', actualizarTodosLosGraficos);
    fechaFin.addEventListener('change', actualizarTodosLosGraficos);

    // Cargar gráficos iniciales
    actualizarTodosLosGraficos();

    // Actualizar automáticamente cada 5 minutos solo si está en el día actual
    setInterval(function() {
        const fechaSeleccionada = document.getElementById('fechaSelector').value;
        const hoy = new Date().toISOString().split('T')[0];
        if (fechaSeleccionada === hoy) {
            actualizarTodosLosGraficos();
        }
    }, 300000);
});
</script>

@endsection
