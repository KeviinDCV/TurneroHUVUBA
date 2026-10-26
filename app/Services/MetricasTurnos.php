<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Reglas de tiempo de los informes (Excel, PDF y estadísticas del asesor), en un solo sitio para que todos digan lo mismo.
 *
 * Duración de una atención: la del cronómetro del panel del asesor (duracion_atencion), salvo cuando no sirve:
 *   - negativa: el reloj del equipo del asesor cambió durante la atención;
 *   - más corta que el tiempo que el turno estuvo abierto en el servidor (fecha_llamado → fecha_atencion): la página se
 *     recargó y el cronómetro volvió a cero.
 *   En esos casos se usa el tiempo del servidor. Si el cronómetro es más largo que el del servidor se respeta: «Volver a
 *   llamar» mueve fecha_llamado, pero el cronómetro sigue contando desde el primer llamado.
 *
 * Atenciones de más de ATENCION_MAXIMA_SEG: el turno quedó abierto (en los datos casi todas se cierran a la hora del
 * almuerzo). Cuentan como atendidas, pero no entran en promedios ni en totales de tiempo; se informan aparte.
 *
 * Tiempo entre turnos: desde que el asesor cierra una atención hasta que empieza la siguiente, solo dentro del mismo día
 * (las noches y los fines de semana no son tiempo entre turnos). Los ratos de más de PAUSA_LARGA_SEG (almuerzo, reuniones)
 * se informan aparte como pausas largas, y el tiempo en canales no presenciales se descuenta porque es trabajo (salvo una
 * actividad que quedó abierta de un día para otro: no se sabe cuánto duró). Incluye los ratos en que no había pacientes
 * esperando: el sistema no guarda la cola de cada momento.
 */
class MetricasTurnos
{
    public const ATENCION_MAXIMA_SEG = 3600;
    public const PAUSA_LARGA_SEG = 3600;

    /** Diferencia que se tolera entre el cronómetro y el reloj del servidor antes de desconfiar del cronómetro. */
    private const HOLGURA_SEG = 60;

    /** Segundos que duró la atención de un turno atendido, o null si no hay con qué medirla. */
    public static function duracion($turno): ?int
    {
        $servidor = null;
        if ($turno->fecha_llamado && $turno->fecha_atencion) {
            $servidor = Carbon::parse($turno->fecha_atencion)->getTimestamp() - Carbon::parse($turno->fecha_llamado)->getTimestamp();
            if ($servidor < 0) {
                $servidor = null;
            }
        }

        $cronometro = $turno->duracion_atencion;
        if ($cronometro === null || $cronometro === '' || (int) $cronometro < 0) {
            return $servidor;
        }
        $cronometro = (int) $cronometro;

        if ($servidor !== null && $cronometro < $servidor - self::HOLGURA_SEG) {
            return $servidor;
        }

        return $cronometro;
    }

    /**
     * Segundos de una actividad en canal no presencial, o null si quedó abierta de un día para otro: no se sabe cuánto
     * duró de verdad, así que no se suma ni se descuenta del tiempo entre turnos.
     */
    public static function duracionCanal($actividad): ?int
    {
        if ($actividad->inicio && $actividad->fin) {
            $inicio = Carbon::parse($actividad->inicio);
            $fin = Carbon::parse($actividad->fin);
            if (!$inicio->isSameDay($fin) || $fin->lessThan($inicio)) {
                return null;
            }

            return $fin->getTimestamp() - $inicio->getTimestamp();
        }

        return $actividad->duracion_minutos !== null ? (int) $actividad->duracion_minutos * 60 : null;
    }

    /** La atención duró tanto que el turno quedó abierto: no se promedia. */
    public static function esAtipica(?int $segundos): bool
    {
        return $segundos !== null && $segundos > self::ATENCION_MAXIMA_SEG;
    }

    /**
     * Tiempos de atención de un grupo de turnos (solo cuenta los atendidos).
     * promedio y total en segundos, sin las atenciones atípicas ni las que no tienen dato.
     */
    public static function atencion(Collection $turnos): array
    {
        $medidas = [];
        $atipicas = 0;
        $sinDato = 0;

        foreach ($turnos as $turno) {
            if ($turno->estado !== 'atendido') {
                continue;
            }
            $segundos = self::duracion($turno);
            if ($segundos === null) {
                $sinDato++;
            } elseif (self::esAtipica($segundos)) {
                $atipicas++;
            } else {
                $medidas[] = $segundos;
            }
        }

        return [
            'promedio' => $medidas ? array_sum($medidas) / count($medidas) : null,
            'total' => array_sum($medidas),
            'medidas' => count($medidas),
            'atipicas' => $atipicas,
            'sin_dato' => $sinDato,
        ];
    }

