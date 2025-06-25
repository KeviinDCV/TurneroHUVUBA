<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Servicio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AsignacionServicioController extends Controller
{
    /**
     * Mostrar la página de asignación de servicios
     */
    public function index()
    {
        $user = Auth::user();
        
        // Obtener todos los usuarios (solo asesores para asignación)
        $usuarios = User::where('rol', 'Asesor')->orderBy('nombre_completo')->get();
        
        // Obtener todos los servicios activos
        $servicios = Servicio::where('estado', 'activo')
                            ->with('servicioPadre')
                            ->orderBy('nivel')
                            ->orderBy('servicio_padre_id')
                            ->orderBy('orden')
                            ->get();
        
        return view('admin.asignacion-servicios', compact('user', 'usuarios', 'servicios'));
    }

    /**
     * Obtener servicios asignados y no asignados para un usuario específico
     */
    public function getServiciosUsuario($userId)
    {
        $usuario = User::findOrFail($userId);
        
        // Obtener servicios asignados al usuario
        $serviciosAsignados = $usuario->servicios()
                                    ->where('estado', 'activo')
                                    ->with('servicioPadre')
                                    ->orderBy('nivel')
                                    ->orderBy('servicio_padre_id')
                                    ->orderBy('orden')
                                    ->get();
        
        // Obtener servicios NO asignados al usuario
        $serviciosDisponibles = Servicio::where('estado', 'activo')
                                      ->whereNotIn('id', $serviciosAsignados->pluck('id'))
                                      ->with('servicioPadre')
                                      ->orderBy('nivel')
                                      ->orderBy('servicio_padre_id')
                                      ->orderBy('orden')
                                      ->get();
        
        return response()->json([
            'usuario' => $usuario,
            'serviciosAsignados' => $serviciosAsignados,
            'serviciosDisponibles' => $serviciosDisponibles
        ]);
    }

    /**
     * Asignar un servicio a un usuario
     */
    public function asignarServicio(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'servicio_id' => 'required|exists:servicios,id'
        ]);

        $usuario = User::findOrFail($request->user_id);
        $servicio = Servicio::findOrFail($request->servicio_id);

        // Verificar que el usuario sea asesor
        if ($usuario->rol !== 'Asesor') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden asignar servicios a usuarios con rol de Asesor'
            ], 400);
        }

        // Verificar que el servicio esté activo
        if ($servicio->estado !== 'activo') {
            return response()->json([
                'success' => false,
                'message' => 'No se pueden asignar servicios inactivos'
            ], 400);
        }

        // Verificar si ya está asignado
        if ($usuario->tieneServicio($request->servicio_id)) {
            return response()->json([
                'success' => false,
                'message' => 'El servicio ya está asignado a este usuario'
            ], 400);
        }

        // Recopilar servicios a asignar
        $serviciosAAsignar = [$request->servicio_id];
        $serviciosAsignados = 1;

        // Si es un servicio padre, también asignar todos sus subservicios activos
        if ($servicio->esServicioPrincipal()) {
            $subservicios = $servicio->subservicios()->where('estado', 'activo')->get();

            foreach ($subservicios as $subservicio) {
                if (!$usuario->tieneServicio($subservicio->id)) {
                    $serviciosAAsignar[] = $subservicio->id;
                    $serviciosAsignados++;
                }
            }
        }

        // Asignar todos los servicios
        $usuario->servicios()->attach($serviciosAAsignar);

        // Mensaje personalizado según la cantidad de servicios asignados
        if ($serviciosAsignados === 1) {
            $message = 'Servicio asignado correctamente';
        } else {
            $message = "Servicio padre y {$serviciosAsignados} servicios asignados correctamente";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'servicios_asignados' => $serviciosAsignados
        ]);
    }

    /**
     * Desasignar un servicio de un usuario
     */
    public function desasignarServicio(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'servicio_id' => 'required|exists:servicios,id'
        ]);

        $usuario = User::findOrFail($request->user_id);
        $servicio = Servicio::findOrFail($request->servicio_id);

        // Verificar si el servicio está asignado
        if (!$usuario->tieneServicio($request->servicio_id)) {
            return response()->json([
                'success' => false,
                'message' => 'El servicio no está asignado a este usuario'
            ], 400);
        }

        // Recopilar servicios a desasignar
        $serviciosADesasignar = [$request->servicio_id];
        $serviciosDesasignados = 1;

        // Si es un servicio padre, también desasignar todos sus subservicios
        if ($servicio->esServicioPrincipal()) {
            $subservicios = $servicio->subservicios()->get();

            foreach ($subservicios as $subservicio) {
                if ($usuario->tieneServicio($subservicio->id)) {
                    $serviciosADesasignar[] = $subservicio->id;
                    $serviciosDesasignados++;
                }
            }
        }

        // Desasignar todos los servicios
        $usuario->servicios()->detach($serviciosADesasignar);

        // Mensaje personalizado según la cantidad de servicios desasignados
        if ($serviciosDesasignados === 1) {
            $message = 'Servicio desasignado correctamente';
        } else {
            $message = "Servicio padre y {$serviciosDesasignados} servicios desasignados correctamente";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'servicios_desasignados' => $serviciosDesasignados
        ]);
    }

    /**
     * Asignar múltiples servicios a un usuario
     */
    public function asignarMultiplesServicios(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'servicio_ids' => 'required|array',
            'servicio_ids.*' => 'exists:servicios,id'
        ]);

        $usuario = User::findOrFail($request->user_id);

        // Verificar que el usuario sea asesor
        if ($usuario->rol !== 'Asesor') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden asignar servicios a usuarios con rol de Asesor'
            ], 400);
        }

        // Verificar que todos los servicios estén activos
        $servicios = Servicio::whereIn('id', $request->servicio_ids)->get();
        $serviciosInactivos = $servicios->where('estado', 'inactivo');
        
        if ($serviciosInactivos->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se pueden asignar servicios inactivos'
            ], 400);
        }

        // Filtrar servicios que no estén ya asignados
        $serviciosYaAsignados = $usuario->servicios()->whereIn('servicio_id', $request->servicio_ids)->pluck('servicio_id');
        $serviciosNuevos = collect($request->servicio_ids)->diff($serviciosYaAsignados);

        if ($serviciosNuevos->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Todos los servicios seleccionados ya están asignados a este usuario'
            ], 400);
        }

        // Asignar los servicios nuevos
        $usuario->servicios()->attach($serviciosNuevos->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Servicios asignados correctamente',
            'servicios_asignados' => $serviciosNuevos->count()
        ]);
    }
}
