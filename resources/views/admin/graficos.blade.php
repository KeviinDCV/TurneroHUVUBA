@extends('layouts.admin')

@section('title', 'Gráficos y Estadísticas')
@section('content')

<div class="bg-white rounded-lg shadow-md p-4 md:p-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-gray-800">Analytics Dashboard</h1>
            <p class="text-sm text-gray-600 mt-1">Análisis de datos actuales e históricos del sistema de turnos</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-2">
            <!-- Selector de Fechas -->
            <div class="flex flex-col sm:flex-row gap-2 items-center">
                <label class="text-sm font-medium text-gray-700 whitespace-nowrap">Fecha:</label>
                <input type="date" id="fechaSelector" class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent" value="{{ date('Y-m-d') }}">

                <label class="text-sm font-medium text-gray-700 whitespace-nowrap">Rango:</label>
                <select id="rangoSelector" class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent">
                    <option value="dia" selected>Día</option>
                    <option value="semana">Semana</option>
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

    <!-- Tab Navigation -->
    <div class="mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button id="tab-actual" onclick="cambiarTab('actual')" class="tab-button active whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Datos Actuales
                    </div>
                </button>
                <button id="tab-historico" onclick="cambiarTab('historico')" class="tab-button whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Datos Históricos
                    </div>
                </button>
            </nav>
        </div>
    </div>

    <!-- Tab Content Container -->
    <div id="tab-content">

    <!-- DATOS ACTUALES TAB -->
    <div id="content-actual" class="tab-content active">

    <!-- Estadísticas Generales -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8" id="estadisticas-generales">
        <!-- Las estadísticas se cargarán dinámicamente -->
    </div>

    <!-- Grid de Gráficos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Turnos por Estado (Hoy) -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Turnos por Estado (Hoy)</h3>
                <button onclick="exportarGrafico('turnosPorEstadoChart')" class="text-hospital-blue hover:text-hospital-blue-hover p-2 rounded-md hover:bg-hospital-blue-light transition-colors duration-200" title="Exportar gráfico">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </button>
            </div>
            <div class="relative h-64">
                <canvas id="turnosPorEstadoChart"></canvas>
            </div>
        </div>

        <!-- Distribución de Prioridades -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Distribución de Prioridades (Hoy)</h3>
                <button onclick="exportarGrafico('distribucionPrioridadesChart')" class="text-hospital-blue hover:text-hospital-blue-hover p-2 rounded-md hover:bg-hospital-blue-light transition-colors duration-200" title="Exportar gráfico">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </button>
            </div>
            <div class="relative h-64">
                <canvas id="distribucionPrioridadesChart"></canvas>
            </div>
        </div>

        <!-- Turnos por Hora del Día -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm lg:col-span-2">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Turnos por Hora del Día (Hoy)</h3>
                <button onclick="exportarGrafico('turnosPorHoraChart')" class="text-hospital-blue hover:text-hospital-blue-hover p-2 rounded-md hover:bg-hospital-blue-light transition-colors duration-200" title="Exportar gráfico">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </button>
            </div>
            <div class="relative h-64">
                <canvas id="turnosPorHoraChart"></canvas>
            </div>
        </div>

        <!-- Turnos por Servicio (Hoy) -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm lg:col-span-2">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Turnos por Servicio (Hoy)</h3>
                <button onclick="exportarGrafico('turnosPorServicioChart')" class="text-hospital-blue hover:text-hospital-blue-hover p-2 rounded-md hover:bg-hospital-blue-light transition-colors duration-200" title="Exportar gráfico">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </button>
            </div>
            <div class="relative h-64">
                <canvas id="turnosPorServicioChart"></canvas>
            </div>
        </div>

        <!-- Top Asesores (Hoy) -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm lg:col-span-2">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Top Asesores (Hoy)</h3>
                <button onclick="exportarGrafico('rendimientoAsesoresChart')" class="text-hospital-blue hover:text-hospital-blue-hover p-2 rounded-md hover:bg-hospital-blue-light transition-colors duration-200" title="Exportar gráfico">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </button>
            </div>
            <div class="relative h-64">
                <canvas id="rendimientoAsesoresChart"></canvas>
            </div>
        </div>

        <!-- Tiempo de Atención por Servicio -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm lg:col-span-2">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Tiempo Promedio de Atención por Servicio (min)</h3>
                <button onclick="exportarGrafico('tiempoAtencionChart')" class="text-hospital-blue hover:text-hospital-blue-hover p-2 rounded-md hover:bg-hospital-blue-light transition-colors duration-200" title="Exportar gráfico">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </button>
            </div>
            <div class="relative h-64">
                <canvas id="tiempoAtencionChart"></canvas>
            </div>
        </div>

        <!-- Distribución de Turnos por Hora (Hoy) -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm lg:col-span-2">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Distribución de Turnos por Hora (Hoy)</h3>
                <button onclick="exportarGrafico('turnosPorDiaChart')" class="text-hospital-blue hover:text-hospital-blue-hover p-2 rounded-md hover:bg-hospital-blue-light transition-colors duration-200" title="Exportar gráfico">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </button>
            </div>
            <div class="relative h-64">
                <canvas id="turnosPorDiaChart"></canvas>
            </div>
        </div>

    </div>

    </div> <!-- End content-actual -->

    <!-- DATOS HISTÓRICOS TAB -->
    <div id="content-historico" class="tab-content hidden">

        <!-- Filtros Avanzados Históricos -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm mb-6">
            <div class="flex items-center mb-4">
                <svg class="w-5 h-5 text-hospital-blue mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                </svg>
                <h3 class="text-lg font-semibold text-gray-800">Filtros de Análisis</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Rango de Fechas -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Período de Análisis</label>
                    <select id="periodoSelectorHistorico" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent transition-colors duration-200">
                        <option value="7">Últimos 7 días</option>
                        <option value="30" selected>Últimos 30 días</option>
                        <option value="90">Últimos 3 meses</option>
                        <option value="365">Último año</option>
                        <option value="personalizado">Personalizado</option>
                    </select>
                </div>

                <!-- Fechas Personalizadas -->
                <div id="fechasPersonalizadasHistorico" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Inicio</label>
                    <input type="date" id="fechaInicioHistorico" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent transition-colors duration-200">
                </div>

                <div id="fechasPersonalizadasHistorico2" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Fin</label>
                    <input type="date" id="fechaFinHistorico" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent transition-colors duration-200">
                </div>

                <!-- Filtro por Servicio -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Servicio</label>
                    <select id="servicioFiltroHistorico" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent transition-colors duration-200">
                        <option value="">Todos los servicios</option>
                        <!-- Se cargarán dinámicamente -->
                    </select>
                </div>

                <!-- Botones de Acción -->
                <div class="flex flex-col gap-2">
                    <button onclick="actualizarDashboardHistorico()" class="bg-hospital-blue hover:bg-hospital-blue-hover text-white px-4 py-2 rounded-md transition-colors duration-200 flex items-center justify-center gap-2 font-medium text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Actualizar
                    </button>
                    <button onclick="exportarDashboardHistorico()" class="bg-hospital-blue-light border border-hospital-blue text-hospital-blue hover:bg-hospital-blue hover:text-white px-4 py-2 rounded-md transition-colors duration-200 flex items-center justify-center gap-2 font-medium text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Exportar
                    </button>
                </div>
            </div>
        </div>

        <!-- Estadísticas Generales Históricas -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8" id="estadisticas-historicas">
            <!-- Loading placeholder -->
            <div class="bg-hospital-blue rounded-lg p-4 text-white shadow-md animate-pulse">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-8 w-8 bg-white bg-opacity-20 rounded"></div>
                    </div>
                    <div class="ml-3 flex-1">
                        <div class="h-3 bg-white bg-opacity-20 rounded mb-2"></div>
                        <div class="h-6 bg-white bg-opacity-30 rounded"></div>
                    </div>
                </div>
            </div>
            <div class="bg-hospital-blue rounded-lg p-4 text-white shadow-md animate-pulse">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-8 w-8 bg-white bg-opacity-20 rounded"></div>
                    </div>
                    <div class="ml-3 flex-1">
                        <div class="h-3 bg-white bg-opacity-20 rounded mb-2"></div>
                        <div class="h-6 bg-white bg-opacity-30 rounded"></div>
                    </div>
                </div>
            </div>
            <div class="bg-hospital-blue rounded-lg p-4 text-white shadow-md animate-pulse">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-8 w-8 bg-white bg-opacity-20 rounded"></div>
                    </div>
                    <div class="ml-3 flex-1">
                        <div class="h-3 bg-white bg-opacity-20 rounded mb-2"></div>
                        <div class="h-6 bg-white bg-opacity-30 rounded"></div>
                    </div>
                </div>
            </div>
            <div class="bg-hospital-blue rounded-lg p-4 text-white shadow-md animate-pulse">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-8 w-8 bg-white bg-opacity-20 rounded"></div>
                    </div>
                    <div class="ml-3 flex-1">
                        <div class="h-3 bg-white bg-opacity-20 rounded mb-2"></div>
                        <div class="h-6 bg-white bg-opacity-30 rounded"></div>
                    </div>
                </div>
            </div>
            <div class="bg-hospital-blue rounded-lg p-4 text-white shadow-md animate-pulse">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-8 w-8 bg-white bg-opacity-20 rounded"></div>
                    </div>
                    <div class="ml-3 flex-1">
                        <div class="h-3 bg-white bg-opacity-20 rounded mb-2"></div>
                        <div class="h-6 bg-white bg-opacity-30 rounded"></div>
                    </div>
                </div>
            </div>
            <div class="bg-hospital-blue rounded-lg p-4 text-white shadow-md animate-pulse">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-8 w-8 bg-white bg-opacity-20 rounded"></div>
                    </div>
                    <div class="ml-3 flex-1">
                        <div class="h-3 bg-white bg-opacity-20 rounded mb-2"></div>
                        <div class="h-6 bg-white bg-opacity-30 rounded"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grid de Gráficos Históricos -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- 1. Volumen de Turnos por Tiempo -->
            <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm lg:col-span-2">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Evolución de Turnos Histórica</h3>
                    <div class="flex gap-2">
                        <select id="periodoGraficoHistorico" class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent transition-colors duration-200">
                            <option value="daily">Diario</option>
                            <option value="weekly">Semanal</option>
                            <option value="monthly">Mensual</option>
                        </select>
                        <button onclick="exportarGrafico('volumenChartHistorico')" class="text-hospital-blue hover:text-hospital-blue-hover p-2 rounded-md hover:bg-hospital-blue-light transition-colors duration-200" title="Exportar gráfico">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="relative h-64">
                    <canvas id="volumenChartHistorico"></canvas>
                </div>
            </div>

            <!-- 2. Distribución por Servicios -->
            <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Distribución por Servicios</h3>
                    <button onclick="exportarGrafico('serviciosChartHistorico')" class="text-hospital-blue hover:text-hospital-blue-hover p-2 rounded-md hover:bg-hospital-blue-light transition-colors duration-200" title="Exportar gráfico">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </button>
                </div>
                <div class="relative h-64">
                    <canvas id="serviciosChartHistorico"></canvas>
                </div>
            </div>

            <!-- 3. Estados Finales -->
            <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Estados Finales</h3>
                    <button onclick="exportarGrafico('estadosChartHistorico')" class="text-hospital-blue hover:text-hospital-blue-hover p-2 rounded-md hover:bg-hospital-blue-light transition-colors duration-200" title="Exportar gráfico">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </button>
                </div>
                <div class="relative h-64">
                    <canvas id="estadosChartHistorico"></canvas>
                </div>
            </div>

            <!-- 4. Horas Pico -->
            <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm lg:col-span-2">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Análisis de Horas Pico</h3>
                    <button onclick="exportarGrafico('horasPicoChartHistorico')" class="text-hospital-blue hover:text-hospital-blue-hover p-2 rounded-md hover:bg-hospital-blue-light transition-colors duration-200" title="Exportar gráfico">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </button>
                </div>
                <div class="relative h-64">
                    <canvas id="horasPicoChartHistorico"></canvas>
                </div>
            </div>

            <!-- 5. Tiempo Promedio por Servicio -->
            <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Tiempo Promedio de Atención</h3>
                    <button onclick="exportarGrafico('tiempoAtencionChartHistorico')" class="text-hospital-blue hover:text-hospital-blue-hover p-2 rounded-md hover:bg-hospital-blue-light transition-colors duration-200" title="Exportar gráfico">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </button>
                </div>
                <div class="relative h-64">
                    <canvas id="tiempoAtencionChartHistorico"></canvas>
                </div>
            </div>

            <!-- 6. Rendimiento de Asesores -->
            <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Top Asesores</h3>
                    <button onclick="exportarGrafico('asesoresChartHistorico')" class="text-hospital-blue hover:text-hospital-blue-hover p-2 rounded-md hover:bg-hospital-blue-light transition-colors duration-200" title="Exportar gráfico">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </button>
                </div>
                <div class="relative h-64">
                    <canvas id="asesoresChartHistorico"></canvas>
                </div>
            </div>

        </div>

    </div> <!-- End content-historico -->

    </div> <!-- End tab-content -->

