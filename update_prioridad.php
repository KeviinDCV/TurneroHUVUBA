<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Actualizar servicios sin prioridad
$updated = DB::table('servicios')->whereNull('prioridad')->update(['prioridad' => 3]);
echo "Servicios actualizados: $updated\n";

// Verificar cuántos servicios tienen prioridad NULL
$nullCount = DB::table('servicios')->whereNull('prioridad')->count();
echo "Servicios con prioridad NULL: $nullCount\n";

// Mostrar todos los servicios con su prioridad
$servicios = DB::table('servicios')->select('id', 'nombre', 'prioridad')->get();
echo "\nServicios actuales:\n";
foreach ($servicios as $servicio) {
    echo "  ID: {$servicio->id} | {$servicio->nombre} | Prioridad: {$servicio->prioridad}\n";
}
