<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Servicio extends Model
{
    protected $fillable = [
        'nombre',
        'descripcion',
        'nivel',
        'servicio_padre_id',
        'estado',
        'codigo',
        'orden',
        'ocultar_turno'
    ];

    protected $casts = [
        'nivel' => 'string',
        'estado' => 'string',
        'orden' => 'integer',
        'ocultar_turno' => 'boolean',
    ];

    // Relación: Un servicio puede tener muchos subservicios
    public function subservicios()
    {
        return $this->hasMany(Servicio::class, 'servicio_padre_id')->orderBy('orden');
    }

    // Relación: Un subservicio pertenece a un servicio padre
    public function servicioPadre()
    {
        return $this->belongsTo(Servicio::class, 'servicio_padre_id');
    }

    // Scope para servicios principales (nivel servicio)
    public function scopeServicios($query)
    {
        return $query->where('nivel', 'servicio');
    }

    // Scope para subservicios
    public function scopeSubservicios($query)
    {
        return $query->where('nivel', 'subservicio');
    }

    // Scope para servicios activos
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    // Scope para servicios inactivos
    public function scopeInactivos($query)
    {
        return $query->where('estado', 'inactivo');
    }

    // Método para obtener el nombre completo (incluye padre si es subservicio)
    public function getNombreCompletoAttribute()
    {
        if ($this->nivel === 'subservicio' && $this->servicioPadre) {
            return $this->servicioPadre->nombre . ' > ' . $this->nombre;
        }
        return $this->nombre;
    }

    // Método para verificar si es un servicio principal
    public function esServicioPrincipal()
    {
        return $this->nivel === 'servicio';
    }

    /**
     * Relación muchos a muchos con usuarios
     */
    public function usuarios()
    {
        return $this->belongsToMany(User::class, 'user_servicio')->withTimestamps();
    }

    /**
     * Verificar si el servicio está asignado a un usuario específico
     */
    public function estaAsignadoA($userId)
    {
        return $this->usuarios()->where('user_id', $userId)->exists();
    }

    /**
     * Obtener usuarios asesores asignados a este servicio
     */
    public function asesores()
    {
        return $this->usuarios()->where('rol', 'Asesor');
    }

    // Método para verificar si es un subservicio
    public function esSubservicio()
    {
        return $this->nivel === 'subservicio';
    }

    /**
     * Relación uno a muchos con turnos
     */
    public function turnos()
    {
        return $this->hasMany(Turno::class);
    }

    /**
     * Obtener turnos pendientes del servicio
     */
    public function turnosPendientes()
    {
        return $this->turnos()->where('estado', 'pendiente');
    }

    /**
     * Obtener turnos aplazados del servicio
     */
    public function turnosAplazados()
    {
        return $this->turnos()->where('estado', 'aplazado');
    }
}
