<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Caja;
use App\Models\Turno;
use App\Models\Servicio;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Dashboard principal del administrador
     */
    public function dashboard()
    {
        $user = Auth::user();

        // Todo lo que pinta el Inicio (cifras, cola por servicio, asesores conectados), con las mismas
        // definiciones que su refresco (GET /api/admin/tablero). Si el cálculo fallara, el Inicio se muestra
        // en cero en vez de dar error 500: es la página a la que llegan todos al iniciar sesión.
        try {
            $tablero = app(\App\Services\TableroService::class)->generar();
        } catch (\Throwable $e) {
            report($e);
            $tablero = \App\Services\TableroService::vacio();
        }

        return view('admin.dashboard', compact('user', 'tablero'));
    }

    /**
     * Usuarios en una sola vista: todos (son pocos; la búsqueda y los filtros van en el navegador) con lo que sirve
     * para operar: rol, servicios asignados, si está conectado ahora y, en huvuba, el auto-llamado.
     * Solo se mandan a la vista los campos que se pintan (nada de session_id ni IP).
     */
    public function users(Request $request)
    {
        $user = Auth::user();
        $search = (string) $request->input('search', '');

        $usuarios = User::orderBy('nombre_completo')->get();

        // Servicios asignados: lo que el kiosco reparte (una sección con subservicios solo agrupa, no se lista).
        $conHijos = Servicio::whereNotNull('servicio_padre_id')->distinct()->pluck('servicio_padre_id')->flip();
        $asignados = DB::table('user_servicio')
            ->join('servicios', 'servicios.id', '=', 'user_servicio.servicio_id')
            ->where('servicios.estado', 'activo')
            ->orderBy('servicios.orden')
            ->get(['user_servicio.user_id', 'servicios.id', 'servicios.codigo', 'servicios.nombre'])
            ->reject(fn ($s) => isset($conHijos[$s->id]))
            ->groupBy('user_id');

        // Quién está conectado ahora (módulo y estado), con el mismo cálculo del Inicio.
        try {
            $conectados = collect(app(\App\Services\TableroService::class)->generar()['asesores'])->keyBy('id');
        } catch (\Throwable $e) {
            report($e);
            $conectados = collect();
        }

        // El auto-llamado de turnos solo existe en el turnero que tiene AsesorController::autoLlamarTurno.
        $autoLlamado = method_exists(AsesorController::class, 'autoLlamarTurno');

        $filas = $usuarios->map(fn (User $u) => [
            'id' => (int) $u->id,
            'nombre' => $u->nombre_completo,
            'usuario' => $u->nombre_usuario,
            'cedula' => $u->cedula,
            'correo' => $u->correo_electronico,
            'rol' => $u->rol,
            'servicios' => $asignados->get($u->id, collect())->map(fn ($s) => ['codigo' => $s->codigo, 'nombre' => $s->nombre])->values()->all(),
            'ultima_actividad' => $u->last_activity?->toIso8601String(),
            'conectado' => $conectados->has($u->id)
                ? ['modulo' => $conectados->get($u->id)['modulo'], 'estado' => $conectados->get($u->id)['estado']]
                : null,
            'auto_llamado' => $autoLlamado ? (bool) $u->auto_llamado_activo : null,
            'auto_llamado_minutos' => (int) ($u->auto_llamado_minutos ?: 10),
            'yo' => (int) $u->id === (int) $user->id,
        ])->values();

        return view('admin.users', compact('user', 'filas', 'search', 'autoLlamado'));
    }

    // Ya no necesitamos el método createUser() porque usamos un modal en la misma página

    /**
     * Crear un usuario. La contraseña es opcional: sin ella la cuenta no puede iniciar sesión (el login la exige)
     * hasta que se le asigne una. NUNCA se registra en el log (antes quedaba en claro en laravel.log).
     */
    public function storeUser(Request $request)
    {
        $rules = [
            'nombre_completo' => 'required|string|max:255',
            'cedula' => 'nullable|string|max:20',
            'correo_electronico' => 'nullable|string|max:255',
            'nombre_usuario' => 'required|string|max:255|unique:users,nombre_usuario',
            'rol' => 'required|in:Administrador,Asesor',
            'password' => 'nullable|string|confirmed',
        ];

        // Solo validar unique si el campo no está vacío
        if ($request->filled('cedula')) {
            $rules['cedula'] .= '|unique:users,cedula';
        }
        if ($request->filled('correo_electronico')) {
            $rules['correo_electronico'] .= '|unique:users,correo_electronico';
        }

        $validated = $request->validate($rules, [
            'nombre_usuario.unique' => 'Ya existe un usuario con ese nombre de usuario.',
            'password.confirmed' => 'Las dos contraseñas no coinciden.',
        ], $this->atributosUsuario());

        try {
            $nuevoUsuario = User::create([
                'nombre_completo' => $validated['nombre_completo'],
                'cedula' => $validated['cedula'] ?? null,
                'correo_electronico' => $validated['correo_electronico'] ?? null,
                'nombre_usuario' => $validated['nombre_usuario'],
                'rol' => $validated['rol'],
                'password' => Hash::make($request->filled('password') ? $validated['password'] : ''),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error inesperado al crear usuario', ['usuario' => $request->input('nombre_usuario'), 'error' => $e->getMessage()]);
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'No se pudo crear el usuario. Inténtalo de nuevo.'], 500);
            }
            return redirect()->back()->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['error' => 'Ocurrió un error inesperado al crear el usuario. Por favor, inténtalo de nuevo.']);
        }

        \Log::info('Usuario creado', ['id' => $nuevoUsuario->id, 'usuario' => $nuevoUsuario->nombre_usuario, 'rol' => $nuevoUsuario->rol,
            'por' => Auth::user()->nombre_usuario ?? null]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Usuario creado', 'id' => $nuevoUsuario->id]);
        }

        return redirect()->route('admin.users')->with('success', 'Usuario creado correctamente');
    }

    /**
     * Nombres de los campos en los mensajes de validación.
     */
    private function atributosUsuario(): array
    {
        return [
            'nombre_completo' => 'nombre',
            'cedula' => 'cédula',
            'correo_electronico' => 'correo',
            'nombre_usuario' => 'usuario',
            'rol' => 'rol',
            'password' => 'contraseña',
        ];
    }

    /**
     * Un usuario: solo los campos del formulario y lo que se perdería al eliminarlo.
     */
    public function getUser($id)
    {
        $u = User::findOrFail($id);

        try {
            $canal = \App\Models\CanalNoPresencialHistorial::where('user_id', $u->id)->count();
        } catch (\Throwable $e) {
            $canal = 0;
        }

        return response()->json([
            'id' => $u->id,
            'nombre_completo' => $u->nombre_completo,
            'cedula' => $u->cedula,
            'correo_electronico' => $u->correo_electronico,
            'nombre_usuario' => $u->nombre_usuario,
            'rol' => $u->rol,
            'auto_llamado_activo' => (bool) $u->auto_llamado_activo,
            'auto_llamado_minutos' => (int) ($u->auto_llamado_minutos ?: 10),
            'impacto' => [
                'turnos' => Turno::where('asesor_id', $u->id)->count(),
                'atendidos' => Turno::where('asesor_id', $u->id)->where('estado', 'atendido')->count(),
                'servicios' => DB::table('user_servicio')->where('user_id', $u->id)->count(),
                'canal' => $canal,
            ],
        ]);
    }

    /**
     * Actualizar datos de un usuario
     */
    public function updateUser(Request $request, $id)
    {
        $userToUpdate = User::findOrFail($id);

        // Validar los datos del formulario
        $rules = [
            'nombre_completo' => 'required|string|max:255',
            'cedula' => 'nullable|string|max:20',
            'correo_electronico' => 'nullable|string|max:255',
            'nombre_usuario' => 'required|string|max:255|unique:users,nombre_usuario,' . $id,
            'rol' => 'required|in:Administrador,Asesor',
        ];
        
        // Solo validar unique si el campo no está vacío
        if ($request->filled('cedula')) {
            $rules['cedula'] .= '|unique:users,cedula,' . $id;
        }
        
        if ($request->filled('correo_electronico')) {
            $rules['correo_electronico'] .= '|unique:users,correo_electronico,' . $id;
        }

        // Si se está cambiando la contraseña, validarla
        if ($request->filled('password')) {
            $rules['password'] = 'nullable|string|confirmed';
        }

        $validated = $request->validate($rules, [
            'nombre_usuario.unique' => 'Ya existe un usuario con ese nombre de usuario.',
            'password.confirmed' => 'Las dos contraseñas no coinciden.',
        ], $this->atributosUsuario());

        // Preparar datos para actualizar
        $updateData = [
            'nombre_completo' => $validated['nombre_completo'],
            'cedula' => $validated['cedula'] ?? null,
            'correo_electronico' => $validated['correo_electronico'] ?? null,
            'nombre_usuario' => $validated['nombre_usuario'],
            'rol' => $validated['rol'],
            'auto_llamado_activo' => $request->has('auto_llamado_activo') ? true : false,
            'auto_llamado_minutos' => max(1, min(60, intval($request->input('auto_llamado_minutos', 10)))),
        ];

        // La contraseña solo cambia si llega escrita (un campo vacío no la borra)
        if ($request->filled('password')) {
            $password = $request->input('password', '');
            $updateData['password'] = Hash::make($password);
        }

        // Actualizar el usuario
        $userToUpdate->update($updateData);

        // Si la petición es AJAX, devolver JSON
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Usuario actualizado correctamente']);
        }

        // Redireccionar con mensaje de éxito para peticiones normales
        return redirect()->route('admin.users')
            ->with('success', 'Usuario actualizado correctamente');
    }

    /**
     * Eliminar un usuario
     */
    public function deleteUser(Request $request, $id)
    {
        $userToDelete = User::findOrFail($id);

        // Evitar que se elimine a sí mismo
        if (Auth::id() == $id) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes eliminar tu propio usuario'
                ], 400);
            }

            return redirect()->route('admin.users')
                ->with('error', 'No puedes eliminar tu propio usuario');
        }

        // Sus turnos quedan sin asesor en Reportes y Gráficos: con turnos, solo escribiendo su usuario para confirmar.
        if (Turno::where('asesor_id', $userToDelete->id)->exists()
            && trim((string) $request->input('confirmar')) !== $userToDelete->nombre_usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Escribe el usuario «' . $userToDelete->nombre_usuario . '» para confirmar.',
            ], 422);
        }

        $userToDelete->delete();

        // Si es una petición AJAX, devolver JSON
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Usuario eliminado correctamente'
            ]);
        }

        return redirect()->route('admin.users')
            ->with('success', 'Usuario eliminado correctamente');
    }

    /**
     * Limpiar sesiones expiradas manualmente
     */
    public function cleanExpiredSessions(Request $request)
    {
        // Verificar que el usuario sea administrador
        if (!Auth::user()->esAdministrador()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para realizar esta acción'
            ], 403);
        }

        try {
            // La sesión de quien ejecuta la limpieza no se toca: si su pestaña estuvo oculta 15 min o más, su última
            // actividad guardada es vieja y se borraba a sí mismo (el siguiente clic recibía la página 419 en HTML).
            $sesionPropia = $request->session()->getId();

            // Obtener todos los usuarios con session_id, menos quien ejecuta la limpieza
            $usersWithSessions = User::whereNotNull('session_id')->where('id', '!=', Auth::id())->get();
            $cleanedUsers = 0;
            $cleanedBoxes = 0;

            foreach ($usersWithSessions as $user) {
                $shouldClean = false;
                $reason = '';

                // Verificar si la sesión ha expirado por tiempo (más de 15 minutos)
                if ($user->last_activity && $user->last_activity->diffInMinutes(now()) >= 15) {
                    $shouldClean = true;
                    $reason = 'sesión expirada por tiempo';
                }
                // Verificar si la sesión no existe en la tabla sessions
                elseif (!DB::table('sessions')->where('id', $user->session_id)->exists()) {
                    $shouldClean = true;
                    $reason = 'sesión no existe en base de datos';
                }

                if ($shouldClean) {
                    // Limpiar sesión del usuario
                    $user->limpiarSession();
                    $cleanedUsers++;

                    // Liberar cualquier caja que el usuario tenga asignada
                    $cajasLiberadas = Caja::where('asesor_activo_id', $user->id)->count();
                    if ($cajasLiberadas > 0) {
                        Caja::where('asesor_activo_id', $user->id)->update([
                            'asesor_activo_id' => null,
                            'session_id' => null,
                            'fecha_asignacion' => null,
                            'ip_asesor' => null
                        ]);
                        $cleanedBoxes += $cajasLiberadas;
                    }

                    // También liberar cajas que puedan estar asignadas por session_id
                    $cajasLiberadasPorSession = Caja::where('session_id', $user->session_id)->count();
                    if ($cajasLiberadasPorSession > 0) {
                        Caja::where('session_id', $user->session_id)->update([
                            'asesor_activo_id' => null,
                            'session_id' => null,
                            'fecha_asignacion' => null,
                            'ip_asesor' => null
                        ]);
                        $cleanedBoxes += $cajasLiberadasPorSession;
                    }
                }
            }

            // Limpiar sesiones huérfanas en la tabla sessions (sin usuario asociado o expiradas)
            $expiredSessionsCount = DB::table('sessions')
                ->where('last_activity', '<', now()->subMinutes(15)->timestamp)
                ->where('id', '!=', $sesionPropia)
                ->count();

            if ($expiredSessionsCount > 0) {
                DB::table('sessions')
                    ->where('last_activity', '<', now()->subMinutes(15)->timestamp)
                    ->where('id', '!=', $sesionPropia)
                    ->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Limpieza de sesiones expiradas completada exitosamente. Se han liberado las cajas asignadas.',
                'data' => [
                    'usuarios_limpiados' => $cleanedUsers,
                    'cajas_liberadas' => $cleanedBoxes,
                    'sesiones_expiradas_eliminadas' => $expiredSessionsCount
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar sesiones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Limpiar todas las sesiones de todos los usuarios
     */
    public function cleanAllSessions(Request $request)
    {
        // Verificar que el usuario sea administrador
        if (!Auth::user()->esAdministrador()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para realizar esta acción'
            ], 403);
        }

        try {
            // Todas menos la de quien ejecuta la limpieza: borrarla dejaba esta página sin sesión
            $sesionPropia = $request->session()->getId();

            // Obtener todos los usuarios con session_id (activos), menos quien ejecuta la limpieza
            $usersWithSessions = User::whereNotNull('session_id')->where('id', '!=', Auth::id())->get();
            $cleanedUsers = $usersWithSessions->count();
            $cleanedBoxes = 0;

            foreach ($usersWithSessions as $user) {
                // Liberar cualquier caja que el usuario tenga asignada (antes de limpiar sesión)
                $cajasLiberadas = Caja::where('asesor_activo_id', $user->id)->count();
                if ($cajasLiberadas > 0) {
                    Caja::where('asesor_activo_id', $user->id)->update([
                        'asesor_activo_id' => null,
                        'session_id' => null,
                        'fecha_asignacion' => null,
                        'ip_asesor' => null
                    ]);
                    $cleanedBoxes += $cajasLiberadas;
                }

                // También liberar cajas que puedan estar asignadas por session_id
                if ($user->session_id) {
                    $cajasLiberadasPorSession = Caja::where('session_id', $user->session_id)->count();
                    if ($cajasLiberadasPorSession > 0) {
                        Caja::where('session_id', $user->session_id)->update([
                            'asesor_activo_id' => null,
                            'session_id' => null,
                            'fecha_asignacion' => null,
                            'ip_asesor' => null
                        ]);
                        $cleanedBoxes += $cajasLiberadasPorSession;
                    }
                }

                // Limpiar sesión del usuario
                $user->limpiarSession();
            }

            // Liberar cualquier caja huérfana que pueda haber quedado
            $cajasHuerfanas = Caja::whereNotNull('asesor_activo_id')
                ->whereNotIn('asesor_activo_id', User::whereNotNull('session_id')->pluck('id'))
                ->count();

            if ($cajasHuerfanas > 0) {
                Caja::whereNotNull('asesor_activo_id')
                    ->whereNotIn('asesor_activo_id', User::whereNotNull('session_id')->pluck('id'))
                    ->update([
                        'asesor_activo_id' => null,
                        'session_id' => null,
                        'fecha_asignacion' => null,
                        'ip_asesor' => null
                    ]);
                $cleanedBoxes += $cajasHuerfanas;
            }

            // Limpiar todas las sesiones de la tabla sessions, menos la de quien ejecuta la limpieza
            $allSessionsCount = DB::table('sessions')->where('id', '!=', $sesionPropia)->count();
            if ($allSessionsCount > 0) {
                DB::table('sessions')->where('id', '!=', $sesionPropia)->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Todas las sesiones han sido limpiadas exitosamente, menos la tuya. Se han liberado todas las cajas asignadas.',
                'data' => [
                    'usuarios_limpiados' => $cleanedUsers,
                    'cajas_liberadas' => $cleanedBoxes,
                    'sesiones_eliminadas' => $allSessionsCount
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar todas las sesiones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Limpiar sesión de un usuario específico
     */
    public function cleanUserSession(Request $request)
    {
        // Verificar que el usuario sea administrador
        if (!Auth::user()->esAdministrador()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para realizar esta acción'
            ], 403);
        }

        $request->validate([
            'user_id' => 'required|integer|exists:users,id'
        ]);

        // La sesión propia no se limpia desde aquí: dejaría esta página sin sesión
        if ((int) $request->user_id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes limpiar tu propia sesión desde aquí. Para salir, usa "Cerrar sesión".'
            ], 422);
        }

        try {
            $user = User::findOrFail($request->user_id);

            // Verificar que el usuario tenga una sesión activa
            if (!$user->session_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'El usuario seleccionado no tiene una sesión activa'
                ]);
            }

            $userName = $user->nombre_completo;
            $cleanedBoxes = 0;

            // Liberar cualquier caja que el usuario tenga asignada por ID de usuario
            $cajasLiberadas = Caja::where('asesor_activo_id', $user->id)->count();
            if ($cajasLiberadas > 0) {
                Caja::where('asesor_activo_id', $user->id)->update([
                    'asesor_activo_id' => null,
                    'session_id' => null,
                    'fecha_asignacion' => null,
                    'ip_asesor' => null
                ]);
                $cleanedBoxes += $cajasLiberadas;
            }

            // También liberar cajas que puedan estar asignadas por session_id
            if ($user->session_id) {
                $cajasLiberadasPorSession = Caja::where('session_id', $user->session_id)
                    ->where('asesor_activo_id', '!=', $user->id) // Evitar duplicados
                    ->count();
                if ($cajasLiberadasPorSession > 0) {
                    Caja::where('session_id', $user->session_id)
                        ->where('asesor_activo_id', '!=', $user->id)
                        ->update([
                            'asesor_activo_id' => null,
                            'session_id' => null,
                            'fecha_asignacion' => null,
                            'ip_asesor' => null
                        ]);
                    $cleanedBoxes += $cajasLiberadasPorSession;
                }
            }

            // Eliminar la sesión de la tabla sessions si existe
            $sessionDeleted = 0;
            if ($user->session_id) {
                $sessionDeleted = DB::table('sessions')->where('id', $user->session_id)->delete();
            }

            // Limpiar sesión del usuario
            $user->limpiarSession();

            return response()->json([
                'success' => true,
                'message' => "Sesión de {$userName} limpiada exitosamente. Se han liberado las cajas asignadas.",
                'data' => [
                    'usuario_limpiado' => $userName,
                    'cajas_liberadas' => $cleanedBoxes,
                    'sesion_eliminada' => $sessionDeleted > 0
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar la sesión del usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Emergencia - Eliminar turnos según la opción seleccionada
     *
     * IMPORTANTE: Esta función solo elimina registros de la tabla 'turnos' (temporal).
     * Los registros del historial en 'turno_historial' NUNCA son afectados,
     * manteniendo así un registro permanente de todos los turnos creados.
     */
    public function emergencyTurnos(Request $request)
    {
        // Verificar que el usuario sea administrador
        if (!Auth::user()->esAdministrador()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para realizar esta acción'
            ], 403);
        }

        $request->validate([
            'option' => 'required|in:pending,today,service',
            'service_id' => 'required_if:option,service|exists:servicios,id'
        ]);

        try {
            $option = $request->input('option');
            $serviceId = $request->input('service_id');
            $deletedCount = 0;
            $message = '';

            switch ($option) {
                case 'pending':
                    // Eliminar solo turnos pendientes y aplazados del día actual
                    // Usar Eloquent para que se disparen eventos del modelo (historial)
                    $turnosAEliminar = Turno::whereDate('fecha_creacion', Carbon::today())
                        ->whereIn('estado', ['pendiente', 'aplazado'])
                        ->get();
                    $deletedCount = $turnosAEliminar->count();
                    foreach ($turnosAEliminar as $turno) {
                        $turno->delete();
                    }

                    $message = "Se eliminaron {$deletedCount} turnos pendientes y aplazados del día actual.";
                    break;

                case 'today':
                    // Eliminar todos los turnos del día actual
                    // Usar Eloquent para que se disparen eventos del modelo (historial)
                    $turnosAEliminar = Turno::whereDate('fecha_creacion', Carbon::today())->get();
                    $deletedCount = $turnosAEliminar->count();
                    foreach ($turnosAEliminar as $turno) {
                        $turno->delete();
                    }

                    $message = "Se eliminaron {$deletedCount} turnos del día actual (todos los estados).";
                    break;

                case 'service':
                    // Eliminar todos los turnos de un servicio específico del día actual
                    $servicio = Servicio::find($serviceId);
                    if (!$servicio) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Servicio no encontrado'
                        ], 404);
                    }

                    $turnosAEliminar = Turno::whereDate('fecha_creacion', Carbon::today())
                        ->where('servicio_id', $serviceId)
                        ->get();
                    $deletedCount = $turnosAEliminar->count();
                    foreach ($turnosAEliminar as $turno) {
                        $turno->delete();
                    }

                    $message = "Se eliminaron {$deletedCount} turnos del servicio '{$servicio->nombre}' del día actual.";
                    break;
            }

            // Verificar que el historial se mantiene intacto
            $historialCount = \App\Models\TurnoHistorial::count();

            // Log de la acción de emergencia
            \Log::warning('Acción de emergencia - Eliminación de turnos', [
                'usuario' => Auth::user()->nombre_usuario,
                'opcion' => $option,
                'servicio_id' => $serviceId,
                'turnos_eliminados' => $deletedCount,
                'historial_preservado' => $historialCount,
                'nota' => 'El historial de turnos permanece intacto - solo se eliminan registros temporales',
                'timestamp' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'turnos_eliminados' => $deletedCount,
                    'opcion' => $option,
                    'servicio_id' => $serviceId
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en emergencia de turnos', [
                'error' => $e->getMessage(),
                'usuario' => Auth::user()->nombre_usuario,
                'opcion' => $request->input('option'),
                'servicio_id' => $request->input('service_id')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar turnos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener servicios activos para el selector de emergencia
     */
    public function getServiciosActivos()
    {
        try {
            $servicios = Servicio::where('estado', 'activo')
                ->select('id', 'nombre', 'codigo')
                ->orderBy('nombre')
                ->get();

            return response()->json($servicios);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar servicios: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener usuarios activos con sus estados (API)
     */
    public function getUsuariosActivos()
    {
        $usuariosActivos = $this->getUsuariosActivosData();
        return response()->json($usuariosActivos);
    }

    /**
     * Obtener datos de usuarios activos
     */
    private function getUsuariosActivosData()
    {
        $usuarios = User::activos()
            ->select('id', 'nombre_completo', 'nombre_usuario', 'rol', 'estado_asesor', 'last_activity', 'session_id', 'session_start', 'actividad_canal_no_presencial', 'inicio_canal_no_presencial')
            ->get();

        return $usuarios->map(function($usuario) {
            // Determinar disponibilidad basada en si tiene caja asignada (para asesores)
            $disponibilidad = 'DISPONIBLE';
            $caja = null;

            if ($usuario->esAsesor()) {
                // Verificar si tiene caja asignada
                $cajaAsignada = \App\Models\Caja::where('asesor_activo_id', $usuario->id)->first();
                if ($cajaAsignada) {
                    $disponibilidad = 'CAJA ' . $cajaAsignada->numero_caja;
                    $caja = $cajaAsignada->numero_caja;
                } else {
                    $disponibilidad = 'SIN CAJA';
                }
            } else {
                $disponibilidad = 'ADMINISTRADOR';
            }

            // Determinar estado (usar método actualizado si está en canal no presencial)
            $estado = $usuario->estaEnCanalNoPresencial() 
                ? strtoupper($usuario->getEstadoFormateadoActualizado())
                : strtoupper($usuario->getEstadoFormateado());

            return [
                'id' => $usuario->id,
                'name' => $usuario->nombre_completo,
                'nombre_usuario' => $usuario->nombre_usuario,
                'rol' => $usuario->rol,
                'availability' => $disponibilidad,
                'status' => $estado,
                'last_activity' => $usuario->last_activity->diffForHumans(),
                'tiempo_sesion' => $usuario->getTiempoSesionActiva(),
                'caja' => $caja,
                'is_online' => true,
                'actividad_canal' => $usuario->actividad_canal_no_presencial,
                'en_canal_no_presencial' => $usuario->estaEnCanalNoPresencial()
            ];
        });
    }

    /**
     * Obtener estadísticas de turnos por servicio
     */
    private function getTurnosPorServicioData()
    {
        // Obtener turnos atendidos del día actual agrupados por servicio
        $turnosAtendidos = Turno::select('servicios.nombre as servicio_nombre', DB::raw('COUNT(*) as total_atendidos'))
            ->join('servicios', 'turnos.servicio_id', '=', 'servicios.id')
            ->where('turnos.estado', 'atendido')
            ->whereDate('turnos.fecha_creacion', Carbon::today())
            ->groupBy('servicios.id', 'servicios.nombre')
            ->orderBy('servicios.nombre')
            ->get();

        return $turnosAtendidos->map(function($turno) {
            return [
                'servicio' => strtoupper($turno->servicio_nombre),
                'terminados' => $turno->total_atendidos
            ];
        });
    }

    /**
     * API para obtener turnos por servicio
     */
    public function getTurnosPorServicio()
    {
        $turnosPorServicio = $this->getTurnosPorServicioData();
        return response()->json($turnosPorServicio);
    }

    /**
     * Obtener estadísticas de turnos por asesor
     */
    private function getTurnosPorAsesorData()
    {
        // Obtener turnos atendidos del día actual agrupados por asesor
        // Buscar por fecha_creacion O fecha_atencion para capturar todos los turnos del día
        $turnosAtendidos = Turno::select('users.nombre_usuario as asesor_usuario', DB::raw('COUNT(*) as total_atendidos'))
            ->join('users', 'turnos.asesor_id', '=', 'users.id')
            ->where('turnos.estado', 'atendido')
            ->where(function($q) {
                $q->whereDate('turnos.fecha_creacion', Carbon::today())
                  ->orWhereDate('turnos.fecha_atencion', Carbon::today());
            })
            ->whereNotNull('turnos.asesor_id')
            ->groupBy('users.id', 'users.nombre_usuario')
            ->orderBy('total_atendidos', 'desc')
            ->get();

        return $turnosAtendidos->map(function($turno) {
            return [
                'asesor' => $turno->asesor_usuario,
                'terminados' => $turno->total_atendidos
            ];
        });
    }

    /**
     * API para obtener turnos por asesor
     */
    public function getTurnosPorAsesor()
    {
        $turnosPorAsesor = $this->getTurnosPorAsesorData();
        return response()->json($turnosPorAsesor);
    }

    /**
     * Obtener turnos en cola por servicio
     */
    private function getTurnosEnColaData()
    {
        // Obtener turnos pendientes del día actual agrupados por servicio
        $turnosEnCola = Turno::select('servicios.nombre as servicio_nombre', DB::raw('COUNT(*) as total_en_cola'))
            ->join('servicios', 'turnos.servicio_id', '=', 'servicios.id')
            ->whereIn('turnos.estado', ['pendiente', 'aplazado'])
            ->whereDate('turnos.fecha_creacion', Carbon::today())
            ->groupBy('servicios.id', 'servicios.nombre')
            ->orderBy('servicios.nombre')
            ->get();

        return $turnosEnCola->map(function($turno) {
            return [
                'servicio' => strtoupper($turno->servicio_nombre),
                'en_cola' => $turno->total_en_cola
            ];
        });
    }

    /**
     * API para obtener turnos en cola por servicio
     */
    public function getTurnosEnCola()
    {
        $turnosEnCola = $this->getTurnosEnColaData();
        return response()->json($turnosEnCola);
    }

    /**
     * API para obtener estadísticas detalladas de un usuario específico
     */
    public function getEstadisticasUsuario(Request $request, $userId)
    {
        $request->validate([
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
        ]);

        $usuario = User::findOrFail($userId);

        // Fechas por defecto: hoy
        $fechaInicio = $request->fecha_inicio 
            ? Carbon::parse($request->fecha_inicio)->startOfDay() 
            : Carbon::today()->startOfDay();
        $fechaFin = $request->fecha_fin 
            ? Carbon::parse($request->fecha_fin)->endOfDay() 
            : Carbon::today()->endOfDay();

        // Obtener turnos del usuario en el rango de fechas
        // Buscar por fecha_creacion O fecha_atencion para capturar todos los turnos
        // que el asesor manejó en el período, incluyendo transferencias y edge cases
        $turnos = Turno::with(['servicio', 'caja'])
            ->where('asesor_id', $userId)
            ->where(function($q) use ($fechaInicio, $fechaFin) {
                $q->whereBetween('fecha_creacion', [$fechaInicio, $fechaFin])
                  ->orWhereBetween('fecha_atencion', [$fechaInicio, $fechaFin]);
            })
            ->orderBy('fecha_creacion', 'desc')
            ->get();

        // IMPORTANTE: ->values() re-indexa las claves para que JSON serialice como array []
        // Sin values(), las claves no-secuenciales (0,2,5,8...) generan un objeto {} en JSON
        // y en JavaScript {}.length === undefined, causando "No hay turnos atendidos"
        $turnosAtendidos = $turnos->where('estado', 'atendido')->values();

        // Estadísticas generales
        $estadisticas = [
            'total_turnos' => $turnos->count(),
            'turnos_atendidos' => $turnosAtendidos->count(),
            'turnos_pendientes' => $turnos->whereIn('estado', ['pendiente', 'aplazado'])->count(),
            'turnos_aplazados' => $turnos->where('estado', 'aplazado')->count(),
            'turnos_cancelados' => $turnos->where('estado', 'cancelado')->count(),
            'tiempo_promedio_atencion' => $turnosAtendidos->count() > 0 
                ? sprintf('%02d:%02d', floor($turnosAtendidos->avg('duracion_atencion') / 60), floor($turnosAtendidos->avg('duracion_atencion')) % 60) 
                : '00:00',
            'tiempo_total_atencion' => sprintf('%02d:%02d', floor($turnosAtendidos->sum('duracion_atencion') / 60), $turnosAtendidos->sum('duracion_atencion') % 60),
        ];

        // Calcular tiempo promedio entre turnos
        $turnosOrdenados = $turnosAtendidos->sortBy('fecha_atencion')->values();
        $tiemposEntreTurnos = [];
        for ($i = 1; $i < $turnosOrdenados->count(); $i++) {
            $anterior = $turnosOrdenados[$i - 1];
            $actual = $turnosOrdenados[$i];
            
            if ($anterior->fecha_finalizacion && $actual->fecha_llamado) {
                $segundos = Carbon::parse($anterior->fecha_finalizacion)
                    ->diffInSeconds(Carbon::parse($actual->fecha_llamado));
                $tiemposEntreTurnos[] = $segundos;
            }
        }
        
        if (count($tiemposEntreTurnos) > 0) {
            $promedioSegundos = array_sum($tiemposEntreTurnos) / count($tiemposEntreTurnos);
            $estadisticas['tiempo_promedio_entre_turnos'] = sprintf('%02d:%02d', floor($promedioSegundos / 60), floor($promedioSegundos) % 60);
        } else {
            $estadisticas['tiempo_promedio_entre_turnos'] = '00:00';
        }

        // Turnos por servicio
        $turnosPorServicio = $turnos->groupBy('servicio.nombre')->map(function ($grupo) {
            return [
                'total' => $grupo->count(),
                'atendidos' => $grupo->where('estado', 'atendido')->count(),
                'pendientes' => $grupo->whereIn('estado', ['pendiente', 'aplazado'])->count(),
            ];
        });

        // Turnos por día (para gráfico)
        $turnosPorDia = $turnos->groupBy(function ($turno) {
            return Carbon::parse($turno->fecha_creacion)->format('Y-m-d');
        })->map(function ($grupo) {
            return [
                'total' => $grupo->count(),
                'atendidos' => $grupo->where('estado', 'atendido')->count(),
            ];
        });

        // Canales no presenciales
        $canalesNoPresenciales = \App\Models\CanalNoPresencialHistorial::where('user_id', $userId)
            ->whereBetween('inicio', [$fechaInicio, $fechaFin])
            ->orderBy('inicio', 'desc')
            ->get();

        $totalMinutosCanal = $canalesNoPresenciales->sum('duracion_minutos');

        // Detalle de turnos (últimos 20) - values() asegura array JSON
        $turnosDetalle = $turnosAtendidos->take(20)->map(function ($turno) {
            return [
                'codigo' => $turno->codigo_completo,
                'servicio' => $turno->servicio->nombre ?? 'N/A',
                'fecha_llamado' => $turno->fecha_llamado 
                    ? Carbon::parse($turno->fecha_llamado)->format('d/m/Y h:i:s A') 
                    : 'N/A',
                'fecha_atencion' => $turno->fecha_atencion 
                    ? Carbon::parse($turno->fecha_atencion)->format('d/m/Y h:i:s A') 
                    : 'N/A',
                'fecha_finalizacion' => $turno->fecha_finalizacion 
                    ? Carbon::parse($turno->fecha_finalizacion)->format('d/m/Y h:i:s A') 
                    : 'N/A',
                'duracion_minutos' => $turno->duracion_atencion 
                    ? sprintf('%02d:%02d', floor(abs($turno->duracion_atencion) / 60), abs($turno->duracion_atencion) % 60) 
                    : '00:00',
                'caja' => $turno->caja->numero_caja ?? 'N/A',
            ];
        });

        // Detalle de canales no presenciales
        $canalesDetalle = $canalesNoPresenciales->take(10)->map(function ($actividad) {
            return [
                'inicio' => $actividad->inicio->format('d/m/Y h:i:s A'),
                'fin' => $actividad->fin ? $actividad->fin->format('d/m/Y h:i:s A') : 'En curso',
                'duracion_minutos' => $actividad->duracion_minutos ?? 0,
                'actividad' => $actividad->actividad,
            ];
        });

        return response()->json([
            'usuario' => [
                'id' => $usuario->id,
                'nombre_completo' => $usuario->nombre_completo,
                'nombre_usuario' => $usuario->nombre_usuario,
                'rol' => $usuario->rol,
            ],
            'periodo' => [
                'inicio' => $fechaInicio->format('d/m/Y'),
                'fin' => $fechaFin->format('d/m/Y'),
            ],
            'estadisticas' => $estadisticas,
            'turnos_por_servicio' => $turnosPorServicio,
            'turnos_por_dia' => $turnosPorDia,
            'canal_no_presencial' => [
                'cantidad_actividades' => $canalesNoPresenciales->count(),
                'tiempo_total_minutos' => $totalMinutosCanal,
                'tiempo_total_horas' => round($totalMinutosCanal / 60, 2),
                'detalle' => $canalesDetalle,
            ],
            'turnos_detalle' => $turnosDetalle->values(),
        ]);
    }

    /**
     * Pantalla Turnos: los turnos de hoy, paginados y filtrables. La primera pintura y el refresco
     * (GET /api/admin/turnos-hoy, cada 5 s) usan los mismos datos: turnosHoyDatos().
     */
    public function turnos(Request $request)
    {
        $user = Auth::user();
        $servicios = Servicio::where('estado', 'activo')->orderBy('nombre')->get(['id', 'nombre']);
        $asesores = User::whereIn('rol', ['asesor', 'administrador'])->orderBy('nombre_completo')->get(['id', 'nombre_completo']);
        $datos = $this->turnosHoyDatos($request);

        return view('admin.turnos', compact('user', 'servicios', 'asesores', 'datos'));
    }

    /**
     * Refresco de la pantalla Turnos (mismos filtros y página que la URL).
     */
    public function getTurnosHoy(Request $request)
    {
        return response()
            ->json($this->turnosHoyDatos($request), 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ->header('Cache-Control', 'no-store');
    }

    /**
     * Turnos de HOY con filtros y paginación, conteos por estado y umbrales de espera.
     *
     *  - "Hoy" = created_at. NO fecha_creacion: se reescribía en cada UPDATE (ON UPDATE heredado).
     *  - Búsqueda: "C-093", "c93", "C 93" o "CIT-MG-001" -> código + número exactos; solo cifras -> número;
     *    texto -> código de servicio, nombre del servicio o del asesor.
     *  - Los mosaicos de estado cuentan el día con los filtros de servicio y asesor, sin la búsqueda.
     *  - En espera y aplazados: primero el que más lleva esperando; en lo demás, lo más reciente arriba.
     */
    private function turnosHoyDatos(Request $request): array
    {
        $ahora = Carbon::now();
        $desde = $ahora->copy()->startOfDay();
        $hasta = $desde->copy()->addDay();
        $umbrales = array_merge(['espera' => 30, 'prioritario' => 15, 'atencion' => 20], (array) config('panel.umbrales', []));

        $estados = ['pendiente', 'llamado', 'atendido', 'aplazado', 'cancelado'];
        $estado = in_array($request->input('estado'), $estados, true) ? $request->input('estado') : '';
        $servicio = (int) $request->input('servicio') ?: null;
        $asesor = (int) $request->input('asesor') ?: null;
        $search = trim((string) $request->input('search', ''));
        $porPagina = min(100, max(5, (int) $request->input('per_page', 25)));

        $delDia = fn () => Turno::query()
            ->where('created_at', '>=', $desde)->where('created_at', '<', $hasta)
            ->when($servicio, fn ($q) => $q->where('servicio_id', $servicio))
            ->when($asesor, fn ($q) => $q->where('asesor_id', $asesor));

        $conteos = $delDia()->selectRaw('estado, COUNT(*) AS n')->groupBy('estado')->pluck('n', 'estado');

        $q = $delDia()->with(['servicio:id,nombre', 'caja:id,numero_caja', 'asesor:id,nombre_completo'])
            ->when($estado !== '', fn ($q) => $q->where('estado', $estado));

        if ($search !== '') {
            if (preg_match('/^([A-Za-z][A-Za-z\-]{0,9}?)\s*-?\s*(\d{1,4})$/u', $search, $m)) {
                $q->where('codigo', strtoupper($m[1]))->where('numero', (int) $m[2]);
            } elseif (ctype_digit($search)) {
                $q->where('numero', (int) $search);
            } else {
                $q->where(function ($w) use ($search) {
                    $w->where('codigo', strtoupper($search))
                      ->orWhereHas('servicio', fn ($s) => $s->where('nombre', 'like', '%' . $search . '%'))
                      ->orWhereHas('asesor', fn ($a) => $a->where('nombre_completo', 'like', '%' . $search . '%'));
                });
            }
        }

        if (in_array($estado, ['pendiente', 'aplazado'], true)) {
            $q->orderBy('created_at')->orderBy('id');
        } else {
            $q->orderByDesc('created_at')->orderByDesc('id');
        }

        $pagina = $q->paginate($porPagina, ['*'], 'page', max(1, (int) $request->input('page', 1)));
        $minutos = fn ($a, $b) => ($a && $b) ? intdiv(max(0, $b->getTimestamp() - $a->getTimestamp()), 60) : null;

        $filas = $pagina->getCollection()->map(function (Turno $t) use ($ahora, $umbrales, $minutos) {
            $prioritario = $t->esPrioritario();
            $esperando = in_array($t->estado, ['pendiente', 'aplazado'], true);
            $espera = $minutos($t->created_at, $esperando ? $ahora : $t->fecha_llamado);
            $limite = $prioritario ? $umbrales['prioritario'] : $umbrales['espera'];
            $duracion = $t->duracion_atencion !== null ? abs((int) $t->duracion_atencion) : null;

            return [
                'id' => $t->id,
                'codigo' => $t->codigo_completo,
                'prioritario' => $prioritario,
                'servicio' => $t->servicio?->nombre,
                'estado' => $t->estado,
                'observaciones' => $t->observaciones,
                'modulo' => $t->caja?->numero_caja,
                'asesor' => $t->asesor?->nombre_completo,
                'llego' => $t->created_at?->format('H:i'),
                'espera_min' => $espera,
                'espera_alerta' => $esperando && $espera !== null && $espera > $limite,
                'espera_limite' => $limite,
                'en_atencion_min' => $t->estado === 'llamado' ? $minutos($t->fecha_llamado, $ahora) : null,
                'duracion' => $duracion !== null ? sprintf('%d:%02d', intdiv($duracion, 60), $duracion % 60) : null,
            ];
        })->values();

        $conteo = fn ($e) => (int) ($conteos[$e] ?? 0);

        return [
            'turnos' => $filas,
            'meta' => [
                'pagina' => $pagina->currentPage(), 'ultima' => $pagina->lastPage(),
                'por_pagina' => $pagina->perPage(), 'total' => $pagina->total(),
                'desde' => $pagina->firstItem(), 'hasta' => $pagina->lastItem(),
            ],
            'conteos' => [
                'pendiente' => $conteo('pendiente'), 'llamado' => $conteo('llamado'), 'atendido' => $conteo('atendido'),
                'aplazado' => $conteo('aplazado'), 'cancelado' => $conteo('cancelado'), 'total' => (int) $conteos->sum(),
            ],
            'filtros' => ['estado' => $estado, 'servicio' => $servicio, 'asesor' => $asesor, 'search' => $search],
        ];
    }

    /**
     * Refresco del Inicio: cifras, cola por servicio y asesores conectados en una sola respuesta.
     * Los cálculos viven en App\Services\TableroService.
     */
    public function tablero(\App\Services\TableroService $tablero)
    {
        try {
            $datos = $tablero->generar();
        } catch (\Throwable $e) {
            report($e);
            // El Inicio conserva lo último que pintó y lo vuelve a intentar en el siguiente refresco.
            return response()->json(['error' => 'No se pudo calcular el tablero'], 500);
        }

        return response()
            ->json($datos, 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ->header('Cache-Control', 'no-store');
    }
}
