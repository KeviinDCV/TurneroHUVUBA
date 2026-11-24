@extends('layouts.admin')

@section('title', 'Módulos')

@section('acciones_pagina')
    {{-- La única acción azul de la vista. El formulario escucha 'abrir-modulo' en window. --}}
    <button type="button" class="btn-primario" @click="$dispatch('abrir-modulo', { modo: 'crear' })">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
        Nuevo módulo
    </button>
@endsection

@section('content')
{{-- Módulos en una sola vista: catálogo + ocupación de ahora mismo (CajaController::index, con el mismo
     cálculo del Inicio). La ocupación se refresca cada 15 s desde GET /api/admin/tablero.
     Las tarjetas son las del Inicio (.sala-modulo): número coloreado por estado, estado con palabra, asesor y,
     en el pie, el turno en curso; aquí además llevan editar y eliminar. --}}
<div class="vista-modulos"
     x-data="modulosVista(@js($modulos), @js($turnosPorModulo))">

    @if (session('success'))
        <div class="aviso aviso--ok" role="status">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8.5 12.25 2.5 2.5 4.75-5"/></svg>
            <p>{{ session('success') }}</p>
        </div>
    @endif
    @if (session('error'))
        <div class="aviso aviso--error" role="alert">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v4.5M12 16h.01"/></svg>
            <p>{{ session('error') }}</p>
        </div>
    @endif

    <!-- Filtros rápidos y búsqueda -->
    <div class="barra-vista">
        <div class="filtros-rapidos" role="group" aria-label="Filtrar módulos">
            {{-- Mientras arranca Alpine: la forma de los filtros (init() los quita) --}}
            @foreach ([4.1, 4.4, 5.6, 4.8] as $ancho)
                <span class="filtro-rapido vm-filtro-esq" data-esqueleto aria-hidden="true"><span class="esqueleto" style="width: {{ $ancho }}rem"></span></span>
            @endforeach
            <template x-for="f in filtros" :key="f.clave">
                <button type="button" class="filtro-rapido" :aria-pressed="(filtro === f.clave).toString()" @click="filtro = f.clave">
                    <span x-text="f.rotulo"></span> <span class="filtro-rapido__n" x-text="contar(f.clave)"></span>
                </button>
            </template>
        </div>
        <div class="buscador">
            <svg class="buscador__icono" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z"/></svg>
            <input type="search" x-model.debounce.150ms="buscar" class="campo" placeholder="Número, nombre, ubicación o asesor" aria-label="Buscar módulo">
        </div>
    </div>

    <!-- Tarjetas de módulo -->
    <div class="vm-modulos">
        {{-- Mientras arranca Alpine: una tarjeta con la forma de cada módulo, para que nada salte (init() las quita) --}}
        @foreach ($modulos as $m)
            <div class="vm-modulo" data-esqueleto aria-hidden="true">
                <div class="vm-modulo__cabeza">
                    <span class="esqueleto vm-esq-num"></span>
                    <span class="esqueleto vm-esq-chip" style="width: {{ [5.5, 4.25, 6.25, 4.75][$loop->index % 4] }}rem"></span>
                </div>
                <span class="vm-modulo__quien"><span class="esqueleto" style="width: {{ [72, 58, 80, 64][$loop->index % 4] }}%"></span></span>
                <span class="vm-modulo__lugar"><span class="esqueleto" style="width: {{ [48, 60, 40, 54][$loop->index % 4] }}%"></span></span>
                <div class="vm-modulo__pie"><span class="esqueleto" style="width: {{ [36, 44, 30, 40][$loop->index % 4] }}%"></span></div>
            </div>
        @endforeach
        <template x-for="m in visibles()" :key="m.id">
            <article class="vm-modulo" :class="'vm-modulo--' + uso(m).tono + (m.activa ? '' : ' vm-modulo--inactivo') + (m.asesor ? ' vm-modulo--abierto' : '')">
                <div class="vm-modulo__cabeza">
                    <span class="vm-modulo__num" x-text="String(m.numero).padStart(2, '0')"></span>
                    <span class="estado-chip" :class="'estado-chip--' + uso(m).tono" :title="uso(m).texto"><span class="vm-chip__texto" x-text="uso(m).texto"></span></span>
                </div>
                {{-- Con asesor: quién está y, debajo, el módulo y dónde queda. Sin asesor: el nombre del módulo y dónde queda. --}}
                <p class="vm-modulo__quien" :class="!m.asesor && 'is-vacio'" :title="quien(m)" x-text="m.asesor ? m.asesor.nombre : m.nombre"></p>
                <p class="vm-modulo__lugar" :title="[m.nombre, m.ubicacion, m.descripcion].filter(Boolean).join(' · ')"
                   x-text="m.asesor ? [m.nombre, m.ubicacion].filter(Boolean).join(' · ') : (m.ubicacion || m.descripcion || '')"></p>
                <div class="vm-modulo__pie">
                    <div class="vm-modulo__ahora">
                        <template x-if="m.turno">
                            <span class="vm-modulo__en-curso">
                                <b class="vm-modulo__turno" x-text="m.turno.codigo"></b>
                                <span class="vm-modulo__min" :class="{ 'is-alerta': largo(m) }" :title="largo(m) ? 'Pasó el límite de atención de ' + limite + ' min' : null"
                                      x-text="m.turno.minutos !== null ? m.turno.minutos + ' min' : ''"></span>
                            </span>
                        </template>
                        <template x-if="!m.turno && m.asesor">
                            <span class="vm-modulo__vacio" x-text="m.estado === 'descanso' ? 'En descanso' : (m.estado === 'canal' ? 'En canal no presencial' : 'Sin turno')"></span>
                        </template>
                    </div>
                    <div class="vm-modulo__acciones">
                        <button type="button" class="accion-icono" :aria-label="'Editar módulo ' + m.numero" title="Editar"
                                @click="$dispatch('abrir-modulo', { modo: 'editar', modulo: m })">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4L18.5 9.5a2.12 2.12 0 0 0-3-3L5 17v3Z"/><path d="m14 8 3 3"/></svg>
                        </button>
                        <button type="button" class="accion-icono accion-icono--peligro" :aria-label="'Eliminar módulo ' + m.numero" title="Eliminar"
                                @click="$dispatch('eliminar-modulo', { modulo: m, turnos: historial[m.id] || 0 })">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3"/></svg>
                        </button>
                    </div>
                </div>
            </article>
        </template>
    </div>

    <!-- Vacío: sin módulos, o ninguno coincide con el filtro o la búsqueda -->
    <div class="superficie vacio" x-show="!visibles().length" x-cloak role="status">
        <p class="vacio__titulo" x-text="modulos.length ? 'Ningún módulo coincide' : 'Todavía no hay módulos'"></p>
        <p class="vacio__texto" x-text="modulos.length ? 'Pruebe con otro número, nombre, ubicación o asesor.' : 'Los asesores eligen uno de estos módulos al empezar a atender.'"></p>
        <button type="button" class="enlace-panel" x-show="modulos.length" @click="buscar = ''; filtro = 'todos'">Quitar filtros</button>
        <button type="button" class="enlace-panel" x-show="!modulos.length" @click="$dispatch('abrir-modulo', { modo: 'crear' })">Crear el primero</button>
    </div>

    <!-- Crear / editar -->
    <div x-data="formularioModulo()" @abrir-modulo.window="abrir($event.detail)" @keydown.escape.window="cerrar()">
        <div class="modal-panel" x-show="abierto" x-cloak x-transition.opacity @click.self="cerrar()">
            <div class="modal-panel__caja" role="dialog" aria-modal="true" aria-labelledby="vm-form-titulo">
                <div class="modal-panel__cabeza">
                    <h2 id="vm-form-titulo" x-text="modo === 'crear' ? 'Nuevo módulo' : 'Editar el módulo ' + numeroOriginal"></h2>
                    <button type="button" class="accion-icono" @click="cerrar()" aria-label="Cerrar">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                    </button>
                </div>
                <form @submit.prevent="guardar()" class="form-panel">
                    <div class="aviso aviso--error" role="alert" x-show="errores.general">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v4.5M12 16h.01"/></svg>
                        <p x-text="errores.general"></p>
                    </div>
                    <div class="form-panel__fila vm-form__fila-num">
                        <label class="form-campo">
                            <span>Número</span>
                            <input type="number" min="1" required inputmode="numeric" x-model="datos.numero_caja" class="campo vm-campo-num" x-ref="primero"
                                   :aria-invalid="errores.numero_caja ? 'true' : 'false'">
                            <small x-show="errores.numero_caja" x-text="errores.numero_caja"></small>
                        </label>
                        <label class="form-campo">
                            <span>Nombre</span>
                            <input type="text" required maxlength="255" x-model="datos.nombre" class="campo" placeholder="Ej.: Caja 3"
                                   :aria-invalid="errores.nombre ? 'true' : 'false'">
                            <small x-show="errores.nombre" x-text="errores.nombre"></small>
                        </label>
                    </div>
                    <label class="form-campo">
                        <span>Ubicación <span class="form-opcional">(opcional)</span></span>
                        <input type="text" maxlength="255" x-model="datos.ubicacion" class="campo" placeholder="Ej.: Primer piso"
                               :aria-invalid="errores.ubicacion ? 'true' : 'false'">
                        <small x-show="errores.ubicacion" x-text="errores.ubicacion"></small>
                    </label>
                    <div class="form-campo">
                        <span id="vm-estado-rotulo">Estado</span>
                        <div class="vm-estado-fila">
                            <div class="segmentado vm-estado" role="radiogroup" aria-labelledby="vm-estado-rotulo" aria-describedby="vm-estado-ayuda">
                                <label>
                                    <input type="radio" name="estado" value="activa" x-model="datos.estado">
                                    <span>Activo</span>
                                </label>
                                <label>
                                    <input type="radio" name="estado" value="inactiva" x-model="datos.estado">
                                    <span>Inactivo</span>
                                </label>
                            </div>
                            <p id="vm-estado-ayuda" class="form-ayuda vm-estado-ayuda"
                               x-text="datos.estado === 'activa' ? 'Los asesores lo pueden elegir al empezar a atender.' : 'Ningún asesor lo puede elegir. Su historial se conserva.'"></p>
                        </div>
                        <div class="aviso aviso--alerta vm-estado-aviso" role="alert" x-show="asesor && datos.estado === 'inactiva'" x-cloak>
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="m21.7 18-8-14a2 2 0 0 0-3.4 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                            <p>Lo está usando <b x-text="asesor"></b>. Si lo pone inactivo, esa persona ya no podrá llamar turnos desde este módulo.</p>
                        </div>
                    </div>
                    <label class="form-campo">
                        <span>Descripción <span class="form-opcional">(opcional)</span></span>
                        <textarea rows="2" maxlength="500" x-model="datos.descripcion" class="campo campo--area"
                                  :aria-invalid="errores.descripcion ? 'true' : 'false'"></textarea>
                        <small x-show="errores.descripcion" x-text="errores.descripcion"></small>
                    </label>
                    <div class="modal-panel__pie">
                        <button type="button" class="btn-secundario" @click="cerrar()">Cancelar</button>
                        <button type="submit" class="btn-primario" :disabled="guardando" :aria-busy="guardando.toString()" x-text="guardando ? 'Guardando…' : (modo === 'crear' ? 'Crear módulo' : 'Guardar cambios')"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Eliminar -->
    <div x-data="eliminarModulo()" @eliminar-modulo.window="abrir($event.detail)" @keydown.escape.window="cerrar()">
        <div class="modal-panel" x-show="abierto" x-cloak x-transition.opacity @click.self="cerrar()">
            <div class="modal-panel__caja modal-panel__caja--angosta" role="alertdialog" aria-modal="true" aria-labelledby="vm-eliminar-titulo" aria-describedby="vm-eliminar-texto">
                <div class="modal-panel__cabeza">
                    <h2 id="vm-eliminar-titulo" x-text="enUso ? 'El módulo ' + (modulo && modulo.numero) + ' está en uso' : '¿Eliminar el módulo ' + (modulo && modulo.numero) + '?'"></h2>
                    <button type="button" class="accion-icono" @click="cerrar()" aria-label="Cerrar">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                    </button>
                </div>
                <div class="form-panel">
                    <div id="vm-eliminar-texto" class="vm-eliminar__texto">
                        <p class="form-texto" x-show="enUso">
                            Lo está usando <b x-text="modulo && modulo.asesor ? modulo.asesor.nombre : 'un asesor'"></b>. Podrá eliminarlo cuando lo libere.
                        </p>
                        <p class="form-texto vm-eliminar__otra" x-show="enUso">
                            El módulo se libera cuando esa persona cierra su sesión. También puede cerrarla usted desde
                            <a href="{{ route('admin.dashboard') }}" class="enlace-panel">Inicio</a>, con «Limpiar sesiones».
                        </p>
                        <div class="aviso aviso--error" role="note" x-show="!enUso">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="m21.7 18-8-14a2 2 0 0 0-3.4 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                            <p>
                                <span x-show="turnos > 0">Los <b x-text="turnos.toLocaleString('es-CO')"></b> turnos atendidos en este módulo quedarán sin módulo en el historial y en los informes.</span>
                                <span x-show="!turnos">No tiene turnos en el historial.</span>
                                Esta acción no se puede deshacer.
                            </p>
                        </div>
                        <p class="form-texto vm-eliminar__otra" x-show="!enUso && modulo && modulo.activa">
                            Si solo quiere dejar de usarlo,
                            <button type="button" class="enlace-panel vm-inactivar" @click="inactivar()">póngalo como inactivo</button>:
                            así conserva su historia.
                        </p>
                    </div>
                    <div class="aviso aviso--error" role="alert" x-show="error">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v4.5M12 16h.01"/></svg>
                        <p x-text="error"></p>
                    </div>
                    <div class="modal-panel__pie">
                        <button type="button" class="btn-secundario" x-ref="cancelar" @click="cerrar()" x-text="enUso ? 'Entendido' : 'Cancelar'"></button>
                        <button type="button" class="btn-peligro" x-show="!enUso" :disabled="eliminando" :aria-busy="eliminando.toString()" @click="confirmar()" x-text="eliminando ? 'Eliminando…' : 'Eliminar módulo'"></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    const TABLERO_URL = @json(route('api.admin.tablero'));
    const CAJAS_URL = @json(route('admin.cajas'));
    // Minutos de atención a partir de los cuales el tiempo del turno en curso se pinta en rojo (el mismo umbral del Inicio)
    const LIMITE_ATENCION = @json((int) config('panel.umbrales.atencion', 20));
    const TOKEN = () => document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    // Estado de ocupación: la palabra y el tono del estado-chip (y del número), los mismos del Inicio
    const USO = {
        atendiendo: ['Atendiendo', 'ok'],
        libre: ['Libre', 'info'],
        descanso: ['En descanso', 'alerta'],
        canal: ['Canal no presencial', 'neutro'],
        cerrado: ['Sin asesor', 'neutro'],
        inhabilitado: ['Inactivo', 'neutro'],
    };
    const EN_USO = ['atendiendo', 'libre', 'descanso', 'canal'];

    Alpine.data('modulosVista', (inicial, historial) => ({
        modulos: inicial,
        historial: historial || {},
        limite: LIMITE_ATENCION,
        filtro: 'todos',
        buscar: '',
        filtros: [
            { clave: 'todos', rotulo: 'Todos' },
            { clave: 'uso', rotulo: 'En uso' },
            { clave: 'sin', rotulo: 'Sin asesor' },
            { clave: 'inactivos', rotulo: 'Inactivos' },
        ],
        init() {
            this.$el.querySelectorAll('[data-esqueleto]').forEach(e => e.remove());
            // Solo la ocupación cambia sola; el catálogo cambia al guardar (y la página se recarga).
            setInterval(() => { if (!document.hidden) this.refrescar(); }, 15000);
        },
        pasa(m, clave) {
            if (clave === 'uso') return EN_USO.includes(m.estado);
            if (clave === 'sin') return m.estado === 'cerrado';
            if (clave === 'inactivos') return !m.activa;
            return true;
        },
        contar(clave) { return this.modulos.filter(m => this.pasa(m, clave)).length; },
        visibles() {
            const q = this.buscar.trim().toLowerCase();
            return this.modulos.filter(m => this.pasa(m, this.filtro) && (!q || [m.numero, m.nombre, m.ubicacion, m.descripcion, m.asesor && m.asesor.nombre]
                .some(v => String(v ?? '').toLowerCase().includes(q))));
        },
        uso(m) { const [texto, tono] = USO[m.estado] || [m.estado, 'neutro']; return { texto, tono }; },
        largo(m) { return !!(m.turno && m.turno.minutos !== null && m.turno.minutos > this.limite); },
        quien(m) {
            if (!m.asesor) return m.nombre || null;
            const n = m.atendidos_hoy;
            return m.asesor.nombre + (n === null || n === undefined ? '' : ' · ' + n + (n === 1 ? ' atendido hoy' : ' atendidos hoy'));
        },
        refrescar() {
            fetch(TABLERO_URL, { headers: { Accept: 'application/json' }, cache: 'no-store' })
                .then(r => r.ok ? r.json() : Promise.reject(r.status))
                .then(d => {
                    if (d.umbrales && d.umbrales.atencion) this.limite = d.umbrales.atencion;
                    const vivos = Object.fromEntries(d.modulos.map(x => [x.id, x]));
                    this.modulos = this.modulos.map(m => vivos[m.id]
                        ? { ...m, estado: vivos[m.id].estado, asesor: vivos[m.id].asesor, turno: vivos[m.id].turno, atendidos_hoy: vivos[m.id].atendidos_hoy }
                        : m);
                })
                .catch(e => console.warn('No se pudo actualizar la ocupación de los módulos:', e));
        },
    }));

    function enviar(url, datos, metodo) {
        const cuerpo = new FormData();
        Object.entries(datos).forEach(([k, v]) => cuerpo.append(k, v ?? ''));
        if (metodo) cuerpo.append('_method', metodo);
        return fetch(url, { method: 'POST', body: cuerpo, headers: { 'X-CSRF-TOKEN': TOKEN(), 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } })
            .then(async r => ({ ok: r.ok, datos: await r.json().catch(() => ({})) }));
    }

    // Devuelve el foco al botón que abrió el modal (si sigue en la página)
    function devolverFoco(el) { if (el && el.isConnected && typeof el.focus === 'function') el.focus(); }
    // Enfoca un campo del modal; si el modal aún no se ve (su transición de entrada no ha empezado), reintenta
    function enfocar(el) {
        if (!el) return;
        el.focus();
        if (document.activeElement !== el) setTimeout(() => el.focus(), 60);
    }

    Alpine.data('formularioModulo', () => ({
        abierto: false, modo: 'crear', id: null, guardando: false, errores: {},
        numeroOriginal: '', asesor: null, origen: null,
        datos: { numero_caja: '', nombre: '', ubicacion: '', estado: 'activa', descripcion: '' },
        abrir({ modo, modulo, estado }) {
            this.modo = modo; this.errores = {}; this.guardando = false;
            this.id = modulo ? modulo.id : null;
            this.numeroOriginal = modulo ? modulo.numero : '';
            // Quién lo ocupa ahora (para advertir antes de ponerlo inactivo)
            this.asesor = modulo && modulo.activa && modulo.asesor ? modulo.asesor.nombre : null;
            this.datos = modulo
                ? { numero_caja: modulo.numero, nombre: modulo.nombre || '', ubicacion: modulo.ubicacion || '', estado: estado || (modulo.activa ? 'activa' : 'inactiva'), descripcion: modulo.descripcion || '' }
                : { numero_caja: '', nombre: '', ubicacion: '', estado: 'activa', descripcion: '' };
            this.origen = document.activeElement;
            this.abierto = true;
            this.$nextTick(() => enfocar(this.$refs.primero));
        },
        cerrar() {
            if (!this.abierto || this.guardando) return;
            this.abierto = false;
            devolverFoco(this.origen);
        },
        guardar() {
            this.guardando = true; this.errores = {};
            const url = this.modo === 'crear' ? CAJAS_URL : CAJAS_URL + '/' + this.id;
            enviar(url, this.datos, this.modo === 'crear' ? null : 'PUT').then(({ ok, datos }) => {
                if (ok && datos.success !== false) { location.reload(); return; }
                this.guardando = false;
                const e = datos.errors || {};
                this.errores = Object.fromEntries(Object.entries(e).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]));
                if (!Object.keys(this.errores).length) this.errores = { general: datos.message || 'No se pudo guardar el módulo.' };
            }).catch(() => { this.guardando = false; this.errores = { general: 'Error de conexión. Inténtelo de nuevo.' }; });
        },
    }));

    Alpine.data('eliminarModulo', () => ({
        abierto: false, modulo: null, turnos: 0, eliminando: false, error: '', origen: null,
        get enUso() { return !!(this.modulo && this.modulo.activa && EN_USO.includes(this.modulo.estado)); },
        abrir({ modulo, turnos }) {
            this.modulo = modulo; this.turnos = turnos; this.error = ''; this.eliminando = false;
            this.origen = document.activeElement;
            this.abierto = true;
            this.$nextTick(() => enfocar(this.$refs.cancelar));
        },
        cerrar() {
            if (!this.abierto || this.eliminando) return;
            this.abierto = false;
            devolverFoco(this.origen);
        },
        // «Póngalo como inactivo»: abre el formulario de edición con Inactivo ya marcado; se guarda allá.
        inactivar() {
            const m = this.modulo;
            this.abierto = false;
            devolverFoco(this.origen);   // el foco vuelve a la tarjeta, y el formulario lo devuelve ahí al cerrarse
            this.$dispatch('abrir-modulo', { modo: 'editar', modulo: m, estado: 'inactiva' });
        },
        confirmar() {
            this.eliminando = true; this.error = '';
            fetch(CAJAS_URL + '/' + this.modulo.id, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': TOKEN(), 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } })
                .then(async r => ({ ok: r.ok, datos: await r.json().catch(() => ({})) }))
                .then(({ ok, datos }) => {
                    if (ok && datos.success !== false) { location.reload(); return; }
                    this.eliminando = false; this.error = datos.message || 'No se pudo eliminar el módulo.';
                })
                .catch(() => { this.eliminando = false; this.error = 'Error de conexión. Inténtelo de nuevo.'; });
        },
    }));
});
</script>

