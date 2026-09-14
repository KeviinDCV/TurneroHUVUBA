@extends('layouts.admin')

@section('title', 'Asignación')

@section('content')
{{-- Matriz de cobertura (AsignacionServicioController::index): asesores en filas y, en columnas, lo que el kiosco
     reparte, agrupado por sección. Cada casilla se guarda sola; abajo, cuántos asesores cubren cada columna. --}}
@php
    $columnasMatriz = collect($matriz['secciones'])->sum(fn ($s) => max(1, count($s['hijos'])));
    $filasEsqueleto = min(count($matriz['asesores']), 12);
@endphp
<div class="asignacion-vista max-w-7xl mx-auto space-y-4" x-data="matrizCobertura(@js($matriz))">
    <h1 class="sr-only">Asignación de servicios</h1>
    <!-- Lo que acaba de cambiar, con opción de deshacer -->
    <div class="aviso-flotante" :class="{ 'aviso-flotante--error': aviso && aviso.error }" role="status" x-show="aviso" x-transition.opacity x-cloak>
        <span x-text="aviso && aviso.texto"></span>
        <button type="button" x-show="aviso && aviso.deshacer" @click="deshacer()">Deshacer</button>
    </div>

    <div class="barra-vista">
        <div class="filtros-rapidos" role="group" aria-label="Filtrar asesores">
            @foreach ([3.25, 5.75, 6.5] as $ancho)
                <span class="filtro-rapido" data-esqueleto aria-hidden="true"><span class="esqueleto" style="width: {{ $ancho }}rem"></span></span>
            @endforeach
            <template x-for="f in filtros" :key="f.clave">
                <button type="button" class="filtro-rapido" :aria-pressed="(filtro === f.clave).toString()" @click="filtro = f.clave">
                    <span x-text="f.rotulo"></span> <span class="filtro-rapido__n" x-text="contar(f.clave)"></span>
                </button>
            </template>
        </div>
        <div class="buscador">
            <svg class="buscador__icono" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 110-15 7.5 7.5 0 010 15z"></path></svg>
            <input type="search" x-model.debounce.150ms="buscar" class="campo" placeholder="Buscar asesor" aria-label="Buscar asesor">
        </div>
    </div>

    <!-- Llegando desde "Nadie · Asignar": qué columna completar -->
    <div class="guia-columna" x-show="columnaGuia()" x-cloak>
        <span>Marca quién atenderá <b x-text="columnaGuia()?.nombre"></b>.</span>
        <button type="button" class="accion-icono" aria-label="Quitar resaltado" @click="destacada = null">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>

    <div class="superficie matriz-marco" x-ref="marco">
        <table class="matriz" @mouseleave="columnaActiva = null">
            <thead>
                {{-- El encabezado sale del servidor: coincide con las filas desde el primer pintado --}}
                <tr class="matriz-secciones">
                    <th scope="col" rowspan="2" class="col-asesor">Asesor</th>
                    @foreach ($matriz['secciones'] as $s)
                        @if (count($s['hijos']))
                            <th scope="colgroup" colspan="{{ count($s['hijos']) }}" class="th-seccion">{{ $s['nombre'] }}</th>
                        @else
                            <th scope="col" rowspan="2" class="th-seccion th-seccion--sola" :class="{ 'col-activa': columnaActiva === {{ $s['id'] }}, 'col-destacada': destacada === {{ $s['id'] }} }"
                                title="{{ $s['nombre'] }}{{ $s['ocultar'] ? ' · oculto en el TV: se llama por código' : '' }}">{{ $s['nombre'] }}</th>
                        @endif
                    @endforeach
                    <th scope="col" rowspan="2" class="col-total">Total</th>
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
                        <td class="col-asesor"><span class="esqueleto" style="width: {{ [10, 8, 11.5, 9][$i % 4] }}rem"></span></td>
                        @for ($c = 0; $c < $columnasMatriz; $c++)
                            <td class="celda"><span class="esqueleto esqueleto-casilla"></span></td>
                        @endfor
                        <td class="col-total"><span class="esqueleto" style="width: 1rem"></span></td>
                    </tr>
                @endfor
            </tbody>
            <tbody>
                <template x-for="a in visibles()" :key="a.id">
                    <tr :class="{ 'fila-destacada': a.id === filaDestacada }" :id="'asesor-' + a.id">
                        <th scope="row" class="col-asesor">
                            <div class="asesor-celda">
                                <span class="asesor-nombre" x-text="a.nombre" :title="a.nombre"></span>
                                <span class="asesor-conexion" x-show="a.modulo !== null" :class="'conexion--' + a.estado" :title="conexion(a)">
                                    <span class="asesor-punto" aria-hidden="true"></span><span x-text="'M' + a.modulo"></span>
                                </span>
                            </div>
                        </th>
                        <template x-for="h in columnas" :key="h.id">
                            <td class="celda" :class="{ 'inicio-seccion': h.primera, 'col-activa': columnaActiva === h.id, 'col-destacada': destacada === h.id }"
                                @mouseenter="columnaActiva = h.id">
                                <input type="checkbox" class="casilla-matriz" :checked="tiene(a.id, h.id)" :disabled="!!guardando[a.id + ':' + h.id]"
                                       :aria-label="(tiene(a.id, h.id) ? 'Quitar ' : 'Asignar ') + h.nombre + ' a ' + a.nombre"
                                       @change="alternar(a, h, $event.target.checked)">
                            </td>
                        </template>
                        <td class="col-total" :class="{ 'total-cero': totalAsesor(a.id) === 0 }" x-text="totalAsesor(a.id)"
                            :title="totalAsesor(a.id) === 0 ? 'No atiende ningún servicio' : ''"></td>
                    </tr>
                </template>
            </tbody>
            <tfoot x-cloak>
                <tr>
                    <th scope="row" class="col-asesor">Asesores</th>
                    <template x-for="h in columnas" :key="h.id">
                        <td class="celda" :class="{ 'inicio-seccion': h.primera, 'col-destacada': destacada === h.id }">
                            <span x-show="totalColumna(h.id) > 0" x-text="totalColumna(h.id)"></span>
                            <span class="nadie" x-show="totalColumna(h.id) === 0" title="Ningún asesor atiende este servicio">Nadie</span>
                        </td>
                    </template>
                    <td class="col-total"></td>
                </tr>
            </tfoot>
        </table>
        <p class="vacio-matriz" x-show="!visibles().length" x-cloak>
            @if (count($matriz['asesores']))
                Ningún asesor coincide. <button type="button" class="enlace-panel" @click="filtro = 'todos'; buscar = ''">Quitar filtros</button>
            @else
                Todavía no hay asesores. <a class="enlace-panel" href="{{ route('admin.users') }}">Créalos en Usuarios</a> con el rol Asesor.
            @endif
        </p>
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
        aviso: null,
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
            const ajustar = () => {
                const marco = this.$refs.marco;
                marco.style.maxHeight = Math.max(240, window.innerHeight - marco.getBoundingClientRect().top - 24) + 'px';
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
        fijar(aid, sid, marcado) {
            const lista = (this.asignados[aid] || []).filter(x => x !== sid);
            this.asignados[aid] = marcado ? [...lista, sid] : lista;
        },
        avisar(texto, extra = {}) {
            this.aviso = { texto, ...extra };
            clearTimeout(this._aviso);
            this._aviso = setTimeout(() => this.aviso = null, extra.error ? 7000 : 6000);
        },
        deshacer() {
            const d = this.aviso && this.aviso.deshacer;
            this.aviso = null;
            if (d) this.alternar(d.a, d.h, d.marcado, true);
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
                        const e = new Error(estado === 419 ? 'La sesión expiró. Recarga la página e inténtalo de nuevo.' : (datos.message || 'No se pudo guardar el cambio.'));
                        throw e;
                    }
                    if (--this.pendientes[a.id] === 0 && Array.isArray(datos.asignados)) this.asignados[a.id] = datos.asignados;
                    if (esDeshacer) { this.avisar('Cambio deshecho.'); return; }
                    const quedan = this.totalColumna(h.id);
                    this.avisar(marcado
                        ? nombre + ' ahora atiende ' + h.nombre + '.'
                        : nombre + ' ya no atiende ' + h.nombre + '.' + (quedan === 0 ? ' Nadie más lo atiende.' : ''),
                        { deshacer: { a, h, marcado: !marcado } });
                })
                .catch(e => {
                    this.pendientes[a.id]--;
                    this.fijar(a.id, h.id, !marcado);
                    this.avisar(e instanceof TypeError ? 'No hay conexión con el servidor. El cambio no se guardó.' : e.message, { error: true });
                })
                .finally(() => { delete this.guardando[clave]; }));
        },
    }));
});
</script>

