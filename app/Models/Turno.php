<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Turno extends Model
{
    /**
     * Boot del modelo para configurar eventos automáticos
     */
    protected static function boot()
    {
        parent::boot();

        // Crear backup automáticamente cuando se crea un turno
        static::created(function ($turno) {
            TurnoHistorial::crearDesdeturno($turno, 'creacion');
        });

        // Crear backup automáticamente cuando se actualiza un turno
        static::updated(function ($turno) {
            TurnoHistorial::crearDesdeturno($turno, 'actualizacion', [
                'cambios' => $turno->getChanges(),
                'valores_anteriores' => $turno->getOriginal()
            ]);
        });

        // Crear backup automáticamente antes de eliminar un turno
        static::deleting(function ($turno) {
            TurnoHistorial::crearDesdeturno($turno, 'eliminacion', [
                'motivo' => 'Turno eliminado del sistema principal'
            ]);
        });
    }

    protected $fillable = [
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
        'observaciones'
    ];

    protected $casts = [
        'fecha_creacion' => 'datetime',
        'fecha_llamado' => 'datetime',
        'fecha_atencion' => 'datetime',
        'estado' => 'string',
        'prioridad' => 'integer',
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

    public function historial()
    {
        return $this->hasMany(TurnoHistorial::class, 'turno_original_id');
    }

    // Scopes
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeLlamados($query)
    {
        return $query->where('estado', 'llamado');
    }

    public function scopeAtendidos($query)
    {
        return $query->where('estado', 'atendido');
    }

    public function scopeAplazados($query)
    {
        return $query->where('estado', 'aplazado');
    }

    public function scopePorPrioridad($query, $prioridad)
    {
        return $query->where('prioridad', $prioridad);
    }

    public function scopePrioridadAlta($query)
    {
        return $query->where('prioridad', '>=', 4); // D y E
    }

    public function scopePrioridadBaja($query)
    {
        return $query->where('prioridad', '<=', 2); // A y B
    }

    public function scopeDelDia($query, $fecha = null)
    {
        $fecha = $fecha ?: Carbon::today();
        return $query->whereDate('fecha_creacion', $fecha);
    }

    public function scopeDelServicio($query, $servicioId)
    {
        return $query->where('servicio_id', $servicioId);
    }

    public function scopeDeLaCaja($query, $cajaId)
    {
        return $query->where('caja_id', $cajaId);
    }

    // Métodos auxiliares
    public function getCodigoCompletoAttribute()
    {
        return $this->codigo . '-' . str_pad($this->numero, 3, '0', STR_PAD_LEFT);
    }

    public function esPendiente()
    {
        return $this->estado === 'pendiente';
    }

    public function esLlamado()
    {
        return $this->estado === 'llamado';
    }

    public function esAtendido()
    {
        return $this->estado === 'atendido';
    }

    public function esAplazado()
    {
        return $this->estado === 'aplazado';
    }

    public function getPrioridadLetraAttribute()
    {
        // Convertir número a letra (1=A, 2=B, 3=C, 4=D, 5=E)
        $letras = ['', 'A', 'B', 'C', 'D', 'E'];
        return $letras[$this->prioridad] ?? 'C';
    }

    public function getPrioridadColorAttribute()
    {
        // Colores para cada nivel de prioridad
        $colores = [
            1 => '#10b981', // A - Verde (baja)
            2 => '#3b82f6', // B - Azul
            3 => '#f59e0b', // C - Amarillo (media)
            4 => '#f97316', // D - Naranja
            5 => '#ef4444', // E - Rojo (alta)
        ];
        return $colores[$this->prioridad] ?? $colores[3];
    }

    public function esPrioridadAlta()
    {
        return $this->prioridad >= 4; // D o E
    }

    public function marcarComoLlamado($cajaId = null, $asesorId = null)
    {
        $this->update([
            'estado' => 'llamado',
            'fecha_llamado' => now(),
            'caja_id' => $cajaId,
            'asesor_id' => $asesorId
        ]);
    }

    public function marcarComoAtendido($duracionFrontend = null)
    {
        // Usar la duración del frontend (cronómetro real) si está disponible
        $duracion = $duracionFrontend;

        // Si no hay duración del frontend, calcular desde fecha_llamado
        if ($duracion === null && $this->fecha_llamado) {
            try {
                // Hacer el cálculo completamente en UTC para evitar problemas de zona horaria
                $fechaInicio = \Carbon\Carbon::parse($this->fecha_llamado)->utc();
                $fechaFin = \Carbon\Carbon::now()->utc();

                // Calcular diferencia en segundos de forma más explícita
                if ($fechaFin->greaterThan($fechaInicio)) {
                    $duracion = $fechaInicio->diffInSeconds($fechaFin);
                } else {
                    $duracion = 0; // Si la fecha de fin es anterior o igual, usar 0
                }

                // Log para debugging (comentado para evitar spam)
                // \Log::info('Calculando duración automática', [
                //     'turno_id' => $this->id,
                //     'codigo_completo' => $this->codigo_completo,
                //     'fecha_llamado_original' => $this->fecha_llamado,
                //     'fecha_inicio_utc' => $fechaInicio->toDateTimeString(),
                //     'fecha_fin_utc' => $fechaFin->toDateTimeString(),
                //     'duracion_calculada' => $duracion
                // ]);
            } catch (\Exception $e) {
                // En caso de error, usar 0 segundos
                $duracion = 0;
                \Log::error('Error calculando duración', [
                    'turno_id' => $this->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Asegurar que la duración no sea null
        $duracion = $duracion ?? 0;

        // Log final para verificar qué se está guardando (comentado para evitar spam)
        // \Log::info('Guardando turno como atendido', [
        //     'turno_id' => $this->id,
        //     'codigo_completo' => $this->codigo_completo,
        //     'duracion_final' => $duracion,
        //     'duracion_frontend' => $duracionFrontend
        // ]);

        $this->update([
            'estado' => 'atendido',
            'fecha_atencion' => now(),
            'duracion_atencion' => $duracion
        ]);

        return $duracion;
    }

    public function marcarComoAplazado($duracionFrontend = null)
    {
        // Calcular duración transcurrida hasta el momento del aplazamiento
        $duracion = $duracionFrontend;

        // Si no hay duración del frontend, calcular desde fecha_llamado
        if ($duracion === null && $this->fecha_llamado) {
            try {
                // Hacer el cálculo completamente en UTC para evitar problemas de zona horaria
                $fechaInicio = \Carbon\Carbon::parse($this->fecha_llamado)->utc();
                $fechaFin = \Carbon\Carbon::now()->utc();

                // Calcular diferencia en segundos correctamente
                if ($fechaFin->greaterThan($fechaInicio)) {
                    $duracion = $fechaInicio->diffInSeconds($fechaFin);
                } else {
                    $duracion = 0;
                }
            } catch (\Exception $e) {
                // En caso de error, usar 0 segundos
                $duracion = 0;
            }
        }

        // Asegurar que la duración no sea null
        $duracion = $duracion ?? 0;

        $this->update([
            'estado' => 'aplazado',
            'fecha_llamado' => null,
            'duracion_atencion' => $duracion
        ]);

        return $duracion;
    }

    // Método estático para generar el siguiente número de turno
    public static function siguienteNumero($servicioId, $fecha = null)
    {
        $fecha = $fecha ?: Carbon::today();

        $ultimoTurno = static::where('servicio_id', $servicioId)
            ->whereDate('fecha_creacion', $fecha)
            ->orderBy('numero', 'desc')
            ->first();

        return $ultimoTurno ? $ultimoTurno->numero + 1 : 1;
    }

    // Método estático para crear un nuevo turno
    public static function crear($servicioId, $prioridad = 3)
    {
        $servicio = Servicio::find($servicioId);
        if (!$servicio) {
            throw new \Exception('Servicio no encontrado');
        }

        // Validar que la prioridad esté entre 1 y 5
        if ($prioridad < 1 || $prioridad > 5) {
            $prioridad = 3; // Por defecto C (media)
        }

        $numero = static::siguienteNumero($servicioId);

        return static::create([
            'codigo' => $servicio->codigo,
            'numero' => $numero,
            'servicio_id' => $servicioId,
            'prioridad' => $prioridad,
            'fecha_creacion' => now(),
            'estado' => 'pendiente'
        ]);
    }

    // Método estático para convertir letra a número
    public static function letraAPrioridad($letra)
    {
        $mapa = [
            'A' => 1,
            'B' => 2,
            'C' => 3,
            'D' => 4,
            'E' => 5,
        ];
        return $mapa[strtoupper($letra)] ?? 3;
    }
}
