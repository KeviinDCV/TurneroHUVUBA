<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NoSessionForApi
{
    /**
     * Handle an incoming request.
     * Evita la creación automática de sesiones para APIs públicas
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Deshabilitar sesiones para rutas API específicas
        $apiRoutes = [
            '/api/turnos-llamados',
            '/api/tv-config', 
            '/api/multimedia',
            '/api/auth-check',
            '/api/csrf-token'
        ];

        $currentPath = $request->getPathInfo();
        
        if (in_array($currentPath, $apiRoutes)) {
            // Configurar para no iniciar sesión automáticamente
            config(['session.driver' => 'array']);
        }

        return $next($request);
    }
}
