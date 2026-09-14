<?php

namespace App\Services;

use App\Models\Caja;
use App\Models\Servicio;
use App\Models\Turno;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Datos del nuevo Inicio del panel admin (GET /api/admin/tablero).
 *
 * Las definiciones son las del contrato del tablero y deben ser las mismas en toda la app:
 *  - "Hoy" = created_at dentro del día, en la zona horaria de la app. NUNCA fecha_creacion: en
 *    producción se reescribe en cada UPDATE (ON UPDATE current_timestamp()).
 *  - En espera = pendiente + aplazado de hoy. Prioritario = prioridad >= 4 (Turno::esPrioritario()).
 *
 * Rendimiento: la tabla turnos NO tiene índice en created_at, así que cada consulta sobre ella es un
 * recorrido completo de la tabla (decenas de miles de filas en producción). Por eso se lee UNA sola vez,
 * sin hidratar modelos salvo los pocos turnos vivos, y todo se agrega en PHP en una pasada. El total es
 * de 6 consultas como mucho: no crece con el número de módulos, servicios, asesores ni turnos.
 *
 * Módulo abierto = caja ACTIVA con asesor (una caja inactiva con asesor_activo_id es 'inhabilitado').
 *
 * Fin de una atención = fecha_atencion de un turno 'atendido': Turno::marcarComoAtendido() la pone en now() en
 * los dos turneros. NO se usa fecha_finalizacion: turnero-huv no tiene esa columna y en huvuba puede faltar en
 * producción (el propio modelo lo prevé con un try/catch).
 */
class TableroService
{
    /** Partículas que no cuentan como apellido al abreviar un nombre ("Asesor de Prueba" -> "Asesor P."). */
    private const PARTICULAS = ['de', 'del', 'la', 'las', 'los', 'y', 'da', 'das', 'do', 'dos', 'di', 'van', 'von'];

    /** Máximo de avisos por tipo (prioritario y atención larga). Los de sin cobertura no tienen tope. */
    private const MAX_AVISOS = 3;

    /**
     * Estructura completa en cero. La usa el Inicio si generar() falla, para mostrarse igual (sin error 500)
     * mientras el refresco vuelve a intentarlo.
     */
    public static function vacio(): array
    {
        $u = config('panel.umbrales', []);

        return [
            'generado' => now()->toIso8601String(),
            'unidad' => (string) config('panel.unidad', 'UBA'),
            'umbrales' => ['espera' => (int) ($u['espera'] ?? 30), 'prioritario' => (int) ($u['prioritario'] ?? 15), 'atencion' => (int) ($u['atencion'] ?? 20)],
            'resumen' => [
                'en_espera' => 0, 'prioritarios' => 0, 'aplazados' => 0, 'espera_max' => null,
                'modulos' => ['habilitados' => 0, 'abiertos' => 0, 'atendiendo' => 0, 'libres' => 0, 'descanso' => 0, 'canal' => 0],
                'ultima_hora' => ['llegaron' => 0, 'finalizaron' => 0, 'tendencia' => 'estable'],
                'atendidos_hoy' => 0, 'transferidos_hoy' => 0,
                'asesores' => ['conectados' => 0, 'atendiendo' => 0, 'libres' => 0, 'descanso' => 0, 'canal' => 0, 'sin_modulo' => 0],
            ],
            'servicios' => [], 'modulos' => [], 'asesores' => [], 'avisos' => [], 'por_asesor' => [],
        ];
    }

