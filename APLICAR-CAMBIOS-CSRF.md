# 🚀 Aplicar Cambios del Middleware CSRF

## ⚠️ IMPORTANTE

Los cambios en `app/Http/Middleware/VerifyCsrfToken.php` **NO se aplican automáticamente** en producción porque Laravel cachea las configuraciones.

## ✅ Solución: Limpiar Cache de Laravel

### **Paso 1: Acceder al Servidor**

Conéctate al servidor por SSH o usa el terminal de cPanel.

### **Paso 2: Navegar al Proyecto**

```bash
cd /ruta/a/turnero-huv
```

Por ejemplo:
```bash
cd /home/usuario/public_html/turnero-huv
```

### **Paso 3: Ejecutar Comandos de Limpieza** ⭐

**Opción A: Limpieza Completa (Recomendado)**

Ejecuta TODOS estos comandos en orden:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
```

**Opción B: Comando Único (Si tienes prisa)**

```bash
php artisan optimize:clear
```

Este comando limpia todo el cache de una vez.

### **Paso 4: Verificar que Funcionó**

1. Ve a http://turnero.huv.gov.co/turnos/menu
2. Abre la consola del navegador (F12)
3. Intenta sacar un turno
4. **Ya NO debería aparecer el error 419**

---

## 🔧 Si No Tienes Acceso SSH

### **Opción 1: Crear Script de Limpieza**

Crea este archivo en la raíz del proyecto:

**Archivo:** `limpiar-cache.php`

```php
<?php
// Limpiar cache de Laravel

// Cargar el autoloader de Laravel
require __DIR__.'/vendor/autoload.php';

// Crear la aplicación
$app = require_once __DIR__.'/bootstrap/app.php';

// Crear kernel
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

// Ejecutar comandos
echo "🔄 Limpiando cache de configuración...\n";
$kernel->call('config:clear');
echo "✅ Cache de configuración limpiado\n\n";

echo "🔄 Limpiando cache general...\n";
$kernel->call('cache:clear');
echo "✅ Cache general limpiado\n\n";

echo "🔄 Limpiando cache de rutas...\n";
$kernel->call('route:clear');
echo "✅ Cache de rutas limpiado\n\n";

echo "🔄 Limpiando cache de vistas...\n";
$kernel->call('view:clear');
echo "✅ Cache de vistas limpiado\n\n";

echo "✅✅✅ CACHE LIMPIADO COMPLETAMENTE ✅✅✅\n";
echo "Ahora el middleware CSRF debería funcionar correctamente.\n";
echo "⚠️ ELIMINA este archivo por seguridad después de usarlo.\n";
```

**Uso:**
1. Sube el archivo `limpiar-cache.php` a la raíz del proyecto
2. Accede a: http://turnero.huv.gov.co/limpiar-cache.php
3. **ELIMINA el archivo inmediatamente después** por seguridad

### **Opción 2: Archivo .htaccess con Redirección Temporal**

Si nada funciona, crea un script simple:

**Archivo:** `clear.php` (en la raíz del proyecto)

```php
<?php
// Script simple para limpiar cache
$commands = [
    'php artisan config:clear',
    'php artisan cache:clear',
    'php artisan route:clear',
    'php artisan optimize:clear'
];

echo "<h1>Limpiando Cache...</h1><pre>";

foreach ($commands as $cmd) {
    echo "Ejecutando: $cmd\n";
    $output = shell_exec($cmd . ' 2>&1');
    echo $output . "\n\n";
}

echo "</pre><h2>✅ Cache limpiado. Elimina este archivo ahora.</h2>";
?>
```

**Uso:**
1. Sube `clear.php` a la raíz
2. Visita: http://turnero.huv.gov.co/clear.php
3. **Elimina el archivo inmediatamente**

---

## 🔄 Reiniciar Servicios (Si Aplica)

Si usas un servidor con PHP-FPM o OPcache:

```bash
# PHP-FPM
sudo systemctl restart php8.1-fpm  # Ajusta la versión

# Apache
sudo systemctl restart apache2

# Nginx
sudo systemctl restart nginx
```

---

## 🧪 Verificación Final

Después de limpiar el cache:

1. Abre http://turnero.huv.gov.co/turnos/menu
2. Abre la consola (F12)
3. Toca un servicio
4. En la consola deberías ver:
   ```
   ✅ [Sin errores 419]
   ```

**Si aún sale 419:**
- Verifica que el archivo `app/Http/Middleware/VerifyCsrfToken.php` se subió correctamente
- Asegúrate de que las rutas están exactamente así:
  ```php
  'turnos/seleccionar',
  'turnos/crear-con-prioridad',
  ```
  (Sin barra diagonal al inicio)

---

## 📋 Checklist

- [ ] Me conecté al servidor
- [ ] Navegué a la carpeta del proyecto
- [ ] Ejecuté `php artisan optimize:clear`
- [ ] Verifiqué que no hay error 419
- [ ] Si usé script PHP, lo eliminé del servidor
- [ ] Confirmé que los turnos se crean correctamente

---

## ❓ Solución de Problemas

### **Error: "php command not found"**

Usa la ruta completa de PHP:
```bash
/usr/bin/php artisan optimize:clear
```

O encuentra PHP:
```bash
which php
```

### **Error: "permission denied"**

Agrega permisos:
```bash
chmod +x artisan
php artisan optimize:clear
```

### **Aún sale error 419 después de limpiar cache**

1. Verifica el archivo `VerifyCsrfToken.php`:
```bash
cat app/Http/Middleware/VerifyCsrfToken.php | grep -A 5 "except"
```

Deberías ver:
```php
protected $except = [
    'admin',
    'login',
    'api/*',
    'turnos/seleccionar',
    'turnos/crear-con-prioridad',
];
```

2. Si está correcto, reinicia PHP-FPM:
```bash
sudo systemctl restart php-fpm
```

---

## ✅ Una Vez Aplicado

El error 419 **desaparecerá completamente** y los turnos se crearán sin problemas.

La pantalla puede permanecer abierta 24/7 sin ningún error.
