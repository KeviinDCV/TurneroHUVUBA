@extends('layouts.admin')

@section('title', 'Asignación')

@section('content')
{{-- Matriz de cobertura (AsignacionServicioController::index): asesores en filas y, en columnas, lo que el kiosco
     reparte, agrupado por sección. Cada casilla se guarda sola; abajo, cuántos asesores cubren cada columna. --}}
@php
    $columnasMatriz = collect($matriz['secciones'])->sum(fn ($s) => max(1, count($s['hijos'])));
    $filasEsqueleto = min(count($matriz['asesores']), 12);
@endphp
<div class="asignacion-vista" x-data="matrizCobertura(@js($matriz))">

    <div class="barra-vista">
        <div class="filtros-rapidos" role="group" aria-label="Filtrar asesores">
            @foreach ([3.25, 5.75, 6.5] as $ancho)
                <span class="filtro-rapido" data-esqueleto aria-hidden="true"><span class="esqueleto" style="width: {{ $ancho }}rem"></span></span>
            @endforeach
            <template x-for="f in filtros" :key="f.clave">
                <button type="button" class="filtro-rapido" :class="{ 'is-alerta': f.clave === 'sin' && contar('sin') > 0 }"
                        :aria-pressed="(filtro === f.clave).toString()" @click="filtro = f.clave">
                    <span x-text="f.rotulo"></span> <span class="filtro-rapido__n" x-text="contar(f.clave)"></span>
                </button>
            </template>
        </div>
        <p class="asignacion-nota">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
            Cada casilla se guarda al marcarla
        </p>
        <div class="buscador">
            <svg class="buscador__icono" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 110-15 7.5 7.5 0 010 15z"></path></svg>
            <input type="search" x-model.debounce.150ms="buscar" class="campo" placeholder="Buscar asesor" aria-label="Buscar asesor">
        </div>
    </div>

    <!-- Llegando desde "Nadie · Asignar": qué columna completar -->
    <div class="aviso aviso--info" role="status" x-show="columnaGuia()" x-cloak>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z"/><path d="M12 11v5M12 8h.01"/></svg>
        <p>Marque quién atenderá <b x-text="columnaGuia()?.nombre"></b>; la columna queda resaltada.</p>
        <button type="button" class="accion-icono aviso__cerrar" aria-label="Quitar resaltado" @click="destacada = null">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>

    <div class="superficie matriz-marco" x-ref="marco">
        <table class="matriz" @mouseleave="columnaActiva = null">
            <caption class="sr-only">Servicios que atiende cada asesor. Cada casilla asigna o quita un servicio y se guarda sola.</caption>
            <thead>
                {{-- El encabezado sale del servidor: coincide con las filas desde el primer pintado --}}
                <tr class="matriz-secciones">
                    <th scope="col" rowspan="2" class="col-asesor">Asesor</th>
                    @foreach ($matriz['secciones'] as $s)
                        @if (count($s['hijos']))
                            <th scope="colgroup" colspan="{{ count($s['hijos']) }}" class="th-seccion"><span class="th-seccion__texto">{{ $s['nombre'] }}</span></th>
                        @else
                            <th scope="col" rowspan="2" class="th-seccion th-seccion--sola" :class="{ 'col-activa': columnaActiva === {{ $s['id'] }}, 'col-destacada': destacada === {{ $s['id'] }} }"
                                title="{{ $s['nombre'] }}{{ $s['ocultar'] ? ' · oculto en el TV: se llama por código' : '' }}"><span class="th-columna__texto">{{ $s['nombre'] }}</span></th>
                        @endif
                    @endforeach
                    <th scope="col" rowspan="2" class="col-total">Servicios</th>
                </tr>
                <tr class="matriz-columnas">
                    @foreach ($matriz['secciones'] as $s)
                        @foreach ($s['hijos'] as $h)
                            <th scope="col" class="th-columna {{ $loop->first ? 'inicio-seccion' : '' }}" :class="{ 'col-activa': columnaActiva === {{ $h['id'] }}, 'col-destacada': destacada === {{ $h['id'] }} }"
                                title="{{ $h['nombre'] }}{{ $h['ocultar'] ? ' · oculto en el TV: se llama por código' : '' }}"><span class="th-columna__texto">{{ $h['corto'] }}</span></th>
                        @endforeach
                    @endforeach
                </tr>
            </thead>
            {{-- Mientras arranca Alpine: la forma de las filas (init() las quita) --}}
            <tbody data-esqueleto aria-hidden="true">
                @for ($i = 0; $i < $filasEsqueleto; $i++)
                    <tr>
                        <td class="col-asesor">
                            <span class="asesor-celda">
                                <span class="esqueleto" style="width: {{ [10, 8, 11.5, 9][$i % 4] }}rem"></span>
                                @if ($i % 3 !== 2)<span class="esqueleto esqueleto--linea" style="width: {{ [6.5, 5.5][$i % 2] }}rem"></span>@endif
                            </span>
                        </td>
                        @for ($c = 0; $c < $columnasMatriz; $c++)
                            <td class="celda"><span class="esqueleto esqueleto-casilla"></span></td>
                        @endfor
                        <td class="col-total"><span class="esqueleto" style="width: 1rem; margin-left: auto"></span></td>
                    </tr>
                @endfor
            </tbody>
            <tbody>
                <template x-for="a in visibles()" :key="a.id">
                    <tr :class="{ 'fila-destacada': a.id === filaDestacada }" :id="'asesor-' + a.id">
                        <th scope="row" class="col-asesor">
                            <div class="asesor-celda">
                                <span class="asesor-nombre" x-text="a.nombre" :title="a.nombre"></span>
                                {{-- Debajo del nombre, en palabras: si no atiende nada y dónde está conectado ahora --}}
                                <span class="asesor-linea" x-show="a.modulo !== null || totalAsesor(a.id) === 0">
                                    <span class="asesor-sin" x-show="totalAsesor(a.id) === 0">Sin servicios</span>
                                    <span class="asesor-conexion" x-show="a.modulo !== null" :class="'conexion--' + a.estado" :title="conexion(a)">
                                        <span class="punto" :class="'punto--' + ({ atendiendo: 'ok', libre: 'info', descanso: 'alerta' }[a.estado] || 'neutro')" aria-hidden="true"></span><span class="asesor-conexion__texto" x-text="lineaConexion(a)"></span>
                                    </span>
                                </span>
                            </div>
                        </th>
                        <template x-for="h in columnas" :key="h.id">
                            <td class="celda" :class="{ 'inicio-seccion': h.primera, 'col-activa': columnaActiva === h.id, 'col-destacada': destacada === h.id }"
                                @mouseenter="columnaActiva = h.id">
                                {{-- Toda la celda es el blanco del clic; la casilla conserva su nombre accesible --}}
                                <label class="celda__toque">
                                    <input type="checkbox" class="casilla" :checked="tiene(a.id, h.id)" :disabled="!!guardando[a.id + ':' + h.id]"
                                           :aria-label="(tiene(a.id, h.id) ? 'Quitar ' : 'Asignar ') + h.nombre + ' a ' + a.nombre"
                                           @change="alternar(a, h, $event.target.checked)">
                                </label>
                            </td>
                        </template>
                        <td class="col-total" :class="{ 'total-cero': totalAsesor(a.id) === 0 }" x-text="totalAsesor(a.id)"
                            :title="totalAsesor(a.id) === 0 ? 'No atiende ningún servicio' : ''"></td>
                    </tr>
                </template>
            </tbody>
            <tfoot x-cloak>
                <tr>
                    <th scope="row" class="col-asesor">Lo atienden</th>
                    <template x-for="h in columnas" :key="h.id">
                        <td class="celda" :class="{ 'inicio-seccion': h.primera, 'col-activa': columnaActiva === h.id, 'col-destacada': destacada === h.id }">
                            <span x-show="totalColumna(h.id) > 0" x-text="totalColumna(h.id)"></span>
                            <span class="estado-chip estado-chip--error estado-chip--chico" x-show="totalColumna(h.id) === 0" title="Ningún asesor atiende este servicio">Nadie</span>
                        </td>
                    </template>
                    <td class="col-total"></td>
                </tr>
            </tfoot>
        </table>
        <div class="vacio" x-show="!visibles().length" x-cloak>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 19v-1.5a3.5 3.5 0 0 0-3.5-3.5h-5A3.5 3.5 0 0 0 4 17.5V19"/><path d="M10 10.5a3.25 3.25 0 1 0 0-6.5 3.25 3.25 0 0 0 0 6.5Z"/><path d="M17.5 8.5 21 12M21 8.5 17.5 12"/></svg>
            @if (count($matriz['asesores']))
                <p class="vacio__titulo">Ningún asesor coincide</p>
                <p class="vacio__texto">Pruebe con otro nombre o vea a todos los asesores.</p>
                <button type="button" class="enlace-panel" @click="filtro = 'todos'; buscar = ''">Quitar filtros</button>
            @else
                <p class="vacio__titulo">Todavía no hay asesores</p>
                <p class="vacio__texto">Los usuarios con el rol Asesor aparecen aquí para asignarles servicios.</p>
                <a class="enlace-panel" href="{{ route('admin.users') }}">Créelos en Usuarios</a>
            @endif
        </div>
    </div>

