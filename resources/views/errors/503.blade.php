<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Servicio No Disponible - {{ config('app.name', 'Turnero HUV') }}</title>
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
            max-width: 480px;
            margin: 0 auto;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .error-icon {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 20px;
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
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .maintenance-icon {
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 53%, 80%, 100% {
                transform: translate3d(0,0,0);
            }
            40%, 43% {
                transform: translate3d(0, -10px, 0);
            }
            70% {
                transform: translate3d(0, -5px, 0);
            }
            90% {
                transform: translate3d(0, -2px, 0);
            }
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
                <div class="error-icon maintenance-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                        </path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>

                <h2 class="text-lg font-bold text-hospital-blue mb-3">
                    Servicio Temporalmente No Disponible
                </h2>

                <p class="text-sm text-gray-700 mb-4">
                    El sistema está experimentando problemas técnicos. Estamos trabajando para solucionarlo.
                </p>
                
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
                    <div class="flex items-center justify-center mb-1">
                        <svg class="w-4 h-4 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z">
                            </path>
                        </svg>
                        <span class="font-semibold text-yellow-800 text-sm">Mantenimiento en Progreso</span>
                    </div>
                    <p class="text-xs text-yellow-700">
                        Verifica que todos los servicios necesarios estén funcionando correctamente.
                    </p>
                </div>

                <!-- Lista de verificación -->
                <div class="text-left mb-4 bg-gray-50 rounded-lg p-3 border border-gray-200">
                    <h3 class="font-semibold text-hospital-blue mb-2 text-center text-sm">🔍 Verificar:</h3>
                    <div class="space-y-1 text-xs">
                        <div class="flex items-center">
                            <span class="w-2 h-2 bg-red-400 rounded-full mr-2"></span>
                            <span>Servidor de base de datos (MySQL/XAMPP)</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-2 h-2 bg-red-400 rounded-full mr-2"></span>
                            <span>Servicios web del servidor</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-2 h-2 bg-red-400 rounded-full mr-2"></span>
                            <span>Configuración de red</span>
                        </div>
                    </div>
                </div>
                
                <!-- Botones de acción -->
                <div class="flex flex-col sm:flex-row gap-2 justify-center mb-4">
                    <button onclick="checkAndReload()" class="retry-btn" id="retryBtn">
                        <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                            </path>
                        </svg>
                        Verificar Estado
                    </button>

                    <a href="{{ url('/') }}" class="retry-btn" style="background: #6b7280;">
                        <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                            </path>
                        </svg>
                        Página Principal
                    </a>
                </div>

                <!-- Información adicional -->
                <div class="p-3 bg-blue-50 rounded-lg">
                    <p class="text-xs text-blue-700">
                        💡 <strong>Sugerencia:</strong> Si eres administrador, verifica que XAMPP esté ejecutándose y que el servicio MySQL esté activo.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function checkAndReload() {
            const btn = document.getElementById('retryBtn');
            btn.disabled = true;
            btn.innerHTML = `
                <svg class="inline w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                    </path>
                </svg>
                Verificando...
            `;
            
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        }
        
        // Auto-verificar cada 30 segundos
        setInterval(() => {
            fetch(window.location.href, { method: 'HEAD' })
                .then(response => {
                    if (response.ok) {
                        window.location.reload();
                    }
                })
                .catch(() => {
                    // Servicio aún no disponible
                });
        }, 30000);
    </script>
</body>
</html>
