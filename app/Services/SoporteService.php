<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

/**
 * Solicitudes de soporte guardadas en el servidor (storage/app/soporte/solicitudes.json), sin tabla ni migración.
 * Antes solo se escribían en laravel.log y nadie las veía. Son pocas: un archivo JSON con bloqueo basta, y las
 * capturas van a storage/app/soporte/adjuntos/ (fuera de public: se sirven por una ruta del panel).
 */
class SoporteService
{
    public const ESTADOS = ['nueva', 'en_curso', 'resuelta'];

    public function dir(): string
    {
        return storage_path('app/soporte');
    }

    /** Todas, de la más reciente a la más antigua. */
    public function listar(): array
    {
        return $this->leer()['solicitudes'];
    }

    public function buscar(int $id): ?array
    {
        foreach ($this->listar() as $s) {
            if ((int) $s['id'] === $id) {
                return $s;
            }
        }
        return null;
    }

    /** Registra una solicitud y le da su número (S-0001, S-0002…). */
    public function crear(array $datos): array
    {
        return $this->conBloqueo(function (array &$bd) use ($datos) {
            $id = (int) $bd['siguiente'];
            $bd['siguiente'] = $id + 1;
            $s = $datos + [
                'id' => $id,
                'numero' => sprintf('S-%04d', $id),
                'estado' => 'nueva',
                'nota' => null,
                'adjunto' => null,
                'creada' => now()->toIso8601String(),
                'actualizada' => null,
                'actualizada_por' => null,
            ];
            array_unshift($bd['solicitudes'], $s);
            return $s;
        });
    }

    /** Cambia campos de una solicitud (estado, nota, adjunto…). */
    public function actualizar(int $id, array $cambios): ?array
    {
        return $this->conBloqueo(function (array &$bd) use ($id, $cambios) {
            foreach ($bd['solicitudes'] as $i => $s) {
                if ((int) $s['id'] === $id) {
                    $bd['solicitudes'][$i] = array_merge($s, $cambios);
                    return $bd['solicitudes'][$i];
                }
            }
            return null;
        });
    }

    public function rutaAdjunto(string $nombre): string
    {
        return $this->dir() . '/adjuntos/' . basename($nombre);
    }

    private function leer(): array
    {
        $archivo = $this->dir() . '/solicitudes.json';
        $bd = is_file($archivo) ? json_decode((string) file_get_contents($archivo), true) : null;

        return is_array($bd) && isset($bd['solicitudes'], $bd['siguiente']) ? $bd : ['siguiente' => 1, 'solicitudes' => []];
    }

    /** Lee, cambia y escribe con el archivo bloqueado (dos envíos a la vez no se pisan ni repiten número). */
    private function conBloqueo(callable $cambio)
    {
        File::ensureDirectoryExists($this->dir());
        $bloqueo = fopen($this->dir() . '/.bloqueo', 'c');
        flock($bloqueo, LOCK_EX);

        try {
            $bd = $this->leer();
            $resultado = $cambio($bd);
            $temporal = $this->dir() . '/solicitudes.json.tmp';
            file_put_contents($temporal, json_encode($bd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            rename($temporal, $this->dir() . '/solicitudes.json');
            return $resultado;
        } finally {
            flock($bloqueo, LOCK_UN);
            fclose($bloqueo);
        }
    }
}
