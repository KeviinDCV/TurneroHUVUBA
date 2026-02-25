<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TurnoHistorial extends Model
{
    /**
     * Nombre de la tabla
     */
    protected $table = 'turno_historial';

    /**
     * Campos que se pueden asignar masivamente
     */
    protected $fillable = [
        'turno_original_id',
        'codigo',
        'numero',
        'servicio_id',
        'caja_id',
        'asesor_id',
        'estado',
        'prioridad',
        'fecha_creacion',
        'fecha_llamado',
        'fecha_atencion',
        'duracion_atencion',
        'observaciones',
        'fecha_backup',
        'tipo_backup',
        'datos_adicionales'
    ];

    /**
     * Campos que deben ser tratados como fechas
     */
    protected $casts = [
        'fecha_creacion' => 'datetime',
        'fecha_llamado' => 'datetime',
        'fecha_atencion' => 'datetime',
        'fecha_backup' => 'datetime',
        'estado' => 'string',
        'prioridad' => 'string',
        'tipo_backup' => 'string',
        'datos_adicionales' => 'array',
    ];

    // Relaciones
    public function servicio()
    {
        return $this->belongsTo(Servicio::class);
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class);
    }

    public function asesor()
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    public function turnoOriginal()
    {
        return $this->belongsTo(Turno::class, 'turno_original_id');
    }

    public function scopeDelDia($query, $fecha = null)
    {
        $fecha = $fecha ? \Carbon\Carbon::parse($fecha) : Carbon::today();
        $inicio = $fecha->copy()->startOfDay();
        $fin = $fecha->copy()->endOfDay();
        
        return $query->whereBetween('fecha_creacion', [$inicio, $fin]);
    }

    public function scopePorServicio($query, $servicioId)
    {
        return $query->where('servicio_id', $servicioId);
    }

    public function scopePorTipoBackup($query, $tipo)
    {
        return $query->where('tipo_backup', $tipo);
    }

    public function scopeEntreFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha_creacion', [$fechaInicio, $fechaFin]);
    }

    // Métodos auxiliares
    public function getCodigoCompletoAttribute()
    {
        return $this->codigo . '-' . str_pad($this->numero, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Crear un registro de historial desde un turno
     */
    public static function crearDesdeturno(Turno $turno, $tipoBackup = 'creacion', $datosAdicionales = null)
    {
        return static::create([
            'turno_original_id' => $turno->id,
            'codigo' => $turno->codigo,
            'numero' => $turno->numero,
            'servicio_id' => $turno->servicio_id,
            'caja_id' => $turno->caja_id,
            'asesor_id' => $turno->asesor_id,
            'estado' => $turno->estado,
            'prioridad' => $turno->prioridad,
            'fecha_creacion' => $turno->fecha_creacion,
            'fecha_llamado' => $turno->fecha_llamado,
            'fecha_atencion' => $turno->fecha_atencion,
            'duracion_atencion' => $turno->duracion_atencion,
            'observaciones' => $turno->observaciones,
            'fecha_backup' => now(),
            'tipo_backup' => $tipoBackup,
            'datos_adicionales' => $datosAdicionales
        ]);
    }

    /**
     * Obtener estadísticas del historial
     */
    public static function obtenerEstadisticas($fechaInicio = null, $fechaFin = null)
    {
        $baseQuery = static::query();

        if ($fechaInicio && $fechaFin) {
            $baseQuery->whereBetween('fecha_creacion', [$fechaInicio, $fechaFin]);
        }

        // Crear consultas separadas para evitar conflictos
        $totalQuery = clone $baseQuery;
        $estadoQuery = clone $baseQuery;
        $servicioQuery = clone $baseQuery;
        $tipoQuery = clone $baseQuery;

        return [
            'total_turnos' => $totalQuery->count(),
            'por_estado' => $estadoQuery->groupBy('turno_historial.estado')
                                      ->selectRaw('turno_historial.estado, count(*) as total')
                                      ->pluck('total', 'estado'),
            'por_servicio' => $servicioQuery->join('servicios', 'turno_historial.servicio_id', '=', 'servicios.id')
                                          ->groupBy('servicios.nombre')
                                          ->selectRaw('servicios.nombre, count(*) as total')
                                          ->pluck('total', 'nombre'),
            'por_tipo_backup' => $tipoQuery->groupBy('turno_historial.tipo_backup')
                                         ->selectRaw('turno_historial.tipo_backup, count(*) as total')
                                         ->pluck('total', 'tipo_backup')
        ];
    }

    /**
     * Obtener volumen de turnos por período (diario, semanal, mensual)
     */
    public static function obtenerVolumenPorTiempo($fechaInicio, $fechaFin, $periodo = 'daily')
    {
        $query = static::where('tipo_backup', 'creacion')
                      ->whereBetween('fecha_creacion', [$fechaInicio, $fechaFin]);

        switch ($periodo) {
            case 'hourly':
                return $query->selectRaw('DATE(fecha_creacion) as fecha, HOUR(fecha_creacion) as hora, COUNT(*) as total')
                           ->groupBy('fecha', 'hora')
                           ->orderBy('fecha')
                           ->orderBy('hora')
                           ->get();

            case 'daily':
                return $query->selectRaw('DATE(fecha_creacion) as fecha, COUNT(*) as total')
                           ->groupBy('fecha')
                           ->orderBy('fecha')
                           ->get();

            case 'weekly':
                return $query->selectRaw('YEARWEEK(fecha_creacion) as semana, COUNT(*) as total')
                           ->groupBy('semana')
                           ->orderBy('semana')
                           ->get();

            case 'monthly':
                return $query->selectRaw('YEAR(fecha_creacion) as año, MONTH(fecha_creacion) as mes, COUNT(*) as total')
                           ->groupBy('año', 'mes')
                           ->orderBy('año')
                           ->orderBy('mes')
                           ->get();
        }
    }

    /**
     * Obtener distribución por servicios
     */
    public static function obtenerDistribucionServicios($fechaInicio = null, $fechaFin = null)
    {
        $query = static::join('servicios', 'turno_historial.servicio_id', '=', 'servicios.id')
                      ->where('turno_historial.tipo_backup', 'creacion');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('turno_historial.fecha_creacion', [$fechaInicio, $fechaFin]);
        }

        return $query->selectRaw('servicios.nombre, servicios.codigo, COUNT(*) as total')
                    ->groupBy('servicios.id', 'servicios.nombre', 'servicios.codigo')
                    ->orderBy('total', 'desc')
                    ->get();
    }

    /**
     * Obtener distribución por estados finales
     */
    public static function obtenerDistribucionEstados($fechaInicio = null, $fechaFin = null)
    {
        // Obtener el último estado de cada turno
        $subquery = static::selectRaw('turno_original_id, MAX(fecha_backup) as ultima_fecha')
                         ->where('tipo_backup', '!=', 'eliminacion');

        if ($fechaInicio && $fechaFin) {
            $subquery->whereBetween('fecha_creacion', [$fechaInicio, $fechaFin]);
        }

        $subquery = $subquery->groupBy('turno_original_id');

        return static::joinSub($subquery, 'ultimos', function ($join) {
                     $join->on('turno_historial.turno_original_id', '=', 'ultimos.turno_original_id')
                          ->on('turno_historial.fecha_backup', '=', 'ultimos.ultima_fecha');
                 })
                 ->selectRaw('turno_historial.estado, COUNT(*) as total')
                 ->groupBy('turno_historial.estado')
                 ->orderBy('total', 'desc')
                 ->get();
    }

    /**
     * Obtener análisis de horas pico
     */
    public static function obtenerHorasPico($fechaInicio = null, $fechaFin = null)
    {
        $query = static::where('tipo_backup', 'creacion');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('fecha_creacion', [$fechaInicio, $fechaFin]);
        }

        return $query->selectRaw('HOUR(fecha_creacion) as hora, COUNT(*) as total')
                    ->groupBy('hora')
                    ->orderBy('hora')
                    ->get();
    }

    /**
     * Obtener tiempo promedio de atención por servicio
     */
    public static function obtenerTiempoPromedioAtencion($fechaInicio = null, $fechaFin = null)
    {
        $query = static::join('servicios', 'turno_historial.servicio_id', '=', 'servicios.id')
                      ->where('turno_historial.estado', 'atendido')
                      ->whereNotNull('turno_historial.duracion_atencion');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('turno_historial.fecha_creacion', [$fechaInicio, $fechaFin]);
        }

        return $query->selectRaw('servicios.nombre, servicios.codigo, AVG(turno_historial.duracion_atencion) as promedio_segundos')
                    ->groupBy('servicios.id', 'servicios.nombre', 'servicios.codigo')
                    ->orderBy('promedio_segundos', 'desc')
                    ->get()
                    ->map(function ($item) {
                        $item->promedio_minutos = round($item->promedio_segundos / 60, 2);
                        return $item;
                    });
    }

    /**
     * Obtener rendimiento de asesores
     */
    public static function obtenerRendimientoAsesores($fechaInicio = null, $fechaFin = null, $limite = 10)
    {
        $query = static::join('users', 'turno_historial.asesor_id', '=', 'users.id')
                      ->where('turno_historial.estado', 'atendido')
                      ->whereNotNull('turno_historial.asesor_id');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('turno_historial.fecha_creacion', [$fechaInicio, $fechaFin]);
        }

        return $query->selectRaw('users.nombre_usuario, users.id as asesor_id, COUNT(*) as total_atendidos, AVG(turno_historial.duracion_atencion) as tiempo_promedio')
                    ->groupBy('users.id', 'users.nombre_usuario')
                    ->orderBy('total_atendidos', 'desc')
                    ->limit($limite)
                    ->get()
                    ->map(function ($item) {
                        $item->tiempo_promedio_minutos = $item->tiempo_promedio ? round($item->tiempo_promedio / 60, 2) : 0;
                        return $item;
                    });
    }

    /**
     * Obtener estadísticas generales del historial
     */
    public static function obtenerEstadisticasGenerales($fechaInicio = null, $fechaFin = null)
    {
        $baseQuery = static::query();

        if ($fechaInicio && $fechaFin) {
            $baseQuery->whereBetween('fecha_creacion', [$fechaInicio, $fechaFin]);
        }

        $totalTurnos = (clone $baseQuery)->where('tipo_backup', 'creacion')->count();
        $turnosAtendidos = (clone $baseQuery)->where('estado', 'atendido')->count();
        $tiempoPromedio = (clone $baseQuery)->where('estado', 'atendido')
                                           ->whereNotNull('duracion_atencion')
                                           ->avg('duracion_atencion');

        return [
            'total_turnos_historicos' => $totalTurnos,
            'turnos_atendidos_historicos' => $turnosAtendidos,
            'tiempo_promedio_atencion_historico' => $tiempoPromedio ? round($tiempoPromedio / 60, 2) : 0,
            'tasa_atencion' => $totalTurnos > 0 ? round(($turnosAtendidos / $totalTurnos) * 100, 2) : 0,
            'servicios_utilizados' => (clone $baseQuery)->distinct('servicio_id')->count(),
            'asesores_participantes' => (clone $baseQuery)->whereNotNull('asesor_id')->distinct('asesor_id')->count()
        ];
    }

    /**
     * Obtener tendencias de crecimiento
     */
    public static function obtenerTendenciasCrecimiento($fechaInicio, $fechaFin)
    {
        $datos = static::where('tipo_backup', 'creacion')
                      ->whereBetween('fecha_creacion', [$fechaInicio, $fechaFin])
                      ->selectRaw('DATE(fecha_creacion) as fecha, COUNT(*) as total')
                      ->groupBy('fecha')
                      ->orderBy('fecha')
                      ->get();

        // Calcular tendencia (regresión lineal simple)
        $n = $datos->count();
        if ($n < 2) {
            return ['datos' => $datos, 'tendencia' => 'sin_datos'];
        }

        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        foreach ($datos as $index => $dato) {
            $x = $index + 1;
            $y = $dato->total;
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }

        $pendiente = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);

        return [
            'datos' => $datos,
            'tendencia' => $pendiente > 0.1 ? 'creciente' : ($pendiente < -0.1 ? 'decreciente' : 'estable'),
            'pendiente' => round($pendiente, 4)
        ];
    }

    /**
     * Obtener comparación de períodos
     */
    public static function compararPeriodos($fechaInicio1, $fechaFin1, $fechaInicio2, $fechaFin2)
    {
        $periodo1 = static::where('tipo_backup', 'creacion')
                         ->whereBetween('fecha_creacion', [$fechaInicio1, $fechaFin1])
                         ->count();

        $periodo2 = static::where('tipo_backup', 'creacion')
                         ->whereBetween('fecha_creacion', [$fechaInicio2, $fechaFin2])
                         ->count();

        $cambio = $periodo1 > 0 ? (($periodo2 - $periodo1) / $periodo1) * 100 : 0;

        return [
            'periodo_anterior' => $periodo1,
            'periodo_actual' => $periodo2,
            'cambio_porcentual' => round($cambio, 2),
            'tipo_cambio' => $cambio > 0 ? 'aumento' : ($cambio < 0 ? 'disminucion' : 'sin_cambio')
        ];
    }

    /**
     * Obtener patrones de uso por día de la semana
     */
    public static function obtenerPatronesDiaSemana($fechaInicio = null, $fechaFin = null)
    {
        $query = static::where('tipo_backup', 'creacion');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('fecha_creacion', [$fechaInicio, $fechaFin]);
        }

        return $query->selectRaw('DAYOFWEEK(fecha_creacion) as dia_semana, COUNT(*) as total')
                    ->groupBy('dia_semana')
                    ->orderBy('dia_semana')
                    ->get()
                    ->map(function ($item) {
                        $dias = ['', 'Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                        $item->nombre_dia = $dias[$item->dia_semana];
                        return $item;
                    });
    }

    /**
     * Obtener eficiencia por servicio (tiempo vs volumen)
     */
    public static function obtenerEficienciaServicios($fechaInicio = null, $fechaFin = null)
    {
        $query = static::join('servicios', 'turno_historial.servicio_id', '=', 'servicios.id')
                      ->where('turno_historial.estado', 'atendido')
                      ->whereNotNull('turno_historial.duracion_atencion');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('turno_historial.fecha_creacion', [$fechaInicio, $fechaFin]);
        }

        return $query->selectRaw('
                        servicios.nombre,
                        servicios.codigo,
                        COUNT(*) as total_atendidos,
                        AVG(turno_historial.duracion_atencion) as tiempo_promedio,
                        MIN(turno_historial.duracion_atencion) as tiempo_minimo,
                        MAX(turno_historial.duracion_atencion) as tiempo_maximo
                    ')
                    ->groupBy('servicios.id', 'servicios.nombre', 'servicios.codigo')
                    ->orderBy('total_atendidos', 'desc')
                    ->get()
                    ->map(function ($item) {
                        $item->tiempo_promedio_minutos = round($item->tiempo_promedio / 60, 2);
                        $item->tiempo_minimo_minutos = round($item->tiempo_minimo / 60, 2);
                        $item->tiempo_maximo_minutos = round($item->tiempo_maximo / 60, 2);
                        $item->eficiencia = $item->tiempo_promedio > 0 ? round($item->total_atendidos / ($item->tiempo_promedio / 60), 2) : 0;
                        return $item;
                    });
    }
}
