<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Turnero HUV') }} - Panel Administrativo</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slide-down {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slide-up {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slide-left {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .animate-fade-in {
            animation: fade-in 0.8s ease-out;
        }

        .animate-slide-down {
            animation: slide-down 1s ease-out;
        }

        .animate-slide-up {
            animation: slide-up 1s ease-out 0.2s both;
        }

        .animate-slide-left {
            animation: slide-left 1s ease-out 0.4s both;
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
                <!-- Left side - Logo -->
                <div class="flex items-center justify-center p-8">
                    <div class="text-center">
                        <!-- Logo del Hospital -->
                        <div class="mb-6 transform transition-all duration-1000 animate-slide-down">
                            <img src="{{ asset('images/logo.png') }}" alt="Logo Hospital Universitario del Valle" class="mx-auto h-32 w-auto hover:scale-105 transition-transform duration-300">
                        </div>
                        <!-- Texto del Hospital -->
                        <div class="mb-4 transform transition-all duration-1000 animate-slide-up">
                            <h1 class="text-2xl font-bold leading-tight transition-all duration-300 hover:scale-105" style="color: #064b9e;">Hospital Universitario</h1>
                            <h1 class="text-2xl font-bold leading-tight transition-all duration-300 hover:scale-105" style="color: #064b9e;">Del Valle</h1>
                            <h2 class="text-lg font-semibold text-gray-700 mt-1 transition-colors duration-300 hover:text-gray-900">"Evaristo García"</h2>
                            <p class="text-sm text-gray-600 mt-1 transition-colors duration-300 hover:text-gray-800">E.S.E</p>
                        </div>
                    </div>
                </div>

                <!-- Right side - Login Form -->
                <div class="flex items-center justify-center p-8">
                    <div class="w-full max-w-sm space-y-4 transform transition-all duration-1000 animate-slide-left">
                        <form method="POST" action="{{ route('admin.login.post') }}" class="space-y-4" id="loginForm">
                            @csrf

                            @if ($errors->has('session_active'))
                                <div class="border border-orange-400 text-orange-700 px-4 py-3 rounded-full text-sm mb-4" style="background-color: #fff3e0;">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        <div>
                                            <div class="font-medium">Sesión Activa Detectada</div>
                                            <div class="text-sm">{{ $errors->first('session_active') }}</div>
                                        </div>
                                    </div>
                                </div>
                            @elseif ($errors->any())
                                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-full text-sm">
                                    {{ $errors->first() }}
                                </div>
                            @endif

                            @if (session('info'))
                                <div class="border border-blue-400 text-blue-700 px-4 py-3 rounded-full text-sm" style="background-color: #e3f2fd;">
                                    {{ session('info') }}
                                </div>
                            @endif

                            <div class="space-y-4">
                                <input
                                    type="text"
                                    name="usuario"
                                    placeholder="Usuario"
                                    value="{{ old('usuario') }}"
                                    class="w-full h-12 rounded-full border-gray-300 px-4 shadow-sm focus:outline-none focus:ring-2 transition-all duration-300 hover:shadow-md transform hover:scale-[1.02]"
                                    style="--tw-ring-color: #064b9e; border-color: #d1d5db;"
                                    onfocus="this.style.borderColor='#064b9e'; this.style.transform='scale(1.02)'"
                                    onblur="this.style.borderColor='#d1d5db'; this.style.transform='scale(1)'"
                                    required
                                />

                                <div class="relative">
                                    <input
                                        type="password"
                                        name="password"
                                        id="password"
                                        placeholder="Contraseña"
                                        class="w-full h-12 rounded-full border-gray-300 px-4 pr-12 shadow-sm focus:outline-none focus:ring-2 transition-all duration-300 hover:shadow-md transform hover:scale-[1.02]"
                                        style="--tw-ring-color: #064b9e; border-color: #d1d5db;"
                                        onfocus="this.style.borderColor='#064b9e'; this.style.transform='scale(1.02)'"
                                        onblur="this.style.borderColor='#d1d5db'; this.style.transform='scale(1)'"
                                        required
                                    />
                                    <button
                                        type="button"
                                        id="togglePassword"
                                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors duration-200 focus:outline-none"
                                        onclick="togglePasswordVisibility()"
                                    >
                                        <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="flex justify-center">
                                <button
                                    type="submit"
                                    class="w-24 h-10 text-white rounded-full shadow-md transition-all duration-300 transform hover:scale-105 hover:shadow-lg active:scale-95"
                                    style="background-color: #064b9e;"
                                    onmouseover="this.style.backgroundColor='#053a7a'; this.style.boxShadow='0 10px 25px rgba(6, 75, 158, 0.3)'"
                                    onmouseout="this.style.backgroundColor='#064b9e'; this.style.boxShadow='0 4px 6px -1px rgba(0, 0, 0, 0.1)'"
                                >
                                    Entrar
                                </button>
                            </div>
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

    <script>
        // Simplificar el manejo del formulario de login
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');

            // Remover el event listener de AJAX y permitir envío normal del formulario
            // El formulario se enviará de manera tradicional sin interceptar con JavaScript
        });

        // Función para mostrar/ocultar contraseña
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                // Cambiar icono a ojo tachado
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18"></path>
                `;
            } else {
                passwordInput.type = 'password';
                // Cambiar icono a ojo normal
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                `;
            }
        }

        // La verificación de autenticación se maneja en el servidor (AuthController::showLogin)

        // Prevenir cache del navegador
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                // La página fue cargada desde cache, recargar
                window.location.reload();
            }
        });

        // Verificar autenticación cuando la página se vuelve visible
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                // Verificar autenticación cuando la página se vuelve visible
                fetch('/api/auth-check', {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.authenticated) {
                        window.location.href = data.redirect_url;
                    }
                })
                .catch(error => {
                    // Ignorar errores
                });
            }
        });
    </script>
</body>
</html>
