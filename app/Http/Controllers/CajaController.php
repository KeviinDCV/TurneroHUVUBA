<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CajaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Módulos en una sola vista: el catálogo de cajas con la ocupación de ahora mismo (asesor, turno en
     * curso, minutos), con el mismo cálculo del Inicio. Sin paginación: son pocos y caben en pantalla.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        try {
            $modulos = app(\App\Services\TableroService::class)->generar()['modulos'];
        } catch (\Throwable $e) {
            report($e);
            // Si el cálculo en vivo fallara, al menos el catálogo se puede administrar.
            $modulos = Caja::orderBy('numero_caja')->get()->map(fn (Caja $c) => [
                'id' => (int) $c->id, 'nombre' => $c->nombre, 'descripcion' => $c->descripcion,
                'activa' => $c->estado === 'activa', 'numero' => (int) $c->numero_caja, 'ubicacion' => $c->ubicacion,
                'estado' => $c->estado === 'activa' ? 'cerrado' : 'inhabilitado',
                'asesor' => null, 'turno' => null, 'atendidos_hoy' => null,
            ])->all();
        }

        // Turnos del historial atendidos en cada módulo: quedarían sin módulo (caja_id = NULL) si se elimina.
        $turnosPorModulo = \App\Models\Turno::whereNotNull('caja_id')
            ->selectRaw('caja_id, COUNT(*) AS n')->groupBy('caja_id')->pluck('n', 'caja_id');

        return view('admin.cajas', compact('user', 'modulos', 'turnosPorModulo'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Log de inicio
            \Log::info('CajaController@store - Iniciando creación de caja', [
                'request_data' => $request->all(),
                'user_id' => auth()->id(),
                'ip' => $request->ip()
            ]);

            $request->validate([
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string|max:500',
                'ubicacion' => 'nullable|string|max:255',
                'numero_caja' => 'required|integer|unique:cajas|min:1',
                'estado' => 'required|in:activa,inactiva'
            ]);

            \Log::info('CajaController@store - Validación exitosa');

            $caja = Caja::create($request->all());

            \Log::info('CajaController@store - Caja creada exitosamente', [
                'caja_id' => $caja->id,
                'caja_data' => $caja->toArray()
            ]);

            // Si es una petición AJAX, devolver JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Caja creada correctamente',
                    'caja' => $caja
                ]);
            }

            return redirect()->route('admin.cajas')
                ->with('success', 'Caja creada correctamente');

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('CajaController@store - Error de validación', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            throw $e;
        } catch (\Exception $e) {
            \Log::error('CajaController@store - Error general', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear la caja: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Error al crear la caja: ' . $e->getMessage()]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            // Log de inicio
            \Log::info('CajaController@update - Iniciando actualización de caja', [
                'caja_id' => $id,
                'request_data' => $request->all(),
                'user_id' => auth()->id(),
                'ip' => $request->ip()
            ]);

            $caja = Caja::findOrFail($id);

            \Log::info('CajaController@update - Caja encontrada', [
                'caja_actual' => $caja->toArray()
            ]);

            $request->validate([
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string|max:500',
                'ubicacion' => 'nullable|string|max:255',
                'numero_caja' => 'required|integer|unique:cajas,numero_caja,' . $id . '|min:1',
                'estado' => 'required|in:activa,inactiva'
            ]);

            \Log::info('CajaController@update - Validación exitosa');

            $caja->update($request->all());

            \Log::info('CajaController@update - Caja actualizada exitosamente', [
                'caja_actualizada' => $caja->fresh()->toArray()
            ]);

            // Si es una petición AJAX, devolver JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Caja actualizada correctamente',
                    'caja' => $caja->fresh()
                ]);
            }

            return redirect()->route('admin.cajas')
                ->with('success', 'Caja actualizada correctamente');

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('CajaController@update - Error de validación', [
                'caja_id' => $id,
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            throw $e;
        } catch (\Exception $e) {
            \Log::error('CajaController@update - Error general', [
                'caja_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al actualizar la caja: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Error al actualizar la caja: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $caja = Caja::findOrFail($id);

        // No se elimina un módulo en uso: el asesor perdería su puesto en plena atención.
        if ($caja->estado === 'activa' && $caja->asesor_activo_id) {
            $asesor = \App\Models\User::find($caja->asesor_activo_id)?->nombre_completo ?? 'un asesor';
            $mensaje = "El módulo está en uso por {$asesor}. Elimínalo cuando lo libere.";
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => $mensaje], 422);
            }
            return redirect()->route('admin.cajas')->with('error', $mensaje);
        }

        $caja->delete();

        // Si es una petición AJAX, devolver JSON
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Caja eliminada correctamente'
            ]);
        }

        return redirect()->route('admin.cajas')
            ->with('success', 'Caja eliminada correctamente');
    }
}