</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>

<style>
/* Tab Styles */
.tab-button {
    border-bottom-color: transparent;
    color: #6b7280;
}

.tab-button.active {
    border-bottom-color: #064b9e;
    color: #064b9e;
}

.tab-button:hover {
    color: #064b9e;
    border-bottom-color: #93c5fd;
}

.tab-content {
    display: block;
}

.tab-content.hidden {
    display: none;
}

.tab-content.active {
    display: block;
}
</style>

<script>
// Variables globales para los gráficos
let charts = {};
let chartsHistorico = {};
let currentTab = 'actual';
let currentFiltersHistorico = {};

// Hospital brand color palette for charts
// Historical Data section uses monochromatic hospital-blue theme for professional consistency
// Current Data section maintains varied colors for data differentiation
const colors = {
    primary: '#064b9e',    // Hospital Blue - main brand color (dominant in Historical Data)
    secondary: '#3b82f6',  // Light Blue - secondary brand color
    success: '#10b981',    // Green - positive outcomes, completed actions
    warning: '#f59e0b',    // Amber - caution, pending states
    danger: '#ef4444',     // Red - urgent, critical, cancelled states
    info: '#06b6d4',       // Cyan - informational, in-progress states
    purple: '#8b5cf6',     // Purple - specialty services, performance metrics
    pink: '#ec4899',       // Pink - care-related metrics
    indigo: '#6366f1',     // Indigo - professional services
    orange: '#f97316'      // Orange - attention, delayed states
};

