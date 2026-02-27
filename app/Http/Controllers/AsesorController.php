<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Caja;
use App\Models\User;
use App\Models\Servicio;
use App\Models\Turno;
use App\Broadcasting\TurneroBroadcaster;
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

        // Log para debugging
        \Log::info('Selección de caja - Usuario: ' . $user->nombre_usuario, [
            'total_cajas' => $cajas->count(),
            'cajas_raw' => $cajas->map(function($c) {
                return [
                    'id' => $c->id,
                    'nombre' => $c->nombre,
                    'asesor_activo_id' => $c->asesor_activo_id,
                    'session_id' => $c->session_id,
                    'fecha_asignacion' => $c->fecha_asignacion,
                    'esta_ocupada' => $c->estaOcupada()
                ];
            })
        ]);

        // Marcar cajas como disponibles u ocupadas
        $cajas = $cajas->map(function($caja) use ($user) {
            $estaOcupada = $caja->estaOcupada();
            $ocupadaPorMi = $caja->estaOcupadaPor($user->id, session()->getId());

            $caja->disponible = !$estaOcupada || $ocupadaPorMi;
            $caja->ocupada_por_mi = $ocupadaPorMi;
            $caja->nombre_asesor = $caja->asesorActivo ? $caja->asesorActivo->nombre_completo : null;

            // Log detallado por caja
            \Log::info("Caja {$caja->nombre} - Estado:", [
                'esta_ocupada' => $estaOcupada,
                'ocupada_por_mi' => $ocupadaPorMi,
                'disponible' => $caja->disponible,
                'asesor_activo' => $caja->nombre_asesor
            ]);

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

        // Iniciar el timer de auto-llamado desde este momento
        session(['auto_llamado_inicio_ts' => now()->timestamp]);

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
        $caja = null;

        // Si hay caja en sesión, verificar que siga válida
        if ($cajaId) {
            $caja = Caja::find($cajaId);
            
            // Verificar que la caja exista, esté activa y asignada al usuario
            if (!$caja || $caja->estado !== 'activa' || $caja->asesor_activo_id != $user->id) {
                // La caja ya no es válida, buscar si tiene otra asignada
                session()->forget('caja_seleccionada');
                $cajaId = null;
                $caja = null;
            }
        }

        // Si no hay caja válida en sesión, buscar en la base de datos
        if (!$cajaId) {
            $cajaAsignada = Caja::where('asesor_activo_id', $user->id)
                ->where('estado', 'activa')
                ->first();

            if ($cajaAsignada) {
                // El usuario tiene una caja asignada en DB, restaurar la sesión
                // Actualizar session_id y fecha para la nueva sesión (recarga de página)
                $cajaAsignada->update([
                    'session_id' => session()->getId(),
                    'fecha_asignacion' => now(),
                    'ip_asesor' => request()->ip()
                ]);

                // Restaurar la caja en la sesión
                session(['caja_seleccionada' => $cajaAsignada->id]);
                $cajaId = $cajaAsignada->id;
                $caja = $cajaAsignada;

                \Log::info("Caja restaurada en sesión para usuario {$user->nombre_usuario}", [
                    'caja_id' => $cajaAsignada->id,
                    'caja_nombre' => $cajaAsignada->nombre,
                    'nueva_session_id' => session()->getId()
                ]);
            } else {
                // No tiene caja asignada en DB, redirigir a selección
                return redirect()->route('asesor.seleccionar-caja');
            }
        }

        // Renovar la fecha de asignación para mantener la sesión activa
        if ($caja && $caja->asesor_activo_id == $user->id) {
            $caja->update([
                'session_id' => session()->getId(),
                'fecha_asignacion' => now(),
                'ip_asesor' => request()->ip()
            ]);
        }

        // Obtener servicios asignados al asesor con estadísticas de turnos
        $serviciosAsignados = $user->serviciosActivos()
            ->with(['turnos' => function($query) {
                $query->delDia()->whereIn('estado', ['pendiente', 'aplazado']);
            }])
            ->with(['subservicios.turnos' => function($query) {
                $query->delDia()->whereIn('estado', ['pendiente', 'aplazado']);
            }])
            ->get();

        // Organizar servicios jerárquicamente
        $serviciosPadre = $serviciosAsignados->filter(function($servicio) {
            return $servicio->esServicioPrincipal();
        });

        // Estructurar servicios con estadísticas
        $serviciosEstructurados = [];

        foreach ($serviciosPadre as $servicioPadre) {
            // Obtener los turnos pendientes y aplazados del servicio padre
            $turnosPendientesPadre = $servicioPadre->turnos->where('estado', 'pendiente')->count();
            $turnosAplazadosPadre = $servicioPadre->turnos->where('estado', 'aplazado')->count();

            // Preparar arrays para subservicios
            $subserviciosDatos = [];

            // Variables para sumar turnos de los hijos
            $totalPendientesHijos = 0;
            $totalAplazadosHijos = 0;

            // Añadir subservicios si existen
            foreach ($servicioPadre->subservicios as $subservicio) {
                // Si el subservicio está asignado al asesor
                if ($subservicio->estaAsignadoA($user->id)) {
                    $turnosPendientesHijo = $subservicio->turnos->where('estado', 'pendiente')->count();
                    $turnosAplazadosHijo = $subservicio->turnos->where('estado', 'aplazado')->count();

                    // Sumar a los totales
                    $totalPendientesHijos += $turnosPendientesHijo;
                    $totalAplazadosHijos += $turnosAplazadosHijo;

                    $subserviciosDatos[] = [
                        'id' => $subservicio->id,
                        'nombre' => $subservicio->nombre,
                        'codigo' => $subservicio->codigo,
                        'pendientes' => $turnosPendientesHijo,
                        'aplazados' => $turnosAplazadosHijo,
                        'total' => $turnosPendientesHijo // Solo pendientes para el botón DISPONIBLE
                    ];
                }
            }

            // Sumar los turnos del padre y los hijos
            $totalPendientes = $turnosPendientesPadre + $totalPendientesHijos;
            $totalAplazados = $turnosAplazadosPadre + $totalAplazadosHijos;

            // Datos del servicio padre (incluye suma de todos los hijos)
            $servicioDatos = [
                'id' => $servicioPadre->id,
                'nombre' => $servicioPadre->nombre,
                'codigo' => $servicioPadre->codigo,
                'pendientes' => $totalPendientes,
                'aplazados' => $totalAplazados,
                'total' => $totalPendientes, // Solo pendientes para el botón DISPONIBLE
                'tiene_hijos' => $servicioPadre->subservicios->isNotEmpty(),
                'pendientes_propios' => $turnosPendientesPadre, // Solo los turnos del padre
                'aplazados_propios' => $turnosAplazadosPadre,   // Solo los turnos del padre
                'subservicios' => $subserviciosDatos
            ];

            $serviciosEstructurados[] = $servicioDatos;
        }

        // Añadir subservicios que son huérfanos (asignados al usuario pero sin padre o con padre no asignado)
        $subserviciosHuerfanos = $serviciosAsignados->filter(function($servicio) {
            return $servicio->esSubservicio() && (!$servicio->servicioPadre || !$servicio->servicioPadre->estaAsignadoA(Auth::id()));
        });

        foreach ($subserviciosHuerfanos as $subservicio) {
            $turnosPendientes = $subservicio->turnos->where('estado', 'pendiente')->count();
            $turnosAplazados = $subservicio->turnos->where('estado', 'aplazado')->count();

            $serviciosEstructurados[] = [
                'id' => $subservicio->id,
                'nombre' => $subservicio->nombre,
                'codigo' => $subservicio->codigo,
                'pendientes' => $turnosPendientes,
                'aplazados' => $turnosAplazados,
                'total' => $turnosPendientes, // Solo pendientes para el botón DISPONIBLE
                'tiene_hijos' => false,
                'pendientes_propios' => $turnosPendientes, // Mismo valor que pendientes
                'aplazados_propios' => $turnosAplazados,   // Mismo valor que aplazados
                'subservicios' => []
            ];
        }

        return view('asesor.llamar-turnos', compact('user', 'caja', 'serviciosEstructurados'));
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

        // Si no hay caja en sesión, intentar recuperarla de la base de datos
        if (!$cajaId) {
            $cajaAsignada = Caja::where('asesor_activo_id', $user->id)
                ->where('estado', 'activa')
                ->first();
            
            if ($cajaAsignada) {
                session(['caja_seleccionada' => $cajaAsignada->id]);
                $cajaId = $cajaAsignada->id;
            }
        }

        if (!$user->esAsesor() || !$cajaId) {
            return response()->json(['success' => false, 'message' => 'No autorizado o sin caja asignada'], 403);
        }

        // Verificar que el asesor no esté en canal no presencial
        if ($user->estaEnCanalNoPresencial()) {
            return response()->json([
                'success' => false,
                'message' => 'No puede llamar turnos mientras está en canal no presencial'
            ], 403);
        }

        // VALIDACIÓN CRÍTICA: Verificar si el asesor ya tiene un turno en proceso
        $turnoEnProceso = Turno::where('asesor_id', $user->id)
            ->where('estado', 'llamado')
            ->delDia()
            ->first();

        if ($turnoEnProceso) {
            // Log para debugging
            \Log::info('Turno en proceso encontrado', [
                'user_id' => $user->id,
                'caja_id' => $cajaId,
                'turno_id' => $turnoEnProceso->id,
                'turno_codigo' => $turnoEnProceso->codigo_completo,
                'estado' => $turnoEnProceso->estado
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Debe marcar el turno actual como "Atendido" antes de llamar un nuevo turno.',
                'turno_en_proceso' => $turnoEnProceso->codigo_completo
            ], 400);
        }

        $request->validate([
            'servicio_id' => 'required|exists:servicios,id'
        ]);

        $servicioId = $request->servicio_id;
        $servicio = Servicio::find($servicioId);

        // Verificar que el servicio esté asignado al asesor
        if (!$user->servicios()->where('servicios.id', $servicioId)->exists()) {
            return response()->json(['success' => false, 'message' => 'Servicio no asignado'], 403);
        }

        // Verificar si el servicio tiene ocultar_turno activado
        if ($servicio->ocultar_turno) {
            return response()->json([
                'success' => false,
                'message' => 'Este servicio tiene los turnos ocultos y no se pueden llamar automáticamente'
            ]);
        }

        // Determinar si es un servicio padre con subservicios
        $esServicioPadre = $servicio->subservicios->isNotEmpty();

        if ($esServicioPadre) {
            // Si es servicio padre, obtener todos los IDs de servicios hijos asignados al asesor
            // Excluir servicios con ocultar_turno = true
            $serviciosHijosIds = $servicio->subservicios()
                ->whereHas('usuarios', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->where('ocultar_turno', false)
                ->pluck('id')
                ->toArray();

            // Añadir el ID del servicio padre a la lista solo si no tiene ocultar_turno activado
            $serviciosIds = $serviciosHijosIds;
            if (!$servicio->ocultar_turno) {
                $serviciosIds = array_merge([$servicioId], $serviciosHijosIds);
            }

            // Buscar turno usando algoritmo de peso proporcional
            $turno = $this->buscarTurnoConPesoProporcional($serviciosIds);
        } else {
            // Si es un servicio regular, buscar turnos en ese servicio
            $turno = $this->buscarTurnoConPesoProporcional([$servicioId]);
        }

        if (!$turno) {
            return response()->json([
                'success' => false,
                'message' => 'No hay turnos pendientes para este servicio'
            ]);
        }

        return $this->responderConTurno($turno, $cajaId, $user->id);
    }

    /**
     * Método auxiliar para buscar un turno usando algoritmo de peso proporcional
     * 
     * Pesos por prioridad:
     * - Prioridad 5 (E): 40%
     * - Prioridad 4 (D): 30%
     * - Prioridad 3 (C): 20%
     * - Prioridad 2 (B): 7%
     * - Prioridad 1 (A): 3%
     *
     * @param array $serviciosIds IDs de servicios donde buscar
     * @return Turno|null El turno encontrado o null
     */
    private function buscarTurnoConPesoProporcional($serviciosIds)
    {
        if (empty($serviciosIds)) {
            return null;
        }

        // Obtener solo los turnos pendientes del día para los servicios especificados
        // Los turnos aplazados se llaman desde el modal de la columna APLAZADOS
        $turnos = Turno::whereIn('servicio_id', $serviciosIds)
            ->where('estado', 'pendiente')
            ->delDia()
            ->orderBy('fecha_creacion', 'asc')
            ->get();

        if ($turnos->isEmpty()) {
            return null;
        }

        // Separar turnos en normales (prioridad < 4) y prioritarios (prioridad >= 4)
        $turnosNormales = $turnos->filter(fn($t) => $t->prioridad < 4)->sortBy('fecha_creacion');
        $turnosPrioritarios = $turnos->filter(fn($t) => $t->prioridad >= 4)->sortBy('fecha_creacion');

        // Si solo hay un tipo, retornar el primero de ese tipo
        if ($turnosPrioritarios->isEmpty()) {
            return $turnosNormales->first();
        }
        if ($turnosNormales->isEmpty()) {
            return $turnosPrioritarios->first();
        }

        // Hay ambos tipos pendientes
        $primerNormal = $turnosNormales->first();
        $primerPrioritario = $turnosPrioritarios->first();

        // REGLA 1: Si el prioritario es más antiguo o igual al normal, 
        // llamarlo en su orden natural (no retrasarlo)
        if ($primerPrioritario->fecha_creacion <= $primerNormal->fecha_creacion) {
            return $primerPrioritario;
        }

        // REGLA 2: El prioritario tiene número mayor (llegó después)
        // Verificar si toca adelantarlo (cada 5 normales)
        $ultimoPrioritarioAtendido = Turno::whereIn('servicio_id', $serviciosIds)
            ->where('prioridad', '>=', 4)
            ->whereIn('estado', ['atendido', 'llamado'])
            ->delDia()
            ->orderBy('fecha_llamado', 'desc')
            ->first();

        if ($ultimoPrioritarioAtendido) {
            // Contar normales llamados después del último prioritario
            $normalesDesdeUltimoPrioritario = Turno::whereIn('servicio_id', $serviciosIds)
                ->where('prioridad', '<', 4)
                ->whereIn('estado', ['atendido', 'llamado'])
                ->where('fecha_llamado', '>', $ultimoPrioritarioAtendido->fecha_llamado)
                ->delDia()
                ->count();
        } else {
            // No hay prioritarios atendidos, contar todos los normales atendidos
            $normalesDesdeUltimoPrioritario = Turno::whereIn('servicio_id', $serviciosIds)
                ->where('prioridad', '<', 4)
                ->whereIn('estado', ['atendido', 'llamado'])
                ->delDia()
                ->count();
        }

        // Si ya se llamaron 5 normales, adelantar al prioritario
        if ($normalesDesdeUltimoPrioritario >= 5) {
            return $primerPrioritario;
        }

        // Si no, seguir el orden normal
        return $primerNormal;
    }

    /**
     * Seleccionar una prioridad basándose en pesos ponderados
     *
     * @param array $pesos Array con prioridades como key y pesos como value
     * @return int La prioridad seleccionada
     */
    private function seleccionarPrioridadPonderada($pesos)
    {
        $totalPeso = array_sum($pesos);
        $random = rand(1, $totalPeso);
        
        $acumulado = 0;
        foreach ($pesos as $prioridad => $peso) {
            $acumulado += $peso;
            if ($random <= $acumulado) {
                return $prioridad;
            }
        }
        
        // Fallback: retornar la prioridad más alta disponible
        return max(array_keys($pesos));
    }

    /**
     * Iniciar actividad en canal no presencial
     */
    public function iniciarCanalNoPresencial(Request $request)
    {
        \Log::info('🟠 Iniciando canal no presencial', [
            'user_id' => Auth::id(),
            'actividad' => $request->actividad
        ]);

        try {
            $request->validate([
                'actividad' => 'required|string|max:500'
            ]);

            $user = Auth::user();

            if (!$user->esAsesor()) {
                \Log::warning('⚠️ Usuario no es asesor', ['user_id' => $user->id]);
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado'
                ], 403);
            }

            $user->iniciarCanalNoPresencial($request->actividad);

            \Log::info('✅ Canal no presencial iniciado correctamente', ['user_id' => $user->id]);

            return response()->json([
                'success' => true,
                'message' => 'Actividad de canal no presencial iniciada correctamente'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('❌ Error de validación en canal no presencial', [
                'errors' => $e->errors()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('❌ Error al iniciar canal no presencial', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar actividad: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Finalizar actividad en canal no presencial
     */
    public function finalizarCanalNoPresencial()
    {
        $user = Auth::user();

        if (!$user->esAsesor()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        if (!$user->estaEnCanalNoPresencial()) {
            return response()->json([
                'success' => false,
                'message' => 'No está en canal no presencial actualmente'
            ], 400);
        }

        try {
            // Guardar en el historial antes de finalizar
            $inicio = $user->inicio_canal_no_presencial;
            $fin = now();
            $duracionMinutos = $inicio ? $inicio->diffInMinutes($fin) : 0;

            \App\Models\CanalNoPresencialHistorial::create([
                'user_id' => $user->id,
                'actividad' => $user->actividad_canal_no_presencial,
                'inicio' => $inicio,
                'fin' => $fin,
                'duracion_minutos' => $duracionMinutos,
            ]);

            $user->finalizarCanalNoPresencial();

            return response()->json([
                'success' => true,
                'message' => 'Actividad de canal no presencial finalizada correctamente',
                'duracion_minutos' => $duracionMinutos
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al finalizar canal no presencial', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al finalizar actividad: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Método auxiliar para responder con los datos del turno
     */
    private function responderConTurno($turno, $cajaId, $userId)
    {
        // Marcar el turno como llamado
        $turno->marcarComoLlamado($cajaId, $userId);

        // Preparar datos del turno para la respuesta
        $turnoData = [
            'id' => $turno->id,
            'codigo_completo' => $turno->codigo_completo,
            'servicio' => $turno->servicio->nombre,
            'prioridad' => $turno->prioridad
        ];

        // Cargar la relación caja para tener acceso a sus datos
        $turno->load('caja');

        // Notificar usando el broadcaster personalizado
        \App\Broadcasting\TurneroBroadcaster::notificarTurnoLlamado($turno);

        return response()->json([
            'success' => true,
            'message' => 'Turno llamado correctamente',
            'turno' => $turnoData
        ]);
    }

    /**
     * Llamar un turno específico por código y número
     */
    public function llamarTurnoEspecifico(Request $request)
    {
        $user = Auth::user();
        
        // Obtener caja_id del request, sesión o base de datos
        $cajaId = $request->input('caja_id') ?? session('caja_seleccionada');
        
        // Si no hay caja en request ni sesión, intentar recuperarla de la base de datos
        if (!$cajaId) {
            $cajaAsignada = Caja::where('asesor_activo_id', $user->id)
                ->where('estado', 'activa')
                ->first();
            
            if ($cajaAsignada) {
                session(['caja_seleccionada' => $cajaAsignada->id]);
                $cajaId = $cajaAsignada->id;
            }
        }
        
        $codigo = $request->input('codigo');
        $numero = $request->input('numero');

        \Log::info('🎯 Intento de llamar turno específico', [
            'user_id' => $user->id,
            'caja_id' => $cajaId,
            'codigo' => $codigo,
            'numero' => $numero
        ]);

        if (!$cajaId) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene una caja asignada'
            ], 403);
        }

        // Verificar que el asesor no esté en canal no presencial
        if ($user->estaEnCanalNoPresencial()) {
            return response()->json([
                'success' => false,
                'message' => 'No puede llamar turnos mientras está en canal no presencial'
            ], 403);
        }

        // VALIDACIÓN CRÍTICA: Verificar si el asesor ya tiene un turno en proceso
        $turnoEnProceso = Turno::where('asesor_id', $user->id)
            ->where('estado', 'llamado')
            ->delDia()
            ->first();

        if ($turnoEnProceso) {
            return response()->json([
                'success' => false,
                'message' => 'Debe marcar el turno actual como "Atendido" antes de llamar un nuevo turno.',
                'turno_en_proceso' => $turnoEnProceso->codigo_completo
            ], 400);
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

        // Si no hay caja en sesión, intentar recuperarla de la base de datos
        if (!$cajaId) {
            $cajaAsignada = Caja::where('asesor_activo_id', $user->id)
                ->where('estado', 'activa')
                ->first();
            
            if ($cajaAsignada) {
                session(['caja_seleccionada' => $cajaAsignada->id]);
                $cajaId = $cajaAsignada->id;
            }
        }

        if (!$user->esAsesor() || !$cajaId) {
            return response()->json(['success' => false, 'message' => 'No autorizado o sin caja asignada'], 403);
        }

        // Validar que se proporcione turno_id O codigo_completo
        $request->validate([
            'turno_id' => 'nullable|exists:turnos,id',
            'codigo_completo' => 'nullable|string',
            'duracion' => 'nullable|integer'
        ]);

        // Buscar turno por ID o por código completo
        if ($request->has('turno_id') && $request->turno_id) {
            $turno = Turno::find($request->turno_id);
        } elseif ($request->has('codigo_completo') && $request->codigo_completo) {
            // Parsear código completo (formato: "CP-001")
            $datos = Turno::parsearCodigoCompleto($request->codigo_completo);

            if (!$datos) {
                return response()->json([
                    'success' => false,
                    'message' => 'Formato de código completo inválido'
                ]);
            }

            $turno = Turno::where('codigo', $datos['codigo'])
                ->where('numero', $datos['numero'])
                ->where('asesor_id', $user->id)
                ->delDia()
                ->first();
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Debe proporcionar turno_id o codigo_completo'
            ]);
        }

        if (!$turno) {
            return response()->json([
                'success' => false,
                'message' => 'Turno no encontrado'
            ]);
        }

        // Verificar que el turno esté asignado al asesor actual
        if ($turno->asesor_id != $user->id) {
            \Log::warning("Intento de atender turno ajeno", [
                'turno_id' => $turno->id,
                'turno_asesor_id' => $turno->asesor_id,
                'user_id' => $user->id,
                'caja_sesion' => $cajaId,
                'turno_caja_id' => $turno->caja_id
            ]);
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

        // Marcar como atendido usando la duración del cronómetro del frontend
        $duracionFrontend = $request->duracion; // Duración en segundos del cronómetro
        $duracion = $turno->marcarComoAtendido($duracionFrontend);

        // Log adicional para debugging (comentado para evitar spam)
        // \Log::info('Respuesta marcarAtendido', [
        //     'turno_id' => $turno->id,
        //     'codigo_completo' => $turno->codigo_completo,
        //     'duracion_retornada' => $duracion,
        //     'duracion_guardada' => $turno->fresh()->duracion_atencion
        // ]);

        // Actualizar el inicio del timer de auto-llamado
        session(['auto_llamado_inicio_ts' => now()->timestamp]);

        return response()->json([
            'success' => true,
            'message' => 'Turno marcado como atendido',
            'duracion' => $duracion
        ]);
    }

    /**
     * Aplazar turno
     */
    public function aplazarTurno(Request $request)
    {
        $user = Auth::user();
        $cajaId = session('caja_seleccionada');

        // Si no hay caja en sesión, intentar recuperarla de la base de datos
        if (!$cajaId) {
            $cajaAsignada = Caja::where('asesor_activo_id', $user->id)
                ->where('estado', 'activa')
                ->first();
            
            if ($cajaAsignada) {
                session(['caja_seleccionada' => $cajaAsignada->id]);
                $cajaId = $cajaAsignada->id;
            }
        }

        if (!$user->esAsesor() || !$cajaId) {
            return response()->json(['success' => false, 'message' => 'No autorizado o sin caja asignada'], 403);
        }

        // Validar que se proporcione turno_id O codigo_completo
        $request->validate([
            'turno_id' => 'nullable|exists:turnos,id',
            'codigo_completo' => 'nullable|string',
            'duracion' => 'nullable|integer'
        ]);

        // Buscar turno por ID o por código completo
        if ($request->has('turno_id') && $request->turno_id) {
            $turno = Turno::find($request->turno_id);
        } elseif ($request->has('codigo_completo') && $request->codigo_completo) {
            // Parsear código completo (formato: "CP-001")
            $datos = Turno::parsearCodigoCompleto($request->codigo_completo);

            if (!$datos) {
                return response()->json([
                    'success' => false,
                    'message' => 'Formato de código completo inválido'
                ]);
            }

            $turno = Turno::where('codigo', $datos['codigo'])
                ->where('numero', $datos['numero'])
                ->where('asesor_id', $user->id)
                ->delDia()
                ->first();
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Debe proporcionar turno_id o codigo_completo'
            ]);
        }

        if (!$turno) {
            return response()->json([
                'success' => false,
                'message' => 'Turno no encontrado'
            ]);
        }

        // Verificar que el turno esté asignado al asesor actual
        if ($turno->asesor_id != $user->id) {
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

        // Aplazar turno usando la duración del cronómetro del frontend
        $duracionFrontend = $request->duracion; // Duración en segundos del cronómetro
        $turno->marcarComoAplazado($duracionFrontend);

        // Actualizar el inicio del timer de auto-llamado
        session(['auto_llamado_inicio_ts' => now()->timestamp]);

        return response()->json([
            'success' => true,
            'message' => 'Turno aplazado correctamente'
        ]);
    }

    /**
     * Transferir turno a otro servicio
     */
    public function transferirTurno(Request $request)
    {
        \Log::info('Solicitud de transferencia recibida', $request->all());

        $user = Auth::user();

        if (!$user->esAsesor()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        $request->validate([
            'codigo_completo' => 'required|string',
            'servicio_destino_id' => 'required|exists:servicios,id',
            'posicion' => 'required|in:primero,ultimo'
        ]);

        // Buscar el turno
        $turno = Turno::buscarPorCodigoCompleto($request->codigo_completo);

        if (!$turno) {
            return response()->json([
                'success' => false,
                'message' => 'Turno no encontrado'
            ]);
        }

        // Verificar que el asesor tenga relación con el turno
        // Cualquier asesor puede transferir un turno que esté en su vista
        // La idea de transferir es mover turnos entre servicios libremente
        \Log::info('Verificación de transferencia', [
            'turno_id' => $turno->id,
            'turno_asesor_id' => $turno->asesor_id,
            'turno_servicio_id' => $turno->servicio_id,
            'turno_estado' => $turno->estado,
            'user_id' => $user->id,
        ]);

        // Verificar que el turno esté en estado llamado o atendido
        // (atendido: porque el flujo es primero marcar atendido y luego transferir)
        if (!in_array($turno->estado, ['llamado', 'atendido'])) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden transferir turnos que estén llamados o recién atendidos'
            ]);
        }

        // Verificar que el servicio destino exista y esté activo
        $servicioDestino = Servicio::where('id', $request->servicio_destino_id)
            ->where('estado', 'activo')
            ->first();

        if (!$servicioDestino) {
            return response()->json([
                'success' => false,
                'message' => 'El servicio destino no existe o no está activo'
            ]);
        }

        // No permitir transferir al mismo servicio
        if ($turno->servicio_id == $servicioDestino->id) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede transferir al mismo servicio'
            ]);
        }

        // Guardar servicio original para el log
        $servicioOriginal = $turno->servicio;

        // Determinar el nuevo número según la posición
        if ($request->posicion === 'primero') {
            // Obtener la fecha de creación mínima en el servicio destino
            $minFecha = Turno::where('servicio_id', $servicioDestino->id)
                ->where('estado', 'pendiente')
                ->delDia()
                ->min('fecha_creacion');
            
            // Si hay turnos, usar esa fecha menos 1 segundo, si no, usar ahora
            $nuevaFechaCreacion = $minFecha ? Carbon::parse($minFecha)->subSeconds(1) : now();
        } else {
            // Posición último: usar fecha actual (ahora)
            $nuevaFechaCreacion = now();
        }

        try {
            // Marcar el turno original como "atendido" con observación de transferencia
            // Calcular duración desde que se llamó
            $duracion = 0;
            if ($turno->fecha_llamado) {
                $duracion = abs(now()->diffInSeconds($turno->fecha_llamado));
            }
            
            try {
                $turno->update([
                    'estado' => 'atendido',
                    'fecha_atencion' => now(),
                    'fecha_finalizacion' => now(),
                    'duracion_atencion' => $duracion,
                    'observaciones' => 'Transferido a ' . $servicioDestino->nombre
                ]);
            } catch (\Exception $e) {
                // Si falla (probablemente porque no existe el campo fecha_finalizacion en producción),
                // intentamos actualizar sin ese campo
                \Log::warning('Error actualizando fecha_finalizacion, intentando sin campo: ' . $e->getMessage());
                $turno->update([
                    'estado' => 'atendido',
                    'fecha_atencion' => now(),
                    'duracion_atencion' => $duracion,
                    'observaciones' => 'Transferido a ' . $servicioDestino->nombre
                ]);
            }

            // Crear un NUEVO turno en el servicio destino pero con el MISMO código y número
            // para que el paciente mantenga su ticket original
            $nuevoTurno = new Turno();
            $nuevoTurno->servicio_id = $servicioDestino->id;
            $nuevoTurno->codigo = $turno->codigo; // Mantener el código ORIGINAL
            $nuevoTurno->numero = $turno->numero; // Mantener el número ORIGINAL
            $nuevoTurno->prioridad = $turno->prioridad; // Mantener la prioridad original
            $nuevoTurno->estado = 'pendiente';
            $nuevoTurno->fecha_creacion = $nuevaFechaCreacion;
            $nuevoTurno->observaciones = 'Transferido desde ' . $servicioOriginal->nombre;
            $nuevoTurno->save();

            \Log::info('Turno transferido', [
                'turno_original_id' => $turno->id,
                'nuevo_turno_id' => $nuevoTurno->id,
                'codigo_turno' => $nuevoTurno->codigo_completo,
                'servicio_origen' => $servicioOriginal->nombre,
                'servicio_destino' => $servicioDestino->nombre,
                'posicion' => $request->posicion,
                'asesor' => $user->nombre_completo
            ]);

            return response()->json([
                'success' => true,
                'message' => "Turno {$nuevoTurno->codigo_completo} transferido a {$servicioDestino->nombre} exitosamente"
            ]);

        } catch (\Exception $e) {
            \Log::error('Error crítico al transferir turno: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno al transferir: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todos los servicios activos (para transferir turnos)
     */
    public function getServiciosActivos()
    {
        $user = Auth::user();

        if (!$user->esAsesor()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        try {
            $servicios = Servicio::where('estado', 'activo')
                ->select('id', 'nombre', 'codigo')
                ->orderBy('nombre')
                ->get();

            return response()->json([
                'success' => true,
                'servicios' => $servicios
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar servicios: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas actualizadas de servicios (para actualización en tiempo real)
     */
    public function getServiciosEstadisticas()
    {
        $user = Auth::user();

        // Verificar que el usuario sea asesor
        if (!$user->esAsesor()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        // Obtener servicios asignados al asesor con estadísticas de turnos
        $serviciosAsignados = $user->serviciosActivos()
            ->with(['turnos' => function($query) {
                $query->delDia()->whereIn('estado', ['pendiente', 'aplazado']);
            }])
            ->with(['subservicios.turnos' => function($query) {
                $query->delDia()->whereIn('estado', ['pendiente', 'aplazado']);
            }])
            ->get();

        // Organizar servicios jerárquicamente
        $serviciosPadre = $serviciosAsignados->filter(function($servicio) {
            return $servicio->esServicioPrincipal();
        });

        // Estructurar servicios con estadísticas
        $serviciosEstructurados = [];

        foreach ($serviciosPadre as $servicioPadre) {
            // Obtener los turnos pendientes y aplazados del servicio padre
            $turnosPendientesPadre = $servicioPadre->turnos->where('estado', 'pendiente')->count();
            $turnosAplazadosPadre = $servicioPadre->turnos->where('estado', 'aplazado')->count();

            // Preparar arrays para subservicios
            $subserviciosDatos = [];

            // Variables para sumar turnos de los hijos
            $totalPendientesHijos = 0;
            $totalAplazadosHijos = 0;

            // Añadir subservicios si existen
            foreach ($servicioPadre->subservicios as $subservicio) {
                // Si el subservicio está asignado al asesor
                if ($subservicio->estaAsignadoA($user->id)) {
                    $turnosPendientesHijo = $subservicio->turnos->where('estado', 'pendiente')->count();
                    $turnosAplazadosHijo = $subservicio->turnos->where('estado', 'aplazado')->count();

                    // Sumar a los totales
                    $totalPendientesHijos += $turnosPendientesHijo;
                    $totalAplazadosHijos += $turnosAplazadosHijo;

                    $subserviciosDatos[] = [
                        'id' => $subservicio->id,
                        'nombre' => $subservicio->nombre,
                        'codigo' => $subservicio->codigo,
                        'pendientes' => $turnosPendientesHijo,
                        'aplazados' => $turnosAplazadosHijo,
                        'total' => $turnosPendientesHijo // Solo pendientes para el botón DISPONIBLE
                    ];
                }
            }

            // Sumar los turnos del padre y los hijos
            $totalPendientes = $turnosPendientesPadre + $totalPendientesHijos;
            $totalAplazados = $turnosAplazadosPadre + $totalAplazadosHijos;

            // Datos del servicio padre (incluye suma de todos los hijos)
            $servicioDatos = [
                'id' => $servicioPadre->id,
                'nombre' => $servicioPadre->nombre,
                'codigo' => $servicioPadre->codigo,
                'pendientes' => $totalPendientes,
                'aplazados' => $totalAplazados,
                'total' => $totalPendientes, // Solo pendientes para el botón DISPONIBLE
                'tiene_hijos' => $servicioPadre->subservicios->isNotEmpty(),
                'pendientes_propios' => $turnosPendientesPadre, // Solo los turnos del padre
                'aplazados_propios' => $turnosAplazadosPadre,   // Solo los turnos del padre
                'subservicios' => $subserviciosDatos
            ];

            $serviciosEstructurados[] = $servicioDatos;
        }

        // Añadir subservicios que son huérfanos (asignados al usuario pero sin padre o con padre no asignado)
        $subserviciosHuerfanos = $serviciosAsignados->filter(function($servicio) {
            return $servicio->esSubservicio() && (!$servicio->servicioPadre || !$servicio->servicioPadre->estaAsignadoA(Auth::id()));
        });

        foreach ($subserviciosHuerfanos as $subservicio) {
            $turnosPendientes = $subservicio->turnos->where('estado', 'pendiente')->count();
            $turnosAplazados = $subservicio->turnos->where('estado', 'aplazado')->count();

            $serviciosEstructurados[] = [
                'id' => $subservicio->id,
                'nombre' => $subservicio->nombre,
                'codigo' => $subservicio->codigo,
                'pendientes' => $turnosPendientes,
                'aplazados' => $turnosAplazados,
                'total' => $turnosPendientes, // Solo pendientes para el botón DISPONIBLE
                'tiene_hijos' => false,
                'pendientes_propios' => $turnosPendientes, // Mismo valor que pendientes
                'aplazados_propios' => $turnosAplazados,   // Mismo valor que aplazados
                'subservicios' => []
            ];
        }

        return response()->json($serviciosEstructurados);
    }

    /**
     * Verificar si el asesor tiene un turno en proceso
     */
    public function verificarTurnoEnProceso()
    {
        $user = Auth::user();

        if (!$user->esAsesor()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        // Buscar turno en proceso solo por asesor_id (no depender de caja_id en sesión)
        $turnoEnProceso = Turno::where('asesor_id', $user->id)
            ->where('estado', 'llamado')
            ->delDia()
            ->with('servicio')
            ->first();

        if ($turnoEnProceso) {
            return response()->json([
                'turno_en_proceso' => true,
                'turno' => [
                    'id' => $turnoEnProceso->id,
                    'codigo_completo' => $turnoEnProceso->codigo_completo,
                    'servicio' => $turnoEnProceso->servicio->nombre,
                    'prioridad' => $turnoEnProceso->prioridad
                ]
            ]);
        }

        return response()->json(['turno_en_proceso' => false]);
    }

    /**
     * Verificar el estado de un turno específico
     */
    public function verificarEstadoTurno($turnoId)
    {
        $user = Auth::user();
        $cajaId = session('caja_seleccionada');

        if (!$user->esAsesor() || !$cajaId) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $turno = Turno::find($turnoId);

        if (!$turno) {
            return response()->json([
                'existe' => false,
                'mensaje' => 'Turno no encontrado'
            ]);
        }

        // Verificar que el turno pertenece al asesor y caja actual
        if ($turno->asesor_id != $user->id || $turno->caja_id != $cajaId) {
            return response()->json([
                'existe' => false,
                'mensaje' => 'Turno no pertenece al asesor actual'
            ]);
        }

        return response()->json([
            'existe' => true,
            'estado' => $turno->estado,
            'codigo_completo' => $turno->codigo_completo,
            'fecha_llamado' => $turno->fecha_llamado,
            'fecha_atencion' => $turno->fecha_atencion
        ]);
    }

    /**
     * Obtener historial de turnos llamados por el asesor hoy
     */
    public function historialTurnos()
    {
        $user = Auth::user();
        $cajaId = session('caja_seleccionada');

        if (!$user->esAsesor() || !$cajaId) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $turnos = Turno::where('asesor_id', $user->id)
            ->where('caja_id', $cajaId)
            ->whereIn('estado', ['llamado', 'atendido', 'aplazado'])
            ->whereNotNull('fecha_llamado')
            ->delDia()
            ->with('servicio')
            ->orderBy('fecha_llamado', 'desc')
            ->get();

        $turnosFormateados = $turnos->map(function ($turno) {
            // Calcular tiempo transcurrido
            $tiempoTranscurrido = '00:00';

            if ($turno->estado === 'atendido' && $turno->duracion_atencion) {
                // Para turnos atendidos, usar la duración guardada (en segundos)
                // Asegurar que siempre sea positiva para visualización
                $duracion = abs((int) $turno->duracion_atencion);

                $minutos = floor($duracion / 60);
                $segundos = $duracion % 60;
                $tiempoTranscurrido = sprintf('%02d:%02d', $minutos, $segundos);
            } elseif ($turno->estado === 'llamado' && $turno->fecha_llamado) {
                // Para turnos en proceso, calcular tiempo desde llamado hasta ahora
                try {
                    // Hacer el cálculo completamente en UTC para evitar problemas de zona horaria
                    $fechaInicio = \Carbon\Carbon::parse($turno->fecha_llamado)->utc();
                    $fechaFin = \Carbon\Carbon::now()->utc();

                    // Log para debugging del historial (comentado para evitar spam en logs)
                    // \Log::info('Calculando tiempo historial para turno llamado', [
                    //     'turno_id' => $turno->id,
                    //     'codigo_completo' => $turno->codigo_completo,
                    //     'fecha_llamado_original' => $turno->fecha_llamado,
                    //     'fecha_inicio_utc' => $fechaInicio->toDateTimeString(),
                    //     'fecha_fin_utc' => $fechaFin->toDateTimeString(),
                    //     'is_future' => $fechaInicio->isFuture()
                    // ]);

                    // Asegurar que la fecha de inicio no sea posterior a la fecha actual
                    if ($fechaInicio->isFuture()) {
                        $tiempoTranscurrido = '00:00';
                        // \Log::info('Fecha de inicio es futura, usando 00:00');
                    } else {
                        // Calcular diferencia correctamente: fecha_fin - fecha_inicio
                        $diferenciaSegundos = $fechaInicio->diffInSeconds($fechaFin);

                        // \Log::info('Diferencia calculada', [
                        //     'diferencia_segundos' => $diferenciaSegundos,
                        //     'fecha_inicio' => $fechaInicio->toDateTimeString(),
                        //     'fecha_fin' => $fechaFin->toDateTimeString()
                        // ]);

                        // diffInSeconds siempre devuelve un valor positivo, así que no necesitamos verificar negativos
                        $minutos = floor($diferenciaSegundos / 60);
                        $segundos = $diferenciaSegundos % 60;
                        $tiempoTranscurrido = sprintf('%02d:%02d', $minutos, $segundos);
                    }
                } catch (\Exception $e) {
                    // En caso de error al parsear la fecha, mostrar ERROR
                    $tiempoTranscurrido = 'ERR:' . $e->getMessage();
                    \Log::error('Error calculando tiempo historial', [
                        'turno_id' => $turno->id,
                        'error' => $e->getMessage()
                    ]);
                }
            } elseif ($turno->estado === 'aplazado' && $turno->duracion_atencion) {
                // Para turnos aplazados, usar la duración guardada
                $duracion = max(0, (int) $turno->duracion_atencion); // Asegurar que no sea negativo
                $minutos = floor($duracion / 60);
                $segundos = $duracion % 60;
                $tiempoTranscurrido = sprintf('%02d:%02d', $minutos, $segundos);
            }

            return [
                'id' => $turno->id,
                'codigo_completo' => $turno->codigo_completo,
                'servicio' => [
                    'nombre' => $turno->servicio ? $turno->servicio->nombre : 'Servicio no encontrado'
                ],
                'estado' => $turno->estado,
                'fecha_llamado' => $turno->fecha_llamado,
                'fecha_atencion' => $turno->fecha_atencion,
                'prioridad' => $turno->prioridad,
                'tiempo_transcurrido' => $tiempoTranscurrido
            ];
        });

        return response()->json([
            'success' => true,
            'turnos' => $turnosFormateados
        ]);
    }

    /**
     * Obtener turnos aplazados de un servicio específico
     */
    public function getTurnosAplazados(Request $request)
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
        $servicio = Servicio::find($servicioId);

        // Verificar que el servicio esté asignado al asesor
        if (!$user->servicios()->where('servicios.id', $servicioId)->exists()) {
            return response()->json(['success' => false, 'message' => 'Servicio no asignado'], 403);
        }

        // Obtener turnos aplazados del servicio y sus subservicios asignados al asesor
        $serviciosIds = [$servicioId];
        
        // Si es un servicio padre, incluir subservicios asignados al asesor
        if ($servicio->subservicios->isNotEmpty()) {
            $subserviciosIds = $servicio->subservicios()
                ->whereHas('usuarios', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->pluck('id')
                ->toArray();
            $serviciosIds = array_merge($serviciosIds, $subserviciosIds);
        }

        $turnosAplazados = Turno::whereIn('servicio_id', $serviciosIds)
            ->where('estado', 'aplazado')
            ->delDia()
            ->with('servicio')
            ->orderBy('prioridad', 'desc')
            ->orderBy('numero', 'asc')
            ->get();

        $turnosFormateados = $turnosAplazados->map(function ($turno) {
            return [
                'id' => $turno->id,
                'codigo_completo' => $turno->codigo_completo,
                'servicio' => [
                    'id' => $turno->servicio->id,
                    'nombre' => $turno->servicio->nombre
                ],
                'prioridad' => $turno->prioridad,
                'prioridad_letra' => $turno->prioridad_letra
            ];
        });

        return response()->json([
            'success' => true,
            'turnos' => $turnosFormateados
        ]);
    }

    /**
     * Volver a llamar un turno aplazado
     */
    public function volverLlamarTurno(Request $request)
    {
        $user = Auth::user();
        $cajaId = session('caja_seleccionada');

        // Si no hay caja en sesión, intentar recuperarla de la base de datos
        if (!$cajaId) {
            $cajaAsignada = Caja::where('asesor_activo_id', $user->id)
                ->where('estado', 'activa')
                ->first();
            
            if ($cajaAsignada) {
                session(['caja_seleccionada' => $cajaAsignada->id]);
                $cajaId = $cajaAsignada->id;
            }
        }

        if (!$user->esAsesor() || !$cajaId) {
            return response()->json(['success' => false, 'message' => 'No autorizado o sin caja asignada'], 403);
        }

        // Verificar si ya tiene un turno en proceso
        $turnoEnProceso = Turno::where('asesor_id', $user->id)
            ->where('estado', 'llamado')
            ->delDia()
            ->first();

        if ($turnoEnProceso) {
            return response()->json([
                'success' => false,
                'message' => 'Debe marcar el turno actual como "Atendido" antes de llamar otro turno.',
                'turno_en_proceso' => $turnoEnProceso->codigo_completo
            ], 400);
        }

        // Validar que se proporcione turno_id O codigo_completo
        $request->validate([
            'turno_id' => 'nullable|integer|exists:turnos,id',
            'codigo_completo' => 'nullable|string'
        ]);

        // Buscar turno por ID o por código completo
        if ($request->has('turno_id') && $request->turno_id) {
            $turno = Turno::find($request->turno_id);
        } elseif ($request->has('codigo_completo') && $request->codigo_completo) {
            // Parsear código completo (formato: "CP-001")
            $datos = Turno::parsearCodigoCompleto($request->codigo_completo);

            if (!$datos) {
                return response()->json([
                    'success' => false,
                    'message' => 'Formato de código completo inválido'
                ]);
            }

            $turno = Turno::where('codigo', $datos['codigo'])
                ->where('numero', $datos['numero'])
                ->where('estado', 'aplazado')
                ->delDia()
                ->first();
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Debe proporcionar turno_id o codigo_completo'
            ]);
        }

        if (!$turno) {
            return response()->json([
                'success' => false,
                'message' => 'Turno no encontrado'
            ]);
        }

        // Verificar que el turno esté aplazado
        if ($turno->estado !== 'aplazado') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden volver a llamar turnos aplazados'
            ], 400);
        }

        // Verificar que el asesor tenga el servicio del turno asignado
        if (!$user->servicios()->where('servicios.id', $turno->servicio_id)->exists()) {
            // También verificar si tiene asignado el servicio padre
            $servicio = Servicio::find($turno->servicio_id);
            $tieneAcceso = false;
            if ($servicio && $servicio->servicio_padre_id) {
                $tieneAcceso = $user->servicios()->where('servicios.id', $servicio->servicio_padre_id)->exists();
            }
            if (!$tieneAcceso) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para gestionar este turno'
                ], 403);
            }
        }

        // Marcar como llamado nuevamente (esto establece fecha_llamado y estado='llamado')
        $turno->marcarComoLlamado($cajaId, $user->id);

        // Refrescar el modelo para asegurar que tenga los datos actualizados
        $turno->refresh();

        // Cargar relaciones necesarias para el broadcaster
        $turno->load('servicio', 'caja');

        // Log para debugging
        \Log::info('Volviendo a llamar turno aplazado', [
            'turno_id' => $turno->id,
            'codigo_completo' => $turno->codigo_completo,
            'estado' => $turno->estado,
            'fecha_llamado' => $turno->fecha_llamado,
            'caja_id' => $turno->caja_id,
            'asesor_id' => $turno->asesor_id
        ]);

        // Transmitir el evento al televisor
        $broadcastResult = TurneroBroadcaster::notificarTurnoLlamado($turno);

        // Log del resultado del broadcast
        \Log::info('Resultado del broadcast', [
            'turno_id' => $turno->id,
            'broadcast_exitoso' => $broadcastResult
        ]);

        return response()->json([
            'success' => true,
            'message' => "Turno {$turno->codigo_completo} llamado nuevamente",
            'turno' => [
                'id' => $turno->id,
                'codigo_completo' => $turno->codigo_completo,
                'servicio' => $turno->servicio->nombre,
                'prioridad' => $turno->prioridad
            ]
        ]);
    }

    /**
     * Rellamar el turno actual (volver a emitir sonido en TV sin cambiar estado)
     */
    public function rellamarTurno(Request $request)
    {
        $user = Auth::user();
        $cajaId = session('caja_seleccionada');

        // Si no hay caja en sesión, intentar recuperarla de la base de datos
        if (!$cajaId) {
            $cajaAsignada = Caja::where('asesor_activo_id', $user->id)
                ->where('estado', 'activa')
                ->first();
            
            if ($cajaAsignada) {
                session(['caja_seleccionada' => $cajaAsignada->id]);
                $cajaId = $cajaAsignada->id;
            }
        }

        if (!$user->esAsesor() || !$cajaId) {
            return response()->json(['success' => false, 'message' => 'No autorizado o sin caja asignada'], 403);
        }

        // Validar que se proporcione turno_id O codigo_completo
        $request->validate([
            'turno_id' => 'nullable|integer|exists:turnos,id',
            'codigo_completo' => 'nullable|string'
        ]);

        // Buscar turno por ID o por código completo
        if ($request->has('turno_id') && $request->turno_id) {
            $turno = Turno::find($request->turno_id);
        } elseif ($request->has('codigo_completo') && $request->codigo_completo) {
            // Parsear código completo (formato: "CP-001")
            $datos = Turno::parsearCodigoCompleto($request->codigo_completo);

            if (!$datos) {
                return response()->json([
                    'success' => false,
                    'message' => 'Formato de código completo inválido'
                ]);
            }

            $turno = Turno::where('codigo', $datos['codigo'])
                ->where('numero', $datos['numero'])
                ->where('asesor_id', $user->id)
                ->delDia()
                ->first();
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Debe proporcionar turno_id o codigo_completo'
            ]);
        }

        if (!$turno) {
            return response()->json([
                'success' => false,
                'message' => 'Turno no encontrado'
            ]);
        }

        // Verificar que el turno pertenece al asesor
        if ($turno->asesor_id != $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para gestionar este turno'
            ], 403);
        }

        // Verificar que el turno esté en estado llamado
        if ($turno->estado !== 'llamado') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden rellamar turnos que estén en estado llamado'
            ], 400);
        }

        // Actualizar fecha_llamado para que el TV lo detecte como nuevo
        $turno->fecha_llamado = Carbon::now();
        $turno->save();

        // Cargar relaciones necesarias para el broadcaster
        $turno->load('servicio', 'caja');

        // Log para debugging
        \Log::info('Rellamando turno actual', [
            'turno_id' => $turno->id,
            'codigo_completo' => $turno->codigo_completo,
            'estado' => $turno->estado,
            'fecha_llamado' => $turno->fecha_llamado,
            'caja_id' => $turno->caja_id,
            'asesor_id' => $turno->asesor_id
        ]);

        // Transmitir el evento al televisor (sin cambiar estado)
        $broadcastResult = TurneroBroadcaster::notificarTurnoLlamado($turno);

        // Log del resultado del broadcast
        \Log::info('Resultado del broadcast (rellamar)', [
            'turno_id' => $turno->id,
            'broadcast_exitoso' => $broadcastResult
        ]);

        return response()->json([
            'success' => true,
            'message' => "Turno {$turno->codigo_completo} rellamado - sonará en el televisor",
            'turno' => [
                'id' => $turno->id,
                'codigo_completo' => $turno->codigo_completo,
                'servicio' => $turno->servicio->nombre,
                'prioridad' => $turno->prioridad
            ]
        ]);
    }

    /**
     * Auto-llamar turno automáticamente cuando el asesor lleva tiempo sin turno.
     * Este endpoint es llamado desde el frontend del asesor cuando tiene
     * la opción auto_llamado_activo habilitada.
     */
    public function autoLlamarTurno(Request $request)
    {
        $user = Auth::user();
        $cajaId = session('caja_seleccionada');

        // Si no hay caja en sesión, intentar recuperarla de la base de datos
        if (!$cajaId) {
            $cajaAsignada = Caja::where('asesor_activo_id', $user->id)
                ->where('estado', 'activa')
                ->first();
            
            if ($cajaAsignada) {
                session(['caja_seleccionada' => $cajaAsignada->id]);
                $cajaId = $cajaAsignada->id;
            }
        }

        if (!$user->esAsesor() || !$cajaId) {
            return response()->json(['success' => false, 'message' => 'No autorizado o sin caja asignada'], 403);
        }

        // Verificar que el usuario tenga auto_llamado_activo
        if (!$user->auto_llamado_activo) {
            return response()->json([
                'success' => false,
                'message' => 'Auto-llamado no está activado para este usuario'
            ]);
        }

        // Verificar que el asesor no esté en canal no presencial
        if ($user->estaEnCanalNoPresencial()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede auto-llamar mientras está en canal no presencial'
            ]);
        }

        // Verificar que el asesor no esté en descanso
        if ($user->estado_asesor === 'descanso') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede auto-llamar mientras está en descanso'
            ]);
        }

        // Verificar que no tenga un turno en proceso
        $turnoEnProceso = Turno::where('asesor_id', $user->id)
            ->where('estado', 'llamado')
            ->delDia()
            ->first();

        if ($turnoEnProceso) {
            return response()->json([
                'success' => false,
                'message' => 'Ya tiene un turno en proceso',
                'turno_en_proceso' => $turnoEnProceso->codigo_completo
            ]);
        }

        // Obtener TODOS los servicios asignados al asesor (activos, sin ocultar_turno)
        $serviciosAsignados = $user->serviciosActivos()
            ->where('ocultar_turno', false)
            ->get();

        if ($serviciosAsignados->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene servicios asignados con turnos disponibles'
            ]);
        }

        // Recopilar todos los IDs de servicios (incluyendo subservicios)
        $todosLosServiciosIds = [];
        foreach ($serviciosAsignados as $servicio) {
            $todosLosServiciosIds[] = $servicio->id;
            
            // Si es servicio padre, también incluir subservicios asignados
            if ($servicio->subservicios->isNotEmpty()) {
                $subserviciosIds = $servicio->subservicios()
                    ->whereHas('usuarios', function($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })
                    ->where('ocultar_turno', false)
                    ->pluck('id')
                    ->toArray();
                $todosLosServiciosIds = array_merge($todosLosServiciosIds, $subserviciosIds);
            }
        }

        $todosLosServiciosIds = array_unique($todosLosServiciosIds);

        // Buscar turno usando el algoritmo de peso proporcional existente
        $turno = $this->buscarTurnoConPesoProporcional($todosLosServiciosIds);

        if (!$turno) {
            return response()->json([
                'success' => false,
                'message' => 'No hay turnos pendientes en ningún servicio'
            ]);
        }

        \Log::info('Auto-llamado de turno activado', [
            'user_id' => $user->id,
            'user_name' => $user->nombre_completo,
            'caja_id' => $cajaId,
            'turno_id' => $turno->id,
            'turno_codigo' => $turno->codigo_completo,
            'servicio' => $turno->servicio->nombre ?? 'N/A'
        ]);

        return $this->responderConTurno($turno, $cajaId, $user->id);
    }

    /**
     * Obtener estado actual del auto-llamado para el asesor.
     * Devuelve la configuración actualizada y la última actividad de turno real.
     * Esto permite que el frontend se sincronice con cambios del admin y
     * mantenga el conteo real incluso al recargar la página.
     */
    public function getAutoLlamadoStatus()
    {
        $user = Auth::user();

        if (!$user->esAsesor()) {
            return response()->json(['success' => false], 403);
        }

        // Refrescar datos del usuario desde la BD (por si el admin cambió la config)
        $user->refresh();

        // Detectar cambio de estado de auto-llamado (activación/desactivación)
        $estabaActivo = session('auto_llamado_was_active', false);
        $ahoraActivo = (bool) $user->auto_llamado_activo;

        if ($ahoraActivo && !$estabaActivo) {
            // Se acaba de ACTIVAR → resetear timer desde ahora (empieza de cero)
            session(['auto_llamado_inicio_ts' => now()->timestamp]);
            \Log::info('Auto-llamado ACTIVADO para usuario ' . $user->nombre_completo);
        } elseif (!$ahoraActivo && $estabaActivo) {
            // Se acaba de DESACTIVAR → limpiar el timer guardado
            session()->forget('auto_llamado_inicio_ts');
            \Log::info('Auto-llamado DESACTIVADO para usuario ' . $user->nombre_completo);
        }
        // Guardar estado actual para la próxima comparación
        session(['auto_llamado_was_active' => $ahoraActivo]);

        // Buscar la última actividad de turno del asesor hoy
        $ultimoTurnoFinalizado = Turno::where('asesor_id', $user->id)
            ->whereIn('estado', ['atendido', 'aplazado'])
            ->delDia()
            ->orderBy('fecha_atencion', 'desc')
            ->first();

        // También verificar si tiene un turno llamado actualmente
        $turnoLlamado = Turno::where('asesor_id', $user->id)
            ->where('estado', 'llamado')
            ->delDia()
            ->first();

        // Determinar la última actividad:
        $ultimaActividad = null;

        if ($turnoLlamado) {
            // Tiene turno en proceso - no aplica auto-llamado
            $ultimaActividad = now()->timestamp;
        } elseif ($ultimoTurnoFinalizado && $ultimoTurnoFinalizado->fecha_atencion) {
            $ultimaActividad = Carbon::parse($ultimoTurnoFinalizado->fecha_atencion)->timestamp;
            session(['auto_llamado_inicio_ts' => $ultimaActividad]);
        } else {
            // No ha atendido turnos hoy - usar el timestamp persistente de sesión
            if (session()->has('auto_llamado_inicio_ts')) {
                $ultimaActividad = session('auto_llamado_inicio_ts');
            } else {
                $ultimaActividad = now()->timestamp;
                session(['auto_llamado_inicio_ts' => $ultimaActividad]);
            }
        }

        return response()->json([
            'success' => true,
            'auto_llamado_activo' => (bool) $user->auto_llamado_activo,
            'auto_llamado_minutos' => (int) ($user->auto_llamado_minutos ?? 10),
            'ultima_actividad_timestamp' => $ultimaActividad,
            'servidor_timestamp' => now()->timestamp,
            'tiene_turno_en_proceso' => $turnoLlamado ? true : false,
        ]);
    }

}
