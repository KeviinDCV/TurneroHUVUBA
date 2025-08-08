@echo off
echo ========================================
echo   TURNERO HUV - Limpiar Sesiones
echo ========================================
echo.

echo [1/3] Limpiando sesiones de la base de datos...
php artisan tinker --execute="DB::table('sessions')->delete(); echo 'Sesiones eliminadas: ' . DB::table('sessions')->count();"

echo [2/3] Limpiando datos de sesión de usuarios...
php artisan tinker --execute="DB::table('users')->update(['session_id' => null, 'session_start' => null, 'last_activity' => null, 'last_ip' => null]); echo 'Usuarios actualizados';"

echo [3/3] Limpiando cache de aplicación...
php artisan config:clear
php artisan cache:clear
php artisan view:clear

echo.
echo ✅ Sesiones limpiadas correctamente
echo Ahora puedes iniciar el servidor sin problemas de sesión
echo.
