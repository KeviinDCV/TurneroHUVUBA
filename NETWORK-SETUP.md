# 🌐 Configuración de Red Local - Turnero HUV

## 🚨 Problema Identificado

El error HTTP 419 "Page Expired" al acceder desde otros equipos de la red se debe a configuraciones incompatibles entre el entorno de producción y el desarrollo en red local.

### Causas del Problema:
1. **APP_URL incorrecta**: Configurada para HTTPS pero se accede vía HTTP
2. **SESSION_SECURE_COOKIE=true**: Requiere HTTPS, incompatible con HTTP local
3. **SESSION_DOMAIN**: No configurado para IPs de red local
4. **SESSION_SAME_SITE=lax**: Puede causar problemas con diferentes dominios/IPs

## ✅ Solución Implementada

### 1. Configuraciones de Entorno Separadas

Se han creado archivos de configuración específicos:

- **`.env.local`**: Para desarrollo en red local
- **`.env.production`**: Para servidor de producción
- **`.env`**: Archivo activo (se sobrescribe según el entorno)

### 2. Configuración Local Optimizada

```bash
APP_ENV=local
APP_DEBUG=true
APP_URL=http://192.168.2.202:3000
SESSION_DOMAIN=192.168.2.202
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=none
```

### 3. Middleware CSRF Mejorado

El middleware ahora es más permisivo en desarrollo local para IPs de red privada.

## 🚀 Cómo Usar

### Opción 1: Comando Artisan (Recomendado)

```bash
# Cambiar a configuración local
php artisan env:switch local

# Cambiar a configuración de producción
php artisan env:switch production

# Modo interactivo
php artisan env:switch
```

### Opción 2: Scripts de Cambio Rápido

**Windows:**
```cmd
switch-env.bat
```

**Linux/Mac:**
```bash
./switch-env.sh
```

### Opción 3: Manual

```bash
# Para desarrollo local
cp .env.local .env

# Para producción
cp .env.production .env
```

## 📋 Pasos para Desarrollo en Red Local

1. **Cambiar a configuración local:**
   ```bash
   php artisan env:switch local
   ```

2. **Iniciar servidor en red:**
   ```bash
   php artisan serve --host=0.0.0.0 --port=3000
   ```

3. **Acceder desde otros equipos:**
   ```
   http://192.168.2.202:3000
   ```

## 🔧 Configuraciones Clave

### Desarrollo Local
| Configuración | Valor | Propósito |
|---------------|-------|-----------|
| `APP_URL` | `http://192.168.2.202:3000` | URL correcta para red local |
| `SESSION_SECURE_COOKIE` | `false` | Permitir cookies en HTTP |
| `SESSION_DOMAIN` | `192.168.2.202` | Dominio específico para sesiones |
| `SESSION_SAME_SITE` | `none` | Permitir cookies cross-site |
| `APP_DEBUG` | `true` | Habilitar debugging |

### Producción
| Configuración | Valor | Propósito |
|---------------|-------|-----------|
| `APP_URL` | `https://turnero.huv.gov.co` | URL de producción |
| `SESSION_SECURE_COOKIE` | `true` | Seguridad HTTPS |
| `SESSION_DOMAIN` | `null` | Dominio automático |
| `SESSION_SAME_SITE` | `lax` | Seguridad estándar |
| `APP_DEBUG` | `false` | Ocultar errores |

## 🛠️ Troubleshooting

### Error 419 persiste:
1. Verificar que se aplicó la configuración local:
   ```bash
   php artisan config:show app.url
   ```

2. Limpiar cache:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan session:flush
   ```

3. Verificar IP del servidor:
   ```bash
   ipconfig  # Windows
   ip addr   # Linux
   ```

### Problemas de sesión:
1. Verificar que la base de datos esté accesible
2. Comprobar tabla `sessions` en la base de datos
3. Verificar permisos de escritura en `storage/`

### CSRF sigue fallando:
1. Verificar que el token se incluye en formularios
2. Comprobar headers en peticiones AJAX
3. Revisar logs en `storage/logs/laravel.log`

## 📝 Notas Importantes

- **Siempre** cambiar a configuración de producción antes de desplegar
- La configuración local incluye debugging extendido
- El middleware CSRF es más permisivo solo en red local
- Mantener `.env.local` y `.env.production` actualizados

## 🔄 Flujo de Trabajo Recomendado

1. **Desarrollo local:**
   ```bash
   php artisan env:switch local
   php artisan serve --host=0.0.0.0 --port=3000
   ```

2. **Antes de desplegar:**
   ```bash
   php artisan env:switch production
   # Verificar configuración
   # Subir archivos al servidor
   ```

3. **En producción:**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
