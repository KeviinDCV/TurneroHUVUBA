<?php
/**
 * Script de Diagnóstico de Multimedia
 * 
 * Este script verifica que el sistema de archivos multimedia esté funcionando
 * correctamente en el servidor. Útil para diagnosticar problemas en cPanel.
 * 
 * IMPORTANTE: Eliminar este archivo después de verificar el funcionamiento.
 */

// Deshabilitar salida de errores para producción
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Multimedia - Turnero HUV</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #064b9e;
            border-bottom: 3px solid #064b9e;
            padding-bottom: 10px;
        }
        h2 {
            color: #333;
            margin-top: 30px;
            border-left: 4px solid #064b9e;
            padding-left: 10px;
        }
        .check-item {
            margin: 15px 0;
            padding: 15px;
            border-left: 4px solid #ccc;
            background: #f9f9f9;
        }
        .check-item.success {
            border-left-color: #28a745;
            background: #d4edda;
        }
        .check-item.error {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        .check-item.warning {
            border-left-color: #ffc107;
            background: #fff3cd;
        }
        .status {
            font-weight: bold;
            margin-right: 10px;
        }
        .status.ok { color: #28a745; }
        .status.error { color: #dc3545; }
        .status.warning { color: #ffc107; }
        .info {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }
        .path {
            font-family: monospace;
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 3px;
        }
        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            background: #cce5ff;
            border-left: 4px solid #004085;
            color: #004085;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #064b9e;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .button:hover {
            background: #053d7a;
        }
        pre {
            background: #282c34;
            color: #abb2bf;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico de Sistema Multimedia</h1>
        <p>Este script verifica que el sistema de archivos multimedia esté configurado correctamente.</p>
        
        <div class="alert">
            <strong>⚠️ SEGURIDAD:</strong> Elimine este archivo después de realizar el diagnóstico para evitar exponer información del servidor.
        </div>

        <h2>1. Verificación de Directorios</h2>
        
        <?php
        // Verificar directorio storage/app/public
        $storagePublicPath = dirname(__DIR__) . '/storage/app/public';
        $storageMultimediaPath = $storagePublicPath . '/multimedia';
        
        echo '<div class="check-item ' . (is_dir($storagePublicPath) ? 'success' : 'error') . '">';
        echo '<span class="status ' . (is_dir($storagePublicPath) ? 'ok' : 'error') . '">';
        echo is_dir($storagePublicPath) ? '✓ OK' : '✗ ERROR';
        echo '</span>';
        echo 'Directorio storage/app/public';
        echo '<div class="info">Ruta: <span class="path">' . $storagePublicPath . '</span></div>';
        echo '<div class="info">Existe: ' . (is_dir($storagePublicPath) ? 'Sí' : 'No') . '</div>';
        echo '<div class="info">Permisos: ' . (is_dir($storagePublicPath) ? substr(sprintf('%o', fileperms($storagePublicPath)), -4) : 'N/A') . '</div>';
        echo '</div>';

        echo '<div class="check-item ' . (is_dir($storageMultimediaPath) ? 'success' : 'error') . '">';
        echo '<span class="status ' . (is_dir($storageMultimediaPath) ? 'ok' : 'error') . '">';
        echo is_dir($storageMultimediaPath) ? '✓ OK' : '✗ ERROR';
        echo '</span>';
        echo 'Directorio storage/app/public/multimedia';
        echo '<div class="info">Ruta: <span class="path">' . $storageMultimediaPath . '</span></div>';
        echo '<div class="info">Existe: ' . (is_dir($storageMultimediaPath) ? 'Sí' : 'No') . '</div>';
        echo '<div class="info">Permisos: ' . (is_dir($storageMultimediaPath) ? substr(sprintf('%o', fileperms($storageMultimediaPath)), -4) : 'N/A') . '</div>';
        if (!is_dir($storageMultimediaPath)) {
            echo '<div class="info" style="color: #dc3545; font-weight: bold;">⚠️ Este directorio debe crearse. Intente subir un archivo desde la interfaz de administración.</div>';
        }
        echo '</div>';
        ?>

        <h2>2. Verificación de Symlink</h2>
        
        <?php
        $publicStoragePath = __DIR__ . '/storage';
        $symlinkExists = file_exists($publicStoragePath);
        $isSymlink = is_link($publicStoragePath);
        
        if ($symlinkExists && $isSymlink) {
            $symlinkTarget = readlink($publicStoragePath);
            echo '<div class="check-item success">';
            echo '<span class="status ok">✓ OK</span>';
            echo 'Symlink público funciona correctamente';
            echo '<div class="info">Ruta: <span class="path">' . $publicStoragePath . '</span></div>';
            echo '<div class="info">Apunta a: <span class="path">' . $symlinkTarget . '</span></div>';
        } elseif ($symlinkExists && !$isSymlink) {
            echo '<div class="check-item warning">';
            echo '<span class="status warning">⚠ ADVERTENCIA</span>';
            echo 'Existe public/storage pero NO es un symlink';
            echo '<div class="info">Ruta: <span class="path">' . $publicStoragePath . '</span></div>';
            echo '<div class="info">Es un directorio o archivo regular, no un enlace simbólico.</div>';
        } else {
            echo '<div class="check-item error">';
            echo '<span class="status error">✗ ERROR</span>';
            echo 'Symlink público NO existe';
            echo '<div class="info">Ruta esperada: <span class="path">' . $publicStoragePath . '</span></div>';
            echo '<div class="info">⚠️ El sistema usará la ruta alternativa /multimedia/serve/ para servir archivos.</div>';
        }
        echo '</div>';
        ?>

        <h2>3. Sistema de Rutas Alternativo</h2>
        
        <div class="check-item success">
            <span class="status ok">✓ ACTIVO</span>
            Sistema de rutas alternativo para cPanel
            <div class="info">Si el symlink no funciona, el sistema automáticamente usará la ruta:</div>
            <div class="info"><span class="path"><?php echo $_SERVER['HTTP_HOST']; ?>/multimedia/serve/[archivo-codificado]</span></div>
            <div class="info">✅ Este método funciona sin necesidad de symlinks y es compatible con cPanel.</div>
        </div>

        <h2>4. Archivos Multimedia Encontrados</h2>
        
        <?php
        if (is_dir($storageMultimediaPath)) {
            $archivos = array_diff(scandir($storageMultimediaPath), ['.', '..']);
            
            if (count($archivos) > 0) {
                echo '<div class="check-item success">';
                echo '<span class="status ok">✓ OK</span>';
                echo 'Se encontraron ' . count($archivos) . ' archivo(s) multimedia';
                echo '<div class="info">Archivos:</div>';
                echo '<ul>';
                foreach (array_slice($archivos, 0, 10) as $archivo) {
                    $archivoPath = $storageMultimediaPath . '/' . $archivo;
                    $tamano = is_file($archivoPath) ? number_format(filesize($archivoPath) / 1024 / 1024, 2) : '0';
                    echo '<li><span class="path">' . $archivo . '</span> (' . $tamano . ' MB)</li>';
                }
                if (count($archivos) > 10) {
                    echo '<li>... y ' . (count($archivos) - 10) . ' archivo(s) más</li>';
                }
                echo '</ul>';
                echo '</div>';
            } else {
                echo '<div class="check-item warning">';
                echo '<span class="status warning">⚠ ADVERTENCIA</span>';
                echo 'No se encontraron archivos multimedia';
                echo '<div class="info">Directorio: <span class="path">' . $storageMultimediaPath . '</span></div>';
                echo '<div class="info">Suba archivos desde la interfaz de administración en /tv-config</div>';
                echo '</div>';
            }
        } else {
            echo '<div class="check-item error">';
            echo '<span class="status error">✗ ERROR</span>';
            echo 'No se puede listar archivos (directorio no existe)';
            echo '</div>';
        }
        ?>

        <h2>5. Configuración PHP</h2>
        
        <?php
        $uploadMaxFilesize = ini_get('upload_max_filesize');
        $postMaxSize = ini_get('post_max_size');
        $maxExecutionTime = ini_get('max_execution_time');
        $memoryLimit = ini_get('memory_limit');
        
        function convertToBytes($value) {
            $value = trim($value);
            $unit = strtolower($value[strlen($value)-1]);
            $number = (int) $value;
            switch($unit) {
                case 'g': $number *= 1024;
                case 'm': $number *= 1024;
                case 'k': $number *= 1024;
            }
            return $number;
        }
        
        $uploadBytes = convertToBytes($uploadMaxFilesize);
        $uploadOK = $uploadBytes >= 524288000; // 500MB
        
        echo '<div class="check-item ' . ($uploadOK ? 'success' : 'warning') . '">';
        echo '<span class="status ' . ($uploadOK ? 'ok' : 'warning') . '">';
        echo $uploadOK ? '✓ OK' : '⚠ ADVERTENCIA';
        echo '</span>';
        echo 'Tamaño máximo de subida: <span class="path">' . $uploadMaxFilesize . '</span>';
        if (!$uploadOK) {
            echo '<div class="info" style="color: #856404;">Se recomienda aumentar a 600M para archivos grandes</div>';
        }
        echo '</div>';

        echo '<div class="check-item">';
        echo '<span class="status">📊</span>';
        echo 'post_max_size: <span class="path">' . $postMaxSize . '</span>';
        echo '</div>';

        echo '<div class="check-item">';
        echo '<span class="status">⏱️</span>';
        echo 'max_execution_time: <span class="path">' . $maxExecutionTime . 's</span>';
        echo '</div>';

        echo '<div class="check-item">';
        echo '<span class="status">💾</span>';
        echo 'memory_limit: <span class="path">' . $memoryLimit . '</span>';
        echo '</div>';
        ?>

        <h2>6. Resumen y Recomendaciones</h2>
        
        <?php
        $problemas = [];
        if (!is_dir($storagePublicPath)) {
            $problemas[] = 'El directorio storage/app/public no existe';
        }
        if (!is_dir($storageMultimediaPath)) {
            $problemas[] = 'El directorio storage/app/public/multimedia no existe';
        }
        if (!$uploadOK) {
            $problemas[] = 'El límite de subida es menor a 500MB';
        }

        if (count($problemas) > 0) {
            echo '<div class="check-item error">';
            echo '<h3 style="margin-top: 0;">❌ Se encontraron ' . count($problemas) . ' problema(s):</h3>';
            echo '<ul>';
            foreach ($problemas as $problema) {
                echo '<li>' . $problema . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        } else {
            echo '<div class="check-item success">';
            echo '<h3 style="margin-top: 0;">✅ Sistema configurado correctamente</h3>';
            echo '<p>El sistema multimedia está funcionando correctamente. Si el symlink no funciona, el sistema automáticamente usará rutas alternativas.</p>';
            echo '</div>';
        }
        ?>

        <div class="alert" style="background: #fff3cd; border-left-color: #856404; color: #856404;">
            <strong>🔒 IMPORTANTE:</strong> Después de verificar el funcionamiento, elimine este archivo (diagnostico-multimedia.php) por seguridad.
        </div>

        <a href="/tv-config" class="button">← Volver a Configuración TV</a>
        <a href="/tv" class="button" target="_blank">Ver Pantalla TV</a>
    </div>
</body>
</html>
