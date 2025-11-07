<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CanalNoPresencialHistorial extends Model
{
    use HasFactory;

    protected $table = 'canal_no_presencial_historial';

    protected $fillable = [
        'user_id',
        'actividad',
        'inicio',
        'fin',
        'duracion_minutos',
    ];

    protected $casts = [
        'inicio' => 'datetime',
        'fin' => 'datetime',
    ];

    /**
     * Relación con el usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calcular la duración en minutos
     */
    public function calcularDuracion()
    {
        if ($this->inicio && $this->fin) {
            return $this->inicio->diffInMinutes($this->fin);
        }
        return null;
    }

    /**
     * Formatear duración como texto legible
     */
    public function getDuracionFormateada()
    {
        if (!$this->duracion_minutos) {
            return 'N/A';
        }

        $horas = floor($this->duracion_minutos / 60);
        $minutos = $this->duracion_minutos % 60;

        if ($horas > 0) {
            return "{$horas}h {$minutos}m";
        }
        return "{$minutos}m";
    }
}