</div>

<script>
document.addEventListener('alpine:init', () => {
    const ASIGNAR_URL = @json(route('admin.asignacion-servicios.asignar'));
    const QUITAR_URL = @json(route('admin.asignacion-servicios.desasignar'));
    const TOKEN = () => document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const normal = t => String(t ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();
    const ESTADOS = { atendiendo: 'Atendiendo', libre: 'Libre', descanso: 'En descanso', canal: 'Canal no presencial' };

    Alpine.data('matrizCobertura', (matriz) => ({
        asesores: matriz.asesores,
        secciones: matriz.secciones,
        asignados: Object.fromEntries(matriz.asesores.map(a => [a.id, (matriz.asignados[a.id] || []).slice()])),
        columnas: [],
        filtro: 'todos',
        buscar: '',
        guardando: {},
        pendientes: {},
        columnaActiva: null,
        destacada: null,
        filaDestacada: null,
        filtros: [
            { clave: 'todos', rotulo: 'Todos' },
            { clave: 'conectados', rotulo: 'Conectados' },
            { clave: 'sin', rotulo: 'Sin servicios' },
        ],
        init() {
            this.$el.querySelectorAll('[data-esqueleto]').forEach(e => e.remove());
            // Una columna por subservicio; la sección sin subservicios es una columna por sí sola.
            this.columnas = this.secciones.flatMap(s => s.hijos.length
                ? s.hijos.map((h, i) => ({ ...h, primera: i === 0, seccion: s.nombre }))
                : [{ ...s, primera: true, seccion: null }]);
            // Enlaces desde Servicios / Inicio (?servicio=ID) y desde Usuarios (?asesor=ID)
            const url = new URLSearchParams(location.search);
            const sid = parseInt(url.get('servicio'), 10), aid = parseInt(url.get('asesor'), 10);
            if (this.columnas.some(c => c.id === sid)) this.destacada = sid;
            if (this.asesores.some(a => a.id === aid)) this.filaDestacada = aid;
            // La matriz ocupa lo que queda de pantalla y se desplaza por dentro (encabezado y pie fijos).
            // Abajo deja el mismo margen de la página, para que la página no se desplace además de la matriz.
            const ajustar = () => {
                const marco = this.$refs.marco;
                const margen = parseFloat(getComputedStyle(marco.closest('.pagina') || document.body).paddingBottom) || 24;
                marco.style.maxHeight = Math.max(240, window.innerHeight - marco.getBoundingClientRect().top - margen) + 'px';
            };
            let espera = null;
            window.addEventListener('resize', () => { clearTimeout(espera); espera = setTimeout(ajustar, 150); });
            this.$watch('destacada', () => this.$nextTick(ajustar));
            this.$nextTick(() => {
                ajustar();
                const fila = aid && document.getElementById('asesor-' + aid);
                if (fila) fila.scrollIntoView({ block: 'center' });
                const th = this.$el.querySelector('th.col-destacada');
                if (th) th.scrollIntoView({ inline: 'center', block: 'nearest' });
            });
        },
        tiene(aid, sid) { return (this.asignados[aid] || []).includes(sid); },
        totalAsesor(aid) { return this.columnas.filter(c => this.tiene(aid, c.id)).length; },
        totalColumna(sid) { return this.asesores.filter(a => this.tiene(a.id, sid)).length; },
        pasa(a, clave) {
            if (clave === 'conectados') return a.modulo !== null;
            if (clave === 'sin') return this.totalAsesor(a.id) === 0;
            return true;
        },
        contar(clave) { return this.asesores.filter(a => this.pasa(a, clave)).length; },
        visibles() {
            const q = normal(this.buscar);
            return this.asesores.filter(a => this.pasa(a, this.filtro) && (!q || normal(a.nombre).includes(q)));
        },
        columnaGuia() { return this.destacada ? this.columnas.find(c => c.id === this.destacada) : null; },
        conexion(a) { return 'Conectado en el módulo ' + a.modulo + (ESTADOS[a.estado] ? ' · ' + ESTADOS[a.estado] : ''); },
        lineaConexion(a) { return 'Módulo ' + a.modulo + (ESTADOS[a.estado] ? ' · ' + ESTADOS[a.estado] : ''); },
        fijar(aid, sid, marcado) {
            const lista = (this.asignados[aid] || []).filter(x => x !== sid);
            this.asignados[aid] = marcado ? [...lista, sid] : lista;
        },
        // Botón "Deshacer" del toast: una sola vez; el toast queda en "Deshaciendo…" hasta que responde el servidor.
        botonDeshacer(d) {
            let usado = false;
            return { title: 'Deshacer', onClick: () => {
                if (usado) return;
                usado = true;
                sileo.show({ type: 'loading', title: 'Deshaciendo…', duration: null });
                this.alternar(d.a, d.h, d.marcado, true);
            } };
        },
        // Cambio inmediato en pantalla; la petición va detrás, en orden por asesor. Si falla, la casilla vuelve.
        alternar(a, h, marcado, esDeshacer = false) {
            const clave = a.id + ':' + h.id;
            this.fijar(a.id, h.id, marcado);
            this.guardando[clave] = true;
            this.pendientes[a.id] = (this.pendientes[a.id] || 0) + 1;
            const nombre = a.nombre.split(/\s+/).slice(0, 2).join(' ');
            this._cola = this._cola || {};
            this._cola[a.id] = (this._cola[a.id] || Promise.resolve()).then(() =>
                fetch(marcado ? ASIGNAR_URL : QUITAR_URL, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': TOKEN(), 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: a.id, servicio_id: h.id }),
                })
                .then(async r => ({ ok: r.ok, estado: r.status, datos: await r.json().catch(() => ({})) }))
                .then(({ ok, estado, datos }) => {
                    if (!ok || datos.success === false) {
                        const e = new Error(estado === 419 ? 'La sesión expiró. Recargue la página e inténtelo de nuevo.' : (datos.message || 'No se pudo guardar el cambio.'));
                        throw e;
                    }
                    if (--this.pendientes[a.id] === 0 && Array.isArray(datos.asignados)) this.asignados[a.id] = datos.asignados;
                    if (esDeshacer) { sileo.success({ title: 'Cambio deshecho' }); return; }
                    const quedan = this.totalColumna(h.id);
                    const button = this.botonDeshacer({ a, h, marcado: !marcado });
                    if (marcado) sileo.success({ title: 'Servicio asignado', description: nombre + ' ahora atiende ' + h.nombre + '.', button });
                    else if (quedan === 0) sileo.warning({ title: 'Servicio quitado', description: nombre + ' ya no atiende ' + h.nombre + '. Nadie más lo atiende.', button });
                    else sileo.success({ title: 'Servicio quitado', description: nombre + ' ya no atiende ' + h.nombre + '.', button });
                })
                .catch(e => {
                    this.pendientes[a.id]--;
                    this.fijar(a.id, h.id, !marcado);
                    sileo.error({ title: 'No se guardó el cambio', description: e instanceof TypeError ? 'No hay conexión con el servidor.' : e.message, duration: 7000 });
                })
                .finally(() => { delete this.guardando[clave]; }));
        },
    }));
});
</script>

