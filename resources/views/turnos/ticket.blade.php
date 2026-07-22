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
        /* Letra mas GRANDE pero sobre todo mas ANCHA: el scaleX ensancha los
           glifos sin gastar ni un milimetro de papel a lo alto. Solo se aplica a
           textos cortos; .servicio NO se ensancha porque es largo y se envolveria
           en mas lineas (eso si gastaria papel). */
        /* inline-block = la caja se ajusta al texto, asi el scaleX ensancha SOLO
           las letras y nunca desborda los 80mm ni empuja un salto de pagina. */
        .ancho { display: inline-block; transform: scaleX(1.18); }
        .logo { height: 36px; margin-bottom: 6px; }
        .label { font-size: 11px; color: #444; font-weight: bold; margin-bottom: 1px; }
        .turno { font-size: 38px; font-weight: bold; color: #064b9e; letter-spacing: 2px; margin: 3px 0; line-height: 1.15; }
        .servicio { font-size: 13px; font-weight: bold; color: #000; padding: 0 2px; line-height: 1.2; }
        .sep { border-top: 1px dashed #888; margin: 6px 0; }
        .info { font-size: 12px; color: #222; }
        .info div { margin: 1px 0; }

        /* Ubicacion de la seccion del turno. Compacta a proposito: si el ticket
           crece de mas, la impresora lo parte en dos paginas y separa FECHA/HORA.
           Negro puro y sin fondos: es lo que mejor rinde en impresora termica. */
        .guia { border: 2px solid #000; padding: 3px 4px 4px; margin: 6px 0; text-align: center; }
        .guia-titulo { font-size: 9px; font-weight: bold; letter-spacing: 1px; color: #000; line-height: 1.1; }
        .guia-sitio { font-size: 13px; font-weight: bold; color: #000; line-height: 1.2; margin-top: 1px; }
        .guia-rango { font-size: 21px; font-weight: bold; color: #000; line-height: 1.1; white-space: nowrap; }
        .qr { position: absolute; top: 2mm; right: 2mm; }
        @media print {
            /* Sin margenes de pagina: el ticket debe caber en UNA sola pagina.
               Si sobra alto, la impresora lo parte y separa FECHA/HORA. */
            @page { size: auto; margin: 0; }
            body { min-height: auto; }
            .ticket { width: 80mm; padding: 4mm 5mm; }
            /* Blindaje: que nada se corte a la mitad */
            .ticket, .guia, .info { page-break-inside: avoid; break-inside: avoid; }
        }
        @media screen {
            .ticket { border: 2px dashed #ccc; max-width: 300px; padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="ticket">
        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="logo">

        <div class="label"><span class="ancho">TURNO</span></div>
        <div class="turno"><span class="ancho">{{ $turno->codigo_completo }}</span></div>

        <div class="label"><span class="ancho">SERVICIO</span></div>
        <div class="servicio">{{ strtoupper($servicio->nombre_completo ?? $servicio->nombre) }}</div>

        <div class="sep"></div>

        {{-- Ubicacion de la seccion del turno. Si la seccion no tiene ubicacion
             definida (p. ej. CITAS), no se imprime nada.
             Para cambiar ventanillas/cajas: App\Models\Servicio::ubicacionAtencion() --}}
        @php($ubicacion = $servicio->ubicacionAtencion())
        @if($ubicacion)
            <div class="guia">
                <div class="guia-titulo"><span class="ancho">¿DÓNDE DEBO UBICARME?</span></div>
                <div class="guia-sitio"><span class="ancho">{{ $ubicacion['sitio'] }}</span></div>
                <div class="guia-rango"><span class="ancho">{{ $ubicacion['rango'] }}</span></div>
            </div>
        @endif

        <div class="info">
            <div><span class="ancho">FECHA: {{ $turno->fecha_creacion->format('d/m/Y') }}</span></div>
            <div><span class="ancho">HORA: {{ $turno->fecha_creacion->format('h:i:s A') }}</span></div>
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