// ========================================
// FUNCIONES DE NAVEGACIÓN POR TABS
// ========================================

// Función para cambiar entre tabs
function cambiarTab(tab) {
    currentTab = tab;

    // Actualizar botones de tab
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });
    document.getElementById(`tab-${tab}`).classList.add('active');

    // Mostrar/ocultar contenido
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
        content.classList.add('hidden');
    });
    document.getElementById(`content-${tab}`).classList.remove('hidden');
    document.getElementById(`content-${tab}`).classList.add('active');

    // Cargar datos según el tab activo
    if (tab === 'actual') {
        actualizarTodosLosGraficos();
    } else if (tab === 'historico') {
        if (!chartsHistorico.initialized) {
            configurarEventListenersHistorico();
            cargarServiciosParaFiltroHistorico();
            chartsHistorico.initialized = true;
        }
        actualizarDashboardHistorico();
    }
}

// ========================================
// FUNCIONES PARA DATOS ACTUALES (EXISTENTES)
// ========================================

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
    // Generate hospital blue variations for consistent branding
    const generateHospitalBlueShades = (count) => {
        const baseColor = colors.primary; // #064b9e
        const shades = [];

        for (let i = 0; i < count; i++) {
            if (i === 0) {
                shades.push(baseColor); // Pure hospital blue for first item
            } else {
                // Create variations by adjusting opacity
                const opacity = 1 - (i * 0.15);
                const rgb = hexToRgb(baseColor);
                shades.push(`rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${Math.max(0.3, opacity)})`);
            }
        }
        return shades;
    };

    // Helper function to convert hex to rgb
    const hexToRgb = (hex) => {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16)
        } : null;
    };

    return new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.data,
                // Hospital blue monochromatic theme for consistency
                backgroundColor: generateHospitalBlueShades(data.labels.length),
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 00-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 010 7.75"></path>
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
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
            charts.turnosPorHora = crearGraficoBarras(ctx, data, 'Turnos por Hora', colors.primary);
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
            charts.turnosPorServicio = crearGraficoBarras(ctx, data, 'Turnos por Servicio', colors.primary);
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
            charts.rendimientoAsesores = crearGraficoBarras(ctx, data, 'Turnos Atendidos', colors.primary);
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
            charts.tiempoAtencion = crearGraficoBarras(ctx, data, 'Tiempo Promedio (min)', colors.primary);
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

