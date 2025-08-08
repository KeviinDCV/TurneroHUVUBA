# 🚀 Inicio Rápido - Turnero HUV

## 🌐 Para Desarrollo en Red Local

### Método 1: Script Automático (Recomendado)
```bash
# Windows
start-local-server.bat

# Linux/Mac
./start-local-server.sh
```

### Método 2: Comandos Manuales
```bash
# 1. Configurar entorno local
php artisan env:switch local

# 2. Limpiar cache
php artisan config:clear
php artisan cache:clear

# 3. Iniciar servidor
php artisan serve --host=0.0.0.0 --port=3000
```

## 🌍 Acceso desde Otros Equipos

Una vez iniciado el servidor, accede desde cualquier equipo en la red:
```
http://192.168.2.202:3000
```

## 🔄 Cambiar Entre Entornos

```bash
# Desarrollo local
php artisan env:switch local

# Producción
php artisan env:switch production

# Interactivo
php artisan env:switch
```

## 🛠️ Si Hay Problemas

1. **Error 419 persiste:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan session:flush
   ```

2. **No se puede acceder desde otros equipos:**
   - Verificar firewall de Windows
   - Comprobar IP del servidor: `ipconfig`
   - Asegurar que el puerto 3000 esté libre

3. **Problemas de autenticación:**
   - Verificar que se aplicó configuración local
   - Comprobar logs: `storage/logs/laravel.log`

## 📋 Configuración Aplicada

Cuando usas configuración local:
- ✅ APP_URL: http://192.168.2.202:3000
- ✅ SESSION_SECURE_COOKIE: false
- ✅ SESSION_DOMAIN: 192.168.2.202
- ✅ SESSION_SAME_SITE: none
- ✅ APP_DEBUG: true
- ✅ Middleware CSRF permisivo para red local

## ⚠️ Importante

- **Siempre** cambiar a producción antes de desplegar:
  ```bash
  php artisan env:switch production
  ```

- Para más detalles ver: `NETWORK-SETUP.md`
