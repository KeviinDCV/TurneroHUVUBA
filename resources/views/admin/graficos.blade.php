@extends('layouts.admin')

@section('title', 'Gráficos')

@section('acciones_pagina')
    {{-- Salidas del periodo, en voz baja como en el Inicio. El enlace del informe lo mantiene al día graficosVista
         (x-effect → sincronizarInforme) con el periodo y el servicio que se están viendo. --}}
    <a id="g-informe" class="btn-secundario" title="Excel o PDF con este mismo periodo y servicio"
       href="{{ route('admin.reportes', array_filter(['desde' => $datos['periodo']['desde'] ?? null, 'hasta' => $datos['periodo']['hasta'] ?? null, 'servicio' => $datos['periodo']['servicio'] ?? null])) }}">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M9 13h6"/><path d="M9 17h4"/></svg>
        Informe
    </a>
    <button type="button" class="btn-secundario" @click="window.print()">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 9V3h10v6"/><path d="M7 18H5a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M7 14h10v7H7z"/></svg>
        Imprimir
    </button>
@endsection

@section('content')
{{-- Gráficos: un periodo y un servicio gobiernan TODOS los bloques. Los datos salen de App\Services\GraficosService
     (turnos por created_at, como el Inicio y Turnos) y llegan con la página; al cambiar el periodo se piden por JSON. --}}
@php
    // Esqueleto de una lista de barras (nombre · pista · cifra): antes de que arranque Alpine y mientras llega otro periodo.
    $filasEsqueleto = '<span class="esqueleto-fila esqueleto-fila--cabeza"><span class="esqueleto" style="width: 4.5rem"></span><span></span><span class="esqueleto" style="width: 3rem; justify-self: end"></span></span>';
    foreach ([[70, 92], [56, 74], [80, 60], [62, 48], [46, 34]] as [$nombre, $barra]) {
        $filasEsqueleto .= '<span class="esqueleto-fila"><span class="esqueleto" style="width: ' . $nombre . '%"></span><span class="esqueleto" style="width: ' . $barra . '%"></span><span class="esqueleto" style="width: 1.75rem; justify-self: end"></span></span>';
    }
    $esqueletoLista = '<div class="grafico__cuerpo" data-esqueleto aria-hidden="true">' . $filasEsqueleto . '</div>'
        . '<div class="grafico__cuerpo" x-show="cargando" x-cloak aria-hidden="true">' . $filasEsqueleto . '</div>';

    // Los textos del resumen que Blade ya sabe (los mismos de periodoTexto, servicioTexto y textoActualizado del JS),
    // escritos dentro de su x-text para que nunca salgan vacíos antes de que arranque Alpine.
    $periodoInicial = $datos['periodo'] ?? [];
    $hoyServidor = now()->toDateString();
    $esHoyInicial = ($periodoInicial['desde'] ?? null) === $hoyServidor && ($periodoInicial['hasta'] ?? null) === $hoyServidor;
    $servicioInicial = collect($opciones)->first(fn ($o) => (string) $o['id'] === (string) ($periodoInicial['servicio'] ?? ''));
    $servicioTextoInicial = $servicioInicial ? $servicioInicial['nombre'] . (!empty($servicioInicial['seccion']) ? ' (sección)' : '') : 'Todos los servicios';
    $actualizadoInicial = '';
    if (!empty($periodoInicial['actualizado'])) {
        [$hh, $mm] = array_map('intval', explode(':', $periodoInicial['actualizado']) + [1 => 0]);
        $actualizadoInicial = (($periodoInicial['hasta'] ?? '') >= $hoyServidor ? 'Al día a las ' : 'Consultado a las ')
            . (($hh % 12) ?: 12) . ':' . str_pad((string) $mm, 2, '0', STR_PAD_LEFT) . ($hh < 12 ? ' a. m.' : ' p. m.');
    }
