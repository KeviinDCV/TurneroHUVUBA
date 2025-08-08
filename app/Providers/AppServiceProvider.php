<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configurar URL y sesiones automáticamente basada en la request actual
        if (app()->runningInConsole() === false && request()) {
            $url = request()->getSchemeAndHttpHost();
            $host = request()->getHost();
            $isSecure = request()->isSecure();

            // Configurar URL de la aplicación
            config(['app.url' => $url]);

            // Configuración dinámica de sesiones para máxima compatibilidad
            $sessionConfig = [
                'domain' => null, // Siempre null para máxima compatibilidad
                'secure' => $isSecure, // Solo secure si es HTTPS
                'same_site' => 'lax', // Lax es más compatible que none
                'http_only' => true, // Seguridad adicional
            ];

            // Para desarrollo local, ser aún más permisivo
            if (config('app.env') === 'local') {
                $sessionConfig['same_site'] = 'none';
                $sessionConfig['secure'] = false; // No requerir HTTPS en desarrollo
            }

            // Aplicar configuración de sesión
            config([
                'session.domain' => $sessionConfig['domain'],
                'session.secure' => $sessionConfig['secure'],
                'session.same_site' => $sessionConfig['same_site'],
                'session.http_only' => $sessionConfig['http_only'],
            ]);

            // Log para debugging
            \Log::info('Dynamic configuration updated', [
                'host' => $host,
                'url' => $url,
                'is_secure' => $isSecure,
                'session_config' => $sessionConfig,
                'app_env' => config('app.env')
            ]);
        }

        // Inicializar el broadcaster personalizado
        \App\Broadcasting\TurneroBroadcaster::init();

        // Otras configuraciones...
    }
}
