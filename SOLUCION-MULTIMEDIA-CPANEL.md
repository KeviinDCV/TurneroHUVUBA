# 🔧 Solución: Error de Multimedia en cPanel

## Problema Identificado

Los archivos multimedia (videos/imágenes) no se visualizan correctamente en la página `/tv-config` en cPanel, aunque funcionan correctamente en local. Aparece el icono de imagen rota en lugar del preview del archivo.

## Causa del Problema

El problema ocurre porque en cPanel **el symlink `public/storage → storage/app/public` no funciona correctamente** o no existe, lo que impide que las imágenes y videos se muestren desde su ubicación en `storage/app/public/multimedia`.

## Solución Implementada

Se ha implementado un **sistema inteligente de detección de rutas** que:

1. ✅ **Intenta usar el symlink** (funciona en local)
2. ✅ **Detecta automáticamente si falla** 
3. ✅ **Usa ruta alternativa** `/multimedia/serve/` (funciona en cPanel sin symlink)

### Archivos Modificados

1. **`app/Models/Multimedia.php`** - Método `getUrlAttribute()` mejorado con detección automática
2. **`routes/web.php`** - Nueva ruta pública `/multimedia/serve/{encodedPath}` para servir archivos
3. **`public/diagnostico-multimedia.php`** - Script de diagnóstico (eliminar después de usar)

---

## 📋 Pasos para Solucionar en cPanel

### Paso 1: Subir Archivos Modificados

Sube los siguientes archivos al servidor cPanel (sobrescribiendo los existentes):

- ✅ `app/Models/Multimedia.php`
- ✅ `routes/web.php`
- ✅ `public/diagnostico-multimedia.php` (temporal, para diagnóstico)

### Paso 2: Verificar Permisos de Directorios

En el **File Manager de cPanel**, verifica los permisos de estas carpetas:

```
storage/app/public/          → Permisos: 755 o 777
storage/app/public/multimedia/ → Permisos: 755 o 777
```

**Cómo cambiar permisos:**
1. Clic derecho en la carpeta → **Change Permissions**
2. Marca: `Read`, `Write`, `Execute` para Owner, Group, World
3. ✅ Marca **"Recurse into subdirectories"**
4. Clic en **Change Permissions**

### Paso 3: Verificar el Sistema

Accede a la página de diagnóstico en tu navegador:

```
https://turnero.huv.gov.co/diagnostico-multimedia.php
```

Esta página te mostrará:
- ✅ Estado de los directorios
- ✅ Estado del symlink
- ✅ Archivos encontrados
- ✅ Configuración PHP
- ✅ Recomendaciones específicas

### Paso 4: Probar la Funcionalidad

1. **Accede a la configuración TV:**
   ```
   https://turnero.huv.gov.co/tv-config
   ```

2. **Ve a la pestaña "Multimedia"**

3. **Sube un archivo de prueba:**
   - Sube una imagen pequeña (ej: 1-5 MB) primero
   - Verifica que aparezca el **preview correcto** en la lista
   - Si aparece correctamente, prueba con un video

4. **Verifica en la pantalla TV:**
   ```
   https://turnero.huv.gov.co/tv
   ```

### Paso 5: Limpiar (IMPORTANTE)

Una vez que todo funcione correctamente, **ELIMINA** el archivo de diagnóstico por seguridad:

```
public/diagnostico-multimedia.php
```

**Cómo eliminarlo:**
- File Manager → `public/diagnostico-multimedia.php` → Delete

---

## 🔄 Alternativa: Crear el Symlink Manualmente (Opcional)

Si prefieres intentar crear el symlink manualmente en cPanel:

### Opción A: Terminal de cPanel

Si tu hosting tiene **Terminal** habilitado:

```bash
cd public_html
php artisan storage:link
```

### Opción B: SSH

Si tienes acceso SSH:

```bash
cd /home/tuusuario/public_html
php artisan storage:link
```

### Opción C: Manual con PHP

Crea un archivo temporal `crear-symlink.php` en `/public`:

```php
<?php
$target = '../storage/app/public';
$link = __DIR__ . '/storage';

if (file_exists($link)) {
    if (is_link($link)) {
        echo "El symlink ya existe\n";
    } else {
        echo "Existe un directorio/archivo con ese nombre\n";
    }
} else {
    if (symlink($target, $link)) {
        echo "✅ Symlink creado exitosamente\n";
    } else {
        echo "❌ No se pudo crear el symlink (el servidor puede no permitirlo)\n";
    }
}
?>
```

Accede a: `https://turnero.huv.gov.co/crear-symlink.php`

