# 🔒 Solución Error 419 - Token CSRF Expirado

## 📋 Problema

Al usar la pantalla de turnos (`http://turnero.huv.gov.co/turnos/menu`) después de un tiempo, aparecía el siguiente error:

```
POST http://turnero.huv.gov.co/turnos/seleccionar 419 (proxy reauthentication required)
Error: SyntaxError: Unexpected token '<', "<!DOCTYPE "... is not valid JSON
```

### Causa

El error **419** en Laravel indica que el **token CSRF ha expirado**. Esto ocurre cuando:
- La página permanece abierta durante mucho tiempo (común en dispositivos táctiles/quioscos)
- La sesión del servidor expira (por defecto Laravel expira las sesiones después de 2 horas)
- El token CSRF se invalida al reiniciar el servidor

---

## ✅ Solución Implementada

### **1. Ruta para Refrescar Token CSRF**

**Archivo:** `routes/web.php` (líneas 233-236)

```php
// Ruta para refrescar el token CSRF (usado cuando expira)
Route::get('/refresh-csrf', function() {
    return response()->json(['csrf_token' => csrf_token()]);
})->name('refresh-csrf');
```

Esta ruta permite obtener un nuevo token CSRF sin recargar toda la página.

---

### **2. Sistema de Reintento Automático**

**Archivo:** `resources/views/turnos/menu.blade.php` (líneas 285-333)

Se implementaron dos funciones JavaScript:

#### **a) Función para Refrescar Token**

```javascript
async function refreshCsrfToken() {
    try {
        const response = await fetch('/refresh-csrf');
        const data = await response.json();
        csrfToken = data.csrf_token;
        document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', csrfToken);
        console.log('✅ Token CSRF refrescado exitosamente');
        return true;
    } catch (error) {
        console.error('❌ Error al refrescar token CSRF:', error);
        return false;
    }
}
```

#### **b) Función con Reintento Automático**

```javascript
async function fetchWithCsrfRetry(url, options, maxRetries = 1) {
    let attempt = 0;
    
    while (attempt <= maxRetries) {
        try {
            const response = await fetch(url, options);
            
            // Si es error 419 (token expirado), refrescar y reintentar
            if (response.status === 419 && attempt < maxRetries) {
                console.warn('⚠️ Token CSRF expirado (419). Refrescando token...');
                const refreshed = await refreshCsrfToken();
                
                if (refreshed) {
                    // Actualizar el token en los headers para el reintento
                    options.headers['X-CSRF-TOKEN'] = csrfToken;
                    attempt++;
                    console.log('🔄 Reintentando petición con nuevo token...');
                    continue; // Reintentar
                } else {
                    throw new Error('No se pudo refrescar el token CSRF');
                }
            }
            
            return response;
        } catch (error) {
            if (attempt >= maxRetries) {
                throw error;
            }
            attempt++;
        }
    }
}
```

---

### **3. Actualización de Funciones**

Todas las funciones que hacen peticiones POST ahora usan `fetchWithCsrfRetry`:

#### **Antes:**
```javascript
function seleccionarServicio(servicioId, nombreServicio) {
    fetch('/turnos/seleccionar', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ servicio_id: servicioId })
    })
    .then(response => response.json())
    .then(data => { /* ... */ })
    .catch(error => { /* ... */ });
}
```

#### **Después:**
```javascript
async function seleccionarServicio(servicioId, nombreServicio) {
    try {
        const response = await fetchWithCsrfRetry('/turnos/seleccionar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ servicio_id: servicioId })
        });

        const data = await response.json();
        // Procesar respuesta...
    } catch (error) {
        console.error('Error:', error);
        mostrarModal('Error de conexión. Por favor, intente nuevamente.');
    }
}
```

---

## 🎯 Funciones Actualizadas

Las siguientes funciones ahora tienen **reintento automático** cuando el token expira:

