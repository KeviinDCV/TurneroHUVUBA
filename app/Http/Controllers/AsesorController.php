<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Caja;
use App\Models\User;

class AsesorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Mostrar la página de selección de caja para asesores
     */
    public function seleccionarCaja()
    {
        $user = Auth::user();
        
        // Verificar que el usuario sea asesor
        if (!$user->esAsesor()) {
            return redirect()->route('admin.dashboard');
        }

        // Obtener todas las cajas activas
        $cajas = Caja::activas()->orderBy('numero_caja')->get();

        return view('asesor.seleccionar-caja', compact('user', 'cajas'));
    }

    /**
     * Procesar la selección de caja y redirigir al dashboard del asesor
     */
    public function procesarSeleccionCaja(Request $request)
    {
        $user = Auth::user();
        
        // Verificar que el usuario sea asesor
        if (!$user->esAsesor()) {
            return redirect()->route('admin.dashboard');
        }

        $request->validate([
            'caja_id' => 'required|exists:cajas,id'
        ]);

        $caja = Caja::findOrFail($request->caja_id);

        // Verificar que la caja esté activa
        if ($caja->estado !== 'activa') {
            return back()->withErrors([
                'caja_id' => 'La caja seleccionada no está disponible.'
            ]);
        }

        // Guardar la caja seleccionada en la sesión
        session(['caja_seleccionada' => $caja->id]);

        // Redirigir al dashboard del asesor
        return redirect()->route('asesor.dashboard');
    }

    /**
     * Mostrar el dashboard del asesor
     */
    public function dashboard()
    {
        $user = Auth::user();
        
        // Verificar que el usuario sea asesor
        if (!$user->esAsesor()) {
            return redirect()->route('admin.dashboard');
        }

        // Verificar que tenga una caja seleccionada
        $cajaId = session('caja_seleccionada');
        if (!$cajaId) {
            return redirect()->route('asesor.seleccionar-caja');
        }

        $caja = Caja::find($cajaId);
        if (!$caja || $caja->estado !== 'activa') {
            // Si la caja ya no existe o está inactiva, redirigir a selección
            session()->forget('caja_seleccionada');
            return redirect()->route('asesor.seleccionar-caja')
                ->withErrors(['caja' => 'La caja seleccionada ya no está disponible.']);
        }

        return view('asesor.dashboard', compact('user', 'caja'));
    }

    /**
     * Cambiar de caja
     */
    public function cambiarCaja()
    {
        $user = Auth::user();
        
        // Verificar que el usuario sea asesor
        if (!$user->esAsesor()) {
            return redirect()->route('admin.dashboard');
        }

        // Limpiar la caja de la sesión
        session()->forget('caja_seleccionada');

        return redirect()->route('asesor.seleccionar-caja');
    }
}
