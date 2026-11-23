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
            ], [
                'usuario.required' => 'Escriba su usuario.',
                'password.required' => 'Escriba su contraseña.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Error de validación en login', [
                'errors' => $e->errors(),
                'request_data' => $request->except('password')
            ]);
            throw $e;
        }

        // Límite de intentos fallidos por usuario y equipo: frena a quien prueba contraseñas sin estorbar al que se equivoca.
        $claveIntentos = 'login:' . mb_strtolower((string) $request->usuario) . '|' . $request->ip();
        if ($this->intentosAgotados($claveIntentos)) {
            $segundos = max(1, \Illuminate\Support\Facades\RateLimiter::availableIn($claveIntentos));

            return back()->withErrors([
                'acceso' => "Demasiados intentos seguidos. Espere {$segundos} segundos e inténtelo de nuevo.",
            ])->withInput($request->only('usuario'));
        }

        // Buscar usuario por nombre_usuario
        $user = User::where('nombre_usuario', $request->usuario)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            $this->limpiarIntentos($claveIntentos);

            // Cuenta desactivada: se avisa solo con la contraseña correcta (sus turnos e historial se conservan).
            if ($user->estaDesactivada()) {
                return back()->withErrors([
                    'acceso' => 'Esta cuenta está desactivada. Si necesita entrar, hable con un administrador.',
                ])->withInput($request->only('usuario'));
            }

            $currentSessionId = session()->getId();

            // Si el usuario ya tenía una sesión previa distinta (por ejemplo, cerró el
            // navegador sin pulsar "Cerrar Sesión"), NO lo bloqueamos: como acaba de
            // autenticarse correctamente, esta nueva sesión REEMPLAZA a la anterior
            // (sigue habiendo una sola sesión activa por usuario, la última gana).
            // Invalidamos la sesión anterior y liberamos sus cajas para que pueda volver
            // a seleccionarlas; el middleware UpdateUserActivity cerrará el otro
            // dispositivo en su siguiente petición. Esto evita el falso "Sesión Activa
            // Detectada" que dejaba al usuario fuera de su propia cuenta.
            if ($user->tieneSessionActiva() && $user->esDiferenteSession($currentSessionId)) {
                \DB::table('sessions')->where('id', $user->session_id)->delete();

                \App\Models\Caja::where('asesor_activo_id', $user->id)->update([
                    'asesor_activo_id' => null,
                    'session_id' => null,
                    'fecha_asignacion' => null,
                    'ip_asesor' => null,
                ]);

                $user->limpiarSession();
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
                    'acceso' => 'No se pudo iniciar la sesión. Inténtelo de nuevo.',
                ])->withInput($request->only('usuario'));
            }
        }

        $this->contarIntento($claveIntentos);

        return back()->withErrors([
            'usuario' => "Usuario o contraseña incorrectos. Revise que Bloq\u{00A0}Mayús esté desactivado e inténtelo de nuevo.",
        ])->withInput($request->only('usuario'));
    }

    // 6 intentos fallidos por minuto. Si la caché fallara, el login sigue funcionando sin límite: nunca deja a nadie afuera.
    private const MAX_INTENTOS = 6;

    private function intentosAgotados(string $clave): bool
    {
        try {
            return \Illuminate\Support\Facades\RateLimiter::tooManyAttempts($clave, self::MAX_INTENTOS);
        } catch (\Throwable $e) {
            \Log::warning('Login: no se pudo consultar el límite de intentos', ['error' => $e->getMessage()]);

            return false;
        }
    }

    private function contarIntento(string $clave): void
    {
        try {
            \Illuminate\Support\Facades\RateLimiter::hit($clave, 60);
        } catch (\Throwable $e) {
            \Log::warning('Login: no se pudo contar el intento', ['error' => $e->getMessage()]);
        }
    }

    private function limpiarIntentos(string $clave): void
    {
        try {
            \Illuminate\Support\Facades\RateLimiter::clear($clave);
        } catch (\Throwable $e) {
            // sin caché no hay nada que limpiar
        }
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