**⚠️ Elimina el archivo después de usarlo**

---

## 🎯 Cómo Funciona el Sistema Nuevo

### 1. Detección Automática (Multimedia.php)

```php
public function getUrlAttribute()
{
    // 1. Intenta con symlink (local)
    if (file_exists(public_path('storage/' . $this->archivo))) {
        return asset('storage/' . $this->archivo);
    }
    
    // 2. Si falla, usa ruta alternativa (cPanel)
    if (file_exists(storage_path('app/public/' . $this->archivo))) {
        return url('multimedia/serve/' . base64_encode($this->archivo));
    }
    
    // 3. Fallback
    return Storage::url($this->archivo);
}
```

### 2. Ruta Alternativa (web.php)

```php
Route::get('/multimedia/serve/{encodedPath}', function ($encodedPath) {
    $filePath = base64_decode($encodedPath);
    $fullPath = storage_path('app/public/' . $filePath);
    return response()->file($fullPath);
});
```

**Ventajas:**
- ✅ Funciona con o sin symlink
- ✅ Automático, sin configuración manual
- ✅ Compatible con cPanel compartido
- ✅ Cache activado (mejor rendimiento)
- ✅ Seguro (valida rutas)

---

## 🐛 Solución de Problemas

### Problema: Los archivos aún no se ven

**Verifica:**
1. ✅ Los archivos se subieron correctamente al servidor
2. ✅ Los permisos de `storage/app/public/multimedia/` son 755 o 777
3. ✅ Accede a `/diagnostico-multimedia.php` y revisa los errores
4. ✅ Verifica que los archivos multimedia estén físicamente en `storage/app/public/multimedia/`

### Problema: Error 404 al acceder a multimedia

**Causa:** El archivo `routes/web.php` no se actualizó correctamente.

**Solución:**
1. Vuelve a subir `routes/web.php`
2. Borra el cache de rutas:
   - Accede a: `https://turnero.huv.gov.co/clear-cache.php` (si existe)
   - O espera unos minutos y vuelve a intentar

### Problema: Sale "forbidden" o error de permisos

**Solución:**
1. Verifica permisos de `storage/` (debe ser 755 o 777)
2. Verifica permisos de `storage/app/` (debe ser 755 o 777)
3. Verifica permisos de `storage/app/public/` (debe ser 755 o 777)

### Problema: Los videos se suben pero se ven muy lentos

**Causa:** Archivos muy grandes o conexión lenta.

**Solución:**
1. Comprime los videos antes de subirlos (recomendado: < 100MB)
2. Usa formato MP4 con codificación H.264 (mejor compatibilidad)
3. Reduce la resolución a 1080p o 720p
4. Verifica que el limite de PHP sea suficiente:
   - Revisa `/diagnostico-multimedia.php`
   - `upload_max_filesize` debe ser al menos 600M

---

## 📞 Soporte

Si después de seguir todos los pasos el problema persiste:

1. **Ejecuta el diagnóstico:**
   - Accede a `/diagnostico-multimedia.php`
   - Toma un screenshot completo de la página

2. **Verifica los logs:**
   - En cPanel → Errors
   - Busca errores relacionados con "storage" o "multimedia"

3. **Información a recopilar:**
   - URL del sitio
   - Resultado del diagnóstico
   - Mensaje de error específico
   - Tipo de hosting (compartido, VPS, dedicado)

---

## ✅ Checklist Final

Antes de considerar el problema resuelto, verifica:

- [ ] Archivos actualizados subidos al servidor
- [ ] Permisos de carpetas correctos (755 o 777)
- [ ] Diagnóstico ejecutado sin errores críticos
- [ ] Archivo de prueba subido y visible en `/tv-config`
- [ ] Preview del archivo aparece correctamente (no icono roto)
- [ ] Archivo se reproduce en `/tv` correctamente
- [ ] Archivo `diagnostico-multimedia.php` eliminado
- [ ] Sistema funcionando en producción

---

## 🎉 Resultado Esperado

Después de aplicar esta solución:

1. ✅ Podrás **subir archivos multimedia** desde `/tv-config`
2. ✅ Verás el **preview correcto** de imágenes y videos
3. ✅ Los archivos se **mostrarán en el TV** sin problemas
4. ✅ El sistema funcionará **tanto en local como en cPanel**
5. ✅ **No necesitarás configuración adicional** - todo es automático

---

**Fecha:** 2024-11-11  
**Versión:** 1.0  
**Sistema:** Turnero HUV - Hospital Universitario del Valle
