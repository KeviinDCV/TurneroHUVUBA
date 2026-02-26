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
            
            $validated = $request->validate($rules);

            \Log::info('Validación exitosa', ['datos_validados' => $validated]);

            // Crear nuevo usuario
            // Si no se proporciona contraseña, usar una cadena vacía hasheada
            $password = $request->filled('password') ? $validated['password'] : '';
            
            $nuevoUsuario = User::create([
                'nombre_completo' => $validated['nombre_completo'],
                'cedula' => $validated['cedula'] ?? null,
                'correo_electronico' => $validated['correo_electronico'] ?? null,
                'nombre_usuario' => $validated['nombre_usuario'],
                'rol' => $validated['rol'],
                'password' => Hash::make($password),
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

        $validated = $request->validate($rules);

        // Preparar datos para actualizar
        $updateData = [
            'nombre_completo' => $validated['nombre_completo'],
            'cedula' => $validated['cedula'] ?? null,
            'correo_electronico' => $validated['correo_electronico'] ?? null,
            'nombre_usuario' => $validated['nombre_usuario'],
            'rol' => $validated['rol'],
        ];

        // Actualizar contraseña si el campo está presente (incluso si está vacío)
        if ($request->has('password')) {
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
     * Vista de gestión de turnos del día
     */
    public function turnos(Request $request)
    {
        $user = Auth::user();
        
        // Obtener filtros
        $estado = $request->input('estado');
        $servicio = $request->input('servicio');
        $asesor = $request->input('asesor');
        $search = $request->input('search');
        
        // Obtener todos los servicios para el filtro
        $servicios = Servicio::where('estado', 'activo')->orderBy('nombre')->get();
        
        // Obtener todos los asesores para el filtro
        $asesores = User::whereIn('rol', ['asesor', 'administrador'])
            ->orderBy('nombre_completo')
            ->get();
        
        // Construir query de turnos del día
        $query = Turno::with(['servicio', 'caja', 'asesor'])
            ->whereDate('fecha_creacion', Carbon::today())
            ->orderBy('fecha_creacion', 'desc');
        
        // Aplicar filtros
        if ($estado) {
            $query->where('estado', $estado);
        }
        
        if ($servicio) {
            $query->where('servicio_id', $servicio);
        }
        
        if ($asesor) {
            $query->where('asesor_id', $asesor);
        }
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('codigo', 'like', "%{$search}%")
                  ->orWhere('numero', 'like', "%{$search}%")
                  ->orWhereHas('servicio', function($sq) use ($search) {
                      $sq->where('nombre', 'like', "%{$search}%");
                  });
            });
        }
        
        $turnos = $query->paginate(20);
        
        // Estadísticas rápidas
        $estadisticas = [
            'total' => Turno::whereDate('fecha_creacion', Carbon::today())->count(),
            'pendientes' => Turno::whereDate('fecha_creacion', Carbon::today())->where('estado', 'pendiente')->count(),
            'llamados' => Turno::whereDate('fecha_creacion', Carbon::today())->where('estado', 'llamado')->count(),
            'atendidos' => Turno::whereDate('fecha_creacion', Carbon::today())->where('estado', 'atendido')->count(),
            'aplazados' => Turno::whereDate('fecha_creacion', Carbon::today())->where('estado', 'aplazado')->count(),
            'cancelados' => Turno::whereDate('fecha_creacion', Carbon::today())->where('estado', 'cancelado')->count(),
        ];
        
        return view('admin.turnos', compact('user', 'turnos', 'servicios', 'asesores', 'estadisticas', 'estado', 'servicio', 'asesor', 'search'));
    }

    /**
     * API para obtener turnos del día (actualización en tiempo real)
     */
    public function getTurnosHoy(Request $request)
    {
        $estado = $request->input('estado');
        $servicio = $request->input('servicio');
        $asesor = $request->input('asesor');
        $search = $request->input('search');
        
        $query = Turno::with(['servicio', 'caja', 'asesor'])
            ->whereDate('fecha_creacion', Carbon::today())
            ->orderBy('fecha_creacion', 'desc');
        
        if ($estado) {
            $query->where('estado', $estado);
        }
        
        if ($servicio) {
            $query->where('servicio_id', $servicio);
        }
        
        if ($asesor) {
            $query->where('asesor_id', $asesor);
        }
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('codigo', 'like', "%{$search}%")
                  ->orWhere('numero', 'like', "%{$search}%");
            });
        }
        
        $turnos = $query->get()->map(function($turno) {
            return [
                'id' => $turno->id,
                'codigo' => $turno->codigo,
                'numero' => $turno->numero,
                'codigo_completo' => $turno->codigo_completo,
                'servicio' => $turno->servicio ? [
                    'id' => $turno->servicio->id,
                    'nombre' => $turno->servicio->nombre,
                    'codigo' => $turno->servicio->codigo,
                ] : null,
                'caja' => $turno->caja ? [
                    'id' => $turno->caja->id,
                    'nombre' => $turno->caja->nombre,
                    'numero' => $turno->caja->numero_caja,
                ] : null,
                'asesor' => $turno->asesor ? [
                    'id' => $turno->asesor->id,
                    'nombre' => $turno->asesor->nombre_completo,
                ] : null,
                'estado' => $turno->estado,
                'prioridad' => $turno->prioridad,
                'prioridad_letra' => $turno->prioridad_letra,
                'prioridad_color' => $turno->prioridad_color,
                'fecha_creacion' => $turno->fecha_creacion ? $turno->fecha_creacion->format('H:i:s') : null,
                'fecha_llamado' => $turno->fecha_llamado ? $turno->fecha_llamado->format('H:i:s') : null,
                'fecha_atencion' => $turno->fecha_atencion ? $turno->fecha_atencion->format('H:i:s') : null,
                'duracion_atencion' => $turno->duracion_atencion,
                'duracion_formateada' => $turno->duracion_atencion 
                    ? gmdate('i:s', abs($turno->duracion_atencion)) 
                    : null,
                'observaciones' => $turno->observaciones,
            ];
        });
        
        $estadisticas = [
            'total' => Turno::whereDate('fecha_creacion', Carbon::today())->count(),
            'pendientes' => Turno::whereDate('fecha_creacion', Carbon::today())->where('estado', 'pendiente')->count(),
            'llamados' => Turno::whereDate('fecha_creacion', Carbon::today())->where('estado', 'llamado')->count(),
            'atendidos' => Turno::whereDate('fecha_creacion', Carbon::today())->where('estado', 'atendido')->count(),
            'aplazados' => Turno::whereDate('fecha_creacion', Carbon::today())->where('estado', 'aplazado')->count(),
            'cancelados' => Turno::whereDate('fecha_creacion', Carbon::today())->where('estado', 'cancelado')->count(),
        ];
        
        return response()->json([
            'turnos' => $turnos,
            'estadisticas' => $estadisticas,
        ]);
    }
}
