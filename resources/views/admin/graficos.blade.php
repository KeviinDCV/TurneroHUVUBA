@extends('layouts.admin')

@section('title', 'Gráficos')

@section('content')
{{-- Gráficos: un periodo y un servicio gobiernan TODOS los bloques. Los datos salen de App\Services\GraficosService
     (turnos por created_at, como el Inicio y Turnos) y llegan con la página; al cambiar el periodo se piden por JSON. --}}
@php
    // Esqueleto de un bloque de barras horizontales: antes de que arranque Alpine y mientras llega otro periodo.
    $filasEsqueleto = '<span class="esqueleto-fila"><span class="esqueleto" style="width: 70%"></span><span class="esqueleto" style="width: 90%"></span></span><span class="esqueleto-fila"><span class="esqueleto" style="width: 55%"></span><span class="esqueleto" style="width: 72%"></span></span><span class="esqueleto-fila"><span class="esqueleto" style="width: 80%"></span><span class="esqueleto" style="width: 60%"></span></span><span class="esqueleto-fila"><span class="esqueleto" style="width: 60%"></span><span class="esqueleto" style="width: 48%"></span></span><span class="esqueleto-fila"><span class="esqueleto" style="width: 45%"></span><span class="esqueleto" style="width: 36%"></span></span><span class="esqueleto-fila"><span class="esqueleto" style="width: 65%"></span><span class="esqueleto" style="width: 28%"></span></span><span class="esqueleto-fila"><span class="esqueleto" style="width: 50%"></span><span class="esqueleto" style="width: 20%"></span></span>';
    $esqueletoLista = '<div class="panel__cuerpo" data-esqueleto aria-hidden="true">' . $filasEsqueleto . '</div>'
        . '<div class="panel__cuerpo" x-show="cargando" x-cloak aria-hidden="true">' . $filasEsqueleto . '</div>';
