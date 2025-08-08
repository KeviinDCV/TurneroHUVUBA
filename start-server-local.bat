@echo off
echo ========================================
echo   TURNERO HUV - Servidor Local
echo ========================================
echo.

echo [1/4] Configurando servidor para acceso local...
REM Actualizar APP_URL para acceso local
powershell -Command "(Get-Content .env) -replace 'APP_URL=.*', 'APP_URL=http://localhost:8000' | Set-Content .env"
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

echo [4/4] Iniciando servidor local...
echo.
echo Servidor disponible en: http://localhost:8000
echo.
echo Presiona Ctrl+C para detener el servidor
echo.
php artisan serve
