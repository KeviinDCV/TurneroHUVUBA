<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Quita el "ON UPDATE current_timestamp()" de turnos.fecha_creacion.
 *
 * EL PROBLEMA
 * La migracion original declaraba `$table->timestamp('fecha_creacion')` sin
 * nullable() ni default. En MySQL/MariaDB, cuando `explicit_defaults_for_timestamp`
 * esta desactivado, la PRIMERA columna TIMESTAMP de una tabla recibe de forma
 * automatica `DEFAULT current_timestamp() ON UPDATE current_timestamp()`.
 *
 * Resultado: cada vez que el turno se actualizaba (al llamarlo y al atenderlo),
 * MySQL reescribia `fecha_creacion` con su propio reloj. La columna dejaba de
 * contener la hora de creacion y pasaba a contener la de la ultima modificacion.
 *
 * SINTOMAS QUE PRODUCIA
 *  1. En /admin/turnos la columna CREADO aparecia 1 hora adelantada respecto a
 *     LLAMADO y ATENDIDO, con los segundos identicos: las escribian dos relojes
 *     distintos en el mismo instante (PHP en America/Bogota vs. el servidor MySQL).
 *  2. El tiempo de espera era imposible de calcular: `fecha_llamado - fecha_creacion`
 *     daba negativo en practicamente todos los registros.
 *
 * LA CORRECCION
 * Se redefine la columna conservando NOT NULL y el DEFAULT, pero SIN el ON UPDATE.
 * Se usa SQL directo porque `->change()` de Laravel no controla esta clausula.
 * Es un cambio de metadatos: no reescribe filas y es inmediato incluso con
 * cientos de miles de registros.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return; // solo aplica a MySQL/MariaDB
        }

        DB::statement(
            'ALTER TABLE `turnos`
             MODIFY `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Restaura el comportamiento anterior (no recomendado: reintroduce el bug)
        DB::statement(
            'ALTER TABLE `turnos`
             MODIFY `fecha_creacion` TIMESTAMP NOT NULL
             DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        );
    }
};