    public function generar(?CarbonInterface $ahora = null): array
    {
        $ahora = $ahora ? Carbon::instance($ahora) : Carbon::now();
        $umbrales = $this->umbrales();

        // Límites como texto 'Y-m-d H:i:s' en la zona de la app. created_at se escribe y se lee con ese
        // formato por la misma conexión, así que se compara tal cual, sin convertir fila por fila.
        // [hoy, mañana) equivale a whereDate('created_at', today()), pero sin envolver la columna en DATE().
        $hoy = $ahora->copy()->startOfDay()->format('Y-m-d H:i:s');
        $manana = $ahora->copy()->startOfDay()->addDay()->format('Y-m-d H:i:s');
        $haceUnaHora = $ahora->copy()->subHour()->format('Y-m-d H:i:s');

        // --- Consultas 1 y 2: catálogos pequeños --------------------------------------------------
        $servicios = Servicio::query()->get()->keyBy('id');
        $cajas = Caja::query()->orderBy('numero_caja')->get();

        // --- Consulta 3: turnos, una sola lectura ------------------------------------------------
        // De hoy, más los de ayer que caen en "la última hora" (pasada la medianoche) y los de días
        // anteriores que se finalizaron en la última hora (atenciones que se quedaron abiertas).
        $filas = Turno::query()->toBase()
            ->select([
                'id', 'codigo', 'numero', 'servicio_id', 'caja_id', 'asesor_id', 'estado', 'prioridad',
                'created_at', 'fecha_llamado', 'fecha_atencion', 'duracion_atencion',
            ])
            ->selectRaw("observaciones LIKE 'Transferido a%' AS transferido")
            ->where(function ($q) use ($hoy, $haceUnaHora) {
                $q->where('created_at', '>=', min($hoy, $haceUnaHora))
                  ->orWhere(fn ($q2) => $q2->where('estado', 'atendido')->where('fecha_atencion', '>=', $haceUnaHora));
            })
            ->get();

        $llegaron = 0;
        $finalizaron = 0;
        $atendidosHoy = 0;
        $transferidosHoy = 0;
        $porServicio = [];        // servicio_id => contadores
        $atendidosPorAsesor = []; // asesor_id => n
        $vivos = [];              // filas pendiente | aplazado | llamado de hoy

        foreach ($filas as $f) {
            if ($f->created_at !== null && $f->created_at >= $haceUnaHora) {
                $llegaron++;
            }
            if ($f->estado === 'atendido' && $f->fecha_atencion !== null && $f->fecha_atencion >= $haceUnaHora) {
                $finalizaron++;
            }
            if ($f->created_at === null || $f->created_at < $hoy || $f->created_at >= $manana) {
                continue; // no es de hoy
            }

            if ($f->transferido) {
                $transferidosHoy++;
            }

            if ($f->estado === 'atendido') {
                $porServicio[$f->servicio_id] ??= $this->contadoresVacios();
                $porServicio[$f->servicio_id]['atendidos']++;
                if ($f->duracion_atencion !== null) {
                    // abs(): igual que el historial del asesor y los reportes, que muestran |duración|.
                    $porServicio[$f->servicio_id]['duraciones'][] = abs((int) $f->duracion_atencion);
                }
                $atendidosHoy++;
                if ($f->asesor_id !== null) {
                    $atendidosPorAsesor[$f->asesor_id] = ($atendidosPorAsesor[$f->asesor_id] ?? 0) + 1;
                }
            } elseif (in_array($f->estado, ['pendiente', 'aplazado', 'llamado'], true)) {
                $vivos[] = $f;
            }
        }

        // Solo los turnos vivos (unas decenas) se convierten en modelos, para reutilizar
        // codigo_completo, esPrioritario() y las fechas ya convertidas a Carbon.
        $vivos = Turno::hydrate($vivos);

        $enEspera = 0;
        $prioritariosEnEspera = 0;
        $aplazados = 0;
        $masAntiguo = null;       // pendiente de hoy con created_at más antiguo
        $pendientesPrio = [];     // pendientes prioritarios (avisos)
        $llamadoPorAsesor = [];   // asesor_id => su turno llamado más reciente (el que muestra su módulo)

        foreach ($vivos as $t) {
            if ($t->esLlamado()) {
                if ($t->asesor_id !== null) {
                    $actual = $llamadoPorAsesor[$t->asesor_id] ?? null;
                    if (!$actual || ($t->fecha_llamado && (!$actual->fecha_llamado || $t->fecha_llamado->gt($actual->fecha_llamado)))) {
                        $llamadoPorAsesor[$t->asesor_id] = $t;
                    }
                }
                continue;
            }

            // En cola: pendiente o aplazado
            $porServicio[$t->servicio_id] ??= $this->contadoresVacios();
            $s = &$porServicio[$t->servicio_id];
            $s['en_cola']++;
            $enEspera++;
            if ($t->esPrioritario()) {
                $s['prioritarios']++;
                $prioritariosEnEspera++;
            }
            if ($t->esAplazado()) {
                $s['aplazados']++;
                $aplazados++;
                if ($this->esMasAntiguo($t, $s['aplazado_mas_antiguo'])) {
                    $s['aplazado_mas_antiguo'] = $t;
                }
            } else { // pendiente
                if ($this->esMasAntiguo($t, $s['pendiente_mas_antiguo'])) {
                    $s['pendiente_mas_antiguo'] = $t;
                }
                if ($this->esMasAntiguo($t, $masAntiguo)) {
                    $masAntiguo = $t;
                }
                if ($t->esPrioritario()) {
                    $pendientesPrio[] = $t;
                    if ($this->esMasAntiguo($t, $s['prio_mas_antiguo'])) {
                        $s['prio_mas_antiguo'] = $t;
                    }
                }
            }
            unset($s);
        }

        // --- Consulta 4: asesores involucrados (con módulo abierto o con atendidos hoy) -----------
        // Módulo abierto = caja ACTIVA con asesor. Una caja inactiva puede conservar asesor_activo_id
        // (CajaController::update no lo libera al inactivarla), pero ahí ya no se puede trabajar:
        // AsesorController::dashboard() la descarta y manda a seleccionar caja, y llamarSiguienteTurno,
        // autoLlamarTurno y llamarTurnoEspecifico solo recuperan cajas con estado 'activa'. Esa caja es
        // 'inhabilitado' y su asesor no abre módulo ni da cobertura.
        $cajasAbiertas = $cajas->filter(fn (Caja $c) => $c->estado === 'activa' && $c->asesor_activo_id);
        $idsAbiertos = $cajasAbiertas->pluck('asesor_activo_id')->unique()->values();
        // Asesores con sesión viva (mismo criterio que la tabla vieja de "Usuarios activos": User::activos()),
        // aunque todavía no tengan módulo: el Inicio los lista como "Sin módulo".
        $idsConectados = User::query()->activos()->where('rol', 'Asesor')->pluck('id');
        $idsUsuarios = $idsAbiertos
            ->merge(array_keys($atendidosPorAsesor))
            ->merge($idsConectados)
            ->unique()->values();
        $usuarios = $idsUsuarios->isEmpty()
            ? collect()
            : User::query()->whereIn('id', $idsUsuarios)->get()->keyBy('id');

        // --- Consulta 5: servicios asignados a los asesores con módulo abierto -------------------
        $cubren = $this->cobertura($idsAbiertos, $usuarios, $servicios);

        $moduloDeAsesor = $cajasAbiertas->pluck('numero_caja', 'asesor_activo_id');

        $servicioNombre = fn ($id) => $servicios->get($id)?->nombre;

        // ------------------------------------------------------------------ módulos
        $modulos = [];
        $conteoModulos = ['habilitados' => 0, 'abiertos' => 0, 'atendiendo' => 0, 'libres' => 0, 'descanso' => 0, 'canal' => 0];
        $atencionesEnCurso = []; // turno de cada tarjeta 'atendiendo' (avisos de atención larga)

        foreach ($cajas as $caja) {
            $activa = $caja->estado === 'activa';
            if ($activa) {
                $conteoModulos['habilitados']++;
            }

            $uid = $activa ? $caja->asesor_activo_id : null;
            $asesor = null;
            $turno = null;

            if ($uid) {
                $conteoModulos['abiertos']++;
                $u = $usuarios->get($uid);
                $enCurso = $llamadoPorAsesor[$uid] ?? null;

                if ($enCurso) {
                    $estado = 'atendiendo';
                } elseif ($u && $u->estado_asesor === 'descanso') {
                    $estado = 'descanso';
                } elseif ($u && method_exists($u, 'estaEnCanalNoPresencial') && $u->estaEnCanalNoPresencial()) {
                    $estado = 'canal';
                } else {
                    $estado = 'libre';
                }
                $conteoModulos[$estado === 'libre' ? 'libres' : $estado]++;

                $nombre = $this->nombreDe($u, $uid);
                $asesor = ['id' => (int) $uid, 'nombre' => $nombre, 'corto' => $this->nombreCorto($nombre)];

                if ($enCurso) {
                    $turno = [
                        'codigo' => $enCurso->codigo_completo,
                        'minutos' => $this->minutosDesde($enCurso->fecha_llamado, $ahora),
                        'servicio' => $servicioNombre($enCurso->servicio_id),
                    ];
                    $atencionesEnCurso[] = [
                        't' => $enCurso, 'min' => $turno['minutos'],
                        'modulo' => (int) $caja->numero_caja, 'asesor' => $nombre,
                    ];
                }
            } else {
                $estado = $activa ? 'cerrado' : 'inhabilitado';
            }

            $ubicacion = trim((string) $caja->ubicacion);
            $modulos[] = [
                'numero' => (int) $caja->numero_caja,
                'ubicacion' => $ubicacion === '' ? null : $ubicacion,
                'estado' => $estado,
                'asesor' => $asesor,
                'turno' => $turno,
                // Lo atendido hoy por el asesor que ocupa el módulo (cuadra con por_asesor).
                'atendidos_hoy' => $uid ? ($atendidosPorAsesor[$uid] ?? 0) : null,
            ];
        }

        // ------------------------------------------------------------------ asesores conectados
        // Los que tienen módulo abierto más los que tienen sesión viva sin módulo. El estado de un asesor con
        // módulo es el mismo de su tarjeta de módulo; sin módulo solo puede estar en canal o "sin módulo".
        $asesores = [];
        $conteoAsesores = ['conectados' => 0, 'atendiendo' => 0, 'libres' => 0, 'descanso' => 0, 'canal' => 0, 'sin_modulo' => 0];
        foreach ($idsAbiertos->merge($idsConectados)->unique() as $uid) {
            $u = $usuarios->get($uid);
            if (!$u || !$u->esAsesor()) {
                continue;
            }
            $modulo = $moduloDeAsesor->get($uid);
            $enCanal = method_exists($u, 'estaEnCanalNoPresencial') && $u->estaEnCanalNoPresencial();
            $enCurso = $modulo !== null ? ($llamadoPorAsesor[$uid] ?? null) : null;

            if ($enCurso) {
                $estado = 'atendiendo';
            } elseif ($modulo !== null && $u->estado_asesor === 'descanso') {
                $estado = 'descanso';
            } elseif ($enCanal) {
                $estado = 'canal';
            } else {
                $estado = $modulo !== null ? 'libre' : 'sin_modulo';
            }
            $conteoAsesores['conectados']++;
            $conteoAsesores[$estado === 'libre' ? 'libres' : $estado]++;

            $asesores[] = [
                'id' => (int) $uid,
                'nombre' => $this->nombreDe($u, $uid),
                'modulo' => $modulo !== null ? (int) $modulo : null,
                'estado' => $estado,
                'turno' => $enCurso ? [
                    'codigo' => $enCurso->codigo_completo,
                    'minutos' => $this->minutosDesde($enCurso->fecha_llamado, $ahora),
                ] : null,
                // Solo turnero-huv registra canales no presenciales.
                'canal' => $enCanal ? [
                    'actividad' => $u->actividad_canal_no_presencial ?? null,
                    'minutos' => $this->minutosDesde($u->inicio_canal_no_presencial ?? null, $ahora),
                ] : null,
                'atendidos_hoy' => $atendidosPorAsesor[$uid] ?? 0,
            ];
        }
        // Por número de módulo (los sin módulo al final) y luego por nombre: orden estable entre refrescos.
        usort($asesores, fn ($a, $b) => [$a['modulo'] === null, $a['modulo'], $a['nombre']]
                                       <=> [$b['modulo'] === null, $b['modulo'], $b['nombre']]);

        // ------------------------------------------------------------------ servicios
        $listaServicios = [];
        $primeroEnCola = []; // servicio_id => turno que se nombra en el aviso de sin cobertura
        $idsCandidatos = array_unique(array_merge(array_keys($porServicio), array_keys($cubren)));
        $tieneHijos = $servicios->whereNotNull('servicio_padre_id')->pluck('servicio_padre_id')->flip();

        foreach ($idsCandidatos as $sid) {
            $modelo = $servicios->get($sid);
            $s = $porServicio[$sid] ?? $this->contadoresVacios();
            $nCubren = $cubren[$sid] ?? 0;

            $conActividad = $s['en_cola'] > 0 || $s['atendidos'] > 0;
            // Solo por cobertura se listan servicios que de verdad reciben turnos: activos y sin
            // subservicios (una sección como "CITAS" no recibe turnos propios; sus hijos sí).
            $listablePorCobertura = $nCubren > 0 && $modelo && $modelo->estado === 'activo' && !isset($tieneHijos[$sid]);
            if (!$conActividad && !$listablePorCobertura) {
                continue;
            }

            $padre = $modelo && $modelo->servicio_padre_id ? $servicios->get($modelo->servicio_padre_id) : null;
            $pendMasAntiguo = $s['pendiente_mas_antiguo'];

            // Alerta: un prioritario pasó su límite, o el pendiente más antiguo pasó el límite general.
            $minPrio = $s['prio_mas_antiguo'] ? $this->minutosDesde($s['prio_mas_antiguo']->created_at, $ahora) : null;
            $minMax = $pendMasAntiguo ? $this->minutosDesde($pendMasAntiguo->created_at, $ahora) : null;
            $motivo = null;
            if ($minPrio !== null && $minPrio > $umbrales['prioritario']) {
                $motivo = $s['prio_mas_antiguo']->codigo_completo . ' prioritario: pasó el límite de ' . $umbrales['prioritario'] . ' min';
            } elseif ($minMax !== null && $minMax > $umbrales['espera']) {
                $motivo = $pendMasAntiguo->codigo_completo . ': pasó el límite de ' . $umbrales['espera'] . ' min';
            }
            $primeroEnCola[$sid] = $pendMasAntiguo ?? $s['aplazado_mas_antiguo'];

            $listaServicios[] = [
                'id' => (int) $sid,
                'nombre' => $modelo?->nombre,
                'seccion' => $padre?->nombre ?? $modelo?->nombre, // la sección es el servicio raíz
                'codigo' => $modelo?->codigo,
                'en_cola' => $s['en_cola'],
                'prioritarios' => $s['prioritarios'],
                'aplazados' => $s['aplazados'],
                'espera_max_min' => $pendMasAntiguo ? $this->minutosDesde($pendMasAntiguo->created_at, $ahora) : null,
                'cubren' => $nCubren,
                'atendidos_hoy' => $s['atendidos'],
                'atencion_mediana_seg' => $this->mediana($s['duraciones']),
                'sin_cobertura' => $s['en_cola'] > 0 && $nCubren === 0,
                'alerta' => $motivo !== null,
                'alerta_motivo' => $motivo,
            ];
        }

        // Sin cobertura primero, luego espera máxima desc, luego en cola desc, luego nombre.
        usort($listaServicios, fn ($a, $b) =>
            [$b['sin_cobertura'], $b['espera_max_min'] ?? -1, $b['en_cola'], (string) $a['nombre']]
            <=> [$a['sin_cobertura'], $a['espera_max_min'] ?? -1, $a['en_cola'], (string) $b['nombre']]);

        // ------------------------------------------------------------------ avisos
        $avisos = [];

        foreach ($listaServicios as $fila) {
            if ($fila['sin_cobertura']) {
                $primero = $primeroEnCola[$fila['id']] ?? null;
                $avisos[] = [
                    'tipo' => 'sin_cobertura',
                    'servicio' => $fila['nombre'],
                    'en_cola' => $fila['en_cola'],
                    'turno' => $primero ? $primero->codigo_completo : null,
                    'accion' => ['texto' => 'Asignar asesor', 'url' => route('admin.asignacion-servicios', [], false)],
                ];
            }
        }

        // Mayor espera primero (empate: el de id menor).
        $porMinutos = fn ($a, $b) => [$b['min'], $a['t']->id] <=> [$a['min'], $b['t']->id];

        $prio = collect($pendientesPrio)
            ->map(fn (Turno $t) => ['t' => $t, 'min' => $this->minutosDesde($t->created_at, $ahora)])
            ->filter(fn ($x) => $x['min'] > $umbrales['prioritario'])
            ->sort($porMinutos)
            ->take(self::MAX_AVISOS);
        foreach ($prio as $x) {
            $t = $x['t'];
            $avisos[] = [
                'tipo' => 'prioritario',
                'turno' => $t->codigo_completo,
                'minutos' => $x['min'],
                'servicio' => $servicioNombre($t->servicio_id),
                'limite' => $umbrales['prioritario'],
                // Gestión de turnos (AdminController::turnos y su API turnos-hoy) busca `search` con LIKE en
                // codigo, en numero y en el nombre del servicio, por separado: el código completo "K-016"
                // no coincide con nada. Servicio + número sí encuentran el turno (y como mucho otros del
                // mismo servicio cuyo número contenga esas cifras: 116, 160...).
                'accion' => ['texto' => 'Ver turno', 'url' => route('admin.turnos', [
                    'servicio' => (int) $t->servicio_id, 'search' => (int) $t->numero,
                ], false)],
            ];
        }

        // Atención larga = el turno que muestra la tarjeta de un módulo abierto, con el número de ESE módulo,
        // para que aviso y tarjeta coincidan (turnos.caja_id se queda en la caja anterior si el asesor cambia
        // de caja con el turno abierto). Un 'llamado' cuyo asesor ya no ocupa ningún módulo (CleanExpiredBoxes
        // libera la caja tras 30 min sin actividad sin cerrar el turno) no es una atención en curso: no se
        // avisa, porque llenaría los avisos con módulos cerrados y taparía las atenciones reales.
        $largas = collect($atencionesEnCurso)
            ->filter(fn ($x) => $x['min'] !== null && $x['min'] > $umbrales['atencion'])
            ->unique(fn ($x) => $x['t']->id)
            ->sort($porMinutos)
            ->take(self::MAX_AVISOS);
        foreach ($largas as $x) {
            $avisos[] = [
                'tipo' => 'atencion_larga',
                'turno' => $x['t']->codigo_completo,
                'minutos' => $x['min'],
                'modulo' => $x['modulo'],
                'asesor' => $x['asesor'],
                'accion' => ['texto' => 'Ver módulo', 'url' => null],
            ];
        }

        // ------------------------------------------------------------------ por asesor
        $porAsesor = [];
        foreach ($idsUsuarios as $uid) {
            $atendidos = $atendidosPorAsesor[$uid] ?? 0;
            $modulo = $moduloDeAsesor->get($uid);
            if ($atendidos === 0 && $modulo === null) {
                continue; // sin módulo abierto ni atendidos hoy
            }
            $porAsesor[] = [
                'id' => (int) $uid,
                'nombre' => $this->nombreDe($usuarios->get($uid), $uid),
                'modulo' => $modulo !== null ? (int) $modulo : null,
                'atendidos' => $atendidos,
            ];
        }
        usort($porAsesor, fn ($a, $b) => [$b['atendidos'], $a['nombre']] <=> [$a['atendidos'], $b['nombre']]);

        // ------------------------------------------------------------------ resumen
        $esperaMax = null;
        if ($masAntiguo) {
            $min = $this->minutosDesde($masAntiguo->created_at, $ahora);
            $esPrio = $masAntiguo->esPrioritario();
            $esperaMax = [
                'minutos' => $min,
                'turno' => $masAntiguo->codigo_completo,
                'prioritario' => $esPrio,
                'servicio' => $servicioNombre($masAntiguo->servicio_id),
                'supera_umbral' => $min > ($esPrio ? $umbrales['prioritario'] : $umbrales['espera']),
            ];
        }

        return [
            'generado' => $ahora->toIso8601String(),
            'unidad' => (string) config('panel.unidad', 'UBA'),
            'umbrales' => $umbrales,
            'resumen' => [
                'en_espera' => $enEspera,
                'prioritarios' => $prioritariosEnEspera,
                'aplazados' => $aplazados,
                'espera_max' => $esperaMax,
                'modulos' => $conteoModulos,
                'ultima_hora' => [
                    'llegaron' => $llegaron,
                    'finalizaron' => $finalizaron,
                    'tendencia' => $finalizaron > $llegaron ? 'baja' : ($finalizaron < $llegaron ? 'sube' : 'estable'),
                ],
                'atendidos_hoy' => $atendidosHoy,
                'transferidos_hoy' => $transferidosHoy,
                'asesores' => $conteoAsesores,
            ],
            'servicios' => $listaServicios,
            'modulos' => $modulos,
            'asesores' => $asesores,
            'avisos' => $avisos,
            'por_asesor' => $porAsesor,
        ];
    }

