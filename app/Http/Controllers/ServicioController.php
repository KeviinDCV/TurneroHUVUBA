<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Servicio;
use Illuminate\Support\Facades\Auth;

class ServicioController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->get('search');

        $query = Servicio::with('servicioPadre');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%")
                  ->orWhere('nivel', 'like', "%{$search}%");
            });
        }

        $servicios = $query->orderBy('nivel')
                          ->orderBy('servicio_padre_id')
                          ->orderBy('orden')
                          ->paginate(10);

        // Obtener servicios principales para el select de servicios padre
        $serviciosPrincipales = Servicio::servicios()->activos()->orderBy('nombre')->get();

        return view('admin.servicios', compact('servicios', 'search', 'user', 'serviciosPrincipales'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'nombre' => 'required|string|max:255',
            'nivel' => 'required|in:servicio,subservicio',
            'codigo' => 'nullable|string|max:50|unique:servicios',
            'estado' => 'required|in:activo,inactivo',
            'descripcion' => 'nullable|string|max:500',
            'orden' => 'nullable|integer|min:0',
            'ocultar_turno' => 'boolean'
        ];

        // Si es subservicio, el servicio_padre_id es requerido
        if ($request->nivel === 'subservicio') {
            $rules['servicio_padre_id'] = 'required|exists:servicios,id';
        }

        $request->validate($rules);

        // Preparar datos
        $data = [
            'nombre' => $request->nombre,
            'nivel' => $request->nivel,
            'codigo' => $request->codigo,
            'estado' => $request->estado,
            'descripcion' => $request->descripcion,
            'orden' => $request->orden,
            'ocultar_turno' => $request->has('ocultar_turno'),
            'servicio_padre_id' => null, // Por defecto es servicio principal
        ];

        // Si es subservicio, asignar servicio padre
        if ($request->nivel === 'subservicio') {
            $data['servicio_padre_id'] = $request->servicio_padre_id;
        }

        // Si no se proporciona orden, calcular automáticamente
        if (empty($data['orden'])) {
            if ($request->nivel === 'servicio') {
                $maxOrden = Servicio::where('servicio_padre_id', null)->max('orden');
            } else {
                $maxOrden = Servicio::where('servicio_padre_id', $request->servicio_padre_id)->max('orden');
            }
            $data['orden'] = ($maxOrden ?? 0) + 1;
        }

        Servicio::create($data);

        // Si es una petición AJAX, devolver JSON
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Servicio creado correctamente'
            ]);
        }

        return redirect()->route('admin.servicios')
            ->with('success', 'Servicio creado correctamente');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Servicio $servicio)
    {
        $rules = [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:500',
            'nivel' => 'required|in:servicio,subservicio',
            'estado' => 'required|in:activo,inactivo',
            'codigo' => 'nullable|string|max:50|unique:servicios,codigo,' . $servicio->id,
            'orden' => 'nullable|integer|min:0',
            'ocultar_turno' => 'boolean'
        ];

        // Si es subservicio, el servicio_padre_id es requerido
        if ($request->nivel === 'subservicio') {
            $rules['servicio_padre_id'] = 'required|exists:servicios,id';
        }

        $request->validate($rules);

        $data = $request->all();

        // Manejar el checkbox ocultar_turno
        $data['ocultar_turno'] = $request->has('ocultar_turno');

        // Si es servicio principal, asegurar que servicio_padre_id sea null
        if ($request->nivel === 'servicio') {
            $data['servicio_padre_id'] = null;
        }

        $servicio->update($data);

        // Si es una petición AJAX, devolver JSON
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Servicio actualizado correctamente'
            ]);
        }

        return redirect()->route('admin.servicios')
            ->with('success', 'Servicio actualizado correctamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Servicio $servicio)
    {
        // Verificar si el servicio tiene subservicios
        if ($servicio->esServicioPrincipal() && $servicio->subservicios()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el servicio porque tiene subservicios asociados'
            ], 400);
        }

        $servicio->delete();

        return response()->json([
            'success' => true,
            'message' => 'Servicio eliminado correctamente'
        ]);
    }

    /**
     * Get servicios for AJAX requests
     */
    public function getServicios(Request $request)
    {
        $search = $request->get('search');

        $query = Servicio::with('servicioPadre');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%");
            });
        }

        $servicios = $query->orderBy('nivel')
                          ->orderBy('servicio_padre_id')
                          ->orderBy('orden')
                          ->get();

        return response()->json($servicios);
    }

    /**
     * Get a specific servicio for editing
     */
    public function show(Servicio $servicio)
    {
        $servicio->load('servicioPadre', 'subservicios');
        return response()->json($servicio);
    }
}
