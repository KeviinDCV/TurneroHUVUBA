<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Histórico - {{ $fecha_inicio->format('d/m/Y') }} - {{ $fecha_fin->format('d/m/Y') }}</title>
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
        
        .header h1 {
            color: #064b9e;
            font-size: 24px;
            margin: 0 0 10px 0;
            font-weight: bold;
        }
        
        .header h2 {
            color: #666;
            font-size: 16px;
            margin: 0 0 10px 0;
            font-weight: normal;
        }
        
        .header .period {
            color: #064b9e;
            font-size: 14px;
            font-weight: bold;
        }
        
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        
        .section-title {
            background-color: #064b9e;
            color: white;
            padding: 8px 12px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            border: 1px solid #ddd;
            padding: 12px;
            background-color: #f8f9fa;
        }
        
        .stat-label {
            font-weight: bold;
            color: #064b9e;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-top: 5px;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .table th {
            background-color: #064b9e;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
        }
        
        .table td {
            padding: 6px 8px;
            border-bottom: 1px solid #ddd;
            font-size: 10px;
        }
        
        .table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .footer {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media print {
            .page-break {
                page-break-before: always;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>DASHBOARD HISTÓRICO</h1>
        <h2>Sistema de Turnos - Hospital Universitario del Valle</h2>
        <div class="period">
            Período: {{ $fecha_inicio->format('d/m/Y') }} - {{ $fecha_fin->format('d/m/Y') }}
        </div>
        <div style="margin-top: 10px; font-size: 11px; color: #666;">
            Generado el {{ $fecha_generacion->format('d/m/Y H:i:s') }}
        </div>
    </div>

    <!-- Estadísticas Generales -->
    <div class="section">
        <div class="section-title">ESTADÍSTICAS GENERALES</div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total de Turnos</div>
                <div class="stat-value">{{ number_format($dashboard_data['estadisticas_generales']['total_turnos']) }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Turnos Atendidos</div>
                <div class="stat-value">{{ number_format($dashboard_data['estadisticas_generales']['turnos_atendidos']) }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Turnos Pendientes</div>
                <div class="stat-value">{{ number_format($dashboard_data['estadisticas_generales']['turnos_pendientes']) }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Tiempo Promedio (min)</div>
                <div class="stat-value">{{ $dashboard_data['estadisticas_generales']['tiempo_promedio_atencion'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Servicios Activos</div>
                <div class="stat-value">{{ $dashboard_data['estadisticas_generales']['servicios_activos'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Asesores Activos</div>
                <div class="stat-value">{{ $dashboard_data['estadisticas_generales']['asesores_activos'] }}</div>
            </div>
        </div>
    </div>

    <!-- Distribución por Estados -->
    <div class="section">
        <div class="section-title">DISTRIBUCIÓN POR ESTADOS</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Estado</th>
                    <th>Cantidad</th>
                    <th>Porcentaje</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dashboard_data['distribucion_estados'] as $estado => $cantidad)
                <tr>
                    <td style="text-transform: capitalize;">{{ $estado }}</td>
                    <td>{{ number_format($cantidad) }}</td>
                    <td>{{ round(($cantidad / $dashboard_data['estadisticas_generales']['total_turnos']) * 100, 1) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Distribución por Servicios y Top Asesores -->
    <div class="two-column">
        <div class="section">
            <div class="section-title">DISTRIBUCIÓN POR SERVICIOS</div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Servicio</th>
                        <th>Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dashboard_data['distribucion_servicios'] as $servicio => $cantidad)
                    <tr>
                        <td>{{ $servicio }}</td>
                        <td>{{ number_format($cantidad) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">TOP ASESORES</div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Asesor</th>
                        <th>Atendidos</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dashboard_data['top_asesores'] as $asesor => $cantidad)
                    <tr>
                        <td>{{ $asesor }}</td>
                        <td>{{ number_format($cantidad) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div>Hospital Universitario del Valle - Sistema de Turnos</div>
        <div>Dashboard Histórico generado el {{ $fecha_generacion->format('d/m/Y H:i:s') }}</div>
    </div>
</body>
</html>