    /**
     * Cuántos asesores con módulo abierto pueden recibir turnos de cada servicio.
     *
     * @return array<int,int> servicio_id => asesores
     */
    private function cobertura(Collection $idsAbiertos, Collection $usuarios, Collection $servicios): array
    {
        $llaman = $idsAbiertos->filter(fn ($uid) => $this->puedeLlamarTurnos($usuarios->get($uid)))->values();
        if ($llaman->isEmpty()) {
            return [];
        }

        $asignaciones = DB::table('user_servicio')
            ->whereIn('user_id', $llaman)
            ->get(['user_id', 'servicio_id'])
            ->groupBy('user_id');

        $hijosDe = $servicios->groupBy('servicio_padre_id');
        $cubren = [];

        foreach ($llaman as $uid) {
            $u = $usuarios->get($uid);
            $asignados = $asignaciones->get($uid, collect())->pluck('servicio_id')->map(fn ($id) => (int) $id)->all();
            // autoLlamarTurno() se niega en descanso; el llamado manual (botones y por código) no.
            $autoLlamado = (bool) $u->auto_llamado_activo && $u->estado_asesor !== 'descanso';

            foreach ($this->serviciosLlamables($asignados, $servicios, $hijosDe, $autoLlamado) as $sid) {
                $cubren[$sid] = ($cubren[$sid] ?? 0) + 1;
            }
        }

        return $cubren;
    }

