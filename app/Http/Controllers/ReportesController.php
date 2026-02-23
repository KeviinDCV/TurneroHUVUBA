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

        // VALIDACIÓN: Debe haber al menos usuarios O servicios seleccionados
        if (empty($usuarios) && empty($servicios)) {
            return response()->json([
                'success' => false,
                'message' => 'Debe seleccionar al menos un usuario o un servicio para generar el reporte'
            ], 422);
        }

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
        
        // Contar turnos transferidos (tienen observación que empieza con "Transferido")
        $turnosTransferidos = $turnos->filter(function($turno) {
            return $turno->observaciones && str_starts_with($turno->observaciones, 'Transferido');
        })->count();

        // Estadísticas por servicio
        $porServicio = $turnos->groupBy('servicio.nombre')->map(function ($grupo) {
            return [
                'total' => $grupo->count(),
                'atendidos' => $grupo->where('estado', 'atendido')->count(),
                'pendientes' => $grupo->whereIn('estado', ['pendiente', 'aplazado'])->count(),
                'cancelados' => $grupo->where('estado', 'cancelado')->count(),
                'tiempo_promedio' => round($grupo->where('estado', 'atendido')->avg('duracion_atencion'), 2),
                'tiempo_total' => round($grupo->where('estado', 'atendido')->sum('duracion_atencion'), 2)
            ];
        })->sortByDesc('total');

        // Estadísticas DETALLADAS por asesor
        $porAsesor = $turnos->whereNotNull('asesor_id')->groupBy('asesor_id')->map(function ($grupo) use ($fechaInicio, $fechaFin) {
            $asesor = $grupo->first()->asesor;
            $turnosAtendidos = $grupo->where('estado', 'atendido');
            
            // Contar turnos transferidos (solo los salientes)
            $turnosTransferidos = $grupo->filter(function($turno) {
                return $turno->observaciones && str_contains($turno->observaciones, 'Transferido a ');
            })->count();
            
            // Calcular tiempo entre turnos
            $turnosOrdenados = $grupo->where('estado', 'atendido')
                ->sortBy('fecha_atencion')
                ->values();
            
            $tiemposEntreTurnos = [];
            for ($i = 1; $i < $turnosOrdenados->count(); $i++) {
                $anterior = $turnosOrdenados[$i - 1];
                $actual = $turnosOrdenados[$i];
                
                if ($anterior->fecha_finalizacion && $actual->fecha_llamado) {
                    $minutos = Carbon::parse($anterior->fecha_finalizacion)
                        ->diffInMinutes(Carbon::parse($actual->fecha_llamado));
                    $tiemposEntreTurnos[] = $minutos;
                }
            }
            
            $tiempoPromedioEntreTurnos = count($tiemposEntreTurnos) > 0 
                ? round(array_sum($tiemposEntreTurnos) / count($tiemposEntreTurnos), 2) 
                : 0;

            return [
                'nombre_completo' => $asesor->nombre_completo,
                'nombre_usuario' => $asesor->nombre_usuario,
                'total' => $grupo->count(),
                'atendidos' => $turnosAtendidos->count(),
                'pendientes' => $grupo->whereIn('estado', ['pendiente', 'aplazado'])->count(),
                'aplazados' => $grupo->where('estado', 'aplazado')->count(),
                'transferidos' => $turnosTransferidos,
                'tiempo_promedio_atencion' => round($turnosAtendidos->avg('duracion_atencion'), 2),
                'tiempo_total_atencion' => round($turnosAtendidos->sum('duracion_atencion'), 2),
                'tiempo_promedio_entre_turnos' => $tiempoPromedioEntreTurnos,
                // Detalle de turnos
                'turnos_detalle' => $turnosAtendidos->map(function($turno) {
                    // Convertir duración de segundos a formato mm:ss
                    $duracionSegundos = abs($turno->duracion_atencion ?? 0);
                    $minutos = floor($duracionSegundos / 60);
                    $segundos = $duracionSegundos % 60;
                    $duracionFormato = sprintf('%02d:%02d', $minutos, $segundos);
                    
                    return [
                        'codigo' => $turno->codigo_completo,
                        'servicio' => $turno->servicio->nombre ?? 'N/A',
                        'fecha_llamado' => $turno->fecha_llamado ? Carbon::parse($turno->fecha_llamado)->format('d/m/Y H:i:s') : 'N/A',
                        'fecha_atencion' => $turno->fecha_atencion ? Carbon::parse($turno->fecha_atencion)->format('d/m/Y H:i:s') : 'N/A',
                        'fecha_finalizacion' => $turno->fecha_finalizacion ? Carbon::parse($turno->fecha_finalizacion)->format('d/m/Y H:i:s') : 'N/A',
                        'duracion_atencion' => $duracionFormato,
                        'caja' => $turno->caja->nombre ?? 'N/A',
                        'observaciones' => $turno->observaciones ?? '-'
                    ];
                })->toArray()
            ];
        })->sortByDesc('atendidos');

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
                'turnos_transferidos' => $turnosTransferidos,
                'porcentaje_atencion' => $totalTurnos > 0 ? round(($turnosAtendidos / $totalTurnos) * 100, 2) : 0,
                'tiempo_promedio_atencion' => round($turnos->where('estado', 'atendido')->avg('duracion_atencion'), 2),
                'tiempo_total_atencion' => round($turnos->where('estado', 'atendido')->sum('duracion_atencion'), 2)
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

        // Hojas 5+: Detalle de turnos por cada asesor
        foreach ($estadisticas['por_asesor'] as $asesorId => $datosAsesor) {
            // Hoja de turnos detallados
            $this->crearHojaDetalleTurnosAsesor($spreadsheet, $datosAsesor);
        }

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

        // Color institucional del Hospital Universitario del Valle
        $colorInstitucional = '064b9e';
        $colorInstitucionalClaro = 'e6f0ff';

        // Encabezado principal con estilo institucional
        $sheet->setCellValue('A1', 'REPORTE DE TURNOS - HOSPITAL UNIVERSITARIO DEL VALLE');
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $colorInstitucional]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(35);

        // Período
        $sheet->setCellValue('A3', 'Período:');
        $sheet->setCellValue('B3', $fechaInicio->format('d/m/Y') . ' - ' . $fechaFin->format('d/m/Y'));
        $sheet->getStyle('A3')->getFont()->setBold(true);
        $sheet->getStyle('B3')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($colorInstitucional));

        // Fecha de generación
        $sheet->setCellValue('A4', 'Fecha de generación:');
        $sheet->setCellValue('B4', Carbon::now()->format('d/m/Y H:i:s'));
        $sheet->getStyle('A4')->getFont()->setBold(true);

        // Resumen general - Título
        $row = 6;
        $sheet->setCellValue('A' . $row, 'RESUMEN GENERAL');
        $sheet->mergeCells('A' . $row . ':B' . $row);
        $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $colorInstitucional]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(25);

        // Encabezados de tabla
        $row++;
        $sheet->setCellValue('A' . $row, 'Concepto');
        $sheet->setCellValue('B' . $row, 'Valor');
        $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $colorInstitucional]],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // Datos del resumen
        $resumen = $estadisticas['resumen'];
        $datos = [
            ['Total de Turnos', $resumen['total_turnos']],
            ['Turnos Atendidos', $resumen['turnos_atendidos']],
            ['Turnos Pendientes', $resumen['turnos_pendientes']],
            ['Turnos Cancelados', $resumen['turnos_cancelados']],
            ['Turnos Transferidos', $resumen['turnos_transferidos'] ?? 0],
            ['Porcentaje de Atención', $resumen['porcentaje_atencion'] . '%'],
            ['Tiempo Promedio de Atención', $resumen['tiempo_promedio_atencion'] ? round($resumen['tiempo_promedio_atencion'] / 60, 2) . ' minutos' : 'N/A'],
        ];

        $startRow = $row + 1;
        foreach ($datos as $index => $dato) {
            $row++;
            $sheet->setCellValue('A' . $row, $dato[0]);
            $sheet->setCellValue('B' . $row, $dato[1]);
            
            // Alternar colores de fondo
            $bgColor = $index % 2 === 0 ? $colorInstitucionalClaro : 'FFFFFF';
            $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
        }

        // Resaltar primera fila de datos (Total de Turnos)
        $sheet->getStyle('A' . $startRow . ':B' . $startRow)->applyFromArray([
            'font' => ['bold' => true],
        ]);

        // Ajustar ancho de columnas
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(20);
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
            'I1' => 'Hora Llamado',
            'J1' => 'Hora Finalización',
            'K1' => 'Duración (mm:ss)',
            'L1' => 'Observaciones/Transferencia'
        ];

        foreach ($headers as $cell => $header) {
            $sheet->setCellValue($cell, $header);
        }

        // Aplicar estilo a encabezados
        $sheet->getStyle('A1:L1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '064b9e']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // Datos
        $row = 2;
        foreach ($turnos as $turno) {
            // Formato de duración mm:ss
            $duracionSegundos = abs($turno->duracion_atencion ?? 0);
            $minutos = floor($duracionSegundos / 60);
            $segundos = $duracionSegundos % 60;
            $duracionFormato = $duracionSegundos ? sprintf('%02d:%02d', $minutos, $segundos) : 'N/A';
            
            $sheet->setCellValue('A' . $row, $turno->codigo);
            $sheet->setCellValue('B' . $row, $turno->numero);
            $sheet->setCellValue('C' . $row, $turno->servicio->nombre ?? 'N/A');
            $sheet->setCellValue('D' . $row, $turno->asesor->nombre_usuario ?? 'N/A');
            $sheet->setCellValue('E' . $row, $turno->caja->nombre ?? 'N/A');
            $sheet->setCellValue('F' . $row, strtoupper($turno->estado));
            $sheet->setCellValue('G' . $row, strtoupper($turno->prioridad));
            $sheet->setCellValue('H' . $row, $turno->fecha_creacion ? Carbon::parse($turno->fecha_creacion)->format('d/m/Y H:i:s') : 'N/A');
            $sheet->setCellValue('I' . $row, $turno->fecha_llamado ? Carbon::parse($turno->fecha_llamado)->format('d/m/Y H:i:s') : 'N/A');
            $sheet->setCellValue('J' . $row, $turno->fecha_finalizacion ? Carbon::parse($turno->fecha_finalizacion)->format('d/m/Y H:i:s') : 'N/A');
            $sheet->setCellValue('K' . $row, $duracionFormato);
            $sheet->setCellValue('L' . $row, $turno->observaciones ?? '-');
            $row++;
        }

        // Aplicar bordes a todos los datos
        if ($row > 2) {
            $sheet->getStyle('A1:L' . ($row - 1))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ]);
        }

        // Ajustar ancho de columnas
        foreach (range('A', 'L') as $column) {
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
            'F1' => 'Tiempo Promedio (mm:ss)'
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
            // Formatear promedio mm:ss
            $promedioSegundos = abs($datos['tiempo_promedio'] ?? 0);
            $minPromedio = floor($promedioSegundos / 60);
            $segPromedio = $promedioSegundos % 60;
            $tiempoPromedioFormato = sprintf('%02d:%02d', $minPromedio, $segPromedio);

            $sheet->setCellValue('A' . $row, $servicio);
            $sheet->setCellValue('B' . $row, $datos['total']);
            $sheet->setCellValue('C' . $row, $datos['atendidos']);
            $sheet->setCellValue('D' . $row, $datos['pendientes']);
            $sheet->setCellValue('E' . $row, $datos['cancelados']);
            $sheet->setCellValue('F' . $row, $tiempoPromedioFormato);
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
        $sheet->setCellValue('A1', 'Asesor');
        $sheet->setCellValue('B1', 'Usuario');
        $sheet->setCellValue('C1', 'Total Turnos');
        $sheet->setCellValue('D1', 'Atendidos');
        $sheet->setCellValue('E1', 'Aplazados');
        $sheet->setCellValue('F1', 'Transferidos');
        $sheet->setCellValue('G1', 'Tiempo Prom. (mm:ss)');
        $sheet->setCellValue('H1', 'Tiempo Total (mm:ss)');
        $sheet->setCellValue('I1', 'Entre Turnos (min)');

        // Aplicar estilo a encabezados
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '064b9e']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(20);

        // Datos
        $row = 2;
        foreach ($porAsesor as $asesorId => $datos) {
            // Formato de tiempos mm:ss
            // Formatear promedio
            $promedioSegundos = abs($datos['tiempo_promedio_atencion']);
            $minPromedio = floor($promedioSegundos / 60);
            $segPromedio = $promedioSegundos % 60;
            $tiempoPromedioFormato = sprintf('%02d:%02d', $minPromedio, $segPromedio);

            // Formatear total
            $totalSegundos = abs($datos['tiempo_total_atencion']);
            $minTotal = floor($totalSegundos / 60);
            $segTotal = $totalSegundos % 60;
            $tiempoTotalFormato = sprintf('%02d:%02d', $minTotal, $segTotal);
            
            $sheet->setCellValue('A' . $row, $datos['nombre_completo']);
            $sheet->setCellValue('B' . $row, $datos['nombre_usuario']);
            $sheet->setCellValue('C' . $row, $datos['total']);
            $sheet->setCellValue('D' . $row, $datos['atendidos']);
            $sheet->setCellValue('E' . $row, $datos['aplazados']);
            $sheet->setCellValue('F' . $row, $datos['transferidos'] ?? 0);
            $sheet->setCellValue('G' . $row, $tiempoPromedioFormato);
            $sheet->setCellValue('H' . $row, $tiempoTotalFormato);
            $sheet->setCellValue('I' . $row, round($datos['tiempo_promedio_entre_turnos'], 2));
            
            // Alternar colores de fondo
            $bgColor = $row % 2 === 0 ? 'e6f0ff' : 'FFFFFF';
            $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
            
            $row++;
        }

        // Ajustar ancho de columnas
        foreach (range('A', 'I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * Crear hoja de detalle de turnos por asesor
     */
    private function crearHojaDetalleTurnosAsesor($spreadsheet, $datosAsesor)
    {
        $sheet = $spreadsheet->createSheet();
        $nombreHoja = substr('Turnos_' . $datosAsesor['nombre_usuario'], 0, 31);
        $sheet->setTitle($nombreHoja);

        // Encabezados (sin Hora Atención porque es redundante con Hora Finalización)
        $sheet->setCellValue('A1', 'Código');
        $sheet->setCellValue('B1', 'Servicio');
        $sheet->setCellValue('C1', 'Hora Llamado');
        $sheet->setCellValue('D1', 'Hora Finalización');
        $sheet->setCellValue('E1', 'Duración (mm:ss)');
        $sheet->setCellValue('F1', 'Caja');
        $sheet->setCellValue('G1', 'Observaciones');

        // Estilo encabezado
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '064b9e']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        // Datos
        $row = 2;
        foreach ($datosAsesor['turnos_detalle'] as $turno) {
            $sheet->setCellValue('A' . $row, $turno['codigo']);
            $sheet->setCellValue('B' . $row, $turno['servicio']);
            $sheet->setCellValue('C' . $row, $turno['fecha_llamado']);
            $sheet->setCellValue('D' . $row, $turno['fecha_finalizacion']);
            $sheet->setCellValue('E' . $row, $turno['duracion_atencion']);
            $sheet->setCellValue('F' . $row, $turno['caja']);
            $sheet->setCellValue('G' . $row, $turno['observaciones'] ?? '-');
            $row++;
        }

        // Ajustar columnas
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}
