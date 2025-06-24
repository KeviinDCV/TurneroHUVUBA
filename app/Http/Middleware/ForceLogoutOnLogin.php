<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ForceLogoutOnLogin
{
    /**
     * Handle an incoming request.
     * Redirige usuarios autenticados lejos del login, pero permite navegación normal
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo aplicar en métodos GET (mostrar formulario de login)
        if ($request->isMethod('GET') && Auth::check()) {
            $user = Auth::user();

            // Redirigir al dashboard apropiado según el rol del usuario
            if ($user->esAdministrador()) {
                return redirect()->route('admin.dashboard');
            } elseif ($user->esAsesor()) {
                // Verificar si el asesor tiene una caja seleccionada
                $cajaId = session('caja_seleccionada');
                if ($cajaId) {
                    return redirect()->route('asesor.dashboard');
                } else {
                    return redirect()->route('asesor.seleccionar-caja');
                }
            }

            // Fallback: redirigir al dashboard general
            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}
