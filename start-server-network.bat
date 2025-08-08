@echo off
echo ========================================
echo   TURNERO HUV - Servidor de Red
echo ========================================
echo.

echo [1/4] Configurando servidor para acceso en red...
REM Actualizar APP_URL para acceso en red
powershell -Command "(Get-Content .env) -replace 'APP_URL=.*', 'APP_URL=http://192.168.2.202:3000' | Set-Content .env"
REM Limpiar configuración de dominio de sesión para máxima compatibilidad
powershell -Command "(Get-Content .env) -replace 'SESSION_DOMAIN=.*', 'SESSION_DOMAIN=' | Set-Content .env"

echo [2/4] Limpiando cache de configuracion...
php artisan config:clear
php artisan cache:clear

echo [3/4] Optimizando aplicacion y limpiando sesiones...
php artisan route:clear
php artisan view:clear
php artisan session:table --quiet 2>nul || echo "Tabla de sesiones ya existe"
php artisan migrate --force --quiet

echo [4/4] Iniciando servidor en red...
echo.
echo Servidor disponible en:
echo   - Local: http://localhost:3000
echo   - Red:   http://192.168.2.202:3000
echo.
echo Presiona Ctrl+C para detener el servidor
echo.
php artisan serve --host=0.0.0.0 --port=3000
