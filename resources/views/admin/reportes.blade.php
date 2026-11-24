@extends('layouts.admin')

@section('title', 'Reportes')

@section('content')
{{-- Informe de turnos en una sola vista: periodo y qué incluir a la izquierda; a la derecha, la hoja del informe
     (resumen, formato y Generar), la única pieza elevada. La descarga va por URL en un iframe oculto (nunca Blob):
     ver generar(). ReportesController::generarReporte arma el Excel o el PDF. --}}
@php
    // Los mismos textos que informeTurnos calcula al arrancar (init, textoPeriodo, textoAlcance…), escritos desde el
    // servidor para que ningún x-text salga vacío antes de Alpine. Alpine los reemplaza con los suyos.
    $mesesInf = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $cuentaInf = fn ($n, $uno, $varios) => $n . ' ' . ($n === 1 ? $uno : $varios);
    $fechaInf = fn ($f, $anio = true) => $f->day . ' ' . $mesesInf[$f->month - 1] . ($anio ? ' ' . $f->year : '');
    $hojasInf = collect($secciones)->flatMap(fn ($s) => count($s['hijos']) ? $s['hijos'] : [$s])->values();
    $nHojasInf = $hojasInf->count();
    $nAsesoresInf = count($asesores);
    $todoInf = $cuentaInf($nHojasInf, 'servicio', 'servicios') . ' · ' . $cuentaInf($nAsesoresInf, 'asesor', 'asesores');

    // Periodo: el que llega por la URL (desde Gráficos) o «Este mes».
    $hoyInf = now()->startOfDay();
    $conUrlInf = $inicial['desde'] && $inicial['hasta'];
    $desdeInf = $conUrlInf ? \Illuminate\Support\Carbon::parse($inicial['desde'])->startOfDay() : $hoyInf->copy()->startOfMonth();
    $hastaInf = $conUrlInf ? \Illuminate\Support\Carbon::parse($inicial['hasta'])->startOfDay() : $hoyInf->copy();
    $validoInf = $desdeInf->lte($hastaInf) && $hastaInf->lte($hoyInf);
    $diasInf = $validoInf ? (int) $desdeInf->diffInDays($hastaInf) + 1 : 0;
    if (!$validoInf) $periodoCortoInf = 'Periodo sin definir';
    elseif ($desdeInf->equalTo($hastaInf)) $periodoCortoInf = $fechaInf($hastaInf);
    elseif ($desdeInf->year === $hastaInf->year && $desdeInf->month === $hastaInf->month) $periodoCortoInf = $desdeInf->day . ' – ' . $fechaInf($hastaInf);
    else $periodoCortoInf = $fechaInf($desdeInf, $desdeInf->year !== $hastaInf->year) . ' – ' . $fechaInf($hastaInf);
    if ($desdeInf->gt($hastaInf)) $periodoInf = 'La fecha final es anterior a la inicial.';
    elseif ($hastaInf->gt($hoyInf)) $periodoInf = 'El periodo no puede terminar después de hoy.';
    else $periodoInf = $diasInf === 1 ? 'Un día.' : $diasInf . ' días, del ' . $fechaInf($desdeInf, false) . ' al ' . $fechaInf($hastaInf) . '.';
    // El atajo elegido, como lo deja init(): «Este mes» sin periodo en la URL; con periodo, el primero cuyo rango coincida
    // (atajoDe(), mismo orden y misma semana de lunes a domingo que rango()).
    $lunesInf = $hoyInf->copy()->subDays($hoyInf->dayOfWeekIso - 1);
    $rangosInf = [
        'hoy' => [$hoyInf, $hoyInf],
        'ayer' => [$hoyInf->copy()->subDay(), $hoyInf->copy()->subDay()],
        'semana' => [$lunesInf, $hoyInf],
        'semana_pasada' => [$lunesInf->copy()->subDays(7), $lunesInf->copy()->subDay()],
        'mes' => [$hoyInf->copy()->startOfMonth(), $hoyInf],
        'mes_anterior' => [$hoyInf->copy()->subMonthNoOverflow()->startOfMonth(), $hoyInf->copy()->startOfMonth()->subDay()],
    ];
    $atajoInf = $conUrlInf
        ? collect($rangosInf)->search(fn ($r) => $r[0]->toDateString() === $desdeInf->toDateString() && $r[1]->toDateString() === $hastaInf->toDateString())
        : 'mes';

    // Desde Gráficos con un servicio: ese servicio (y sus subservicios) ya marcado, como en init().
    $marcadosInf = [];
    if ($inicial['servicio']) {
        $seccionInf = collect($secciones)->firstWhere('id', $inicial['servicio']);
        $idsInf = $seccionInf ? (count($seccionInf['hijos']) ? array_column($seccionInf['hijos'], 'id') : [$seccionInf['id']]) : [$inicial['servicio']];
        if ($hojasInf->contains(fn ($h) => in_array($h['id'], $idsInf, true))) $marcadosInf = $idsInf;
    }
    $nMarcadosInf = count($marcadosInf);
    if (!$nMarcadosInf) $detalleInf = $todoInf;
    elseif ($nMarcadosInf === $nHojasInf) $detalleInf = 'Todos los servicios';
    else {
        $nombresInf = $hojasInf->filter(fn ($h) => in_array($h['id'], $marcadosInf, true))->pluck('nombre')->values()->all();
        $detalleInf = count($nombresInf) <= 2 ? implode(' y ', $nombresInf)
            : implode(', ', array_slice($nombresInf, 0, 2)) . ' y ' . (count($nombresInf) - 2) . ' más';
    }
