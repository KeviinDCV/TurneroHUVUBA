<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionCompatibility
{
    /**
     * Handle an incoming request.
     * Asegura que las sesiones funcionen correctamente independientemente del host
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo aplicar en desarrollo local
        if (config('app.env') === 'local' && config('app.debug')) {
            $host = $request->getHost();
            
            // Configurar cookies de sesión dinámicamente
            $sessionConfig = [
                'domain' => null, // Siempre null para máxima compatibilidad
                'secure' => false, // No HTTPS en desarrollo
                'same_site' => 'lax', // Más compatible
                'http_only' => true,
            ];
            
            // Aplicar configuración
            config([
                'session.domain' => $sessionConfig['domain'],
                'session.secure' => $sessionConfig['secure'],
                'session.same_site' => $sessionConfig['same_site'],
                'session.http_only' => $sessionConfig['http_only'],
            ]);
            
            // Asegurar que la sesión esté iniciada
            if (!$request->session()->isStarted()) {
                $request->session()->start();
            }
            
            // Log para debugging
            \Log::info('Session compatibility middleware applied', [
                'host' => $host,
                'session_id' => $request->session()->getId(),
                'session_config' => $sessionConfig
            ]);
        }
        
        return $next($request);
    }
}