@endphp
<div class="graficos-vista max-w-7xl mx-auto space-y-3" x-data="graficosVista(@js($datos), @js($opciones))">
    <h1 class="sr-only">Gráficos</h1>

    <!-- Periodo, servicio y salidas en una fila -->
    <div class="barra-vista barra-graficos">
        <div class="filtros-rapidos" role="group" aria-label="Periodo">
            @foreach ([2.5, 2.75, 3.75, 4.25, 4.5, 6, 6.5] as $ancho)
                <span class="filtro-rapido" data-esqueleto aria-hidden="true"><span class="esqueleto" style="width: {{ $ancho }}rem"></span></span>
            @endforeach
            <template x-for="p in presets" :key="p.clave">
                <button type="button" class="filtro-rapido" :aria-pressed="(preset === p.clave).toString()" @click="elegir(p.clave)" x-text="p.rotulo"></button>
            </template>
        </div>
        <form class="rango-personalizado" x-show="preset === 'personalizado'" x-cloak @submit.prevent="aplicarPersonalizado()">
            <label class="sr-only" for="g-desde">Desde</label>
            <input id="g-desde" type="date" class="campo" x-model="desdeManual" :max="hoy()">
            <span class="texto-mudo" aria-hidden="true">a</span>
            <label class="sr-only" for="g-hasta">Hasta</label>
            <input id="g-hasta" type="date" class="campo" x-model="hastaManual" :max="hoy()" :min="desdeManual">
            <button type="submit" class="btn-secundario" :disabled="!rangoManualValido()">Ver</button>
        </form>
        <div class="acciones-graficos">
            <label class="sr-only" for="g-servicio">Servicio</label>
            <select id="g-servicio" class="campo campo-servicio" x-model="servicio" @change="cargar()">
                <option value="">Todos los servicios</option>
                @foreach ($opciones as $o)
                    <option value="{{ $o['id'] }}">{{ !empty($o['hijo']) ? '   ' : '' }}{{ $o['nombre'] }}{{ !empty($o['seccion']) ? ' (toda la sección)' : '' }}{{ $o['activo'] ? '' : ' · inactivo' }}</option>
                @endforeach
            </select>
            <a class="btn-secundario" :href="urlInforme()" title="Excel o PDF con este mismo periodo y servicio">Informe</a>
            <button type="button" class="btn-secundario" @click="window.print()">Imprimir</button>
        </div>
    </div>
    <p class="aviso-error-graficos" role="alert" x-show="error" x-cloak>
        <span x-text="error"></span> <button type="button" class="enlace-panel" @click="cargar(false, pedido.desde, pedido.hasta)">Reintentar</button>
    </p>

    <!-- Cifras del periodo -->
    <section class="superficie cifras-graficos" aria-label="Resumen del periodo">
        <div class="cifra cifra--principal">
            <span class="cifra__rotulo"><span x-text="periodoTexto()"></span> <span class="cifra__hora" x-text="'· ' + datos.periodo.actualizado"></span></span>
            <span class="cifra__valor"><span x-show="!cargando" x-text="n(r().total)"></span><span class="esqueleto esqueleto-cifra" x-show="cargando" x-cloak></span> <small>turnos</small></span>
            <span class="cifra__nota" x-text="servicioTexto()"></span>
        </div>
        <div class="cifra">
            <span class="cifra__rotulo">Atendidos</span>
            <span class="cifra__valor"><span x-show="!cargando" x-text="n(r().atendidos)"></span><span class="esqueleto esqueleto-cifra" x-show="cargando" x-cloak></span></span>
            <span class="cifra__nota" x-text="r().total ? pct(r().atendidos, r().total) + ' del total' : '—'"></span>
        </div>
        <div class="cifra">
            <span class="cifra__rotulo">Espera promedio</span>
            <span class="cifra__valor" :class="{ 'cifra__valor--alerta': r().espera_seg !== null && r().espera_seg / 60 >= datos.umbral_espera }">
                <span x-show="!cargando" x-text="duracion(r().espera_seg)"></span><span class="esqueleto esqueleto-cifra" x-show="cargando" x-cloak></span></span>
            <span class="cifra__nota">hasta ser llamado</span>
        </div>
        <div class="cifra">
            <span class="cifra__rotulo">Atención promedio</span>
            <span class="cifra__valor"><span x-show="!cargando" x-text="duracion(r().atencion_seg)"></span><span class="esqueleto esqueleto-cifra" x-show="cargando" x-cloak></span></span>
            <span class="cifra__nota">por turno</span>
        </div>
        <div class="cifra">
            <span class="cifra__rotulo">Cancelados</span>
            <span class="cifra__valor"><span x-show="!cargando" x-text="n(r().cancelados)"></span><span class="esqueleto esqueleto-cifra" x-show="cargando" x-cloak></span></span>
            <span class="cifra__nota" x-text="n(r().aplazados) + ' aplazados'"></span>
        </div>
        <div class="cifra" x-show="r().transferidos > 0" x-cloak>
            <span class="cifra__rotulo">Transferidos</span>
            <span class="cifra__valor"><span x-show="!cargando" x-text="n(r().transferidos)"></span><span class="esqueleto esqueleto-cifra" x-show="cargando" x-cloak></span></span>
            <span class="cifra__nota">a otro servicio</span>
        </div>
    </section>

    <div class="rejilla-graficos">
        <!-- Turnos en el tiempo: la vista principal -->
        <section class="superficie panel panel--serie" aria-labelledby="t-serie">
            <header class="panel__cabeza">
                <h2 id="t-serie" x-text="{ hora: 'Turnos por hora', dia: 'Turnos por día', mes: 'Turnos por mes' }[datos.serie.modo]">Turnos</h2>
                <span class="leyenda"><i class="muestra muestra--atendidos"></i>Atendidos <i class="muestra muestra--resto"></i>Sin atender</span>
            </header>
            <div class="panel__cuerpo" data-esqueleto aria-hidden="true"><div class="esqueleto-columnas">@for ($i = 0; $i < 14; $i++)<span class="esqueleto" style="height: {{ [45, 70, 85, 60, 90, 75, 50, 65, 80, 55, 72, 40, 62, 48][$i] }}%"></span>@endfor</div></div>
            <div class="panel__cuerpo" x-show="cargando" x-cloak aria-hidden="true"><div class="esqueleto-columnas"><template x-for="i in 14"><span class="esqueleto" :style="'height:' + (35 + (i * 37) % 55) + '%'"></span></template></div></div>
            <div class="panel__cuerpo" x-show="!cargando" x-cloak>
                <p class="vacio-panel" x-show="!r().total">Sin turnos en este periodo.</p>
                <div class="columnas" x-show="r().total" @mouseleave="punto = null" role="img" :aria-label="descripcionSerie()">
                    <div class="columnas__rejilla" aria-hidden="true">
                        <template x-for="linea in lineas(maxSerie())"><span :style="'bottom:' + linea.pct + '%'"><b x-text="n(linea.v)"></b></span></template>
                    </div>
                    <div class="columnas__barras" :class="'columnas__barras--' + datos.serie.modo" :style="'--n:' + datos.serie.puntos.length">
                        <template x-for="(p, i) in datos.serie.puntos" :key="p.k">
                            <div class="columna" :class="{ 'columna--en-curso': p.en_curso, 'columna--finde': esFinDeSemana(p) }" @mouseenter="punto = i">
                                <span class="columna__total" :style="'height:' + alto(p.total, maxSerie()) + '%'">
                                    <span class="columna__atendidos" :style="'height:' + (p.total ? p.atendidos / p.total * 100 : 0) + '%'"></span>
                                </span>
                                <span class="columna__rotulo" x-text="rotuloPunto(p, i)"></span>
                            </div>
                        </template>
                    </div>
                    <div class="globo" x-show="punto !== null" x-cloak :style="globoEstilo()">
                        <template x-if="punto !== null">
                            <span><b x-text="rotuloLargo(datos.serie.puntos[punto])"></b><br>
                                <span x-text="n(datos.serie.puntos[punto].total) + ' turnos · ' + n(datos.serie.puntos[punto].atendidos) + ' atendidos'"></span>
                                <span x-show="datos.serie.puntos[punto].en_curso"><br>En curso</span></span>
                        </template>
                    </div>
                </div>
            </div>
        </section>

        <!-- Estados: una barra al 100 % -->
        <section class="superficie panel panel--estados" aria-labelledby="t-estados">
            <header class="panel__cabeza"><h2 id="t-estados">Estado de los turnos</h2></header>
            <div class="panel__cuerpo" data-esqueleto aria-hidden="true"><span class="esqueleto" style="height: .75rem"></span>@for ($i = 0; $i < 5; $i++)<span class="esqueleto" style="width: {{ [70, 55, 62, 48, 58][$i] }}%; margin-top: .9rem"></span>@endfor</div>
            <div class="panel__cuerpo" x-show="cargando" x-cloak aria-hidden="true"><span class="esqueleto" style="height: .75rem"></span><template x-for="i in 5"><span class="esqueleto" :style="'width:' + (45 + i * 7) + '%; margin-top: .9rem'"></span></template></div>
            <div class="panel__cuerpo" x-show="!cargando" x-cloak>
                <p class="vacio-panel" x-show="!r().total">Sin turnos en el periodo.</p>
                <div x-show="r().total">
                    <div class="barra-estados" role="img" :aria-label="estados().map(e => e.rotulo + ' ' + e.n).join(', ')">
                        <template x-for="e in estados()" :key="e.clave"><span :class="'estado--' + e.clave" :style="'width:' + (e.n / r().total * 100) + '%'" :title="e.rotulo + ': ' + n(e.n)"></span></template>
                    </div>
                    <ul class="lista-estados">
                        <template x-for="e in estados()" :key="e.clave">
                            <li :class="{ 'texto-mudo': !e.n }"><i class="punto" :class="'estado--' + e.clave"></i><span x-text="e.rotulo"></span>
                                <b x-text="n(e.n)"></b><span class="pct" x-text="pct(e.n, r().total)"></span></li>
                        </template>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Esperas por tramo, con el límite del panel -->
        <section class="superficie panel panel--esperas" aria-labelledby="t-esperas">
            <header class="panel__cabeza"><h2 id="t-esperas">Tiempo de espera</h2></header>
            <div class="panel__cuerpo" data-esqueleto aria-hidden="true">@for ($i = 0; $i < 5; $i++)<span class="esqueleto" style="width: {{ [85, 60, 40, 25, 12][$i] }}%; margin-bottom: 1rem"></span>@endfor</div>
            <div class="panel__cuerpo" x-show="cargando" x-cloak aria-hidden="true"><template x-for="i in 5"><span class="esqueleto" :style="'width:' + (95 - i * 17) + '%; margin-bottom: 1rem'"></span></template></div>
            <div class="panel__cuerpo" x-show="!cargando" x-cloak>
                <p class="vacio-panel" x-show="!totalEsperas()">Ningún turno fue llamado en el periodo.</p>
                <div x-show="totalEsperas()">
                    <ul class="barras-h barras-h--compactas">
                        <template x-for="t in datos.esperas" :key="t.tramo">
                            <li :class="{ 'fila--alerta': t.hasta === null || t.hasta > datos.umbral_espera }">
                                <span class="barras-h__nombre" x-text="t.tramo"></span>
                                <span class="barras-h__pista"><span class="barras-h__barra" :style="'width:' + ancho(t.n, maxEsperas()) + '%'"></span></span>
                                <b class="barras-h__valor" x-text="pct(t.n, totalEsperas())"></b>
                            </li>
                        </template>
                    </ul>
                    <p class="panel__pie" x-text="pct(sobreUmbral(), totalEsperas()) + ' esperó más de ' + datos.umbral_espera + ' min (' + n(totalEsperas()) + ' turnos llamados).'"></p>
                </div>
            </div>
        </section>

        <!-- Por servicio -->
        <section class="superficie panel panel--tercio" aria-labelledby="t-servicios">
            <header class="panel__cabeza"><h2 id="t-servicios">Por servicio</h2><span class="leyenda">turnos · atendidos</span></header>
            {!! $esqueletoLista !!}
            <div class="panel__cuerpo" x-show="!cargando" x-cloak>
                <p class="vacio-panel" x-show="!datos.servicios.length">Sin turnos en el periodo.</p>
                <ul class="barras-h" :class="{ 'barras-h--todas': verTodo.servicios }">
                    <template x-for="s in recortar(datos.servicios, 'servicios')" :key="s.id">
                        <li :title="(s.seccion ? s.seccion + ' · ' : '') + s.nombre + ': ' + n(s.total) + ' turnos, ' + n(s.atendidos) + ' atendidos'">
                            <span class="barras-h__nombre" x-text="s.nombre"></span>
                            <span class="barras-h__pista"><span class="barras-h__barra barras-h__barra--clara" :style="'width:' + ancho(s.total, datos.servicios[0].total) + '%'">
                                <span class="barras-h__barra" :style="'width:' + (s.total ? s.atendidos / s.total * 100 : 0) + '%'"></span></span></span>
                            <b class="barras-h__valor" x-text="n(s.total)"></b>
                        </li>
                    </template>
                </ul>
                <button type="button" class="enlace-panel ver-todo" x-show="datos.servicios.length > LIMITE" @click="verTodo.servicios = !verTodo.servicios"
                        x-text="verTodo.servicios ? 'Ver menos' : 'Ver los ' + datos.servicios.length"></button>
            </div>
        </section>

        <!-- Por asesor -->
        <section class="superficie panel panel--tercio" aria-labelledby="t-asesores">
            <header class="panel__cabeza"><h2 id="t-asesores">Atendidos por asesor</h2><span class="leyenda">atendidos · promedio</span></header>
            {!! $esqueletoLista !!}
            <div class="panel__cuerpo" x-show="!cargando" x-cloak>
                <p class="vacio-panel" x-show="!datos.asesores.length">Nadie atendió turnos en el periodo.</p>
                <ul class="barras-h">
                    <template x-for="a in recortar(datos.asesores, 'asesores')" :key="a.id">
                        <li :title="a.nombre + ': ' + n(a.atendidos) + ' atendidos' + (a.transferidos ? ', ' + n(a.transferidos) + ' transferidos' : '') + (a.atencion_seg ? ', ' + duracion(a.atencion_seg) + ' en promedio' : '')">
                            <span class="barras-h__nombre" x-text="a.nombre"></span>
                            <span class="barras-h__pista"><span class="barras-h__barra" :style="'width:' + ancho(a.atendidos, datos.asesores[0].atendidos) + '%'"></span></span>
                            <b class="barras-h__valor" x-text="n(a.atendidos)"></b>
                            <span class="barras-h__extra" x-text="duracion(a.atencion_seg)"></span>
                        </li>
                    </template>
                </ul>
                <button type="button" class="enlace-panel ver-todo" x-show="datos.asesores.length > LIMITE" @click="verTodo.asesores = !verTodo.asesores"
                        x-text="verTodo.asesores ? 'Ver menos' : 'Ver los ' + datos.asesores.length"></button>
            </div>
        </section>

        <!-- Tiempos por servicio: lo que más espera arriba -->
        <section class="superficie panel panel--tercio" aria-labelledby="t-tiempos">
            <header class="panel__cabeza"><h2 id="t-tiempos">Tiempos por servicio</h2>
                <span class="leyenda"><i class="muestra muestra--espera"></i>Espera <i class="muestra muestra--atendidos"></i>Atención</span></header>
            {!! $esqueletoLista !!}
            <div class="panel__cuerpo" x-show="!cargando" x-cloak>
                <p class="vacio-panel" x-show="!tiempos().length">Sin turnos atendidos en el periodo.</p>
                <ul class="barras-doble">
                    <template x-for="s in recortar(tiempos(), 'tiempos')" :key="s.id">
                        <li :title="s.nombre + ': espera ' + duracion(s.espera_seg) + ', atención ' + duracion(s.atencion_seg)">
                            <span class="barras-h__nombre" x-text="s.nombre"></span>
                            <span class="barras-doble__pistas">
                                <span class="barras-doble__barra barras-doble__barra--espera" :class="{ 'barras-doble__barra--alerta': s.espera_seg / 60 >= datos.umbral_espera }" :style="'width:' + ancho(s.espera_seg || 0, maxTiempo()) + '%'"></span>
                                <span class="barras-doble__barra" :style="'width:' + ancho(s.atencion_seg || 0, maxTiempo()) + '%'"></span>
                            </span>
                            <span class="barras-doble__valores"><span x-text="duracion(s.espera_seg)"></span><span x-text="duracion(s.atencion_seg)"></span></span>
                        </li>
                    </template>
                </ul>
                <button type="button" class="enlace-panel ver-todo" x-show="tiempos().length > 4" @click="verTodo.tiempos = !verTodo.tiempos"
                        x-text="verTodo.tiempos ? 'Ver menos' : 'Ver los ' + tiempos().length"></button>
            </div>
        </section>

        <!-- Horas pico (solo con más de un día: con un día, "Turnos por hora" ya lo muestra) -->
        <section class="superficie panel panel--medio" aria-labelledby="t-horas" x-show="datos.serie.modo !== 'hora' && datos.horas.length" x-cloak>
            <header class="panel__cabeza"><h2 id="t-horas">Horas pico</h2><span class="leyenda">turnos que llegan por hora, en un día promedio</span></header>
            <div class="panel__cuerpo" x-show="cargando" aria-hidden="true"><div class="esqueleto-columnas"><template x-for="i in 11"><span class="esqueleto" :style="'height:' + (30 + (i * 29) % 60) + '%'"></span></template></div></div>
            <div class="panel__cuerpo" x-show="!cargando">
                <div class="columnas columnas--bajas" role="img" :aria-label="'Hora con más llegadas: ' + horaPico()">
                    <div class="columnas__barras" :style="'--n:' + datos.horas.length">
                        <template x-for="h in datos.horas" :key="h.hora">
                            <div class="columna" :class="{ 'columna--pico': h.promedio === maxHoras() }" :title="h.hora + ':00 a ' + (h.hora + 1) + ':00 · ' + dec(h.promedio) + ' turnos en promedio'">
                                <span class="columna__total columna__total--simple" :style="'height:' + alto(h.promedio, maxHoras()) + '%'"><b x-text="dec(h.promedio)"></b></span>
                                <span class="columna__rotulo" x-text="h.hora + ':00'"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </section>

        <!-- Transferencias (UBA): de qué servicio a cuál -->
        <section class="superficie panel panel--medio" aria-labelledby="t-transferencias" x-show="datos.transferencias.total" x-cloak>
            <header class="panel__cabeza"><h2 id="t-transferencias">Transferencias</h2><span class="leyenda" x-text="n(datos.transferencias.total) + ' en el periodo'"></span></header>
            <div class="panel__cuerpo" x-show="!cargando">
                <ul class="barras-h">
                    <template x-for="(t, i) in datos.transferencias.rutas.slice(0, 6)" :key="i">
                        <li :title="t.desde + ' → ' + t.hacia + ': ' + n(t.n)">
                            <span class="barras-h__nombre barras-h__nombre--ruta"><span x-text="t.desde"></span> <span class="texto-mudo">→</span> <span x-text="t.hacia"></span></span>
                            <span class="barras-h__pista"><span class="barras-h__barra" :style="'width:' + ancho(t.n, datos.transferencias.rutas[0].n) + '%'"></span></span>
                            <b class="barras-h__valor" x-text="n(t.n)"></b>
                        </li>
                    </template>
                </ul>
            </div>
        </section>

        <!-- Canales no presenciales (no dependen del servicio) -->
        <section class="superficie panel panel--medio" aria-labelledby="t-canales" x-show="datos.canales.total_minutos" x-cloak>
            <header class="panel__cabeza"><h2 id="t-canales">Canales no presenciales</h2><span class="leyenda" x-text="duracion(datos.canales.total_minutos * 60) + ' en total'"></span></header>
            <div class="panel__cuerpo" x-show="!cargando">
                <ul class="barras-h">
                    <template x-for="(a, i) in datos.canales.asesores.slice(0, 6)" :key="i">
                        <li :title="a.nombre + ': ' + duracion(a.minutos * 60) + ' en ' + a.actividades + ' actividades'">
                            <span class="barras-h__nombre" x-text="a.nombre"></span>
                            <span class="barras-h__pista"><span class="barras-h__barra barras-h__barra--canal" :style="'width:' + ancho(a.minutos, datos.canales.asesores[0].minutos) + '%'"></span></span>
                            <b class="barras-h__valor" x-text="duracion(a.minutos * 60)"></b>
                            <span class="barras-h__extra" x-text="a.actividades + ' act.'"></span>
                        </li>
                    </template>
                </ul>
            </div>
        </section>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    const URL_BASE = @json(route('admin.graficos'));
    const URL_INFORME = @json(route('admin.reportes'));
    const numero = new Intl.NumberFormat('es-CO');
    const decimal = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 1 });
    const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    const DIAS = ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'];
    // Fechas LOCALES (new Date('AAAA-MM-DD') es UTC y en Bogotá cae en el día anterior).
    const aFecha = t => { const [a, m, d] = t.split('-').map(Number); return new Date(a, m - 1, d || 1); };
    const aTexto = f => f.getFullYear() + '-' + String(f.getMonth() + 1).padStart(2, '0') + '-' + String(f.getDate()).padStart(2, '0');
    const sumar = (f, dias) => { const x = new Date(f); x.setDate(x.getDate() + dias); return x; };

    Alpine.data('graficosVista', (inicial, opciones) => ({
        datos: inicial,
        servicio: inicial.periodo.servicio ? String(inicial.periodo.servicio) : '',
        preset: 'hoy',
        desdeManual: inicial.periodo.desde,
        hastaManual: inicial.periodo.hasta,
        cargando: false,
        error: '',
        punto: null,
        pedido: { desde: inicial.periodo.desde, hasta: inicial.periodo.hasta },   // lo último que se pidió (para Reintentar)
        verTodo: { servicios: false, asesores: false, tiempos: false },
        LIMITE: 5,
        presets: [
            { clave: 'hoy', rotulo: 'Hoy' }, { clave: 'ayer', rotulo: 'Ayer' }, { clave: '7', rotulo: '7 días' },
            { clave: '30', rotulo: '30 días' }, { clave: 'mes', rotulo: 'Este mes' }, { clave: 'mes_anterior', rotulo: 'Mes anterior' },
            { clave: 'personalizado', rotulo: 'Personalizado' },
        ],
        init() {
            this.$el.querySelectorAll('[data-esqueleto]').forEach(e => e.remove());
            this.preset = this.presetDe(this.datos.periodo.desde, this.datos.periodo.hasta);
            // Con hoy dentro del periodo, las cifras se refrescan solas cada minuto (sin esqueleto ni animación).
            setInterval(() => { if (!document.hidden && this.datos.periodo.hasta >= this.hoy()) this.cargar(true); }, 60000);
        },
        hoy() { return aTexto(new Date()); },
        rango(clave) {
            const h = new Date(); h.setHours(0, 0, 0, 0);
            switch (clave) {
                case 'hoy': return [h, h];
                case 'ayer': return [sumar(h, -1), sumar(h, -1)];
                case '7': return [sumar(h, -6), h];
                case '30': return [sumar(h, -29), h];
                case 'mes': return [new Date(h.getFullYear(), h.getMonth(), 1), h];
                case 'mes_anterior': return [new Date(h.getFullYear(), h.getMonth() - 1, 1), new Date(h.getFullYear(), h.getMonth(), 0)];
            }
            return null;
        },
        presetDe(desde, hasta) {
            const p = this.presets.find(p => { const r = this.rango(p.clave); return r && aTexto(r[0]) === desde && aTexto(r[1]) === hasta; });
            return p ? p.clave : 'personalizado';
        },
        elegir(clave) {
            this.preset = clave;
            if (clave === 'personalizado') { this.desdeManual = this.datos.periodo.desde; this.hastaManual = this.datos.periodo.hasta; return; }
            const [d, h] = this.rango(clave);
            this.cargar(false, aTexto(d), aTexto(h));
        },
        rangoManualValido() { return this.desdeManual && this.hastaManual && this.desdeManual <= this.hastaManual && this.hastaManual <= this.hoy(); },
        aplicarPersonalizado() { if (this.rangoManualValido()) this.cargar(false, this.desdeManual, this.hastaManual); },
        consulta(desde, hasta) {
            const p = new URLSearchParams({ desde, hasta });
            if (this.servicio) p.set('servicio', this.servicio);
            return p;
        },
        // Pide el periodo al servidor. Si tarda, cada bloque muestra su esqueleto; en silencio no hay esqueleto.
        cargar(silencioso = false, desde = this.datos.periodo.desde, hasta = this.datos.periodo.hasta) {
            const p = this.consulta(desde, hasta);
            this.pedido = { desde, hasta };
            history.replaceState(null, '', location.pathname + '?' + p);
            if (this._pidiendo) this._pidiendo.abort();
            this._pidiendo = new AbortController();
            clearTimeout(this._esqueleto);
            if (!silencioso) this._esqueleto = setTimeout(() => { this.cargando = true; }, 150);
            fetch(URL_BASE + '?' + p, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store', signal: this._pidiendo.signal })
                .then(r => r.ok ? r.json() : Promise.reject(r.status))
                .then(d => { this.datos = d; this.error = ''; this.punto = null; })
                .catch(e => { if (e && e.name === 'AbortError') return; if (!silencioso) this.error = e === 419 || e === 401 ? 'La sesión expiró. Recarga la página.' : 'No se pudieron cargar los datos.'; })
                .finally(() => { clearTimeout(this._esqueleto); this.cargando = false; });
        },
        urlInforme() { return URL_INFORME + '?' + this.consulta(this.datos.periodo.desde, this.datos.periodo.hasta); },

        // ---- textos
        r() { return this.datos.resumen; },
        n(x) { return x === null || x === undefined ? '—' : numero.format(x); },
        dec(x) { return decimal.format(x); },
        pct(a, b) { return b ? decimal.format(Math.round(a / b * 1000) / 10) + ' %' : '—'; },
        duracion(seg) {
            if (seg === null || seg === undefined) return '—';
            const m = seg / 60;
            if (m < 1) return Math.round(seg) + ' s';
            if (m < 60) return decimal.format(Math.round(m * 10) / 10) + ' min';
            const h = Math.floor(m / 60), resto = Math.round(m % 60);
            return h + ' h' + (resto ? ' ' + resto + ' min' : '');
        },
        fechaCorta(t, conAnio) { const f = aFecha(t); return f.getDate() + ' ' + MESES[f.getMonth()] + (conAnio ? ' ' + f.getFullYear() : ''); },
        periodoTexto() {
            const { desde, hasta } = this.datos.periodo;
            if (desde === hasta) return desde === this.hoy() ? 'Hoy, ' + this.fechaCorta(desde, true) : this.fechaCorta(desde, true);
            const d = aFecha(desde), h = aFecha(hasta);
            if (d.getFullYear() === h.getFullYear() && d.getMonth() === h.getMonth()) return d.getDate() + ' – ' + this.fechaCorta(hasta, true);
            return this.fechaCorta(desde, d.getFullYear() !== h.getFullYear()) + ' – ' + this.fechaCorta(hasta, true);
        },
        servicioTexto() {
            const o = opciones.find(o => String(o.id) === this.servicio);
            return o ? o.nombre + (o.seccion ? ' (sección)' : '') : 'Todos los servicios';
        },

        // ---- serie
        maxSerie() { return Math.max(0, ...this.datos.serie.puntos.map(p => p.total)); },
        alto(v, max) { return max ? Math.max(v > 0 ? 1.5 : 0, v / this.techo(max) * 100) : 0; },   // columnas con eje
        ancho(v, max) { return max ? Math.max(v > 0 ? 2 : 0, v / max * 100) : 0; },                    // listas: el mayor llena la pista
        techo(max) { const e = Math.pow(10, Math.floor(Math.log10(max || 1))); return [1, 2, 2.5, 5, 10].map(f => f * e).find(x => x >= max) || max; },
        lineas(max) { if (!max) return []; const t = this.techo(max); return [0, t / 2, t].map(v => ({ v, pct: v / t * 100 })); },
        esFinDeSemana(p) { if (this.datos.serie.modo !== 'dia') return false; const d = aFecha(p.k).getDay(); return d === 0 || d === 6; },
        rotuloPunto(p, i) {
            const modo = this.datos.serie.modo, total = this.datos.serie.puntos.length;
            if (modo === 'hora') return p.k + ':00';
            if (modo === 'mes') { const f = aFecha(p.k); return MESES[f.getMonth()] + (f.getMonth() === 0 || i === 0 ? ' ' + String(f.getFullYear()).slice(2) : ''); }
            const f = aFecha(p.k), paso = total > 45 ? 7 : total > 16 ? 2 : 1;
            if (i % paso && i !== total - 1) return '';
            return total <= 8 ? DIAS[f.getDay()] + ' ' + f.getDate() : f.getDate() + (f.getDate() <= paso || i === 0 ? ' ' + MESES[f.getMonth()] : '');
        },
        rotuloLargo(p) {
            if (!p) return '';
            const modo = this.datos.serie.modo;
            if (modo === 'hora') return 'De ' + p.k + ':00 a ' + (Number(p.k) + 1) + ':00';
            if (modo === 'mes') { const f = aFecha(p.k); return MESES[f.getMonth()] + ' ' + f.getFullYear(); }
            const f = aFecha(p.k); return DIAS[f.getDay()] + ' ' + this.fechaCorta(p.k, true);
        },
        globoEstilo() {
            const total = this.datos.serie.puntos.length;
            const x = (this.punto + .5) / total * 100;
            return 'left: clamp(4rem, calc(2rem + (100% - 2rem) * ' + (x / 100) + '), calc(100% - 4rem))';
        },
        descripcionSerie() {
            const pts = this.datos.serie.puntos; if (!pts.length) return '';
            const max = pts.reduce((a, b) => b.total > a.total ? b : a);
            return 'Máximo: ' + this.rotuloLargo(max) + ' con ' + this.n(max.total) + ' turnos';
        },

        // ---- bloques
        estados() {
            const r = this.r();
            return [
                { clave: 'atendido', rotulo: 'Atendidos', n: r.atendidos },
                { clave: 'llamado', rotulo: 'En atención', n: r.en_atencion },
                { clave: 'pendiente', rotulo: 'En espera', n: r.en_espera },
                { clave: 'aplazado', rotulo: 'Aplazados', n: r.aplazados },
                { clave: 'cancelado', rotulo: 'Cancelados', n: r.cancelados },
            ];
        },
        totalEsperas() { return this.datos.esperas.reduce((s, t) => s + t.n, 0); },
        maxEsperas() { return Math.max(0, ...this.datos.esperas.map(t => t.n)); },
        sobreUmbral() { return this.datos.esperas.filter(t => t.hasta === null || t.hasta > this.datos.umbral_espera).reduce((s, t) => s + t.n, 0); },
        recortar(lista, clave) { return this.verTodo[clave] ? lista : lista.slice(0, clave === 'tiempos' ? 4 : this.LIMITE); },
        tiempos() { return this.datos.servicios.filter(s => s.espera_seg !== null || s.atencion_seg !== null).slice().sort((a, b) => (b.espera_seg || 0) - (a.espera_seg || 0)); },
        maxTiempo() { return Math.max(0, ...this.tiempos().flatMap(s => [s.espera_seg || 0, s.atencion_seg || 0])); },
        maxHoras() { return Math.max(0, ...this.datos.horas.map(h => h.promedio)); },
        horaPico() { const h = this.datos.horas.find(h => h.promedio === this.maxHoras()); return h ? h.hora + ':00' : '—'; },
    }));
});
</script>