@push('estilos')
<style>
[x-cloak] { display: none !important; }
.asignacion-vista { display: flex; flex-direction: column; gap: 1rem; font-variant-numeric: tabular-nums; }

/* ===== Asignación (2026-11, «La sala»): vista de lista de una sola superficie; la matriz va plana, sin pieza elevada ===== */

/* Barra: filtros, cómo se guarda y el buscador */
.asignacion-nota { display: inline-flex; align-items: center; gap: .375rem; margin: 0 0 0 .5rem; font-size: .8125rem; color: var(--tinta-tenue); }
.asignacion-nota svg { width: 15px; height: 15px; flex: none; color: var(--icono); }
@media (max-width: 1099px) { .asignacion-nota { display: none; } }

/* La matriz se desplaza por dentro: encabezado, columna del asesor, total y pie quedan fijos.
   Fondo, radio y roce los pone .superficie; aquí solo el desplazamiento.
   (El alto exacto lo pone init() con lo que queda de pantalla; esto es solo el valor inicial.) */
.matriz-marco {
    position: relative; overflow: auto; overscroll-behavior: contain; max-height: calc(100vh - 12rem);
    scrollbar-width: thin; scrollbar-color: #c3cfdf transparent;
}
.matriz { border-collapse: separate; border-spacing: 0; min-width: 100%; font-size: .875rem; color: var(--tinta); }
.matriz th, .matriz td { background: #ffffff; border-bottom: 1px solid var(--linea-suave); }

/* Encabezado: la sección arriba con una llave que abraza sus servicios; los servicios debajo */
.matriz thead th { position: sticky; z-index: 2; background: #ffffff; font-weight: 550; }
.matriz-secciones th { top: 0; height: 2.25rem; padding: .6875rem .75rem 0; vertical-align: top; text-align: center; white-space: nowrap; border-bottom: 0; }
.th-seccion { font-size: .8125rem; font-weight: 650 !important; color: var(--tinta); letter-spacing: .02em; border-left: 1px solid var(--linea); }
.th-seccion__texto { padding: 0 .5rem; }
.matriz-secciones .th-seccion:not(.th-seccion--sola)::after {
    content: ""; position: absolute; left: .875rem; right: .875rem; bottom: 0; height: .4375rem;
    border: 1px solid #cfd9e7; border-bottom: 0; border-radius: 6px 6px 0 0; pointer-events: none;
}
.matriz-columnas th { top: 2.25rem; height: 2.875rem; border-bottom: 1px solid var(--linea); }
.th-columna {
    min-width: 5.25rem; max-width: 8rem; padding: .25rem .5rem; text-align: center; vertical-align: middle;
    font-size: .78125rem; font-weight: 550; color: var(--tinta-2); transition: color .15s ease-out;
}
.th-columna__texto { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.25; text-wrap: balance; }
.th-seccion--sola { min-width: 5.25rem; max-width: 8rem; padding: .25rem .5rem !important; vertical-align: middle !important; white-space: normal !important;
                    border-bottom: 1px solid var(--linea) !important; font-size: .78125rem; }
.matriz thead .col-asesor, .matriz thead .col-total {
    top: 0; z-index: 4; vertical-align: bottom; padding: 0 1rem .9375rem; border-bottom: 1px solid var(--linea);
    text-align: left; font-size: .78125rem; font-weight: 550; color: var(--tinta-tenue); letter-spacing: 0;
}
.matriz thead .col-total { text-align: right; padding-right: 1.125rem; }
.matriz thead .col-activa, .matriz thead .col-destacada { color: var(--azul); }

/* Columna del asesor: nombre y, debajo, en palabras, si no atiende nada y dónde está conectado */
.col-asesor {
    position: sticky; left: 0; z-index: 1; width: 14rem; min-width: 14rem; max-width: 14rem; padding: 0 1rem;
    text-align: left; font-weight: 400; border-right: 1px solid var(--linea);
}
.matriz tbody .col-asesor { height: 2.75rem; }
.asesor-celda { display: flex; flex-direction: column; justify-content: center; gap: .125rem; min-width: 0; }
.asesor-nombre { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 550; line-height: 1.3; color: var(--tinta); }
.asesor-linea { display: flex; align-items: center; gap: .625rem; min-width: 0; font-size: .75rem; line-height: 1.3; color: var(--tinta-tenue); white-space: nowrap; }
.asesor-sin { flex: none; font-weight: 600; color: var(--error-texto); }
.asesor-conexion { display: inline-flex; align-items: center; gap: .3125rem; min-width: 0; }
.asesor-conexion__texto { overflow: hidden; text-overflow: ellipsis; }
.esqueleto--linea { height: .5625rem; }

/* Celdas: toda la celda recibe el clic */
.celda { height: 2.75rem; padding: 0; text-align: center; }
.celda__toque { display: flex; align-items: center; justify-content: center; min-height: 2.6875rem; padding: 0 .5rem; cursor: pointer; }
.celda__toque:has(.casilla:disabled) { cursor: progress; }
.inicio-seccion { border-left: 1px solid var(--linea); }

/* Total por asesor */
.col-total {
    position: sticky; right: 0; z-index: 1; width: 6rem; min-width: 6rem; padding: 0 1.125rem; text-align: right;
    font-weight: 650; color: var(--tinta); border-left: 1px solid var(--linea);
}
.total-cero { color: var(--error); }

/* Fila al pasar el mouse, columna al pasar el mouse y su cruce; la columna resaltada y la fila que llega desde Usuarios */
.matriz tbody tr:hover > * { background: #f6f9fd; }
.matriz .col-activa { background: #f5f8fd; }
.matriz tbody tr:hover > .col-activa { background: #e9f0fa; }
.matriz .col-destacada { background: var(--azul-claro); }
.matriz tbody tr:hover > .col-destacada { background: #dde8f7; }
.matriz tbody .fila-destacada > * { background: #eef4fc; }

/* Pie: cuántos asesores atienden cada servicio */
.matriz tfoot th, .matriz tfoot td {
    position: sticky; bottom: 0; z-index: 2; height: 2.75rem; background: #f7f9fc; border-top: 1px solid var(--linea); border-bottom: 0;
    font-size: .875rem; font-weight: 650; color: var(--tinta);
}
.matriz tfoot .col-asesor, .matriz tfoot .col-total { z-index: 3; }
.matriz tfoot .col-asesor { font-size: .78125rem; font-weight: 550; color: var(--tinta-tenue); }
.matriz tfoot .col-activa { color: var(--azul); }
.matriz tfoot .col-destacada { background: #e3ecf8; }

/* Casilla: la del layout (.casilla). Aquí solo lo propio de la celda: toda la celda la enciende y se hunde al pulsar */
.celda__toque:hover .casilla:not(:disabled) { border-color: var(--azul); }
.celda__toque:hover .casilla:checked:not(:disabled) { background: var(--azul-hover); border-color: var(--azul-hover); }
.celda__toque:active .casilla:not(:disabled) { scale: .88; }
.esqueleto-casilla { width: 1.125rem; height: 1.125rem; margin: 0 auto; border-radius: 6px; }

/* Pantallas angostas: las columnas fijas ceden espacio a las casillas */
@media (max-width: 900px) {
    .col-asesor { width: 11.5rem; min-width: 11.5rem; max-width: 11.5rem; padding: 0 .75rem; }
    .col-total { width: 4.75rem; min-width: 4.75rem; padding: 0 .875rem; }
}

@media (prefers-reduced-motion: reduce) {
    .th-columna { transition: none; }
    .celda__toque:active .casilla { scale: none; }
}
</style>
@endpush
@endsection
