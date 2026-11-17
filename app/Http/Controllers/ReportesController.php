<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Turno;
use App\Models\TurnoHistorial;
use App\Models\Servicio;
use App\Models\Caja;
use App\Services\LibroExcel;
use App\Services\MetricasTurnos;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportesController extends Controller
{
    /**
     * Columnas que necesitan las cuentas del informe. Se cargan sin modelos de Eloquent: un periodo largo (todo el año)
     * son cientos de miles de turnos, y como modelos con sus relaciones pasaban del límite de memoria del servidor.
     */
    private const COLUMNAS_CUENTAS = ['id', 'servicio_id', 'asesor_id', 'estado', 'created_at', 'fecha_llamado', 'fecha_atencion', 'duracion_atencion', 'observaciones'];

    /** Columnas del detalle de turnos del Excel, que se lee por bloques mientras se escribe. */
    private const COLUMNAS_DETALLE = ['id', 'codigo', 'numero', 'cedula', 'servicio_id', 'asesor_id', 'caja_id', 'estado', 'prioridad', 'created_at', 'fecha_llamado', 'fecha_atencion', 'fecha_finalizacion', 'duracion_atencion', 'observaciones'];

    private const TURNOS_POR_BLOQUE = 2000;

    /** Servicios, usuarios y nombres de cajas por id: tablas pequeñas que reemplazan las relaciones de cada turno. */
    private $servicios;
    private $usuarios;
    private $cajas;
    private ?array $columnasDetalle = null;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Mostrar la vista principal de reportes
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $asesores = User::where('rol', 'Asesor')->orderBy('nombre_completo')->get()
            ->map(fn ($u) => ['id' => (int) $u->id, 'nombre' => ($u->nombre_completo ?: $u->nombre_usuario) . ($u->estaDesactivada() ? ' (desactivada)' : ''),
                'usuario' => $u->nombre_usuario])->values();

        // Secciones con sus subservicios (también los inactivos: pueden tener turnos en el periodo).
        $servicios = Servicio::orderBy('orden')->orderBy('nombre')->get(['id', 'nombre', 'servicio_padre_id', 'estado']);
        $hijosDe = $servicios->whereNotNull('servicio_padre_id')->groupBy('servicio_padre_id');
        $fila = fn ($s) => ['id' => (int) $s->id, 'nombre' => $s->nombre, 'activo' => $s->estado === 'activo'];
        $secciones = $servicios->filter(fn ($s) => !$s->servicio_padre_id || !$servicios->contains('id', $s->servicio_padre_id))
            ->map(fn ($s) => $fila($s) + ['hijos' => $hijosDe->get($s->id, collect())->map($fila)->values()->all()])
            ->values();

        // Valores iniciales desde la URL (Gráficos enlaza aquí con su mismo periodo y servicio).
        $fecha = function ($v) {
            try { return $v ? Carbon::createFromFormat('!Y-m-d', (string) $v)->toDateString() : null; } catch (\Throwable $e) { return null; }
        };
        $inicial = [
            'desde' => $fecha($request->query('desde')),
            'hasta' => $fecha($request->query('hasta')),
            'servicio' => (int) $request->query('servicio') ?: null,
        ];

        return view('admin.reportes', compact('user', 'asesores', 'secciones', 'inicial'));
    }

    /**
     * Generar reporte general
     */
    public function generarReporte(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'alcance' => 'nullable|in:todo,servicios,asesores',
            'usuarios' => 'nullable|array',
            'usuarios.*' => 'integer',
            'servicios' => 'nullable|array',
            'servicios.*' => 'integer',
            'formato' => 'required|in:excel,pdf'
        ], [
            'fecha_fin.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
        ]);

        $fechaInicio = Carbon::parse($request->fecha_inicio)->startOfDay();
        $fechaFin = Carbon::parse($request->fecha_fin)->endOfDay();
        $usuarios = array_map('intval', $request->usuarios ?? []);
        $servicios = array_map('intval', $request->servicios ?? []);

        // Alcance explícito. Sin alcance (enlaces antiguos) se deduce de lo marcado; nada marcado = todo el turnero.
        $alcance = $request->alcance ?: (!empty($servicios) ? 'servicios' : (!empty($usuarios) ? 'asesores' : 'todo'));
        if ($alcance === 'servicios' && empty($servicios)) {
            return response()->json(['success' => false, 'message' => 'Elige al menos un servicio o cambia a «Todo el turnero».'], 422);
        }
        if ($alcance === 'asesores' && empty($usuarios)) {
            return response()->json(['success' => false, 'message' => 'Elige al menos un asesor o cambia a «Todo el turnero».'], 422);
        }

        // Una sección incluye sus subservicios (los turnos se registran en los subservicios).
        if ($alcance === 'servicios') {
            $servicios = array_values(array_unique(array_merge(
                $servicios, Servicio::whereIn('servicio_padre_id', $servicios)->pluck('id')->map(fn ($id) => (int) $id)->all()
            )));
        }

        // Un periodo largo (todo el año) es mucho más trabajo que un mes: más tiempo y, por si acaso, más memoria.
        $this->darMargenParaInformesLargos();

        // Todo el informe se lee en una sola transacción (solo lectura, se deshace al final): las cuentas y el detalle, que se
        // lee por bloques segundos después, ven la misma foto de la base aunque mientras tanto se atiendan turnos.
        DB::beginTransaction();
        try {
            $respuesta = $this->armarInforme($request, $fechaInicio, $fechaFin, $alcance, $servicios, $usuarios);
        } finally {
            DB::rollBack();
        }

        // Reportes descarga por URL (GET) en un iframe para que los gestores de descarga (Chrono, IDM…) puedan tomarla;
        // la página no ve el archivo, así que esta cookie le avisa que ya salió. Legible por JS y sin Secure: el sitio va por http.
        $aviso = (string) $request->query('aviso', '');
        if (preg_match('/^[a-z0-9]{8,40}$/i', $aviso)) {
            $respuesta->headers->setCookie(new \Symfony\Component\HttpFoundation\Cookie('informe_listo_' . $aviso, '1', time() + 120, '/', null, false, false, false, 'lax'));
        }

        return $respuesta;
    }

    /**
     * Consultas, cuentas y archivo del informe.
     */
    private function armarInforme(Request $request, $fechaInicio, $fechaFin, string $alcance, array $servicios, array $usuarios)
    {
        // Periodo por la hora real de llegada (created_at): fecha_creacion se reescribe en cada cambio del turno.
        // Sin modelos ni relaciones (ver COLUMNAS_CUENTAS): los nombres salen de $this->servicios, usuarios y cajas.
        $query = Turno::query()->toBase()
            ->select(self::COLUMNAS_CUENTAS)
            ->whereBetween('created_at', [$fechaInicio, $fechaFin]);

        if ($alcance === 'asesores') {
            $query->whereIn('asesor_id', $usuarios);
        } elseif ($alcance === 'servicios') {
            $query->whereIn('servicio_id', $servicios);
        }

        $turnos = $query->orderBy('created_at', 'desc')->orderBy('id', 'desc')->get();
        $this->servicios = Servicio::all()->keyBy('id');
        $this->usuarios = User::all()->keyBy('id');
        $this->cajas = Caja::pluck('nombre', 'id');

        // Generar estadísticas
        $estadisticas = $this->generarEstadisticas($turnos, $fechaInicio, $fechaFin, $alcance, $usuarios);

        // Lo que el archivo debe decir de sí mismo: unidad, alcance y quién lo generó.
        $contexto = [
            'unidad' => config('panel.unidad_nombre', 'Turnero'),
            'alcance' => $this->textoAlcance($alcance, $servicios, $usuarios),
            'alcance_clave' => $alcance,
            'generado_por' => Auth::user()?->nombre_completo ?: (Auth::user()?->nombre_usuario ?? ''),
        ];

        return $request->formato === 'excel'
            ? $this->exportarExcel($turnos, $estadisticas, $fechaInicio, $fechaFin, $contexto)
            : $this->exportarPDF($turnos, $estadisticas, $fechaInicio, $fechaFin, $contexto);
    }

    /**
     * "Todo el turnero", "Servicios: A, B y 3 más" o "Asesores: …" para el encabezado del archivo.
     */
    private function textoAlcance(string $alcance, array $servicios, array $usuarios): string
    {
        $lista = function ($nombres) {
            $nombres = array_values(array_filter($nombres));
            return count($nombres) > 4 ? implode(', ', array_slice($nombres, 0, 4)) . ' y ' . (count($nombres) - 4) . ' más' : implode(', ', $nombres);
        };

        if ($alcance === 'servicios') {
            // Se nombran las secciones completas y los subservicios sueltos (no cada hijo de una sección elegida).
            $elegidos = Servicio::whereIn('id', $servicios)->get(['id', 'nombre', 'servicio_padre_id']);
            $nombres = $elegidos->filter(fn ($s) => !$s->servicio_padre_id || !$elegidos->contains('id', $s->servicio_padre_id))->pluck('nombre')->all();
            return 'Servicios: ' . $lista($nombres);
        }
        if ($alcance === 'asesores') {
            return 'Asesores: ' . $lista(User::whereIn('id', $usuarios)->orderBy('nombre_completo')->pluck('nombre_completo')->all());
        }

        return 'Todo el turnero';
    }

    /**
     * informe_turnos_<unidad>_<alcance>_<desde>_<hasta>.<ext>: se distinguen los archivos de cada turnero y alcance.
     */
    private function nombreArchivo(array $contexto, $fechaInicio, $fechaFin, string $extension): string
    {
        $unidad = \Illuminate\Support\Str::slug($contexto['unidad'] ?? 'turnero', '_');
        $alcance = ['servicios' => 'por_servicio', 'asesores' => 'por_asesor'][$contexto['alcance_clave'] ?? ''] ?? 'general';

        return 'informe_turnos_' . $unidad . '_' . $alcance . '_' . $fechaInicio->format('Y-m-d') . '_' . $fechaFin->format('Y-m-d') . '.' . $extension;
    }

    /**
     * Generar estadísticas del reporte
     */
    private function generarEstadisticas($turnos, $fechaInicio, $fechaFin, string $alcance = 'todo', array $usuarios = [])
    {
        // Las reglas de tiempo (duración real, atenciones que quedaron abiertas, tiempo entre turnos) están en MetricasTurnos.
        // Transferidos = SALIENTES ("Transferido a ..."). Los "Transferido desde ..." son el ticket de continuación creado en
        // el servicio destino, no una transferencia adicional.
        $esTransferido = fn ($turno) => $turno->observaciones && str_starts_with($turno->observaciones, 'Transferido a ');
        $conteos = fn ($grupo) => [
            'total' => $grupo->count(),
            'atendidos' => $grupo->where('estado', 'atendido')->count(),
            'en_espera' => $grupo->where('estado', 'pendiente')->count(),
            'aplazados' => $grupo->where('estado', 'aplazado')->count(),
            'pendientes' => $grupo->whereIn('estado', ['pendiente', 'aplazado'])->count(),
            // Llamados que nadie marcó como atendidos ni aplazados (en un periodo pasado, turnos que quedaron sin cerrar).
            'sin_cerrar' => $grupo->where('estado', 'llamado')->count(),
            'cancelados' => $grupo->where('estado', 'cancelado')->count(),
            'transferidos' => $grupo->filter($esTransferido)->count(),
        ];

        // Por servicio (por id: dos servicios pueden llamarse igual)
        $porServicio = $turnos->groupBy('servicio_id')->map(function ($grupo, $servicioId) use ($conteos) {
            $atencion = MetricasTurnos::atencion($grupo);

            return ['nombre' => $this->servicios[$servicioId]->nombre ?? 'Servicio eliminado'] + $conteos($grupo) + [
                'tiempo_promedio' => $atencion['promedio'],
                'tiempo_total' => $atencion['total'],
                'atipicas' => $atencion['atipicas'],
            ];
        })->sortByDesc('total');
        $porServicio = $porServicio->keyBy(function ($datos, $servicioId) use ($porServicio) {
            return $porServicio->where('nombre', $datos['nombre'])->count() > 1 ? $datos['nombre'] . ' (#' . $servicioId . ')' : $datos['nombre'];
        });

        // Por asesor
        $asesorIds = $turnos->pluck('asesor_id')->filter()->unique()->values();
        // Con alcance por servicios, $turnos trae solo esos servicios: el tiempo entre turnos se mide con TODAS las atenciones
        // del asesor en el periodo; si no, lo que atendió de otros servicios contaría como tiempo muerto.
        $atencionesDelAsesor = $alcance === 'servicios'
            ? Turno::query()->toBase()->whereIn('asesor_id', $asesorIds)->where('estado', 'atendido')->whereBetween('created_at', [$fechaInicio, $fechaFin])
                ->get(['asesor_id', 'estado', 'fecha_llamado', 'fecha_atencion', 'duracion_atencion'])->groupBy('asesor_id')
            : null;
        // Con alcance por asesores, los aplazamientos se buscan en todos los turnos del periodo: el turno aplazado casi
        // siempre lo termina atendiendo otro asesor, así que no está entre los turnos de los elegidos.
        $aplazamientos = $this->aplazamientosPorAsesor($alcance === 'asesores'
            ? Turno::whereBetween('created_at', [$fechaInicio, $fechaFin])->pluck('id')
            : $turnos->pluck('id'));
        $filaAsesor = function ($grupo, $asesorId) use ($conteos, $aplazamientos, $atencionesDelAsesor, $fechaInicio, $fechaFin) {
            $asesor = $this->usuarios[$asesorId] ?? null;
            $atencion = MetricasTurnos::atencion($grupo);
            $canales = \App\Models\CanalNoPresencialHistorial::where('user_id', $asesorId)
                ->whereBetween('inicio', [$fechaInicio, $fechaFin])->get(['inicio', 'fin']);
            $entre = MetricasTurnos::entreTurnos($atencionesDelAsesor ? $atencionesDelAsesor->get($asesorId, collect()) : $grupo, $canales);

            return [
                'nombre_completo' => $asesor->nombre_completo ?? ('Usuario eliminado #' . $asesorId),
                'nombre_usuario' => $asesor->nombre_usuario ?? ('usuario' . $asesorId),
                // Veces que aplazó un turno (el estado actual del turno no lo dice: ver aplazamientosPorAsesor)
                'aplazados' => $aplazamientos[$asesorId] ?? 0,
            ] + $conteos($grupo) + [
                'tiempo_promedio_atencion' => $atencion['promedio'],
                'tiempo_total_atencion' => $atencion['total'],
                'atipicas' => $atencion['atipicas'],
                'entre_turnos_promedio' => $entre['promedio'],
                'entre_turnos_total' => $entre['total'],
                'entre_turnos_intervalos' => $entre['intervalos'],
                'pausas_largas' => $entre['pausas'],
                'pausas_largas_total' => $entre['pausas_total'],
                // Turnos atendidos, en el orden de su hoja en el Excel; la hoja los lee completos por bloques al escribirse.
                'ids_atendidos' => $grupo->where('estado', 'atendido')->sortBy('fecha_atencion')->pluck('id')->all(),
            ];
        };
        $porAsesor = $turnos->whereNotNull('asesor_id')->groupBy('asesor_id')->map($filaAsesor);
        // Quien en el periodo solo trabajó en canales no presenciales (sin turnos) también va en la tabla.
        if ($alcance !== 'servicios') {
            \App\Models\CanalNoPresencialHistorial::whereBetween('inicio', [$fechaInicio, $fechaFin])
                ->when($alcance === 'asesores', fn ($q) => $q->whereIn('user_id', $usuarios))
                ->whereNotIn('user_id', $porAsesor->keys())
                ->distinct()->pluck('user_id')
                ->each(function ($asesorId) use (&$porAsesor, $filaAsesor) {
                    $porAsesor[$asesorId] = $filaAsesor(collect(), $asesorId);
                });
        }
        $porAsesor = $porAsesor->sortByDesc('atendidos');

        // Estadísticas por día
        $porDia = $turnos->groupBy(function ($turno) {
            return Carbon::parse($turno->created_at)->format('Y-m-d');
        })->map(function ($grupo) {
            return [
                'total' => $grupo->count(),
                'atendidos' => $grupo->where('estado', 'atendido')->count(),
                'pendientes' => $grupo->whereIn('estado', ['pendiente', 'aplazado'])->count()
            ];
        });

        $todos = $conteos($turnos);
        $atencion = MetricasTurnos::atencion($turnos);
        $intervalos = $porAsesor->sum('entre_turnos_intervalos');

        return [
            'resumen' => [
                'total_turnos' => $todos['total'],
                'turnos_atendidos' => $todos['atendidos'],
                'turnos_en_espera' => $todos['en_espera'],
                'turnos_aplazados' => $todos['aplazados'],
                'turnos_pendientes' => $todos['pendientes'],
                'turnos_sin_cerrar' => $todos['sin_cerrar'],
                'turnos_cancelados' => $todos['cancelados'],
                'turnos_transferidos' => $todos['transferidos'],
                'porcentaje_atencion' => $todos['total'] > 0 ? round(($todos['atendidos'] / $todos['total']) * 100, 2) : 0,
                'tiempo_promedio_atencion' => $atencion['promedio'],
                'tiempo_total_atencion' => $atencion['total'],
                'atenciones_atipicas' => $atencion['atipicas'],
                'atenciones_sin_dato' => $atencion['sin_dato'],
                'entre_turnos_promedio' => $intervalos ? $porAsesor->sum('entre_turnos_total') / $intervalos : null,
            ],
            'por_servicio' => $porServicio,
            'por_asesor' => $porAsesor,
            'por_dia' => $porDia
        ];
    }

    /**
     * Exportar a Excel
     */
    private function exportarExcel($turnos, $estadisticas, $fechaInicio, $fechaFin, array $contexto = [])
    {
        // Fila por fila a un archivo temporal (LibroExcel): con PhpSpreadsheet, un año de turnos no cabía en memoria.
        $libro = new LibroExcel();

        // Hoja 1: Resumen
        $this->crearHojaResumen($libro, $estadisticas, $fechaInicio, $fechaFin, $contexto);

        // Hoja 2: Detalle de turnos
        $this->crearHojaDetalle($libro, $turnos->pluck('id')->all());

        // Hoja 3: Estadísticas por servicio
        $this->crearHojaServicios($libro, $estadisticas['por_servicio']);

        // Hoja 4: Estadísticas por asesor
        $this->crearHojaAsesores($libro, $estadisticas['por_asesor']);

        // Hojas 5+: Detalle de turnos por cada asesor
        foreach ($estadisticas['por_asesor'] as $datosAsesor) {
            $this->crearHojaDetalleTurnosAsesor($libro, $datosAsesor);
        }

        $ruta = $this->archivoTemporal('xlsx');
        try {
            $libro->guardar($ruta);
        } catch (\Throwable $e) {
            @unlink($ruta);
            throw $e;
        }

        // El archivo ya está completo antes de enviar nada: si algo falla, la página recibe un error y no un Excel a medias.
        // Se envía de una pieza y sin aceptar rangos (Accept-Ranges: none): cada petición a esta URL arma un informe nuevo,
        // así que un gestor de descargas que la pidiera por partes (IDM abre varias conexiones) juntaría trozos de archivos
        // distintos, y de paso armaría el informe varias veces a la vez.
        return response()->streamDownload(function () use ($ruta) {
            try {
                readfile($ruta);
            } finally {
                @unlink($ruta);
            }
        }, $this->nombreArchivo($contexto, $fechaInicio, $fechaFin, 'xlsx'), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Length' => (string) filesize($ruta),
            'Accept-Ranges' => 'none',
        ]);
    }

    /**
     * Exportar a PDF
     */
    private function exportarPDF($turnos, $estadisticas, $fechaInicio, $fechaFin, array $contexto = [])
    {
        // El PDF solo muestra los 50 más recientes: esos sí se cargan completos, con sus relaciones.
        $orden = array_flip($turnos->take(50)->pluck('id')->all());
        $recientes = Turno::with(['servicio', 'asesor', 'caja'])->whereIn('id', array_keys($orden))->get()
            ->sortBy(fn ($turno) => $orden[$turno->id])->values();

        $data = [
            'contexto' => $contexto,
            'turnos' => $recientes,
            'total_turnos' => $turnos->count(),
            'estadisticas' => $estadisticas,
            'notas' => $this->notasTiempos(),
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'fecha_generacion' => Carbon::now()
        ];

        $pdf = Pdf::loadView('admin.reportes.pdf', $data);
        $pdf->setPaper('A4', 'portrait');

        $filename = $this->nombreArchivo($contexto, $fechaInicio, $fechaFin, 'pdf');

        return $pdf->download($filename);
    }

    /**
     * Exportar dashboard histórico
     */
    public function exportarDashboardHistorico(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'servicio_id' => 'nullable|integer',
            'formato' => 'required|in:excel,pdf'
        ]);

        $fechaInicio = Carbon::parse($request->fecha_inicio)->startOfDay();
        $fechaFin = Carbon::parse($request->fecha_fin)->endOfDay();
        $servicioId = $request->servicio_id;

        // Obtener datos para el dashboard
        $dashboardData = $this->obtenerDatosDashboardHistorico($fechaInicio, $fechaFin, $servicioId);

        if ($request->formato === 'excel') {
            return $this->exportarDashboardExcel($dashboardData, $fechaInicio, $fechaFin);
        } else {
            return $this->exportarDashboardPDF($dashboardData, $fechaInicio, $fechaFin);
        }
    }

    /**
     * Obtener datos para el dashboard histórico
     */
    private function obtenerDatosDashboardHistorico($fechaInicio, $fechaFin, $servicioId = null)
    {
        // Usar TurnoHistorial para datos históricos, como lo hace GraficosController
        // Filtrar solo los registros de 'creacion' para evitar duplicados
        $query = TurnoHistorial::with(['servicio', 'asesor', 'caja'])
            ->whereBetween('fecha_creacion', [$fechaInicio, $fechaFin])
            ->where('tipo_backup', 'creacion');

        if ($servicioId) {
            $query->where('servicio_id', $servicioId);
        }

        $turnos = $query->get();

        // Debug logging
        \Log::info('Dashboard Export Debug', [
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'servicio_id' => $servicioId,
            'total_turnos_query' => $turnos->count(),
            'sample_turnos' => $turnos->take(3)->toArray()
        ]);

        // Estadísticas generales
        $estadisticasGenerales = [
            'total_turnos' => $turnos->count(),
            'turnos_atendidos' => $turnos->where('estado', 'atendido')->count(),
            'turnos_pendientes' => $turnos->where('estado', 'pendiente')->count(),
            'turnos_llamados' => $turnos->where('estado', 'llamado')->count(),
            'turnos_aplazados' => $turnos->where('estado', 'aplazado')->count(),
            'turnos_cancelados' => $turnos->where('estado', 'cancelado')->count(),
            'tiempo_promedio_atencion' => $this->calcularTiempoPromedioAtencion($turnos),
            'servicios_activos' => $turnos->pluck('servicio_id')->unique()->count(),
            'asesores_activos' => $turnos->pluck('asesor_id')->unique()->count()
        ];

        // Distribución por servicios
        $distribucionServicios = $turnos->filter(function($turno) {
                return $turno->servicio;
            })
            ->groupBy(function($turno) {
                return $turno->servicio->nombre;
            })
            ->map(function ($turnosServicio) {
                return $turnosServicio->count();
            })->toArray();

        // Distribución por estados
        $distribucionEstados = $turnos->groupBy('estado')
            ->map(function ($turnosEstado) {
                return $turnosEstado->count();
            })->toArray();

        // Top asesores
        $topAsesores = $turnos->where('estado', 'atendido')
            ->filter(function($turno) {
                return $turno->asesor;
            })
            ->groupBy(function($turno) {
                return $turno->asesor->nombre_completo ?? $turno->asesor->nombre_usuario ?? 'Sin nombre';
            })
            ->map(function ($turnosAsesor) {
                return $turnosAsesor->count();
            })
            ->sortDesc()
            ->take(10)
            ->toArray();

        // Análisis por horas
        $analisisHoras = $turnos->groupBy(function ($turno) {
            return Carbon::parse($turno->fecha_creacion)->format('H');
        })->map(function ($turnosHora) {
            return $turnosHora->count();
        })->toArray();

        return [
            'estadisticas_generales' => $estadisticasGenerales,
            'distribucion_servicios' => $distribucionServicios,
            'distribucion_estados' => $distribucionEstados,
            'top_asesores' => $topAsesores,
            'analisis_horas' => $analisisHoras,
            'turnos_detalle' => $turnos->take(100) // Limitar para el reporte
        ];
    }

    /**
     * Exportar dashboard a Excel
     */
    private function exportarDashboardExcel($dashboardData, $fechaInicio, $fechaFin)
    {
        $spreadsheet = new Spreadsheet();

        // Hoja 1: Resumen Dashboard
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Dashboard Histórico');

        // Encabezado
        $sheet->setCellValue('A1', 'DASHBOARD HISTÓRICO - SISTEMA DE TURNOS');
        $sheet->setCellValue('A2', 'Hospital Universitario del Valle');
        $sheet->setCellValue('A3', 'Período: ' . $fechaInicio->format('d/m/Y') . ' - ' . $fechaFin->format('d/m/Y'));
        $sheet->setCellValue('A4', 'Generado: ' . Carbon::now()->format('d/m/Y H:i:s'));

        // Estadísticas generales
        $row = 6;
        $sheet->setCellValue('A' . $row, 'ESTADÍSTICAS GENERALES');
        $row++;

        foreach ($dashboardData['estadisticas_generales'] as $key => $value) {
            $label = ucwords(str_replace('_', ' ', $key));
            $sheet->setCellValue('A' . $row, $label);
            $sheet->setCellValue('B' . $row, $value);
            $row++;
        }

        // Distribución por servicios
        $row += 2;
        $sheet->setCellValue('A' . $row, 'DISTRIBUCIÓN POR SERVICIOS');
        $row++;
        $sheet->setCellValue('A' . $row, 'Servicio');
        $sheet->setCellValue('B' . $row, 'Cantidad');
        $row++;

        foreach ($dashboardData['distribucion_servicios'] as $servicio => $cantidad) {
            $sheet->setCellValue('A' . $row, $servicio);
            $sheet->setCellValue('B' . $row, $cantidad);
            $row++;
        }

        // Distribución por estados
        $row += 2;
        $sheet->setCellValue('A' . $row, 'DISTRIBUCIÓN POR ESTADOS');
        $row++;
        $sheet->setCellValue('A' . $row, 'Estado');
        $sheet->setCellValue('B' . $row, 'Cantidad');
        $row++;

        foreach ($dashboardData['distribucion_estados'] as $estado => $cantidad) {
            $sheet->setCellValue('A' . $row, ucfirst($estado));
            $sheet->setCellValue('B' . $row, $cantidad);
            $row++;
        }

        // TOP ASESORES - Esta es la sección principal que faltaba
        $row += 2;
        $sheet->setCellValue('A' . $row, 'TOP ASESORES (CASOS ATENDIDOS)');
        $row++;
        $sheet->setCellValue('A' . $row, 'Asesor');
        $sheet->setCellValue('B' . $row, 'Casos Atendidos');
        $row++;

        foreach ($dashboardData['top_asesores'] as $asesor => $cantidad) {
            $sheet->setCellValue('A' . $row, $asesor);
            $sheet->setCellValue('B' . $row, $cantidad);
            $row++;
        }

        // Análisis por horas
        $row += 2;
        $sheet->setCellValue('A' . $row, 'ANÁLISIS POR HORAS');
        $row++;
        $sheet->setCellValue('A' . $row, 'Hora');
        $sheet->setCellValue('B' . $row, 'Cantidad de Turnos');
        $row++;

        foreach ($dashboardData['analisis_horas'] as $hora => $cantidad) {
            $sheet->setCellValue('A' . $row, $hora . ':00');
            $sheet->setCellValue('B' . $row, $cantidad);
            $row++;
        }

        // Aplicar estilos
        $this->aplicarEstilosDashboard($sheet);

        $filename = 'dashboard_historico_' . $fechaInicio->format('Y-m-d') . '_' . $fechaFin->format('Y-m-d') . '.xlsx';

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Exportar dashboard a PDF
     */
    private function exportarDashboardPDF($dashboardData, $fechaInicio, $fechaFin)
    {
        $data = [
            'dashboard_data' => $dashboardData,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'fecha_generacion' => Carbon::now()
        ];

        $pdf = Pdf::loadView('admin.reportes.dashboard-pdf', $data);
        $pdf->setPaper('A4', 'portrait');

        $filename = 'dashboard_historico_' . $fechaInicio->format('Y-m-d') . '_' . $fechaFin->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Calcular tiempo promedio de atención
     */
    private function calcularTiempoPromedioAtencion($turnos)
    {
        $turnosAtendidos = $turnos->where('estado', 'atendido')
            ->whereNotNull('duracion_atencion');

        if ($turnosAtendidos->count() === 0) {
            return 0;
        }

        // TurnoHistorial ya tiene duracion_atencion en segundos, convertir a minutos
        $tiempoPromedio = $turnosAtendidos->avg('duracion_atencion');

        return round($tiempoPromedio / 60, 1); // Convertir segundos a minutos
    }

    /**
     * Aplicar estilos al dashboard de Excel
     */
    private function aplicarEstilosDashboard($sheet)
    {
        // Estilo para el título
        $sheet->getStyle('A1:B1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1:B1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('064b9e');
        $sheet->getStyle('A1:B1')->getFont()->getColor()->setRGB('FFFFFF');

        // Autoajustar columnas
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);

        // Obtener la última fila con datos para aplicar bordes dinámicamente
        $lastRow = $sheet->getHighestRow();

        // Bordes para las celdas con datos
        $sheet->getStyle('A1:B' . $lastRow)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        // Aplicar estilo de encabezado a las secciones principales
        $this->aplicarEstilosSeccionesDashboard($sheet);
    }

    /**
     * Aplicar estilos específicos a las secciones del dashboard
     */
    private function aplicarEstilosSeccionesDashboard($sheet)
    {
        $lastRow = $sheet->getHighestRow();

        // Buscar y aplicar estilos a los encabezados de sección
        for ($row = 1; $row <= $lastRow; $row++) {
            $cellValue = $sheet->getCell('A' . $row)->getValue();

            // Si la celda contiene un encabezado de sección (texto en mayúsculas)
            if (is_string($cellValue) &&
                (strpos($cellValue, 'ESTADÍSTICAS') !== false ||
                 strpos($cellValue, 'DISTRIBUCIÓN') !== false ||
                 strpos($cellValue, 'TOP ASESORES') !== false ||
                 strpos($cellValue, 'ANÁLISIS') !== false)) {

                // Aplicar estilo de encabezado de sección
                $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E8F4FD']
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                        ],
                    ],
                ]);
            }

            // Aplicar estilo a los encabezados de columnas (Asesor, Casos Atendidos, etc.)
            if (is_string($cellValue) &&
                (strpos($cellValue, 'Asesor') !== false ||
                 strpos($cellValue, 'Servicio') !== false ||
                 strpos($cellValue, 'Estado') !== false ||
                 strpos($cellValue, 'Hora') !== false ||
                 strpos($cellValue, 'Cantidad') !== false ||
                 strpos($cellValue, 'Casos Atendidos') !== false)) {

                // Aplicar estilo de encabezado de columna
                $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F0F8FF']
                    ]
                ]);
            }
        }
    }

    /**
     * Crear hoja de resumen en Excel
     */
    private function crearHojaResumen(LibroExcel $libro, $estadisticas, $fechaInicio, $fechaFin, array $contexto = [])
    {
        $libro->hoja('Resumen')->ancho(1, 30)->ancho(2, 25)->ancho(3, 20)->ancho(4, 20);

        // Encabezado principal con el color institucional del Hospital Universitario del Valle
        $libro->fila(['INFORME DE TURNOS - ' . mb_strtoupper($contexto['unidad'] ?? '', 'UTF-8') . ' - HOSPITAL UNIVERSITARIO DEL VALLE', null, null, null], LibroExcel::TITULO, 35)
            ->combinar('A1:D1');
        $libro->fila([]);

        $libro->fila([['Período:', LibroExcel::NEGRITA], [$fechaInicio->format('d/m/Y') . ' - ' . $fechaFin->format('d/m/Y'), LibroExcel::AZUL]]);
        $libro->fila([['Fecha de generación:', LibroExcel::NEGRITA], Carbon::now()->format('d/m/Y H:i:s') . (!empty($contexto['generado_por']) ? ' · ' . $contexto['generado_por'] : '')]);
        // Alcance: qué turnos entran en este informe
        $libro->fila([['Alcance:', LibroExcel::NEGRITA], $contexto['alcance'] ?? 'Todo el turnero']);

        $libro->fila(['RESUMEN GENERAL', null], LibroExcel::SECCION, 25)->combinar('A6:B6');
        $libro->fila(['Concepto', 'Valor'], LibroExcel::ENCABEZADO);

        $resumen = $estadisticas['resumen'];
        $datos = [
            ['Total de Turnos', $resumen['total_turnos']],
            ['Turnos Atendidos', $resumen['turnos_atendidos']],
            ['Turnos en Espera (sin llamar)', $resumen['turnos_en_espera']],
            ['Turnos Aplazados', $resumen['turnos_aplazados']],
            ['Turnos Llamados sin Cerrar', $resumen['turnos_sin_cerrar']],
            ['Turnos Cancelados', $resumen['turnos_cancelados']],
            ['Turnos Transferidos', $resumen['turnos_transferidos'] ?? 0],
            ['Porcentaje de Atención', $resumen['porcentaje_atencion'] . '%'],
            ['Tiempo Promedio de Atención', MetricasTurnos::texto($resumen['tiempo_promedio_atencion'])],
            ['Tiempo Promedio entre Turnos', MetricasTurnos::texto($resumen['entre_turnos_promedio'])],
            ['Atenciones de más de 1 hora (no se promedian)', $resumen['atenciones_atipicas']],
        ];
        foreach ($datos as $index => $dato) {
            // Filas alternas; la primera (Total de Turnos) resaltada
            $libro->fila($dato, $index === 0 ? LibroExcel::CLARO_BORDE_NEGRITA : ($index % 2 === 0 ? LibroExcel::CLARO_BORDE : LibroExcel::BLANCO_BORDE));
        }

        // Cómo se miden los tiempos: sin esto, quien lee el informe no puede saber qué entra en cada promedio.
        $libro->fila([]);
        foreach ($this->notasTiempos() as $nota) {
            $libro->fila([$nota], LibroExcel::NOTA, 38, false);
            $libro->combinar('A' . $libro->filas() . ':D' . $libro->filas());
        }
    }

    /**
     * Crear hoja de detalle en Excel. Los turnos se leen completos por bloques, en el orden de $ids.
     */
    private function crearHojaDetalle(LibroExcel $libro, array $ids)
    {
        $encabezados = ['Código', 'Número', 'Cédula', 'Servicio', 'Asesor', 'Caja', 'Estado', 'Prioridad', 'Fecha Creación',
            'Hora Llamado', 'Hora Finalización', 'Duración de la Atención', 'Observaciones/Transferencia'];
        $abrirHoja = function (int $parte) use ($libro, $encabezados) {
            // Excel admite 1.048.576 filas por hoja: lo que no cabe sigue en «Detalle de Turnos (2)», …
            $libro->hoja($parte === 1 ? 'Detalle de Turnos' : 'Detalle de Turnos (' . $parte . ')')->fijarEncabezado();
            $libro->fila($encabezados, LibroExcel::ENCABEZADO);
        };
        $parte = 1;
        $abrirHoja($parte);

        foreach ($this->turnosEnBloques($ids) as $turno) {
            if ($libro->filas() >= LibroExcel::MAX_FILAS) {
                $abrirHoja(++$parte);
            }
            // Solo los atendidos tienen una atención que medir (en un aplazado, duracion_atencion es lo que alcanzó a durar el llamado).
            $segundos = $turno->estado === 'atendido' ? MetricasTurnos::duracion($turno) : null;
            $asesor = $this->usuarios[$turno->asesor_id] ?? null;

            $libro->fila([
                $turno->codigo,
                is_numeric($turno->numero) ? (int) $turno->numero : $turno->numero,
                $this->textoCedula($turno->cedula ?? null),
                $this->servicios[$turno->servicio_id]->nombre ?? 'N/A',
                $asesor->nombre_completo ?? ($asesor->nombre_usuario ?? 'N/A'),
                $this->cajas[$turno->caja_id] ?? 'N/A',
                strtoupper($turno->estado),
                $turno->prioridad >= 4 ? 'Prioritario' : 'Normal',
                $this->fechaHora($turno->created_at),
                $this->fechaHora($turno->fecha_llamado),
                $this->fechaHora($turno->fecha_finalizacion),
                MetricasTurnos::texto($segundos) . (MetricasTurnos::esAtipica($segundos) ? ' (quedó abierto, no se promedia)' : ''),
                $turno->observaciones ?? '-',
            ], LibroExcel::BORDE);
        }
    }

    /**
     * Los turnos completos (COLUMNAS_DETALLE), por bloques y en el orden de $ids: nunca hay más de un bloque en memoria.
     */
    private function turnosEnBloques(array $ids): \Generator
    {
        foreach (array_chunk($ids, self::TURNOS_POR_BLOQUE) as $bloque) {
            $filas = Turno::query()->toBase()->whereIn('id', $bloque)->get($this->columnasDetalle())->keyBy('id');
            foreach ($bloque as $id) {
                if ($fila = $filas->get($id)) {
                    yield $fila;
                }
            }
        }
    }

    /**
     * turnos.cedula llega con la migración del atril (2026_10_29_090000): sin ella, el informe sale igual, con «-».
     */
    private function columnasDetalle(): array
    {
        return $this->columnasDetalle ??= Schema::hasColumn('turnos', 'cedula')
            ? self::COLUMNAS_DETALLE
            : array_values(array_diff(self::COLUMNAS_DETALLE, ['cedula']));
    }

    /**
     * La cédula que la persona escribió en el atril (el Excel la guarda como texto: como número la mostraría en notación
     * científica o le quitaría los ceros de la izquierda). «-» si no la escribió (servicio que no la pide o turno anterior).
     */
    private function textoCedula(?string $cedula): string
    {
        $cedula = trim((string) $cedula);

        return $cedula !== '' ? $cedula : '-';
    }

    /** 'Y-m-d H:i:s' de la base → 'd/m/Y H:i:s'; 'N/A' si no hay. Sin Carbon: son cientos de miles de fechas. */
    private function fechaHora($valor): string
    {
        if ($valor === null || $valor === '') {
            return 'N/A';
        }
        $valor = (string) $valor;
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}:\d{2}:\d{2})/', $valor, $m)) {
            return $m[3] . '/' . $m[2] . '/' . $m[1] . ' ' . $m[4];
        }

        return Carbon::parse($valor)->format('d/m/Y H:i:s');
    }

    /**
     * Crear hoja de estadísticas por servicio
     */
    private function crearHojaServicios(LibroExcel $libro, $porServicio)
    {
        $libro->hoja('Por Servicio')->fijarEncabezado();
        $libro->fila(['Servicio', 'Total', 'Atendidos', 'Pendientes (en espera + aplazados)', 'Llamados sin Cerrar', 'Cancelados',
            'Transferidos', 'Tiempo Promedio de Atención', 'Atenciones de más de 1 h'], LibroExcel::ENCABEZADO);

        foreach ($porServicio as $servicio => $datos) {
            $libro->fila([
                (string) $servicio,
                $datos['total'],
                $datos['atendidos'],
                $datos['pendientes'],
                $datos['sin_cerrar'],
                $datos['cancelados'],
                $datos['transferidos'] ?? 0,
                MetricasTurnos::texto($datos['tiempo_promedio']),
                $datos['atipicas'],
            ], LibroExcel::BORDE);
        }
    }

    /**
     * Crear hoja de estadísticas por asesor
     */
    private function crearHojaAsesores(LibroExcel $libro, $porAsesor)
    {
        $libro->hoja('Por Asesor')->fijarEncabezado();
        $libro->fila(['Asesor', 'Usuario', 'Total Turnos', 'Atendidos', 'Veces que Aplazó un Turno', 'Llamados sin Cerrar', 'Transferidos',
            'Tiempo Promedio de Atención', 'Tiempo Total de Atención', 'Atenciones de más de 1 h (no se promedian)',
            'Tiempo Promedio entre Turnos', 'Tiempo Total entre Turnos', 'Pausas de más de 1 h', 'Tiempo en Pausas de más de 1 h'],
            LibroExcel::ENCABEZADO, 20);

        foreach ($porAsesor as $datos) {
            // Filas alternas: la primera en azul claro
            $libro->fila([
                (string) $datos['nombre_completo'],
                (string) $datos['nombre_usuario'],
                $datos['total'],
                $datos['atendidos'],
                $datos['aplazados'],
                $datos['sin_cerrar'],
                $datos['transferidos'] ?? 0,
                MetricasTurnos::texto($datos['tiempo_promedio_atencion']),
                MetricasTurnos::texto($datos['tiempo_total_atencion']),
                $datos['atipicas'],
                MetricasTurnos::texto($datos['entre_turnos_promedio']),
                MetricasTurnos::texto($datos['entre_turnos_total']),
                $datos['pausas_largas'],
                MetricasTurnos::texto($datos['pausas_largas_total']),
            ], $libro->filas() % 2 === 1 ? LibroExcel::CLARO_BORDE : LibroExcel::BLANCO_BORDE);
        }

        // Cómo se miden los tiempos (celdas combinadas: no ensanchan la columna A)
        $libro->fila([]);
        foreach ($this->notasTiempos() as $nota) {
            $libro->fila([$nota], LibroExcel::NOTA_SIMPLE, null, false);
            $libro->combinar('A' . $libro->filas() . ':N' . $libro->filas());
        }
    }

    /**
     * Crear hoja de detalle de turnos por asesor
     */
    private function crearHojaDetalleTurnosAsesor(LibroExcel $libro, $datosAsesor)
    {
        $libro->hoja('Turnos_' . $datosAsesor['nombre_usuario'])->fijarEncabezado();
        // Sin Hora Atención porque es redundante con Hora Finalización
        $libro->fila(['Código', 'Cédula', 'Servicio', 'Hora Llamado', 'Hora Finalización', 'Duración de la Atención', 'Caja', 'Observaciones'],
            LibroExcel::ENCABEZADO);

        foreach ($this->turnosEnBloques($datosAsesor['ids_atendidos'] ?? []) as $turno) {
            $segundos = MetricasTurnos::duracion($turno);

            $libro->fila([
                $turno->codigo . '-' . str_pad((string) $turno->numero, 3, '0', STR_PAD_LEFT),
                $this->textoCedula($turno->cedula ?? null),
                $this->servicios[$turno->servicio_id]->nombre ?? 'N/A',
                $this->fechaHora($turno->fecha_llamado),
                $this->fechaHora($turno->fecha_finalizacion),
                MetricasTurnos::texto($segundos) . (MetricasTurnos::esAtipica($segundos) ? ' (quedó abierto, no se promedia)' : ''),
                $this->cajas[$turno->caja_id] ?? 'N/A',
                $turno->observaciones ?? '-',
            ]);
        }
    }

    /**
     * Archivo temporal para un informe. Los que quedaron de envíos cortados (más de una hora) se borran aquí.
     */
    private function archivoTemporal(string $extension): string
    {
        $carpeta = storage_path('app/informes');
        if (!is_dir($carpeta) && !@mkdir($carpeta, 0775, true) && !is_dir($carpeta)) {
            throw new \RuntimeException('No se pudo crear la carpeta de informes en storage/app.');
        }
        foreach (glob($carpeta . '/informe_*') ?: [] as $viejo) {
            if (@filemtime($viejo) < time() - 3600) {
                @unlink($viejo);
            }
        }

        return $carpeta . '/informe_' . bin2hex(random_bytes(8)) . '.' . $extension;
    }

    /**
     * Un informe de un año o más tarda y ocupa más que uno de un mes: más tiempo de ejecución y, si el servidor lo
     * permite, más memoria (con lo de arriba un año entero cabe de sobra en 512 MB; esto es por si acaso).
     */
    private function darMargenParaInformesLargos(): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $limite = trim((string) ini_get('memory_limit'));
        if ($limite === '' || $limite === '-1') {
            return;
        }
        $bytes = (int) $limite * (['g' => 1073741824, 'm' => 1048576, 'k' => 1024][strtolower(substr($limite, -1))] ?? 1);
        if ($bytes < 1073741824) {
            @ini_set('memory_limit', '1024M');
        }
    }

    /**
     * Cuántas veces aplazó cada asesor un turno de los del informe. Se cuenta en el historial porque en UBA aplazar borra
     * el asesor del turno, y en los dos el turno lo puede terminar atendiendo otro: el historial guarda quién lo tenía.
     */
    private function aplazamientosPorAsesor($idsTurnos): array
    {
        $porAsesor = [];
        foreach (collect($idsTurnos)->chunk(1000) as $ids) {
            TurnoHistorial::whereIn('turno_original_id', $ids->all())
                ->where('tipo_backup', 'actualizacion')
                ->where('estado', 'aplazado')
                ->get(['datos_adicionales'])
                ->each(function ($fila) use (&$porAsesor) {
                    $antes = $fila->datos_adicionales['valores_anteriores'] ?? [];
                    // Solo el paso de «llamado» a «aplazado»: otras actualizaciones de un turno ya aplazado no son otro aplazamiento.
                    if (($antes['estado'] ?? null) === 'llamado' && !empty($antes['asesor_id'])) {
                        $porAsesor[$antes['asesor_id']] = ($porAsesor[$antes['asesor_id']] ?? 0) + 1;
                    }
                });
        }

        return $porAsesor;
    }

    /**
     * Cómo se miden los tiempos del informe: va en el Excel y en el PDF.
     */
    private function notasTiempos(): array
    {
        return [
            'Duración de la atención: la del cronómetro del asesor. Si el cronómetro se reinició (la página se recargó) o el reloj del equipo cambió, se usa la hora del servidor (del llamado a «Atendido»).',
            'Atenciones de más de 1 hora: el turno quedó abierto. Cuentan como atendidas pero no entran en los promedios ni en los totales de tiempo.',
            'Tiempo entre turnos: desde que el asesor cierra un turno hasta que empieza a atender el siguiente, el mismo día. No cuenta noches ni fines de semana; los ratos de más de 1 hora (almuerzo, reuniones) se muestran aparte como pausas; el tiempo en canales no presenciales se descuenta. Incluye ratos en que no había pacientes esperando. En un informe por servicios se mide con todas las atenciones del asesor, de cualquier servicio.',
            'Transferencias: un paciente transferido cuenta como dos turnos, el del servicio que lo transfirió (atendido) y el del servicio que lo recibe.',
        ];
    }
}
