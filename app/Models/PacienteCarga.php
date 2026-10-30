<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Cada vez que se carga o se elimina la base de pacientes: qué archivo, cuántos pacientes y quién lo hizo.
 */
class PacienteCarga extends Model
{
    protected $table = 'pacientes_cargas';

    protected $fillable = ['archivo', 'filas', 'total', 'estado', 'cargado_por'];
}
