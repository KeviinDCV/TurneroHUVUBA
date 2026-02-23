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
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
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
        <div class="report-title">REPORTE DE TURNOS</div>
        <div class="report-period">
            Período: {{ $fecha_inicio->format('d/m/Y') }} - {{ $fecha_fin->format('d/m/Y') }}
        </div>
        <div class="generation-date">
            Generado el {{ $fecha_generacion->format('d/m/Y') }} a las {{ $fecha_generacion->format('H:i:s') }}
        </div>
    </div>

    <!-- Resumen General -->
    <div class="section">
        <div class="section-title">RESUMEN GENERAL</div>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-label">Total de Turnos</div>
                <div class="summary-value">{{ number_format($estadisticas['resumen']['total_turnos']) }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Turnos Atendidos</div>
                <div class="summary-value">{{ number_format($estadisticas['resumen']['turnos_atendidos']) }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Turnos Pendientes</div>
                <div class="summary-value">{{ number_format($estadisticas['resumen']['turnos_pendientes']) }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Porcentaje de Atención</div>
                <div class="summary-value">{{ $estadisticas['resumen']['porcentaje_atencion'] }}%</div>
            </div>
        </div>
        
        @if($estadisticas['resumen']['tiempo_promedio_atencion'])
            <div class="summary-item" style="margin-top: 10px;">
                <div class="summary-label">Tiempo Promedio de Atención</div>
                <div class="summary-value">{{ round($estadisticas['resumen']['tiempo_promedio_atencion'] / 60, 2) }} minutos</div>
            </div>
        @endif
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
                    <th class="text-center">Cancelados</th>
                    <th class="text-center">Tiempo Promedio (mm:ss)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($estadisticas['por_servicio'] as $servicio => $datos)
                <tr>
                    <td>{{ $servicio }}</td>
                    <td class="text-center">{{ $datos['total'] }}</td>
                    <td class="text-center">{{ $datos['atendidos'] }}</td>
                    <td class="text-center">{{ $datos['pendientes'] }}</td>
                    <td class="text-center">{{ $datos['cancelados'] }}</td>
                    <td class="text-center">
                        @if(isset($datos['tiempo_promedio']) && $datos['tiempo_promedio'])
                            @php
                                $val = abs($datos['tiempo_promedio']);
                                $min = floor($val / 60);
                                $seg = $val % 60;
                            @endphp
                            {{ sprintf('%02d:%02d', $min, $seg) }}
                        @else
                            N/A
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Estadísticas por Asesor -->
    @if($estadisticas['por_asesor']->count() > 0)
    <div class="section page-break">
        <div class="section-title">RENDIMIENTO POR ASESOR</div>
        <table>
            <thead>
                <tr>
                    <th>Asesor</th>
                    <th class="text-center">Total Turnos</th>
                    <th class="text-center">Turnos Atendidos</th>
                    <th class="text-center">Tiempo Promedio (mm:ss)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($estadisticas['por_asesor'] as $asesorId => $datos)
                <tr>
                    <td>{{ $datos['nombre_completo'] ?? ($datos['nombre_usuario'] ?? 'N/A') }}</td>
                    <td class="text-center">{{ $datos['total'] }}</td>
                    <td class="text-center">{{ $datos['atendidos'] }}</td>
                    <td class="text-center">
                        @if(isset($datos['tiempo_promedio_atencion']) && $datos['tiempo_promedio_atencion'])
                            @php
                                $val = abs($datos['tiempo_promedio_atencion']);
                                $min = floor($val / 60);
                                $seg = $val % 60;
                            @endphp
                            {{ sprintf('%02d:%02d', $min, $seg) }}
                        @else
                            N/A
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
        <div class="section-title">DETALLE DE TURNOS (Primeros 50 registros)</div>
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Servicio</th>
                    <th>Asesor</th>
                    <th>Estado</th>
                    <th>Prioridad</th>
                    <th>Fecha Creación</th>
                    <th>Duración (mm:ss)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($turnos->take(50) as $turno)
                <tr>
                    <td>{{ $turno->codigo }}-{{ $turno->numero }}</td>
                    <td>{{ $turno->servicio->nombre ?? 'N/A' }}</td>
                    <td>{{ $turno->asesor->nombre_usuario ?? 'N/A' }}</td>
                    <td>
                        <span class="status-badge status-{{ strtolower($turno->estado) }}">
                            {{ strtoupper($turno->estado) }}
                        </span>
                    </td>
                    <td class="priority-{{ strtolower($turno->prioridad) }}">
                        {{ strtoupper($turno->prioridad) }}
                    </td>
                    <td>{{ $turno->fecha_creacion ? \Carbon\Carbon::parse($turno->fecha_creacion)->format('d/m/Y H:i') : 'N/A' }}</td>
                    <td class="text-center">
                        @if($turno->duracion_atencion)
                            @php
                                $val = abs($turno->duracion_atencion);
                                $min = floor($val / 60);
                                $seg = $val % 60;
                            @endphp
                            {{ sprintf('%02d:%02d', $min, $seg) }}
                        @else
                            N/A
                        @endif
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
