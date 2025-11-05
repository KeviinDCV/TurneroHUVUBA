<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "Intentando crear turno para servicio ID 15...\n";
    
    $turno = App\Models\Turno::crear(15);
    
    echo "✓ Turno creado exitosamente!\n";
    echo "  Código: {$turno->codigo_completo}\n";
    echo "  Prioridad: {$turno->prioridad}\n";
    echo "  Servicio ID: {$turno->servicio_id}\n";
    
} catch (\Exception $e) {
    echo "✗ ERROR al crear turno:\n";
    echo "  Mensaje: {$e->getMessage()}\n";
    echo "  Archivo: {$e->getFile()}:{$e->getLine()}\n";
    echo "\nStack trace:\n{$e->getTraceAsString()}\n";
}
