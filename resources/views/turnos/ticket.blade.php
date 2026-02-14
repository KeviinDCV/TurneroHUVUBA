@php
use SimpleSoftwareIO\QrCode\Facades\QrCode;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket de Turno - Hospital Universitario del Valle</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('components.favicon')
    <style>
        @media print {
            body {
                margin: 0;
                padding: 0;
                background: white;
            }
            .no-print {
                display: none !important;
            }
            .ticket {
                width: 80mm;
                margin: 0;
                padding: 5mm;
                box-shadow: none;
                border: none;
                font-size: 12px;
                position: relative;
            }
            .logo-print {
                height: 40px !important;
            }
            .turno-number {
                font-size: 28px !important;
            }
            .qr-small {
                position: absolute !important;
                top: 5mm !important;
                right: 5mm !important;
            }
        }

        .ticket {
            width: 80mm;
            max-width: 300px;
            background: white;
            font-family: 'Courier New', monospace;
            line-height: 1.3;
            border: 2px dashed #ccc;
        }

        .turno-number {
            font-size: 48px;
            font-weight: bold;
            letter-spacing: 2px;
        }

        .fade-in {
            animation: fadeIn 0.8s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <!-- Ticket Container -->
    <div class="ticket bg-white shadow-2xl p-6 text-center fade-in mx-auto relative">
        <!-- Logo -->
        <div class="mb-4">
            <img src="{{ asset('images/logo.png') }}" alt="Logo Hospital" class="mx-auto h-12 w-auto logo-print">
        </div>

        <!-- Turno Number -->
        <div class="mb-4">
            <div class="text-xs text-gray-600 mb-1 font-bold">TURNO</div>
            <div class="turno-number" style="color: #064b9e;">{{ $turno->codigo_completo }}</div>
        </div>

        <!-- Service -->
        <div class="mb-3">
            <div class="text-xs text-gray-600 mb-1 font-bold">SERVICIO</div>
            <div class="font-bold text-gray-900 text-sm leading-tight px-2">
                {{ strtoupper($servicio->nombre_completo ?? $servicio->nombre) }}
            </div>
        </div>


        <!-- Separator -->
        <div class="border-t border-dashed border-gray-500 my-3"></div>

        <!-- Date and Time -->
        <div class="mb-3 text-xs text-gray-700 font-medium">
            <div class="mb-1">FECHA: {{ $turno->fecha_creacion->format('d/m/Y') }}</div>
            <div>HORA: {{ $turno->fecha_creacion->format('H:i:s') }}</div>
        </div>



        <!-- QR Code - Small and Simple -->
        <div class="absolute top-2 right-2 qr-small">
            {!! QrCode::size(40)->generate(route('mobile.display', ['turno' => $turno->id])) !!}
        </div>


    </div>

    <!-- Action Buttons -->
    <div class="no-print fixed bottom-6 left-0 right-0 flex justify-center space-x-4">
        <button
            onclick="window.print()"
            class="px-6 py-3 text-white rounded-lg shadow-lg transition-all duration-200 hover:shadow-xl pulse"
            style="background-color: #064b9e;"
        >
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
            </svg>
            Imprimir Ticket
        </button>

        <button
            onclick="window.location.href='{{ route('turnos.menu') }}'"
            class="px-6 py-3 bg-gray-600 text-white rounded-lg shadow-lg transition-all duration-200 hover:bg-gray-700 hover:shadow-xl"
        >
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Volver al Menú
        </button>
    </div>

    <!-- Auto-print script -->
    <script>
        // Auto-print después de 2 segundos
        setTimeout(function() {
            window.print();
        }, 2000);

        // Vibración si está disponible
        if (navigator.vibrate) {
            navigator.vibrate([200, 100, 200]);
        }

        // Reproducir sonido de éxito
        try {
            const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIG2m98OScTgwOUarm7blmGgU7k9n1unEiBC13yO/eizEIHWq+8+OWT');
            audio.play().catch(() => {});
        } catch (e) {}
    </script>
</body>
</html>
