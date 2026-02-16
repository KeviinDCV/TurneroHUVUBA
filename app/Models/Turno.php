<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
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
        'fecha_finalizacion',
        'duracion_atencion',
        'observaciones'
    ];

    protected $casts = [
        'fecha_creacion' => 'datetime',
        'fecha_llamado' => 'datetime',
        'fecha_atencion' => 'datetime',
        'fecha_finalizacion' => 'datetime',
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
        // Todos los turnos usan el mismo formato (sin indicador de prioridad visible)
        return $this->codigo . '-' . str_pad($this->numero, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Verificar si es un turno prioritario (D o E)
     */
    public function esPrioritario()
    {
        return $this->prioridad >= 4;
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
        // Solo 2 tipos: Normal y Prioritario
        return $this->prioridad >= 4 ? 'Prioritario' : 'Normal';
    }

    public function getPrioridadColorAttribute()
    {
        // Solo 2 colores: Azul para Normal, Rojo para Prioritario
        return $this->prioridad >= 4 ? '#ef4444' : '#3b82f6';
    }

    public function esPrioridadAlta()
    {
        return $this->prioridad >= 4;
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

        try {
            $this->update([
                'estado' => 'atendido',
                'fecha_atencion' => now(),
                'fecha_finalizacion' => now(),
                'duracion_atencion' => $duracion
            ]);
        } catch (\Exception $e) {
            // Si falla en producción por falta del campo fecha_finalizacion
            $this->update([
                'estado' => 'atendido',
                'fecha_atencion' => now(),
                'duracion_atencion' => $duracion
            ]);
        }

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
            'duracion_atencion' => $duracion,
            'asesor_id' => null,
            'caja_id' => null
        ]);

        return $duracion;
    }

    // Método estático para generar el siguiente número de turno
    // Numeración única por CÓDIGO (no por servicio_id) para evitar saltos
    // cuando múltiples servicios comparten el mismo código
    public static function siguienteNumero($servicioId, $prioridad = 3, $fecha = null)
    {
        $fecha = $fecha ?: Carbon::today();

        // Obtener el código del servicio para buscar por código compartido
        $servicio = Servicio::find($servicioId);
        if (!$servicio) {
            throw new \Exception('Servicio no encontrado para generar número de turno');
        }

        // Buscar el último turno del día por CÓDIGO (no por servicio_id)
        // Esto asegura que todos los servicios/subservicios que comparten
        // el mismo código (ej: "K") usen una secuencia numérica única
        $ultimoTurno = static::where('codigo', $servicio->codigo)
            ->whereDate('fecha_creacion', $fecha)
            ->lockForUpdate() // Prevenir condiciones de carrera
            ->orderBy('numero', 'desc')
            ->first();

        return $ultimoTurno ? $ultimoTurno->numero + 1 : 1;
    }

    // Método estático para crear un nuevo turno
    // Envuelto en transacción con lockForUpdate para evitar números duplicados
    public static function crear($servicioId, $prioridad = 3)
    {
        return DB::transaction(function () use ($servicioId, $prioridad) {
            $servicio = Servicio::find($servicioId);
            if (!$servicio) {
                throw new \Exception('Servicio no encontrado');
            }

            // Validar que la prioridad esté entre 1 y 5
            if ($prioridad < 1 || $prioridad > 5) {
                $prioridad = 3; // Por defecto C (media)
            }

            // Generar número según el código compartido del servicio
            $numero = static::siguienteNumero($servicioId, $prioridad);

            return static::create([
                'codigo' => $servicio->codigo,
                'numero' => $numero,
                'servicio_id' => $servicioId,
                'prioridad' => $prioridad,
                'fecha_creacion' => now(),
                'estado' => 'pendiente'
            ]);
        });
    }

    // Método estático para convertir tipo de prioridad a número
    // Solo 2 tipos: 'normal' (3) y 'alta' (5)
    public static function tipoAPrioridad($tipo)
    {
        $mapa = [
            'normal' => 3,
            'alta' => 5,
            // Compatibilidad con el sistema anterior (letras A-E)
            'A' => 3,
            'B' => 3,
            'C' => 3,
            'D' => 5,
            'E' => 5,
        ];
        return $mapa[strtolower($tipo)] ?? $mapa[strtoupper($tipo)] ?? 3;
    }
    
    // Alias para compatibilidad
    public static function letraAPrioridad($letra)
    {
        return static::tipoAPrioridad($letra);
    }

    /**
     * Parsear un código completo y extraer sus componentes
     * Formato: CODIGO-NUMERO (ej: "CP-001")
     * 
     * @param string $codigoCompleto
     * @return array|null ['codigo' => string, 'numero' => int] o null si inválido
     */
    public static function parsearCodigoCompleto($codigoCompleto)
    {
        $partes = explode('-', $codigoCompleto);
        
        if (count($partes) !== 2) {
            return null;
        }
        
        $codigo = strtoupper($partes[0]);
        $numero = (int) $partes[1];
        
        if ($numero <= 0) {
            return null;
        }
        
        return [
            'codigo' => $codigo,
            'numero' => $numero
        ];
    }

    /**
     * Buscar un turno por su código completo
     * Cuando hay duplicados (por transferencias), prioriza:
     * 1. Turnos pendientes o llamados (activos)
     * 2. Turno más reciente
     * 
     * @param string $codigoCompleto
     * @return Turno|null
     */
    public static function buscarPorCodigoCompleto($codigoCompleto)
    {
        $datos = static::parsearCodigoCompleto($codigoCompleto);
        
        if (!$datos) {
            return null;
        }
        
        // Primero buscar turnos activos (pendiente o llamado) - más relevantes
        $turnoActivo = static::where('codigo', $datos['codigo'])
            ->where('numero', $datos['numero'])
            ->delDia()
            ->whereIn('estado', ['pendiente', 'llamado'])
            ->orderBy('id', 'desc')
            ->first();

        if ($turnoActivo) {
            return $turnoActivo;
        }

        // Si no hay activos, retornar el más reciente
        return static::where('codigo', $datos['codigo'])
            ->where('numero', $datos['numero'])
            ->delDia()
            ->orderBy('id', 'desc')
            ->first();
    }
}
