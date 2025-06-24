<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Turno extends Model
{
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
        'observaciones'
    ];

    protected $casts = [
        'fecha_creacion' => 'datetime',
        'fecha_llamado' => 'datetime',
        'fecha_atencion' => 'datetime',
        'estado' => 'string',
        'prioridad' => 'string',
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

    public function scopePrioritarios($query)
    {
        return $query->where('prioridad', 'prioritaria');
    }

    public function scopeNormales($query)
    {
        return $query->where('prioridad', 'normal');
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

    public function esPrioritario()
    {
        return $this->prioridad === 'prioritaria';
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

    public function marcarComoAtendido()
    {
        $this->update([
            'estado' => 'atendido',
            'fecha_atencion' => now()
        ]);
    }

    public function marcarComoAplazado()
    {
        $this->update([
            'estado' => 'aplazado',
            'fecha_llamado' => null
        ]);
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
    public static function crear($servicioId, $prioridad = 'normal')
    {
        $servicio = Servicio::find($servicioId);
        if (!$servicio) {
            throw new \Exception('Servicio no encontrado');
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
}