@endphp
<div class="graficos-vista" x-data="graficosVista(@js($datos), @js($opciones))" x-effect="sincronizarInforme()">

    <!-- Periodo y servicio: una fila que gobierna todo lo de abajo -->
    <div class="barra-vista barra-graficos">
        <div class="filtros-rapidos" role="group" aria-label="Periodo">
            @foreach ([2.5, 2.75, 3.75, 4.25, 4.5, 6, 6.5] as $ancho)
                <span class="filtro-rapido" data-esqueleto aria-hidden="true"><span class="esqueleto" style="width: {{ $ancho }}rem"></span></span>
            @endforeach
            <template x-for="p in presets" :key="p.clave">
                <button type="button" class="filtro-rapido" :aria-pressed="(preset === p.clave).toString()" @click="elegir(p.clave)" x-text="p.rotulo"></button>
            </template>
        </div>
        <div class="acciones-graficos">
            <label class="rotulo-barra" for="g-servicio">Servicio</label>
            <select id="g-servicio" class="campo campo-servicio" x-model="servicio" @change="cargar()">
                <option value="">Todos los servicios</option>
                @foreach ($opciones as $o)
                    <option value="{{ $o['id'] }}">{{ !empty($o['hijo']) ? '   ' : '' }}{{ $o['nombre'] }}{{ !empty($o['seccion']) ? ' (toda la sección)' : '' }}{{ $o['activo'] ? '' : ' · inactivo' }}</option>
                @endforeach
            </select>
        </div>
        <form class="rango-personalizado" x-show="preset === 'personalizado'" x-cloak @submit.prevent="aplicarPersonalizado()">
            <label class="rotulo-barra" for="g-desde">Desde</label>
            <input id="g-desde" type="date" class="campo" x-model="desdeManual" :max="hoy()">
            <label class="rotulo-barra" for="g-hasta">Hasta</label>
            <input id="g-hasta" type="date" class="campo" x-model="hastaManual" :max="hoy()" :min="desdeManual">
            <button type="submit" class="btn-secundario" :disabled="!rangoManualValido()">Ver</button>
            <span class="form-ayuda" x-show="desdeManual && hastaManual && !rangoManualValido()">Revise las fechas: «Desde» va antes de «Hasta» y ninguna pasa de hoy.</span>
        </form>
    </div>
    <p class="aviso aviso--error aviso-graficos" role="alert" x-show="error" x-cloak>
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5"/><path d="M12 16.5h.01"/></svg>
        <span x-text="error"></span> <button type="button" class="enlace-panel" @click="cargar(false, pedido.desde, pedido.hasta)">Reintentar</button>
    </p>

    <!-- Cifras del periodo: qué se está viendo y sus números, como las cifras del Inicio -->
    <section class="resumen-graficos" aria-labelledby="t-periodo">
        <div class="resumen-graficos__cabeza">
            <h2 id="t-periodo" class="resumen-graficos__titulo">
                <span x-text="periodoTexto()">{!! $esHoyInicial ? 'Hoy' : '<span class="esqueleto esqueleto--etiqueta" style="width: 9rem"></span>' !!}</span>
                <span class="resumen-graficos__servicio" x-text="'· ' + servicioTexto()">· {{ $servicioTextoInicial }}</span>
            </h2>
            <p class="resumen-graficos__hora"><span x-text="textoActualizado()">{{ $actualizadoInicial }}</span><span class="resumen-graficos__renueva" x-show="datos.periodo.hasta >= hoy()"> · se renueva cada minuto</span></p>
        </div>
        <div class="cifras-graficos">
            <p class="cifra">
                <span><b class="cifra__n"><span x-show="!cargando" x-text="n(r().total)"></span><span class="esqueleto esqueleto-cifra" x-show="cargando" x-cloak></span></b> turnos</span>
                <span class="cifra__mas" x-text="r().prioritarios ? plural(r().prioritarios, 'prioritario', 'prioritarios') : 'Ninguno prioritario'"></span>
            </p>
            <p class="cifra">
                <span><b class="cifra__n"><span x-show="!cargando" x-text="n(r().atendidos)"></span><span class="esqueleto esqueleto-cifra" x-show="cargando" x-cloak></span></b> atendidos</span>
                <span class="cifra__mas" x-text="r().total ? pct(r().atendidos, r().total) + ' del total' : '—'"></span>
            </p>
            <p class="cifra">
                <span><b class="cifra__n" :class="{ 'is-alerta': esperaAlta() }"><span x-show="!cargando" x-text="duracion(r().espera_seg)"></span><span class="esqueleto esqueleto-cifra" x-show="cargando" x-cloak></span></b> de espera</span>
                <span class="cifra__mas" :class="{ 'is-alerta': esperaAlta() }" x-text="esperaAlta() ? 'Pasa el límite de ' + datos.umbral_espera + ' min' : 'promedio hasta el llamado'"></span>
            </p>
            <p class="cifra">
                <span><b class="cifra__n"><span x-show="!cargando" x-text="duracion(r().atencion_seg)"></span><span class="esqueleto esqueleto-cifra" x-show="cargando" x-cloak></span></b> de atención</span>
                <span class="cifra__mas">promedio por turno</span>
            </p>
            <p class="cifra">
                <span><b class="cifra__n"><span x-show="!cargando" x-text="n(r().cancelados)"></span><span class="esqueleto esqueleto-cifra" x-show="cargando" x-cloak></span></b> cancelados</span>
                <span class="cifra__mas" x-text="plural(r().aplazados, 'aplazado', 'aplazados')"></span>
            </p>
            <p class="cifra" x-show="r().transferidos > 0" x-cloak>
                <span><b class="cifra__n"><span x-show="!cargando" x-text="n(r().transferidos)"></span><span class="esqueleto esqueleto-cifra" x-show="cargando" x-cloak></span></b> transferidos</span>
                <span class="cifra__mas">a otro servicio</span>
            </p>
        </div>
    </section>

    <div class="rejilla-graficos">
        <!-- Turnos en el tiempo: la pieza principal (la única elevada de la vista) -->
        <section class="pieza-elevada grafico grafico--serie" aria-labelledby="t-serie">
            <header class="grafico__cabeza">
                <div>
                    <h2 id="t-serie" class="grafico__titulo" x-text="{ hora: 'Turnos por hora', dia: 'Turnos por día', mes: 'Turnos por mes' }[datos.serie.modo]">{{ ['hora' => 'Turnos por hora', 'dia' => 'Turnos por día', 'mes' => 'Turnos por mes'][$datos['serie']['modo'] ?? 'hora'] ?? 'Turnos' }}</h2>
                    <p class="grafico__sub" x-text="'Turnos que llegaron cada ' + { hora: 'hora', dia: 'día', mes: 'mes' }[datos.serie.modo] + ' y cuántos ya se atendieron'">Turnos que llegaron cada {{ ['hora' => 'hora', 'dia' => 'día', 'mes' => 'mes'][$datos['serie']['modo'] ?? 'hora'] ?? 'hora' }} y cuántos ya se atendieron</p>
                </div>
                <p class="leyenda">
                    <span class="leyenda__item"><i class="muestra muestra--atendidos"></i>Atendidos</span>
                    <span class="leyenda__item"><i class="muestra muestra--resto"></i>Sin atender</span>
                </p>
            </header>
            <div class="grafico__cuerpo" data-esqueleto aria-hidden="true"><div class="esqueleto-columnas">@for ($i = 0; $i < 14; $i++)<span class="esqueleto" style="height: {{ [45, 70, 85, 60, 90, 75, 50, 65, 80, 55, 72, 40, 62, 48][$i] }}%"></span>@endfor</div></div>
            <div class="grafico__cuerpo" x-show="cargando" x-cloak aria-hidden="true"><div class="esqueleto-columnas"><template x-for="i in 14"><span class="esqueleto" :style="'height:' + (35 + (i * 37) % 55) + '%'"></span></template></div></div>
            <div class="grafico__cuerpo" x-show="!cargando" x-cloak>
                <p class="vacio-grafico" x-show="!r().total">Sin turnos en este periodo. Pruebe con otro periodo o servicio.</p>
                <div class="columnas" x-show="r().total" @mouseleave="punto = null" role="img" :aria-label="descripcionSerie()">
                    <div class="columnas__rejilla" aria-hidden="true">
                        <template x-for="linea in lineas(maxSerie())"><span :style="'bottom:' + linea.pct + '%'"><b x-text="n(linea.v)"></b></span></template>
                    </div>
                    <div class="columnas__barras" :class="'columnas__barras--' + datos.serie.modo" :style="'--n:' + datos.serie.puntos.length">
                        <template x-for="(p, i) in datos.serie.puntos" :key="p.k">
                            <div class="columna" :class="{ 'columna--en-curso': p.en_curso, 'columna--finde': esFinDeSemana(p), 'columna--activa': punto === i }" @mouseenter="punto = i">
                                <span class="columna__curso" x-show="p.en_curso">en curso</span>
                                <b class="columna__valor" x-show="i === indiceMax() && p.total > 0" x-text="n(p.total)"></b>
                                <span class="columna__total" :style="'height:' + alto(p.total, maxSerie()) + '%'">
                                    <span class="columna__atendidos" :style="'height:' + (p.total ? p.atendidos / p.total * 100 : 0) + '%'"></span>
                                </span>
                                <span class="columna__rotulo" x-text="rotuloPunto(p, i)"></span>
                            </div>
                        </template>
                    </div>
                    {{-- Globo: todo pasa por puntoActivo(), que da null si el punto ya no existe (al llegar otro periodo
                         con el cursor encima, los efectos internos corrían antes que el x-if y fallaban) --}}
                    <div class="globo" x-show="puntoActivo()" x-cloak :style="globoEstilo()">
                        <b x-text="rotuloLargo(puntoActivo())"></b><br>
                        <span x-text="textoGlobo()"></span>
                        <span x-show="puntoActivo() && puntoActivo().en_curso"><br>En curso</span>
                    </div>
                </div>
                {{-- La misma serie como tabla, para lectores de pantalla (el gráfico solo dice el máximo) --}}
                <table class="sr-only" x-show="r().total">
                    <caption x-text="{ hora: 'Turnos por hora', dia: 'Turnos por día', mes: 'Turnos por mes' }[datos.serie.modo]"></caption>
                    <thead><tr><th scope="col" x-text="{ hora: 'Hora', dia: 'Día', mes: 'Mes' }[datos.serie.modo]"></th><th scope="col">Turnos</th><th scope="col">Atendidos</th></tr></thead>
                    <tbody>
                        <template x-for="p in datos.serie.puntos" :key="'t' + p.k">
                            <tr><td x-text="rotuloLargo(p)"></td><td x-text="n(p.total)"></td><td x-text="n(p.atendidos)"></td></tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Estados: una barra al 100 % y la lista con cifras -->
        <section class="superficie grafico grafico--estados" aria-labelledby="t-estados">
            <header class="grafico__cabeza">
                <div>
                    <h2 id="t-estados" class="grafico__titulo">Estado de los turnos</h2>
                    <p class="grafico__sub">Cómo va cada turno del periodo</p>
                </div>
            </header>
            <div class="grafico__cuerpo" data-esqueleto aria-hidden="true"><span class="esqueleto" style="height: 1.75rem; margin-bottom: .875rem; border-radius: 4px"></span>@for ($i = 0; $i < 5; $i++)<span class="esqueleto-fila esqueleto-fila--estado"><span class="esqueleto" style="width: {{ [70, 55, 62, 48, 58][$i] }}%"></span><span class="esqueleto" style="width: 2.5rem; justify-self: end"></span></span>@endfor</div>
            <div class="grafico__cuerpo" x-show="cargando" x-cloak aria-hidden="true"><span class="esqueleto" style="height: 1.75rem; margin-bottom: .875rem; border-radius: 4px"></span><template x-for="i in 5"><span class="esqueleto-fila esqueleto-fila--estado"><span class="esqueleto" :style="'width:' + (45 + i * 7) + '%'"></span><span class="esqueleto" style="width: 2.5rem; justify-self: end"></span></span></template></div>
            <div class="grafico__cuerpo" x-show="!cargando" x-cloak>
                <p class="vacio-grafico" x-show="!r().total">Sin turnos en el periodo.</p>
                <div x-show="r().total">
                    <div class="barra-estados" role="img" :aria-label="estados().map(e => e.rotulo + ' ' + e.n).join(', ')">
                        <template x-for="e in estados()" :key="e.clave"><span x-show="e.n > 0" :class="'estado--' + e.clave" :style="'flex-grow:' + e.n" :title="e.rotulo + ': ' + n(e.n) + ' (' + pct(e.n, r().total) + ')'"><b x-show="e.n / r().total >= .12" x-text="Math.round(e.n / r().total * 100) + ' %'"></b></span></template>
                    </div>
                    <ul class="lista-estados">
                        <template x-for="e in estados()" :key="e.clave">
                            <li :class="{ 'es-cero': !e.n }"><i class="muestra" :class="'estado--' + e.clave" aria-hidden="true"></i><span x-text="e.rotulo"></span>
                                <b x-text="n(e.n)"></b><span class="pct" x-text="pct(e.n, r().total)"></span></li>
                        </template>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Esperas por tramo, con el límite del panel -->
        <section class="superficie grafico grafico--esperas" aria-labelledby="t-esperas">
            <header class="grafico__cabeza">
                <div>
                    <h2 id="t-esperas" class="grafico__titulo">Tiempo de espera</h2>
                    <p class="grafico__sub">De sacar el turno a ser llamado</p>
                </div>
                <p class="leyenda leyenda--apilada" x-show="totalEsperas()">
                    <span class="leyenda__item"><i class="muestra muestra--atendidos"></i>Dentro del límite</span>
                    <span class="leyenda__item"><i class="muestra muestra--alerta"></i><span x-text="'Más de ' + datos.umbral_espera + ' min'"></span></span>
                </p>
            </header>
            {!! $esqueletoLista !!}
            <div class="grafico__cuerpo" x-show="!cargando" x-cloak>
                <p class="vacio-grafico" x-show="!totalEsperas()">Ningún turno fue llamado en el periodo.</p>
                <ul class="barras-h barras-h--esperas" x-show="totalEsperas()">
                    <li class="barras-h__cabeza" aria-hidden="true"><span>Espera</span><span></span><span>Turnos</span><span>%</span></li>
                    <template x-for="t in datos.esperas" :key="t.tramo">
                        <li :class="{ 'fila--alerta': sobreLimite(t) }" :title="t.tramo + ': ' + n(t.n) + ' turnos' + (sobreLimite(t) ? ' (más del límite de ' + datos.umbral_espera + ' min)' : '')">
                            <span class="barras-h__nombre" x-text="t.tramo"></span>
                            <span class="barras-h__pista"><span class="barras-h__barra" :style="'width:' + ancho(t.n, maxEsperas()) + '%'"></span></span>
                            <span class="barras-h__extra" x-text="n(t.n)"></span>
                            <b class="barras-h__valor" x-text="pct(t.n, totalEsperas())"></b>
                        </li>
                    </template>
                </ul>
                <p class="grafico__pie" x-show="totalEsperas()"><b x-text="pct(sobreUmbral(), totalEsperas())"></b> esperó más de <span x-text="datos.umbral_espera"></span> min · <span x-text="plural(totalEsperas(), 'turno llamado', 'turnos llamados')"></span></p>
            </div>
        </section>

        <!-- Tiempos por servicio: lo que más espera arriba -->
        <section class="superficie grafico grafico--tiempos" aria-labelledby="t-tiempos">
            <header class="grafico__cabeza">
                <div>
                    <h2 id="t-tiempos" class="grafico__titulo">Tiempos por servicio</h2>
                    <p class="grafico__sub">Promedios por servicio; arriba, el que más hace esperar</p>
                </div>
                <p class="leyenda">
                    <span class="leyenda__item"><i class="muestra muestra--espera"></i>Espera</span>
                    <span class="leyenda__item"><i class="muestra muestra--atendidos"></i>Atención</span>
                    <span class="leyenda__item" x-show="tiempos().some(s => esperaSobre(s))" x-cloak><i class="muestra muestra--alerta"></i><span x-text="'Espera de más de ' + datos.umbral_espera + ' min'"></span></span>
                </p>
            </header>
            {!! $esqueletoLista !!}
            <div class="grafico__cuerpo" x-show="!cargando" x-cloak>
                <p class="vacio-grafico" x-show="!tiempos().length">Sin turnos atendidos en el periodo.</p>
                <ul class="barras-doble" x-show="tiempos().length">
                    <li class="barras-h__cabeza" aria-hidden="true"><span>Servicio</span><span></span><span>Espera</span><span>Atención</span></li>
                    <template x-for="s in recortar(tiempos(), 'tiempos')" :key="s.id">
                        <li :title="s.nombre + ': espera ' + duracion(s.espera_seg) + ', atención ' + duracion(s.atencion_seg)">
                            <span class="barras-h__nombre" x-text="s.nombre"></span>
                            <span class="barras-doble__pistas" aria-hidden="true">
                                <span class="barras-doble__barra barras-doble__barra--espera" :class="{ 'barras-doble__barra--alerta': esperaSobre(s) }" :style="'width:' + ancho(s.espera_seg || 0, maxTiempo()) + '%'"></span>
                                <span class="barras-doble__barra" :style="'width:' + ancho(s.atencion_seg || 0, maxTiempo()) + '%'"></span>
                            </span>
                            <span class="barras-doble__valor" :class="{ 'is-alerta': esperaSobre(s) }" x-text="duracion(s.espera_seg)"></span>
                            <span class="barras-doble__valor" x-text="duracion(s.atencion_seg)"></span>
                        </li>
                    </template>
                </ul>
                <button type="button" class="enlace-panel ver-todo" x-show="tiempos().length > LIMITE" @click="verTodo.tiempos = !verTodo.tiempos"
                        :aria-expanded="verTodo.tiempos.toString()" x-text="verTodo.tiempos ? 'Ver menos' : 'Ver los ' + tiempos().length"></button>
            </div>
        </section>

        <!-- Por servicio -->
        <section class="superficie grafico grafico--medio" aria-labelledby="t-servicios">
            <header class="grafico__cabeza">
                <div>
                    <h2 id="t-servicios" class="grafico__titulo">Por servicio</h2>
                    <p class="grafico__sub">Turnos sacados en cada servicio</p>
                </div>
                <p class="leyenda">
                    <span class="leyenda__item"><i class="muestra muestra--atendidos"></i>Atendidos</span>
                    <span class="leyenda__item"><i class="muestra muestra--resto"></i>Sin atender</span>
                </p>
            </header>
            {!! $esqueletoLista !!}
            <div class="grafico__cuerpo" x-show="!cargando" x-cloak>
                <p class="vacio-grafico" x-show="!datos.servicios.length">Sin turnos en el periodo.</p>
                <ul class="barras-h barras-h--dos" x-show="datos.servicios.length">
                    <li class="barras-h__cabeza" aria-hidden="true"><span>Servicio</span><span></span><span>Turnos</span><span>Atendidos</span></li>
                    <template x-for="s in recortar(datos.servicios, 'servicios')" :key="s.id">
                        <li :title="(s.seccion ? s.seccion + ' · ' : '') + s.nombre + ': ' + n(s.total) + ' turnos, ' + n(s.atendidos) + ' atendidos'">
                            <span class="barras-h__nombre" x-text="s.nombre"></span>
                            <span class="barras-h__pista"><span class="barras-h__barra barras-h__barra--clara" :style="'width:' + ancho(s.total, datos.servicios[0].total) + '%'">
                                <span class="barras-h__barra" :style="'width:' + (s.total ? s.atendidos / s.total * 100 : 0) + '%'"></span></span></span>
                            <b class="barras-h__valor" x-text="n(s.total)"></b>
                            <span class="barras-h__extra" x-text="n(s.atendidos)"></span>
                        </li>
                    </template>
                </ul>
                <button type="button" class="enlace-panel ver-todo" x-show="datos.servicios.length > LIMITE" @click="verTodo.servicios = !verTodo.servicios"
                        :aria-expanded="verTodo.servicios.toString()" x-text="verTodo.servicios ? 'Ver menos' : 'Ver los ' + datos.servicios.length"></button>
            </div>
        </section>

        <!-- Por asesor -->
        <section class="superficie grafico grafico--medio" aria-labelledby="t-asesores">
            <header class="grafico__cabeza">
                <div>
                    <h2 id="t-asesores" class="grafico__titulo">Atendidos por asesor</h2>
                    <p class="grafico__sub">Turnos atendidos y la duración promedio de cada atención</p>
                </div>
            </header>
            {!! $esqueletoLista !!}
            <div class="grafico__cuerpo" x-show="!cargando" x-cloak>
                <p class="vacio-grafico" x-show="!datos.asesores.length">Nadie atendió turnos en el periodo.</p>
                <ul class="barras-h barras-h--dos" x-show="datos.asesores.length">
                    <li class="barras-h__cabeza" aria-hidden="true"><span>Asesor</span><span></span><span>Atendidos</span><span>Promedio</span></li>
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
                        :aria-expanded="verTodo.asesores.toString()" x-text="verTodo.asesores ? 'Ver menos' : 'Ver los ' + datos.asesores.length"></button>
            </div>
        </section>

        <!-- Horas pico (solo con más de un día: con un día, "Turnos por hora" ya lo muestra) -->
        <section class="superficie grafico grafico--medio" aria-labelledby="t-horas" x-show="datos.serie.modo !== 'hora' && datos.horas.length" x-cloak>
            <header class="grafico__cabeza">
                <div>
                    <h2 id="t-horas" class="grafico__titulo">Horas pico</h2>
                    <p class="grafico__sub">Turnos que llegan por hora en un día promedio; en azul, la hora con más</p>
                </div>
            </header>
            <div class="grafico__cuerpo" x-show="cargando" aria-hidden="true"><div class="esqueleto-columnas esqueleto-columnas--bajas"><template x-for="i in 11"><span class="esqueleto" :style="'height:' + (30 + (i * 29) % 60) + '%'"></span></template></div></div>
            <div class="grafico__cuerpo" x-show="!cargando">
                <div class="columnas columnas--bajas" role="img" :aria-label="'Hora con más llegadas: ' + horaPico()">
                    <div class="columnas__barras" :style="'--n:' + datos.horas.length">
                        <template x-for="h in datos.horas" :key="h.hora">
                            <div class="columna" :class="{ 'columna--pico': h.promedio === maxHoras() }" :title="h.hora + ':00 a ' + (h.hora + 1) + ':00 · ' + dec(h.promedio) + ' turnos en promedio'">
                                <b class="columna__valor" x-show="h.promedio === maxHoras()" x-text="dec(h.promedio)"></b>
                                <span class="columna__total columna__total--simple" :style="'height:' + alto(h.promedio, maxHoras()) + '%'"></span>
                                <span class="columna__rotulo" x-text="h.hora + ':00'"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </section>

        <!-- Transferencias (UBA): de qué servicio a cuál -->
        <section class="superficie grafico grafico--medio" aria-labelledby="t-transferencias" x-show="datos.transferencias.total" x-cloak>
            <header class="grafico__cabeza">
                <div>
                    <h2 id="t-transferencias" class="grafico__titulo">Transferencias</h2>
                    <p class="grafico__sub" x-text="plural(datos.transferencias.total, 'turno pasó', 'turnos pasaron') + ' a otro servicio'"></p>
                </div>
            </header>
            <div class="grafico__cuerpo" x-show="!cargando">
                <ul class="barras-h barras-h--uno">
                    <li class="barras-h__cabeza" aria-hidden="true"><span>De un servicio a otro</span><span></span><span>Turnos</span></li>
                    <template x-for="(t, i) in datos.transferencias.rutas.slice(0, 6)" :key="i">
                        <li :title="t.desde + ' a ' + t.hacia + ': ' + n(t.n)">
                            <span class="barras-h__nombre barras-h__nombre--ruta"><span x-text="t.desde"></span><svg class="ruta-flecha" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg><span class="sr-only"> a </span><span x-text="t.hacia"></span></span>
                            <span class="barras-h__pista"><span class="barras-h__barra" :style="'width:' + ancho(t.n, datos.transferencias.rutas[0].n) + '%'"></span></span>
                            <b class="barras-h__valor" x-text="n(t.n)"></b>
                        </li>
                    </template>
                </ul>
            </div>
        </section>

        <!-- Canales no presenciales (no dependen del servicio) -->
        <section class="superficie grafico grafico--medio" aria-labelledby="t-canales" x-show="datos.canales.total_minutos" x-cloak>
            <header class="grafico__cabeza">
                <div>
                    <h2 id="t-canales" class="grafico__titulo">Canales no presenciales</h2>
                    <p class="grafico__sub" x-text="duracion(datos.canales.total_minutos * 60) + ' registrados en el periodo'"></p>
                </div>
            </header>
            <div class="grafico__cuerpo" x-show="!cargando">
                <ul class="barras-h barras-h--dos">
                    <li class="barras-h__cabeza" aria-hidden="true"><span>Asesor</span><span></span><span>Tiempo</span><span>Actividades</span></li>
                    <template x-for="(a, i) in datos.canales.asesores.slice(0, 6)" :key="i">
                        <li :title="a.nombre + ': ' + duracion(a.minutos * 60) + ' en ' + a.actividades + ' actividades'">
                            <span class="barras-h__nombre" x-text="a.nombre"></span>
                            <span class="barras-h__pista"><span class="barras-h__barra" :style="'width:' + ancho(a.minutos, datos.canales.asesores[0].minutos) + '%'"></span></span>
                            <b class="barras-h__valor" x-text="duracion(a.minutos * 60)"></b>
                            <span class="barras-h__extra" x-text="n(a.actividades)"></span>
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
                .catch(e => { if (e && e.name === 'AbortError') return; if (!silencioso) this.error = e === 419 || e === 401 ? 'La sesión expiró. Recargue la página para seguir.' : 'No se pudieron cargar los datos.'; })
                .finally(() => { clearTimeout(this._esqueleto); this.cargando = false; });
        },
        urlInforme() { return URL_INFORME + '?' + this.consulta(this.datos.periodo.desde, this.datos.periodo.hasta); },
        // El botón «Informe» vive en la cabecera de la página (fuera de este x-data): se le pone el enlace desde aquí.
        sincronizarInforme() { const a = document.getElementById('g-informe'); const url = this.urlInforme(); if (a) a.href = url; },

        // ---- textos
        r() { return this.datos.resumen; },
        n(x) { return x === null || x === undefined ? '—' : numero.format(x); },
        dec(x) { return decimal.format(x); },
        pct(a, b) { return b ? decimal.format(Math.round(a / b * 1000) / 10) + ' %' : '—'; },
        plural(x, uno, varios) { return this.n(x) + ' ' + (x === 1 ? uno : varios); },
        duracion(seg) {
            if (seg === null || seg === undefined) return '—';
            const m = seg / 60;
            if (m < 1) return Math.round(seg) + ' s';
            if (m < 60) return decimal.format(Math.round(m * 10) / 10) + ' min';
            const h = Math.floor(m / 60), resto = Math.round(m % 60);
            return h + ' h' + (resto ? ' ' + resto + ' min' : '');
        },
        // «15:48» del servidor → «3:48 p. m.», como el resto del panel
        hora12(t) {
            if (!t) return '';
            const [h, m] = String(t).split(':').map(Number);
            return ((h % 12) || 12) + ':' + String(m).padStart(2, '0') + (h < 12 ? ' a. m.' : ' p. m.');
        },
        textoActualizado() {
            const hora = this.hora12(this.datos.periodo.actualizado);
            return (this.datos.periodo.hasta >= this.hoy() ? 'Al día a las ' : 'Consultado a las ') + hora;
        },
        fechaCorta(t, conAnio) { const f = aFecha(t); return f.getDate() + ' ' + MESES[f.getMonth()] + (conAnio ? ' ' + f.getFullYear() : ''); },
        periodoTexto() {
            const { desde, hasta } = this.datos.periodo;
            // Hoy: solo «Hoy» (la fecha ya está en la guía de la cabecera). Se mira el periodo que llegó, no la pastilla,
            // para que el título nunca diga «Hoy» mientras todavía se ven los datos de otro día.
            if (desde === hasta) return desde === this.hoy() ? 'Hoy' : this.fechaCorta(desde, true);
            const d = aFecha(desde), h = aFecha(hasta);
            if (d.getFullYear() === h.getFullYear() && d.getMonth() === h.getMonth()) return d.getDate() + ' – ' + this.fechaCorta(hasta, true);
            return this.fechaCorta(desde, d.getFullYear() !== h.getFullYear()) + ' – ' + this.fechaCorta(hasta, true);
        },
        servicioTexto() {
            const o = opciones.find(o => String(o.id) === this.servicio);
            return o ? o.nombre + (o.seccion ? ' (sección)' : '') : 'Todos los servicios';
        },
        esperaAlta() { const s = this.r().espera_seg; return s !== null && s !== undefined && s / 60 >= this.datos.umbral_espera; },

        // ---- serie
        maxSerie() { return Math.max(0, ...this.datos.serie.puntos.map(p => p.total)); },
        indiceMax() { const pts = this.datos.serie.puntos; let k = -1; pts.forEach((p, i) => { if (k < 0 || p.total > pts[k].total) k = i; }); return k; },
        alto(v, max) { return max ? Math.max(v > 0 ? 1.5 : 0, v / this.techo(max) * 100) : 0; },   // columnas con eje
        ancho(v, max) { return max ? Math.max(v > 0 ? 2 : 0, v / max * 100) : 0; },                    // listas: el mayor llena la pista
        // Eje con pasos redondos (1, 2, 5 × 10ⁿ) y tres o cuatro líneas: sin «12,5 turnos» en el eje.
        paso(max) { const bruto = Math.max(max / 3, 1); const e = Math.pow(10, Math.floor(Math.log10(bruto))); return [1, 2, 5, 10].map(f => f * e).find(x => x >= bruto); },
        techo(max) { if (!max) return 1; const p = this.paso(max); return Math.ceil(max / p) * p; },
        lineas(max) {
            if (!max) return [];
            const t = this.techo(max), p = this.paso(max), lineas = [];
            for (let v = 0; v <= t + 1e-9; v += p) lineas.push({ v, pct: v / t * 100 });
            return lineas;
        },
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
        puntoActivo() { return this.punto === null ? null : this.datos.serie.puntos[this.punto] || null; },
        textoGlobo() { const p = this.puntoActivo(); return p ? this.n(p.total) + ' turnos · ' + this.n(p.atendidos) + ' atendidos' : ''; },
        globoEstilo() {
            if (!this.puntoActivo()) return '';
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
        sobreLimite(t) { return t.hasta === null || t.hasta > this.datos.umbral_espera; },
        sobreUmbral() { return this.datos.esperas.filter(t => this.sobreLimite(t)).reduce((s, t) => s + t.n, 0); },
        recortar(lista, clave) { return this.verTodo[clave] ? lista : lista.slice(0, this.LIMITE); },
        tiempos() { return this.datos.servicios.filter(s => s.espera_seg !== null || s.atencion_seg !== null).slice().sort((a, b) => (b.espera_seg || 0) - (a.espera_seg || 0)); },
        esperaSobre(s) { return s.espera_seg !== null && s.espera_seg / 60 >= this.datos.umbral_espera; },
        maxTiempo() { return Math.max(0, ...this.tiempos().flatMap(s => [s.espera_seg || 0, s.atencion_seg || 0])); },
        maxHoras() { return Math.max(0, ...this.datos.horas.map(h => h.promedio)); },
        horaPico() { const h = this.datos.horas.find(h => h.promedio === this.maxHoras()); return h ? h.hora + ':00' : '—'; },
    }));
});
</script>

