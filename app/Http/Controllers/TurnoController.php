<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\Turno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class TurnoController extends Controller
{
    // Nombre de quien escribió su cédula en esta petición (solo para mostrarlo en pantalla: nunca va al ticket)
    private ?string $nombrePaciente = null;

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

            // Cédula: si el servicio la pide, el atril la pregunta primero (antes del tipo de turno)
            $cedula = $this->cedulaDelPaciente($request, $servicio);
            if ($cedula instanceof \Illuminate\Http\JsonResponse) {
                return $cedula;
            }

            // Verificar si el servicio requiere priorización
            if ($servicio->requiere_priorizacion) {
                // Retornar indicación de que debe mostrar selector de prioridad
                return response()->json([
                    'success' => true,
                    'requiere_priorizacion' => true,
                    'servicio_id' => $servicioParaTurno,
                    'servicio_nombre' => $servicio->nombre_completo,
                    'paciente_nombre' => $this->nombrePaciente
                ]);
            }

            // Protección contra doble clic: verificar si ya se creó un turno
            // para este servicio en los últimos 3 segundos (solo doble-clic real).
            // Con cédula, solo es doble clic si es la misma persona: otro paciente puede sacar turno en esos segundos.
            // Y se sabe quién es, así que se mira más atrás: cubre el reintento del atril tras una respuesta perdida.
            $turnoReciente = Turno::where('servicio_id', $servicioParaTurno)
                ->where('estado', 'pendiente')
                ->where('fecha_creacion', '>=', now()->subSeconds($cedula ? 60 : 3))
                ->whereNull('observaciones') // No contar transferencias
                ->when($cedula, fn ($q) => $q->where('cedula', $cedula))
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
                        'numero' => $turnoReciente->numero,
                        'ubicacion' => $servicio->ubicacionAtencion()
                    ],
                    'duplicado' => true
                ]);
            }

            // Si no requiere priorización, crear turno con prioridad por defecto
            // Pasar el objeto $servicio para evitar queries redundantes
            $turno = Turno::crear($servicio, 3, $cedula); // Prioridad C por defecto

            $nombreServicio = $servicio->nombre_completo;
            $mensaje = "Turno generado: {$turno->codigo_completo} para {$nombreServicio}";

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'turno' => [
                    'id' => $turno->id,
                    'codigo_completo' => $turno->codigo_completo,
                    'servicio' => $nombreServicio,
                    'numero' => $turno->numero,
                    // Ubicación física de la sección, para imprimirla en el ticket
                    'ubicacion' => $servicio->ubicacionAtencion()
                ],
                'paciente_nombre' => $this->nombrePaciente,
                'redirect_url' => route('turnos.ticket', $turno->id)
            ]);

        } catch (\Exception $e) {
            return $this->falloAlGenerarTurno($e, 'seleccionarServicio');
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

            // Cédula: el atril la pidió antes del tipo de turno y la manda aquí
            $cedula = $this->cedulaDelPaciente($request, $servicio);
            if ($cedula instanceof \Illuminate\Http\JsonResponse) {
                return $cedula;
            }

            // Protección contra doble clic: verificar si ya se creó un turno
            // para este servicio en los últimos 3 segundos (solo doble-clic real).
            // Con cédula, solo es doble clic si es la misma persona (y se mira más atrás, ver seleccionarServicio).
            $turnoReciente = Turno::where('servicio_id', $servicioId)
                ->where('estado', 'pendiente')
                ->where('fecha_creacion', '>=', now()->subSeconds($cedula ? 60 : 3))
                ->whereNull('observaciones') // No contar transferencias
                ->when($cedula, fn ($q) => $q->where('cedula', $cedula))
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
                        'prioridad' => $turnoReciente->prioridad >= 4 ? 'Prioritario' : 'Normal',
                        'ubicacion' => $servicio->ubicacionAtencion()
                    ],
                    'duplicado' => true
                ]);
            }

            // Crear el turno - pasar el objeto $servicio para evitar queries redundantes
            $turno = Turno::crear($servicio, $prioridad, $cedula);

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
                    'prioridad' => $tipoPrioridad,
                    // Ubicación física de la sección, para imprimirla en el ticket
                    'ubicacion' => $servicio->ubicacionAtencion()
                ],
                'paciente_nombre' => $this->nombrePaciente,
                'redirect_url' => route('turnos.ticket', $turno->id)
            ]);

        } catch (\Exception $e) {
            return $this->falloAlGenerarTurno($e, 'crearTurnoConPrioridad');
        }
    }

    /**
     * El mensaje de un error de BD trae el SQL con sus valores, cédula incluida: ni a la pantalla del atril ni al log.
     */
    private function falloAlGenerarTurno(\Exception $e, string $donde)
    {
        \Log::error('❌ ERROR en ' . $donde . '()', [
            'error' => $e instanceof \Illuminate\Database\QueryException ? ($e->errorInfo[1] ?? $e->getCode()) : $e->getMessage(),
            'lugar' => $e->getFile() . ':' . $e->getLine(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'No se pudo generar el turno. Intente de nuevo o acérquese a una ventanilla.'
        ]);
    }

    /**
     * La cédula que escribió el paciente si el servicio la pide (null si no la pide), o la respuesta para el atril:
     * "pídala" si aún no llegó, o un aviso si no es válida o no está en la base de pacientes: ahí no sale turno.
     * Un servicio que pide cédula nunca genera turno sin ella. Sin base cargada, la cédula se guarda sin comprobarla.
     */
    private function cedulaDelPaciente(Request $request, Servicio $servicio)
    {
        if (!$servicio->requiere_cedula) {
            return null;
        }

        if (!$request->filled('cedula')) {
            return response()->json([
                'success' => true,
                'requiere_cedula' => true,
                'servicio_id' => $servicio->id,
                'servicio_nombre' => $servicio->nombre_completo,
            ]);
        }

        $cedula = Turno::normalizarCedula($request->input('cedula'));
        if ($cedula === null) {
            return response()->json([
                'success' => false,
                'cedula_invalida' => true,
                'message' => 'Revise el número de cédula: debe tener entre 4 y 15 dígitos.',
            ]);
        }

        if (!Paciente::hayBase()) {
            return $cedula;
        }

        // El atril es público y una cédula devuelve un nombre: solo los equipos autorizados consultan la base
        $ip = (string) $request->ip();
        if (!Paciente::equipoAutorizado($ip)) {
            return response()->json([
                'success' => false,
                'cedula_invalida' => true,
                'message' => 'Este servicio solo se puede usar desde los atriles del hospital.',
            ], 403);
        }

        // Además se limita cuánto se puede probar. Lo que frena recorrer números son los fallos (casi todo número al azar
        // falla). Por sesión, porque los atriles suelen salir por una misma IP y uno no debe bloquear a los demás; por IP,
        // un techo mucho más alto, porque una sesión nueva se abre con solo recargar.
        $sesion = $request->hasSession() ? $request->session()->getId() : $ip;
        $fallosPorMinuto = ['atril-fallos-sesion:' . $sesion => 30, 'atril-fallos-ip:' . $ip => 120];
        $fallosPorHora = ['atril-fallos-sesion-hora:' . $sesion => 300, 'atril-fallos-ip-hora:' . $ip => 1500];
        foreach ($fallosPorMinuto + $fallosPorHora + ['atril-consultas-ip:' . $ip => 300] as $clave => $tope) {
            if (RateLimiter::tooManyAttempts($clave, $tope)) {
                \Log::warning('Atril: límite de consultas de cédula', ['limite' => strtok($clave, ':'), 'ip' => $ip]);

                return response()->json([
                    'success' => false,
                    'cedula_invalida' => true,
                    'message' => 'Se han hecho demasiados intentos desde este equipo. Acérquese a una ventanilla.',
                ], 429);
            }
        }
        RateLimiter::hit('atril-consultas-ip:' . $ip, 60);

        $nombre = Paciente::nombreDe($cedula);
        if ($nombre === null) {
            foreach (array_keys($fallosPorMinuto) as $clave) {
                RateLimiter::hit($clave, 60);
            }
            foreach (array_keys($fallosPorHora) as $clave) {
                RateLimiter::hit($clave, 3600);
            }

            return response()->json([
                'success' => false,
                'cedula_invalida' => true,
                'message' => 'No encontramos esta cédula. Revísela o acérquese a una ventanilla.',
            ]);
        }

        $this->nombrePaciente = $nombre;

        return $cedula;
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