// Mostrar estado de carga para datos actuales
function mostrarCargando(chartId) {
    const canvas = document.getElementById(chartId);
    if (!canvas) return;
    const container = canvas.parentElement;
    container.innerHTML = `
        <div class="flex items-center justify-center h-full bg-hospital-blue rounded-lg p-4">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-white"></div>
            <span class="ml-3 text-white font-medium">Cargando...</span>
        </div>
    `;
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

// ========================================
// FUNCIONES PARA DATOS HISTÓRICOS
// ========================================

// Configurar event listeners para históricos
function configurarEventListenersHistorico() {
    // Selector de período
    document.getElementById('periodoSelectorHistorico').addEventListener('change', function() {
        const valor = this.value;
        const fechasPersonalizadas = document.getElementById('fechasPersonalizadasHistorico');
        const fechasPersonalizadas2 = document.getElementById('fechasPersonalizadasHistorico2');

        if (valor === 'personalizado') {
            fechasPersonalizadas.classList.remove('hidden');
            fechasPersonalizadas2.classList.remove('hidden');
        } else {
            fechasPersonalizadas.classList.add('hidden');
            fechasPersonalizadas2.classList.add('hidden');
        }

        // IMPORTANTE: Actualizar el dashboard cuando cambie el período
        actualizarDashboardHistorico();
    });

    // Selector de período para gráfico de volumen
    document.getElementById('periodoGraficoHistorico').addEventListener('change', function() {
        cargarVolumenPorTiempoHistorico();
    });

    // Filtros de fecha
    document.getElementById('fechaInicioHistorico').addEventListener('change', actualizarDashboardHistorico);
    document.getElementById('fechaFinHistorico').addEventListener('change', actualizarDashboardHistorico);
    document.getElementById('servicioFiltroHistorico').addEventListener('change', actualizarDashboardHistorico);
}

// Obtener parámetros de filtros históricos
function obtenerParametrosFiltrosHistorico() {
    const periodo = document.getElementById('periodoSelectorHistorico').value;
    let fechaInicio, fechaFin;

    if (periodo === 'personalizado') {
        fechaInicio = document.getElementById('fechaInicioHistorico').value;
        fechaFin = document.getElementById('fechaFinHistorico').value;
    } else {
        const dias = parseInt(periodo);
        fechaFin = new Date().toISOString().split('T')[0];
        fechaInicio = new Date(Date.now() - dias * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    }

    const servicioId = document.getElementById('servicioFiltroHistorico').value;

    return {
        fecha_inicio: fechaInicio,
        fecha_fin: fechaFin,
        servicio_id: servicioId || undefined
    };
}

// Cargar servicios para el filtro histórico
function cargarServiciosParaFiltroHistorico() {
    fetch('{{ route('api.servicios-activos') }}')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('servicioFiltroHistorico');
            select.innerHTML = '<option value="">Todos los servicios</option>';

            data.forEach(servicio => {
                const option = document.createElement('option');
                option.value = servicio.id;
                option.textContent = servicio.nombre;
                select.appendChild(option);
            });
        })
        .catch(error => console.error('Error al cargar servicios:', error));
}

