<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Turnos - Hospital Universitario del Valle</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #064b9e;
            padding-bottom: 20px;
        }
        
        .hospital-name {
            font-size: 24px;
            font-weight: bold;
            color: #064b9e;
            margin-bottom: 5px;
        }
        
        .report-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .report-period {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .generation-date {
            font-size: 10px;
            color: #999;
        }
        
        .section {
            margin-bottom: 25px;
        }
        /* Las tablas largas se parten entre páginas (antes saltaban enteras y dejaban páginas casi vacías);
           el encabezado de la tabla se repite y ninguna fila queda cortada. */
        .section-title {
            page-break-after: avoid;
        }
        thead {
            display: table-header-group;
        }
        tr {
            page-break-inside: avoid;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #064b9e;
            margin-bottom: 15px;
            border-bottom: 2px solid #064b9e;
            padding-bottom: 5px;
        }
        
        .summary-grid {
            margin-bottom: 12px;
        }
        .summary-grid td {
            width: 50%;
            padding: 0 6px 8px 0;
            border-bottom: none;
            vertical-align: top;
            background: #fff;
        }
        .summary-grid .summary-item {
            padding: 9px 12px;
        }
        
        .summary-item {
            background-color: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #064b9e;
            border-radius: 4px;
        }
        
        .summary-label {
            font-weight: bold;
            color: #064b9e;
            margin-bottom: 5px;
        }
        
        .summary-value {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        .summary-note {
            font-size: 9px;
            color: #666;
            margin-top: 3px;
        }
        .notas p {
            font-size: 9px;
            color: #555;
            font-style: italic;
            margin: 0 0 4px;
        }
        .tabla-asesores {
            font-size: 9px;
        }
        .tabla-asesores th, .tabla-asesores td {
            padding: 5px 4px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }
        
        th {
            background-color: #064b9e;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
        }
        
        td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .status-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-atendido {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-pendiente {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-cancelado {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .priority-normal {
            color: #666;
        }
        
        .priority-prioritaria {
            color: #dc3545;
            font-weight: bold;
        }
        
        .footer {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 20px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="hospital-name">HOSPITAL UNIVERSITARIO DEL VALLE</div>
        <div class="report-title">INFORME DE TURNOS{{ !empty($contexto['unidad']) ? ' · ' . mb_strtoupper($contexto['unidad'], 'UTF-8') : '' }}</div>
        <div class="report-period">
            Período: {{ $fecha_inicio->format('d/m/Y') }} - {{ $fecha_fin->format('d/m/Y') }}
        </div>
        <div class="report-period">Alcance: {{ $contexto['alcance'] ?? 'Todo el turnero' }}</div>
        <div class="generation-date">
            Generado el {{ $fecha_generacion->format('d/m/Y') }} a las {{ $fecha_generacion->format('H:i:s') }}{{ !empty($contexto['generado_por']) ? ' por ' . $contexto['generado_por'] : '' }}
        </div>
    </div>

    <!-- Resumen General -->
    <div class="section">
        <div class="section-title">RESUMEN GENERAL</div>
        @php
            $r = $estadisticas['resumen'];
            $cifra = fn ($n) => number_format($n ?? 0, 0, ',', '.');
            $tarjetas = [
                ['Total de Turnos', $cifra($r['total_turnos']), null],
                ['Turnos Atendidos', $cifra($r['turnos_atendidos']), null],
                ['Turnos Pendientes', $cifra($r['turnos_pendientes']), $r['turnos_en_espera'] . ' en espera · ' . $r['turnos_aplazados'] . ' aplazados'],
                ['Porcentaje de Atención', number_format($r['porcentaje_atencion'], 1, ',', '.') . ' %', null],
                ['Turnos Transferidos', $cifra($r['turnos_transferidos'] ?? 0), null],
                ['Llamados sin Cerrar', $cifra($r['turnos_sin_cerrar']), 'Llamados que nadie marcó como atendidos ni aplazados'],
                ['Turnos Cancelados', $cifra($r['turnos_cancelados']), null],
                ['Tiempo Promedio de Atención', \App\Services\MetricasTurnos::texto($r['tiempo_promedio_atencion']), $r['atenciones_atipicas'] ? 'Sin ' . $r['atenciones_atipicas'] . ' atenciones de más de 1 hora' : null],
                ['Tiempo Promedio entre Turnos', \App\Services\MetricasTurnos::texto($r['entre_turnos_promedio']), null],
            ];
        @endphp
        {{-- dompdf no entiende grid ni flex, y con inline-block las tarjetas caían una por fila: tabla de dos columnas --}}
        <table class="summary-grid">
            @foreach(array_chunk($tarjetas, 2) as $par)
            <tr>
                @foreach($par as [$etiqueta, $valor, $nota])
                <td>
                    <div class="summary-item">
                        <div class="summary-label">{{ $etiqueta }}</div>
                        <div class="summary-value">{{ $valor }}</div>
                        @if($nota)<div class="summary-note">{{ $nota }}</div>@endif
                    </div>
                </td>
                @endforeach
            </tr>
            @endforeach
        </table>
        <div class="notas">
            @foreach($notas as $nota)
                <p>{{ $nota }}</p>
            @endforeach
        </div>
    </div>

    <!-- Estadísticas por Servicio -->
    @if($estadisticas['por_servicio']->count() > 0)
    <div class="section">
        <div class="section-title">ESTADÍSTICAS POR SERVICIO</div>
        <table>
            <thead>
                <tr>
                    <th>Servicio</th>
                    <th class="text-center">Total</th>
                    <th class="text-center">Atendidos</th>
                    <th class="text-center">Pendientes</th>
                    <th class="text-center">Sin cerrar</th>
                    <th class="text-center">Cancelados</th>
                    <th class="text-center">Transferidos</th>
                    <th class="text-center">Tiempo promedio de atención</th>
                </tr>
            </thead>
            <tbody>
                @foreach($estadisticas['por_servicio'] as $servicio => $datos)
                <tr>
                    <td>{{ $servicio }}</td>
                    <td class="text-center">{{ $datos['total'] }}</td>
                    <td class="text-center">{{ $datos['atendidos'] }}</td>
                    <td class="text-center">{{ $datos['pendientes'] }}</td>
                    <td class="text-center">{{ $datos['sin_cerrar'] }}</td>
                    <td class="text-center">{{ $datos['cancelados'] }}</td>
                    <td class="text-center">{{ $datos['transferidos'] ?? 0 }}</td>
                    <td class="text-center">{{ \App\Services\MetricasTurnos::texto($datos['tiempo_promedio']) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Estadísticas por Asesor -->
    @if($estadisticas['por_asesor']->count() > 0)
    <div class="section">
        <div class="section-title">RENDIMIENTO POR ASESOR</div>
        <table class="tabla-asesores">
            <thead>
                <tr>
                    <th>Asesor</th>
                    <th class="text-center">Atendidos</th>
                    <th class="text-center">Transferidos</th>
                    <th class="text-center">Promedio de atención</th>
                    <th class="text-center">Total de atención</th>
                    <th class="text-center">Promedio entre turnos</th>
                    <th class="text-center">Total entre turnos</th>
                    <th class="text-center">Pausas de más de 1 h</th>
                </tr>
            </thead>
            <tbody>
                @foreach($estadisticas['por_asesor'] as $asesorId => $datos)
                <tr>
                    <td>{{ $datos['nombre_completo'] }}</td>
                    <td class="text-center">{{ $datos['atendidos'] }}</td>
                    <td class="text-center">{{ $datos['transferidos'] ?? 0 }}</td>
                    <td class="text-center">
                        {{ \App\Services\MetricasTurnos::texto($datos['tiempo_promedio_atencion']) }}
                        @if($datos['atipicas'])<div class="summary-note">sin {{ $datos['atipicas'] }} de más de 1 h</div>@endif
                    </td>
                    <td class="text-center">{{ \App\Services\MetricasTurnos::texto($datos['tiempo_total_atencion']) }}</td>
                    <td class="text-center">{{ \App\Services\MetricasTurnos::texto($datos['entre_turnos_promedio']) }}</td>
                    <td class="text-center">{{ \App\Services\MetricasTurnos::texto($datos['entre_turnos_total']) }}</td>
                    <td class="text-center">
                        @if($datos['pausas_largas'])
                            {{ $datos['pausas_largas'] }} ({{ \App\Services\MetricasTurnos::texto($datos['pausas_largas_total']) }})
                        @else
                            0
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Detalle de Turnos (Solo primeros 50 para evitar PDF muy largo) -->
    @if($turnos->count() > 0)
    <div class="section page-break">
        <div class="section-title">DETALLE DE TURNOS (los 50 más recientes; el Excel trae todos)</div>
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Servicio</th>
                    <th>Asesor</th>
                    <th>Estado</th>
                    <th>Prioridad</th>
                    <th>Llegada</th>
                    <th>Duración</th>
                    <th>Transferencia</th>
                </tr>
            </thead>
            <tbody>
                @foreach($turnos->take(50) as $turno)
                <tr>
                    <td>{{ $turno->codigo_completo }}</td>
                    <td>{{ $turno->servicio->nombre ?? 'N/A' }}</td>
                    <td>{{ $turno->asesor->nombre_completo ?? ($turno->asesor->nombre_usuario ?? 'N/A') }}</td>
                    <td>
                        <span class="status-badge status-{{ strtolower($turno->estado) }}">
                            {{ strtoupper($turno->estado) }}
                        </span>
                    </td>
                    <td class="{{ $turno->prioridad >= 4 ? 'priority-prioritaria' : 'priority-normal' }}">
                        {{ $turno->prioridad >= 4 ? 'Prioritario' : 'Normal' }}
                    </td>
                    {{-- created_at es la hora real de llegada: fecha_creacion se reescribe en cada cambio del turno --}}
                    <td>{{ $turno->created_at ? \Carbon\Carbon::parse($turno->created_at)->format('d/m/Y H:i') : 'N/A' }}</td>
                    <td class="text-center">
                        @php $segundos = $turno->estado === 'atendido' ? \App\Services\MetricasTurnos::duracion($turno) : null; @endphp
                        {{ \App\Services\MetricasTurnos::texto($segundos) }}{{ \App\Services\MetricasTurnos::esAtipica($segundos) ? ' (abierto)' : '' }}
                    </td>
                    <td style="font-size: 10px;">
                        @php
                            $obs = $turno->observaciones ?? '';
                            if (str_starts_with($obs, 'Transferido a ')) {
                                $tx = 'A: ' . \Illuminate\Support\Str::limit(str_replace('Transferido a ', '', $obs), 20);
                            } elseif (str_starts_with($obs, 'Transferido desde ')) {
                                $tx = 'DE: ' . \Illuminate\Support\Str::limit(str_replace('Transferido desde ', '', $obs), 20);
                            } else {
                                $tx = '-';
                            }
                        @endphp
                        {{ $tx }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @if($turnos->count() > 50)
        <p style="font-style: italic; color: #666; text-align: center; margin-top: 10px;">
            Mostrando los primeros 50 de {{ $turnos->count() }} turnos totales. 
            Para ver todos los registros, exporte en formato Excel.
        </p>
        @endif
    </div>
    @else
    <div class="section">
        <div class="section-title">DETALLE DE TURNOS</div>
        <div class="no-data">
            No se encontraron turnos en el período seleccionado.
        </div>
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <div>Hospital Universitario del Valle - Sistema de Turnos</div>
        <div>Reporte generado automáticamente el {{ $fecha_generacion->format('d/m/Y H:i:s') }}</div>
    </div>
</body>
</html>
