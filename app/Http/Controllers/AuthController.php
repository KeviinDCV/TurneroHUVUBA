<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Mostrar formulario de login
     * Redirige usuarios autenticados a su dashboard apropiado
     */
    public function showLogin()
    {
        // Si el usuario ya está autenticado, redirigir a su dashboard
        if (Auth::check()) {
            $user = Auth::user();

            // Redirigir según el rol del usuario
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

        // Agregar headers para prevenir cache
        return response()
            ->view('admin.login')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Procesar login
     */
    public function login(Request $request)
    {
        // Log de intento de login para auditoría
        \Log::info('Intento de login', [
            'usuario' => $request->usuario,
            'ip' => $request->ip(),
            'user_agent' => substr($request->userAgent(), 0, 100)
        ]);

        try {
            $request->validate([
                'usuario' => 'required|string',
                'password' => 'required|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Error de validación en login', [
                'errors' => $e->errors(),
                'request_data' => $request->except('password')
            ]);
            throw $e;
        }

        // Buscar usuario por nombre_usuario
        $user = User::where('nombre_usuario', $request->usuario)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            $currentSessionId = session()->getId();

            // Verificar si el usuario ya tiene una sesión activa diferente
            if ($user->tieneSessionActiva() && $user->esDiferenteSession($currentSessionId)) {
                return back()->withErrors([
                    'session_active' => 'Esta cuenta ya tiene una sesión activa en otro dispositivo. Solo se permite una sesión por usuario.'
                ])->withInput();
            }

            Auth::login($user);

            // Verificar que la autenticación funcionó
            if (Auth::check()) {
                // Laravel puede regenerar la sesión después del login, obtener la nueva sesión ID
                $sessionId = $request->session()->getId();

                // Actualizar información de sesión con la sesión actual
                $user->actualizarSession($sessionId, $request->ip());

                // Redirigir según el rol
                if ($user->esAdministrador()) {
                    return redirect()->route('admin.dashboard');
                } else {
                    // Para asesores, redirigir a la selección de caja
                    return redirect()->route('asesor.seleccionar-caja');
                }
            } else {
                return back()->withErrors([
                    'usuario' => 'Error al iniciar sesión. Inténtalo de nuevo.',
                ])->withInput();
            }
        }

        return back()->withErrors([
            'usuario' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
        ])->withInput();
    }

    /**
     * Cerrar sesión
     */
    public function logout()
    {
        $user = Auth::user();

        // Limpiar información de sesión del usuario y liberar cajas
        if ($user) {
            $user->limpiarSession();

            // Liberar cualquier caja que el usuario tenga asignada
            \App\Models\Caja::where('asesor_activo_id', $user->id)->update([
                'asesor_activo_id' => null,
                'session_id' => null,
                'fecha_asignacion' => null,
                'ip_asesor' => null
            ]);
        }

        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /**
     * Verificar estado de autenticación (API)
     */
    public function checkAuth()
    {
        if (Auth::check()) {
            $user = Auth::user();
            $redirectUrl = '';

            // Determinar URL de redirección según el rol
            if ($user->esAdministrador()) {
                $redirectUrl = route('admin.dashboard');
            } elseif ($user->esAsesor()) {
                $cajaId = session('caja_seleccionada');
                if ($cajaId) {
                    $redirectUrl = route('asesor.dashboard');
                } else {
                    $redirectUrl = route('asesor.seleccionar-caja');
                }
            } else {
                $redirectUrl = route('admin.dashboard');
            }

            return response()->json([
                'authenticated' => true,
                'redirect_url' => $redirectUrl,
                'user' => [
                    'name' => $user->nombre_completo,
                    'role' => $user->rol
                ]
            ]);
        }

        return response()->json([
            'authenticated' => false
        ]);
    }
}