    /**
     * Si un asesor con módulo abierto puede llamar turnos, con las mismas barreras de AsesorController:
     *  - rol Asesor: el grupo de rutas asesor.role, y llamarSiguienteTurno / autoLlamarTurno lo exigen;
     *  - no estar en canal no presencial: llamarSiguienteTurno, llamarTurnoEspecifico y autoLlamarTurno lo
     *    rechazan. User::estaEnCanalNoPresencial existe en los dos turneros; method_exists por si acaso.
     * (La caja activa ya la garantiza $idsAbiertos.)
     */
    private function puedeLlamarTurnos(?User $u): bool
    {
        if (!$u || !$u->esAsesor()) {
            return false;
        }

        return !(method_exists($u, 'estaEnCanalNoPresencial') && $u->estaEnCanalNoPresencial());
    }

    /**
     * Servicios cuyos turnos puede llamar un asesor, con la MISMA lógica de AsesorController:
     *
     *  - dashboard(): el asesor ve un botón por cada servicio asignado y activo de nivel 'servicio'
     *    (con una fila por cada subservicio suyo asignado) y por cada subservicio asignado y activo
     *    "huérfano" (sin padre, o con un padre que él no tiene asignado).
     *  - llamarSiguienteTurno(): con ocultar_turno no llama nada; si no, busca en ese servicio y en sus
     *    subservicios que TAMBIÉN tenga asignados y sin ocultar_turno. Asignar el padre NO cubre a los
     *    hijos no asignados (Asignación de servicios sí marca los hijos al asignar el padre).
     *  - autoLlamarTurno() (solo con auto_llamado_activo y fuera de descanso): todos sus servicios activos
     *    sin ocultar_turno, más sus subservicios asignados sin ocultar_turno.
     *  - Antes de esto, cobertura() descarta a quien no puede llamar nada (puedeLlamarTurnos()).
     *  - llamarTurnoEspecifico(): los turnos de un servicio con ocultar_turno solo se llaman por código,
     *    y para eso basta con tener asignado exactamente ese servicio.
     *
     * @param  int[]  $asignados  ids de user_servicio del asesor
     * @return int[]
     */
    private function serviciosLlamables(array $asignados, Collection $servicios, Collection $hijosDe, bool $autoLlamado): array
    {
        $esAsignado = array_flip($asignados);
        $llamables = [];

        // Lo que busca llamarSiguienteTurno($s): $s + sus hijos asignados, todo sin ocultar_turno.
        $expandir = function (Servicio $s) use (&$llamables, $esAsignado, $hijosDe) {
            if ($s->ocultar_turno) {
                return;
            }
            $llamables[$s->id] = true;
            foreach ($hijosDe->get($s->id, []) as $hijo) {
                if (isset($esAsignado[$hijo->id]) && !$hijo->ocultar_turno) {
                    $llamables[$hijo->id] = true;
                }
            }
        };

        foreach ($asignados as $id) {
            $s = $servicios->get($id);
            if (!$s) {
                continue;
            }
            $activo = $s->estado === 'activo';

            if ($s->ocultar_turno) {
                $llamables[$s->id] = true; // llamarTurnoEspecifico (por código)
            }

            if ($s->nivel === 'servicio' && $activo) {
                // Botón del servicio principal + filas de sus subservicios asignados (sin filtrar
                // estado). Las filas existen aunque el principal tenga ocultar_turno.
                $expandir($s);
                foreach ($hijosDe->get($s->id, []) as $hijo) {
                    if (isset($esAsignado[$hijo->id])) {
                        $expandir($hijo);
                    }
                }
            } elseif ($s->nivel === 'subservicio' && $activo) {
                $padre = $s->servicio_padre_id ? $servicios->get($s->servicio_padre_id) : null;
                if (!$padre || !isset($esAsignado[$padre->id])) {
                    $expandir($s); // subservicio huérfano: botón propio
                }
            }

            if ($autoLlamado && $activo) {
                $expandir($s);
            }
        }

        return array_keys($llamables);
    }

