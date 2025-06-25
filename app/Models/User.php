<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nombre_completo',
        'correo_electronico',
        'rol',
        'cedula',
        'nombre_usuario',
        'password',
        'session_id',
        'last_activity',
        'last_ip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_activity' => 'datetime',
        ];
    }

    /**
     * Verificar si el usuario es administrador
     */
    public function esAdministrador(): bool
    {
        return $this->rol === 'Administrador';
    }

    /**
     * Verificar si el usuario es asesor
     */
    public function esAsesor(): bool
    {
        return $this->rol === 'Asesor';
    }

    /**
     * Obtener el nombre de usuario para autenticación
     */
    public function getAuthIdentifierName()
    {
        return 'id'; // Laravel usa 'id' por defecto
    }

    /**
     * Obtener el nombre del campo de usuario para login
     */
    public function username()
    {
        return 'nombre_usuario';
    }

    /**
     * Obtener el identificador único para autenticación
     */
    public function getAuthIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Obtener el email para notificaciones
     */
    public function getEmailForPasswordReset()
    {
        return $this->correo_electronico;
    }

    /**
     * Relación muchos a muchos con servicios
     */
    public function servicios()
    {
        return $this->belongsToMany(Servicio::class, 'user_servicio')->withTimestamps();
    }

    /**
     * Verificar si el usuario tiene asignado un servicio específico
     */
    public function tieneServicio($servicioId)
    {
        return $this->servicios()->where('servicio_id', $servicioId)->exists();
    }

    /**
     * Obtener servicios activos asignados al usuario
     */
    public function serviciosActivos()
    {
        return $this->servicios()->where('estado', 'activo');
    }

    /**
     * Relación uno a muchos con turnos (como asesor)
     */
    public function turnos()
    {
        return $this->hasMany(Turno::class, 'asesor_id');
    }

    /**
     * Obtener turnos atendidos por el asesor
     */
    public function turnosAtendidos()
    {
        return $this->turnos()->where('estado', 'atendido');
    }

    /**
     * Obtener turnos llamados por el asesor
     */
    public function turnosLlamados()
    {
        return $this->turnos()->where('estado', 'llamado');
    }

    /**
     * Verificar si el usuario tiene una sesión activa
     */
    public function tieneSessionActiva()
    {
        // Si no hay session_id o last_activity, no hay sesión activa
        if (empty($this->session_id) || !$this->last_activity) {
            return false;
        }

        // Verificar si la sesión realmente existe en la tabla sessions de Laravel
        $sessionData = \DB::table('sessions')
            ->where('id', $this->session_id)
            ->first();

        // Si la sesión no existe en la tabla sessions, limpiar los datos
        if (!$sessionData) {
            $this->limpiarSession();
            return false;
        }

        // Verificar si la sesión ha expirado basándose en la tabla sessions (más de 15 minutos)
        $sessionLastActivity = \Carbon\Carbon::createFromTimestamp($sessionData->last_activity);
        if ($sessionLastActivity->diffInMinutes(now()) >= 15) {
            // Limpiar la sesión expirada automáticamente
            $this->limpiarSession();
            return false;
        }

        // También verificar la actividad del usuario (por si acaso)
        if ($this->last_activity->diffInMinutes(now()) >= 15) {
            // Limpiar la sesión expirada automáticamente
            $this->limpiarSession();
            return false;
        }

        return true;
    }

    /**
     * Actualizar información de sesión
     */
    public function actualizarSession($sessionId, $ip = null)
    {
        $this->update([
            'session_id' => $sessionId,
            'last_activity' => now(),
            'last_ip' => $ip ?: request()->ip()
        ]);
    }

    /**
     * Limpiar información de sesión
     */
    public function limpiarSession()
    {
        $this->update([
            'session_id' => null,
            'last_activity' => null,
            'last_ip' => null
        ]);

        // Refrescar el modelo para asegurar que los cambios se reflejen en memoria
        $this->refresh();
    }

    /**
     * Verificar si la sesión actual es diferente a la almacenada
     */
    public function esDiferenteSession($sessionId)
    {
        return $this->session_id && $this->session_id !== $sessionId;
    }
}