@endphp
<div class="reportes-vista" x-data="informeTurnos(@js($secciones), @js($asesores), @js($inicial))">

    <form class="informe vista-lateral" @submit.prevent="generar()" novalidate aria-label="Informe de turnos">
        <div class="informe__principal">
            <!-- Periodo -->
            <section class="superficie informe-bloque" aria-labelledby="informe-periodo-titulo">
                <div class="informe-bloque__cabeza">
                    <h2 id="informe-periodo-titulo" class="informe-bloque__titulo">Periodo</h2>
                    <p class="informe-bloque__sub">Use un atajo o escriba las dos fechas. El periodo puede terminar hoy, no después.</p>
                </div>
                <div class="filtros-rapidos filtros-rapidos--papel informe-atajos" role="group" aria-label="Atajos de periodo">
                    @foreach (['hoy' => 'Hoy', 'ayer' => 'Ayer', 'semana' => 'Esta semana', 'semana_pasada' => 'Semana pasada', 'mes' => 'Este mes', 'mes_anterior' => 'Mes anterior'] as $clave => $rotulo)
                        <button type="button" class="filtro-rapido" aria-pressed="{{ $atajoInf === $clave ? 'true' : 'false' }}" :aria-pressed="(atajo === '{{ $clave }}').toString()" @click="elegirAtajo('{{ $clave }}')">{{ $rotulo }}</button>
                    @endforeach
                </div>
                <div class="informe-fechas">
                    <label class="form-campo informe-fechas__campo">Desde
                        <input type="date" class="campo" value="{{ $desdeInf->toDateString() }}" x-model="desde" :max="hoy()" @input="atajo = atajoDe()" required
                               :aria-invalid="fechaInvalida('desde').toString()" aria-describedby="informe-periodo-texto">
                    </label>
                    <label class="form-campo informe-fechas__campo">Hasta
                        <input type="date" class="campo" value="{{ $hastaInf->toDateString() }}" x-model="hasta" :max="hoy()" :min="desde" @input="atajo = atajoDe()" required
                               :aria-invalid="fechaInvalida('hasta').toString()" aria-describedby="informe-periodo-texto">
                    </label>
                    <p id="informe-periodo-texto" class="informe-fechas__texto{{ $validoInf ? '' : ' is-error' }}" :class="{ 'is-error': !periodoValido() }">
                        <svg x-show="!periodoValido()" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v4.5"/><path d="M12 16h.01"/></svg>
                        <span x-text="textoPeriodo()">{{ $periodoInf }}</span>
                    </p>
                </div>
            </section>

            <!-- Qué incluir -->
            <section class="superficie informe-bloque" aria-labelledby="informe-alcance-titulo">
                <div class="informe-bloque__cabeza">
                    <h2 id="informe-alcance-titulo" class="informe-bloque__titulo">Qué incluir</h2>
                    <p class="informe-bloque__sub">Todo el turnero, o solo algunos servicios o asesores.</p>
                </div>
                <div class="informe-opciones" role="radiogroup" aria-labelledby="informe-alcance-titulo"
                     @keydown.right.prevent="moverOpcion($event, 1)" @keydown.down.prevent="moverOpcion($event, 1)"
                     @keydown.left.prevent="moverOpcion($event, -1)" @keydown.up.prevent="moverOpcion($event, -1)">
                    <button type="button" role="radio" class="informe-opcion" aria-checked="{{ $nMarcadosInf ? 'false' : 'true' }}" tabindex="{{ $nMarcadosInf ? -1 : 0 }}" :aria-checked="(alcance === 'todo').toString()" :tabindex="alcance === 'todo' ? 0 : -1"
                            @click="alcance = 'todo'" aria-labelledby="alcance-todo-t" aria-describedby="alcance-todo-d alcance-todo-n">
                        <span class="informe-opcion__cabeza">
                            <svg class="informe-opcion__icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
                            <span class="informe-marca" aria-hidden="true"></span>
                        </span>
                        <span id="alcance-todo-t" class="informe-opcion__titulo">Todo el turnero</span>
                        <span id="alcance-todo-d" class="informe-opcion__texto">Todos los turnos del periodo, de todos los servicios y asesores.</span>
                        <span id="alcance-todo-n" class="informe-opcion__dato" x-text="cuenta(hojas().length, 'servicio', 'servicios') + ' · ' + cuenta(asesores.length, 'asesor', 'asesores')">{{ $todoInf }}</span>
                    </button>
                    <button type="button" role="radio" class="informe-opcion" aria-checked="{{ $nMarcadosInf ? 'true' : 'false' }}" tabindex="{{ $nMarcadosInf ? 0 : -1 }}" :aria-checked="(alcance === 'servicios').toString()" :tabindex="alcance === 'servicios' ? 0 : -1"
                            @click="alcance = 'servicios'" aria-labelledby="alcance-serv-t" aria-describedby="alcance-serv-d alcance-serv-n">
                        <span class="informe-opcion__cabeza">
                            <svg class="informe-opcion__icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 2v2"/><path d="M5 2v2"/><path d="M5 3H4a2 2 0 0 0-2 2v4a6 6 0 0 0 12 0V5a2 2 0 0 0-2-2h-1"/><path d="M8 15a6 6 0 0 0 12 0v-3"/><circle cx="20" cy="10" r="2"/></svg>
                            <span class="informe-marca" aria-hidden="true"></span>
                        </span>
                        <span id="alcance-serv-t" class="informe-opcion__titulo">Por servicio</span>
                        <span id="alcance-serv-d" class="informe-opcion__texto">Solo los servicios que marque. Marcar una sección incluye todos sus subservicios.</span>
                        <span id="alcance-serv-n" class="informe-opcion__dato" x-text="servicios.length ? servicios.length + ' de ' + hojas().length + ' marcados' : cuenta(hojas().length, 'servicio', 'servicios') + ' para elegir'">{{ $nMarcadosInf ? $nMarcadosInf . ' de ' . $nHojasInf . ' marcados' : $cuentaInf($nHojasInf, 'servicio', 'servicios') . ' para elegir' }}</span>
                    </button>
                    <button type="button" role="radio" class="informe-opcion" aria-checked="false" tabindex="-1" :aria-checked="(alcance === 'asesores').toString()" :tabindex="alcance === 'asesores' ? 0 : -1"
                            @click="alcance = 'asesores'" aria-labelledby="alcance-ases-t" aria-describedby="alcance-ases-d alcance-ases-n">
                        <span class="informe-opcion__cabeza">
                            <svg class="informe-opcion__icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            <span class="informe-marca" aria-hidden="true"></span>
                        </span>
                        <span id="alcance-ases-t" class="informe-opcion__titulo">Por asesor</span>
                        <span id="alcance-ases-d" class="informe-opcion__texto">Solo los turnos que llamaron o atendieron los asesores marcados; no incluye los que nadie llamó.</span>
                        <span id="alcance-ases-n" class="informe-opcion__dato" x-text="usuarios.length ? usuarios.length + ' de ' + asesores.length + ' marcados' : cuenta(asesores.length, 'asesor', 'asesores') + ' para elegir'">{{ $cuentaInf($nAsesoresInf, 'asesor', 'asesores') }} para elegir</span>
                    </button>
                </div>

                <!-- Servicios agrupados por sección -->
                <div class="informe-selector" x-show="alcance === 'servicios'" x-cloak>
                    <div class="informe-selector__barra">
                        <div class="buscador informe-selector__buscar">
                            <svg class="buscador__icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                            <input type="search" class="campo" placeholder="Buscar servicio" x-model="buscarServicio" aria-label="Buscar servicio">
                        </div>
                        <span class="informe-selector__cuenta" aria-live="polite" x-text="serviciosMarcados().length + ' de ' + hojas().length + ' marcados'"></span>
                        <span class="informe-selector__acciones">
                            <button type="button" class="enlace-panel" @click="marcarServicios(true)">Todos</button>
                            <button type="button" class="enlace-panel" @click="marcarServicios(false)">Ninguno</button>
                        </span>
                    </div>
                    <ul class="informe-grupos" x-show="seccionesVisibles().length">
                        <template x-for="s in seccionesVisibles()" :key="s.id">
                            <li class="informe-grupo">
                                <label class="informe-casilla informe-casilla--seccion">
                                    <input type="checkbox" class="casilla" :checked="estadoSeccion(s) === 'todo'" x-effect="$el.indeterminate = estadoSeccion(s) === 'parte'" @change="alternarSeccion(s, $event.target.checked)">
                                    <span class="informe-casilla__textos"><span class="informe-casilla__linea"><span class="informe-casilla__nombre" x-text="s.nombre"></span>
                                        <span class="estado-chip estado-chip--neutro estado-chip--chico" x-show="!s.activo">Inactivo</span></span></span>
                                    <span class="informe-grupo__cuenta" x-show="s.hijos.length" x-text="contarMarcados(s) + ' de ' + s.hijos.length"></span>
                                </label>
                                <ul class="informe-grupo__hijos" x-show="s.hijos.length">
                                    <template x-for="h in hijosVisibles(s)" :key="h.id">
                                        <li><label class="informe-casilla">
                                            <input type="checkbox" class="casilla" :value="h.id" x-model.number="servicios">
                                            <span class="informe-casilla__textos"><span class="informe-casilla__linea"><span class="informe-casilla__nombre" x-text="h.nombre"></span>
                                                <span class="estado-chip estado-chip--neutro estado-chip--chico" x-show="!h.activo">Inactivo</span></span></span>
                                        </label></li>
                                    </template>
                                </ul>
                            </li>
                        </template>
                    </ul>
                    {{-- Vacío donde iría la lista, con su salida: quitar la búsqueda o ir a crear los servicios --}}
                    <div class="vacio" x-show="!seccionesVisibles().length" x-cloak>
                        @if (count($secciones))
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                            <p class="vacio__titulo" x-text="'Ningún servicio coincide con «' + buscarServicio.trim() + '»'">Ningún servicio coincide con la búsqueda</p>
                            <p class="vacio__texto">Pruebe con otro nombre o vea todos los servicios.</p>
                            <button type="button" class="enlace-panel" @click="buscarServicio = ''">Quitar filtros</button>
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 2v2"/><path d="M5 2v2"/><path d="M5 3H4a2 2 0 0 0-2 2v4a6 6 0 0 0 12 0V5a2 2 0 0 0-2-2h-1"/><path d="M8 15a6 6 0 0 0 12 0v-3"/><circle cx="20" cy="10" r="2"/></svg>
                            <p class="vacio__titulo">Aún no hay servicios creados</p>
                            <p class="vacio__texto">Los servicios que cree aparecen aquí para incluirlos en el informe.</p>
                            <a class="enlace-panel" href="{{ route('admin.servicios') }}">Créelos en Servicios</a>
                        @endif
                    </div>
                </div>

                <!-- Asesores -->
                <div class="informe-selector" x-show="alcance === 'asesores'" x-cloak>
                    <div class="informe-selector__barra">
                        <div class="buscador informe-selector__buscar">
                            <svg class="buscador__icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                            <input type="search" class="campo" placeholder="Buscar asesor" x-model="buscarAsesor" aria-label="Buscar asesor">
                        </div>
                        <span class="informe-selector__cuenta" aria-live="polite" x-text="usuarios.length + ' de ' + asesores.length + ' marcados'"></span>
                        <span class="informe-selector__acciones">
                            <button type="button" class="enlace-panel" @click="usuarios = asesores.map(a => a.id)">Todos</button>
                            <button type="button" class="enlace-panel" @click="usuarios = []">Ninguno</button>
                        </span>
                    </div>
                    <ul class="informe-asesores" x-show="asesoresVisibles().length">
                        <template x-for="a in asesoresVisibles()" :key="a.id">
                            <li><label class="informe-casilla informe-casilla--asesor">
                                <input type="checkbox" class="casilla" :value="a.id" x-model.number="usuarios">
                                <span class="informe-casilla__textos">
                                    <span class="informe-casilla__linea"><span class="informe-casilla__nombre" x-text="nombreAsesor(a)"></span>
                                        <span class="estado-chip estado-chip--neutro estado-chip--chico" x-show="asesorDesactivado(a)">Desactivada</span></span>
                                    <span class="informe-casilla__usuario" x-text="a.usuario"></span>
                                </span>
                            </label></li>
                        </template>
                    </ul>
                    <div class="vacio" x-show="!asesoresVisibles().length" x-cloak>
                        @if (count($asesores))
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                            <p class="vacio__titulo" x-text="'Ningún asesor coincide con «' + buscarAsesor.trim() + '»'">Ningún asesor coincide con la búsqueda</p>
                            <p class="vacio__texto">Pruebe con otro nombre o usuario, o vea a todos los asesores.</p>
                            <button type="button" class="enlace-panel" @click="buscarAsesor = ''">Quitar filtros</button>
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            <p class="vacio__titulo">Aún no hay asesores creados</p>
                            <p class="vacio__texto">Los usuarios con el rol Asesor aparecen aquí para incluirlos en el informe.</p>
                            <a class="enlace-panel" href="{{ route('admin.users') }}">Créelos en Usuarios</a>
                        @endif
                    </div>
                </div>
            </section>
        </div>

        <!-- La hoja del informe: resumen, formato y acción (la pieza elevada de la vista) -->
        <aside class="informe-hoja pieza-elevada vista-lateral__lado" aria-labelledby="informe-hoja-titulo">
            <h2 id="informe-hoja-titulo" class="informe-hoja__titulo">Informe de turnos</h2>
            <p class="informe-hoja__sub">{{ config('panel.unidad_nombre', 'Turnero') }}</p>

            <dl class="informe-resumen">
                <div class="informe-resumen__fila">
                    <dt>Periodo</dt>
                    <dd>
                        <span class="informe-resumen__valor{{ $validoInf ? '' : ' is-falta' }}" :class="{ 'is-falta': !periodoValido() }" x-text="textoPeriodoCorto()">{{ $periodoCortoInf }}</span>
                        <span class="informe-resumen__mas" x-show="periodoValido()" @unless ($validoInf) style="display: none" @endunless x-text="cuenta(dias(), 'día', 'días')">{{ $validoInf ? $cuentaInf($diasInf, 'día', 'días') : '' }}</span>
                    </dd>
                </div>
                <div class="informe-resumen__fila">
                    <dt>Incluye</dt>
                    <dd>
                        <span class="informe-resumen__valor" :class="{ 'is-falta': incluyeVacio() }" x-text="textoAlcance()">{{ $nMarcadosInf ? $cuentaInf($nMarcadosInf, 'servicio', 'servicios') : 'Todo el turnero' }}</span>
                        <span class="informe-resumen__mas" x-text="detalleAlcance()">{{ $detalleInf }}</span>
                    </dd>
                </div>
            </dl>

            <div class="informe-formato">
                <span id="informe-formato-titulo" class="informe-formato__etiqueta">Formato</span>
                <div class="informe-formatos" role="radiogroup" aria-labelledby="informe-formato-titulo"
                     @keydown.right.prevent="moverOpcion($event, 1)" @keydown.down.prevent="moverOpcion($event, 1)"
                     @keydown.left.prevent="moverOpcion($event, -1)" @keydown.up.prevent="moverOpcion($event, -1)">
                    <button type="button" role="radio" class="informe-formato__opcion" aria-checked="true" tabindex="0" :aria-checked="(formato === 'excel').toString()" :tabindex="formato === 'excel' ? 0 : -1" @click="formato = 'excel'">
                        <svg class="informe-formato__icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M3 15h18"/><path d="M9 3v18"/></svg>
                        <span class="informe-formato__textos"><span class="informe-formato__nombre">Excel</span><span class="informe-formato__ext">.xlsx</span></span>
                        <span class="informe-marca" aria-hidden="true"></span>
                    </button>
                    <button type="button" role="radio" class="informe-formato__opcion" aria-checked="false" tabindex="-1" :aria-checked="(formato === 'pdf').toString()" :tabindex="formato === 'pdf' ? 0 : -1" @click="formato = 'pdf'">
                        <svg class="informe-formato__icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
                        <span class="informe-formato__textos"><span class="informe-formato__nombre">PDF</span><span class="informe-formato__ext">.pdf</span></span>
                        <span class="informe-marca" aria-hidden="true"></span>
                    </button>
                </div>
                <p class="informe-formato__ayuda" x-show="formato === 'excel'">Hojas: Resumen, Detalle de turnos (con la cédula, si la persona la escribió en el atril), Por servicio, Por asesor y una hoja por asesor con sus turnos.</p>
                <p class="informe-formato__ayuda" x-show="formato === 'pdf'" x-cloak>Resumen, tablas por servicio y por asesor, y los 50 turnos más recientes (el Excel trae todos).</p>
            </div>

            <button type="submit" class="btn-primario btn-primario--pieza informe-generar" :disabled="!listo() || trabajando" :aria-busy="trabajando.toString()">
                <span x-text="trabajando ? 'Generando…' : 'Generar informe'">Generar informe</span>
            </button>
            <p class="informe-falta" x-show="!listo() && !trabajando" x-cloak>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v4.5"/><path d="M12 16h.01"/></svg>
                <span x-text="faltante()"></span>
            </p>

            {{-- Zonas vivas siempre presentes: el aviso entra y sale dentro de ellas para que el lector de pantalla lo anuncie.
                 Trabajando y listo (aviso info y ok) en la de role="status"; el error en la de role="alert" (DESIGN.md · Notices). --}}
            <div class="informe-estado" role="status" aria-live="polite">
                <template x-if="estado && estado.tipo !== 'error'">
                    <div class="aviso informe-aviso" :class="estado.tipo === 'ok' ? 'aviso--ok' : 'aviso--info'">
                        <svg x-show="estado.tipo === 'trabajando'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7.5V12l3 2"/></svg>
                        <svg x-show="estado.tipo === 'ok'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/></svg>
                        <span class="informe-aviso__texto" x-text="estado.texto"></span>
                        <span class="informe-aviso__pista" x-show="estado.tipo === 'trabajando'" aria-hidden="true"><i></i></span>
                    </div>
                </template>
            </div>
            <div class="informe-estado" role="alert">
                <template x-if="estado && estado.tipo === 'error'">
                    <div class="aviso aviso--error informe-aviso">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                        <span class="informe-aviso__texto" x-text="estado.texto"></span>
                    </div>
                </template>
            </div>
        </aside>
    </form>
