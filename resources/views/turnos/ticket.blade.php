@php
use SimpleSoftwareIO\QrCode\Facades\QrCode;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket {{ $turno->codigo_completo }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: white;
            font-family: 'Courier New', monospace;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .ticket {
            width: 80mm;
            padding: 5mm;
            text-align: center;
            line-height: 1.3;
            position: relative;
        }
        .logo { height: 40px; margin-bottom: 8px; }
        .label { font-size: 10px; color: #666; font-weight: bold; margin-bottom: 2px; }
        .turno { font-size: 36px; font-weight: bold; color: #064b9e; letter-spacing: 2px; margin: 4px 0; }
        .servicio { font-size: 12px; font-weight: bold; color: #111; padding: 0 4px; }
        .sep { border-top: 1px dashed #888; margin: 8px 0; }
        .info { font-size: 10px; color: #444; }
        .info div { margin: 2px 0; }
        .qr { position: absolute; top: 2mm; right: 2mm; }
        @media print {
            body { min-height: auto; }
            .ticket { width: 80mm; padding: 5mm; }
        }
        @media screen {
            .ticket { border: 2px dashed #ccc; max-width: 300px; padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="ticket">
        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="logo">

        <div class="label">TURNO</div>
        <div class="turno">{{ $turno->codigo_completo }}</div>

        <div class="label">SERVICIO</div>
        <div class="servicio">{{ strtoupper($servicio->nombre_completo ?? $servicio->nombre) }}</div>

        <div class="sep"></div>

        <div class="info">
            <div>FECHA: {{ $turno->fecha_creacion->format('d/m/Y') }}</div>
            <div>HORA: {{ $turno->fecha_creacion->format('h:i:s A') }}</div>
        </div>

        <div class="qr">
            {!! QrCode::size(40)->generate(route('mobile.display', ['turno' => $turno->id])) !!}
        </div>
    </div>

    <script>
        // Imprimir INMEDIATAMENTE al cargar (sin delay)
        window.onload = function() {
            window.print();
        };

        // Fallback: si afterprint no se dispara (ej: kiosk mode), redirigir después de 5s
        let fallbackTimer = setTimeout(function() {
            window.location.href = '{{ route('turnos.menu') }}';
        }, 5000);

        // Redirigir al menú después de imprimir (cancela el fallback)
        window.addEventListener('afterprint', function() {
            clearTimeout(fallbackTimer);
            window.location.href = '{{ route('turnos.menu') }}';
        });
    </script>
</body>
</html>
