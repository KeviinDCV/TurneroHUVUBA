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

        /* Ubicacion de la seccion del turno.
           Negro puro y sin fondos: es lo que mejor rinde en impresora termica. */
        .guia { border: 2px solid #000; padding: 5px 6px 6px; margin: 8px 0; text-align: center; }
        .guia-titulo { font-size: 9px; font-weight: bold; letter-spacing: 1px; color: #000; margin-bottom: 3px; }
        .guia-lugar { font-size: 11px; font-weight: bold; color: #000; letter-spacing: 1px; }
        .guia-sitio { font-size: 16px; font-weight: bold; color: #000; line-height: 1.2; margin-top: 2px; }
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

        {{-- Ubicacion de la seccion del turno. Si la seccion no tiene ubicacion
             definida (p. ej. CITAS), no se imprime nada.
             Para cambiar ventanillas/cajas: App\Models\Servicio::ubicacionAtencion() --}}
        @php($ubicacion = $servicio->ubicacionAtencion())
        @if($ubicacion)
            <div class="guia">
                <div class="guia-titulo">¿DÓNDE DEBO UBICARME?</div>
                <div class="guia-lugar">{{ $ubicacion['lugar'] }}</div>
                <div class="guia-sitio">{{ $ubicacion['sitio'] }}</div>
            </div>
        @endif

        <div class="info">
            <div>FECHA: {{ $turno->fecha_creacion->format('d/m/Y') }}</div>
            <div>HORA: {{ $turno->fecha_creacion->format('h:i:s A') }}</div>
        </div>

        <div class="qr">
            {!! QrCode::size(40)->generate(route('mobile.display', ['turno' => $turno->id])) !!}
        </div>
    </div>

    <script>
        // Imprimir cuando la página cargue completamente
        let printed = false;
        
        function intentarImprimir() {
            if (printed) return;
            printed = true;
            try {
                window.print();
            } catch(e) {
                console.error('Error al imprimir:', e);
            }
        }
        
        window.onload = function() {
            // Pequeño delay para asegurar que todo esté renderizado
            setTimeout(intentarImprimir, 200);
        };

        // Fallback más largo para servidores lentos (15s)
        let fallbackTimer = setTimeout(function() {
            window.location.href = '{{ route('turnos.menu') }}';
        }, 15000);

        // Redirigir al menú después de imprimir (cancela el fallback)
        window.addEventListener('afterprint', function() {
            clearTimeout(fallbackTimer);
            setTimeout(function() {
                window.location.href = '{{ route('turnos.menu') }}';
            }, 500);
        });
    </script>
</body>
</html>
