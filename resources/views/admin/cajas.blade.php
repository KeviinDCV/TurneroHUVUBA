@extends('layouts.admin')

@section('title', 'Módulos')

@section('content')
{{-- Módulos en una sola vista: catálogo + ocupación de ahora mismo (CajaController::index, con el mismo
     cálculo del Inicio). La ocupación se refresca cada 15 s desde GET /api/admin/tablero. --}}
<div class="modulos-vista max-w-7xl mx-auto space-y-4"
     x-data="modulosVista(@js($modulos), @js($turnosPorModulo))">
    <h1 class="sr-only">Módulos</h1>

    @if (session('success'))
        <div class="aviso aviso--ok" role="status">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="aviso aviso--error" role="alert">{{ session('error') }}</div>
    @endif

    <!-- Filtros rápidos, búsqueda y alta en una sola fila -->
    <div class="mod-barra">
        <div class="mod-filtros" role="group" aria-label="Filtrar módulos">
            <template x-for="f in filtros" :key="f.clave">
                <button type="button" class="mod-filtro" :aria-pressed="(filtro === f.clave).toString()" @click="filtro = f.clave">
                    <span x-text="f.rotulo"></span> <span class="mod-filtro__n" x-text="contar(f.clave)"></span>
                </button>
            </template>
        </div>
        <div class="buscador">
            <svg class="buscador__icono" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 110-15 7.5 7.5 0 010 15z"></path></svg>
            <input type="search" x-model.debounce.150ms="buscar" class="campo" placeholder="Número, nombre, ubicación o asesor" aria-label="Buscar módulo">
        </div>
        <button type="button" class="btn-primario" @click="$dispatch('abrir-modulo', { modo: 'crear' })">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Nuevo módulo
        </button>
    </div>

    <!-- Fichas -->
    <div class="mod-grid">
        <template x-for="m in visibles()" :key="m.id">
            <article class="mod-tile" :class="!m.activa && 'mod-tile--inactivo'">
                <div class="mod-cabeza">
                    <span class="mod-num" x-text="m.numero"></span>
                    <div class="min-w-0">
                        <p class="mod-nombre" x-text="m.nombre"></p>
                        <p class="mod-sub" x-text="m.ubicacion || m.descripcion || '—'" :title="[m.ubicacion, m.descripcion].filter(Boolean).join(' · ')"></p>
                    </div>
                    <div class="mod-acciones">
                        <button type="button" class="mod-accion" :aria-label="'Editar módulo ' + m.numero" title="Editar"
                                @click="$dispatch('abrir-modulo', { modo: 'editar', modulo: m })">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </button>
                        <button type="button" class="mod-accion mod-accion--peligro" :aria-label="'Eliminar módulo ' + m.numero" title="Eliminar"
                                @click="$dispatch('eliminar-modulo', { modulo: m, turnos: historial[m.id] || 0 })">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>
                </div>
                <div class="mod-uso" :class="'mod-uso--' + m.estado" :title="uso(m).texto + (m.asesor ? ': ' + m.asesor.nombre : '')">
                    <span class="mod-punto" aria-hidden="true"></span>
                    <span class="mod-uso__quien" x-text="m.asesor ? m.asesor.corto : uso(m).texto"></span>
                    <span class="mod-uso__detalle" x-show="m.asesor">
                        <template x-if="m.turno"><span><b x-text="m.turno.codigo"></b><span class="mod-uso__min" x-text="m.turno.minutos !== null ? ' · ' + m.turno.minutos + ' min' : ''"></span></span></template>
                        <template x-if="!m.turno"><span x-text="uso(m).texto"></span></template>
                    </span>
                </div>
            </article>
        </template>
    </div>
    <p class="mod-vacio" x-show="!visibles().length" x-cloak>Ningún módulo coincide con la búsqueda.</p>

    <!-- Crear / editar -->
    <div x-data="formularioModulo()" @abrir-modulo.window="abrir($event.detail)" @keydown.escape.window="cerrar()">
        <div class="mod-modal" x-show="abierto" x-cloak x-transition.opacity @click.self="cerrar()">
            <div class="mod-modal__caja" role="dialog" aria-modal="true" :aria-label="modo === 'crear' ? 'Nuevo módulo' : 'Editar módulo'">
                <div class="mod-modal__cabeza">
                    <h2 x-text="modo === 'crear' ? 'Nuevo módulo' : 'Editar módulo ' + datos.numero_caja"></h2>
                    <button type="button" class="mod-accion" @click="cerrar()" aria-label="Cerrar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <form @submit.prevent="guardar()" class="mod-form">
                    <p class="aviso aviso--error" x-show="errores.general" x-text="errores.general"></p>
                    <div class="mod-form__fila">
                        <label class="mod-campo mod-campo--num">
                            <span>Número *</span>
                            <input type="number" min="1" required x-model="datos.numero_caja" class="campo" x-ref="primero">
                            <small x-show="errores.numero_caja" x-text="errores.numero_caja"></small>
                        </label>
                        <label class="mod-campo">
                            <span>Nombre *</span>
                            <input type="text" required maxlength="255" x-model="datos.nombre" class="campo" placeholder="Ej.: Caja 3">
                            <small x-show="errores.nombre" x-text="errores.nombre"></small>
                        </label>
                    </div>
                    <div class="mod-form__fila">
                        <label class="mod-campo">
                            <span>Ubicación</span>
                            <input type="text" maxlength="255" x-model="datos.ubicacion" class="campo" placeholder="Ej.: Primer piso">
                            <small x-show="errores.ubicacion" x-text="errores.ubicacion"></small>
                        </label>
                        <label class="mod-campo mod-campo--estado">
                            <span>Estado *</span>
                            <select x-model="datos.estado" class="campo">
                                <option value="activa">Activo</option>
                                <option value="inactiva">Inactivo</option>
                            </select>
                        </label>
                    </div>
                    <label class="mod-campo">
                        <span>Descripción</span>
                        <textarea rows="2" x-model="datos.descripcion" class="campo campo--area" placeholder="Opcional"></textarea>
                        <small x-show="errores.descripcion" x-text="errores.descripcion"></small>
                    </label>
                    <div class="mod-modal__pie">
                        <button type="button" class="btn-secundario" @click="cerrar()">Cancelar</button>
                        <button type="submit" class="btn-primario" :disabled="guardando" x-text="guardando ? 'Guardando…' : (modo === 'crear' ? 'Crear módulo' : 'Guardar cambios')"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Eliminar -->
    <div x-data="eliminarModulo()" @eliminar-modulo.window="abrir($event.detail)" @keydown.escape.window="cerrar()">
        <div class="mod-modal" x-show="abierto" x-cloak x-transition.opacity @click.self="cerrar()">
            <div class="mod-modal__caja mod-modal__caja--angosta" role="alertdialog" aria-modal="true" aria-label="Eliminar módulo">
                <div class="mod-modal__cabeza">
                    <h2 x-text="'Eliminar el módulo ' + (modulo && modulo.numero)"></h2>
                </div>
                <div class="mod-form">
                    <p class="aviso aviso--error" x-show="enUso" x-text="'Está en uso por ' + (modulo && modulo.asesor ? modulo.asesor.nombre : 'un asesor') + '. Elimínalo cuando lo libere.'"></p>
                    <p class="mod-texto" x-show="!enUso">
                        <span x-show="turnos > 0">Los <b x-text="turnos.toLocaleString('es-CO')"></b> turnos atendidos en este módulo quedarán sin módulo en el historial y en los reportes. </span>
                        Si solo quieres dejar de usarlo, edítalo y ponlo como <b>Inactivo</b>: conserva la historia.
                    </p>
                    <p class="aviso aviso--error" x-show="error" x-text="error"></p>
                    <div class="mod-modal__pie">
                        <button type="button" class="btn-secundario" @click="cerrar()">Cancelar</button>
                        <button type="button" class="btn-peligro" x-show="!enUso" :disabled="eliminando" @click="confirmar()" x-text="eliminando ? 'Eliminando…' : 'Eliminar'"></button>
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
    const TOKEN = () => document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const USO = {
        atendiendo: ['Atendiendo', 'bg-yellow-100 text-yellow-800'],
        libre: ['Libre', 'bg-green-100 text-green-800'],
        descanso: ['En descanso', 'bg-blue-100 text-blue-800'],
        canal: ['Canal no presencial', 'bg-orange-100 text-orange-800'],
        cerrado: ['Sin asesor', 'bg-gray-100 text-gray-700'],
        inhabilitado: ['Inactivo', 'bg-gray-100 text-gray-700'],
    };
    const EN_USO = ['atendiendo', 'libre', 'descanso', 'canal'];

    Alpine.data('modulosVista', (inicial, historial) => ({
        modulos: inicial,
        historial: historial || {},
        filtro: 'todos',
        buscar: '',
        filtros: [
            { clave: 'todos', rotulo: 'Todos' },
            { clave: 'uso', rotulo: 'En uso' },
            { clave: 'sin', rotulo: 'Sin asesor' },
            { clave: 'inactivos', rotulo: 'Inactivos' },
        ],
        init() {
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
        uso(m) { const [texto, clase] = USO[m.estado] || [m.estado, 'bg-gray-100 text-gray-700']; return { texto, clase }; },
        refrescar() {
            fetch(TABLERO_URL, { headers: { Accept: 'application/json' }, cache: 'no-store' })
                .then(r => r.ok ? r.json() : Promise.reject(r.status))
                .then(d => {
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

    Alpine.data('formularioModulo', () => ({
        abierto: false, modo: 'crear', id: null, guardando: false, errores: {},
        datos: { numero_caja: '', nombre: '', ubicacion: '', estado: 'activa', descripcion: '' },
        abrir({ modo, modulo }) {
            this.modo = modo; this.errores = {}; this.guardando = false;
            this.id = modulo ? modulo.id : null;
            this.datos = modulo
                ? { numero_caja: modulo.numero, nombre: modulo.nombre || '', ubicacion: modulo.ubicacion || '', estado: modulo.activa ? 'activa' : 'inactiva', descripcion: modulo.descripcion || '' }
                : { numero_caja: '', nombre: '', ubicacion: '', estado: 'activa', descripcion: '' };
            this.abierto = true;
            this.$nextTick(() => this.$refs.primero && this.$refs.primero.focus());
        },
        cerrar() { if (!this.guardando) this.abierto = false; },
        guardar() {
            this.guardando = true; this.errores = {};
            const url = this.modo === 'crear' ? CAJAS_URL : CAJAS_URL + '/' + this.id;
            enviar(url, this.datos, this.modo === 'crear' ? null : 'PUT').then(({ ok, datos }) => {
                if (ok && datos.success !== false) { location.reload(); return; }
                this.guardando = false;
                const e = datos.errors || {};
                this.errores = Object.fromEntries(Object.entries(e).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]));
                if (!Object.keys(this.errores).length) this.errores = { general: datos.message || 'No se pudo guardar el módulo.' };
            }).catch(() => { this.guardando = false; this.errores = { general: 'Error de conexión. Inténtalo de nuevo.' }; });
        },
    }));

    Alpine.data('eliminarModulo', () => ({
        abierto: false, modulo: null, turnos: 0, eliminando: false, error: '',
        get enUso() { return !!(this.modulo && this.modulo.activa && EN_USO.includes(this.modulo.estado)); },
        abrir({ modulo, turnos }) { this.modulo = modulo; this.turnos = turnos; this.error = ''; this.eliminando = false; this.abierto = true; },
        cerrar() { if (!this.eliminando) this.abierto = false; },
        confirmar() {
            this.eliminando = true; this.error = '';
            fetch(CAJAS_URL + '/' + this.modulo.id, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': TOKEN(), 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } })
                .then(async r => ({ ok: r.ok, datos: await r.json().catch(() => ({})) }))
                .then(({ ok, datos }) => {
                    if (ok && datos.success !== false) { location.reload(); return; }
                    this.eliminando = false; this.error = datos.message || 'No se pudo eliminar el módulo.';
                })
                .catch(() => { this.eliminando = false; this.error = 'Error de conexión. Inténtalo de nuevo.'; });
        },
    }));
});
</script>

<style>
[x-cloak] { display: none !important; }
.modulos-vista { font-variant-numeric: tabular-nums; }

/* Barra: filtros, búsqueda y alta en una fila */
.mod-barra { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem; }
.mod-filtros { display: flex; gap: .375rem; flex-wrap: wrap; }
.mod-filtro {
    display: inline-flex; align-items: center; gap: .4rem; height: 2.5rem; padding: 0 .9rem; border-radius: .5rem;
    background: #ffffff; box-shadow: 0 1px 2px rgba(16, 24, 40, .06); font-size: .875rem; font-weight: 600; color: #374151; cursor: pointer;
}
.mod-filtro:hover { color: #064b9e; }
.mod-filtro[aria-pressed="true"] { background: #064b9e; color: #ffffff; }
.mod-filtro__n { font-weight: 500; opacity: .75; }
.mod-filtro:focus-visible, .mod-accion:focus-visible, .btn-primario:focus-visible, .btn-secundario:focus-visible, .btn-peligro:focus-visible { outline: 2px solid #064b9e; outline-offset: 2px; }
.buscador { position: relative; flex: 1 1 16rem; max-width: 22rem; margin-left: auto; }
.buscador__icono { position: absolute; left: .75rem; top: 50%; width: 1rem; height: 1rem; transform: translateY(-50%); color: #6b7280; pointer-events: none; }
.campo {
    width: 100%; height: 2.5rem; padding: 0 .75rem; font-size: .875rem; color: #111827; background: #ffffff;
    border: 1px solid #d1d5db; border-radius: .5rem;
}
.buscador .campo { padding-left: 2.25rem; }
.campo:focus { outline: none; border-color: #064b9e; box-shadow: 0 0 0 3px rgba(6, 75, 158, .18); }
.campo--area { height: auto; padding: .5rem .75rem; resize: vertical; }
.btn-primario, .btn-secundario, .btn-peligro {
    display: inline-flex; align-items: center; justify-content: center; gap: .4rem; height: 2.5rem; padding: 0 1rem;
    border-radius: .5rem; font-size: .875rem; font-weight: 600; cursor: pointer; transition: background-color .15s ease;
}
.btn-primario { background: #064b9e; color: #ffffff; }
.btn-primario:hover { background: #053d7a; }
.btn-secundario { background: #eef1f6; color: #374151; }
.btn-secundario:hover { background: #e2e8f2; }
.btn-peligro { background: #b7191c; color: #ffffff; }
.btn-peligro:hover { background: #9a1518; }
.btn-primario:disabled, .btn-peligro:disabled { opacity: .6; cursor: default; }

/* Fichas: 20 módulos a la vista sin scroll en 1366 x 768 */
.mod-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: .75rem; }
@media (max-width: 1279px) { .mod-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
@media (max-width: 1023px) { .mod-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
@media (max-width: 767px) { .mod-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 479px) { .mod-grid { grid-template-columns: 1fr; } }
.mod-tile {
    display: flex; flex-direction: column; gap: .55rem; background: #ffffff; border: 1px solid transparent;
    border-radius: .75rem; padding: .7rem .8rem; box-shadow: 0 1px 2px rgba(16, 24, 40, .06);
    transition: border-color .15s ease, box-shadow .15s ease;
}
.mod-tile:hover { border-color: #cdd9ec; box-shadow: 0 6px 18px -10px rgba(16, 24, 40, .18); }
.mod-tile--inactivo { background: #f6f8fc; box-shadow: none; }
.mod-tile--inactivo .mod-num, .mod-tile--inactivo .mod-nombre { color: #6b7280; }
.mod-cabeza { display: flex; align-items: flex-start; gap: .6rem; min-width: 0; }
.mod-num { font-size: 1.6rem; font-weight: 700; line-height: 1.05; color: #0f2547; min-width: 2rem; letter-spacing: -.02em; }
.mod-nombre { font-size: .875rem; font-weight: 600; color: #111827; line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.mod-sub { font-size: .75rem; color: #6b7280; line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.mod-tile { position: relative; }
.mod-acciones { position: absolute; top: .45rem; right: .45rem; display: flex; gap: .125rem; background: #ffffff; border-radius: .5rem; }
.mod-accion { width: 1.75rem; height: 1.75rem; border-radius: .375rem; display: grid; place-items: center; color: #9ca3af; cursor: pointer; }
.mod-accion:hover { background: #eef1f6; color: #064b9e; }
.mod-accion--peligro:hover { background: #fdecec; color: #b7191c; }
.mod-uso { display: flex; align-items: center; gap: .45rem; min-height: 1.6rem; padding-top: .5rem; border-top: 1px solid #eef1f6; font-size: .8125rem; min-width: 0; }
.mod-punto { width: .5rem; height: .5rem; border-radius: 9999px; flex-shrink: 0; background: #9ca3af; }
.mod-uso__quien { color: #111827; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 0; }
.mod-uso__detalle { margin-left: auto; white-space: nowrap; flex-shrink: 0; }
.mod-uso__detalle b { font-weight: 600; color: #111827; }
.mod-uso__min { color: #6b7280; }
/* Estado de ocupación: el punto y la palabra llevan el color; mismos tonos que los estados de Turnos */
.mod-uso--atendiendo .mod-punto { background: #d08700; }
.mod-uso--libre .mod-punto { background: #00a63e; } .mod-uso--libre .mod-uso__detalle { color: #008236; font-weight: 600; }
.mod-uso--descanso .mod-punto { background: #155dfc; } .mod-uso--descanso .mod-uso__detalle { color: #1447e6; font-weight: 600; }
.mod-uso--canal .mod-punto { background: #f54900; } .mod-uso--canal .mod-uso__detalle { color: #ca3500; font-weight: 600; }
.mod-uso--cerrado .mod-uso__quien, .mod-uso--inhabilitado .mod-uso__quien { color: #6b7280; font-weight: 400; }
.mod-uso--inhabilitado .mod-punto { background: #d1d5db; }
/* Acciones discretas: aparecen con el mouse o el teclado; en pantallas táctiles, siempre */
.mod-acciones { opacity: 0; transition: opacity .15s ease; }
.mod-tile:hover .mod-acciones, .mod-tile:focus-within .mod-acciones { opacity: 1; }
@media (hover: none) { .mod-acciones { opacity: 1; } }
.mod-vacio { padding: 2rem; text-align: center; font-size: .875rem; color: #6b7280; }

.aviso { padding: .6rem .8rem; border-radius: .5rem; font-size: .875rem; }
.aviso--ok { background: #e4faec; color: #005d38; }
.aviso--error { background: #ffefed; color: #901e1c; }

/* Modales */
.mod-modal { position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; padding: 1rem;
             background: rgba(15, 23, 42, .35); backdrop-filter: blur(2px); }
.mod-modal__caja { width: 100%; max-width: 34rem; background: #ffffff; border-radius: .875rem; box-shadow: 0 24px 48px -12px rgba(16, 24, 40, .28); }
.mod-modal__caja--angosta { max-width: 28rem; }
.mod-modal__cabeza { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem .25rem; }
.mod-modal__cabeza h2 { font-size: 1.05rem; font-weight: 700; color: #0f2547; }
.mod-form { display: flex; flex-direction: column; gap: .8rem; padding: .75rem 1.25rem 1.25rem; }
.mod-form__fila { display: grid; grid-template-columns: 7rem 1fr; gap: .75rem; }
.mod-form__fila:nth-of-type(2) { grid-template-columns: 1fr 9rem; }
.mod-campo { display: flex; flex-direction: column; gap: .3rem; font-size: .75rem; font-weight: 600; color: #374151; }
.mod-campo small { color: #b7191c; font-weight: 500; }
.mod-texto { font-size: .875rem; line-height: 1.5; color: #374151; }
.mod-modal__pie { display: flex; justify-content: flex-end; gap: .5rem; padding-top: .25rem; }

@media (min-width: 768px) and (max-height: 719px) {
    .mod-tile { padding: .55rem .7rem; gap: .4rem; }
    .mod-uso { padding-top: .4rem; }
    .mod-grid { gap: .6rem; }
}
</style>
@endsection
