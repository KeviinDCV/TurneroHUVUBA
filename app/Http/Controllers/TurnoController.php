<?php

namespace App\Http\Controllers;

use App\Models\Servicio;
use App\Models\Turno;
use Illuminate\Http\Request;

class TurnoController extends Controller
{
    /**
     * Mostrar la página de inicio de turnos
     */
    public function inicio()
    {
        return view('turnos.inicio');
    }

    /**
     * Mostrar el menú de servicios
     */
    public function menu(Request $request)
    {
        $servicioId = $request->get('servicio_id');
        
        if ($servicioId) {
            // Mostrar subservicios del servicio seleccionado
            $servicioSeleccionado = Servicio::with('subservicios')
                ->where('id', $servicioId)
                ->where('estado', 'activo')
                ->first();
                
            if (!$servicioSeleccionado) {
                return redirect()->route('turnos.menu');
            }
            
            $subservicios = $servicioSeleccionado->subservicios()
                ->where('estado', 'activo')
                ->orderBy('orden')
                ->get();
                
            return view('turnos.menu', [
                'servicioSeleccionado' => $servicioSeleccionado,
                'subservicios' => $subservicios,
                'mostrandoSubservicios' => true
            ]);
        } else {
            // Mostrar servicios principales
            $servicios = Servicio::where('nivel', 'servicio')
                ->where('estado', 'activo')
                ->orderBy('orden')
                ->get();
                
            return view('turnos.menu', [
                'servicios' => $servicios,
                'mostrandoSubservicios' => false
            ]);
        }
    }

    /**
     * Procesar la selección de un servicio o subservicio
     */
    public function seleccionarServicio(Request $request)
    {
        $servicioId = $request->get('servicio_id');
        $subservicioId = $request->get('subservicio_id');

        try {
            // Determinar qué servicio usar para generar el turno
            $servicioParaTurno = $subservicioId ? $subservicioId : $servicioId;

            // Crear el turno
            $turno = Turno::crear($servicioParaTurno);

            // Obtener información del servicio para el mensaje
            if ($subservicioId) {
                $subservicio = Servicio::find($subservicioId);
                $nombreServicio = $subservicio->nombre_completo;
            } else {
                $servicio = Servicio::find($servicioId);
                $nombreServicio = $servicio->nombre;
            }

            $mensaje = "Turno generado: {$turno->codigo_completo} para {$nombreServicio}";

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'turno' => [
                    'id' => $turno->id,
                    'codigo_completo' => $turno->codigo_completo,
                    'servicio' => $nombreServicio,
                    'numero' => $turno->numero
                ],
                'redirect_url' => route('turnos.ticket', $turno->id)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el turno: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Mostrar el ticket del turno generado
     */
    public function mostrarTicket($turnoId)
    {
        $turno = Turno::with('servicio')->find($turnoId);

        if (!$turno) {
            return redirect()->route('turnos.menu')->with('error', 'Turno no encontrado');
        }

        // Verificar que el turno sea del día actual
        if (!$turno->fecha_creacion->isToday()) {
            return redirect()->route('turnos.menu')->with('error', 'Turno no válido');
        }

        $servicio = $turno->servicio;

        return view('turnos.ticket', compact('turno', 'servicio'));
    }
}
