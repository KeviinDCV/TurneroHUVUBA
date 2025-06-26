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

        // Registrar middleware personalizado
        $middleware->alias([
            'redirect.authenticated' => \App\Http\Middleware\ForceLogoutOnLogin::class,
            'update.user.activity' => \App\Http\Middleware\UpdateUserActivity::class,
            'clean.expired.boxes' => \App\Http\Middleware\CleanExpiredBoxes::class,
            'admin.role' => \App\Http\Middleware\CheckAdminRole::class,
            'asesor.role' => \App\Http\Middleware\CheckAsesorRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
