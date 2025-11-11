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
        // En desarrollo local, ser más permisivo pero mantener funcionalidad básica
        if (config('app.env') === 'local' && config('app.debug')) {
            // Asegurar que la sesión esté iniciada
            if (!$request->session()->isStarted()) {
                $request->session()->start();
            }

            // Log para debugging
            \Log::info('CSRF middleware - session info', [
                'session_id' => $request->session()->getId(),
                'session_started' => $request->session()->isStarted(),
                'host' => $request->getHost(),
                'url' => $request->url(),
                'method' => $request->method(),
                'has_csrf_token' => $request->has('_token'),
                'csrf_token' => $request->input('_token'),
                'session_token' => $request->session()->token()
            ]);
        }

        return $next($request);
    }

    /**
     * Determine if the session and input CSRF tokens match.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function tokensMatch($request)
    {
        // En desarrollo local, ser completamente permisivo con CSRF
        if (config('app.env') === 'local' && config('app.debug')) {
            \Log::info('CSRF check bypassed for local development', [
                'ip' => $request->ip(),
                'url' => $request->url(),
                'method' => $request->method(),
                'path' => $request->path()
            ]);

            return true; // Siempre permitir en desarrollo local
        }

        return parent::tokensMatch($request);
    }

    /**
     * Determine if the request has a URI that should pass through CSRF verification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function inExceptArray($request)
    {
        // En desarrollo local, ser más permisivo
        if (config('app.env') === 'local' && config('app.debug')) {
            $clientIp = $request->ip();
            $isLocalNetwork = str_starts_with($clientIp, '192.168.') ||
                             str_starts_with($clientIp, '10.') ||
                             str_starts_with($clientIp, '172.') ||
                             $clientIp === '127.0.0.1' ||
                             $clientIp === 'localhost';

            // Excluir rutas de autenticación en desarrollo local
            if ($isLocalNetwork) {
                $path = $request->path();
                $excludedPaths = ['admin', 'login'];

                if (in_array($path, $excludedPaths)) {
                    \Log::info('CSRF verification bypassed for local development', [
                        'ip' => $clientIp,
                        'path' => $path,
                        'url' => $request->url(),
                        'method' => $request->method()
                    ]);

                    return true;
                }
            }
        }

        return parent::inExceptArray($request);
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
        // En desarrollo local, ser más permisivo con errores de token
        if (config('app.env') === 'local' && config('app.debug')) {
            \Log::warning('CSRF Token mismatch in development', [
                'url' => $request->url(),
                'method' => $request->method(),
                'host' => $request->getHost(),
                'ip' => $request->ip(),
                'session_id' => $request->session()->getId(),
                'has_token' => $request->has('_token'),
                'referer' => $request->header('referer')
            ]);

            // Regenerar token y redirigir de vuelta
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Token CSRF expirado. Recargando página...',
                    'csrf_token' => csrf_token()
                ], 419);
            }

            return redirect()->back()
                ->withInput($request->except('password', '_token'))
                ->withErrors(['csrf' => 'Token de seguridad expirado. Por favor, intenta de nuevo.']);
        }

        return parent::handleTokenMismatch($request, $exception);
    }
}