1. ✅ `seleccionarServicio()` - Seleccionar servicio principal
2. ✅ `seleccionarSubservicio()` - Seleccionar subservicio
3. ✅ `seleccionarPrioridad()` - Crear turno con prioridad

---

## 🔄 Cómo Funciona

### **Flujo Normal:**
1. Usuario toca un botón en la pantalla de turnos
2. JavaScript envía petición POST con token CSRF
3. Servidor valida token y procesa la solicitud
4. Usuario recibe su turno ✅

### **Flujo con Token Expirado (Ahora Automático):**
1. Usuario toca un botón después de que la pantalla estuvo inactiva
2. JavaScript envía petición POST con token expirado
3. **Servidor responde con error 419**
4. **JavaScript detecta el error 419 automáticamente**
5. **Solicita un nuevo token CSRF al servidor**
6. **Actualiza el token en memoria**
7. **Reintenta la petición original con el nuevo token**
8. Servidor valida el nuevo token y procesa la solicitud
9. Usuario recibe su turno ✅ (sin darse cuenta del problema)

---

## 🧪 Pruebas

### **Simular Token Expirado**

Para probar el sistema, puedes forzar un error 419:

1. Abre la consola del navegador en `/turnos/menu`
2. Cambia manualmente el token a uno inválido:
```javascript
csrfToken = 'token-invalido';
```
3. Intenta seleccionar un servicio
4. Deberías ver en la consola:
```
⚠️ Token CSRF expirado (419). Refrescando token...
✅ Token CSRF refrescado exitosamente
🔄 Reintentando petición con nuevo token...
```
5. El turno debe crearse exitosamente

---

## 📊 Ventajas

✅ **Transparente para el usuario** - No se da cuenta del error  
✅ **Sin recargas de página** - Experiencia fluida  
✅ **Automático** - No requiere intervención manual  
✅ **Robusto** - Maneja errores de red y token expirado  
✅ **Logs en consola** - Fácil debugging en desarrollo  
✅ **Seguridad mantenida** - No desactiva la protección CSRF  

---

## 🔒 Seguridad

Esta solución **NO compromete la seguridad** porque:

- ✅ Mantiene la validación CSRF activa en todas las peticiones
- ✅ El token se refresca desde el servidor (no se genera en el cliente)
- ✅ Solo refresca el token cuando es necesario (error 419)
- ✅ Limita los reintentos (máximo 1 reintento por defecto)
- ✅ La ruta `/refresh-csrf` solo devuelve un nuevo token, no expone datos sensibles

---

## 🛠️ Configuración

### **Cambiar Número de Reintentos**

Por defecto, se intenta **1 vez** después del error 419. Para cambiar:

```javascript
// En menu.blade.php, línea 302
async function fetchWithCsrfRetry(url, options, maxRetries = 2) { // Cambiar a 2
    // ...
}
```

### **Aumentar Tiempo de Sesión**

Para reducir la frecuencia de expiración del token, edita `config/session.php`:

```php
'lifetime' => 240, // Cambiar de 120 a 240 minutos (4 horas)
```

---

## 📝 Archivos Modificados

1. **`routes/web.php`** (líneas 233-236)
   - Nueva ruta `/refresh-csrf` para obtener nuevo token

2. **`resources/views/turnos/menu.blade.php`** (líneas 278-474)
   - Variable `csrfToken` ahora es `let` (mutable)
   - Nueva función `refreshCsrfToken()`
   - Nueva función `fetchWithCsrfRetry()`
   - Funciones convertidas a `async/await`:
     - `seleccionarServicio()`
     - `seleccionarSubservicio()`
     - `seleccionarPrioridad()`

---

## ✅ Resultado

El error **419 (proxy reauthentication required)** ahora se maneja automáticamente sin interrumpir la experiencia del usuario. La pantalla de turnos puede permanecer abierta indefinidamente sin problemas.

**Antes:** ❌ Error 419 → Usuario no puede sacar turno → Necesita recargar la página  
**Ahora:** ✅ Error 419 detectado → Token refrescado automáticamente → Turno creado exitosamente