@push('estilos')
<style>
/* ===== Módulos (2026-11): las tarjetas de módulo del Inicio, con la administración del catálogo (ver DESIGN.md) ===== */
.vista-modulos { display: flex; flex-direction: column; gap: 1rem; font-variant-numeric: tabular-nums; }

/* Avisos, vacío, modales y control segmentado: las piezas del layout (.aviso, .vacio, .modal-panel, .segmentado). Aquí solo medidas propias. */

/* Barra: la forma de los filtros mientras arranca Alpine */
.vm-filtro-esq { cursor: default; }
.vm-filtro-esq .esqueleto { height: .625rem; }

/* Tarjetas: las del Inicio (.sala-modulo, 138 px). Número coloreado por estado, estado con palabra, asesor, y el pie con el turno en curso */
/* 13rem y no los 12,5 del Inicio: aquí el pie lleva además editar y eliminar, y a 1440 px «A-900 156 min» se cortaba */
.vm-modulos { display: grid; grid-template-columns: repeat(auto-fill, minmax(14.5rem, 1fr)); gap: .75rem; }
/* Sin tarjetas que mostrar, la rejilla vacía no ocupa su hueco: el vacío queda a 16 px de la barra */
.vm-modulos:not(:has(> .vm-modulo)) { display: none; }
.vm-modulo {
    min-width: 0; display: flex; flex-direction: column; gap: .375rem; min-height: 8.5rem; padding: .875rem 1rem .75rem;
    background: #fff; border-radius: .875rem; box-shadow: var(--roce);
}
.vm-modulo__cabeza { display: flex; align-items: center; justify-content: space-between; gap: .5rem; min-width: 0; min-height: 1.5rem; }
.vm-modulo__num { flex: none; font-size: 1.5rem; line-height: 1; font-weight: 650; letter-spacing: -.02em; color: var(--azul); font-variant-numeric: tabular-nums; }
.vm-modulo--info .vm-modulo__num { color: var(--tinta-tenue); }
.vm-modulo--alerta .vm-modulo__num { color: var(--alerta-tinta); }
.vm-modulo--neutro .vm-modulo__num { color: #6d7f99; }
.vm-modulo__cabeza .estado-chip { min-width: 0; }
.vm-chip__texto { min-width: 0; overflow: hidden; text-overflow: ellipsis; }
.vm-modulo__quien {
    margin: 0; min-height: 1.3125rem; font-size: .875rem; line-height: 1.5; font-weight: 600; color: var(--tinta);
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.vm-modulo__quien.is-vacio { font-weight: 550; color: var(--tinta-suave); }
.vm-modulo__lugar {
    margin: 0; min-height: 1.125rem; font-size: .75rem; line-height: 1.5; color: var(--tinta-tenue);
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
/* El pie: el corte punteado solo cuando el módulo tiene asesor (ahí puede haber un turno); si no, una línea quieta */
.vm-modulo__pie {
    margin-top: auto; display: flex; align-items: center; justify-content: space-between; gap: .5rem;
    min-height: 0; padding-top: .5rem; border-top: 1px solid var(--linea-suave);
}
.vm-modulo--abierto .vm-modulo__pie { border-top: 1px dashed var(--corte); }
/* En fila flexible: «Sin turno» mide su propia línea (19,5 px), no la del cuerpo, y el pie queda igual al del Inicio */
.vm-modulo__ahora { min-width: 0; display: flex; align-items: center; overflow: hidden; white-space: nowrap; }
.vm-modulo__en-curso { display: flex; align-items: baseline; gap: .5rem; }
.vm-modulo__turno { font-size: .9375rem; font-weight: 650; color: var(--tinta); letter-spacing: .01em; font-variant-numeric: tabular-nums; }
.vm-modulo__min { font-size: .8125rem; color: var(--tinta-suave); font-variant-numeric: tabular-nums; }
.vm-modulo__min.is-alerta { color: var(--error); font-weight: 650; }
.vm-modulo__vacio { font-size: .8125rem; color: var(--tinta-tenue); }
/* Editar y eliminar (32 px) sin agrandar el pie: el mismo alto que el turno en curso */
.vm-modulo__acciones { flex: none; display: flex; gap: .125rem; margin: -.375rem -.5rem -.375rem 0; }
/* Inactivo: la tarjeta se apaga (sin roce, contorno fino); el chip dice «Inactivo» */
.vm-modulo--inactivo { background: rgba(255, 255, 255, .5); box-shadow: inset 0 0 0 1px var(--linea); }

/* Esqueletos (antes de que arranque Alpine): mismas medidas que las tarjetas reales */
.vm-modulo[data-esqueleto] .vm-modulo__quien, .vm-modulo[data-esqueleto] .vm-modulo__lugar { display: flex; align-items: center; }
/* El pie con turno mide 31,5 px: la tarjeta de espera queda en 138,5 px, igual que la real y que la del Inicio */
.vm-modulo[data-esqueleto] .vm-modulo__pie { min-height: 1.96875rem; }
.vm-esq-num { width: 2rem; height: 1.5rem; border-radius: .375rem; flex: none; }
.vm-esq-chip { height: 1.3rem; border-radius: 999px; }

/* Formulario del módulo: número angosto junto al nombre; el estado como control segmentado con su efecto escrito al lado */
.vm-form__fila-num { grid-template-columns: 7.5rem minmax(0, 1fr); }
.vm-campo-num { font-variant-numeric: tabular-nums; }
.vm-estado-fila { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem 1rem; }
.vm-estado { flex: none; width: 15rem; }
.vm-estado-ayuda { flex: 1 1 12rem; margin: 0; }
.vm-estado-aviso { margin-top: .375rem; }

/* Eliminar: la consecuencia en el aviso de error; la salida («póngalo como inactivo») debajo, en texto */
.vm-eliminar__texto { display: flex; flex-direction: column; gap: .625rem; }
.vm-eliminar__texto .form-texto { margin: 0; }
.vm-eliminar__otra .enlace-panel { font-size: inherit; }

/* Pantallas bajas (1366 x 650): las tarjetas se aprietan un poco; de la barra al contenido siguen 16 px */
@media (min-width: 768px) and (max-height: 719px) {
    .vm-modulo { min-height: 8.25rem; padding-top: .75rem; gap: .25rem; }
    .vm-modulos { gap: .625rem; }
}
</style>
@endpush
@endsection
