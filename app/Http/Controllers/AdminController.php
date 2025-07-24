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

        // Obtener usuarios activos reales
        $usuariosActivos = $this->getUsuariosActivosData();

        // Obtener estadísticas de turnos por servicio
        $turnosPorServicio = $this->getTurnosPorServicioData();

        // Obtener estadísticas de turnos por asesor
        $turnosPorAsesor = $this->getTurnosPorAsesorData();

        // Obtener turnos en cola por servicio
        $turnosEnCola = $this->getTurnosEnColaData();

        return view('admin.dashboard', compact('user', 'usuariosActivos', 'turnosPorServicio', 'turnosPorAsesor', 'turnosEnCola'));
    }

    /**
     * Mostrar listado de usuarios con buscador
     */
    public function users(Request $request)
    {
        $user = Auth::user();
        $search = $request->input('search');

        $query = User::query();

        // Aplicar filtro de búsqueda si existe
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nombre_completo', 'like', "%{$search}%")
                  ->orWhere('cedula', 'like', "%{$search}%")
                  ->orWhere('correo_electronico', 'like', "%{$search}%")
                  ->orWhere('nombre_usuario', 'like', "%{$search}%")
                  ->orWhere('rol', 'like', "%{$search}%");
            });
        }

        // Obtener usuarios paginados
        $users = $query->paginate(10);

        return view('admin.users', compact('user', 'users', 'search'));
    }

    // Ya no necesitamos el método createUser() porque usamos un modal en la misma página

    /**
     * Procesar la creación de un nuevo usuario
     */
    public function storeUser(Request $request)
    {
        // Log para debugging
        \Log::info('Intento de creación de usuario', [
            'datos_recibidos' => $request->all(),
            'usuario_actual' => Auth::user()->nombre_usuario ?? 'No autenticado'
        ]);

        try {
            // Validar los datos del formulario
            $validated = $request->validate([
                'nombre_completo' => 'required|string|max:255',
                'cedula' => 'required|string|max:20|unique:users,cedula',
                'correo_electronico' => 'required|email|max:255|unique:users,correo_electronico',
                'nombre_usuario' => 'required|string|max:255|unique:users,nombre_usuario',
                'rol' => 'required|in:Administrador,Asesor',
                'password' => 'required|string|min:8|confirmed',
            ]);

            \Log::info('Validación exitosa', ['datos_validados' => $validated]);

            // Crear nuevo usuario
            $nuevoUsuario = User::create([
                'nombre_completo' => $validated['nombre_completo'],
                'cedula' => $validated['cedula'],
                'correo_electronico' => $validated['correo_electronico'],
                'nombre_usuario' => $validated['nombre_usuario'],
                'rol' => $validated['rol'],
                'password' => Hash::make($validated['password']),
            ]);

            \Log::info('Usuario creado exitosamente', ['usuario_id' => $nuevoUsuario->id]);

            // Redireccionar con mensaje de éxito
            return redirect()->route('admin.users')
                ->with('success', 'Usuario creado correctamente');

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Error de validación al crear usuario', [
                'errores' => $e->errors(),
                'datos' => $request->all()
            ]);
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error inesperado al crear usuario', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'datos' => $request->all()
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Ocurrió un error inesperado al crear el usuario. Por favor, inténtalo de nuevo.']);
        }
    }

    /**
     * Obtener datos de un usuario para editar
     */
    public function getUser($id)
    {
        $userToEdit = User::findOrFail($id);
        return response()->json($userToEdit);
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
            'cedula' => 'required|string|max:20|unique:users,cedula,' . $id,
            'correo_electronico' => 'required|email|max:255|unique:users,correo_electronico,' . $id,
            'nombre_usuario' => 'required|string|max:255|unique:users,nombre_usuario,' . $id,
            'rol' => 'required|in:Administrador,Asesor',
        ];

        // Si se está cambiando la contraseña, validarla
        if ($request->filled('password')) {
            $rules['password'] = 'required|string|min:8|confirmed';
        }

        $validated = $request->validate($rules);

        // Preparar datos para actualizar
        $updateData = [
            'nombre_completo' => $validated['nombre_completo'],
            'cedula' => $validated['cedula'],
            'correo_electronico' => $validated['correo_electronico'],
            'nombre_usuario' => $validated['nombre_usuario'],
            'rol' => $validated['rol'],
        ];

        // Actualizar contraseña solo si se proporcionó
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($validated['password']);
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
            // Obtener todos los usuarios con session_id
            $usersWithSessions = User::whereNotNull('session_id')->get();
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
                ->count();

            if ($expiredSessionsCount > 0) {
                DB::table('sessions')
                    ->where('last_activity', '<', now()->subMinutes(15)->timestamp)
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
            // Obtener todos los usuarios con session_id (activos)
            $usersWithSessions = User::whereNotNull('session_id')->get();
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

            // Limpiar todas las sesiones de la tabla sessions
            $allSessionsCount = DB::table('sessions')->count();
            if ($allSessionsCount > 0) {
                DB::table('sessions')->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Todas las sesiones han sido limpiadas exitosamente. Se han liberado todas las cajas asignadas.',
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
                    $deletedCount = Turno::whereDate('fecha_creacion', Carbon::today())
                        ->whereIn('estado', ['pendiente', 'aplazado'])
                        ->delete();

                    $message = "Se eliminaron {$deletedCount} turnos pendientes y aplazados del día actual.";
                    break;

                case 'today':
                    // Eliminar todos los turnos del día actual
                    $deletedCount = Turno::whereDate('fecha_creacion', Carbon::today())
                        ->delete();

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

                    $deletedCount = Turno::whereDate('fecha_creacion', Carbon::today())
                        ->where('servicio_id', $serviceId)
                        ->delete();

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
            ->select('id', 'nombre_completo', 'nombre_usuario', 'rol', 'estado_asesor', 'last_activity', 'session_id', 'session_start')
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

            return [
                'id' => $usuario->id,
                'name' => $usuario->nombre_completo,
                'nombre_usuario' => $usuario->nombre_usuario,
                'rol' => $usuario->rol,
                'availability' => $disponibilidad,
                'status' => strtoupper($usuario->getEstadoFormateado()),
                'last_activity' => $usuario->last_activity->diffForHumans(),
                'tiempo_sesion' => $usuario->getTiempoSesionActiva(),
                'caja' => $caja,
                'is_online' => true
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
        $turnosAtendidos = Turno::select('users.nombre_usuario as asesor_usuario', DB::raw('COUNT(*) as total_atendidos'))
            ->join('users', 'turnos.asesor_id', '=', 'users.id')
            ->where('turnos.estado', 'atendido')
            ->whereDate('turnos.fecha_creacion', Carbon::today())
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
}
