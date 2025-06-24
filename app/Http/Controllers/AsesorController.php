<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Caja;
use App\Models\User;
use App\Models\Servicio;
use App\Models\Turno;
use Carbon\Carbon;

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

        // Obtener cajas activas con información de ocupación
        $cajas = Caja::activas()->with('asesorActivo')->orderBy('numero_caja')->get();

        // Marcar cajas como disponibles u ocupadas
        $cajas = $cajas->map(function($caja) use ($user) {
            $caja->disponible = !$caja->estaOcupada() || $caja->estaOcupadaPor($user->id);
            $caja->ocupada_por_mi = $caja->estaOcupadaPor($user->id, session()->getId());
            $caja->nombre_asesor = $caja->asesorActivo ? $caja->asesorActivo->nombre_completo : null;
            return $caja;
        });

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

        // Verificar si la caja está ocupada por otro asesor
        if ($caja->estaOcupada() && !$caja->estaOcupadaPor($user->id)) {
            $asesorActivo = $caja->asesorActivo;
            $nombreAsesor = $asesorActivo ? $asesorActivo->nombre_completo : 'otro usuario';

            return back()->withErrors([
                'caja_ocupada' => "La caja {$caja->nombre} ya está siendo utilizada por {$nombreAsesor}. Por favor selecciona otra caja."
            ]);
        }

        // Liberar cualquier caja anterior que el usuario pudiera tener asignada
        Caja::where('asesor_activo_id', $user->id)->update([
            'asesor_activo_id' => null,
            'session_id' => null,
            'fecha_asignacion' => null,
            'ip_asesor' => null
        ]);

        // Asignar la nueva caja al asesor
        $caja->asignarAsesor($user->id, session()->getId(), $request->ip());

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

        // Obtener servicios asignados al asesor con estadísticas de turnos
        $serviciosAsignados = $user->serviciosActivos()->with(['turnos' => function($query) {
            $query->delDia()->whereIn('estado', ['pendiente', 'aplazado']);
        }])->get();

        // Calcular estadísticas por servicio
        $estadisticasServicios = $serviciosAsignados->map(function($servicio) {
            $turnosPendientes = $servicio->turnos->where('estado', 'pendiente')->count();
            $turnosAplazados = $servicio->turnos->where('estado', 'aplazado')->count();

            return [
                'id' => $servicio->id,
                'nombre' => $servicio->nombre,
                'codigo' => $servicio->codigo,
                'pendientes' => $turnosPendientes,
                'aplazados' => $turnosAplazados,
                'total' => $turnosPendientes + $turnosAplazados
            ];
        });

        return view('asesor.llamar-turnos', compact('user', 'caja', 'estadisticasServicios'));
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

        // Liberar cualquier caja que el usuario tenga asignada
        Caja::where('asesor_activo_id', $user->id)->update([
            'asesor_activo_id' => null,
            'session_id' => null,
            'fecha_asignacion' => null,
            'ip_asesor' => null
        ]);

        // Limpiar la caja de la sesión
        session()->forget('caja_seleccionada');

        return redirect()->route('asesor.seleccionar-caja');
    }

    /**
     * Llamar el siguiente turno de un servicio específico
     */
    public function llamarSiguienteTurno(Request $request)
    {
        $user = Auth::user();
        $cajaId = session('caja_seleccionada');

        if (!$user->esAsesor() || !$cajaId) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $request->validate([
            'servicio_id' => 'required|exists:servicios,id'
        ]);

        $servicioId = $request->servicio_id;

        // Verificar que el servicio esté asignado al asesor
        if (!$user->servicios()->where('servicios.id', $servicioId)->exists()) {
            return response()->json(['success' => false, 'message' => 'Servicio no asignado'], 403);
        }

        // Buscar el siguiente turno pendiente o aplazado
        $turno = Turno::where('servicio_id', $servicioId)
            ->whereIn('estado', ['pendiente', 'aplazado'])
            ->delDia()
            ->orderBy('prioridad', 'desc') // Prioritarios primero
            ->orderBy('numero', 'asc')
            ->first();

        if (!$turno) {
            return response()->json([
                'success' => false,
                'message' => 'No hay turnos pendientes para este servicio'
            ]);
        }

        // Marcar el turno como llamado
        $turno->marcarComoLlamado($cajaId, $user->id);

        return response()->json([
            'success' => true,
            'message' => 'Turno llamado correctamente',
            'turno' => [
                'id' => $turno->id,
                'codigo_completo' => $turno->codigo_completo,
                'servicio' => $turno->servicio->nombre,
                'prioridad' => $turno->prioridad
            ]
        ]);
    }

    /**
     * Llamar un turno específico por código y número
     */
    public function llamarTurnoEspecifico(Request $request)
    {
        $user = Auth::user();
        $cajaId = session('caja_seleccionada');

        if (!$user->esAsesor() || !$cajaId) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $request->validate([
            'codigo' => 'required|string',
            'numero' => 'required|integer|min:1'
        ]);

        // Buscar el turno específico
        $turno = Turno::where('codigo', strtoupper($request->codigo))
            ->where('numero', $request->numero)
            ->delDia()
            ->first();

        if (!$turno) {
            return response()->json([
                'success' => false,
                'message' => 'Turno no encontrado'
            ]);
        }

        if ($turno->estado === 'atendido') {
            return response()->json([
                'success' => false,
                'message' => 'Este turno ya fue atendido'
            ]);
        }

        if ($turno->estado === 'cancelado') {
            return response()->json([
                'success' => false,
                'message' => 'Este turno fue cancelado'
            ]);
        }

        // Verificar que el servicio esté asignado al asesor
        if (!$user->servicios()->where('servicios.id', $turno->servicio_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para llamar turnos de este servicio'
            ]);
        }

        // Marcar el turno como llamado
        $turno->marcarComoLlamado($cajaId, $user->id);

        return response()->json([
            'success' => true,
            'message' => 'Turno llamado correctamente',
            'turno' => [
                'id' => $turno->id,
                'codigo_completo' => $turno->codigo_completo,
                'servicio' => $turno->servicio->nombre,
                'prioridad' => $turno->prioridad
            ]
        ]);
    }

    /**
     * Marcar turno como atendido
     */
    public function marcarAtendido(Request $request)
    {
        $user = Auth::user();
        $cajaId = session('caja_seleccionada');

        if (!$user->esAsesor() || !$cajaId) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $request->validate([
            'turno_id' => 'required|exists:turnos,id'
        ]);

        $turno = Turno::find($request->turno_id);

        // Verificar que el turno esté asignado a esta caja y asesor
        if ($turno->caja_id != $cajaId || $turno->asesor_id != $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para atender este turno'
            ]);
        }

        if ($turno->estado !== 'llamado') {
            return response()->json([
                'success' => false,
                'message' => 'El turno debe estar en estado llamado para ser atendido'
            ]);
        }

        $turno->marcarComoAtendido();

        return response()->json([
            'success' => true,
            'message' => 'Turno marcado como atendido'
        ]);
    }

    /**
     * Aplazar turno
     */
    public function aplazarTurno(Request $request)
    {
        $user = Auth::user();
        $cajaId = session('caja_seleccionada');

        if (!$user->esAsesor() || !$cajaId) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $request->validate([
            'turno_id' => 'required|exists:turnos,id'
        ]);

        $turno = Turno::find($request->turno_id);

        // Verificar que el turno esté asignado a esta caja y asesor
        if ($turno->caja_id != $cajaId || $turno->asesor_id != $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para aplazar este turno'
            ]);
        }

        if ($turno->estado !== 'llamado') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden aplazar turnos que estén llamados'
            ]);
        }

        $turno->marcarComoAplazado();

        return response()->json([
            'success' => true,
            'message' => 'Turno aplazado correctamente'
        ]);
    }
}