@push('estilos')
<style>
[x-cloak] { display: none !important; }
.asignacion-vista { font-variant-numeric: tabular-nums; }

.guia-columna { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .45rem .5rem .45rem .9rem;
                border-radius: .5rem; background: #e8f0fb; color: #0f2547; font-size: .875rem; }

/* La matriz se desplaza por dentro: encabezado, columna del asesor y pie quedan fijos */
.matriz-marco { overflow: auto; max-height: calc(100vh - var(--admin-header-h, 3.25rem) - 7.25rem); overscroll-behavior: contain; }
/* (el alto exacto lo pone init() con lo que queda de pantalla; esto es solo el valor inicial) */
.matriz { border-collapse: separate; border-spacing: 0; min-width: 100%; font-size: .875rem; }
.matriz th, .matriz td { background: #ffffff; border-bottom: 1px solid #eef1f6; }
.matriz thead th { position: sticky; z-index: 2; background: #f6f8fc; color: #6b7280; }
.matriz-secciones th { top: 0; height: 2rem; font-size: .75rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; text-align: center; padding: 0 .5rem; }
.matriz-columnas th { top: 2rem; height: 2.75rem; }
.matriz thead .col-asesor, .matriz thead .col-total { top: 0; z-index: 4; text-align: left; font-size: .75rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
.th-seccion { color: #0f2547 !important; border-left: 1px solid #dfe5ee; }
.th-columna { min-width: 5rem; max-width: 7.5rem; padding: .25rem .4rem; text-align: center; vertical-align: middle; font-size: .75rem; font-weight: 500; color: #374151 !important; }
.th-columna__texto { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.2; }
.col-asesor { position: sticky; left: 0; z-index: 1; width: 12.75rem; min-width: 12.75rem; max-width: 12.75rem; padding: 0 .9rem; text-align: left; font-weight: 400; border-right: 1px solid #eef1f6; }
.asesor-celda { display: flex; align-items: center; gap: .5rem; min-width: 0; }
.matriz tbody .col-asesor { height: 2.375rem; }
.th-seccion--sola { min-width: 5rem; max-width: 7.5rem; white-space: normal; line-height: 1.2; }
.asesor-nombre { flex: 0 1 auto; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #111827; font-weight: 500; }
.asesor-conexion { display: inline-flex; align-items: center; gap: .3rem; flex-shrink: 0; font-size: .75rem; color: #6b7280; }
.asesor-punto { width: .45rem; height: .45rem; border-radius: 9999px; background: #9ca3af; }
.conexion--atendiendo .asesor-punto { background: #d08700; }
.conexion--libre .asesor-punto { background: #00a63e; }
.conexion--descanso .asesor-punto { background: #155dfc; }
.conexion--canal .asesor-punto { background: #f54900; }
.celda { text-align: center; padding: 0 .4rem; height: 2.375rem; }
.inicio-seccion { border-left: 1px solid #dfe5ee; }
.col-total { position: sticky; right: 0; z-index: 1; min-width: 3.5rem; padding: 0 .9rem; text-align: right; font-weight: 600; color: #0f2547; border-left: 1px solid #eef1f6; }
.total-cero { color: #b7191c; }
.matriz tbody tr:hover > * { background: #f8fafd; }
.matriz .col-activa { background: #f3f7fd; }
.matriz tbody tr:hover > .col-activa { background: #ebf2fc; }
.matriz .col-destacada { background: #e8f0fb !important; }
.fila-destacada > * { background: #f1f6fd !important; }
.matriz tfoot th, .matriz tfoot td { position: sticky; bottom: 0; z-index: 2; background: #f6f8fc; height: 2.25rem; border-top: 1px solid #dfe5ee; border-bottom: 0;
                                     font-size: .8125rem; font-weight: 600; color: #0f2547; }
.matriz tfoot .col-asesor { z-index: 3; font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; }
.nadie { display: inline-block; padding: .1rem .4rem; border-radius: .375rem; background: #ffe2e2; color: #9f0712; font-size: .75rem; font-weight: 600; }

/* Casilla propia: cuadro azul institucional al marcar */
.casilla-matriz { appearance: none; -webkit-appearance: none; width: 1.125rem; height: 1.125rem; margin: 0; vertical-align: middle; cursor: pointer;
                  border: 1.5px solid #b9c3d3; border-radius: .3rem; background: #ffffff; display: inline-grid; place-content: center;
                  transition: background-color .12s ease, border-color .12s ease; }
.casilla-matriz::before { content: ''; width: .6rem; height: .6rem; transform: scale(0); transition: transform .12s ease;
                          clip-path: polygon(14% 44%, 0 65%, 50% 100%, 100% 16%, 80% 0%, 43% 62%); background: #ffffff; }
.casilla-matriz:checked { background: #064b9e; border-color: #064b9e; }
.casilla-matriz:checked::before { transform: scale(1); }
.casilla-matriz:hover { border-color: #064b9e; }
.casilla-matriz:focus-visible { outline: 2px solid #064b9e; outline-offset: 2px; }
.casilla-matriz:disabled { opacity: .55; cursor: progress; }
.esqueleto-casilla { width: 1.125rem; height: 1.125rem; margin: 0 auto; border-radius: .3rem; }
.vacio-matriz { padding: 2rem; text-align: center; font-size: .875rem; color: #6b7280; }
@media (prefers-reduced-motion: reduce) { .casilla-matriz, .casilla-matriz::before { transition: none; } }
</style>
@endpush
@endsection
