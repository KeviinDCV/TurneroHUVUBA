<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Turno;
use App\Models\Servicio;
use Carbon\Carbon;

class GraficosController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Mostrar la vista principal de gráficos
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
}
