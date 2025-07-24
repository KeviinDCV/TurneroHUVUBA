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

    // Scopes
    public function scopeDelDia($query, $fecha = null)
    {
        $fecha = $fecha ?: Carbon::today();
        return $query->whereDate('fecha_creacion', $fecha);
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
}
