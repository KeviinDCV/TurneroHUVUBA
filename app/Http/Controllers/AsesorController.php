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

        // Si no hay caja en sesión, buscar en la base de datos
        if (!$cajaId) {
            $cajaAsignada = Caja::where('asesor_activo_id', $user->id)
                ->where('estado', 'activa')
                ->first();

            if ($cajaAsignada && $cajaAsignada->estaOcupada()) {
                // Actualizar el session_id en la base de datos para la nueva sesión
                $cajaAsignada->update([
                    'session_id' => session()->getId(),
                    'fecha_asignacion' => now(), // Renovar la fecha de asignación
                    'ip_asesor' => request()->ip()
                ]);

                // Restaurar la caja en la sesión
                session(['caja_seleccionada' => $cajaAsignada->id]);
                $cajaId = $cajaAsignada->id;

                \Log::info("Caja restaurada en sesión para usuario {$user->nombre_usuario}", [
                    'caja_id' => $cajaAsignada->id,
                    'caja_nombre' => $cajaAsignada->nombre,
                    'nueva_session_id' => session()->getId()
                ]);
            } else {
                // No tiene caja asignada, redirigir a selección
                return redirect()->route('asesor.seleccionar-caja');
            }
        }

        $caja = Caja::find($cajaId);
        if (!$caja || $caja->estado !== 'activa') {
            // Si la caja ya no existe o está inactiva, redirigir a selección
            session()->forget('caja_seleccionada');
            return redirect()->route('asesor.seleccionar-caja')
                ->withErrors(['caja' => 'La caja seleccionada ya no está disponible.']);
        }

        // Verificar que la caja siga asignada al usuario actual
        if (!$caja->estaOcupadaPor($user->id)) {
            // La caja ya no está asignada a este usuario, limpiar sesión
            session()->forget('caja_seleccionada');
            return redirect()->route('asesor.seleccionar-caja')
                ->withErrors(['caja' => 'La caja ya no está asignada a tu usuario.']);
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
                        'total' => $turnosPendientesHijo + $turnosAplazadosHijo
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
                'total' => $totalPendientes + $totalAplazados,
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
                'total' => $turnosPendientes + $turnosAplazados,
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

        if (!$user->esAsesor() || !$cajaId) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        // VALIDACIÓN CRÍTICA: Verificar si el asesor ya tiene un turno en proceso
        $turnoEnProceso = Turno::where('asesor_id', $user->id)
            ->where('caja_id', $cajaId)
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

        // Obtener IDs de servicios a consultar
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
        } else {
            // Si es un servicio regular, solo buscar en ese servicio
            $serviciosIds = [$servicioId];
        }

        // Buscar turno usando lógica de ratio fijo por prioridad
        $turno = $this->buscarTurnoConRatioFijo($serviciosIds, $user->id);

        if (!$turno) {
            return response()->json([
                'success' => false,
                'message' => 'No hay turnos pendientes para este servicio'
            ]);
        }

        return $this->responderConTurno($turno, $cajaId, $user->id);
    }

    /**
     * Método para buscar turno con lógica de ratio fijo por prioridad
     * Ratio: Por cada 3 turnos de una prioridad, 1 de la prioridad inmediata inferior
     *
     * @param array $serviciosIds IDs de servicios donde buscar
     * @param int $asesorId ID del asesor
     * @return Turno|null El turno encontrado o null
     */
    private function buscarTurnoConRatioFijo($serviciosIds, $asesorId)
    {
        if (empty($serviciosIds)) {
            return null;
        }

        // Obtener contadores de sesión para este asesor
        $sessionKey = 'prioridad_contador_' . $asesorId;
        $contadores = session($sessionKey, [
            5 => 0, // Contador de turnos llamados de prioridad 5
            4 => 0,
            3 => 0,
            2 => 0,
            1 => 0
        ]);

        // Determinar qué prioridad buscar según el ratio 3:1
        $prioridadBuscada = $this->determinarPrioridadSegunRatio($contadores);

        // Buscar turno de la prioridad determinada
        $turno = $this->buscarTurnoPorPrioridad($serviciosIds, $prioridadBuscada);

        // Si no hay turno de esa prioridad, buscar de la más alta disponible
        if (!$turno) {
            for ($prioridad = 5; $prioridad >= 1; $prioridad--) {
                $turno = $this->buscarTurnoPorPrioridad($serviciosIds, $prioridad);
                if ($turno) {
                    $prioridadBuscada = $prioridad;
                    break;
                }
            }
        }

        // Si encontramos turno, actualizar contadores
        if ($turno) {
            $contadores[$prioridadBuscada]++;
            session([$sessionKey => $contadores]);
        }

        return $turno;
    }

    /**
     * Determinar qué prioridad buscar según los contadores y el ratio 3:1
     *
     * @param array $contadores Contadores actuales
     * @return int Prioridad a buscar (1-5)
     */
    private function determinarPrioridadSegunRatio($contadores)
    {
        // Lógica de ratio 3:1 descendente
        // Si se han llamado 3 turnos de prioridad 5, buscar 1 de prioridad 4
        if ($contadores[5] >= 3 && $contadores[5] % 4 == 3) {
            return 4;
        }

        // Si se han llamado 3 turnos de prioridad 4, buscar 1 de prioridad 3
        if ($contadores[4] >= 3 && $contadores[4] % 4 == 3) {
            return 3;
        }

        // Si se han llamado 3 turnos de prioridad 3, buscar 1 de prioridad 2
        if ($contadores[3] >= 3 && $contadores[3] % 4 == 3) {
            return 2;
        }

        // Si se han llamado 3 turnos de prioridad 2, buscar 1 de prioridad 1
        if ($contadores[2] >= 3 && $contadores[2] % 4 == 3) {
            return 1;
        }

        // Por defecto, buscar de la prioridad más alta (5)
        return 5;
    }

    /**
     * Buscar un turno de una prioridad específica
     *
     * @param array $serviciosIds IDs de servicios donde buscar
     * @param int $prioridad Prioridad a buscar (1-5)
     * @return Turno|null El turno encontrado o null
     */
    private function buscarTurnoPorPrioridad($serviciosIds, $prioridad)
    {
        return Turno::whereIn('servicio_id', $serviciosIds)
            ->whereIn('estado', ['pendiente', 'aplazado'])
            ->where('prioridad', $prioridad)
            ->delDia()
            ->orderBy('estado', 'asc') // Pendientes primero
            ->orderBy('numero', 'asc')
            ->first();
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
        $cajaId = session('caja_seleccionada');

        if (!$user->esAsesor() || !$cajaId) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        // VALIDACIÓN CRÍTICA: Verificar si el asesor ya tiene un turno en proceso
        $turnoEnProceso = Turno::where('asesor_id', $user->id)
            ->where('caja_id', $cajaId)
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

        if (!$user->esAsesor() || !$cajaId) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        // Validar que se proporcione turno_id O codigo_completo
        $request->validate([
            'turno_id' => 'nullable|exists:turnos,id',
            'codigo_completo' => 'nullable|string',
            'duracion' => 'nullable|integer'
        ]);

        // Buscar turno por ID o por código completo
        if ($request->has('turno_id')) {
            $turno = Turno::find($request->turno_id);
        } elseif ($request->has('codigo_completo')) {
            // Extraer código y número del código completo (ej: "CP-001" -> código="CP", número=1)
            $codigoCompleto = $request->codigo_completo;
            $partes = explode('-', $codigoCompleto);

            if (count($partes) !== 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Formato de código completo inválido'
                ]);
            }

            $codigo = $partes[0];
            $numero = (int) $partes[1];

            $turno = Turno::where('codigo', $codigo)
                ->where('numero', $numero)
                ->where('asesor_id', $user->id)
                ->where('caja_id', $cajaId)
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

        // Verificar que el turno esté asignado a esta caja y asesor (solo si se buscó por turno_id)
        if ($request->has('turno_id') && ($turno->caja_id != $cajaId || $turno->asesor_id != $user->id)) {
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

        if (!$user->esAsesor() || !$cajaId) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        // Validar que se proporcione turno_id O codigo_completo
        $request->validate([
            'turno_id' => 'nullable|exists:turnos,id',
            'codigo_completo' => 'nullable|string',
            'duracion' => 'nullable|integer'
        ]);

        // Buscar turno por ID o por código completo
        if ($request->has('turno_id')) {
            $turno = Turno::find($request->turno_id);
        } elseif ($request->has('codigo_completo')) {
            // Extraer código y número del código completo (ej: "CP-001" -> código="CP", número=1)
            $codigoCompleto = $request->codigo_completo;
            $partes = explode('-', $codigoCompleto);

            if (count($partes) !== 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Formato de código completo inválido'
                ]);
            }

            $codigo = $partes[0];
            $numero = (int) $partes[1];

            $turno = Turno::where('codigo', $codigo)
                ->where('numero', $numero)
                ->where('asesor_id', $user->id)
                ->where('caja_id', $cajaId)
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

        // Verificar que el turno esté asignado a esta caja y asesor (solo si se buscó por turno_id)
        if ($request->has('turno_id') && ($turno->caja_id != $cajaId || $turno->asesor_id != $user->id)) {
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

        return response()->json([
            'success' => true,
            'message' => 'Turno aplazado correctamente'
        ]);
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
                        'total' => $turnosPendientesHijo + $turnosAplazadosHijo
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
                'total' => $totalPendientes + $totalAplazados,
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
                'total' => $turnosPendientes + $turnosAplazados,
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
        $cajaId = session('caja_seleccionada');

        if (!$user->esAsesor() || !$cajaId) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $turnoEnProceso = Turno::where('asesor_id', $user->id)
            ->where('caja_id', $cajaId)
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
                $duracion = (int) $turno->duracion_atencion;

                // Si la duración es negativa, mostrar el valor para debugging
                if ($duracion < 0) {
                    $tiempoTranscurrido = 'NEG:' . abs($duracion);
                } else {
                    $minutos = floor($duracion / 60);
                    $segundos = $duracion % 60;
                    $tiempoTranscurrido = sprintf('%02d:%02d', $minutos, $segundos);
                }
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
     * Volver a llamar un turno aplazado
     */
    public function volverLlamarTurno(Request $request)
    {
        $user = Auth::user();
        $cajaId = session('caja_seleccionada');

        if (!$user->esAsesor() || !$cajaId) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        // Verificar si ya tiene un turno en proceso
        $turnoEnProceso = Turno::where('asesor_id', $user->id)
            ->where('caja_id', $cajaId)
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

        $request->validate([
            'turno_id' => 'required|integer|exists:turnos,id'
        ]);

        $turno = Turno::find($request->turno_id);

        // Verificar que el turno pertenece al asesor
        if ($turno->asesor_id != $user->id || $turno->caja_id != $cajaId) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para gestionar este turno'
            ], 403);
        }

        // Verificar que el turno esté aplazado
        if ($turno->estado !== 'aplazado') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden volver a llamar turnos aplazados'
            ], 400);
        }

        // Marcar como llamado nuevamente
        $turno->marcarComoLlamado($cajaId, $user->id);

        // Transmitir el evento
        TurneroBroadcaster::notificarTurnoLlamado($turno);

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


}
