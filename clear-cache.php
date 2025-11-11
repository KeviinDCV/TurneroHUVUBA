<?php
/**
 * Script para limpiar cache de Laravel
 * IMPORTANTE: Eliminar este archivo después de usarlo por seguridad
 */

echo "<!DOCTYPE html>
<html>
<head>
    <title>Limpiar Cache Laravel</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        h1 { color: #064b9e; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .success { color: #10b981; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        .warning { color: #f59e0b; font-weight: bold; }
        .command { color: #3b82f6; }
    </style>
</head>
<body>
    <h1>🔄 Limpiando Cache de Laravel</h1>
    <p>Este script limpiará todo el cache para aplicar los cambios del middleware CSRF.</p>
    <pre>";

$commands = [
    'php artisan config:clear' => 'Limpiar cache de configuración',
    'php artisan cache:clear' => 'Limpiar cache de aplicación',
    'php artisan route:clear' => 'Limpiar cache de rutas',
    'php artisan view:clear' => 'Limpiar cache de vistas',
    'php artisan optimize:clear' => 'Limpiar cache optimizado',
];

$allSuccess = true;

foreach ($commands as $command => $description) {
    echo "<span class='command'>🔄 {$description}...</span>\n";
    echo "   Ejecutando: <strong>{$command}</strong>\n";
    
    $output = [];
    $returnCode = 0;
    
    exec($command . ' 2>&1', $output, $returnCode);
    
    if ($returnCode === 0) {
        echo "<span class='success'>   ✅ Completado exitosamente</span>\n\n";
    } else {
        echo "<span class='error'>   ❌ Error al ejecutar</span>\n";
        echo "   Salida: " . implode("\n   ", $output) . "\n\n";
        $allSuccess = false;
    }
}

echo "</pre>";

if ($allSuccess) {
    echo "
    <div style='background: #10b981; color: white; padding: 20px; border-radius: 10px; margin: 20px 0;'>
        <h2 style='margin: 0; color: white;'>✅ Cache limpiado exitosamente</h2>
        <p style='margin: 10px 0 0 0;'>Los cambios del middleware CSRF ahora están aplicados.</p>
    </div>
    
    <div style='background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0;'>
        <p style='margin: 0;' class='warning'><strong>⚠️ IMPORTANTE:</strong></p>
        <p style='margin: 10px 0 0 0;'>Por razones de seguridad, <strong>ELIMINA este archivo (clear-cache.php) INMEDIATAMENTE</strong>.</p>
    </div>
    
    <div style='background: #e0f2fe; border-left: 4px solid #3b82f6; padding: 15px; margin: 20px 0;'>
        <h3 style='margin: 0 0 10px 0;'>📋 Próximos pasos:</h3>
        <ol style='margin: 0; padding-left: 20px;'>
            <li>Eliminar este archivo <code>clear-cache.php</code></li>
            <li>Ir a: <a href='http://turnero.huv.gov.co/turnos/menu' target='_blank'>http://turnero.huv.gov.co/turnos/menu</a></li>
            <li>Intentar sacar un turno</li>
            <li>El error 419 ya NO debería aparecer</li>
        </ol>
    </div>";
} else {
    echo "
    <div style='background: #fecaca; border-left: 4px solid #ef4444; padding: 15px; margin: 20px 0;'>
        <p style='margin: 0;' class='error'><strong>❌ Algunos comandos fallaron</strong></p>
        <p style='margin: 10px 0 0 0;'>Intenta ejecutar los comandos manualmente en el terminal:</p>
        <pre style='background: white; margin-top: 10px;'>";
        
    foreach ($commands as $command => $description) {
        echo $command . "\n";
    }
    
    echo "</pre>
    </div>";
}

echo "
    <hr style='margin: 30px 0;'>
    <p style='text-align: center; color: #6b7280;'>
        Turnero HUV - " . date('Y-m-d H:i:s') . "
    </p>
</body>
</html>";
?>
