# 🚨 INSTRUCCIONES PARA CPANEL - Solucionar Error 419

## ⚠️ PROBLEMA ACTUAL

El error 419 sigue apareciendo porque:
1. El archivo `VerifyCsrfToken.php` modificado NO está en el servidor
2. O el cache de Laravel no se ha limpiado

---

## ✅ PASO 1: Subir Archivos al Servidor

### **Usando File Manager de cPanel:**

1. **Accede a cPanel → File Manager**

2. **Navega a la carpeta del proyecto** (ejemplo: `/public_html/turnero-huv/`)

3. **Sube estos archivos:**

   📁 **Archivo 1:** `app/Http/Middleware/VerifyCsrfToken.php`
   - Navega a: `app/Http/Middleware/`
   - Sube el archivo y **SOBRESCRIBE** el existente
   - Clic derecho → **Edit** para verificar que contiene:
     ```php
     protected $except = [
         'admin',
         'login',
         'api/*',
         'turnos/*',  // ← Debe estar esta línea
     ];
     ```

   📁 **Archivo 2:** `resources/views/turnos/menu.blade.php`
   - Navega a: `resources/views/turnos/`
   - Sube el archivo y **SOBRESCRIBE** el existente

   📁 **Archivo 3:** `diagnostico-csrf.php`
   - Sube a la **RAÍZ** del proyecto (donde está `artisan`)

4. **Verifica los permisos:**
   - Todos los archivos deben tener **644** (rw-r--r--)

---

## ✅ PASO 2: Ejecutar Diagnóstico

1. **Visita:** http://turnero.huv.gov.co/diagnostico-csrf.php

2. **Lee el reporte completo:**
   - ✅ Debe decir: "El archivo existe y contiene 'turnos/*'"
   - ✅ Debe decir: "No se encontraron archivos de cache"
   - ❌ Si hay errores, sigue las instrucciones del reporte

3. **Si el diagnóstico ejecutó la limpieza automáticamente:**
   - Verás mensajes "✅" para cada comando
   - Pasa al PASO 4

4. **Si NO limpió cache automáticamente:**
   - Continúa con el PASO 3

---

## ✅ PASO 3: Limpiar Cache (Manual)

### **Opción A: Terminal de cPanel**

1. En cPanel, busca **Terminal**
2. Ejecuta estos comandos:

```bash
cd public_html/turnero-huv  # Ajusta la ruta si es diferente
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
```

### **Opción B: File Manager (Si no tienes Terminal)**

1. **En File Manager, navega a:**
   - `bootstrap/cache/`

2. **Elimina estos archivos** (si existen):
   - `config.php`
   - `routes-v7.php`
   - `packages.php`
   - `services.php`

   **NO elimines:** `.gitignore`

3. **Navega a:**
   - `storage/framework/cache/`

4. **Elimina todos los archivos dentro** (excepto `.gitignore`)

5. **Navega a:**
   - `storage/framework/views/`

6. **Elimina todos los archivos `.php`** (excepto `.gitignore`)

### **Opción C: Script PHP (clear-cache.php)**

1. Sube `clear-cache.php` a la raíz del proyecto
2. Visita: http://turnero.huv.gov.co/clear-cache.php
3. Espera a que termine
4. Elimina el archivo

---

## ✅ PASO 4: Probar el Sistema

1. **Ve a:** http://turnero.huv.gov.co/turnos/menu

2. **Abre la consola del navegador:**
   - Presiona `F12`
   - Ve a la pestaña **Console**

3. **Toca un servicio para sacar un turno**

4. **Verifica:**
   - ❌ **Si aún sale error 419:** Vuelve al PASO 1, asegúrate de que el archivo se subió correctamente
   - ✅ **Si NO sale error 419:** ¡Funciona! El turno debe crearse y redirigir a la impresión

---

## ✅ PASO 5: Limpieza Final

1. **Elimina estos archivos del servidor:**
   - `diagnostico-csrf.php`
   - `clear-cache.php` (si lo subiste)

2. **Prueba nuevamente** que todo funciona

---

## 🔍 Verificación del Archivo VerifyCsrfToken.php

Si quieres verificar manualmente que el archivo está correcto:

### **En File Manager:**

1. Navega a: `app/Http/Middleware/VerifyCsrfToken.php`
2. Clic derecho → **Edit**
3. Busca (Ctrl+F): `protected $except`
4. Debe verse **EXACTAMENTE** así:

```php
protected $except = [
    // En desarrollo local, excluir rutas problemáticas
    'admin',
    'login',
    'api/*',
    // Excluir TODAS las rutas públicas de turnos
    'turnos/*',
];
```

**IMPORTANTE:**
- Debe decir `'turnos/*'` (con comillas simples)
- NO debe tener barra al inicio: `'turnos/*'` ✅ no `'/turnos/*'` ❌
- Debe terminar con coma: `'turnos/*',` ✅

---

## ❓ Troubleshooting

### **El archivo está correcto pero sigue el error 419**

1. **Verifica la versión de PHP:**
   - En cPanel → **Select PHP Version**
   - Debe ser PHP 8.1 o superior
   - Cambia y guarda

2. **Reinicia PHP-FPM:**
   - En cPanel → **MultiPHP Manager**
   - Selecciona el dominio
   - Clic en **Apply**

3. **Verifica permisos de storage:**
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```

### **El diagnóstico dice "función exec() deshabilitada"**

- Es normal en hosting compartido
- Usa la **Opción B** del PASO 3 (eliminar archivos manualmente)

### **No puedo acceder a Terminal en cPanel**

- Usa **File Manager** para eliminar cache manualmente (Opción B)
- O contacta a tu proveedor de hosting para que ejecuten `php artisan optimize:clear`

---

## 📋 Checklist Final

- [ ] Subí `VerifyCsrfToken.php` al servidor
- [ ] Verifiqué que contiene `'turnos/*'`
- [ ] Subí `menu.blade.php` actualizado
- [ ] Ejecuté diagnóstico en `diagnostico-csrf.php`
- [ ] Limpié el cache (cualquier método)
- [ ] Probé en http://turnero.huv.gov.co/turnos/menu
- [ ] Ya NO sale error 419
- [ ] Eliminé archivos de diagnóstico

---

## ✅ Resultado Esperado

**En la consola del navegador (F12) al sacar un turno:**

```
✅ Usando modo de polling para actualizaciones de turnos en tiempo real
[Sin error 419]
[Redirige a la página de impresión del turno]
```

**Si ves esto, está funcionando correctamente.** 🎉

---

**¿Aún tienes problemas?** Ejecuta el diagnóstico y comparte el resultado.
