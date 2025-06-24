<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Asesor - Hospital Universitario del Valle</title>
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
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <header class="shadow-sm" style="background-color: #064b9e;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <!-- Logo y título -->
                <div class="flex items-center space-x-4">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo HUV" class="h-10 w-auto">
                    <div>
                        <h1 class="text-xl font-bold text-white">Turnero HUV</h1>
                        <p class="text-blue-200 text-sm">Panel de Asesor</p>
                    </div>
                </div>

                <!-- Información del usuario y caja -->
                <div class="flex items-center space-x-6">
                    <!-- Información de la caja -->
                    <div class="text-right">
                        <p class="text-white font-medium">{{ $caja->nombre }}</p>
                        <p class="text-blue-200 text-sm">Caja {{ $caja->numero_caja }}</p>
                    </div>

                    <!-- Información del usuario -->
                    <div class="flex items-center space-x-3">
                        <div class="text-right">
                            <p class="text-white font-medium">{{ $user->nombre_completo }}</p>
                            <p class="text-blue-200 text-sm">{{ $user->rol }}</p>
                        </div>
                        <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center border border-white/30">
                            <span class="text-white font-medium">{{ substr($user->nombre_completo, 0, 1) }}</span>
                        </div>
                    </div>

                    <!-- Menú de opciones -->
                    <div class="flex items-center space-x-2">
                        <a href="{{ route('asesor.cambiar-caja') }}" 
                           class="px-4 py-2 bg-white/20 text-white rounded-lg hover:bg-white/30 transition-colors duration-200 text-sm">
                            Cambiar Caja
                        </a>
                        <form action="{{ route('logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" 
                                    class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors duration-200 text-sm">
                                Cerrar Sesión
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="animate-fade-in">
            <!-- Estado de la caja -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Estado de la Caja</h2>
                        <p class="text-gray-600 mt-1">{{ $caja->nombre }} - Caja {{ $caja->numero_caja }}</p>
                        @if($caja->ubicacion)
                            <p class="text-sm text-gray-500">Ubicación: {{ $caja->ubicacion }}</p>
                        @endif
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            <span class="w-2 h-2 bg-green-400 rounded-full mr-2"></span>
                            Activa
                        </span>
                    </div>
                </div>
            </div>

            <!-- Sección de llamada de turnos -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h3 class="text-xl font-bold text-gray-900 mb-6">Gestión de Turnos</h3>
                
                <div class="text-center py-12">
                    <div class="max-w-md mx-auto">
                        <div class="mb-6">
                            <div class="w-24 h-24 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2h4a1 1 0 011 1v1a1 1 0 01-1 1H3a1 1 0 01-1-1V5a1 1 0 011-1h4zM3 8v11a2 2 0 002 2h14a2 2 0 002-2V8H3z"></path>
                                </svg>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900 mb-2">Sistema de Llamada de Turnos</h4>
                            <p class="text-gray-600 mb-6">
                                Desde aquí podrás gestionar y llamar a los pacientes según los servicios asignados a tu caja.
                            </p>
                        </div>

                        <div class="space-y-4">
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <h5 class="font-medium text-blue-900 mb-2">Próximamente disponible</h5>
                                <p class="text-sm text-blue-700">
                                    La funcionalidad de llamada de turnos estará disponible en la siguiente actualización.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                                <div class="bg-gray-50 rounded-lg p-4 text-center">
                                    <div class="text-2xl font-bold text-gray-900">0</div>
                                    <div class="text-sm text-gray-600">Turnos en Cola</div>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-4 text-center">
                                    <div class="text-2xl font-bold text-gray-900">0</div>
                                    <div class="text-sm text-gray-600">Turnos Atendidos</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información adicional -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Servicios asignados -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Servicios Asignados</h3>
                    <div class="text-center py-8">
                        <p class="text-gray-500">No hay servicios asignados actualmente.</p>
                        <p class="text-sm text-gray-400 mt-2">Contacta al administrador para asignar servicios.</p>
                    </div>
                </div>

                <!-- Estadísticas del día -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Estadísticas del Día</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Turnos llamados:</span>
                            <span class="font-semibold">0</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Turnos atendidos:</span>
                            <span class="font-semibold">0</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Tiempo promedio:</span>
                            <span class="font-semibold">--</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Estado:</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Disponible
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