// Actualizar todo el dashboard histórico
function actualizarDashboardHistorico() {
    currentFiltersHistorico = obtenerParametrosFiltrosHistorico();

    cargarEstadisticasHistoricas();
    cargarVolumenPorTiempoHistorico();
    cargarDistribucionServiciosHistorico();
    cargarDistribucionEstadosHistorico();
    cargarHorasPicoHistorico();
    cargarTiempoAtencionHistorico();
    cargarRendimientoAsesoresHistorico();
}

// Mostrar estado de carga
function mostrarCargandoHistorico(chartId) {
    const canvas = document.getElementById(chartId);
    if (!canvas) return;
    const container = canvas.parentElement;
    container.innerHTML = `
        <div class="flex items-center justify-center h-full bg-hospital-blue rounded-lg p-4">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-white"></div>
            <span class="ml-3 text-white font-medium">Cargando...</span>
        </div>
    `;
}

// Mostrar error
function mostrarErrorHistorico(chartId, mensaje) {
    const canvas = document.getElementById(chartId);
    if (!canvas) return;
    const container = canvas.parentElement;
    container.innerHTML = `
        <div class="flex items-center justify-center h-full bg-red-50 border border-red-200 rounded-lg p-4 text-red-600">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="font-medium">${mensaje}</span>
        </div>
    `;
}

// Restaurar canvas
function restaurarCanvasHistorico(chartId) {
    const container = document.getElementById(chartId).parentElement;
    container.innerHTML = `<canvas id="${chartId}"></canvas>`;
}

