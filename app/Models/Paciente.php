<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Base de pacientes del atril: documento y nombre, nada más. Se carga desde Servicios (PacienteController).
 * En un servicio que pide cédula, solo se da turno a quien esté aquí; su nombre se muestra en pantalla y no se imprime.
 */
class Paciente extends Model
{
    protected $table = 'pacientes';
    protected $primaryKey = 'documento';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['documento', 'nombre', 'carga_id'];

    /**
     * Si la tabla ya existe: el código puede subirse al servidor antes de correr la migración.
     * Solo se recuerda el sí, para que tras migrar funcione de inmediato.
     */
    public static function soportada(): bool
    {
        if (Cache::get('pacientes_tabla')) {
            return true;
        }

        $existe = Schema::hasTable('pacientes');
        if ($existe) {
            Cache::put('pacientes_tabla', true, 3600);
        }

        return $existe;
    }

    /**
     * Si hay base cargada. Sin base, el atril pide la cédula y la guarda sin comprobarla.
     */
    public static function hayBase(): bool
    {
        return self::soportada() && self::query()->exists();
    }

    /**
     * Las IP o rangos de los atriles (ATRIL_IPS en el .env). Vacío: sin restricción.
     */
    public static function ipsDeAtriles(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) config('panel.atril_ips')))));
    }

    /**
     * Si este equipo puede consultar la base. El atril es público y una cédula devuelve un nombre.
     */
    public static function equipoAutorizado(?string $ip): bool
    {
        $ips = self::ipsDeAtriles();

        return !$ips || IpUtils::checkIp((string) $ip, $ips);
    }

    /**
     * El documento tal como se guarda y se busca: solo dígitos y sin ceros a la izquierda (Excel los quita si la
     * columna es numérica). null si no se puede escribir en el teclado del atril.
     */
    public static function claveDocumento($valor): ?string
    {
        $crudo = trim((string) $valor);
        if (!preg_match('/^\d{4,15}$/', $crudo)) {
            return null;
        }

        $clave = ltrim($crudo, '0');

        return strlen($clave) >= 4 ? $clave : null;
    }

    /**
     * El nombre listo para guardar: sin caracteres de control ni espacios de más. null si queda vacío.
     */
    public static function limpiarNombre($valor): ?string
    {
        if (!is_string($valor)) {
            return null;
        }

        $nombre = trim(preg_replace('/\s+/u', ' ', preg_replace('/[\x00-\x1F\x7F]/u', ' ', $valor) ?? '') ?? '');

        return $nombre === '' ? null : mb_substr($nombre, 0, 150);
    }

    /**
     * El nombre de quien tiene ese documento, o null si no está en la base.
     */
    public static function nombreDe(string $documento): ?string
    {
        $clave = ltrim($documento, '0');

        return $clave === '' ? null : self::query()->whereKey($clave)->value('nombre');
    }

    /**
     * Lo que muestra Servicios sobre la base; null si aún no se ha corrido la migración.
     */
    public static function resumen(): ?array
    {
        if (!self::soportada()) {
            return null;
        }

        $total = self::query()->count();
        $carga = $total > 0 ? PacienteCarga::where('estado', 'lista')->latest('id')->first() : null;

        return [
            'total' => $total,
            'archivo' => $carga?->archivo,
            'cargado_por' => $carga?->cargado_por,
            'fecha' => $carga?->updated_at?->copy()->timezone('America/Bogota')->toIso8601String(),
        ];
    }
}
