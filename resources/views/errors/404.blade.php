<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Página No Encontrada - {{ config('app.name', 'Turnero HUV') }}</title>
    @include('components.favicon')
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    @vite(['resources/css/app.css'])
    
    <style>
        :root {
            --hospital-blue: #064b9e;
            --hospital-blue-hover: #053d7a;
            --hospital-blue-light: #e6f0ff;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        
        .error-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }
        
        .error-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            max-width: 450px;
            margin: 0 auto;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .error-number {
            font-size: 4rem;
            font-weight: 900;
            background: linear-gradient(135deg, var(--hospital-blue), var(--hospital-blue-hover));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .retry-btn {
            background: var(--hospital-blue);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        
        .retry-btn:hover {
            background: var(--hospital-blue-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(6, 75, 158, 0.3);
        }
        
        .floating {
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .hospital-header {
            background: var(--hospital-blue);
            color: white;
        }

        .text-hospital-blue {
            color: var(--hospital-blue);
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="error-container flex items-center justify-center p-4">
        <div class="error-card">
            <!-- Header con logo -->
            <div class="hospital-header p-4 text-center">
                <div class="flex items-center justify-center mb-2">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo Hospital Universitario del Valle" class="h-10 w-auto mr-3">
                    <div class="text-left">
                        <h1 class="text-sm font-bold text-white leading-tight">Hospital Universitario Del Valle</h1>
                        <h2 class="text-xs font-medium text-blue-100">"Evaristo García" E.S.E</h2>
                    </div>
                </div>
                <p class="text-blue-100 text-xs">Sistema de Gestión de Turnos</p>
            </div>

            <!-- Contenido del error -->
            <div class="p-5 text-center">
                <div class="floating mb-4">
                    <div class="error-number">404</div>
                </div>

                <h2 class="text-lg font-bold text-hospital-blue mb-3">
                    Página No Encontrada
                </h2>

                <p class="text-sm text-gray-700 mb-4">
                    Lo sentimos, la página que buscas no existe o ha sido movida.
                </p>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                    <div class="flex items-center justify-center mb-1">
                        <svg class="w-4 h-4 text-hospital-blue mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                            </path>
                        </svg>
                        <span class="font-semibold text-hospital-blue text-sm">¿Qué puedes hacer?</span>
                    </div>
                    <p class="text-xs text-gray-700">
                        Verifica la URL o navega a una de las secciones principales del sistema.
                    </p>
                </div>

                <!-- Enlaces útiles -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-4">
                    <a href="{{ url('/') }}" class="flex items-center justify-center p-2 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors border border-gray-200">
                        <svg class="w-4 h-4 text-hospital-blue mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                            </path>
                        </svg>
                        <span class="text-xs font-medium text-gray-700">Página Principal</span>
                    </a>

                    <a href="{{ route('admin.login') }}" class="flex items-center justify-center p-2 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors border border-gray-200">
                        <svg class="w-4 h-4 text-hospital-blue mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                            </path>
                        </svg>
                        <span class="text-xs font-medium text-gray-700">Administración</span>
                    </a>
                </div>
                
                <!-- Botones de acción -->
                <div class="flex flex-col sm:flex-row gap-2 justify-center mb-4">
                    <button onclick="window.history.back()" class="retry-btn" style="background: #6b7280;">
                        <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M10 19l-7-7m0 0l7-7m-7 7h18">
                            </path>
                        </svg>
                        Volver Atrás
                    </button>

                    <a href="{{ url('/') }}" class="retry-btn">
                        <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                            </path>
                        </svg>
                        Ir al Inicio
                    </a>
                </div>

                <!-- Información adicional -->
                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <p class="text-xs text-gray-600">
                        Si crees que esto es un error, contacta al administrador del sistema.
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        URL: <code class="bg-gray-200 px-1 rounded text-xs">{{ Str::limit(request()->fullUrl(), 40) }}</code>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