// Cargar estadísticas generales históricas
function cargarEstadisticasHistoricas() {
    const params = new URLSearchParams(currentFiltersHistorico);
    const url = '{{ route('api.graficos.historial.estadisticas-generales') }}?' + params;

    fetch(url)
        .then(response => {
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return response.json();
        })
        .then(data => {
            if (data.error) throw new Error(data.error);

            const container = document.getElementById('estadisticas-historicas');
            container.innerHTML = `
                <div class="bg-hospital-blue rounded-lg p-4 text-white shadow-md">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium opacity-90">Total Histórico</p>
                            <p class="text-2xl font-bold">${data.total_turnos_historicos || 0}</p>
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
                            <p class="text-2xl font-bold">${data.turnos_atendidos_historicos || 0}</p>
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
                            <p class="text-2xl font-bold">${data.tiempo_promedio_atencion_historico ? data.tiempo_promedio_atencion_historico.toFixed(1) + 'm' : '0m'}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-hospital-blue rounded-lg p-4 text-white shadow-md">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium opacity-90">Tasa Atención</p>
                            <p class="text-2xl font-bold">${data.tasa_atencion ? data.tasa_atencion.toFixed(1) + '%' : '0%'}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-hospital-blue rounded-lg p-4 text-white shadow-md">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium opacity-90">Servicios</p>
                            <p class="text-2xl font-bold">${data.servicios_utilizados || 0}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-hospital-blue rounded-lg p-4 text-white shadow-md">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 00-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 010 7.75"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium opacity-90">Asesores</p>
                            <p class="text-2xl font-bold">${data.asesores_participantes || 0}</p>
                        </div>
                    </div>
                </div>
            `;
        })
        .catch(error => {
            console.error('Error al cargar estadísticas históricas:', error);
            document.getElementById('estadisticas-historicas').innerHTML = '<div class="col-span-6 bg-red-50 border border-red-200 rounded-lg p-4 text-center text-red-600 flex items-center justify-center gap-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>Error al cargar estadísticas</div>';
        });
}

// ========================================
// FUNCIONES PARA DATOS HISTÓRICOS
// ========================================



// Cargar servicios para el filtro histórico
function cargarServiciosParaFiltroHistorico() {
    fetch('{{ route('api.servicios-activos') }}')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('servicioFiltroHistorico');
            select.innerHTML = '<option value="">Todos los servicios</option>';

            data.forEach(servicio => {
                const option = document.createElement('option');
                option.value = servicio.id;
                option.textContent = servicio.nombre;
                select.appendChild(option);
            });
        })
        .catch(error => console.error('Error al cargar servicios:', error));
}

// Actualizar todo el dashboard histórico
function actualizarDashboardHistorico() {
    currentFiltersHistorico = obtenerParametrosFiltrosHistorico();

    cargarEstadisticasHistoricas();
    cargarVolumenPorTiempoHistorico();
    cargarDistribucionServiciosHistorico();
    cargarDistribucionEstadosHistorico();
    cargarHorasPicoHistorico();
    cargarTiempoAtencionHistorico();
    cargarRendimientoAsesoresHistorico();
}

// Cargar estadísticas generales históricas
function cargarEstadisticasHistoricas() {
    const params = new URLSearchParams(currentFiltersHistorico);
    const url = '{{ route('api.graficos.historial.estadisticas-generales') }}?' + params;

    fetch(url)
        .then(response => {
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return response.json();
        })
        .then(data => {
            if (data.error) throw new Error(data.error);

            const container = document.getElementById('estadisticas-historicas');
            container.innerHTML = `
                <div class="bg-hospital-blue rounded-lg p-4 text-white shadow-md">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium opacity-90">Total Histórico</p>
                            <p class="text-2xl font-bold">${data.total_turnos_historicos || 0}</p>
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
                            <p class="text-2xl font-bold">${data.turnos_atendidos_historicos || 0}</p>
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
                            <p class="text-2xl font-bold">${data.tiempo_promedio_atencion_historico ? data.tiempo_promedio_atencion_historico.toFixed(1) + 'm' : '0m'}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-hospital-blue rounded-lg p-4 text-white shadow-md">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium opacity-90">Tasa Atención</p>
                            <p class="text-2xl font-bold">${data.tasa_atencion ? data.tasa_atencion.toFixed(1) + '%' : '0%'}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-hospital-blue rounded-lg p-4 text-white shadow-md">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium opacity-90">Servicios</p>
                            <p class="text-2xl font-bold">${data.servicios_utilizados || 0}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-hospital-blue rounded-lg p-4 text-white shadow-md">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 00-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 010 7.75"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium opacity-90">Asesores</p>
                            <p class="text-2xl font-bold">${data.asesores_participantes || 0}</p>
                        </div>
                    </div>
                </div>
            `;
        })
        .catch(error => {
            console.error('Error al cargar estadísticas históricas:', error);
            document.getElementById('estadisticas-historicas').innerHTML = '<div class="col-span-6 bg-red-50 border border-red-200 rounded-lg p-4 text-center text-red-600 flex items-center justify-center gap-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>Error al cargar estadísticas</div>';
        });
}