    private function contadoresVacios(): array
    {
        return [
            'en_cola' => 0, 'prioritarios' => 0, 'aplazados' => 0, 'atendidos' => 0, 'duraciones' => [],
            'pendiente_mas_antiguo' => null, 'prio_mas_antiguo' => null, 'aplazado_mas_antiguo' => null,
        ];
    }

    /** Si $t se creó antes que $actual (empate: el de id menor). */
    private function esMasAntiguo(Turno $t, ?Turno $actual): bool
    {
        if (!$actual) {
            return true;
        }
        if ($t->created_at->eq($actual->created_at)) {
            return $t->id < $actual->id;
        }

        return $t->created_at->lt($actual->created_at);
    }

    /** Minutos enteros transcurridos (nunca negativos). */
    private function minutosDesde(?CarbonInterface $desde, CarbonInterface $ahora): ?int
    {
        if (!$desde) {
            return null;
        }

        return intdiv(max(0, $ahora->getTimestamp() - $desde->getTimestamp()), 60);
    }

    /** Mediana (promedio de los dos centrales si la cantidad es par), redondeada. */
    private function mediana(array $valores): ?int
    {
        $n = count($valores);
        if ($n === 0) {
            return null;
        }
        sort($valores, SORT_NUMERIC);
        $m = intdiv($n, 2);

        return (int) round($n % 2 ? $valores[$m] : ($valores[$m - 1] + $valores[$m]) / 2);
    }