</div>

<script>
document.addEventListener('alpine:init', () => {
    const URL_GENERAR = @json(route('admin.reportes.generar'));
    const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    const normal = t => String(t ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();
    // Fechas LOCALES (new Date('AAAA-MM-DD') es UTC y en Bogotá cae en el día anterior).
    const aFecha = t => { const [a, m, d] = t.split('-').map(Number); return new Date(a, m - 1, d); };
    const aTexto = f => f.getFullYear() + '-' + String(f.getMonth() + 1).padStart(2, '0') + '-' + String(f.getDate()).padStart(2, '0');
    const sumar = (f, dias) => { const x = new Date(f); x.setDate(x.getDate() + dias); return x; };
    const DESACTIVADA = ' (desactivada)'; // sufijo que pone ReportesController::index al nombre de una cuenta desactivada

    Alpine.data('informeTurnos', (secciones, asesores, inicial) => ({
        secciones, asesores,
        desde: '', hasta: '', atajo: 'mes',
        formato: 'excel',
        alcance: 'todo',
        servicios: [],
        usuarios: [],
        buscarServicio: '', buscarAsesor: '',
        trabajando: false,
        estado: null,
        init() {
            if (inicial.desde && inicial.hasta) { this.desde = inicial.desde; this.hasta = inicial.hasta; this.atajo = this.atajoDe(); }
            else this.elegirAtajo('mes');
            // Desde Gráficos con un servicio: ese servicio (y sus subservicios) ya marcado.
            if (inicial.servicio) {
                const s = this.secciones.find(s => s.id === inicial.servicio);
                const ids = s ? (s.hijos.length ? s.hijos.map(h => h.id) : [s.id]) : [inicial.servicio];
                if (this.hojas().some(h => ids.includes(h.id))) { this.alcance = 'servicios'; this.servicios = ids; }
            }
        },
        hoy() { return aTexto(new Date()); },
        rango(clave) {
            const h = new Date(); h.setHours(0, 0, 0, 0);
            const lunes = sumar(h, -((h.getDay() + 6) % 7));
            return {
                hoy: [h, h], ayer: [sumar(h, -1), sumar(h, -1)],
                semana: [lunes, h], semana_pasada: [sumar(lunes, -7), sumar(lunes, -1)],
                mes: [new Date(h.getFullYear(), h.getMonth(), 1), h],
                mes_anterior: [new Date(h.getFullYear(), h.getMonth() - 1, 1), new Date(h.getFullYear(), h.getMonth(), 0)],
            }[clave];
        },
        elegirAtajo(clave) { const [d, h] = this.rango(clave); this.desde = aTexto(d); this.hasta = aTexto(h); this.atajo = clave; },
        atajoDe() {
            return ['hoy', 'ayer', 'semana', 'semana_pasada', 'mes', 'mes_anterior']
                .find(c => { const [d, h] = this.rango(c); return aTexto(d) === this.desde && aTexto(h) === this.hasta; }) || null;
        },
        periodoValido() { return this.desde && this.hasta && this.desde <= this.hasta && this.hasta <= this.hoy(); },
        // Solo presentación: qué campo de fecha marcar en rojo (aria-invalid).
        fechaInvalida(campo) {
            const cruzadas = this.desde && this.hasta && this.desde > this.hasta;
            if (campo === 'desde') return !this.desde || this.desde > this.hoy() || !!cruzadas;
            return !this.hasta || this.hasta > this.hoy() || !!cruzadas;
        },
        dias() { return this.periodoValido() ? Math.round((aFecha(this.hasta) - aFecha(this.desde)) / 864e5) + 1 : 0; },
        fecha(t, anio = true) { const f = aFecha(t); return f.getDate() + ' ' + MESES[f.getMonth()] + (anio ? ' ' + f.getFullYear() : ''); },
        textoPeriodoCorto() {
            if (!this.periodoValido()) return 'Periodo sin definir';
            if (this.desde === this.hasta) return this.fecha(this.desde);
            const d = aFecha(this.desde), h = aFecha(this.hasta);
            if (d.getFullYear() === h.getFullYear() && d.getMonth() === h.getMonth()) return d.getDate() + ' – ' + this.fecha(this.hasta);
            return this.fecha(this.desde, d.getFullYear() !== h.getFullYear()) + ' – ' + this.fecha(this.hasta);
        },
        textoPeriodo() {
            if (!this.desde || !this.hasta) return 'Elija las dos fechas.';
            if (this.desde > this.hasta) return 'La fecha final es anterior a la inicial.';
            if (this.hasta > this.hoy()) return 'El periodo no puede terminar después de hoy.';
            return this.dias() === 1 ? 'Un día.' : this.dias() + ' días, del ' + this.fecha(this.desde, false) + ' al ' + this.fecha(this.hasta) + '.';
        },

        // ---- servicios (se envían los subservicios; una sección sin subservicios va sola)
        hojas() { return this.secciones.flatMap(s => s.hijos.length ? s.hijos : [s]); },
        idsDe(s) { return s.hijos.length ? s.hijos.map(h => h.id) : [s.id]; },
        contarMarcados(s) { return this.idsDe(s).filter(id => this.servicios.includes(id)).length; },
        estadoSeccion(s) { const n = this.contarMarcados(s), t = this.idsDe(s).length; return n === 0 ? 'nada' : n === t ? 'todo' : 'parte'; },
        alternarSeccion(s, marcar) {
            const ids = this.idsDe(s);
            this.servicios = marcar ? [...new Set([...this.servicios, ...ids])] : this.servicios.filter(id => !ids.includes(id));
        },
        marcarServicios(marcar) { this.servicios = marcar ? this.hojas().map(h => h.id) : []; },
        serviciosMarcados() { return this.servicios; },
        seccionesVisibles() {
            const q = normal(this.buscarServicio);
            return q ? this.secciones.filter(s => normal(s.nombre).includes(q) || s.hijos.some(h => normal(h.nombre).includes(q))) : this.secciones;
        },
        hijosVisibles(s) {
            const q = normal(this.buscarServicio);
            return !q || normal(s.nombre).includes(q) ? s.hijos : s.hijos.filter(h => normal(h.nombre).includes(q));
        },
        asesoresVisibles() {
            const q = normal(this.buscarAsesor);
            return q ? this.asesores.filter(a => normal(a.nombre + ' ' + a.usuario).includes(q)) : this.asesores;
        },
        nombreAsesor(a) { return a.nombre.endsWith(DESACTIVADA) ? a.nombre.slice(0, -DESACTIVADA.length) : a.nombre; },
        asesorDesactivado(a) { return a.nombre.endsWith(DESACTIVADA); },

        // ---- textos del resumen (solo presentación)
        cuenta(n, uno, varios) { return n + ' ' + (n === 1 ? uno : varios); },
        enumerar(nombres) {
            return nombres.length <= 2 ? nombres.join(' y ') : nombres.slice(0, 2).join(', ') + ' y ' + (nombres.length - 2) + ' más';
        },
        incluyeVacio() {
            return (this.alcance === 'servicios' && !this.servicios.length) || (this.alcance === 'asesores' && !this.usuarios.length);
        },
        textoAlcance() {
            if (this.alcance === 'servicios') return this.servicios.length ? this.cuenta(this.servicios.length, 'servicio', 'servicios') : 'Ningún servicio marcado';
            if (this.alcance === 'asesores') return this.usuarios.length ? this.cuenta(this.usuarios.length, 'asesor', 'asesores') : 'Ningún asesor marcado';
            return 'Todo el turnero';
        },
        detalleAlcance() {
            if (this.alcance === 'servicios') {
                if (!this.servicios.length) return 'Márquelos en «Por servicio».';
                if (this.servicios.length === this.hojas().length) return 'Todos los servicios';
                return this.enumerar(this.hojas().filter(h => this.servicios.includes(h.id)).map(h => h.nombre));
            }
            if (this.alcance === 'asesores') {
                if (!this.usuarios.length) return 'Márquelos en «Por asesor».';
                if (this.usuarios.length === this.asesores.length) return 'Todos los asesores';
                return this.enumerar(this.asesores.filter(a => this.usuarios.includes(a.id)).map(a => this.nombreAsesor(a)));
            }
            return this.cuenta(this.hojas().length, 'servicio', 'servicios') + ' · ' + this.cuenta(this.asesores.length, 'asesor', 'asesores');
        },
        listo() {
            return this.periodoValido() && (this.alcance === 'todo' || (this.alcance === 'servicios' ? this.servicios.length : this.usuarios.length) > 0);
        },
        faltante() {
            if (!this.periodoValido()) return 'Revise el periodo.';
            if (this.alcance === 'servicios') return 'Marque al menos un servicio.';
            if (this.alcance === 'asesores') return 'Marque al menos un asesor.';
            return '';
        },
        // Flechas dentro de un grupo de opciones (role="radiogroup"): pasa a la vecina y la elige.
        moverOpcion(ev, paso) {
            const botones = [...ev.currentTarget.querySelectorAll('[role="radio"]')];
            const i = botones.indexOf(document.activeElement);
            if (i < 0) return;
            const otro = botones[(i + paso + botones.length) % botones.length];
            otro.focus(); otro.click();
        },

        // La descarga va por URL (GET) en un iframe oculto, como el formulario GET de antes: así los gestores de descarga
        // (Chrono, IDM…) la toman y la bajan ellos. Con fetch + Blob no la tomaban y, por http, Chrome la dejaba al 100 %
        // sin terminar. El servidor avisa con la cookie informe_listo_<aviso> que el archivo ya salió. Si en el iframe carga
        // un documento, es un error (sesión cerrada o fallo del servidor; el .htaccess no deja mostrarlo: X-Frame-Options).
        generar() {
            if (!this.listo() || this.trabajando) return;
            this.trabajando = true;
            this.estado = { tipo: 'trabajando', texto: 'Generando el informe' + (this.dias() > 31 ? '; con un periodo largo puede tardar un poco.' : '…') };
            const aviso = Date.now().toString(36) + Math.random().toString(36).slice(2, 10);
            const datos = new URLSearchParams({ fecha_inicio: this.desde, fecha_fin: this.hasta, formato: this.formato, alcance: this.alcance, aviso });
            if (this.alcance === 'servicios') this.servicios.forEach(id => datos.append('servicios[]', id));
            if (this.alcance === 'asesores') this.usuarios.forEach(id => datos.append('usuarios[]', id));
            const cookie = 'informe_listo_' + aviso + '=';
            const marco = document.createElement('iframe');
            marco.hidden = true;
            marco.src = URL_GENERAR + '?' + datos;
            let vigia = null, limite = null;
            const terminar = estado => {
                clearInterval(vigia); clearTimeout(limite);
                document.cookie = cookie + '; Max-Age=0; path=/';
                setTimeout(() => marco.remove(), 60000); // el navegador o el gestor de descargas aún pueden estar tomándola
                this.estado = estado;
                this.trabajando = false;
            };
            vigia = setInterval(() => {
                if (document.cookie.split('; ').some(c => c.startsWith(cookie))) terminar({ tipo: 'ok', texto: 'Listo: el informe se está descargando.' });
            }, 250);
            marco.addEventListener('load', () => {
                if (this.trabajando) terminar({ tipo: 'error', texto: 'No se pudo generar el informe. Si lleva un rato sin usar el turnero, recargue la página (la sesión pudo cerrarse) e inténtelo de nuevo.' });
            });
            limite = setTimeout(() => terminar({ tipo: 'error', texto: 'El informe está tardando demasiado. Revise sus descargas o inténtelo de nuevo.' }), 10 * 60 * 1000);
            document.body.appendChild(marco);
        },
    }));
});
</script>

@push('estilos')
<style>
/* ===== Reportes (2026-11, «La sala»): el informe se arma a la izquierda y la hoja del informe, a la derecha, es la
   única pieza elevada (como la Cola del Inicio). La rejilla (.vista-lateral), la pieza elevada, los atajos sobre papel,
   el botón de pieza, los avisos, las casillas y los chips son las recetas del layout: aquí solo van medidas propias.
   Ver DESIGN.md. ===== */
.reportes-vista { font-variant-numeric: tabular-nums; }
.informe__principal { min-width: 0; display: flex; flex-direction: column; gap: 1rem; }

/* Bloques planos del formulario */
.informe-bloque { display: flex; flex-direction: column; gap: 1rem; padding: 1.25rem 1.25rem 1.125rem; min-width: 0; }
.informe-bloque__titulo { margin: 0; font-size: 1rem; font-weight: 650; color: var(--tinta); }
.informe-bloque__sub { margin: .125rem 0 0; font-size: .8125rem; color: var(--tinta-suave); text-wrap: pretty; }

/* Periodo: los atajos son pastillas sobre papel (.filtros-rapidos--papel); el azul relleno es solo de Generar */
.informe-fechas { display: flex; flex-wrap: wrap; align-items: flex-end; gap: .75rem 1rem; }
.informe-fechas__campo { flex: 0 1 12rem; min-width: 10.5rem; }
.informe-fechas .campo { font-variant-numeric: tabular-nums; }
.informe-fechas__texto {
    flex: 1 1 14rem; display: flex; align-items: center; gap: .375rem; min-height: 2.5rem; margin: 0;
    font-size: .875rem; color: var(--tinta-suave); text-wrap: pretty;
}
.informe-fechas__texto.is-error { color: var(--error); font-weight: 550; }
.informe-fechas__texto svg { width: 16px; height: 16px; flex: none; }

/* Qué incluir: tres opciones como tarjetas; la elegida con contorno azul y la marca rellena */
.informe-opciones { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .75rem; }
@media (max-width: 767px) { .informe-opciones { grid-template-columns: minmax(0, 1fr); } }
.informe-opcion {
    display: flex; flex-direction: column; align-items: flex-start; gap: .25rem; min-width: 0; padding: .875rem 1rem 1rem;
    text-align: left; font: inherit; color: var(--tinta-suave); background: #fff; border-radius: .75rem; cursor: pointer;
    box-shadow: inset 0 0 0 1px var(--linea);
    transition-property: box-shadow, background-color; transition-duration: .15s; transition-timing-function: ease-out;
}
.informe-opcion:hover { background: #f5f8fc; }
.informe-opcion__cabeza { display: flex; align-items: center; justify-content: space-between; width: 100%; margin-bottom: .375rem; }
.informe-opcion__icono { width: 20px; height: 20px; color: var(--icono); transition: color .15s ease-out; }
.informe-opcion[aria-checked="true"] .informe-opcion__icono { color: var(--azul); }
.informe-opcion__titulo { font-size: .875rem; font-weight: 650; color: var(--tinta); }
.informe-opcion__texto { font-size: .8125rem; line-height: 1.45; text-wrap: pretty; }
.informe-opcion__dato { margin-top: auto; padding-top: .5rem; font-size: .8125rem; font-weight: 600; color: var(--tinta-2); font-variant-numeric: tabular-nums; }

/* Marca de opción (la del radio): anillo en reposo, anillo grueso azul al elegir */
.informe-marca {
    width: 18px; height: 18px; flex: none; border-radius: 50%; background: #fff; box-shadow: inset 0 0 0 1.5px var(--borde);
    transition: box-shadow .15s ease-out;
}
[aria-checked="true"] > .informe-marca, [aria-checked="true"] > * > .informe-marca { box-shadow: inset 0 0 0 5px var(--azul); }

/* Selector de servicios o asesores */
.informe-selector { display: flex; flex-direction: column; gap: .75rem; padding-top: 1rem; border-top: 1px solid var(--linea-suave); }
.informe-selector__barra { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem 1rem; }
.informe-selector__buscar { margin-left: 0; flex: 1 1 16rem; }
.informe-selector__cuenta { font-size: .8125rem; color: var(--tinta-tenue); white-space: nowrap; font-variant-numeric: tabular-nums; }
.informe-selector__acciones { display: inline-flex; gap: 1rem; margin-left: auto; font-size: .875rem; }

/* Sin desplazamiento interno: la página baja y la hoja del informe (pegajosa) sigue a la vista con Generar */
.informe-grupos, .informe-asesores { list-style: none; margin: 0; padding: 0; }
.informe-grupos { display: grid; grid-template-columns: repeat(auto-fill, minmax(15rem, 1fr)); gap: .625rem; align-items: start; }
.informe-grupo { padding: .375rem .375rem .5rem; border-radius: .75rem; background: #f5f8fc; }
.informe-grupo__hijos { list-style: none; margin: .125rem 0 0; padding: 0 0 0 1.625rem; }
.informe-grupo__cuenta { margin-left: auto; font-size: .75rem; font-weight: 550; color: var(--tinta-tenue); font-variant-numeric: tabular-nums; white-space: nowrap; }
.informe-asesores { display: grid; grid-template-columns: repeat(auto-fill, minmax(14rem, 1fr)); gap: .125rem .75rem; }

/* Los nombres se parten en vez de cortarse: la casilla y el estado se alinean con la primera línea */
.informe-casilla {
    display: flex; align-items: flex-start; gap: .625rem; min-height: 2.5rem; padding: .59375rem .5rem; border-radius: .5rem;
    font-size: .875rem; line-height: 1.4; color: var(--tinta); cursor: pointer; transition: background-color .15s ease-out;
}
.informe-casilla:hover { background: #eef3fa; }
.informe-grupo .informe-casilla:hover { background: #e9f0f9; }
/* La casilla es la .casilla del layout (18 px); aquí solo baja al centro de la primera línea del nombre */
.informe-casilla .casilla { margin-top: .0625rem; }
.informe-casilla--seccion { font-weight: 650; color: var(--tinta); }
.informe-casilla__textos { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.informe-casilla__linea { overflow-wrap: break-word; text-wrap: pretty; }
.informe-casilla__nombre { margin-right: .375rem; }
.informe-casilla--asesor .informe-casilla__linea { line-height: 1.3; margin-top: .0625rem; }
.informe-casilla__usuario { font-size: .75rem; line-height: 1.3; color: var(--tinta-tenue); overflow-wrap: break-word; }
.informe-grupo__cuenta { margin-top: .125rem; }

/* La hoja del informe: la única pieza elevada (.pieza-elevada) y la columna lateral pegajosa (.vista-lateral__lado) */
.informe-hoja { display: flex; flex-direction: column; padding: 1.25rem; }
/* Si la hoja no cabe en la pantalla (avisos largos en pantallas bajas), se desplaza por dentro en vez de esconder su pie */
@media (min-width: 1100px) { .informe-hoja { max-height: calc(100vh - 3rem); overflow-y: auto; overscroll-behavior: contain; scrollbar-width: thin; } }
.informe-hoja__titulo { margin: 0; font-size: 1rem; font-weight: 650; color: var(--tinta); }
.informe-hoja__sub { margin: .125rem 0 0; font-size: .8125rem; color: var(--tinta-suave); }

.informe-resumen { margin: 1rem 0 0; }
.informe-resumen__fila { display: grid; grid-template-columns: 4.5rem minmax(0, 1fr); gap: .75rem; padding: .6875rem 0; border-top: 1px solid var(--linea-suave); }
.informe-resumen__fila:last-child { border-bottom: 1px solid var(--linea-suave); }
.informe-resumen dt { font-size: .8125rem; color: var(--tinta-tenue); padding-top: .0625rem; }
.informe-resumen dd { margin: 0; min-width: 0; display: flex; flex-direction: column; gap: .0625rem; }
.informe-resumen__valor { font-size: .875rem; font-weight: 650; color: var(--tinta); font-variant-numeric: tabular-nums; }
.informe-resumen__valor.is-falta { color: var(--alerta-tinta); }
.informe-resumen__mas { font-size: .8125rem; color: var(--tinta-suave); font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }

.informe-formato { display: flex; flex-direction: column; gap: .4375rem; margin-top: 1.125rem; }
.informe-formato__etiqueta { font-size: .8125rem; font-weight: 600; color: var(--tinta-2); }
.informe-formatos { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; }
.informe-formato__opcion {
    display: flex; align-items: center; gap: .625rem; min-width: 0; height: 3.25rem; padding: 0 .75rem; text-align: left; font: inherit;
    background: #fff; border-radius: .75rem; box-shadow: inset 0 0 0 1px var(--linea); cursor: pointer; color: var(--tinta-suave);
    transition-property: box-shadow, background-color; transition-duration: .15s; transition-timing-function: ease-out;
}
.informe-formato__opcion:hover { background: #f5f8fc; }
/* Opción elegida (alcance y formato) como la .opcion del Inicio y el .tipo de Soporte: azul claro con contorno azul de
   1.5 px. Va después de los dos :hover para que la elegida no cambie al pasar el cursor. */
.informe-opcion[aria-checked="true"], .informe-formato__opcion[aria-checked="true"] { background: var(--azul-claro); box-shadow: inset 0 0 0 1.5px var(--azul); }
.informe-formato__icono { width: 20px; height: 20px; flex: none; color: var(--icono); transition: color .15s ease-out; }
.informe-formato__opcion[aria-checked="true"] .informe-formato__icono { color: var(--azul); }
.informe-formato__textos { flex: 1; min-width: 0; display: flex; flex-direction: column; line-height: 1.2; }
.informe-formato__nombre { font-size: .875rem; font-weight: 650; color: var(--tinta); }
.informe-formato__ext { font-size: .75rem; color: var(--tinta-tenue); }
.informe-formato__opcion .informe-marca { width: 16px; height: 16px; }
.informe-formato__opcion[aria-checked="true"] .informe-marca { box-shadow: inset 0 0 0 4.5px var(--azul); }
.informe-formato__ayuda { margin: .125rem 0 0; font-size: .75rem; line-height: 1.45; color: var(--tinta-tenue); text-wrap: pretty; }

.informe-opcion:focus-visible, .informe-formato__opcion:focus-visible { outline: 2px solid var(--azul); outline-offset: 2px; }

/* Generar: la única acción azul, con la receta .btn-primario--pieza del layout (48 px, ocupado y deshabilitado incluidos) */
.informe-generar { margin-top: 1.375rem; }
.informe-falta { display: flex; align-items: center; gap: .375rem; margin: .625rem 0 0; font-size: .8125rem; font-weight: 500; color: var(--alerta-tinta); }
.informe-falta svg { width: 15px; height: 15px; flex: none; }

/* Avisos de trabajando, listo y error: la receta .aviso del layout; aquí solo el aire y la pista que corre */
.informe-aviso { position: relative; overflow: hidden; margin-top: .75rem; }
.informe-aviso__texto { min-width: 0; }
/* Mientras trabaja, una línea tenue corre por el pie del aviso: dice que sigue vivo */
.informe-aviso__pista { position: absolute; left: 0; right: 0; bottom: 0; height: 2px; overflow: hidden; background: rgba(23, 58, 106, .08); }
.informe-aviso__pista i {
    position: absolute; top: 0; bottom: 0; left: 0; width: 30%; background: rgba(23, 58, 106, .35);
    animation: informe-pista 1.6s cubic-bezier(.65, 0, .35, 1) infinite;
}
@keyframes informe-pista { from { transform: translateX(-100%); } to { transform: translateX(333.34%); } }

/* Pantallas bajas (1366x650): la hoja se compacta para que Generar y su aviso queden a la vista */
@media (min-width: 1100px) and (max-height: 760px) {
    .informe-hoja { padding-bottom: 1.125rem; }
    .informe-resumen { margin-top: .75rem; }
    .informe-resumen__fila { padding: .5rem 0; }
    .informe-formato { margin-top: .875rem; }
    .informe-generar { margin-top: 1rem; }
}

@media (prefers-reduced-motion: reduce) {
    .informe-opcion, .informe-formato__opcion, .informe-marca, .informe-casilla, .informe-opcion__icono, .informe-formato__icono { transition: none; }
    .informe-aviso__pista { display: none; }
}
</style>
@endpush
@endsection
