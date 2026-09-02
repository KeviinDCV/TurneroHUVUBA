<?php
/* =====================================================================
   TIEMPO DE ESPERA REAL  -  TURNERO HUV

   Uso en cPanel, en la carpeta del proyecto:
       php espera.php > espera.txt

   Por que este script existe:
   `fecha_creacion` esta corrupta (MySQL la reescribe por el ON UPDATE),
   pero `created_at` conserva la hora real del INSERT y NO tiene ON UPDATE.
   Por eso el tiempo de espera si se puede reconstruir: created_at -> fecha_llamado.

   Solo ejecuta SELECT. No modifica nada.
   ===================================================================== */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

function q($s) { return \Illuminate\Support\Facades\DB::select($s); }
function sec($t) { echo "\n\n===== {$t} =====\n"; }
function tabla($filas) {
    $filas = array_map(fn($f) => (array) $f, $filas);
    if (!$filas) { echo "(sin datos)\n"; return; }
    $cols = array_keys($filas[0]); $w = [];
    foreach ($cols as $c) { $w[$c] = strlen($c);
        foreach ($filas as $f) $w[$c] = max($w[$c], strlen((string) $f[$c])); }
    $l = fn($f) => implode(' | ', array_map(fn($c) => str_pad((string) $f[$c], $w[$c]), $cols));
    echo $l(array_combine($cols, $cols)) . "\n";
    echo implode('-+-', array_map(fn($c) => str_repeat('-', $w[$c]), $cols)) . "\n";
    foreach ($filas as $f) echo $l($f) . "\n";
}

echo "TIEMPO DE ESPERA - " . config('app.name') . "\n";
echo "Base: " . config('database.connections.mysql.database') . "  |  " . date('Y-m-d H:i:s') . "\n";

sec('0. COBERTURA DE created_at');
tabla(q("SELECT COUNT(*) total_turnos,
  SUM(created_at IS NOT NULL) con_created_at,
  SUM(fecha_llamado IS NOT NULL) con_fecha_llamado,
  SUM(created_at IS NOT NULL AND fecha_llamado IS NOT NULL) medibles
  FROM turnos"));

sec('1. PRUEBA DEL DESFASE (fecha_creacion vs created_at)');
tabla(q("SELECT ROUND(AVG(TIMESTAMPDIFF(SECOND, created_at, fecha_creacion)),1) desfase_promedio_seg,
  MIN(TIMESTAMPDIFF(SECOND, created_at, fecha_creacion)) minimo,
  MAX(TIMESTAMPDIFF(SECOND, created_at, fecha_creacion)) maximo
  FROM turnos WHERE created_at IS NOT NULL"));

sec('2. TIEMPO DE ESPERA REAL (created_at -> fecha_llamado)');
tabla(q("SELECT COUNT(*) turnos_medidos,
  ROUND(AVG(TIMESTAMPDIFF(SECOND, created_at, fecha_llamado)),1) promedio_seg,
  MIN(TIMESTAMPDIFF(SECOND, created_at, fecha_llamado)) minimo_seg,
  MAX(TIMESTAMPDIFF(SECOND, created_at, fecha_llamado)) maximo_seg,
  ROUND(STDDEV(TIMESTAMPDIFF(SECOND, created_at, fecha_llamado)),1) desviacion_seg
  FROM turnos
  WHERE created_at IS NOT NULL AND fecha_llamado IS NOT NULL
    AND TIMESTAMPDIFF(SECOND, created_at, fecha_llamado) BETWEEN 0 AND 43200"));

sec('3. DISTRIBUCION DE LA ESPERA');
tabla(q("SELECT CASE
    WHEN d < 300 THEN 'a) menos de 5 min'
    WHEN d < 900 THEN 'b) 5 a 15 min'
    WHEN d < 1800 THEN 'c) 15 a 30 min'
    WHEN d < 3600 THEN 'd) 30 a 60 min'
    WHEN d < 7200 THEN 'e) 1 a 2 horas'
    ELSE 'f) mas de 2 horas' END franja,
  COUNT(*) turnos,
  ROUND(100*COUNT(*)/(SELECT COUNT(*) FROM turnos
     WHERE created_at IS NOT NULL AND fecha_llamado IS NOT NULL
       AND TIMESTAMPDIFF(SECOND, created_at, fecha_llamado) BETWEEN 0 AND 43200),1) porcentaje
  FROM (SELECT TIMESTAMPDIFF(SECOND, created_at, fecha_llamado) d FROM turnos
        WHERE created_at IS NOT NULL AND fecha_llamado IS NOT NULL
          AND TIMESTAMPDIFF(SECOND, created_at, fecha_llamado) BETWEEN 0 AND 43200) t
  GROUP BY franja ORDER BY franja"));

sec('4. ESPERA POR SERVICIO');
tabla(q("SELECT s.nombre servicio, COUNT(*) turnos,
  ROUND(AVG(TIMESTAMPDIFF(SECOND, t.created_at, t.fecha_llamado)),0) espera_prom_seg,
  MAX(TIMESTAMPDIFF(SECOND, t.created_at, t.fecha_llamado)) espera_max_seg
  FROM turnos t JOIN servicios s ON s.id = t.servicio_id
  WHERE t.created_at IS NOT NULL AND t.fecha_llamado IS NOT NULL
    AND TIMESTAMPDIFF(SECOND, t.created_at, t.fecha_llamado) BETWEEN 0 AND 43200
  GROUP BY s.id, s.nombre ORDER BY turnos DESC"));

sec('5. ESPERA POR FRANJA HORARIA (hora real de solicitud)');
tabla(q("SELECT HOUR(created_at) hora, COUNT(*) turnos,
  ROUND(AVG(TIMESTAMPDIFF(SECOND, created_at, fecha_llamado)),0) espera_prom_seg
  FROM turnos
  WHERE created_at IS NOT NULL AND fecha_llamado IS NOT NULL
    AND TIMESTAMPDIFF(SECOND, created_at, fecha_llamado) BETWEEN 0 AND 43200
  GROUP BY hora ORDER BY hora"));

echo "\n\n=== FIN ===\n";