    private function nombreDe(?User $u, $id): string
    {
        $nombre = trim((string) ($u?->nombre_completo ?: $u?->nombre_usuario));

        return $nombre !== '' ? $nombre : 'Asesor #' . $id;
    }

    /**
     * Primer nombre + inicial del primer apellido: "Jorge Orlando Duarte Martinez" -> "Jorge D.".
     * Se asume nombre(s) + apellido(s): con 2 palabras el apellido es la 2.ª, con 3 o más es la 3.ª
     * ("Karen Julieth Meneses" -> "Karen M."). Las partículas (de, del, la...) no cuentan.
     */
    private function nombreCorto(string $nombre): string
    {
        $partes = preg_split('/\s+/u', trim($nombre), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $palabras = array_values(array_filter(
            $partes,
            fn ($p) => !in_array(mb_strtolower($p, 'UTF-8'), self::PARTICULAS, true)
        ));
        if (count($palabras) < 2) {
            $palabras = $partes;
        }
        if (count($palabras) < 2) {
            return $palabras[0] ?? $nombre;
        }
        $apellido = $palabras[min(2, count($palabras) - 1)];

        return $palabras[0] . ' ' . mb_strtoupper(mb_substr($apellido, 0, 1, 'UTF-8'), 'UTF-8') . '.';
    }

    /** Umbrales en minutos (con los mismos valores por defecto de config/panel.php). */
    private function umbrales(): array
    {
        $u = (array) config('panel.umbrales', []);

        return [
            'espera' => (int) ($u['espera'] ?? 30),
            'prioritario' => (int) ($u['prioritario'] ?? 15),
            'atencion' => (int) ($u['atencion'] ?? 20),
        ];
    }
}
