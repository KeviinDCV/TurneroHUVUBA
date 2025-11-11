<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Multimedia extends Model
{
    protected $table = 'multimedia';

    protected $fillable = [
        'nombre',
        'archivo',
        'tipo',
        'extension',
        'orden',
        'duracion',
        'activo',
        'tamaño'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
        'duracion' => 'integer',
        'tamaño' => 'integer',
    ];

    /**
     * Obtener multimedia activa ordenada
     */
    public static function getActiveOrdered()
    {
        return self::where('activo', true)
            ->orderBy('orden')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Obtener URL completa del archivo
     * 
     * Genera la URL del archivo de forma robusta para que funcione tanto en local
     * como en cPanel (donde el symlink puede no funcionar correctamente)
     */
    public function getUrlAttribute()
    {
        // Verificar si el archivo existe en public/storage (symlink funcionando)
        $publicPath = public_path('storage/' . $this->archivo);
        
        if (file_exists($publicPath)) {
            // El symlink funciona correctamente (típico en local)
            return asset('storage/' . $this->archivo);
        }
        
        // Si no existe vía symlink, intentar acceso directo
        // Esto es necesario en algunos servidores cPanel donde el symlink no funciona
        $storagePath = storage_path('app/public/' . $this->archivo);
        
        if (file_exists($storagePath)) {
            // Generar URL directa al script de servir archivos
            // Usamos una ruta especial que sirve archivos desde storage
            return url('multimedia/serve/' . base64_encode($this->archivo));
        }
        
        // Fallback: intentar con Storage::url()
        return Storage::url($this->archivo);
    }

    /**
     * Verificar si es una imagen
     */
    public function esImagen()
    {
        return $this->tipo === 'imagen';
    }

    /**
     * Verificar si es un video
     */
    public function esVideo()
    {
        return $this->tipo === 'video';
    }

    /**
     * Obtener el siguiente orden disponible
     */
    public static function getNextOrder()
    {
        $maxOrder = self::max('orden');
        return $maxOrder ? $maxOrder + 1 : 1;
    }

    /**
     * Formatear tamaño del archivo
     */
    public function getTamañoFormateadoAttribute()
    {
        if (!$this->tamaño) return 'N/A';

        $bytes = $this->tamaño;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