// Cargar gráfico de volumen por tiempo histórico
function cargarVolumenPorTiempoHistorico() {
    const params = new URLSearchParams(currentFiltersHistorico);
    params.set('periodo', document.getElementById('periodoGraficoHistorico').value);
    const url = '{{ route('api.graficos.historial.volumen-por-tiempo') }}?' + params;

    fetch(url)
        .then(response => {
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return response.json();
        })
        .then(data => {
            if (data.error) throw new Error(data.error);

            const ctx = document.getElementById('volumenChartHistorico').getContext('2d');
            if (chartsHistorico.volumen) {
                chartsHistorico.volumen.destroy();
            }

            chartsHistorico.volumen = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Turnos Creados',
                        data: data.data,
                        borderColor: colors.primary,
                        backgroundColor: colors.primary + '20',
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
        })
        .catch(error => {
            console.error('Error al cargar volumen histórico:', error);
        });
}

// Cargar distribución por servicios histórico
function cargarDistribucionServiciosHistorico() {
    const params = new URLSearchParams(currentFiltersHistorico);
    const url = '{{ route('api.graficos.historial.distribucion-servicios') }}?' + params;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            const ctx = document.getElementById('serviciosChartHistorico').getContext('2d');
            if (chartsHistorico.servicios) {
                chartsHistorico.servicios.destroy();
            }

            // Monochromatic hospital-blue theme with subtle variations
            const generateBlueShades = (count) => {
                const baseColor = colors.primary; // #064b9e
                const shades = [];

                for (let i = 0; i < count; i++) {
                    // Create variations by adjusting opacity and lightness
                    const opacity = 1 - (i * 0.15); // Decrease opacity gradually
                    const lightness = Math.min(1, 0.7 + (i * 0.1)); // Slight lightness variation

                    if (i === 0) {
                        shades.push(baseColor); // First item uses pure hospital blue
                    } else {
                        // Create rgba variations of hospital blue
                        const rgb = hexToRgb(baseColor);
                        shades.push(`rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${opacity})`);
                    }
                }
                return shades;
            };

            // Helper function to convert hex to rgb
            const hexToRgb = (hex) => {
                const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
                return result ? {
                    r: parseInt(result[1], 16),
                    g: parseInt(result[2], 16),
                    b: parseInt(result[3], 16)
                } : null;
            };

            const chartColors = generateBlueShades(data.labels.length);

            chartsHistorico.servicios = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: data.labels,
                    datasets: [{
                        data: data.data,
                        backgroundColor: chartColors,
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
        })
        .catch(error => console.error('Error al cargar distribución servicios histórico:', error));
}

// Cargar distribución por estados histórico
function cargarDistribucionEstadosHistorico() {
    const params = new URLSearchParams(currentFiltersHistorico);
    const url = '{{ route('api.graficos.historial.distribucion-estados') }}?' + params;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            const ctx = document.getElementById('estadosChartHistorico').getContext('2d');
            if (chartsHistorico.estados) {
                chartsHistorico.estados.destroy();
            }

            // Monochromatic hospital-blue theme with minimal semantic colors
            const estadoColors = {
                'pendiente': colors.primary,   // Hospital blue - pending status
                'llamado': '#4a90e2',          // Light hospital blue - in progress/called
                'atendido': '#2563eb',         // Blue - completed successfully (maintaining monochromatic theme)
                'aplazado': '#7bb3f0',         // Lighter hospital blue - postponed/delayed
                'cancelado': colors.danger     // Red - cancelled/failed (essential semantic)
            };

            const chartColors = data.labels.map(estado => estadoColors[estado] || colors.primary);

            chartsHistorico.estados = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Cantidad',
                        data: data.data,
                        backgroundColor: chartColors,
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
        })
        .catch(error => console.error('Error al cargar distribución estados histórico:', error));
}

// Cargar análisis de horas pico histórico
function cargarHorasPicoHistorico() {
    const params = new URLSearchParams(currentFiltersHistorico);
    const url = '{{ route('api.graficos.historial.horas-pico') }}?' + params;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            const ctx = document.getElementById('horasPicoChartHistorico').getContext('2d');
            if (chartsHistorico.horasPico) {
                chartsHistorico.horasPico.destroy();
            }

            chartsHistorico.horasPico = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Turnos por Hora',
                        data: data.data,
                        backgroundColor: colors.primary,
                        borderColor: colors.primary,
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
        })
        .catch(error => console.error('Error al cargar horas pico histórico:', error));
}

