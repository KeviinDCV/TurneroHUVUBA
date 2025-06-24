<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Caja extends Model
{
    protected $fillable = [
        'nombre',
        'descripcion',
        'estado',
        'ubicacion',
        'numero_caja',
        'asesor_activo_id',
        'session_id',
        'fecha_asignacion',
        'ip_asesor',
    ];

    protected $casts = [
        'estado' => 'string',
        'fecha_asignacion' => 'datetime',
    ];

    // Scope para cajas activas
    public function scopeActivas($query)
    {
        return $query->where('estado', 'activa');
    }

    // Scope para cajas inactivas
    public function scopeInactivas($query)
    {
        return $query->where('estado', 'inactiva');
    }

    /**
     * Relación uno a muchos con turnos
     */
    public function turnos()
    {
        return $this->hasMany(Turno::class);
    }

    /**
     * Obtener turnos atendidos en esta caja
     */
    public function turnosAtendidos()
    {
        return $this->turnos()->where('estado', 'atendido');
    }

    /**
     * Obtener turnos llamados en esta caja
     */
    public function turnosLlamados()
    {
        return $this->turnos()->where('estado', 'llamado');
    }

    /**
     * Relación con el asesor activo
     */
    public function asesorActivo()
    {
        return $this->belongsTo(User::class, 'asesor_activo_id');
    }

    /**
     * Verificar si la caja está ocupada por un asesor
     */
    public function estaOcupada()
    {
        return !empty($this->asesor_activo_id) &&
               !empty($this->session_id) &&
               $this->fecha_asignacion &&
               $this->fecha_asignacion->diffInMinutes(now()) < 30; // 30 minutos de timeout
    }

    /**
     * Verificar si la caja está ocupada por un asesor específico
     */
    public function estaOcupadaPor($asesorId, $sessionId = null)
    {
        if (!$this->estaOcupada()) {
            return false;
        }

        $ocupadaPorAsesor = $this->asesor_activo_id == $asesorId;

        if ($sessionId) {
            return $ocupadaPorAsesor && $this->session_id === $sessionId;
        }

        return $ocupadaPorAsesor;
    }

    /**
     * Asignar caja a un asesor
     */
    public function asignarAsesor($asesorId, $sessionId, $ip = null)
    {
        $this->update([
            'asesor_activo_id' => $asesorId,
            'session_id' => $sessionId,
            'fecha_asignacion' => now(),
            'ip_asesor' => $ip ?: request()->ip()
        ]);
    }

    /**
     * Liberar caja del asesor
     */
    public function liberarAsesor()
    {
        $this->update([
            'asesor_activo_id' => null,
            'session_id' => null,
            'fecha_asignacion' => null,
            'ip_asesor' => null
        ]);
    }

    /**
     * Verificar si la sesión de la caja es diferente a la actual
     */
    public function esDiferenteSession($sessionId)
    {
        return $this->session_id && $this->session_id !== $sessionId;
    }

    /**
     * Scope para cajas disponibles (no ocupadas)
     */
    public function scopeDisponibles($query)
    {
        return $query->where(function($q) {
            $q->whereNull('asesor_activo_id')
              ->orWhereNull('session_id')
              ->orWhere('fecha_asignacion', '<', now()->subMinutes(30));
        })->where('estado', 'activa');
    }

    /**
     * Scope para cajas ocupadas
     */
    public function scopeOcupadas($query)
    {
        return $query->whereNotNull('asesor_activo_id')
                    ->whereNotNull('session_id')
                    ->where('fecha_asignacion', '>=', now()->subMinutes(30));
    }
}