@push('estilos')
<style>
[x-cloak] { display: none !important; }

/* ===== Gráficos (2026-11): el lenguaje de «La sala» llevado a los números del periodo (ver DESIGN.md) =====
   Serie en azul institucional y sus tonos (validados: el claro pasa 2:1 sobre blanco, el medio 3:1): es volumen, no
   estado. Los estados usan la familia de Turnos de todo el panel (En espera = info, En atención = ok, Atendido = neutro,
   Aplazado = alerta, Cancelado = error), siempre con su palabra al lado. */
.graficos-vista {
    --g-azul: var(--azul);          /* atendidos, atención, la serie principal */
    --g-medio: #5a8fd8;             /* espera (tono medio del mismo azul) */
    --g-claro: #9db8e2;             /* sin atender, el resto de una serie */
    --g-pista: #e9eef5;             /* fondo de una barra horizontal (como en el Inicio) */
    --g-ok: #277a51; --g-info: #3f74c0; --g-alerta: #d99a2e; --g-neutro: #8796ae; --g-error: var(--error);
    display: flex; flex-direction: column; gap: 1rem; font-variant-numeric: tabular-nums;
}

/* Barra: periodo a la izquierda, servicio a la derecha; el rango propio baja a su propia línea */
.barra-graficos { gap: .75rem 1rem; }
.acciones-graficos { display: flex; align-items: center; gap: .625rem; margin-left: auto; }
.campo-servicio { width: 17rem; }
.rango-personalizado { order: 3; flex-basis: 100%; display: flex; flex-wrap: wrap; align-items: center; gap: .5rem .625rem; }
.rango-personalizado .campo { width: 10.5rem; }
.rango-personalizado .rotulo-barra + .campo { margin-right: .375rem; }
/* El aviso es el del layout; aquí solo su acomodo: una línea corta con «Reintentar» al lado */
.aviso-graficos { flex-wrap: wrap; align-items: center; }
.aviso-graficos > svg { margin-top: 0; }

