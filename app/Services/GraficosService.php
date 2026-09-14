<?php

namespace App\Services;

use App\Models\CanalNoPresencialHistorial;
use App\Models\Servicio;
use App\Models\Turno;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Datos de Gráficos: TODOS los bloques salen de la misma consulta base (turnos creados en el periodo, por
 * created_at, y del servicio elegido), igual que el Inicio y Turnos. Así las cifras cuadran entre sí y con el
 * resto del panel. (fecha_creacion NO sirve: la base la reescribe en cada actualización del turno.)
 */
class GraficosService
{
    private const ATENCION = "CASE WHEN turnos.estado = 'atendido' AND turnos.duracion_atencion > 0 THEN turnos.duracion_atencion END";
    private const ESPERA = 'CASE WHEN turnos.fecha_llamado IS NOT NULL AND turnos.fecha_llamado >= turnos.created_at '
        . 'THEN TIMESTAMPDIFF(SECOND, turnos.created_at, turnos.fecha_llamado) END';
    private const TRANSFERIDO = "turnos.observaciones LIKE 'Transferido a %'";

    public function generar(Carbon $desde, Carbon $hasta, ?int $servicioId = null): array
    {
        $inicio = $desde->copy()->startOfDay();
        $fin = $hasta->copy()->endOfDay();
        $idsServicio = $this->serviciosDe($servicioId);

        $base = fn (): Builder => Turno::query()
            ->whereBetween('turnos.created_at', [$inicio, $fin])
            ->when($idsServicio !== null, fn ($q) => $q->whereIn('turnos.servicio_id', $idsServicio));

        $dias = (int) round($inicio->copy()->diffInDays($hasta->copy()->startOfDay(), true)) + 1;

        return [
            'periodo' => [
                'desde' => $inicio->toDateString(),
                'hasta' => $hasta->toDateString(),
                'dias' => $dias,
                'servicio' => $servicioId,
                'actualizado' => now()->format('H:i'),
            ],
            'umbral_espera' => (int) config('panel.umbrales.espera', 30),
            'resumen' => $this->resumen($base),
            'serie' => $this->serie($base, $inicio, $hasta, $dias),
            'esperas' => $this->esperas($base),
            'servicios' => $this->porServicio($base),
            'asesores' => $this->porAsesor($base),
            'horas' => $dias > 1 ? $this->horasPico($base) : [],
            'transferencias' => $this->transferencias($base),
            'canales' => $this->canales($inicio, $fin),
        ];
    }

    /** Un servicio y, si es una sección, sus subservicios. null = todos. */
    private function serviciosDe(?int $servicioId): ?array
    {
        if (!$servicioId) {
            return null;
        }

        return array_merge([$servicioId], Servicio::where('servicio_padre_id', $servicioId)->pluck('id')->all());
    }

