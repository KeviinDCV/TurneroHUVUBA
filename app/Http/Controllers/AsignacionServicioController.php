<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Servicio;
use App\Services\TableroService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AsignacionServicioController extends Controller
{
    /**
     * Matriz de cobertura: asesores en filas y, en columnas, lo que el kiosco reparte (los subservicios de cada
     * sección, o la sección si no tiene subservicios). Todo llega en una sola carga; marcar o quitar es una
     * petición por casilla.
     */
    public function index()
    {
        $user = Auth::user();

        $asesores = User::where('rol', 'Asesor')->orderBy('nombre_completo')->get(['id', 'nombre_completo', 'nombre_usuario']);

        // Solo lo activo: lo inactivo no sale en el kiosco (y asignarlo está bloqueado).
        $servicios = Servicio::where('estado', 'activo')->orderBy('orden')->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo', 'nivel', 'servicio_padre_id', 'ocultar_turno']);

        $existe = $servicios->pluck('id')->flip();
        $hijosDe = $servicios->filter(fn ($s) => $s->servicio_padre_id && isset($existe[$s->servicio_padre_id]))
            ->groupBy('servicio_padre_id');
        $columna = fn (Servicio $s) => [
            'id' => (int) $s->id,
            'nombre' => $s->nombre,
            'codigo' => $s->codigo,
            'ocultar' => (bool) $s->ocultar_turno,
        ];

        // Bajo CITAS, "Citas Medicina General" se lee "Medicina General": la sección ya está en el encabezado.
        $corto = function (string $hijo, string $seccion) {
            $partes = preg_split('/\s+/', trim($hijo));
            $p = Str::lower(Str::ascii($partes[0]));
            $s1 = Str::lower(Str::ascii(preg_split('/\s+/', trim($seccion))[0]));
            if (count($partes) > 1 && min(strlen($p), strlen($s1)) >= 4 && (str_starts_with($s1, $p) || str_starts_with($p, $s1))) {
                return Str::ucfirst(implode(' ', array_slice($partes, 1)));
            }
            return $hijo;
        };

        $secciones = $servicios
            ->reject(fn ($s) => $s->servicio_padre_id && isset($existe[$s->servicio_padre_id]))
            ->map(fn (Servicio $s) => $columna($s) + ['corto' => $s->nombre, 'hijos' => $hijosDe->get($s->id, collect())
                ->map(fn (Servicio $h) => $columna($h) + ['corto' => $corto($h->nombre, $s->nombre)])->values()->all()])
            ->values()
            ->all();

        $asignados = DB::table('user_servicio')
            ->whereIn('user_id', $asesores->pluck('id'))
            ->get(['user_id', 'servicio_id'])
            ->groupBy('user_id')
            ->map(fn ($filas) => $filas->pluck('servicio_id')->map(fn ($id) => (int) $id)->values()->all());

        // Quién está conectado ahora (módulo y estado), con el mismo cálculo del Inicio.
        try {
            $conectados = collect(app(TableroService::class)->generar()['asesores'])->keyBy('id');
        } catch (\Throwable $e) {
            report($e);
            $conectados = collect();
        }

        $matriz = [
            'asesores' => $asesores->map(fn (User $a) => [
                'id' => (int) $a->id,
                'nombre' => $a->nombre_completo ?: $a->nombre_usuario,
                'modulo' => $conectados->get($a->id)['modulo'] ?? null,
                'estado' => $conectados->get($a->id)['estado'] ?? null,
            ])->values()->all(),
            'secciones' => $secciones,
            'asignados' => (object) $asignados->all(),
        ];

        return view('admin.asignacion-servicios', compact('user', 'matriz'));
    }

    /**
     * Servicios asignados y disponibles de un asesor (del asesor solo se devuelve lo necesario).
     */
    public function getServiciosUsuario($userId)
    {
        $usuario = User::findOrFail($userId);

        $serviciosAsignados = $usuario->servicios()
                                    ->where('estado', 'activo')
                                    ->with('servicioPadre')
                                    ->orderBy('nivel')
                                    ->orderBy('servicio_padre_id')
                                    ->orderBy('orden')
                                    ->get();

        $serviciosDisponibles = Servicio::where('estado', 'activo')
                                      ->whereNotIn('id', $serviciosAsignados->pluck('id'))
                                      ->with('servicioPadre')
                                      ->orderBy('nivel')
                                      ->orderBy('servicio_padre_id')
                                      ->orderBy('orden')
                                      ->get();

        return response()->json([
            'usuario' => ['id' => $usuario->id, 'nombre_completo' => $usuario->nombre_completo],
            'serviciosAsignados' => $serviciosAsignados,
            'serviciosDisponibles' => $serviciosDisponibles
        ]);
    }

    /**
     * Asignar un servicio a un asesor. Una sección arrastra a sus subservicios activos. Si ya estaba asignado
     * no es un error: la casilla queda como se pidió.
     */
    public function asignarServicio(Request $request)
    {
        return $this->cambiar($request, true);
    }

    /**
     * Quitar un servicio a un asesor. Una sección se lleva a sus subservicios.
     */
    public function desasignarServicio(Request $request)
    {
        return $this->cambiar($request, false);
    }

    private function cambiar(Request $request, bool $asignar)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'servicio_id' => 'required|exists:servicios,id'
        ]);

        $usuario = User::findOrFail($request->user_id);
        $servicio = Servicio::with('subservicios')->findOrFail($request->servicio_id);

        if ($asignar && $usuario->rol !== 'Asesor') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden asignar servicios a usuarios con rol de Asesor'
            ], 400);
        }

        if ($asignar && $servicio->estado !== 'activo') {
            return response()->json([
                'success' => false,
                'message' => 'No se pueden asignar servicios inactivos'
            ], 400);
        }

        DB::transaction(function () use ($usuario, $servicio, $asignar) {
            $ids = [$servicio->id];
            if ($servicio->esServicioPrincipal()) {
                $hijos = $asignar ? $servicio->subservicios->where('estado', 'activo') : $servicio->subservicios;
                $ids = array_merge($ids, $hijos->pluck('id')->all());
            }

            $actuales = $usuario->servicios()->pluck('servicios.id')->all();
            if ($asignar) {
                $usuario->servicios()->attach(array_values(array_diff($ids, $actuales)));
            } else {
                $usuario->servicios()->detach($ids);
            }

            // El padre acompaña a sus subservicios: el panel del asesor los agrupa bajo él. Queda asignado
            // mientras el asesor tenga alguno (qué turnos se llaman no cambia: siempre son los subservicios asignados).
            if ($servicio->servicio_padre_id) {
                $padre = Servicio::find($servicio->servicio_padre_id);
                if ($padre) {
                    $conHijos = $usuario->servicios()->where('servicio_padre_id', $padre->id)->exists();
                    $conPadre = $usuario->servicios()->where('servicios.id', $padre->id)->exists();
                    if ($conHijos && !$conPadre && $padre->estado === 'activo') {
                        $usuario->servicios()->attach($padre->id);
                    } elseif (!$conHijos && $conPadre) {
                        $usuario->servicios()->detach($padre->id);
                    }
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => $asignar ? 'Servicio asignado' : 'Servicio quitado',
            'asignados' => $usuario->servicios()->pluck('servicios.id')->map(fn ($id) => (int) $id)->values(),
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
