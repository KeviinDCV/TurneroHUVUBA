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
        // LOG FORZADO para diagnóstico
        \Log::info('🎯 CONTROLADOR - seleccionarServicio() ejecutándose', [
            'url' => $request->url(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'servicio_id' => $request->get('servicio_id'),
            'subservicio_id' => $request->get('subservicio_id'),
        ]);

        $servicioId = $request->get('servicio_id');
        $subservicioId = $request->get('subservicio_id');

        try {
            // Determinar qué servicio usar
            $servicioParaTurno = $subservicioId ? $subservicioId : $servicioId;
            $servicio = Servicio::find($servicioParaTurno);

            if (!$servicio) {
                return response()->json([
                    'success' => false,
                    'message' => 'Servicio no encontrado'
                ]);
            }

            // Verificar si el servicio requiere priorización
            if ($servicio->requiere_priorizacion) {
                // Retornar indicación de que debe mostrar selector de prioridad
                return response()->json([
                    'success' => true,
                    'requiere_priorizacion' => true,
                    'servicio_id' => $servicioParaTurno,
                    'servicio_nombre' => $servicio->nombre_completo
                ]);
            }

            // Protección contra doble clic: verificar si ya se creó un turno
            // para este servicio en los últimos 3 segundos (solo doble-clic real)
            $turnoReciente = Turno::where('servicio_id', $servicioParaTurno)
                ->where('estado', 'pendiente')
                ->where('fecha_creacion', '>=', now()->subSeconds(3))
                ->whereNull('observaciones') // No contar transferencias
                ->orderBy('id', 'desc')
                ->first();

            if ($turnoReciente) {
                \Log::warning('⚠️ Turno duplicado prevenido (doble clic)', [
                    'servicio_id' => $servicioParaTurno,
                    'turno_existente' => $turnoReciente->codigo_completo,
                    'ip' => $request->ip(),
                ]);
                // NO redirigir al ticket (causaría doble impresión)
                // Solo mostrar mensaje de que el turno ya fue generado
                return response()->json([
                    'success' => true,
                    'message' => "Su turno {$turnoReciente->codigo_completo} ya fue generado. Por favor espere.",
                    'turno' => [
                        'id' => $turnoReciente->id,
                        'codigo_completo' => $turnoReciente->codigo_completo,
                        'servicio' => $servicio->nombre_completo,
                        'numero' => $turnoReciente->numero
                    ],
                    'duplicado' => true
                ]);
            }

            // Si no requiere priorización, crear turno con prioridad por defecto
            // Pasar el objeto $servicio para evitar queries redundantes
            $turno = Turno::crear($servicio, 3); // Prioridad C por defecto

            $nombreServicio = $servicio->nombre_completo;
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
            \Log::error('❌ ERROR en seleccionarServicio()', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el turno: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Crear turno con prioridad seleccionada
     */
    public function crearTurnoConPrioridad(Request $request)
    {
        $request->validate([
            'servicio_id' => 'required|exists:servicios,id',
            'prioridad' => 'required|in:normal,alta,A,B,C,D,E'
        ]);

        try {
            $servicioId = $request->servicio_id;
            $prioridadTipo = $request->prioridad;

            // Convertir tipo a número (normal=3, alta=5)
            $prioridad = Turno::tipoAPrioridad($prioridadTipo);

            // Obtener servicio una sola vez para reutilizar en todo el método
            $servicio = Servicio::find($servicioId);
            if (!$servicio) {
                return response()->json([
                    'success' => false,
                    'message' => 'Servicio no encontrado'
                ]);
            }

            // Protección contra doble clic: verificar si ya se creó un turno
            // para este servicio en los últimos 3 segundos (solo doble-clic real)
            $turnoReciente = Turno::where('servicio_id', $servicioId)
                ->where('estado', 'pendiente')
                ->where('fecha_creacion', '>=', now()->subSeconds(3))
                ->whereNull('observaciones') // No contar transferencias
                ->orderBy('id', 'desc')
                ->first();

            if ($turnoReciente) {
                \Log::warning('⚠️ Turno con prioridad duplicado prevenido (doble clic)', [
                    'servicio_id' => $servicioId,
                    'turno_existente' => $turnoReciente->codigo_completo,
                    'ip' => $request->ip(),
                ]);
                // NO redirigir al ticket (causaría doble impresión)
                return response()->json([
                    'success' => true,
                    'message' => "Su turno {$turnoReciente->codigo_completo} ya fue generado. Por favor espere.",
                    'turno' => [
                        'id' => $turnoReciente->id,
                        'codigo_completo' => $turnoReciente->codigo_completo,
                        'servicio' => $servicio->nombre_completo,
                        'numero' => $turnoReciente->numero,
                        'prioridad' => $turnoReciente->prioridad >= 4 ? 'Prioritario' : 'Normal'
                    ],
                    'duplicado' => true
                ]);
            }

            // Crear el turno - pasar el objeto $servicio para evitar queries redundantes
            $turno = Turno::crear($servicio, $prioridad);

            // Obtener información del servicio para el mensaje
            $nombreServicio = $servicio->nombre_completo;

            // Determinar texto de prioridad
            $tipoPrioridad = $prioridad >= 4 ? 'Prioritario' : 'Normal';
            $mensaje = "Turno generado: {$turno->codigo_completo} para {$nombreServicio} - {$tipoPrioridad}";

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'turno' => [
                    'id' => $turno->id,
                    'codigo_completo' => $turno->codigo_completo,
                    'servicio' => $nombreServicio,
                    'numero' => $turno->numero,
                    'prioridad' => $tipoPrioridad
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

    /**
     * Solicitar repetición del audio del último turno en la TV
     */
    public function repetirAudioTurno(Request $request)
    {
        try {
            // Verificar que el usuario esté autenticado y sea asesor
            $user = auth()->user();
            if (!$user || !$user->esAsesor()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado'
                ], 403);
            }

            // Log de la solicitud
            \Log::info('Solicitud de repetición de audio', [
                'usuario' => $user->nombre_usuario,
                'ip' => $request->ip(),
                'timestamp' => now()
            ]);

            // Retornar éxito - el JavaScript en la TV manejará la repetición
            return response()->json([
                'success' => true,
                'message' => 'Solicitud de repetición enviada'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en repetición de audio', [
                'error' => $e->getMessage(),
                'usuario' => auth()->user() ? auth()->user()->nombre_usuario : 'No autenticado'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar solicitud'
            ], 500);
        }
    }
}