@push('estilos')
<style>
[x-cloak] { display: none !important; }
.graficos-vista { font-variant-numeric: tabular-nums; }

/* Barra: presets, rango personalizado, servicio y salidas */
.barra-graficos { gap: .5rem .75rem; }
.barra-graficos .filtro-rapido { height: 2.25rem; padding: 0 .75rem; }
.rango-personalizado { display: flex; align-items: center; gap: .4rem; }
.rango-personalizado .campo { width: 9.5rem; height: 2.25rem; }
.rango-personalizado .btn-secundario { height: 2.25rem; }
.acciones-graficos { display: flex; align-items: center; gap: .5rem; margin-left: auto; }
.campo-servicio { width: 14rem; height: 2.25rem; }
.acciones-graficos .btn-secundario { height: 2.25rem; padding: 0 .8rem; }
.aviso-error-graficos { padding: .5rem .8rem; border-radius: .5rem; background: #ffefed; color: #901e1c; font-size: .875rem; }

/* Cifras: una principal y el resto en columnas separadas por filetes */
.cifras-graficos { display: grid; grid-auto-flow: column; grid-auto-columns: minmax(0, 1fr); grid-template-columns: minmax(0, 1.5fr); }
.cifra { display: flex; flex-direction: column; gap: .05rem; padding: .55rem 1rem; min-width: 0; }
.cifra__hora { color: #9ca3af; font-weight: 500; }
.cifra + .cifra { border-left: 1px solid #eef1f6; }
.cifra__rotulo { font-size: .75rem; font-weight: 600; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cifra__valor { font-size: 1.375rem; font-weight: 700; line-height: 1.2; color: #0f2547; white-space: nowrap; }
.cifra__valor small { font-size: .8125rem; font-weight: 500; color: #6b7280; }
.cifra--principal .cifra__valor { font-size: 1.625rem; }
.cifra--principal .cifra__rotulo { color: #064b9e; }
.cifra__valor--alerta { color: #b7191c; }
.cifra__nota { font-size: .75rem; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.esqueleto-cifra { display: inline-block; width: 3.5rem; height: 1.25rem; vertical-align: middle; }

/* Rejilla: serie ancha + estados + esperas; luego tres tercios; luego mitades opcionales */
.rejilla-graficos { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: .75rem; }
.panel { display: flex; flex-direction: column; min-width: 0; padding: .65rem 1rem .7rem; height: 13rem; }
.panel--serie { grid-column: span 6; }
.panel--estados, .panel--esperas { grid-column: span 3; }
.panel--tercio { grid-column: span 4; }
.panel--medio { grid-column: span 6; height: auto; min-height: 12rem; }
@media (max-width: 1100px) {
    .panel--serie { grid-column: span 12; }
    .panel--estados, .panel--esperas, .panel--medio { grid-column: span 6; }
    .panel--tercio { grid-column: span 6; }
}
@media (max-width: 767px) { .rejilla-graficos > .panel { grid-column: span 12; height: auto; min-height: 12rem; } .cifras-graficos { grid-auto-flow: row; grid-template-columns: 1fr 1fr; } }
.panel__cabeza { display: flex; align-items: baseline; justify-content: space-between; gap: .75rem; margin-bottom: .5rem; }
.panel__cabeza h2 { font-size: .875rem; font-weight: 650; color: #0f2547; white-space: nowrap; }
.leyenda { display: inline-flex; align-items: center; gap: .35rem; font-size: .75rem; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.muestra { display: inline-block; width: .6rem; height: .6rem; border-radius: .15rem; margin-left: .35rem; }
.muestra--atendidos { background: #064b9e; }
.muestra--resto { background: #c9d7ec; }
.muestra--espera { background: #e3a008; }
.panel__cuerpo { flex: 1; min-height: 0; display: flex; flex-direction: column; position: relative; }
.panel__pie { margin-top: auto; padding-top: .5rem; font-size: .75rem; color: #6b7280; }
.vacio-panel { margin: auto; font-size: .8125rem; color: #6b7280; text-align: center; }
.ver-todo { align-self: flex-start; margin-top: .35rem; font-size: .75rem; }

/* Columnas (serie y horas pico) */
.columnas { position: relative; flex: 1; min-height: 0; display: flex; flex-direction: column; padding-left: 2rem; }
.columnas__rejilla { position: absolute; left: 0; right: 0; top: 0; bottom: 1.25rem; pointer-events: none; }
.columnas__rejilla span { position: absolute; left: 2rem; right: 0; border-top: 1px dashed #e3e8f0; }
.columnas__rejilla b { position: absolute; right: calc(100% + .35rem); top: -.5rem; font-size: .6875rem; font-weight: 500; color: #9ca3af; }
.columnas__barras { flex: 1; min-height: 0; display: grid; grid-template-columns: repeat(var(--n), minmax(0, 1fr)); grid-template-rows: minmax(0, 1fr); gap: 3px; }
.columnas__barras--dia { gap: 2px; }
/* la etiqueta va en el relleno inferior: la altura % de la barra se mide sobre el resto */
.columna { position: relative; display: flex; flex-direction: column; justify-content: flex-end; min-width: 0; height: 100%; padding-bottom: 1.25rem; cursor: default; }
.columna__total { position: relative; display: flex; flex-direction: column; justify-content: flex-end; background: #c9d7ec; border-radius: 3px 3px 0 0; min-height: 0; max-width: 3rem; width: 100%; align-self: center; }
.columna__atendidos { background: #064b9e; border-radius: 3px 3px 0 0; }
.columna__rotulo { position: absolute; bottom: 0; transform: translateY(0); font-size: .6875rem; color: #6b7280; white-space: nowrap; }
.columna .columna__rotulo { left: 50%; transform: translateX(-50%); }
.columna:hover .columna__total { filter: brightness(.92); }
.columna--en-curso .columna__total { opacity: .55; }
.columna--finde .columna__rotulo { color: #b0b7c3; }
.columna__total--simple { background: #9db8dd; }
.columna--pico .columna__total--simple { background: #064b9e; }
.columna__total--simple b { position: absolute; top: -1rem; left: 50%; transform: translateX(-50%); font-size: .6875rem; font-weight: 600; color: #374151; }
.columnas--bajas { flex: none; height: 9rem; }
.globo { position: absolute; top: 0; transform: translateX(-50%); z-index: 3; padding: .4rem .6rem; border-radius: .45rem; background: #0f2547; color: #ffffff;
         font-size: .75rem; line-height: 1.4; white-space: nowrap; pointer-events: none; box-shadow: 0 8px 20px -10px rgba(15, 37, 71, .5); }
.esqueleto-columnas { flex: 1; display: flex; align-items: flex-end; gap: .4rem; padding: 0 0 1.25rem 2rem; }
.esqueleto-columnas .esqueleto { flex: 1; border-radius: 3px 3px 0 0; }
.esqueleto-fila { display: grid; grid-template-columns: 10.5rem minmax(0, 1fr); gap: .5rem; align-items: center; min-height: 1.3rem; margin-bottom: .3rem; }

/* Estados: barra al 100 % con los mismos colores de Turnos */
.barra-estados { display: flex; height: .75rem; border-radius: .375rem; overflow: hidden; background: #eef1f6; gap: 2px; }
.barra-estados span { min-width: 2px; }
.estado--atendido { background: #00a63e; }
.estado--llamado { background: #155dfc; }
.estado--pendiente { background: #d08700; }
.estado--aplazado { background: #f54900; }
.estado--cancelado { background: #e7000b; }
.lista-estados { margin-top: .7rem; display: flex; flex-direction: column; gap: .3rem; font-size: .8125rem; }
.lista-estados li { display: grid; grid-template-columns: .9rem 1fr auto 3.5rem; align-items: center; gap: .35rem; }
.lista-estados .punto { width: .5rem; height: .5rem; border-radius: 9999px; }
.lista-estados b { font-weight: 600; color: #0f2547; }
.lista-estados .pct { text-align: right; color: #6b7280; font-size: .75rem; }

/* Barras horizontales: nombre, pista y valor al final */
.barras-h { display: flex; flex-direction: column; gap: .3rem; min-height: 0; overflow: hidden; }
.barras-h--todas { overflow-y: auto; }
.barras-h li { display: grid; grid-template-columns: minmax(0, 10.5rem) minmax(0, 1fr) 2.75rem auto; align-items: center; gap: .5rem; font-size: .8125rem; min-height: 1.3rem; }
.barras-h--compactas li { grid-template-columns: 5.25rem minmax(0, 1fr) 3rem; }
.barras-h__nombre { color: #374151; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.panel--medio .barras-h li { grid-template-columns: minmax(0, 17rem) minmax(0, 1fr) minmax(3rem, max-content) auto; }
.panel--medio .barras-h__valor { white-space: nowrap; }
.barras-h__pista { height: .55rem; border-radius: .3rem; background: #f1f4f8; overflow: hidden; }
.barras-h__barra { display: block; height: 100%; border-radius: .3rem; background: #064b9e; }
.barras-h__barra--clara { background: #c9d7ec; }
.barras-h__barra--canal { background: #7c5cc4; }
.barras-h__valor { text-align: right; font-weight: 600; color: #0f2547; }
.barras-h__extra { font-size: .75rem; color: #6b7280; white-space: nowrap; min-width: 3.25rem; text-align: right; }
.fila--alerta .barras-h__barra { background: #d08700; }
.fila--alerta .barras-h__nombre { color: #92400e; }

/* Tiempos por servicio: dos barras finas (espera y atención) */
.barras-doble { display: flex; flex-direction: column; gap: .45rem; min-height: 0; overflow: hidden; }
.barras-doble li { display: grid; grid-template-columns: minmax(0, 10.5rem) minmax(0, 1fr) 4.25rem; align-items: center; gap: .5rem; font-size: .8125rem; }
.barras-doble__pistas { display: flex; flex-direction: column; gap: 2px; }
.barras-doble__barra { display: block; height: .3rem; border-radius: .2rem; background: #064b9e; min-width: 2px; }
.barras-doble__barra--espera { background: #e3a008; }
.barras-doble__barra--alerta { background: #d24a1a; }
.barras-doble__valores { display: flex; flex-direction: column; font-size: .6875rem; line-height: 1.15; color: #6b7280; text-align: right; }

/* Impresión: solo el contenido, con el periodo arriba */
@media print {
    #admin-sidebar, .header-responsive, .skip-link, .barra-graficos, .ver-todo, .globo { display: none !important; }
    .main-content { margin: 0 !important; padding: 0 !important; }
    body { background: #ffffff !important; }
    .superficie { box-shadow: none !important; border: 1px solid #e5e7eb; }
    .panel { break-inside: avoid; }
    .rejilla-graficos { grid-template-columns: repeat(12, 1fr); }
}
</style>
@endpush
@endsection
