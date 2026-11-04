<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/admin');

        // Reemplazar el middleware CSRF por defecto con nuestro middleware personalizado
        $middleware->web(replace: [
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class => \App\Http\Middleware\VerifyCsrfToken::class,
        ]);

        // Agregar middleware de compatibilidad de sesiones al grupo web
        $middleware->web(append: [
            \App\Http\Middleware\EnsureSessionCompatibility::class,
        ]);

        // Registrar middleware personalizado
        $middleware->alias([
            'redirect.authenticated' => \App\Http\Middleware\ForceLogoutOnLogin::class,
            'update.user.activity' => \App\Http\Middleware\UpdateUserActivity::class,
            'clean.expired.boxes' => \App\Http\Middleware\CleanExpiredBoxes::class,
            'admin.role' => \App\Http\Middleware\CheckAdminRole::class,
            'asesor.role' => \App\Http\Middleware\CheckAsesorRole::class,
            'no.session.api' => \App\Http\Middleware\NoSessionForApi::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 419 = el token de la página no corresponde a la sesión del navegador. Dos causas: la página llevaba abierta más que
        // la sesión (2 h), o el navegador guarda además una cookie de sesión vieja SIN dominio (de una configuración anterior),
        // que se envía primero y gana sobre la buena: así falla cada intento. Se borra esa cookie vieja (sin dominio: con la
        // función de Laravel quedaría con el del .env y no la tocaría) y, si era el login, se vuelve al formulario con un aviso
        // y el usuario escrito en lugar de «419 Page Expired».
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            if ($request->isMethod('POST') && $request->is('admin', 'login') && !$request->expectsJson()) {
                $respuesta = redirect()->route('login')
                    ->withInput($request->only('usuario'))
                    ->withErrors(['usuario' => 'La página había caducado. Escribe de nuevo tu contraseña para entrar.']);
            } elseif ($request->expectsJson() || $request->isJson()) {
                $respuesta = response()->json(['message' => 'La sesión expiró. Recarga la página e inténtalo de nuevo.'], 419);
            } else {
                $respuesta = response('La página expiró. Vuelve atrás, recárgala e inténtalo de nuevo.', 419);
            }

            // Solo si de verdad llegan dos cookies con ese nombre. En local con depuración, EnsureSessionCompatibility quita el
            // dominio y la cookie sin dominio es la buena: ahí no se toca nada.
            $cabecera = (string) $request->header('cookie', '');
            if (config('session.domain') && !(app()->environment('local') && config('app.debug'))) {
                foreach ([config('session.cookie'), 'XSRF-TOKEN'] as $nombre) {
                    if (preg_match_all('/(?:^|;\s*)' . preg_quote($nombre, '/') . '=/', $cabecera) > 1) {
                        $respuesta->headers->setCookie(new \Symfony\Component\HttpFoundation\Cookie($nombre, '', 1, config('session.path', '/'), null));
                    }
                }
            }

            return $respuesta;
        });

        // Manejo personalizado de errores de base de datos
        $exceptions->render(function (\Illuminate\Database\QueryException $e, $request) {
            // Detectar errores de conexión a la base de datos
            $connectionErrors = [
                'Connection refused',
                'No se puede establecer una conexión',
                'denegó expresamente dicha conexión',
                'Connection timed out',
                'Access denied',
                'Unknown database',
                'Can\'t connect to MySQL server',
                'SQLSTATE[HY000] [2002]',
                'SQLSTATE[HY000] [1045]',
                'SQLSTATE[HY000] [1049]'
            ];

            $isConnectionError = false;
            foreach ($connectionErrors as $errorPattern) {
                if (stripos($e->getMessage(), $errorPattern) !== false) {
                    $isConnectionError = true;
                    break;
                }
            }

            if ($isConnectionError) {
                // Log del error para debugging
                \Log::error('Error de conexión a la base de datos', [
                    'error' => $e->getMessage(),
                    'url' => $request->fullUrl(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);

                // Si es una petición AJAX o API, devolver JSON
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Error de conexión a la base de datos',
                        'details' => 'Por favor, verifica que el servidor de base de datos esté funcionando correctamente.'
                    ], 503);
                }

                // Para peticiones web normales, mostrar vista personalizada
                return response()->view('errors.database-connection', [
                    'message' => 'No se pudo conectar a la base de datos',
                    'details' => 'Por favor, verifica que XAMPP esté ejecutándose y que el servicio MySQL esté activo.',
                    'suggestions' => [
                        'Asegúrate de que XAMPP esté iniciado',
                        'Verifica que el servicio MySQL esté corriendo',
                        'Comprueba la configuración de la base de datos en el archivo .env',
                        'Contacta al administrador del sistema si el problema persiste'
                    ]
                ], 503);
            }
        });
    })->create();