// Cargar tiempo de atención histórico
function cargarTiempoAtencionHistorico() {
    const params = new URLSearchParams(currentFiltersHistorico);
    const url = '{{ route('api.graficos.historial.tiempo-atencion') }}?' + params;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            const ctx = document.getElementById('tiempoAtencionChartHistorico').getContext('2d');
            if (chartsHistorico.tiempoAtencion) {
                chartsHistorico.tiempoAtencion.destroy();
            }

            chartsHistorico.tiempoAtencion = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Tiempo Promedio (min)',
                        data: data.data,
                        backgroundColor: colors.primary,
                        borderColor: colors.primary,
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
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Minutos'
                            }
                        }
                    }
                }
            });
        })
        .catch(error => console.error('Error al cargar tiempo atención histórico:', error));
}

// Cargar rendimiento de asesores histórico
function cargarRendimientoAsesoresHistorico() {
    const params = new URLSearchParams(currentFiltersHistorico);
    const url = '{{ route('api.graficos.historial.rendimiento-asesores') }}?' + params;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            const ctx = document.getElementById('asesoresChartHistorico').getContext('2d');
            if (chartsHistorico.asesores) {
                chartsHistorico.asesores.destroy();
            }

            chartsHistorico.asesores = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Turnos Atendidos',
                        data: data.data,
                        backgroundColor: colors.primary,
                        borderColor: colors.primary,
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
        })
        .catch(error => console.error('Error al cargar rendimiento asesores histórico:', error));
}

// Función para exportar gráfico individual
function exportarGrafico(chartId) {
    const chartKey = chartId.replace('Chart', '').replace('Historico', '');
    const chart = currentTab === 'actual' ? charts[chartKey] : chartsHistorico[chartKey];

    if (chart) {
        const url = chart.toBase64Image();
        const link = document.createElement('a');
        link.download = `grafico_${chartId}_${new Date().toISOString().split('T')[0]}.png`;
        link.href = url;
        link.click();
    }
}

// Función para exportar dashboard histórico
function exportarDashboardHistorico() {
    // Mostrar modal de opciones de exportación
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modal.innerHTML = `
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Exportar Dashboard Histórico</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Formato de exportación:</label>
                    <select id="formatoExport" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-hospital-blue focus:border-transparent">
                        <option value="pdf">PDF</option>
                        <option value="excel">Excel</option>
                    </select>
                </div>
                <div class="flex justify-end space-x-3">
                    <button onclick="cerrarModalExport()" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button onclick="procesarExportDashboard()" class="px-4 py-2 bg-hospital-blue text-white rounded-md hover:bg-hospital-blue-hover transition-colors">
                        Exportar
                    </button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    // Función para cerrar modal
    window.cerrarModalExport = function() {
        document.body.removeChild(modal);
    };

    // Función para procesar exportación
    window.procesarExportDashboard = function() {
        const formato = document.getElementById('formatoExport').value;
        const filtros = obtenerParametrosFiltrosHistorico();

        // Mostrar indicador de carga
        const exportBtn = modal.querySelector('button[onclick="procesarExportDashboard()"]');
        exportBtn.innerHTML = '<div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white inline-block mr-2"></div>Exportando...';
        exportBtn.disabled = true;

        // Crear formulario para envío
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route('admin.reportes.dashboard-historico') }}';
        form.style.display = 'none';

        // Token CSRF
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);

        // Parámetros
        const params = {
            fecha_inicio: filtros.fecha_inicio,
            fecha_fin: filtros.fecha_fin,
            servicio_id: filtros.servicio_id || '',
            formato: formato
        };

        Object.keys(params).forEach(key => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = params[key];
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);

        // Cerrar modal después de un breve delay
        setTimeout(() => {
            cerrarModalExport();
        }, 1000);
    };
}

// Cargar todos los gráficos al inicializar la página
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar con el tab actual activo
    cambiarTab('actual');

    // Configurar event listeners para los selectores de datos actuales
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

    // Actualizar automáticamente cada 5 minutos solo si está en el día actual y en tab actual
    setInterval(function() {
        if (currentTab === 'actual') {
            const fechaSeleccionada = document.getElementById('fechaSelector').value;
            const hoy = new Date().toISOString().split('T')[0];
            if (fechaSeleccionada === hoy) {
                actualizarTodosLosGraficos();
            }
        }
    }, 300000);
});
</script>

@endsection
