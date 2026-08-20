<?php
/* =====================================================================
   EXTRACCION DE METRICAS - TURNERO HUV
   Uso en cPanel (Terminal), en la carpeta del proyecto:

       php metricas.php > metricas.txt

   (NO usar "artisan tinker": tinker abre una sesion interactiva y se
    queda esperando Ctrl+D cuando se redirige la salida a un archivo.)

   Solo ejecuta SELECT. No modifica ni borra nada.
   ===================================================================== */

// --- Arrancar Laravel sin tinker -------------------------------------
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// --- Utilidades de salida --------------------------------------------
function seccion($t) { echo "\n\n===== {$t} =====\n"; }

function q($sql) { return \Illuminate\Support\Facades\DB::select($sql); }

function tabla($filas) {
    $filas = array_map(fn($f) => (array) $f, $filas);
    if (!$filas) { echo "(sin datos)\n"; return; }
    $cols = array_keys($filas[0]);
    $w = [];
    foreach ($cols as $c) {
        $w[$c] = strlen($c);
        foreach ($filas as $f) { $w[$c] = max($w[$c], strlen((string) $f[$c])); }
    }
    $linea = fn($f) => implode(' | ', array_map(fn($c) => str_pad((string) $f[$c], $w[$c]), $cols));
    echo $linea(array_combine($cols, $cols)) . "\n";
    echo implode('-+-', array_map(fn($c) => str_repeat('-', $w[$c]), $cols)) . "\n";
    foreach ($filas as $f) { echo $linea($f) . "\n"; }
}

// --- Informe ----------------------------------------------------------
echo "INFORME DE METRICAS - " . config('app.name') . "\n";
echo "Base de datos: " . config('database.connections.mysql.database') . "\n";
echo "Generado: " . date('Y-m-d H:i:s') . "\n";

seccion('1. VENTANA DE DATOS');
tabla(q("SELECT MIN(fecha_creacion) primer_turno, MAX(fecha_creacion) ultimo_turno,
  COUNT(*) total_turnos, COUNT(DISTINCT DATE(fecha_creacion)) dias_operacion FROM turnos"));

seccion('2. TURNOS POR ESTADO');
tabla(q("SELECT estado, COUNT(*) cantidad,
  ROUND(100*COUNT(*)/(SELECT COUNT(*) FROM turnos),2) porcentaje
  FROM turnos GROUP BY estado ORDER BY cantidad DESC"));

seccion('3. VOLUMEN POR MES');
tabla(q("SELECT DATE_FORMAT(fecha_creacion,'%Y-%m') mes, COUNT(*) turnos,
  SUM(estado='atendido') atendidos, COUNT(DISTINCT DATE(fecha_creacion)) dias,
  ROUND(COUNT(*)/COUNT(DISTINCT DATE(fecha_creacion)),1) promedio_dia
  FROM turnos GROUP BY mes ORDER BY mes"));

seccion('4. TIEMPO DE ATENCION (segundos)');
tabla(q("SELECT COUNT(*) turnos_medidos, ROUND(AVG(ABS(duracion_atencion)),1) promedio,
  MIN(ABS(duracion_atencion)) minimo, MAX(ABS(duracion_atencion)) maximo,
  ROUND(STDDEV(ABS(duracion_atencion)),1) desviacion
  FROM turnos WHERE estado='atendido' AND duracion_atencion IS NOT NULL AND duracion_atencion<>0"));

seccion('5. TIEMPO DE ESPERA (segundos)');
tabla(q("SELECT COUNT(*) turnos_medidos,
  ROUND(AVG(TIMESTAMPDIFF(SECOND,fecha_creacion,fecha_llamado)),1) promedio,
  MAX(TIMESTAMPDIFF(SECOND,fecha_creacion,fecha_llamado)) maximo
  FROM turnos WHERE fecha_llamado IS NOT NULL
  AND TIMESTAMPDIFF(SECOND,fecha_creacion,fecha_llamado) BETWEEN 0 AND 43200"));

seccion('6. POR SERVICIO');
tabla(q("SELECT s.nombre servicio, COUNT(t.id) total, SUM(t.estado='atendido') atendidos,
  ROUND(100*SUM(t.estado='atendido')/COUNT(t.id),1) tasa_atencion,
  ROUND(AVG(CASE WHEN t.estado='atendido' THEN ABS(t.duracion_atencion) END),0) atencion_seg
  FROM turnos t JOIN servicios s ON s.id=t.servicio_id
  GROUP BY s.id,s.nombre ORDER BY total DESC"));

seccion('7. PRODUCTIVIDAD POR ASESOR (top 20)');
tabla(q("SELECT u.nombre_completo asesor, COUNT(t.id) atendidos,
  COUNT(DISTINCT DATE(t.fecha_creacion)) dias, ROUND(COUNT(t.id)/COUNT(DISTINCT DATE(t.fecha_creacion)),1) prom_dia,
  ROUND(AVG(ABS(t.duracion_atencion)),0) atencion_seg
  FROM turnos t JOIN users u ON u.id=t.asesor_id WHERE t.estado='atendido'
  GROUP BY u.id,u.nombre_completo ORDER BY atendidos DESC LIMIT 20"));

seccion('8. DISTRIBUCION HORARIA');
tabla(q("SELECT HOUR(fecha_creacion) hora, COUNT(*) turnos FROM turnos GROUP BY hora ORDER BY hora"));

seccion('9. PRIORIDAD');
tabla(q("SELECT prioridad, COUNT(*) cantidad FROM turnos GROUP BY prioridad ORDER BY prioridad"));

seccion('10. TRANSFERENCIAS');
tabla(q("SELECT SUM(observaciones LIKE 'Transferido a%') salientes,
  SUM(observaciones LIKE 'Transferido desde%') recibidos, COUNT(*) total FROM turnos"));

seccion('11. INFRAESTRUCTURA');
tabla(q("SELECT (SELECT COUNT(*) FROM servicios) servicios,
  (SELECT COUNT(*) FROM servicios WHERE estado='activo') serv_activos,
  (SELECT COUNT(*) FROM cajas) puntos_atencion,
  (SELECT COUNT(*) FROM users WHERE rol='Asesor') asesores,
  (SELECT COUNT(*) FROM users WHERE rol='Administrador') administradores"));

seccion('12. DIAS PICO');
tabla(q("SELECT DATE(fecha_creacion) dia, COUNT(*) turnos FROM turnos
  GROUP BY dia ORDER BY turnos DESC LIMIT 5"));

echo "\n\n=== FIN ===\n";
