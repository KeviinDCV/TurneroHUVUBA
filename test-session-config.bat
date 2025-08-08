@echo off
echo ========================================
echo   TURNERO HUV - Test Configuración
echo ========================================
echo.

echo 🧪 Probando configuración de sesiones...
echo.

echo [1/3] Limpiando configuración actual...
php artisan config:clear
php artisan cache:clear

echo [2/3] Verificando configuración de sesiones...
php artisan tinker --execute="
echo 'APP_URL: ' . config('app.url') . PHP_EOL;
echo 'SESSION_DRIVER: ' . config('session.driver') . PHP_EOL;
echo 'SESSION_DOMAIN: ' . (config('session.domain') ?: 'null') . PHP_EOL;
echo 'SESSION_SAME_SITE: ' . config('session.same_site') . PHP_EOL;
echo 'SESSION_SECURE: ' . (config('session.secure') ? 'true' : 'false') . PHP_EOL;
echo 'APP_ENV: ' . config('app.env') . PHP_EOL;
"

echo [3/3] Verificando ruta de debug CSRF...
echo Puedes probar la configuración visitando: http://localhost:3000/debug/csrf
echo.

echo ✅ Configuración verificada
echo 💡 Si aún tienes problemas, ejecuta: fix-session-419.bat
echo.
pause