    /**
     * Tiempo entre turnos de UN asesor. $turnos: sus turnos del periodo (solo se usan los atendidos);
     * $canales: sus actividades de canal no presencial (inicio, fin) del mismo periodo, si las hay.
     */
    public static function entreTurnos(Collection $turnos, ?Collection $canales = null): array
    {
        // Cada atención como intervalo [inicio, fin] en segundos.
        $atenciones = [];
        foreach ($turnos as $turno) {
            if ($turno->estado !== 'atendido' || !$turno->fecha_atencion) {
                continue;
            }
            $fin = Carbon::parse($turno->fecha_atencion);
            $segundos = self::duracion($turno);
            if ($segundos !== null && !self::esAtipica($segundos)) {
                $inicio = $fin->getTimestamp() - $segundos;
            } elseif ($turno->fecha_llamado) {
                $inicio = Carbon::parse($turno->fecha_llamado)->getTimestamp();
            } else {
                $inicio = $fin->getTimestamp();
            }
            $atenciones[] = ['inicio' => min($inicio, $fin->getTimestamp()), 'fin' => $fin->getTimestamp()];
        }
        usort($atenciones, fn ($a, $b) => $a['fin'] <=> $b['fin'] ?: $a['inicio'] <=> $b['inicio']);

        $tramosCanal = [];
        foreach ($canales ?? [] as $canal) {
            if ($canal->inicio && $canal->fin && self::duracionCanal($canal) !== null) {
                $tramosCanal[] = [Carbon::parse($canal->inicio)->getTimestamp(), Carbon::parse($canal->fin)->getTimestamp()];
            }
        }
        // Se unen los tramos que se solapan (un «finalizar» repetido): si no, ese rato se descontaría dos veces.
        usort($tramosCanal, fn ($a, $b) => $a[0] <=> $b[0]);
        $unidos = [];
        foreach ($tramosCanal as [$canalInicio, $canalFin]) {
            $ultimo = count($unidos) - 1;
            if ($ultimo >= 0 && $canalInicio <= $unidos[$ultimo][1]) {
                $unidos[$ultimo][1] = max($unidos[$ultimo][1], $canalFin);
            } else {
                $unidos[] = [$canalInicio, $canalFin];
            }
        }
        $tramosCanal = $unidos;

        $total = 0;
        $intervalos = 0;
        $pausas = 0;
        $pausasTotal = 0;

        for ($i = 1; $i < count($atenciones); $i++) {
            $desde = $atenciones[$i - 1]['fin'];
            $hasta = $atenciones[$i]['inicio'];

            // Solo dentro del mismo día: de un día a otro no hay "tiempo entre turnos".
            if (date('Y-m-d', $desde) !== date('Y-m-d', $hasta)) {
                continue;
            }

            $rato = max(0, $hasta - $desde);
            foreach ($tramosCanal as [$canalInicio, $canalFin]) {
                $rato -= max(0, min($hasta, $canalFin) - max($desde, $canalInicio));
            }
            $rato = max(0, $rato);

            if ($rato > self::PAUSA_LARGA_SEG) {
                $pausas++;
                $pausasTotal += $rato;
            } else {
                $total += $rato;
                $intervalos++;
            }
        }

        return [
            'promedio' => $intervalos ? $total / $intervalos : null,
            'total' => $total,
            'intervalos' => $intervalos,
            'pausas' => $pausas,
            'pausas_total' => $pausasTotal,
        ];
    }

    /** "4 minutos 46 segundos", "1 hora 20 minutos"; desde una hora no se muestran los segundos. */
    public static function texto($segundos): string
    {
        if ($segundos === null || $segundos === '' || !is_numeric($segundos)) {
            return 'N/A';
        }

        $segundos = (int) round(max(0, (float) $segundos));
        if ($segundos === 0) {
            return '0 segundos';
        }

        $horas = intdiv($segundos, 3600);
        $minutos = intdiv($segundos % 3600, 60);
        $resto = $segundos % 60;

        $partes = [];
        if ($horas > 0) {
            $partes[] = $horas . ($horas === 1 ? ' hora' : ' horas');
        }
        if ($minutos > 0) {
            $partes[] = $minutos . ($minutos === 1 ? ' minuto' : ' minutos');
        }
        if ($resto > 0 && $horas === 0) {
            $partes[] = $resto . ($resto === 1 ? ' segundo' : ' segundos');
        }

        return implode(' ', $partes) ?: '0 segundos';
    }
}
