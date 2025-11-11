<?php
/**
 * Diagnóstico completo de CSRF para Turnero HUV
 * Este script verifica si los cambios se aplicaron correctamente
 */

echo "<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico CSRF - Turnero HUV</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; max-width: 1000px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        h1 { color: #064b9e; border-bottom: 3px solid #064b9e; padding-bottom: 10px; }
        h2 { color: #333; margin-top: 30px; }
        .card { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { background: #d1fae5; border-left: 4px solid #10b981; padding: 15px; margin: 10px 0; }
        .error { background: #fee2e2; border-left: 4px solid #ef4444; padding: 15px; margin: 10px 0; }
        .warning { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 10px 0; }
        .info { background: #dbeafe; border-left: 4px solid #3b82f6; padding: 15px; margin: 10px 0; }
        pre { background: #1e293b; color: #e2e8f0; padding: 15px; border-radius: 5px; overflow-x: auto; }
        code { background: #f1f5f9; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New', monospace; color: #0f172a; }
        .status { font-weight: bold; font-size: 18px; }
        .good { color: #10b981; }
        .bad { color: #ef4444; }
        .btn { display: inline-block; background: #064b9e; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #053a7a; }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico CSRF - Turnero HUV</h1>
    <p><strong>Hora de análisis:</strong> " . date('Y-m-d H:i:s') . "</p>";

// 1. Verificar archivo VerifyCsrfToken.php
echo "<div class='card'>
    <h2>1. Archivo VerifyCsrfToken.php</h2>";

$middlewarePath = __DIR__ . '/app/Http/Middleware/VerifyCsrfToken.php';

if (file_exists($middlewarePath)) {
    $content = file_get_contents($middlewarePath);
    
    // Verificar si tiene 'turnos/*'
    if (strpos($content, "'turnos/*'") !== false || strpos($content, '"turnos/*"') !== false) {
        echo "<div class='success'>
            <span class='status good'>✅ CORRECTO</span><br>
            El archivo existe y contiene <code>'turnos/*'</code> en el array \$except
        </div>";
        
        // Extraer el array $except
        if (preg_match('/protected\s+\$except\s*=\s*\[(.*?)\];/s', $content, $matches)) {
            echo "<div class='info'>
                <strong>📋 Array \$except actual:</strong>
                <pre>protected \$except = [" . htmlspecialchars($matches[1]) . "];</pre>
            </div>";
        }
    } else {
        echo "<div class='error'>
            <span class='status bad'>❌ ERROR</span><br>
            El archivo existe pero NO contiene <code>'turnos/*'</code><br>
            <strong>Acción requerida:</strong> Sube el archivo <code>VerifyCsrfToken.php</code> actualizado
        </div>";
    }
    
    echo "<div class='info'>
        <strong>Ruta del archivo:</strong> <code>" . $middlewarePath . "</code><br>
        <strong>Última modificación:</strong> " . date('Y-m-d H:i:s', filemtime($middlewarePath)) . "
    </div>";
} else {
    echo "<div class='error'>
        <span class='status bad'>❌ ERROR CRÍTICO</span><br>
        No se encontró el archivo VerifyCsrfToken.php en la ruta esperada
    </div>";
}
echo "</div>";

// 2. Verificar cache
echo "<div class='card'>
    <h2>2. Estado del Cache</h2>";

$cacheFiles = [
    'bootstrap/cache/config.php' => 'Configuración',
    'bootstrap/cache/routes-v7.php' => 'Rutas',
    'bootstrap/cache/packages.php' => 'Paquetes',
];

$cacheExists = false;
foreach ($cacheFiles as $file => $desc) {
    if (file_exists(__DIR__ . '/' . $file)) {
        $cacheExists = true;
        echo "<div class='warning'>
            ⚠️ <strong>$desc:</strong> Cache encontrado en <code>$file</code><br>
            Última modificación: " . date('Y-m-d H:i:s', filemtime(__DIR__ . '/' . $file)) . "
        </div>";
    }
}

if (!$cacheExists) {
    echo "<div class='success'>
        <span class='status good'>✅ BIEN</span><br>
        No se encontraron archivos de cache principales
    </div>";
} else {
    echo "<div class='error'>
        <span class='status bad'>❌ PROBLEMA</span><br>
        Hay archivos de cache que impiden aplicar los cambios<br>
        <strong>Acción requerida:</strong> Ejecutar <code>php artisan optimize:clear</code>
    </div>";
}
echo "</div>";

// 3. Verificar PHP y Laravel
echo "<div class='card'>
    <h2>3. Información del Servidor</h2>
    <div class='info'>
        <strong>PHP Version:</strong> " . phpversion() . "<br>
        <strong>Directorio de trabajo:</strong> <code>" . getcwd() . "</code><br>
        <strong>Usuario PHP:</strong> " . get_current_user() . "<br>
        <strong>Laravel detectado:</strong> " . (file_exists(__DIR__ . '/artisan') ? '✅ Sí' : '❌ No') . "
    </div>
</div>";

// 4. Intentar ejecutar comandos de limpieza
echo "<div class='card'>
    <h2>4. Limpieza de Cache (Automática)</h2>";

if (function_exists('exec')) {
    $commands = [
        'config:clear' => 'Configuración',
        'cache:clear' => 'Cache',
        'route:clear' => 'Rutas',
        'view:clear' => 'Vistas',
    ];
    
    echo "<div class='info'>Intentando limpiar cache automáticamente...</div>";
    
    foreach ($commands as $cmd => $name) {
        echo "<p>🔄 Limpiando $name...";
        $output = [];
        $return = 0;
        exec("php artisan $cmd 2>&1", $output, $return);
        
        if ($return === 0) {
            echo " <span class='good'>✅</span></p>";
        } else {
            echo " <span class='bad'>❌</span> (Código: $return)</p>";
            if (!empty($output)) {
                echo "<pre style='font-size: 12px;'>" . htmlspecialchars(implode("\n", $output)) . "</pre>";
            }
        }
    }
} else {
    echo "<div class='warning'>
        ⚠️ La función <code>exec()</code> está deshabilitada en el servidor<br>
        No se puede limpiar cache automáticamente
    </div>";
}
echo "</div>";

// 5. Probar ruta sin CSRF
echo "<div class='card'>
    <h2>5. Prueba de Ruta Sin CSRF</h2>
    <div class='info'>
        <p>Intenta sacar un turno en:</p>
        <a href='http://turnero.huv.gov.co/turnos/menu' class='btn' target='_blank'>
            🎫 Ir a Pantalla de Turnos
        </a>
    </div>
</div>";

// Resumen final
echo "<div class='card' style='background: #064b9e; color: white;'>
    <h2 style='color: white;'>📋 Resumen y Siguientes Pasos</h2>";

$allGood = file_exists($middlewarePath) && 
           (strpos(file_get_contents($middlewarePath), "'turnos/*'") !== false) &&
           !$cacheExists;

if ($allGood) {
    echo "<div style='background: #10b981; padding: 15px; border-radius: 5px; margin: 15px 0;'>
        <h3 style='margin: 0; color: white;'>✅ Todo está correcto</h3>
        <p style='margin: 10px 0 0 0;'>Los cambios están aplicados. El error 419 debería haber desaparecido.</p>
    </div>";
} else {
    echo "<div style='background: #ef4444; padding: 15px; border-radius: 5px; margin: 15px 0;'>
        <h3 style='margin: 0; color: white;'>❌ Requiere acción</h3>
        <ol style='margin: 10px 0 0 20px; color: white;'>";
    
    if (!file_exists($middlewarePath) || strpos(file_get_contents($middlewarePath), "'turnos/*'") === false) {
        echo "<li>Sube el archivo <code>app/Http/Middleware/VerifyCsrfToken.php</code> actualizado al servidor</li>";
    }
    
    if ($cacheExists) {
        echo "<li>Ejecuta en terminal: <code>php artisan optimize:clear</code></li>
              <li>O elimina manualmente los archivos de <code>bootstrap/cache/</code></li>";
    }
    
    echo "</ol>
    </div>";
}

echo "<div style='background: #fef3c7; color: #92400e; padding: 15px; border-radius: 5px; margin: 15px 0;'>
    <strong>⚠️ IMPORTANTE:</strong> Elimina este archivo (<code>diagnostico-csrf.php</code>) después de usarlo por seguridad.
</div>

</div>

<div class='card'>
    <h2>🛠️ Comandos Manuales</h2>
    <p>Si necesitas limpiar cache manualmente por SSH/Terminal:</p>
    <pre>cd " . __DIR__ . "
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear</pre>
</div>

<p style='text-align: center; color: #6b7280; margin-top: 40px;'>
    Diagnóstico generado el " . date('Y-m-d H:i:s') . "
</p>

</body>
</html>";
?>
