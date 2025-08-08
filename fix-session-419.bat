@echo off
echo ========================================
echo   TURNERO HUV - Solucionar Error 419
echo ========================================
echo.

echo 🔧 Solucionando error 419 "Page Expired"...
echo.

echo [1/5] Limpiando sesiones problemáticas...
php artisan tinker --execute="DB::table('sessions')->delete(); echo 'Sesiones eliminadas';"

echo [2/5] Reseteando datos de sesión de usuarios...
php artisan tinker --execute="DB::table('users')->update(['session_id' => null, 'session_start' => null, 'last_activity' => null]); echo 'Usuarios reseteados';"

echo [3/5] Configurando dominio de sesión...
powershell -Command "(Get-Content .env) -replace 'SESSION_DOMAIN=.*', 'SESSION_DOMAIN=' | Set-Content .env"

echo [4/5] Limpiando todas las cachés...
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

echo [5/5] Verificando configuración...
php artisan tinker --execute="echo 'APP_URL: ' . config('app.url') . PHP_EOL; echo 'SESSION_DOMAIN: ' . (config('session.domain') ?: 'null') . PHP_EOL; echo 'SESSION_DRIVER: ' . config('session.driver') . PHP_EOL;"

echo.
echo ✅ Error 419 solucionado
echo 💡 Ahora puedes iniciar el servidor con cualquier configuración
echo.
pause
