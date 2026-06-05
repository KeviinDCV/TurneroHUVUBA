<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Turnero HUV') }} - Panel Administrativo</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

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

        /* ===================== Inputs ===================== */
        .auth-field { position: relative; }

        .auth-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9aa6b8;
            pointer-events: none;
            transition: color 0.25s ease;
        }

        .auth-input {
            width: 100%;
            height: 3.25rem;
            border-radius: 9999px;
            border: 1.5px solid #d3dcea;
            background: #fff;
            padding: 0 1.25rem 0 3rem;
            font-size: 0.95rem;
            color: #1f2937;
            box-shadow: 0 4px 14px -6px rgba(16, 24, 40, 0.12);
            transition: border-color 0.25s ease, box-shadow 0.25s ease;
        }
        .auth-input::placeholder { color: #9ca3af; }
        .auth-input:hover { border-color: #b6c2d6; }
        .auth-input:focus {
            outline: none;
            border-color: var(--hospital-blue);
            box-shadow: 0 0 0 4px rgba(6, 75, 158, 0.12), 0 4px 14px -6px rgba(16, 24, 40, 0.12);
        }
        .auth-field:focus-within .auth-icon { color: var(--hospital-blue); }

        .auth-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9aa6b8;
            transition: color 0.2s ease;
        }
        .auth-toggle:hover { color: var(--hospital-blue); }

        /* ===================== Botón ===================== */
        .auth-btn {
            width: 100%;
            height: 3.25rem;
            border-radius: 9999px;
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
        <div class="relative w-full max-w-sm">
            <!-- Logo + identidad del hospital -->
            <div class="text-center mb-8">
                <div class="mb-4">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo Hospital Universitario del Valle" class="mx-auto h-28 w-auto">
                </div>
                <div>
                    <h1 class="text-2xl font-bold leading-tight" style="color: #064b9e;">Hospital Universitario</h1>
                    <h1 class="text-2xl font-bold leading-tight" style="color: #064b9e;">Del Valle</h1>
                    <h2 class="text-lg font-semibold text-gray-700 mt-1">"Evaristo García"</h2>
                    <p class="text-sm text-gray-600 mt-1">E.S.E</p>
                </div>
            </div>

            <!-- Formulario -->
            <form method="POST" action="{{ route('admin.login.post') }}" class="space-y-5" id="loginForm">
                @csrf

                @if ($errors->has('session_active'))
                    <div class="border border-orange-300 text-orange-700 px-4 py-3 rounded-2xl text-sm" style="background-color: #fff3e0;">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <div class="font-medium">Sesión Activa Detectada</div>
                                <div class="text-sm">{{ $errors->first('session_active') }}</div>
                            </div>
                        </div>
                    </div>
                @elseif ($errors->any())
                    <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded-2xl text-sm">
                        {{ $errors->first() }}
                    </div>
                @endif

                @if (session('info'))
                    <div class="border border-blue-300 text-blue-700 px-4 py-3 rounded-2xl text-sm" style="background-color: #e3f2fd;">
                        {{ session('info') }}
                    </div>
                @endif

                <!-- Usuario -->
                <div class="auth-field">
                    <span class="auth-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </span>
                    <input
                        type="text"
                        name="usuario"
                        placeholder="Usuario"
                        value="{{ old('usuario') }}"
                        class="auth-input"
                        autocomplete="username"
                        autofocus
                        required
                    />
                </div>

                <!-- Contraseña -->
                <div class="auth-field">
                    <span class="auth-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </span>
                    <input
                        type="password"
                        name="password"
                        id="password"
                        placeholder="Contraseña"
                        class="auth-input"
                        style="padding-right: 3rem;"
                        autocomplete="current-password"
                        required
                    />
                    <button
                        type="button"
                        id="togglePassword"
                        class="auth-toggle focus:outline-none"
                        aria-label="Mostrar u ocultar contraseña"
                        onclick="togglePasswordVisibility()"
                    >
                        <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </button>
                </div>

                <!-- Botón -->
                <div class="pt-1">
                    <button type="submit" class="auth-btn">
                        Entrar
                    </button>
                </div>
            </form>
        </div>

        <!-- Firma en la parte inferior -->
        <div class="absolute bottom-4 left-0 right-0 text-center">
            <p class="text-xs text-gray-400 transition-colors duration-300 hover:text-gray-600">
                Turnero HUV - Innovación y desarrollo
            </p>
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