/* Resumen: qué periodo se está viendo y sus cifras (las mismas piezas del Inicio) */
.resumen-graficos { display: flex; flex-direction: column; gap: .75rem; container-type: inline-size; }
.resumen-graficos__cabeza { display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: .25rem 1rem; }
.resumen-graficos__titulo { margin: 0; font-size: 1rem; font-weight: 650; color: var(--tinta); }
.resumen-graficos__titulo .esqueleto { display: inline-block; vertical-align: middle; }
.resumen-graficos__servicio { font-weight: 500; color: var(--tinta-suave); }
.resumen-graficos__hora { margin: 0; font-size: .78125rem; color: var(--tinta-tenue); }
.cifras-graficos { display: grid; grid-auto-flow: column; grid-auto-columns: minmax(0, 1fr); gap: .625rem; }
/* Las cifras pasan a tres columnas cuando ya no caben seis con su palabra completa (se mide el resumen, no la ventana) */
@container (max-width: 72rem) { .cifras-graficos { grid-auto-flow: row; grid-template-columns: repeat(3, minmax(0, 1fr)); } }
@container (max-width: 36rem) { .cifras-graficos { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
.cifra {
    margin: 0; min-width: 0; display: flex; flex-direction: column; gap: .1875rem; padding: .6875rem 1rem; border-radius: .875rem;
    background: #fff; box-shadow: var(--roce); font-size: .875rem; color: var(--tinta-suave);
}
.cifra > span { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.cifra__n { font-size: 1.1875rem; font-weight: 650; letter-spacing: -.01em; color: var(--tinta); }
.cifra__n.is-alerta { color: var(--error); }
.cifra__mas { color: var(--tinta-tenue); font-size: .78125rem; }
.cifra__mas.is-alerta { color: var(--error-texto); font-weight: 600; }
.esqueleto-cifra { display: inline-block; width: 2.75rem; height: 1.125rem; vertical-align: -.125rem; }

/* Rejilla: la serie manda (con los estados al lado); luego los tiempos; luego servicio y asesor; luego lo opcional */
/* 24 px del resumen a los gráficos: los 16 px del gap de la vista + .5rem (secciones distintas) */
.rejilla-graficos { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: 1rem; margin-top: .5rem; }
.grafico { grid-column: span 6; display: flex; flex-direction: column; min-width: 0; padding: 1.25rem 1.25rem 1.125rem; }
/* La serie es la pieza elevada de la vista (.pieza-elevada del layout): aquí solo su lugar y su alto */
.grafico--serie { grid-column: span 8; min-height: 21.5rem; }
.grafico--estados, .grafico--esperas { grid-column: span 4; }
.grafico--tiempos { grid-column: span 8; }
@media (max-width: 1100px) {
    .grafico--serie, .grafico--tiempos { grid-column: span 12; }
    .grafico--estados, .grafico--esperas { grid-column: span 6; }
    .grafico--medio { grid-column: span 12; }
}
@media (max-width: 767px) { .rejilla-graficos > .grafico { grid-column: span 12; } }

.grafico__cabeza { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: .375rem 1rem; margin-bottom: 1rem; }
.grafico__cabeza > div { min-width: 0; }
.grafico__titulo { margin: 0; font-size: 1rem; font-weight: 650; color: var(--tinta); }
.grafico__sub { margin: .125rem 0 0; font-size: .8125rem; color: var(--tinta-suave); text-wrap: pretty; }
.grafico__cuerpo { flex: 1; min-height: 0; display: flex; flex-direction: column; position: relative; }
.grafico__pie { margin: auto 0 0; padding-top: .75rem; font-size: .8125rem; color: var(--tinta-suave); }
.grafico__pie b { font-weight: 650; color: var(--tinta); }
.vacio-grafico { margin: auto; padding: 1.5rem 1rem; max-width: 22rem; font-size: .875rem; color: var(--tinta-tenue); text-align: center; text-wrap: balance; }
.ver-todo { align-self: flex-start; margin-top: auto; padding-top: .75rem; font-size: .875rem; }

/* Leyenda: muestra de color + palabra, en tinta (el color va en la muestra, nunca en el texto) */
.leyenda { margin: 0; display: flex; flex-wrap: wrap; align-items: center; gap: .25rem 1rem; padding-top: .1875rem; font-size: .78125rem; color: var(--tinta-suave); }
.leyenda--apilada { flex-direction: column; align-items: flex-start; gap: .25rem; }
.leyenda__item { display: inline-flex; align-items: center; gap: .4375rem; white-space: nowrap; }
.muestra { width: .625rem; height: .625rem; border-radius: 3px; flex: none; }
.muestra--atendidos { background: var(--g-azul); }
.muestra--resto { background: var(--g-claro); }
.muestra--espera { background: var(--g-medio); }
.muestra--alerta { background: var(--g-error); }

/* Columnas (serie y horas pico): rejilla de líneas finas, columnas de 24 px con punta redonda */
.columnas { position: relative; flex: 1; min-height: 13rem; display: flex; flex-direction: column; padding: 1.25rem 0 0 2rem; }
.columnas__rejilla { position: absolute; left: 0; right: 0; top: 1.25rem; bottom: 1.75rem; pointer-events: none; }
.columnas__rejilla span { position: absolute; left: 2rem; right: 0; border-top: 1px solid var(--linea-suave); }
.columnas__rejilla span:first-child { border-top-color: var(--linea); }
.columnas__rejilla b { position: absolute; right: calc(100% + .5rem); top: 0; transform: translateY(-50%); font-size: .6875rem; line-height: 1; font-weight: 500; color: var(--tinta-tenue); }
.columnas__barras { position: relative; flex: 1; min-height: 0; display: grid; grid-template-columns: repeat(var(--n), minmax(0, 1fr)); grid-template-rows: minmax(0, 1fr); gap: 2px; }
/* la etiqueta va en el relleno inferior: la altura % de la columna se mide sobre el resto */
.columna { position: relative; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; min-width: 0; height: 100%; padding-bottom: 1.75rem; cursor: default; }
.columna::before {
    content: ""; position: absolute; inset: -1.25rem 0 1.75rem; border-radius: 6px; background: transparent;
    transition: background-color .15s ease-out;
}
.columna:hover::before, .columna--activa::before { background: #eef3fa; }
.columna__total {
    position: relative; flex: none; display: flex; flex-direction: column; justify-content: flex-end; width: 100%; max-width: 1.5rem; min-height: 0;
    background: var(--g-claro); border-radius: 4px 4px 0 0; overflow: hidden;
}
.columna__atendidos { display: block; flex: none; background: var(--g-azul); box-shadow: 0 -2px 0 #fff; }
.columna--en-curso .columna__total { opacity: .45; }
.columna__valor { position: relative; flex: none; margin-bottom: .3125rem; font-size: .75rem; line-height: 1; font-weight: 650; color: var(--tinta); white-space: nowrap; }
.columna__curso { position: relative; flex: none; margin-bottom: .3125rem; font-size: .6875rem; line-height: 1; color: var(--tinta-tenue); white-space: nowrap; }
.columna__rotulo { position: absolute; bottom: .375rem; left: 50%; transform: translateX(-50%); font-size: .6875rem; line-height: 1; color: var(--tinta-tenue); white-space: nowrap; }
.columna--activa .columna__rotulo { color: var(--tinta); font-weight: 600; }
.columna__total--simple { background: var(--g-claro); }
.columna--pico .columna__total--simple { background: var(--g-azul); }
.columnas--bajas { flex: 1; min-height: 11.5rem; padding-left: 0; }
.columnas--bajas .columna::before { inset: -1.25rem 0 1.75rem; }
.globo {
    position: absolute; top: 0; transform: translateX(-50%); z-index: 3; padding: .5rem .6875rem; border-radius: .625rem;
    background: var(--tinta); color: #ffffff; font-size: .78125rem; line-height: 1.45; white-space: nowrap; pointer-events: none;
    box-shadow: 0 10px 24px -12px rgba(15, 39, 71, .55);
}
.globo b { font-weight: 650; }
.esqueleto-columnas { flex: 1; min-height: 13rem; display: flex; align-items: flex-end; justify-content: space-around; gap: .5rem; padding: 1.25rem 0 1.75rem 2rem; }
.esqueleto-columnas .esqueleto { flex: 0 1 1.5rem; border-radius: 4px 4px 0 0; }
.esqueleto-columnas--bajas { min-height: 11.5rem; padding-left: 0; }

/* Estados: barra alta al 100 % (bloques separados por 2 px de blanco, su parte escrita dentro cuando cabe)
   y debajo la lista con sus palabras y cifras */
.barra-estados { display: flex; gap: 2px; height: 1.75rem; margin-bottom: .875rem; }
.barra-estados span { flex: 1 1 0; min-width: 4px; display: flex; align-items: center; border-radius: 4px; overflow: hidden; }
.barra-estados b { padding-left: .5rem; font-size: .75rem; font-weight: 650; line-height: 1; color: #ffffff; white-space: nowrap; }
/* Texto dentro del bloque: blanco sobre info, ok y error; tinta sobre neutro y alerta (los dos pasan AA así) */
.barra-estados .estado--atendido b, .barra-estados .estado--aplazado b { color: var(--tinta); }
.estado--pendiente { background: var(--g-info); }
.estado--llamado { background: var(--g-ok); }
.estado--atendido { background: var(--g-neutro); }
.estado--aplazado { background: var(--g-alerta); }
.estado--cancelado { background: var(--g-error); }
.lista-estados { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; }
.lista-estados li {
    display: grid; grid-template-columns: .625rem minmax(0, 1fr) auto 3.75rem; align-items: center; gap: .625rem;
    min-height: 2.125rem; border-top: 1px solid var(--linea-suave); font-size: .875rem; color: var(--tinta);
}
.lista-estados li:first-of-type { border-top: 0; }
.lista-estados b { font-weight: 650; text-align: right; }
.lista-estados .pct { text-align: right; color: var(--tinta-tenue); font-size: .8125rem; }
.lista-estados li.es-cero { color: var(--tinta-tenue); }
.lista-estados li.es-cero b { font-weight: 500; }

/* Listas de barras: cabecera en voz baja y filas con filete, como «Por servicio» del Inicio */
.barras-h, .barras-doble { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; min-width: 0; }
.barras-h li, .barras-doble li {
    display: grid; grid-template-columns: minmax(0, 1.25fr) minmax(4rem, 1fr) 3.5rem; align-items: center; gap: .875rem;
    min-height: 2.125rem; border-top: 1px solid var(--linea-suave); font-size: .875rem;
}
.barras-h--dos li { grid-template-columns: minmax(0, 1.25fr) minmax(4rem, 1fr) 4.5rem 5rem; }
.barras-h--uno li { grid-template-columns: minmax(0, 2fr) minmax(4rem, 1fr) 3.5rem; }
.barras-h--esperas li { grid-template-columns: 5.75rem minmax(3rem, 1fr) 3rem 3.75rem; gap: .75rem; }
.barras-doble li { grid-template-columns: minmax(0, 1.1fr) minmax(4rem, 1.4fr) 5rem 5rem; }
.barras-h li.barras-h__cabeza, .barras-doble li.barras-h__cabeza {
    min-height: 0; padding-bottom: .4375rem; border-top: 0; font-size: .78125rem; font-weight: 550; color: var(--tinta-tenue);
}
.barras-h__cabeza > span:nth-child(n+3) { text-align: right; }
.barras-h__nombre { min-width: 0; color: var(--tinta); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.barras-h__nombre--ruta { display: flex; align-items: center; gap: .375rem; }
.barras-h__nombre--ruta > span { min-width: 0; overflow: hidden; text-overflow: ellipsis; }
.ruta-flecha { flex: none; color: var(--icono); }
.barras-h__pista { height: .5rem; border-radius: 4px; background: var(--g-pista); overflow: hidden; }
.barras-h__barra { display: block; height: 100%; border-radius: 4px; background: var(--g-azul); }
.barras-h__barra--clara { display: flex; background: var(--g-claro); overflow: hidden; }
.barras-h__barra--clara > .barras-h__barra { flex: none; border-radius: 0; box-shadow: 2px 0 0 #fff; }
.barras-h__valor { text-align: right; font-weight: 650; color: var(--tinta); white-space: nowrap; }
.barras-h__extra { text-align: right; color: var(--tinta-suave); white-space: nowrap; }
.fila--alerta .barras-h__barra { background: var(--g-error); }

/* Tiempos por servicio: dos barras finas (espera y atención) sobre el mismo eje, y sus valores en columnas */
.barras-doble li { padding: .25rem 0; }
.barras-doble li.barras-h__cabeza { padding-top: 0; }
.barras-doble__pistas { display: flex; flex-direction: column; gap: 3px; }
.barras-doble__barra { display: block; height: .3125rem; border-radius: 3px; background: var(--g-azul); }
.barras-doble__barra--espera { background: var(--g-medio); }
.barras-doble__barra--alerta { background: var(--g-error); }
.barras-doble__valor { text-align: right; color: var(--tinta-2); white-space: nowrap; }
.barras-doble__valor.is-alerta { color: var(--error); font-weight: 650; }

/* Esqueletos de lista: la misma forma (cabecera + filas de nombre, pista y cifra) */
.esqueleto-fila { display: grid; grid-template-columns: minmax(0, 1.25fr) minmax(4rem, 1fr) 3.5rem; gap: .875rem; align-items: center; min-height: 2.125rem; border-top: 1px solid var(--linea-suave); }
.esqueleto-fila--cabeza { min-height: 0; padding-bottom: .4375rem; border-top: 0; }
.esqueleto-fila--cabeza .esqueleto { height: .625rem; }
.esqueleto-fila--estado { grid-template-columns: minmax(0, 1fr) 3.5rem; }
.esqueleto + .esqueleto-fila--estado { border-top: 0; }

@media (prefers-reduced-motion: reduce) { .columna::before { transition: none; } }

/* Impresión: la cabecera de la página (título, unidad y fecha) se queda; el periodo sale en el resumen.
   Fuera el riel, las acciones y los filtros. Hoja horizontal y los colores de las barras tal cual. */
@media print {
    @page { size: A4 landscape; margin: 10mm; }
    #admin-sidebar, .riel, .riel-velo, .skip-link, .pagina-cabeza__menu, .pagina-cabeza__acciones,
    .barra-graficos, .aviso, .ver-todo, .globo, .resumen-graficos__renueva { display: none !important; }
    body.panel { background: #ffffff !important; }
    .pagina { margin: 0 !important; padding: 0 !important; min-height: 0 !important; }
    .pagina-cabeza { min-height: 0; margin-bottom: .75rem; }
    .graficos-vista { gap: .75rem; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .rejilla-graficos { margin-top: 0; }
    .superficie, .pieza-elevada, .cifra { box-shadow: none !important; border: 1px solid var(--linea); }
    .pieza-elevada { filter: none; border-radius: .875rem; }
    .columna::before { display: none; }
    .grafico, .resumen-graficos { break-inside: avoid; }
    /* Rejilla fija en papel (no depende del ancho que el navegador use para las media queries al imprimir):
       la serie y los tiempos a todo el ancho, lo demás de a dos; cifras en tres columnas. */
    .rejilla-graficos { gap: .625rem; }
    .rejilla-graficos > .grafico { grid-column: span 6; }
    .rejilla-graficos > .grafico--serie, .rejilla-graficos > .grafico--tiempos { grid-column: span 12; }
    .cifras-graficos { grid-auto-flow: row; grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
</style>
@endpush
@endsection
