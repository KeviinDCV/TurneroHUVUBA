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
        // LOG SIEMPRE para diagnóstico
        \Log::info('🔒 CSRF Middleware - Petición entrante', [
            'url' => $request->url(),
            'path' => $request->path(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'excepted_uris' => $this->except,
            'is_excepted' => $this->inExceptArray($request),
        ]);

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
        // LOG DETALLADO del error 419
        \Log::error('❌ ERROR 419 - CSRF Token mismatch', [
            'url' => $request->url(),
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'referer' => $request->header('referer'),
            'excepted_uris' => $this->except,
            'is_excepted' => $this->inExceptArray($request),
            'has_csrf_token' => $request->hasHeader('X-CSRF-TOKEN'),
            'expects_json' => $request->expectsJson(),
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
