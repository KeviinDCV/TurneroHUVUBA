<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Turno;
use App\Models\TurnoHistorial;
use App\Models\Servicio;
use App\Services\MetricasTurnos;
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
    public function index(Request $request)
    {
        $user = Auth::user();

        $asesores = User::where('rol', 'Asesor')->orderBy('nombre_completo')->get()
            ->map(fn ($u) => ['id' => (int) $u->id, 'nombre' => ($u->nombre_completo ?: $u->nombre_usuario) . ($u->estaDesactivada() ? ' (desactivada)' : ''),
                'usuario' => $u->nombre_usuario])->values();

        // Secciones con sus subservicios (también los inactivos: pueden tener turnos en el periodo).
        $servicios = Servicio::orderBy('orden')->orderBy('nombre')->get(['id', 'nombre', 'servicio_padre_id', 'estado']);
        $hijosDe = $servicios->whereNotNull('servicio_padre_id')->groupBy('servicio_padre_id');
        $fila = fn ($s) => ['id' => (int) $s->id, 'nombre' => $s->nombre, 'activo' => $s->estado === 'activo'];
        $secciones = $servicios->filter(fn ($s) => !$s->servicio_padre_id || !$servicios->contains('id', $s->servicio_padre_id))
            ->map(fn ($s) => $fila($s) + ['hijos' => $hijosDe->get($s->id, collect())->map($fila)->values()->all()])
            ->values();

        // Valores iniciales desde la URL (Gráficos enlaza aquí con su mismo periodo y servicio).
        $fecha = function ($v) {
            try { return $v ? Carbon::createFromFormat('!Y-m-d', (string) $v)->toDateString() : null; } catch (\Throwable $e) { return null; }
        };
        $inicial = [
            'desde' => $fecha($request->query('desde')),
            'hasta' => $fecha($request->query('hasta')),
            'servicio' => (int) $request->query('servicio') ?: null,
        ];

        return view('admin.reportes', compact('user', 'asesores', 'secciones', 'inicial'));
    }

    /**
     * Generar reporte general
     */
    public function generarReporte(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'alcance' => 'nullable|in:todo,servicios,asesores',
            'usuarios' => 'nullable|array',
            'usuarios.*' => 'integer',
            'servicios' => 'nullable|array',
            'servicios.*' => 'integer',
            'formato' => 'required|in:excel,pdf'
        ], [
            'fecha_fin.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
        ]);

        $fechaInicio = Carbon::parse($request->fecha_inicio)->startOfDay();
        $fechaFin = Carbon::parse($request->fecha_fin)->endOfDay();
        $usuarios = array_map('intval', $request->usuarios ?? []);
        $servicios = array_map('intval', $request->servicios ?? []);

        // Alcance explícito. Sin alcance (enlaces antiguos) se deduce de lo marcado; nada marcado = todo el turnero.
        $alcance = $request->alcance ?: (!empty($servicios) ? 'servicios' : (!empty($usuarios) ? 'asesores' : 'todo'));
        if ($alcance === 'servicios' && empty($servicios)) {
            return response()->json(['success' => false, 'message' => 'Elige al menos un servicio o cambia a «Todo el turnero».'], 422);
        }
        if ($alcance === 'asesores' && empty($usuarios)) {
            return response()->json(['success' => false, 'message' => 'Elige al menos un asesor o cambia a «Todo el turnero».'], 422);
        }

        // Una sección incluye sus subservicios (los turnos se registran en los subservicios).
        if ($alcance === 'servicios') {
            $servicios = array_values(array_unique(array_merge(
                $servicios, Servicio::whereIn('servicio_padre_id', $servicios)->pluck('id')->map(fn ($id) => (int) $id)->all()
            )));
        }

        // Periodo por la hora real de llegada (created_at): fecha_creacion se reescribe en cada cambio del turno.
        $query = Turno::with(['servicio', 'asesor', 'caja'])
            ->whereBetween('created_at', [$fechaInicio, $fechaFin]);

        if ($alcance === 'asesores') {
            $query->whereIn('asesor_id', $usuarios);
        } elseif ($alcance === 'servicios') {
            $query->whereIn('servicio_id', $servicios);
        }

        $turnos = $query->orderBy('created_at', 'desc')->get();

        // Generar estadísticas
        $estadisticas = $this->generarEstadisticas($turnos, $fechaInicio, $fechaFin, $alcance, $usuarios);

        // Lo que el archivo debe decir de sí mismo: unidad, alcance y quién lo generó.
        $contexto = [
            'unidad' => config('panel.unidad_nombre', 'Turnero'),
            'alcance' => $this->textoAlcance($alcance, $servicios, $usuarios),
            'alcance_clave' => $alcance,
            'generado_por' => Auth::user()?->nombre_completo ?: (Auth::user()?->nombre_usuario ?? ''),
        ];

        $respuesta = $request->formato === 'excel'
            ? $this->exportarExcel($turnos, $estadisticas, $fechaInicio, $fechaFin, $contexto)
            : $this->exportarPDF($turnos, $estadisticas, $fechaInicio, $fechaFin, $contexto);

        // Reportes descarga por URL (GET) en un iframe para que los gestores de descarga (Chrono, IDM…) puedan tomarla;
        // la página no ve el archivo, así que esta cookie le avisa que ya salió. Legible por JS y sin Secure: el sitio va por http.
        $aviso = (string) $request->query('aviso', '');
        if (preg_match('/^[a-z0-9]{8,40}$/i', $aviso)) {
            $respuesta->headers->setCookie(new \Symfony\Component\HttpFoundation\Cookie('informe_listo_' . $aviso, '1', time() + 120, '/', null, false, false, false, 'lax'));
        }

        return $respuesta;
    }

    /**
     * "Todo el turnero", "Servicios: A, B y 3 más" o "Asesores: …" para el encabezado del archivo.
     */
    private function textoAlcance(string $alcance, array $servicios, array $usuarios): string
    {
        $lista = function ($nombres) {
            $nombres = array_values(array_filter($nombres));
            return count($nombres) > 4 ? implode(', ', array_slice($nombres, 0, 4)) . ' y ' . (count($nombres) - 4) . ' más' : implode(', ', $nombres);
        };

        if ($alcance === 'servicios') {
            // Se nombran las secciones completas y los subservicios sueltos (no cada hijo de una sección elegida).
            $elegidos = Servicio::whereIn('id', $servicios)->get(['id', 'nombre', 'servicio_padre_id']);
            $nombres = $elegidos->filter(fn ($s) => !$s->servicio_padre_id || !$elegidos->contains('id', $s->servicio_padre_id))->pluck('nombre')->all();
            return 'Servicios: ' . $lista($nombres);
        }
        if ($alcance === 'asesores') {
            return 'Asesores: ' . $lista(User::whereIn('id', $usuarios)->orderBy('nombre_completo')->pluck('nombre_completo')->all());
        }

        return 'Todo el turnero';
    }

    /**
     * informe_turnos_<unidad>_<alcance>_<desde>_<hasta>.<ext>: se distinguen los archivos de cada turnero y alcance.
     */
    private function nombreArchivo(array $contexto, $fechaInicio, $fechaFin, string $extension): string
    {
        $unidad = \Illuminate\Support\Str::slug($contexto['unidad'] ?? 'turnero', '_');
        $alcance = ['servicios' => 'por_servicio', 'asesores' => 'por_asesor'][$contexto['alcance_clave'] ?? ''] ?? 'general';

        return 'informe_turnos_' . $unidad . '_' . $alcance . '_' . $fechaInicio->format('Y-m-d') . '_' . $fechaFin->format('Y-m-d') . '.' . $extension;
    }

    /**
     * Generar estadísticas del reporte
     */
    private function generarEstadisticas($turnos, $fechaInicio, $fechaFin, string $alcance = 'todo', array $usuarios = [])
    {
        // Las reglas de tiempo (duración real, atenciones que quedaron abiertas, tiempo entre turnos) están en MetricasTurnos.
        // Transferidos = SALIENTES ("Transferido a ..."). Los "Transferido desde ..." son el ticket de continuación creado en
        // el servicio destino, no una transferencia adicional.
        $esTransferido = fn ($turno) => $turno->observaciones && str_starts_with($turno->observaciones, 'Transferido a ');
        $conteos = fn ($grupo) => [
            'total' => $grupo->count(),
            'atendidos' => $grupo->where('estado', 'atendido')->count(),
            'en_espera' => $grupo->where('estado', 'pendiente')->count(),
            'aplazados' => $grupo->where('estado', 'aplazado')->count(),
            'pendientes' => $grupo->whereIn('estado', ['pendiente', 'aplazado'])->count(),
            // Llamados que nadie marcó como atendidos ni aplazados (en un periodo pasado, turnos que quedaron sin cerrar).
            'sin_cerrar' => $grupo->where('estado', 'llamado')->count(),
            'cancelados' => $grupo->where('estado', 'cancelado')->count(),
            'transferidos' => $grupo->filter($esTransferido)->count(),
        ];

        // Por servicio (por id: dos servicios pueden llamarse igual)
        $porServicio = $turnos->groupBy('servicio_id')->map(function ($grupo) use ($conteos) {
            $atencion = MetricasTurnos::atencion($grupo);

            return ['nombre' => $grupo->first()->servicio->nombre ?? 'Servicio eliminado'] + $conteos($grupo) + [
                'tiempo_promedio' => $atencion['promedio'],
                'tiempo_total' => $atencion['total'],
                'atipicas' => $atencion['atipicas'],
            ];
        })->sortByDesc('total');
        $porServicio = $porServicio->keyBy(function ($datos, $servicioId) use ($porServicio) {
            return $porServicio->where('nombre', $datos['nombre'])->count() > 1 ? $datos['nombre'] . ' (#' . $servicioId . ')' : $datos['nombre'];
        });

        // Por asesor
        $asesorIds = $turnos->pluck('asesor_id')->filter()->unique()->values();
        // Con alcance por servicios, $turnos trae solo esos servicios: el tiempo entre turnos se mide con TODAS las atenciones
        // del asesor en el periodo; si no, lo que atendió de otros servicios contaría como tiempo muerto.
        $atencionesDelAsesor = $alcance === 'servicios'
            ? Turno::whereIn('asesor_id', $asesorIds)->where('estado', 'atendido')->whereBetween('created_at', [$fechaInicio, $fechaFin])
                ->get(['asesor_id', 'estado', 'fecha_llamado', 'fecha_atencion', 'duracion_atencion'])->groupBy('asesor_id')
            : null;
        // Con alcance por asesores, los aplazamientos se buscan en todos los turnos del periodo: el turno aplazado casi
        // siempre lo termina atendiendo otro asesor, así que no está entre los turnos de los elegidos.
        $aplazamientos = $this->aplazamientosPorAsesor($alcance === 'asesores'
            ? Turno::whereBetween('created_at', [$fechaInicio, $fechaFin])->pluck('id')
            : $turnos->pluck('id'));
        $filaAsesor = function ($grupo, $asesorId) use ($conteos, $aplazamientos, $atencionesDelAsesor, $fechaInicio, $fechaFin) {
            $asesor = $grupo->first()?->asesor ?? User::find($asesorId);
            $atencion = MetricasTurnos::atencion($grupo);
            $canales = \App\Models\CanalNoPresencialHistorial::where('user_id', $asesorId)
                ->whereBetween('inicio', [$fechaInicio, $fechaFin])->get(['inicio', 'fin']);
            $entre = MetricasTurnos::entreTurnos($atencionesDelAsesor ? $atencionesDelAsesor->get($asesorId, collect()) : $grupo, $canales);

            return [
                'nombre_completo' => $asesor->nombre_completo ?? ('Usuario eliminado #' . $asesorId),
                'nombre_usuario' => $asesor->nombre_usuario ?? ('usuario' . $asesorId),
                // Veces que aplazó un turno (el estado actual del turno no lo dice: ver aplazamientosPorAsesor)
                'aplazados' => $aplazamientos[$asesorId] ?? 0,
            ] + $conteos($grupo) + [
                'tiempo_promedio_atencion' => $atencion['promedio'],
                'tiempo_total_atencion' => $atencion['total'],
                'atipicas' => $atencion['atipicas'],
                'entre_turnos_promedio' => $entre['promedio'],
                'entre_turnos_total' => $entre['total'],
                'entre_turnos_intervalos' => $entre['intervalos'],
                'pausas_largas' => $entre['pausas'],
                'pausas_largas_total' => $entre['pausas_total'],
                // Detalle de turnos atendidos (hoja propia del asesor en el Excel)
                'turnos_detalle' => $grupo->where('estado', 'atendido')->sortBy('fecha_atencion')->map(function ($turno) {
                    $segundos = MetricasTurnos::duracion($turno);

                    return [
                        'codigo' => $turno->codigo_completo,
                        'servicio' => $turno->servicio->nombre ?? 'N/A',
                        'fecha_llamado' => $turno->fecha_llamado ? Carbon::parse($turno->fecha_llamado)->format('d/m/Y H:i:s') : 'N/A',
                        'fecha_atencion' => $turno->fecha_atencion ? Carbon::parse($turno->fecha_atencion)->format('d/m/Y H:i:s') : 'N/A',
                        'fecha_finalizacion' => $turno->fecha_finalizacion ? Carbon::parse($turno->fecha_finalizacion)->format('d/m/Y H:i:s') : 'N/A',
                        'duracion_atencion' => MetricasTurnos::texto($segundos) . (MetricasTurnos::esAtipica($segundos) ? ' (quedó abierto, no se promedia)' : ''),
                        'caja' => $turno->caja->nombre ?? 'N/A',
                        'observaciones' => $turno->observaciones ?? '-'
                    ];
                })->values()->toArray()
            ];
        };
        $porAsesor = $turnos->whereNotNull('asesor_id')->groupBy('asesor_id')->map($filaAsesor);
        // Quien en el periodo solo trabajó en canales no presenciales (sin turnos) también va en la tabla.
        if ($alcance !== 'servicios') {
            \App\Models\CanalNoPresencialHistorial::whereBetween('inicio', [$fechaInicio, $fechaFin])
                ->when($alcance === 'asesores', fn ($q) => $q->whereIn('user_id', $usuarios))
                ->whereNotIn('user_id', $porAsesor->keys())
                ->distinct()->pluck('user_id')
                ->each(function ($asesorId) use (&$porAsesor, $filaAsesor) {
                    $porAsesor[$asesorId] = $filaAsesor(collect(), $asesorId);
                });
        }
        $porAsesor = $porAsesor->sortByDesc('atendidos');

        // Estadísticas por día
        $porDia = $turnos->groupBy(function ($turno) {
            return Carbon::parse($turno->created_at)->format('Y-m-d');
        })->map(function ($grupo) {
            return [
                'total' => $grupo->count(),
                'atendidos' => $grupo->where('estado', 'atendido')->count(),
                'pendientes' => $grupo->whereIn('estado', ['pendiente', 'aplazado'])->count()
            ];
        });

        $todos = $conteos($turnos);
        $atencion = MetricasTurnos::atencion($turnos);
        $intervalos = $porAsesor->sum('entre_turnos_intervalos');

        return [
            'resumen' => [
                'total_turnos' => $todos['total'],
                'turnos_atendidos' => $todos['atendidos'],
                'turnos_en_espera' => $todos['en_espera'],
                'turnos_aplazados' => $todos['aplazados'],
                'turnos_pendientes' => $todos['pendientes'],
                'turnos_sin_cerrar' => $todos['sin_cerrar'],
                'turnos_cancelados' => $todos['cancelados'],
                'turnos_transferidos' => $todos['transferidos'],
                'porcentaje_atencion' => $todos['total'] > 0 ? round(($todos['atendidos'] / $todos['total']) * 100, 2) : 0,
                'tiempo_promedio_atencion' => $atencion['promedio'],
                'tiempo_total_atencion' => $atencion['total'],
                'atenciones_atipicas' => $atencion['atipicas'],
                'atenciones_sin_dato' => $atencion['sin_dato'],
                'entre_turnos_promedio' => $intervalos ? $porAsesor->sum('entre_turnos_total') / $intervalos : null,
            ],
            'por_servicio' => $porServicio,
            'por_asesor' => $porAsesor,
            'por_dia' => $porDia
        ];
    }

    /**
     * Exportar a Excel
     */
    private function exportarExcel($turnos, $estadisticas, $fechaInicio, $fechaFin, array $contexto = [])
    {
        $spreadsheet = new Spreadsheet();

        // Hoja 1: Resumen
        $this->crearHojaResumen($spreadsheet, $estadisticas, $fechaInicio, $fechaFin, $contexto);

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

        $filename = $this->nombreArchivo($contexto, $fechaInicio, $fechaFin, 'xlsx');

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
    private function exportarPDF($turnos, $estadisticas, $fechaInicio, $fechaFin, array $contexto = [])
    {
        $data = [
            'contexto' => $contexto,
            'turnos' => $turnos,
            'estadisticas' => $estadisticas,
            'notas' => $this->notasTiempos(),
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'fecha_generacion' => Carbon::now()
        ];

        $pdf = Pdf::loadView('admin.reportes.pdf', $data);
        $pdf->setPaper('A4', 'portrait');

        $filename = $this->nombreArchivo($contexto, $fechaInicio, $fechaFin, 'pdf');

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
    private function crearHojaResumen($spreadsheet, $estadisticas, $fechaInicio, $fechaFin, array $contexto = [])
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Resumen');

        // Color institucional del Hospital Universitario del Valle
        $colorInstitucional = '064b9e';
        $colorInstitucionalClaro = 'e6f0ff';

        // Encabezado principal con estilo institucional
        $sheet->setCellValue('A1', 'INFORME DE TURNOS - ' . mb_strtoupper($contexto['unidad'] ?? '', 'UTF-8') . ' - HOSPITAL UNIVERSITARIO DEL VALLE');
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
        $sheet->setCellValue('B4', Carbon::now()->format('d/m/Y H:i:s') . (!empty($contexto['generado_por']) ? ' · ' . $contexto['generado_por'] : ''));
        $sheet->getStyle('A4')->getFont()->setBold(true);

        // Alcance: qué turnos entran en este informe
        $sheet->setCellValue('A5', 'Alcance:');
        $sheet->setCellValue('B5', $contexto['alcance'] ?? 'Todo el turnero');
        $sheet->getStyle('A5')->getFont()->setBold(true);

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
            ['Turnos en Espera (sin llamar)', $resumen['turnos_en_espera']],
            ['Turnos Aplazados', $resumen['turnos_aplazados']],
            ['Turnos Llamados sin Cerrar', $resumen['turnos_sin_cerrar']],
            ['Turnos Cancelados', $resumen['turnos_cancelados']],
            ['Turnos Transferidos', $resumen['turnos_transferidos'] ?? 0],
            ['Porcentaje de Atención', $resumen['porcentaje_atencion'] . '%'],
            ['Tiempo Promedio de Atención', MetricasTurnos::texto($resumen['tiempo_promedio_atencion'])],
            ['Tiempo Promedio entre Turnos', MetricasTurnos::texto($resumen['entre_turnos_promedio'])],
            ['Atenciones de más de 1 hora (no se promedian)', $resumen['atenciones_atipicas']],
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

        // Cómo se miden los tiempos: sin esto, quien lee el informe no puede saber qué entra en cada promedio.
        $row += 2;
        foreach ($this->notasTiempos() as $nota) {
            $sheet->setCellValue('A' . $row, $nota);
            $sheet->mergeCells('A' . $row . ':D' . $row);
            $sheet->getStyle('A' . $row)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getStyle('A' . $row)->getFont()->setSize(9)->setItalic(true)->getColor()->setRGB('555555');
            $sheet->getRowDimension($row)->setRowHeight(38);
            $row++;
        }

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
            'K1' => 'Duración de la Atención',
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
            // Solo los atendidos tienen una atención que medir (en un aplazado, duracion_atencion es lo que alcanzó a durar el llamado).
            $segundos = $turno->estado === 'atendido' ? MetricasTurnos::duracion($turno) : null;
            $duracionFormato = MetricasTurnos::texto($segundos) . (MetricasTurnos::esAtipica($segundos) ? ' (quedó abierto, no se promedia)' : '');

            $sheet->setCellValue('A' . $row, $turno->codigo);
            $sheet->setCellValue('B' . $row, $turno->numero);
            $sheet->setCellValue('C' . $row, $turno->servicio->nombre ?? 'N/A');
            $sheet->setCellValue('D' . $row, $turno->asesor->nombre_completo ?? ($turno->asesor->nombre_usuario ?? 'N/A'));
            $sheet->setCellValue('E' . $row, $turno->caja->nombre ?? 'N/A');
            $sheet->setCellValue('F' . $row, strtoupper($turno->estado));
            $sheet->setCellValue('G' . $row, $turno->prioridad >= 4 ? 'Prioritario' : 'Normal');
            $sheet->setCellValue('H' . $row, $turno->created_at ? Carbon::parse($turno->created_at)->format('d/m/Y H:i:s') : 'N/A');
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
            'D1' => 'Pendientes (en espera + aplazados)',
            'E1' => 'Llamados sin Cerrar',
            'F1' => 'Cancelados',
            'G1' => 'Transferidos',
            'H1' => 'Tiempo Promedio de Atención',
            'I1' => 'Atenciones de más de 1 h'
        ];

        foreach ($headers as $cell => $header) {
            $sheet->setCellValue($cell, $header);
        }

        // Aplicar estilo a encabezados
        $sheet->getStyle('A1:I1')->applyFromArray([
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
            $sheet->setCellValue('E' . $row, $datos['sin_cerrar']);
            $sheet->setCellValue('F' . $row, $datos['cancelados']);
            $sheet->setCellValue('G' . $row, $datos['transferidos'] ?? 0);
            $sheet->setCellValue('H' . $row, MetricasTurnos::texto($datos['tiempo_promedio']));
            $sheet->setCellValue('I' . $row, $datos['atipicas']);
            $row++;
        }

        // Aplicar bordes
        if ($row > 2) {
            $sheet->getStyle('A1:I' . ($row - 1))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ]);
        }

        // Ajustar ancho de columnas
        foreach (range('A', 'I') as $column) {
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
        $sheet->setCellValue('E1', 'Veces que Aplazó un Turno');
        $sheet->setCellValue('F1', 'Llamados sin Cerrar');
        $sheet->setCellValue('G1', 'Transferidos');
        $sheet->setCellValue('H1', 'Tiempo Promedio de Atención');
        $sheet->setCellValue('I1', 'Tiempo Total de Atención');
        $sheet->setCellValue('J1', 'Atenciones de más de 1 h (no se promedian)');
        $sheet->setCellValue('K1', 'Tiempo Promedio entre Turnos');
        $sheet->setCellValue('L1', 'Tiempo Total entre Turnos');
        $sheet->setCellValue('M1', 'Pausas de más de 1 h');
        $sheet->setCellValue('N1', 'Tiempo en Pausas de más de 1 h');

        // Aplicar estilo a encabezados
        $sheet->getStyle('A1:N1')->applyFromArray([
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
            $sheet->setCellValue('A' . $row, $datos['nombre_completo']);
            $sheet->setCellValue('B' . $row, $datos['nombre_usuario']);
            $sheet->setCellValue('C' . $row, $datos['total']);
            $sheet->setCellValue('D' . $row, $datos['atendidos']);
            $sheet->setCellValue('E' . $row, $datos['aplazados']);
            $sheet->setCellValue('F' . $row, $datos['sin_cerrar']);
            $sheet->setCellValue('G' . $row, $datos['transferidos'] ?? 0);
            $sheet->setCellValue('H' . $row, MetricasTurnos::texto($datos['tiempo_promedio_atencion']));
            $sheet->setCellValue('I' . $row, MetricasTurnos::texto($datos['tiempo_total_atencion']));
            $sheet->setCellValue('J' . $row, $datos['atipicas']);
            $sheet->setCellValue('K' . $row, MetricasTurnos::texto($datos['entre_turnos_promedio']));
            $sheet->setCellValue('L' . $row, MetricasTurnos::texto($datos['entre_turnos_total']));
            $sheet->setCellValue('M' . $row, $datos['pausas_largas']);
            $sheet->setCellValue('N' . $row, MetricasTurnos::texto($datos['pausas_largas_total']));

            // Alternar colores de fondo
            $bgColor = $row % 2 === 0 ? 'e6f0ff' : 'FFFFFF';
            $sheet->getStyle('A' . $row . ':N' . $row)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);

            $row++;
        }

        // Ajustar ancho de columnas
        foreach (range('A', 'N') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Cómo se miden los tiempos (celdas combinadas: así no ensanchan la columna A al autoajustar)
        $row++;
        foreach ($this->notasTiempos() as $nota) {
            $sheet->setCellValue('A' . $row, $nota);
            $sheet->mergeCells('A' . $row . ':N' . $row);
            $sheet->getStyle('A' . $row)->getFont()->setSize(9)->setItalic(true)->getColor()->setRGB('555555');
            $row++;
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
        $sheet->setCellValue('E1', 'Duración de la Atención');
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

    /**
     * Cuántas veces aplazó cada asesor un turno de los del informe. Se cuenta en el historial porque en UBA aplazar borra
     * el asesor del turno, y en los dos el turno lo puede terminar atendiendo otro: el historial guarda quién lo tenía.
     */
    private function aplazamientosPorAsesor($idsTurnos): array
    {
        $porAsesor = [];
        foreach (collect($idsTurnos)->chunk(1000) as $ids) {
            TurnoHistorial::whereIn('turno_original_id', $ids->all())
                ->where('tipo_backup', 'actualizacion')
                ->where('estado', 'aplazado')
                ->get(['datos_adicionales'])
                ->each(function ($fila) use (&$porAsesor) {
                    $antes = $fila->datos_adicionales['valores_anteriores'] ?? [];
                    // Solo el paso de «llamado» a «aplazado»: otras actualizaciones de un turno ya aplazado no son otro aplazamiento.
                    if (($antes['estado'] ?? null) === 'llamado' && !empty($antes['asesor_id'])) {
                        $porAsesor[$antes['asesor_id']] = ($porAsesor[$antes['asesor_id']] ?? 0) + 1;
                    }
                });
        }

        return $porAsesor;
    }

    /**
     * Cómo se miden los tiempos del informe: va en el Excel y en el PDF.
     */
    private function notasTiempos(): array
    {
        return [
            'Duración de la atención: la del cronómetro del asesor. Si el cronómetro se reinició (la página se recargó) o el reloj del equipo cambió, se usa la hora del servidor (del llamado a «Atendido»).',
            'Atenciones de más de 1 hora: el turno quedó abierto. Cuentan como atendidas pero no entran en los promedios ni en los totales de tiempo.',
            'Tiempo entre turnos: desde que el asesor cierra un turno hasta que empieza a atender el siguiente, el mismo día. No cuenta noches ni fines de semana; los ratos de más de 1 hora (almuerzo, reuniones) se muestran aparte como pausas; el tiempo en canales no presenciales se descuenta. Incluye ratos en que no había pacientes esperando. En un informe por servicios se mide con todas las atenciones del asesor, de cualquier servicio.',
            'Transferencias: un paciente transferido cuenta como dos turnos, el del servicio que lo transfirió (atendido) y el del servicio que lo recibe.',
        ];
    }
}