    private function resumen(\Closure $base): array
    {
        $r = $base()->selectRaw("
            COUNT(*) AS total,
            SUM(turnos.estado = 'atendido') AS atendidos,
            SUM(turnos.estado = 'pendiente') AS en_espera,
            SUM(turnos.estado = 'llamado') AS en_atencion,
            SUM(turnos.estado = 'aplazado') AS aplazados,
            SUM(turnos.estado = 'cancelado') AS cancelados,
            SUM(turnos.prioridad >= 4) AS prioritarios,
            SUM(" . self::TRANSFERIDO . ') AS transferidos,
            AVG(' . self::ATENCION . ') AS atencion_seg,
            AVG(' . self::ESPERA . ') AS espera_seg
        ')->first();

        return [
            'total' => (int) $r->total,
            'atendidos' => (int) $r->atendidos,
            'en_espera' => (int) $r->en_espera,
            'en_atencion' => (int) $r->en_atencion,
            'aplazados' => (int) $r->aplazados,
            'cancelados' => (int) $r->cancelados,
            'prioritarios' => (int) $r->prioritarios,
            'transferidos' => (int) $r->transferidos,
            'atencion_seg' => $r->atencion_seg !== null ? (int) round($r->atencion_seg) : null,
            'espera_seg' => $r->espera_seg !== null ? (int) round($r->espera_seg) : null,
        ];
    }

    /**
     * Turnos en el tiempo: por hora si el periodo es un día, por día hasta 3 meses y por mes si es más largo.
     * Los días y meses sin turnos se incluyen en 0 (no se saltan); las horas se recortan a la jornada con turnos.
     */
    private function serie(\Closure $base, Carbon $inicio, Carbon $hasta, int $dias): array
    {
        [$modo, $expr] = match (true) {
            $dias === 1 => ['hora', 'HOUR(turnos.created_at)'],
            $dias <= 92 => ['dia', 'DATE(turnos.created_at)'],
            default => ['mes', "DATE_FORMAT(turnos.created_at, '%Y-%m')"],
        };

        $filas = $base()->selectRaw("{$expr} AS k, COUNT(*) AS total, SUM(turnos.estado = 'atendido') AS atendidos")
            ->groupBy('k')->orderBy('k')->get()->keyBy(fn ($f) => (string) $f->k);

        $claves = [];
        if ($modo === 'hora') {
            if ($filas->isNotEmpty()) {
                $horas = $filas->keys()->map(fn ($h) => (int) $h);
                $claves = range($horas->min(), $horas->max());
            }
        } elseif ($modo === 'dia') {
            for ($d = $inicio->copy(); $d->lte($hasta); $d->addDay()) {
                $claves[] = $d->toDateString();
            }
        } else {
            for ($d = $inicio->copy()->startOfMonth(); $d->lte($hasta); $d->addMonth()) {
                $claves[] = $d->format('Y-m');
            }
        }

        $hoy = now();
        $enCurso = match ($modo) {
            'hora' => $inicio->isSameDay($hoy) ? (string) $hoy->hour : null,
            'dia' => $hoy->toDateString(),
            default => $hoy->format('Y-m'),
        };

        return [
            'modo' => $modo,
            'puntos' => array_map(function ($k) use ($filas, $enCurso) {
                $f = $filas->get((string) $k);
                return [
                    'k' => (string) $k,
                    'total' => (int) ($f->total ?? 0),
                    'atendidos' => (int) ($f->atendidos ?? 0),
                    'en_curso' => (string) $k === $enCurso,
                ];
            }, $claves),
        ];
    }

    /** Cuánto esperaron los turnos que ya fueron llamados, por tramos. */
    private function esperas(\Closure $base): array
    {
        $e = self::ESPERA;
        $r = $base()->selectRaw("
            SUM(({$e}) < 600) AS t0,
            SUM(({$e}) >= 600 AND ({$e}) < 1200) AS t1,
            SUM(({$e}) >= 1200 AND ({$e}) < 1800) AS t2,
            SUM(({$e}) >= 1800 AND ({$e}) < 3600) AS t3,
            SUM(({$e}) >= 3600) AS t4
        ")->first();

        return [
            ['tramo' => '0–10 min', 'hasta' => 10, 'n' => (int) $r->t0],
            ['tramo' => '10–20 min', 'hasta' => 20, 'n' => (int) $r->t1],
            ['tramo' => '20–30 min', 'hasta' => 30, 'n' => (int) $r->t2],
            ['tramo' => '30–60 min', 'hasta' => 60, 'n' => (int) $r->t3],
            ['tramo' => 'Más de 1 h', 'hasta' => null, 'n' => (int) $r->t4],
        ];
    }

    private function porServicio(\Closure $base): array
    {
        $filas = $base()->selectRaw("
                turnos.servicio_id,
                COUNT(*) AS total,
                SUM(turnos.estado = 'atendido') AS atendidos,
                AVG(" . self::ATENCION . ') AS atencion_seg,
                AVG(' . self::ESPERA . ') AS espera_seg
            ')
            ->groupBy('turnos.servicio_id')->get();

        $servicios = Servicio::with('servicioPadre:id,nombre')->whereIn('id', $filas->pluck('servicio_id'))->get(['id', 'nombre', 'servicio_padre_id'])->keyBy('id');

        return $filas->map(fn ($f) => [
            'id' => (int) $f->servicio_id,
            'nombre' => $servicios->get($f->servicio_id)?->nombre ?? 'Servicio eliminado',
            'seccion' => $servicios->get($f->servicio_id)?->servicioPadre?->nombre,
            'total' => (int) $f->total,
            'atendidos' => (int) $f->atendidos,
            'atencion_seg' => $f->atencion_seg !== null ? (int) round($f->atencion_seg) : null,
            'espera_seg' => $f->espera_seg !== null ? (int) round($f->espera_seg) : null,
        ])->sortByDesc('total')->values()->all();
    }

    private function porAsesor(\Closure $base): array
    {
        $filas = $base()->whereNotNull('turnos.asesor_id')
            ->selectRaw("
                turnos.asesor_id,
                SUM(turnos.estado = 'atendido') AS atendidos,
                SUM(" . self::TRANSFERIDO . ') AS transferidos,
                AVG(' . self::ATENCION . ') AS atencion_seg
            ')
            ->groupBy('turnos.asesor_id')->get();

        $nombres = User::whereIn('id', $filas->pluck('asesor_id'))->get(['id', 'nombre_completo', 'nombre_usuario'])->keyBy('id');

        return $filas->map(fn ($f) => [
            'id' => (int) $f->asesor_id,
            'nombre' => $nombres->get($f->asesor_id)?->nombre_completo ?: ($nombres->get($f->asesor_id)?->nombre_usuario ?? 'Usuario eliminado'),
            'atendidos' => (int) $f->atendidos,
            'transferidos' => (int) $f->transferidos,
            'atencion_seg' => $f->atencion_seg !== null ? (int) round($f->atencion_seg) : null,
        ])->filter(fn ($a) => $a['atendidos'] > 0)->sortByDesc('atendidos')->values()->all();
    }

    /** Promedio de turnos que llegan en cada hora, contando solo los días con atención. */
    private function horasPico(\Closure $base): array
    {
        $diasConTurnos = (int) $base()->selectRaw('COUNT(DISTINCT DATE(turnos.created_at)) AS n')->value('n');
        if ($diasConTurnos === 0) {
            return [];
        }

        return $base()->selectRaw('HOUR(turnos.created_at) AS h, COUNT(*) AS total')
            ->groupBy('h')->orderBy('h')->get()
            ->map(fn ($f) => ['hora' => (int) $f->h, 'promedio' => round($f->total / $diasConTurnos, 1)])
            ->values()->all();
    }

    /** Transferencias salientes (el turno de origen dice "Transferido a <servicio>"). */
    private function transferencias(\Closure $base): array
    {
        $filas = $base()->whereRaw(self::TRANSFERIDO)
            ->selectRaw('turnos.servicio_id, turnos.observaciones, COUNT(*) AS n')
            ->groupBy('turnos.servicio_id', 'turnos.observaciones')->get();

        if ($filas->isEmpty()) {
            return ['total' => 0, 'rutas' => []];
        }

        $nombres = Servicio::whereIn('id', $filas->pluck('servicio_id'))->pluck('nombre', 'id');
        $rutas = $filas->groupBy(fn ($f) => $f->servicio_id . '|' . trim(substr($f->observaciones, strlen('Transferido a '))))
            ->map(function ($grupo, $clave) use ($nombres) {
                [$origen, $destino] = explode('|', $clave, 2);
                return ['desde' => $nombres[$origen] ?? 'Servicio eliminado', 'hacia' => $destino, 'n' => (int) $grupo->sum('n')];
            })->sortByDesc('n')->values();

        return ['total' => (int) $rutas->sum('n'), 'rutas' => $rutas->all()];
    }

    /** Tiempo en canales no presenciales por asesor (no depende del servicio elegido). */
    private function canales(Carbon $inicio, Carbon $fin): array
    {
        try {
            $filas = CanalNoPresencialHistorial::query()
                ->whereBetween('inicio', [$inicio, $fin])
                ->selectRaw('user_id, COUNT(*) AS actividades, SUM(COALESCE(duracion_minutos, 0)) AS minutos')
                ->groupBy('user_id')->get();
        } catch (\Throwable $e) {
            report($e);
            return ['total_minutos' => 0, 'asesores' => []];
        }

        $nombres = User::whereIn('id', $filas->pluck('user_id'))->pluck('nombre_completo', 'id');

        return [
            'total_minutos' => (int) $filas->sum('minutos'),
            'asesores' => $filas->map(fn ($f) => [
                'nombre' => $nombres[$f->user_id] ?? 'Usuario eliminado',
                'actividades' => (int) $f->actividades,
                'minutos' => (int) $f->minutos,
            ])->sortByDesc('minutos')->values()->all(),
        ];
    }
}
