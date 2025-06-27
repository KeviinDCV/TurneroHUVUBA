<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Turnero HUV') }} - Sistema de Turnos</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @keyframes pulse-gentle {
            0%, 100% { transform: scale(1); opacity: 0.8; }
            50% { transform: scale(1.05); opacity: 1; }
        }

        @keyframes fade-in {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .animate-pulse-gentle {
            animation: pulse-gentle 2s ease-in-out infinite;
        }

        .animate-fade-in {
            animation: fade-in 1s ease-out;
        }

        .animate-float {
            animation: float 3s ease-in-out infinite;
        }

        .cursor-touch {
            cursor: pointer;
        }

        /* Efecto de ondas al hacer clic */
        .ripple {
            position: relative;
            overflow: hidden;
        }

        .ripple::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .ripple:active::before {
            width: 300px;
            height: 300px;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-100 overflow-hidden">
    <!-- Elementos decorativos de fondo -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <!-- Círculos decorativos -->
        <div class="absolute -top-20 -right-20 w-40 h-40 rounded-full opacity-5 animate-float" style="background-color: #064b9e;"></div>
        <div class="absolute -bottom-16 -left-16 w-32 h-32 rounded-full opacity-5 animate-float" style="background-color: #064b9e; animation-delay: 1s;"></div>
        <div class="absolute top-1/4 left-1/4 w-3 h-3 rounded-full opacity-10 animate-pulse" style="background-color: #064b9e; animation-delay: 2s;"></div>
        <div class="absolute top-3/4 right-1/4 w-2 h-2 rounded-full opacity-10 animate-pulse" style="background-color: #064b9e; animation-delay: 3s;"></div>
    </div>

    <!-- Contenido principal -->
    <div class="min-h-screen flex flex-col items-center justify-center p-8 cursor-touch ripple" onclick="window.location.href='{{ route('turnos.menu') }}'">
        <!-- Logo del Hospital -->
        <div class="mb-12 animate-fade-in">
            <img src="{{ asset('images/logo.png') }}" alt="Logo Hospital Universitario del Valle" class="mx-auto h-32 w-auto animate-float">
        </div>

        <!-- Texto del Hospital -->
        <div class="text-center mb-16 animate-fade-in" style="animation-delay: 0.3s; animation-fill-mode: both;">
            <h1 class="text-3xl font-bold leading-tight mb-2" style="color: #064b9e;">Hospital Universitario Del Valle</h1>
            <h2 class="text-xl font-semibold text-gray-700">"Evaristo García" E.S.E</h2>
            <div class="mt-4 h-1 w-24 mx-auto rounded-full" style="background-color: #064b9e;"></div>
        </div>

        <!-- Mensaje principal -->
        <div class="text-center animate-fade-in" style="animation-delay: 0.6s; animation-fill-mode: both;">
            <h2 class="text-5xl md:text-6xl font-bold text-gray-800 mb-6 animate-pulse-gentle">
                Toque la pantalla
            </h2>
            <h3 class="text-3xl md:text-4xl font-medium text-gray-600 mb-8">
                para continuar
            </h3>

            <!-- Indicador visual -->
            <div class="flex justify-center items-center space-x-2 animate-pulse-gentle" style="animation-delay: 1s;">
                <div class="w-3 h-3 rounded-full" style="background-color: #064b9e;"></div>
                <div class="w-3 h-3 rounded-full" style="background-color: #064b9e; animation-delay: 0.2s;"></div>
                <div class="w-3 h-3 rounded-full" style="background-color: #064b9e; animation-delay: 0.4s;"></div>
            </div>
        </div>

        <!-- Instrucción adicional -->
        <div class="absolute bottom-8 left-0 right-0 text-center animate-fade-in" style="animation-delay: 1s; animation-fill-mode: both;">
            <p class="text-lg text-gray-500">
                👆 Toque en cualquier parte de la pantalla
            </p>
        </div>
    </div>

    <!-- Firma -->
    <div class="absolute bottom-4 right-4">
        <p class="text-xs text-gray-400">
            Turnero HUV - Innovación y desarrollo
        </p>
    </div>

    <script>
        // Agregar efecto de vibración en dispositivos móviles al tocar
        document.addEventListener('click', function() {
            if (navigator.vibrate) {
                navigator.vibrate(50);
            }
        });

        // Prevenir zoom en dispositivos táctiles
        document.addEventListener('touchstart', function(event) {
            if (event.touches.length > 1) {
                event.preventDefault();
            }
        });

        let lastTouchEnd = 0;
        document.addEventListener('touchend', function(event) {
            const now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
    </script>
</body>
</html>
