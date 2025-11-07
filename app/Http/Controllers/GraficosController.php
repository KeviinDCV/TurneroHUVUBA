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

class GraficosController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Middleware adicional para analytics históricos (solo APIs)
        $this->middleware(function ($request, $next) {
            if ($request->is('api/graficos/historial/*')) {
                $user = Auth::user();
                if (!$user || !$user->esAdministrador()) {
                    return response()->json(['error' => 'Acceso denegado. Se requieren permisos de administrador.'], 403);
                }
            }
            return $next($request);
        });
    }

    /**
     * Mostrar la vista principal de gráficos (unificada con analytics históricos)
     */
    public function index()
    {
        $user = Auth::user();
        return view('admin.graficos', compact('user'));
    }

    /**
     * API: Turnos por servicio (rango de fechas)
     */
    public function turnosPorServicioSemana(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio', Carbon::now()->subDays(7)->format('Y-m-d'));
        $fechaFin = $request->get('fecha_fin', Carbon::now()->format('Y-m-d'));

        $datos = Turno::select('servicios.nombre', DB::raw('COUNT(*) as total'))
            ->join('servicios', 'turnos.servicio_id', '=', 'servicios.id')
            ->whereBetween('turnos.fecha_creacion', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay()
            ])
            ->groupBy('servicios.id', 'servicios.nombre')
            ->orderBy('total', 'desc')
            ->get();

        return response()->json([
            'labels' => $datos->pluck('nombre'),
            'data' => $datos->pluck('total')
        ]);
    }

    /**
     * API: Turnos por estado (fecha específica)
     */
    public function turnosPorEstado(Request $request)
    {
        $fecha = $request->get('fecha', Carbon::today()->format('Y-m-d'));

        $datos = Turno::select('estado', DB::raw('COUNT(*) as total'))
            ->whereDate('fecha_creacion', Carbon::parse($fecha))
            ->groupBy('estado')
            ->get();

        return response()->json([
            'labels' => $datos->pluck('estado'),
            'data' => $datos->pluck('total')
        ]);
    }

    /**
     * API: Turnos por hora del día (fecha específica)
     */
    public function turnosPorHora(Request $request)
    {
        $fecha = $request->get('fecha', Carbon::today()->format('Y-m-d'));

        $datos = Turno::select(DB::raw('HOUR(fecha_creacion) as hora'), DB::raw('COUNT(*) as total'))
            ->whereDate('fecha_creacion', Carbon::parse($fecha))
            ->groupBy('hora')
            ->orderBy('hora')
            ->get();

        // Crear array con todas las horas (0-23)
        $horas = [];
        $totales = [];
        for ($i = 0; $i < 24; $i++) {
            $horas[] = $i . ':00';
            $totales[] = $datos->where('hora', $i)->first()->total ?? 0;
        }

        return response()->json([
            'labels' => $horas,
            'data' => $totales
        ]);
    }

    /**
     * API: Rendimiento de asesores (rango de fechas)
     */
    public function rendimientoAsesores(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio', Carbon::now()->subDays(7)->format('Y-m-d'));
        $fechaFin = $request->get('fecha_fin', Carbon::now()->format('Y-m-d'));

        $datos = Turno::select('users.nombre_usuario', DB::raw('COUNT(*) as total'))
            ->join('users', 'turnos.asesor_id', '=', 'users.id')
            ->where('turnos.estado', 'atendido')
            ->whereBetween('turnos.fecha_creacion', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay()
            ])
            ->whereNotNull('turnos.asesor_id')
            ->groupBy('users.id', 'users.nombre_usuario')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'labels' => $datos->pluck('nombre_usuario'),
            'data' => $datos->pluck('total')
        ]);
    }

    /**
     * API: Turnos por día (rango de fechas)
     */
    public function turnosPorDia(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio', Carbon::now()->subDays(30)->format('Y-m-d'));
        $fechaFin = $request->get('fecha_fin', Carbon::now()->format('Y-m-d'));

        $datos = Turno::select(DB::raw('DATE(fecha_creacion) as fecha'), DB::raw('COUNT(*) as total'))
            ->whereBetween('fecha_creacion', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay()
            ])
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        return response()->json([
            'labels' => $datos->pluck('fecha'),
            'data' => $datos->pluck('total')
        ]);
    }

    /**
     * API: Tiempo promedio de atención por servicio (rango de fechas)
     */
    public function tiempoAtencionPorServicio(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio', Carbon::now()->subDays(7)->format('Y-m-d'));
        $fechaFin = $request->get('fecha_fin', Carbon::now()->format('Y-m-d'));

        $datos = Turno::select('servicios.nombre', DB::raw('AVG(duracion_atencion) as promedio'))
            ->join('servicios', 'turnos.servicio_id', '=', 'servicios.id')
            ->where('turnos.estado', 'atendido')
            ->whereNotNull('turnos.duracion_atencion')
            ->whereBetween('turnos.fecha_creacion', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay()
            ])
            ->groupBy('servicios.id', 'servicios.nombre')
            ->orderBy('promedio', 'desc')
            ->get();

        // Convertir segundos a minutos
        $promedios = $datos->pluck('promedio')->map(function($segundos) {
            return round($segundos / 60, 2);
        });

        return response()->json([
            'labels' => $datos->pluck('nombre'),
            'data' => $promedios
        ]);
    }

    /**
     * API: Distribución de prioridades (fecha específica)
     */
    public function distribucionPrioridades(Request $request)
    {
        $fecha = $request->get('fecha', Carbon::today()->format('Y-m-d'));

        $datos = Turno::select('prioridad', DB::raw('COUNT(*) as total'))
            ->whereDate('fecha_creacion', Carbon::parse($fecha))
            ->groupBy('prioridad')
            ->get();

        return response()->json([
            'labels' => $datos->pluck('prioridad'),
            'data' => $datos->pluck('total')
        ]);
    }

    /**
     * API: Estadísticas generales (fecha específica)
     */
    public function estadisticasGenerales(Request $request)
    {
        $fecha = $request->get('fecha', Carbon::today()->format('Y-m-d'));
        $fechaParsed = Carbon::parse($fecha);

        $stats = [
            'turnos_hoy' => Turno::whereDate('fecha_creacion', $fechaParsed)->count(),
            'turnos_atendidos_hoy' => Turno::where('estado', 'atendido')->whereDate('fecha_creacion', $fechaParsed)->count(),
            'turnos_pendientes' => Turno::whereIn('estado', ['pendiente', 'aplazado'])->whereDate('fecha_creacion', $fechaParsed)->count(),
            'asesores_activos' => User::where('rol', 'Asesor')->whereNotNull('last_activity')->where('last_activity', '>=', Carbon::now()->subMinutes(15))->count(),
            'tiempo_promedio_atencion' => Turno::where('estado', 'atendido')->whereDate('fecha_creacion', $fechaParsed)->whereNotNull('duracion_atencion')->avg('duracion_atencion'),
            'servicios_activos' => Servicio::where('estado', 'activo')->count()
        ];

        // Convertir tiempo promedio a minutos
        if ($stats['tiempo_promedio_atencion']) {
            $stats['tiempo_promedio_atencion'] = round($stats['tiempo_promedio_atencion'] / 60, 2);
        }

        return response()->json($stats);
    }

    // ========================================
    // MÉTODOS PARA ANALYTICS HISTÓRICOS
    // ========================================

    /**
     * API: Volumen de turnos históricos por tiempo
     */
    public function historialVolumenPorTiempo(Request $request)
    {
        try {
            // Validar parámetros
            $request->validate([
                'fecha_inicio' => 'nullable|date',
                'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
                'periodo' => 'nullable|in:hourly,daily,weekly,monthly'
            ]);

            $fechaInicio = $request->get('fecha_inicio', Carbon::now()->subDays(30)->format('Y-m-d'));
            $fechaFin = $request->get('fecha_fin', Carbon::now()->format('Y-m-d'));
            $periodo = $request->get('periodo', 'daily');

            // Validar rango de fechas (máximo 2 años)
            $inicio = Carbon::parse($fechaInicio);
            $fin = Carbon::parse($fechaFin);

            if ($fin->diffInDays($inicio) > 730) {
                return response()->json([
                    'error' => 'El rango de fechas no puede exceder 2 años'
                ], 400);
            }

            $datos = TurnoHistorial::obtenerVolumenPorTiempo(
                $inicio->startOfDay(),
                $fin->endOfDay(),
                $periodo
            );

        // Formatear datos según el período
        switch ($periodo) {
            case 'hourly':
                $labels = $datos->map(function($item) {
                    return $item->fecha . ' ' . str_pad($item->hora, 2, '0', STR_PAD_LEFT) . ':00';
                });
                break;
            case 'daily':
                $labels = $datos->pluck('fecha');
                break;
            case 'weekly':
                $labels = $datos->map(function($item) {
                    return 'Semana ' . substr($item->semana, -2);
                });
                break;
            case 'monthly':
                $labels = $datos->map(function($item) {
                    return $item->año . '-' . str_pad($item->mes, 2, '0', STR_PAD_LEFT);
                });
                break;
            default:
                $labels = $datos->pluck('fecha');
        }

            return response()->json([
                'labels' => $labels,
                'data' => $datos->pluck('total')
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en historialVolumenPorTiempo: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_params' => $request->all()
            ]);

            return response()->json([
                'error' => 'Error al obtener datos históricos de volumen'
            ], 500);
        }
    }

    /**
     * API: Distribución histórica por servicios
     */
    public function historialDistribucionServicios(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio');
        $fechaFin = $request->get('fecha_fin');

        $fechaInicioCarbon = $fechaInicio ? Carbon::parse($fechaInicio)->startOfDay() : null;
        $fechaFinCarbon = $fechaFin ? Carbon::parse($fechaFin)->endOfDay() : null;

        $datos = TurnoHistorial::obtenerDistribucionServicios($fechaInicioCarbon, $fechaFinCarbon);

        return response()->json([
            'labels' => $datos->pluck('nombre'),
            'data' => $datos->pluck('total'),
            'codigos' => $datos->pluck('codigo')
        ]);
    }

    /**
     * API: Distribución histórica por estados finales
     */
    public function historialDistribucionEstados(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio');
        $fechaFin = $request->get('fecha_fin');

        $fechaInicioCarbon = $fechaInicio ? Carbon::parse($fechaInicio)->startOfDay() : null;
        $fechaFinCarbon = $fechaFin ? Carbon::parse($fechaFin)->endOfDay() : null;

        $datos = TurnoHistorial::obtenerDistribucionEstados($fechaInicioCarbon, $fechaFinCarbon);

        return response()->json([
            'labels' => $datos->pluck('estado'),
            'data' => $datos->pluck('total')
        ]);
    }

    /**
     * API: Análisis histórico de horas pico
     */
    public function historialHorasPico(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio');
        $fechaFin = $request->get('fecha_fin');

        $fechaInicioCarbon = $fechaInicio ? Carbon::parse($fechaInicio)->startOfDay() : null;
        $fechaFinCarbon = $fechaFin ? Carbon::parse($fechaFin)->endOfDay() : null;

        $datos = TurnoHistorial::obtenerHorasPico($fechaInicioCarbon, $fechaFinCarbon);

        // Crear array con todas las horas (0-23)
        $horas = [];
        $totales = [];
        for ($i = 0; $i < 24; $i++) {
            $horas[] = $i . ':00';
            $totales[] = $datos->where('hora', $i)->first()->total ?? 0;
        }

        return response()->json([
            'labels' => $horas,
            'data' => $totales
        ]);
    }

    /**
     * API: Tiempo promedio histórico de atención por servicio
     */
    public function historialTiempoAtencion(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio');
        $fechaFin = $request->get('fecha_fin');

        $fechaInicioCarbon = $fechaInicio ? Carbon::parse($fechaInicio)->startOfDay() : null;
        $fechaFinCarbon = $fechaFin ? Carbon::parse($fechaFin)->endOfDay() : null;

        $datos = TurnoHistorial::obtenerTiempoPromedioAtencion($fechaInicioCarbon, $fechaFinCarbon);

        return response()->json([
            'labels' => $datos->pluck('nombre'),
            'data' => $datos->pluck('promedio_minutos'),
            'codigos' => $datos->pluck('codigo')
        ]);
    }

    /**
     * API: Rendimiento histórico de asesores
     */
    public function historialRendimientoAsesores(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio');
        $fechaFin = $request->get('fecha_fin');
        $limite = $request->get('limite', 10);

        $fechaInicioCarbon = $fechaInicio ? Carbon::parse($fechaInicio)->startOfDay() : null;
        $fechaFinCarbon = $fechaFin ? Carbon::parse($fechaFin)->endOfDay() : null;

        $datos = TurnoHistorial::obtenerRendimientoAsesores($fechaInicioCarbon, $fechaFinCarbon, $limite);

        return response()->json([
            'labels' => $datos->pluck('nombre_usuario'),
            'data' => $datos->pluck('total_atendidos'),
            'tiempos_promedio' => $datos->pluck('tiempo_promedio_minutos')
        ]);
    }

    /**
     * API: Estadísticas generales históricas
     */
    public function historialEstadisticasGenerales(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio');
        $fechaFin = $request->get('fecha_fin');

        $fechaInicioCarbon = $fechaInicio ? Carbon::parse($fechaInicio)->startOfDay() : null;
        $fechaFinCarbon = $fechaFin ? Carbon::parse($fechaFin)->endOfDay() : null;

        $stats = TurnoHistorial::obtenerEstadisticasGenerales($fechaInicioCarbon, $fechaFinCarbon);

        return response()->json($stats);
    }

    /**
     * API: Patrones de uso por día de la semana
     */
    public function historialPatronesDiaSemana(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio');
        $fechaFin = $request->get('fecha_fin');

        $fechaInicioCarbon = $fechaInicio ? Carbon::parse($fechaInicio)->startOfDay() : null;
        $fechaFinCarbon = $fechaFin ? Carbon::parse($fechaFin)->endOfDay() : null;

        $datos = TurnoHistorial::obtenerPatronesDiaSemana($fechaInicioCarbon, $fechaFinCarbon);

        return response()->json([
            'labels' => $datos->pluck('nombre_dia'),
            'data' => $datos->pluck('total')
        ]);
    }

    /**
     * API: Eficiencia por servicios
     */
    public function historialEficienciaServicios(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio');
        $fechaFin = $request->get('fecha_fin');

        $fechaInicioCarbon = $fechaInicio ? Carbon::parse($fechaInicio)->startOfDay() : null;
        $fechaFinCarbon = $fechaFin ? Carbon::parse($fechaFin)->endOfDay() : null;

        $datos = TurnoHistorial::obtenerEficienciaServicios($fechaInicioCarbon, $fechaFinCarbon);

        return response()->json([
            'labels' => $datos->pluck('nombre'),
            'volumen' => $datos->pluck('total_atendidos'),
            'tiempo_promedio' => $datos->pluck('tiempo_promedio_minutos'),
            'eficiencia' => $datos->pluck('eficiencia')
        ]);
    }

    /**
     * API: Tiempo en canales no presenciales por asesor
     */
    public function tiempoCanalesNoPresenciales(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio', Carbon::now()->subDays(7)->format('Y-m-d'));
        $fechaFin = $request->get('fecha_fin', Carbon::now()->format('Y-m-d'));

        $datos = \App\Models\CanalNoPresencialHistorial::select(
                'users.nombre_completo as asesor',
                DB::raw('SUM(duracion_minutos) as total_minutos'),
                DB::raw('COUNT(*) as total_actividades')
            )
            ->join('users', 'canal_no_presencial_historial.user_id', '=', 'users.id')
            ->whereBetween('canal_no_presencial_historial.inicio', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
            ->groupBy('users.id', 'users.nombre_completo')
            ->orderByDesc('total_minutos')
            ->get();

        return response()->json([
            'labels' => $datos->pluck('asesor'),
            'data' => $datos->pluck('total_minutos'),
            'actividades' => $datos->pluck('total_actividades')
        ]);
    }

    /**
     * API: Distribución de actividades en canales no presenciales
     */
    public function distribucionActividadesCanales(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio', Carbon::now()->subDays(7)->format('Y-m-d'));
        $fechaFin = $request->get('fecha_fin', Carbon::now()->format('Y-m-d'));

        $datos = \App\Models\CanalNoPresencialHistorial::select(
                DB::raw('DATE(inicio) as fecha'),
                DB::raw('SUM(duracion_minutos) as total_minutos'),
                DB::raw('COUNT(*) as cantidad')
            )
            ->whereBetween('inicio', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        return response()->json([
            'labels' => $datos->pluck('fecha')->map(function($fecha) {
                return Carbon::parse($fecha)->format('d/m/Y');
            }),
            'data' => $datos->pluck('total_minutos'),
            'cantidad' => $datos->pluck('cantidad')
        ]);
    }

    /**
     * API: Estadísticas generales de canales no presenciales
     */
    public function estadisticasCanalesNoPresenciales(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio', Carbon::now()->subDays(7)->format('Y-m-d'));
        $fechaFin = $request->get('fecha_fin', Carbon::now()->format('Y-m-d'));

        $totalActividades = \App\Models\CanalNoPresencialHistorial::whereBetween('inicio', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
            ->count();

        $totalMinutos = \App\Models\CanalNoPresencialHistorial::whereBetween('inicio', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
            ->sum('duracion_minutos');

        $promedioMinutos = $totalActividades > 0 ? round($totalMinutos / $totalActividades, 2) : 0;

        $asesoresMasActivos = \App\Models\CanalNoPresencialHistorial::select(
                'users.nombre_completo',
                DB::raw('COUNT(*) as actividades'),
                DB::raw('SUM(duracion_minutos) as minutos')
            )
            ->join('users', 'canal_no_presencial_historial.user_id', '=', 'users.id')
            ->whereBetween('canal_no_presencial_historial.inicio', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
            ->groupBy('users.id', 'users.nombre_completo')
            ->orderByDesc('minutos')
            ->limit(5)
            ->get();

        return response()->json([
            'total_actividades' => $totalActividades,
            'total_horas' => round($totalMinutos / 60, 2),
            'promedio_minutos' => $promedioMinutos,
            'asesores_mas_activos' => $asesoresMasActivos
        ]);
    }
}
