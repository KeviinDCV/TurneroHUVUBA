<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccionar Caja - Hospital Universitario del Valle</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <style>
        .animate-fade-in {
            animation: fadeIn 0.8s ease-in-out;
        }
        .animate-slide-down {
            animation: slideDown 0.6s ease-out;
        }
        .animate-slide-up {
            animation: slideUp 0.6s ease-out;
        }
        .animate-slide-left {
            animation: slideLeft 0.8s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideDown {
            from { transform: translateY(-30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @keyframes slideLeft {
            from { transform: translateX(30px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Estilos personalizados para el scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Efecto de respiración para elementos decorativos */
        @keyframes breathe {
            0%, 100% { transform: scale(1); opacity: 0.1; }
            50% { transform: scale(1.05); opacity: 0.15; }
        }

        .animate-breathe {
            animation: breathe 4s ease-in-out infinite;
        }
    </style>
</head>
<body>
    <div class="min-h-screen bg-gray-100 flex items-center justify-center p-4 relative">
        <!-- Elementos decorativos de fondo -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <!-- Círculo decorativo superior derecho -->
            <div class="absolute -top-20 -right-20 w-40 h-40 rounded-full animate-breathe pointer-events-none" style="background-color: #064b9e; opacity: 0.1;"></div>
            <!-- Círculo decorativo inferior izquierdo -->
            <div class="absolute -bottom-16 -left-16 w-32 h-32 rounded-full animate-breathe pointer-events-none" style="background-color: #064b9e; animation-delay: 2s; opacity: 0.1;"></div>
            <!-- Elemento decorativo central -->
            <div class="absolute top-1/2 left-1/4 w-2 h-2 rounded-full opacity-20 animate-ping" style="background-color: #064b9e; animation-delay: 2s;"></div>
            <div class="absolute top-1/3 right-1/4 w-1 h-1 rounded-full opacity-20 animate-ping" style="background-color: #064b9e; animation-delay: 3s;"></div>
        </div>
        <div class="relative bg-white rounded-3xl shadow-lg max-w-4xl w-full overflow-hidden transform transition-all duration-700 hover:shadow-xl hover:scale-[1.02] animate-fade-in" style="border: 2px solid rgba(6, 75, 158, 0.1);">
            <!-- Borde decorativo superior -->
            <div class="absolute top-0 left-1/4 right-1/4 h-1 rounded-b-full opacity-60" style="background: linear-gradient(90deg, transparent, #064b9e, transparent);"></div>
            <!-- Borde decorativo inferior -->
            <div class="absolute bottom-0 left-1/3 right-1/3 h-1 rounded-t-full opacity-60" style="background: linear-gradient(90deg, transparent, #064b9e, transparent);"></div>
            <div class="grid md:grid-cols-2 min-h-[500px]">
                <!-- Left side - Logo y bienvenida -->
                <div class="flex items-center justify-center p-6">
                    <div class="text-center">
                        <!-- Logo del Hospital -->
                        <div class="mb-4 transform transition-all duration-1000 animate-slide-down">
                            <img src="{{ asset('images/logo.png') }}" alt="Logo Hospital Universitario del Valle" class="mx-auto h-24 w-auto hover:scale-105 transition-transform duration-300">
                        </div>
                        <!-- Texto del Hospital -->
                        <div class="mb-3 transform transition-all duration-1000 animate-slide-up">
                            <h1 class="text-xl font-bold leading-tight transition-all duration-300 hover:scale-105" style="color: #064b9e;">Hospital Universitario</h1>
                            <h1 class="text-xl font-bold leading-tight transition-all duration-300 hover:scale-105" style="color: #064b9e;">Del Valle</h1>
                            <h2 class="text-base font-semibold text-gray-700 mt-1 transition-colors duration-300 hover:text-gray-900">"Evaristo García"</h2>
                            <p class="text-sm text-gray-600 mt-1 transition-colors duration-300 hover:text-gray-800">E.S.E</p>
                        </div>
                        <!-- Información del usuario -->
                        <div class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
                            <p class="text-xs text-gray-600">Bienvenido/a</p>
                            <p class="font-semibold text-sm" style="color: #064b9e;">{{ $user->nombre_completo }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ $user->rol }}</p>
                        </div>
                    </div>
                </div>

                <!-- Right side - Selección de Caja -->
                <div class="flex items-center justify-center p-6">
                    <div class="w-full max-w-sm space-y-3 transform transition-all duration-1000 animate-slide-left">
                        <div class="text-center mb-3">
                            <h3 class="text-lg font-bold mb-1" style="color: #064b9e;">Seleccionar Caja</h3>
                            <p class="text-sm text-gray-600">Elige la caja desde la cual vas a atender</p>
                        </div>

                        <form method="POST" action="{{ route('asesor.procesar-seleccion-caja') }}" class="space-y-4">
                            @csrf

                            @if ($errors->any())
                                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-full text-sm">
                                    {{ $errors->first() }}
                                </div>
                            @endif

                            <!-- Contenedor con scroll para las cajas -->
                            <div class="relative">
                                <div class="max-h-48 overflow-y-auto border border-gray-200 rounded-lg bg-gray-50 p-2 custom-scrollbar">
                                    <div class="space-y-1">
                                    @forelse($cajas as $caja)
                                        <label class="flex items-center p-2 border border-gray-200 bg-white rounded-md cursor-pointer transition-all duration-300 hover:border-blue-300 hover:bg-blue-50">
                                            <input type="radio" name="caja_id" value="{{ $caja->id }}" class="sr-only peer" required>
                                            <div class="flex items-center justify-between w-full">
                                                <div class="flex items-center space-x-2">
                                                    <div class="w-3 h-3 border-2 border-gray-300 rounded-full peer-checked:border-blue-600 peer-checked:bg-blue-600 transition-all duration-200 relative">
                                                        <div class="absolute inset-0 rounded-full bg-white scale-0 peer-checked:scale-50 transition-transform duration-200"></div>
                                                    </div>
                                                    <div>
                                                        <p class="font-medium text-gray-900 text-xs">{{ $caja->nombre }}</p>
                                                        @if($caja->ubicacion)
                                                            <p class="text-xs text-gray-500">{{ $caja->ubicacion }}</p>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                        {{ $caja->numero_caja }}
                                                    </span>
                                                </div>
                                            </div>
                                        </label>
                                    @empty
                                        <div class="text-center py-4">
                                            <p class="text-gray-500 text-sm">No hay cajas disponibles en este momento.</p>
                                        </div>
                                    @endforelse
                                </div>
                                <!-- Indicador de scroll si hay muchas cajas -->
                                @if($cajas->count() > 6)
                                    <div class="absolute bottom-1 right-1 text-xs text-gray-400 bg-white px-1.5 py-0.5 rounded shadow-sm">
                                        <svg class="w-2 h-2 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                        </svg>
                                        Scroll
                                    </div>
                                @endif
                            </div>

                            @if($cajas->count() > 0)
                                <div class="flex justify-center space-x-3 pt-3">
                                    <button
                                        type="submit"
                                        class="px-4 py-2 text-white rounded-full shadow-md transition-all duration-300 transform hover:scale-105 hover:shadow-lg active:scale-95 text-sm"
                                        style="background-color: #064b9e;"
                                        onmouseover="this.style.backgroundColor='#053a7a'; this.style.boxShadow='0 10px 25px rgba(6, 75, 158, 0.3)'"
                                        onmouseout="this.style.backgroundColor='#064b9e'; this.style.boxShadow='0 4px 6px -1px rgba(0, 0, 0, 0.1)'"
                                    >
                                        Continuar
                                    </button>
                                    <a href="{{ route('logout') }}"
                                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                                       class="px-4 py-2 text-gray-600 border border-gray-300 rounded-full shadow-md transition-all duration-300 transform hover:scale-105 hover:shadow-lg active:scale-95 hover:bg-gray-50 text-sm">
                                        Cerrar Sesión
                                    </a>
                                </div>
                            @endif
                        </form>

                        <!-- Formulario oculto para logout -->
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </div>
                </div>
            </div>

            <!-- Firma en la parte inferior -->
            <div class="absolute bottom-4 left-0 right-0 text-center">
                <p class="text-xs text-gray-400 transition-colors duration-300 hover:text-gray-600">
                    Turnero HUV - Innovación y desarrollo
                </p>
            </div>
        </div>
    </div>
</body>
</html>
