<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // En desarrollo local, excluir rutas problemáticas
        'admin',
        'login',
        'api/*',
        // Excluir TODAS las rutas públicas de turnos (no requieren autenticación)
        'turnos/*',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        // Logging solo en desarrollo local
        if (config('app.env') === 'local' && config('app.debug')) {
            \Log::info('CSRF middleware - session info', [
                'session_id' => $request->session()->getId(),
                'session_started' => $request->session()->isStarted(),
                'host' => $request->getHost(),
                'url' => $request->url(),
                'method' => $request->method(),
                'path' => $request->path(),
            ]);
        }

        // IMPORTANTE: Llamar al parent para que procese las exclusiones del array $except
        return parent::handle($request, $next);
    }

    /**
     * Handle a token mismatch.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Session\TokenMismatchException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function handleTokenMismatch($request, $exception)
    {
        // Logging en caso de error
        \Log::warning('CSRF Token mismatch', [
            'url' => $request->url(),
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'referer' => $request->header('referer')
        ]);

        // Si la petición espera JSON, retornar JSON en lugar de HTML
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Token de seguridad expirado. Por favor, recargue la página.'
            ], 419);
        }

        return parent::handleTokenMismatch($request, $exception);
    }
}
