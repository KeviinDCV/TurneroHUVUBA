<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Turno;
use App\Models\TurnoHistorial;
use App\Models\Servicio;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Mostrar la vista principal de reportes
     */
    public function index()
    {
        $user = Auth::user();
        $usuarios = User::where('rol', 'Asesor')->orderBy('nombre_completo')->get();
        $servicios = Servicio::where('estado', 'activo')->orderBy('nombre')->get();

        return view('admin.reportes', compact('user', 'usuarios', 'servicios'));
    }

    /**
     * Generar reporte general
     */
    public function generarReporte(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'usuarios' => 'nullable|array',
            'servicios' => 'nullable|array',
            'formato' => 'required|in:excel,pdf'
        ]);

        $fechaInicio = Carbon::parse($request->fecha_inicio)->startOfDay();
        $fechaFin = Carbon::parse($request->fecha_fin)->endOfDay();
        $usuarios = $request->usuarios ?? [];
        $servicios = $request->servicios ?? [];

        // Construir consulta base
        $query = Turno::with(['servicio', 'asesor', 'caja'])
            ->whereBetween('fecha_creacion', [$fechaInicio, $fechaFin]);

        // Filtrar por usuarios si se especifica
        if (!empty($usuarios)) {
            $query->whereIn('asesor_id', $usuarios);
        }

        // Filtrar por servicios si se especifica
        if (!empty($servicios)) {
            $query->whereIn('servicio_id', $servicios);
        }

        $turnos = $query->orderBy('fecha_creacion', 'desc')->get();

        // Generar estadísticas
        $estadisticas = $this->generarEstadisticas($turnos, $fechaInicio, $fechaFin);

        if ($request->formato === 'excel') {
            return $this->exportarExcel($turnos, $estadisticas, $fechaInicio, $fechaFin);
        } else {
            return $this->exportarPDF($turnos, $estadisticas, $fechaInicio, $fechaFin);
        }
    }

    /**
     * Generar estadísticas del reporte
     */
    private function generarEstadisticas($turnos, $fechaInicio, $fechaFin)
    {
        $totalTurnos = $turnos->count();
        $turnosAtendidos = $turnos->where('estado', 'atendido')->count();
        $turnosPendientes = $turnos->whereIn('estado', ['pendiente', 'aplazado'])->count();
        $turnosCancelados = $turnos->where('estado', 'cancelado')->count();

        // Estadísticas por servicio
        $porServicio = $turnos->groupBy('servicio.nombre')->map(function ($grupo) {
            return [
                'total' => $grupo->count(),
                'atendidos' => $grupo->where('estado', 'atendido')->count(),
                'pendientes' => $grupo->whereIn('estado', ['pendiente', 'aplazado'])->count(),
                'cancelados' => $grupo->where('estado', 'cancelado')->count(),
                'tiempo_promedio' => $grupo->where('estado', 'atendido')->avg('duracion_atencion')
            ];
        });

        // Estadísticas por asesor
        $porAsesor = $turnos->whereNotNull('asesor_id')->groupBy('asesor.nombre_usuario')->map(function ($grupo) {
            return [
                'total' => $grupo->count(),
                'atendidos' => $grupo->where('estado', 'atendido')->count(),
                'tiempo_promedio' => $grupo->where('estado', 'atendido')->avg('duracion_atencion')
            ];
        });

        // Estadísticas por día
        $porDia = $turnos->groupBy(function ($turno) {
            return Carbon::parse($turno->fecha_creacion)->format('Y-m-d');
        })->map(function ($grupo) {
            return [
                'total' => $grupo->count(),
                'atendidos' => $grupo->where('estado', 'atendido')->count(),
                'pendientes' => $grupo->whereIn('estado', ['pendiente', 'aplazado'])->count()
            ];
        });

        return [
            'resumen' => [
                'total_turnos' => $totalTurnos,
                'turnos_atendidos' => $turnosAtendidos,
                'turnos_pendientes' => $turnosPendientes,
                'turnos_cancelados' => $turnosCancelados,
                'porcentaje_atencion' => $totalTurnos > 0 ? round(($turnosAtendidos / $totalTurnos) * 100, 2) : 0,
                'tiempo_promedio_atencion' => $turnos->where('estado', 'atendido')->avg('duracion_atencion')
            ],
            'por_servicio' => $porServicio,
            'por_asesor' => $porAsesor,
            'por_dia' => $porDia
        ];
    }

    /**
     * Exportar a Excel
     */
    private function exportarExcel($turnos, $estadisticas, $fechaInicio, $fechaFin)
    {
        $spreadsheet = new Spreadsheet();

        // Hoja 1: Resumen
        $this->crearHojaResumen($spreadsheet, $estadisticas, $fechaInicio, $fechaFin);

        // Hoja 2: Detalle de turnos
        $this->crearHojaDetalle($spreadsheet, $turnos);

        // Hoja 3: Estadísticas por servicio
        $this->crearHojaServicios($spreadsheet, $estadisticas['por_servicio']);

        // Hoja 4: Estadísticas por asesor
        $this->crearHojaAsesores($spreadsheet, $estadisticas['por_asesor']);

        $filename = 'reporte_turnos_' . $fechaInicio->format('Y-m-d') . '_' . $fechaFin->format('Y-m-d') . '.xlsx';

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Exportar a PDF
     */
    private function exportarPDF($turnos, $estadisticas, $fechaInicio, $fechaFin)
    {
        $data = [
            'turnos' => $turnos,
            'estadisticas' => $estadisticas,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'fecha_generacion' => Carbon::now()
        ];

        $pdf = Pdf::loadView('admin.reportes.pdf', $data);
        $pdf->setPaper('A4', 'portrait');

        $filename = 'reporte_turnos_' . $fechaInicio->format('Y-m-d') . '_' . $fechaFin->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Exportar dashboard histórico
     */
    public function exportarDashboardHistorico(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'servicio_id' => 'nullable|integer',
            'formato' => 'required|in:excel,pdf'
        ]);

        $fechaInicio = Carbon::parse($request->fecha_inicio)->startOfDay();
        $fechaFin = Carbon::parse($request->fecha_fin)->endOfDay();
        $servicioId = $request->servicio_id;

        // Obtener datos para el dashboard
        $dashboardData = $this->obtenerDatosDashboardHistorico($fechaInicio, $fechaFin, $servicioId);

        if ($request->formato === 'excel') {
            return $this->exportarDashboardExcel($dashboardData, $fechaInicio, $fechaFin);
        } else {
            return $this->exportarDashboardPDF($dashboardData, $fechaInicio, $fechaFin);
        }
    }

    /**
     * Obtener datos para el dashboard histórico
     */
    private function obtenerDatosDashboardHistorico($fechaInicio, $fechaFin, $servicioId = null)
    {
        // Usar TurnoHistorial para datos históricos, como lo hace GraficosController
        // Filtrar solo los registros de 'creacion' para evitar duplicados
        $query = TurnoHistorial::with(['servicio', 'asesor', 'caja'])
            ->whereBetween('fecha_creacion', [$fechaInicio, $fechaFin])
            ->where('tipo_backup', 'creacion');

        if ($servicioId) {
            $query->where('servicio_id', $servicioId);
        }

        $turnos = $query->get();

        // Debug logging
        \Log::info('Dashboard Export Debug', [
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'servicio_id' => $servicioId,
            'total_turnos_query' => $turnos->count(),
            'sample_turnos' => $turnos->take(3)->toArray()
        ]);

        // Estadísticas generales
        $estadisticasGenerales = [
            'total_turnos' => $turnos->count(),
            'turnos_atendidos' => $turnos->where('estado', 'atendido')->count(),
            'turnos_pendientes' => $turnos->where('estado', 'pendiente')->count(),
            'turnos_llamados' => $turnos->where('estado', 'llamado')->count(),
            'turnos_aplazados' => $turnos->where('estado', 'aplazado')->count(),
            'turnos_cancelados' => $turnos->where('estado', 'cancelado')->count(),
            'tiempo_promedio_atencion' => $this->calcularTiempoPromedioAtencion($turnos),
            'servicios_activos' => $turnos->pluck('servicio_id')->unique()->count(),
            'asesores_activos' => $turnos->pluck('asesor_id')->unique()->count()
        ];

        // Distribución por servicios
        $distribucionServicios = $turnos->filter(function($turno) {
                return $turno->servicio;
            })
            ->groupBy(function($turno) {
                return $turno->servicio->nombre;
            })
            ->map(function ($turnosServicio) {
                return $turnosServicio->count();
            })->toArray();

        // Distribución por estados
        $distribucionEstados = $turnos->groupBy('estado')
            ->map(function ($turnosEstado) {
                return $turnosEstado->count();
            })->toArray();

        // Top asesores
        $topAsesores = $turnos->where('estado', 'atendido')
            ->filter(function($turno) {
                return $turno->asesor;
            })
            ->groupBy(function($turno) {
                return $turno->asesor->nombre_completo ?? $turno->asesor->nombre_usuario ?? 'Sin nombre';
            })
            ->map(function ($turnosAsesor) {
                return $turnosAsesor->count();
            })
            ->sortDesc()
            ->take(10)
            ->toArray();

        // Análisis por horas
        $analisisHoras = $turnos->groupBy(function ($turno) {
            return Carbon::parse($turno->fecha_creacion)->format('H');
        })->map(function ($turnosHora) {
            return $turnosHora->count();
        })->toArray();

        return [
            'estadisticas_generales' => $estadisticasGenerales,
            'distribucion_servicios' => $distribucionServicios,
            'distribucion_estados' => $distribucionEstados,
            'top_asesores' => $topAsesores,
            'analisis_horas' => $analisisHoras,
            'turnos_detalle' => $turnos->take(100) // Limitar para el reporte
        ];
    }

    /**
     * Exportar dashboard a Excel
     */
    private function exportarDashboardExcel($dashboardData, $fechaInicio, $fechaFin)
    {
        $spreadsheet = new Spreadsheet();

        // Hoja 1: Resumen Dashboard
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Dashboard Histórico');

        // Encabezado
        $sheet->setCellValue('A1', 'DASHBOARD HISTÓRICO - SISTEMA DE TURNOS');
        $sheet->setCellValue('A2', 'Hospital Universitario del Valle');
        $sheet->setCellValue('A3', 'Período: ' . $fechaInicio->format('d/m/Y') . ' - ' . $fechaFin->format('d/m/Y'));
        $sheet->setCellValue('A4', 'Generado: ' . Carbon::now()->format('d/m/Y H:i:s'));

        // Estadísticas generales
        $row = 6;
        $sheet->setCellValue('A' . $row, 'ESTADÍSTICAS GENERALES');
        $row++;

        foreach ($dashboardData['estadisticas_generales'] as $key => $value) {
            $label = ucwords(str_replace('_', ' ', $key));
            $sheet->setCellValue('A' . $row, $label);
            $sheet->setCellValue('B' . $row, $value);
            $row++;
        }

        // Distribución por servicios
        $row += 2;
        $sheet->setCellValue('A' . $row, 'DISTRIBUCIÓN POR SERVICIOS');
        $row++;
        $sheet->setCellValue('A' . $row, 'Servicio');
        $sheet->setCellValue('B' . $row, 'Cantidad');
        $row++;

        foreach ($dashboardData['distribucion_servicios'] as $servicio => $cantidad) {
            $sheet->setCellValue('A' . $row, $servicio);
            $sheet->setCellValue('B' . $row, $cantidad);
            $row++;
        }

        // Distribución por estados
        $row += 2;
        $sheet->setCellValue('A' . $row, 'DISTRIBUCIÓN POR ESTADOS');
        $row++;
        $sheet->setCellValue('A' . $row, 'Estado');
        $sheet->setCellValue('B' . $row, 'Cantidad');
        $row++;

        foreach ($dashboardData['distribucion_estados'] as $estado => $cantidad) {
            $sheet->setCellValue('A' . $row, ucfirst($estado));
            $sheet->setCellValue('B' . $row, $cantidad);
            $row++;
        }

        // TOP ASESORES - Esta es la sección principal que faltaba
        $row += 2;
        $sheet->setCellValue('A' . $row, 'TOP ASESORES (CASOS ATENDIDOS)');
        $row++;
        $sheet->setCellValue('A' . $row, 'Asesor');
        $sheet->setCellValue('B' . $row, 'Casos Atendidos');
        $row++;

        foreach ($dashboardData['top_asesores'] as $asesor => $cantidad) {
            $sheet->setCellValue('A' . $row, $asesor);
            $sheet->setCellValue('B' . $row, $cantidad);
            $row++;
        }

        // Análisis por horas
        $row += 2;
        $sheet->setCellValue('A' . $row, 'ANÁLISIS POR HORAS');
        $row++;
        $sheet->setCellValue('A' . $row, 'Hora');
        $sheet->setCellValue('B' . $row, 'Cantidad de Turnos');
        $row++;

        foreach ($dashboardData['analisis_horas'] as $hora => $cantidad) {
            $sheet->setCellValue('A' . $row, $hora . ':00');
            $sheet->setCellValue('B' . $row, $cantidad);
            $row++;
        }

        // Aplicar estilos
        $this->aplicarEstilosDashboard($sheet);

        $filename = 'dashboard_historico_' . $fechaInicio->format('Y-m-d') . '_' . $fechaFin->format('Y-m-d') . '.xlsx';

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Exportar dashboard a PDF
     */
    private function exportarDashboardPDF($dashboardData, $fechaInicio, $fechaFin)
    {
        $data = [
            'dashboard_data' => $dashboardData,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'fecha_generacion' => Carbon::now()
        ];

        $pdf = Pdf::loadView('admin.reportes.dashboard-pdf', $data);
        $pdf->setPaper('A4', 'portrait');

        $filename = 'dashboard_historico_' . $fechaInicio->format('Y-m-d') . '_' . $fechaFin->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Calcular tiempo promedio de atención
     */
    private function calcularTiempoPromedioAtencion($turnos)
    {
        $turnosAtendidos = $turnos->where('estado', 'atendido')
            ->whereNotNull('duracion_atencion');

        if ($turnosAtendidos->count() === 0) {
            return 0;
        }

        // TurnoHistorial ya tiene duracion_atencion en segundos, convertir a minutos
        $tiempoPromedio = $turnosAtendidos->avg('duracion_atencion');

        return round($tiempoPromedio / 60, 1); // Convertir segundos a minutos
    }

    /**
     * Aplicar estilos al dashboard de Excel
     */
    private function aplicarEstilosDashboard($sheet)
    {
        // Estilo para el título
        $sheet->getStyle('A1:B1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1:B1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('064b9e');
        $sheet->getStyle('A1:B1')->getFont()->getColor()->setRGB('FFFFFF');

        // Autoajustar columnas
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);

        // Obtener la última fila con datos para aplicar bordes dinámicamente
        $lastRow = $sheet->getHighestRow();

        // Bordes para las celdas con datos
        $sheet->getStyle('A1:B' . $lastRow)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        // Aplicar estilo de encabezado a las secciones principales
        $this->aplicarEstilosSeccionesDashboard($sheet);
    }

    /**
     * Aplicar estilos específicos a las secciones del dashboard
     */
    private function aplicarEstilosSeccionesDashboard($sheet)
    {
        $lastRow = $sheet->getHighestRow();

        // Buscar y aplicar estilos a los encabezados de sección
        for ($row = 1; $row <= $lastRow; $row++) {
            $cellValue = $sheet->getCell('A' . $row)->getValue();

            // Si la celda contiene un encabezado de sección (texto en mayúsculas)
            if (is_string($cellValue) &&
                (strpos($cellValue, 'ESTADÍSTICAS') !== false ||
                 strpos($cellValue, 'DISTRIBUCIÓN') !== false ||
                 strpos($cellValue, 'TOP ASESORES') !== false ||
                 strpos($cellValue, 'ANÁLISIS') !== false)) {

                // Aplicar estilo de encabezado de sección
                $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E8F4FD']
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                        ],
                    ],
                ]);
            }

            // Aplicar estilo a los encabezados de columnas (Asesor, Casos Atendidos, etc.)
            if (is_string($cellValue) &&
                (strpos($cellValue, 'Asesor') !== false ||
                 strpos($cellValue, 'Servicio') !== false ||
                 strpos($cellValue, 'Estado') !== false ||
                 strpos($cellValue, 'Hora') !== false ||
                 strpos($cellValue, 'Cantidad') !== false ||
                 strpos($cellValue, 'Casos Atendidos') !== false)) {

                // Aplicar estilo de encabezado de columna
                $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F0F8FF']
                    ]
                ]);
            }
        }
    }

    /**
     * Crear hoja de resumen en Excel
     */
    private function crearHojaResumen($spreadsheet, $estadisticas, $fechaInicio, $fechaFin)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Resumen');

        // Encabezado
        $sheet->setCellValue('A1', 'REPORTE DE TURNOS - HOSPITAL UNIVERSITARIO DEL VALLE');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Período
        $sheet->setCellValue('A3', 'Período: ' . $fechaInicio->format('d/m/Y') . ' - ' . $fechaFin->format('d/m/Y'));
        $sheet->mergeCells('A3:F3');
        $sheet->getStyle('A3')->getFont()->setBold(true);

        // Fecha de generación
        $sheet->setCellValue('A4', 'Fecha de generación: ' . Carbon::now()->format('d/m/Y H:i:s'));
        $sheet->mergeCells('A4:F4');

        // Resumen general
        $row = 6;
        $sheet->setCellValue('A' . $row, 'RESUMEN GENERAL');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);

        $row += 2;
        $resumen = $estadisticas['resumen'];
        $sheet->setCellValue('A' . $row, 'Total de Turnos:');
        $sheet->setCellValue('B' . $row, $resumen['total_turnos']);

        $row++;
        $sheet->setCellValue('A' . $row, 'Turnos Atendidos:');
        $sheet->setCellValue('B' . $row, $resumen['turnos_atendidos']);

        $row++;
        $sheet->setCellValue('A' . $row, 'Turnos Pendientes:');
        $sheet->setCellValue('B' . $row, $resumen['turnos_pendientes']);

        $row++;
        $sheet->setCellValue('A' . $row, 'Turnos Cancelados:');
        $sheet->setCellValue('B' . $row, $resumen['turnos_cancelados']);

        $row++;
        $sheet->setCellValue('A' . $row, 'Porcentaje de Atención:');
        $sheet->setCellValue('B' . $row, $resumen['porcentaje_atencion'] . '%');

        $row++;
        $sheet->setCellValue('A' . $row, 'Tiempo Promedio de Atención:');
        $tiempoPromedio = $resumen['tiempo_promedio_atencion'] ? round($resumen['tiempo_promedio_atencion'] / 60, 2) . ' minutos' : 'N/A';
        $sheet->setCellValue('B' . $row, $tiempoPromedio);

        // Aplicar estilos
        $sheet->getStyle('A6:B' . $row)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // Ajustar ancho de columnas
        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(20);
    }

    /**
     * Crear hoja de detalle en Excel
     */
    private function crearHojaDetalle($spreadsheet, $turnos)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Detalle de Turnos');

        // Encabezados
        $headers = [
            'A1' => 'Código',
            'B1' => 'Número',
            'C1' => 'Servicio',
            'D1' => 'Asesor',
            'E1' => 'Caja',
            'F1' => 'Estado',
            'G1' => 'Prioridad',
            'H1' => 'Fecha Creación',
            'I1' => 'Fecha Llamado',
            'J1' => 'Fecha Atención',
            'K1' => 'Duración (min)'
        ];

        foreach ($headers as $cell => $header) {
            $sheet->setCellValue($cell, $header);
        }

        // Aplicar estilo a encabezados
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '064b9e']
            ],
            'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // Datos
        $row = 2;
        foreach ($turnos as $turno) {
            $sheet->setCellValue('A' . $row, $turno->codigo);
            $sheet->setCellValue('B' . $row, $turno->numero);
            $sheet->setCellValue('C' . $row, $turno->servicio->nombre ?? 'N/A');
            $sheet->setCellValue('D' . $row, $turno->asesor->nombre_usuario ?? 'N/A');
            $sheet->setCellValue('E' . $row, $turno->caja->nombre ?? 'N/A');
            $sheet->setCellValue('F' . $row, strtoupper($turno->estado));
            $sheet->setCellValue('G' . $row, strtoupper($turno->prioridad));
            $sheet->setCellValue('H' . $row, $turno->fecha_creacion ? Carbon::parse($turno->fecha_creacion)->format('d/m/Y H:i:s') : 'N/A');
            $sheet->setCellValue('I' . $row, $turno->fecha_llamado ? Carbon::parse($turno->fecha_llamado)->format('d/m/Y H:i:s') : 'N/A');
            $sheet->setCellValue('J' . $row, $turno->fecha_atencion ? Carbon::parse($turno->fecha_atencion)->format('d/m/Y H:i:s') : 'N/A');
            $sheet->setCellValue('K' . $row, $turno->duracion_atencion ? round($turno->duracion_atencion / 60, 2) : 'N/A');
            $row++;
        }

        // Aplicar bordes a todos los datos
        if ($row > 2) {
            $sheet->getStyle('A1:K' . ($row - 1))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ]);
        }

        // Ajustar ancho de columnas
        foreach (range('A', 'K') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * Crear hoja de estadísticas por servicio
     */
    private function crearHojaServicios($spreadsheet, $porServicio)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Por Servicio');

        // Encabezados
        $headers = [
            'A1' => 'Servicio',
            'B1' => 'Total',
            'C1' => 'Atendidos',
            'D1' => 'Pendientes',
            'E1' => 'Cancelados',
            'F1' => 'Tiempo Promedio (min)'
        ];

        foreach ($headers as $cell => $header) {
            $sheet->setCellValue($cell, $header);
        }

        // Aplicar estilo a encabezados
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '064b9e']
            ],
            'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // Datos
        $row = 2;
        foreach ($porServicio as $servicio => $datos) {
            $sheet->setCellValue('A' . $row, $servicio);
            $sheet->setCellValue('B' . $row, $datos['total']);
            $sheet->setCellValue('C' . $row, $datos['atendidos']);
            $sheet->setCellValue('D' . $row, $datos['pendientes']);
            $sheet->setCellValue('E' . $row, $datos['cancelados']);
            $sheet->setCellValue('F' . $row, $datos['tiempo_promedio'] ? round($datos['tiempo_promedio'] / 60, 2) : 'N/A');
            $row++;
        }

        // Aplicar bordes
        if ($row > 2) {
            $sheet->getStyle('A1:F' . ($row - 1))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ]);
        }

        // Ajustar ancho de columnas
        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * Crear hoja de estadísticas por asesor
     */
    private function crearHojaAsesores($spreadsheet, $porAsesor)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Por Asesor');

        // Encabezados
        $headers = [
            'A1' => 'Asesor',
            'B1' => 'Total Turnos',
            'C1' => 'Turnos Atendidos',
            'D1' => 'Tiempo Promedio (min)'
        ];

        foreach ($headers as $cell => $header) {
            $sheet->setCellValue($cell, $header);
        }

        // Aplicar estilo a encabezados
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '064b9e']
            ],
            'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // Datos
        $row = 2;
        foreach ($porAsesor as $asesor => $datos) {
            $sheet->setCellValue('A' . $row, $asesor);
            $sheet->setCellValue('B' . $row, $datos['total']);
            $sheet->setCellValue('C' . $row, $datos['atendidos']);
            $sheet->setCellValue('D' . $row, $datos['tiempo_promedio'] ? round($datos['tiempo_promedio'] / 60, 2) : 'N/A');
            $row++;
        }

        // Aplicar bordes
        if ($row > 2) {
            $sheet->getStyle('A1:D' . ($row - 1))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ]);
        }

        // Ajustar ancho de columnas
        foreach (range('A', 'D') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }
}
