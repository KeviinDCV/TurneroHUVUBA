<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccionar Caja - Hospital Universitario del Valle</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <style>
        :root {
            --hospital-blue: #064b9e;
            --hospital-blue-hover: #053d7a;
            --hospital-blue-dark: #042b59;
            --hospital-blue-light: #e6f0ff;
        }

        /* ===================== Fondo ===================== */
        .auth-bg {
            background:
                radial-gradient(900px 520px at 50% -12%, #dfeafc 0%, transparent 60%),
                linear-gradient(160deg, #eef3fb 0%, #e4ecf6 100%);
        }

        /* ===================== Scrollbar de la lista ===================== */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #eef2f8; border-radius: 3px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #c3cee0; border-radius: 3px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #a8b6cc; }

        /* ===================== Lista de cajas ===================== */
        .caja-list {
            background: #fff;
            border: 1.5px solid #d3dcea;
            border-radius: 1rem;
            box-shadow: 0 4px 14px -6px rgba(16, 24, 40, 0.12);
            padding: 0.5rem;
        }

        /* ===================== Botón primario ===================== */
        .auth-btn {
            width: 100%;
            height: 3.25rem;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: 0.01em;
            background: linear-gradient(135deg, var(--hospital-blue) 0%, var(--hospital-blue-hover) 100%);
            box-shadow: 0 14px 26px -10px rgba(6, 75, 158, 0.55);
            transition: transform 0.2s ease, box-shadow 0.25s ease, filter 0.25s ease;
        }
        .auth-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 34px -10px rgba(6, 75, 158, 0.6);
            filter: brightness(1.06);
        }
        .auth-btn:active {
            transform: translateY(0);
            box-shadow: 0 8px 16px -8px rgba(6, 75, 158, 0.5);
        }

        /* ===================== Botón secundario ===================== */
        .auth-btn-ghost {
            width: 100%;
            height: 3.25rem;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #475569;
            font-weight: 600;
            font-size: 0.95rem;
            background: #fff;
            border: 1.5px solid #d3dcea;
            box-shadow: 0 4px 14px -6px rgba(16, 24, 40, 0.12);
            transition: border-color 0.25s ease, background 0.25s ease, transform 0.2s ease, color 0.25s ease;
        }
        .auth-btn-ghost:hover {
            border-color: #b6c2d6;
            background: #f8fafc;
            color: var(--hospital-blue);
            transform: translateY(-1px);
        }
        .auth-btn-ghost:active { transform: translateY(0); }
    </style>
</head>
<body>
    <div class="auth-bg min-h-screen flex items-center justify-center p-4 relative">
        <!-- Elementos decorativos de fondo (estáticos, sin animación) -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-20 -right-20 w-44 h-44 rounded-full" style="background-color: #064b9e; opacity: 0.10;"></div>
            <div class="absolute -bottom-16 -left-16 w-36 h-36 rounded-full" style="background-color: #064b9e; opacity: 0.10;"></div>
        </div>

        <!-- Contenido directamente sobre el fondo (sin tarjeta) -->
        <div class="relative w-full max-w-sm py-8">
            <!-- Logo + identidad del hospital -->
            <div class="text-center mb-5">
                <div class="mb-3">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo Hospital Universitario del Valle" class="mx-auto h-24 w-auto">
                </div>
                <div>
                    <h1 class="text-2xl font-bold leading-tight" style="color: #064b9e;">Hospital Universitario</h1>
                    <h1 class="text-2xl font-bold leading-tight" style="color: #064b9e;">Del Valle</h1>
                    <h2 class="text-base font-semibold text-gray-700 mt-1">"Evaristo García"</h2>
                    <p class="text-sm text-gray-600 mt-1">E.S.E</p>
                </div>
            </div>

            <!-- Asesor conectado -->
            <div class="text-center mb-6">
                <p class="text-xs text-gray-500">Bienvenido/a</p>
                <p class="font-semibold text-sm" style="color: #064b9e;">{{ $user->nombre_completo }}</p>
                <p class="text-xs text-gray-400">{{ $user->rol }}</p>
            </div>

            <!-- Encabezado de la acción -->
            <div class="text-center mb-4">
                <h3 class="text-lg font-bold" style="color: #064b9e;">Seleccionar Caja</h3>
                <p class="text-sm text-gray-600">Elige la caja desde la cual vas a atender</p>
            </div>

            <form method="POST" action="{{ route('asesor.procesar-seleccion-caja') }}" class="space-y-4">
                @csrf

                @if ($errors->has('caja_ocupada'))
                    <div class="border border-orange-300 text-orange-700 px-4 py-3 rounded-2xl text-sm" style="background-color: #fff3e0;">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <div class="font-medium">Caja No Disponible</div>
                                <div class="text-sm">{{ $errors->first('caja_ocupada') }}</div>
                            </div>
                        </div>
                    </div>
                @elseif ($errors->any())
                    <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded-2xl text-sm">
                        {{ $errors->first() }}
                    </div>
                @endif

                <!-- Contenedor con scroll para las cajas -->
                <div class="relative">
                    <div class="caja-list max-h-48 overflow-y-auto custom-scrollbar">
                        <div class="space-y-1">
                        @forelse($cajas as $caja)
                            <label class="flex items-center p-2 border border-gray-200 rounded-xl cursor-pointer transition-all duration-300
                                {{ $caja->disponible ? 'bg-white hover:border-blue-300 hover:bg-blue-50' : 'bg-gray-100 cursor-not-allowed opacity-60' }}
                                {{ $caja->ocupada_por_mi ? 'border-blue-500 bg-blue-50' : '' }}">
                                <input type="radio" name="caja_id" value="{{ $caja->id }}"
                                    class="w-3 h-3 text-blue-600 border-gray-300 focus:ring-0 focus:outline-none"
                                    {{ $caja->disponible ? '' : 'disabled' }}
                                    {{ $caja->ocupada_por_mi ? 'checked' : '' }}
                                    required>
                                <div class="flex items-center justify-between w-full ml-2">
                                    <div class="flex items-center space-x-2">
                                        <div>
                                            <p class="font-medium text-gray-900 text-xs">{{ $caja->nombre }}</p>
                                            @if($caja->ubicacion)
                                                <p class="text-xs text-gray-500">{{ $caja->ubicacion }}</p>
                                            @endif
                                            @if(!$caja->disponible && $caja->nombre_asesor)
                                                <p class="text-xs text-orange-600 font-medium">Ocupada por: {{ $caja->nombre_asesor }}</p>
                                            @elseif($caja->ocupada_por_mi)
                                                <p class="text-xs text-blue-600 font-medium">Tu caja actual</p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        @if($caja->disponible)
                                            @if($caja->ocupada_por_mi)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ $caja->numero_caja }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                    {{ $caja->numero_caja }}
                                                </span>
                                            @endif
                                        @else
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                {{ $caja->numero_caja }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </label>
                        @empty
                            <div class="text-center py-4">
                                <p class="text-gray-500 text-sm">No hay cajas disponibles en este momento.</p>
                            </div>
                        @endforelse
                        </div>
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

                <!-- Acciones -->
                <div class="space-y-3 pt-1">
                    @if($cajas->count() > 0)
                        <button type="submit" class="auth-btn">
                            Continuar
                        </button>
                    @endif
                    <a href="{{ route('logout') }}"
                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                       class="auth-btn-ghost">
                        Cerrar Sesión
                    </a>
                </div>
            </form>

            <!-- Formulario oculto para logout -->
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        </div>

        <!-- Firma en la parte inferior -->
        <div class="absolute bottom-4 left-0 right-0 text-center">
            <p class="text-xs text-gray-400 transition-colors duration-300 hover:text-gray-600">
                Turnero HUV - Innovación y desarrollo
            </p>
        </div>
    </div>
</body>
</html>
