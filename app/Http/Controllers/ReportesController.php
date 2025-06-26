<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Turno;
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
