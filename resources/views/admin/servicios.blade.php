@extends('layouts.admin')

@section('title', 'Servicios')

@section('content')
{{-- Servicios en una sola vista: cada sección del kiosco con sus subservicios debajo (ServicioController::index),
     con el código del ticket, los asesores asignados, la cola de hoy, el TV y la prioridad. --}}
@php $totalServicios = collect($secciones)->sum(fn ($s) => 1 + count($s['hijos'])); @endphp
<div class="servicios-vista max-w-7xl mx-auto space-y-4" x-data="serviciosVista(@js($secciones), @js($search))">
    <h1 class="sr-only">Servicios</h1>
    <!-- Aviso de lo que acaba de pasar (sobrevive a la recarga) -->
    <div class="aviso-flotante" role="status" x-show="aviso" x-transition.opacity x-cloak>
        <span x-text="aviso"></span>
    </div>

    <!-- Crear / editar -->
    <div class="envoltorio-modal" x-data="formularioServicio(secciones)" @abrir-servicio.window="abrir($event.detail)" @keydown.escape.window="cerrar()">
        <div class="modal-panel" x-show="abierto" x-cloak x-transition.opacity @click.self="cerrar()">
            <div class="modal-panel__caja" role="dialog" aria-modal="true" :aria-label="titulo()">
                <div class="modal-panel__cabeza">
                    <h2 x-text="titulo()"></h2>
                    <button type="button" class="accion-icono" aria-label="Cerrar" @click="cerrar()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <form class="form-panel" @submit.prevent="guardar()" novalidate>
                    <div class="form-panel__fila form-panel__fila--nombre">
                        <label class="form-campo">Nombre
                            <input type="text" class="campo" x-model="datos.nombre" x-ref="primero" maxlength="255" required>
                            <small x-show="errores.nombre" x-text="errores.nombre"></small>
                            <span class="form-ayuda form-ayuda--alerta" x-show="avisoUbicacion()">
                                El ticket de esta sección indica <b x-text="original.ubicacion"></b> según su nombre: si lo cambias, el ticket dejará de indicar a dónde ir.
                            </span>
                        </label>
                        <label class="form-campo">Código
                            <input type="text" class="campo campo--mono" x-model="datos.codigo" maxlength="10" autocomplete="off" spellcheck="false"
                                   @input="datos.codigo = datos.codigo.toUpperCase()">
                            <small x-show="errores.codigo" x-text="errores.codigo"></small>
                            <span class="form-ayuda" x-show="!errores.codigo && codigoValido()">Así sale en el ticket: <b x-text="datos.codigo.trim().toUpperCase() + '-001'"></b></span>
                            <span class="form-ayuda form-ayuda--alerta" x-show="!errores.codigo && datos.codigo.trim() && !codigoValido()">Solo letras: la voz del TV las deletrea y luego dice el número.</span>
                        </label>
                    </div>
                    <div class="form-panel__fila">
                        <label class="form-campo">Pertenece a
                            <select class="campo" x-model="datos.servicio_padre_id" :disabled="tieneHijos()">
                                <option value="">Ninguna: es una sección del kiosco</option>
                                <template x-for="p in padresPosibles()" :key="p.id">
                                    <option :value="String(p.id)" x-text="p.nombre + (p.activo ? '' : ' (inactiva)')" :selected="String(p.id) === datos.servicio_padre_id"></option>
                                </template>
                            </select>
                            <small x-show="errores.servicio_padre_id" x-text="errores.servicio_padre_id"></small>
                            <span class="form-ayuda" x-show="tieneHijos()">Tiene subservicios: sigue siendo una sección.</span>
                        </label>
                        <label class="form-campo">Posición en el kiosco
                            <input type="number" class="campo" x-model="datos.orden" min="0" max="9999" placeholder="Al final">
                            <small x-show="errores.orden" x-text="errores.orden"></small>
                        </label>
                    </div>
                    <div class="form-casillas">
                        <label class="form-casilla"><input type="checkbox" x-model="datos.activo">
                            <span>Activo <span class="form-ayuda">Sale en el kiosco y se puede asignar a los asesores.</span></span>
                        </label>
                        <label class="form-casilla"><input type="checkbox" x-model="datos.ocultar_turno">
                            <span>Oculto en el TV <span class="form-ayuda">Sus turnos no salen en el TV ni se llaman solos; se llaman por código.</span></span>
                        </label>
                        <label class="form-casilla" x-show="!tieneHijosActivos()"><input type="checkbox" x-model="datos.requiere_priorizacion">
                            <span>Pide prioridad en el kiosco <span class="form-ayuda">Al sacar el turno se elige Normal o Alta.</span></span>
                        </label>
                    </div>
                    <label class="form-campo">Descripción
                        <textarea class="campo campo--area" rows="2" x-model="datos.descripcion" maxlength="500"></textarea>
                        <small x-show="errores.descripcion" x-text="errores.descripcion"></small>
                    </label>
                    <p class="form-error" x-show="errores.general" x-text="errores.general"></p>
                    <div class="modal-panel__pie">
                        <button type="button" class="btn-secundario" @click="cerrar()">Cancelar</button>
                        <button type="submit" class="btn-primario" :disabled="guardando" x-text="guardando ? 'Guardando…' : (modo === 'crear' ? 'Crear servicio' : 'Guardar cambios')"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Eliminar: primero se mira qué se perdería -->
    <div class="envoltorio-modal" x-data="eliminarServicio()" @eliminar-servicio.window="abrir($event.detail)" @keydown.escape.window="cerrar()">
        <div class="modal-panel" x-show="abierto" x-cloak x-transition.opacity @click.self="cerrar()">
            <div class="modal-panel__caja modal-panel__caja--angosta" role="dialog" aria-modal="true" aria-label="Eliminar servicio">
                <div class="modal-panel__cabeza">
                    <h2 x-text="servicio ? servicio.nombre : ''"></h2>
                    <button type="button" class="accion-icono" aria-label="Cerrar" @click="cerrar()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="form-panel">
                    <div x-show="cargando" class="impacto-esqueleto" role="status">
                        <span class="sr-only">Revisando qué depende de este servicio…</span>
                        <span class="esqueleto" style="width: 92%" aria-hidden="true"></span>
                        <span class="esqueleto" style="width: 80%" aria-hidden="true"></span>
                        <span class="esqueleto" style="width: 55%" aria-hidden="true"></span>
                    </div>
                    <template x-if="!cargando && impacto">
                        <div class="form-texto space-y-2">
                            <template x-if="impacto.hijos > 0">
                                <p>No se puede eliminar: tiene <b x-text="impacto.hijos"></b> subservicio(s). Elimínalos o muévelos a otra sección primero.</p>
                            </template>
                            <template x-if="!impacto.hijos && conHistorial()">
                                <p>Tiene <b x-text="miles(impacto.turnos || impacto.historial)"></b> turnos registrados. Si lo eliminas, se borran también de Reportes y Gráficos.
                                   <span x-show="servicio.activo">Desactívalo: deja de salir en el kiosco y conserva su historial.</span>
                                   <span x-show="!servicio.activo">Ya está inactivo: así conserva su historial.</span></p>
                            </template>
                            <template x-if="!impacto.hijos && !conHistorial()">
                                <p>Se eliminará este servicio. <span x-show="impacto.asesores > 0"><b x-text="impacto.asesores"></b> asesor(es) lo tienen asignado y lo perderán.</span>
                                   Esta acción no se puede deshacer.</p>
                            </template>
                        </div>
                    </template>
                    <p class="form-error" x-show="error" x-text="error"></p>
                    <div class="modal-panel__pie">
                        <button type="button" class="btn-secundario" @click="cerrar()" x-text="puedeEliminar() || puedeDesactivar() ? 'Cancelar' : 'Cerrar'"></button>
                        <button type="button" class="btn-primario" x-show="puedeDesactivar()" :disabled="trabajando" @click="desactivar()" x-text="trabajando ? 'Desactivando…' : 'Desactivar'"></button>
                        <button type="button" class="btn-peligro" x-show="puedeEliminar()" :disabled="trabajando" @click="confirmar()" x-text="trabajando ? 'Eliminando…' : 'Eliminar'"></button>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Filtros rápidos, búsqueda y alta en una sola fila -->
    <div class="barra-vista">
        <div class="filtros-rapidos" role="group" aria-label="Filtrar servicios">
            @foreach ([3.25, 4.75, 7, 5.5] as $ancho)
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
            <input type="search" x-model.debounce.150ms="buscar" class="campo" placeholder="Nombre, código o descripción" aria-label="Buscar servicio">
        </div>
        <button type="button" class="btn-primario" @click="$dispatch('abrir-servicio', { modo: 'crear' })">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Nuevo servicio
        </button>
    </div>

    <!-- Catálogo: una tabla, una sección por bloque -->
    <div class="superficie overflow-x-auto">
        <table class="tabla-panel tabla-servicios">
            <thead>
                <tr>
                    <th scope="col">Servicio</th>
                    <th scope="col">Código</th>
                    <th scope="col">Asesores</th>
                    <th scope="col">En cola</th>
                    <th scope="col">TV</th>
                    <th scope="col">Prioridad</th>
                    <th scope="col"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            {{-- Mientras arranca Alpine: la forma de las filas (init() las quita) --}}
            <tbody data-esqueleto aria-hidden="true">
                @for ($i = 0; $i < min($totalServicios, 14); $i++)
                    <tr class="fila-esqueleto">
                        <td style="padding-left: {{ $i % 4 ? '2.5rem' : '1rem' }}"><span class="esqueleto" style="width: {{ [9, 11, 8, 12][$i % 4] }}rem"></span></td>
                        <td><span class="esqueleto" style="width: 2.25rem"></span></td>
                        <td><span class="esqueleto" style="width: 1.25rem"></span></td>
                        <td><span class="esqueleto" style="width: 1.25rem"></span></td>
                        <td><span class="esqueleto" style="width: 3.25rem"></span></td>
                        <td><span class="esqueleto" style="width: 1.5rem"></span></td>
                        <td></td>
                    </tr>
                @endfor
            </tbody>
            <template x-for="g in visibles()" :key="g.s.id">
                <tbody class="grupo-servicio">
                    <tr class="fila-seccion" :class="{ 'fila--inactiva': !g.s.activo, 'fila--contexto': g.contexto }">
                        <td>
                            <div class="celda-servicio">
                                <span class="servicio-nombre" x-text="g.s.nombre"></span>
                                <span class="servicio-marca" x-show="!g.s.activo">Inactivo</span>
                                <span class="servicio-sub" x-show="g.s.ubicacion" :title="'El ticket de esta sección indica: ' + g.s.ubicacion">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                                    <span x-text="g.s.ubicacion"></span>
                                </span>
                                <span class="servicio-sub" x-show="!g.s.ubicacion && g.s.descripcion" x-text="g.s.descripcion" :title="g.s.descripcion"></span>
                            </div>
                        </td>
                        <td x-html="celdaCodigo(g.s)"></td>
                        <td x-html="g.s.hijos.length ? vacio() : celdaAsesores(g.s)"></td>
                        <td x-html="celdaCola(g.s.hijos.length ? g.s.hijos.reduce((n, h) => n + h.en_cola, 0) : g.s.en_cola)"></td>
                        <td x-html="celdaTv(g.s)"></td>
                        <td x-html="g.s.hijos.length ? vacio() : celdaPrioridad(g.s)"></td>
                        <td class="celda-acciones">
                            <div class="acciones-fila">
                                <button type="button" class="accion-icono" title="Agregar subservicio" :aria-label="'Agregar subservicio a ' + g.s.nombre"
                                        x-show="!g.s.padre_id" @click="$dispatch('abrir-servicio', { modo: 'crear', padreId: g.s.id })">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                </button>
                                <button type="button" class="accion-icono" title="Editar" :aria-label="'Editar ' + g.s.nombre"
                                        @click="$dispatch('abrir-servicio', { modo: 'editar', servicio: g.s })">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                <button type="button" class="accion-icono accion-icono--peligro" title="Eliminar" :aria-label="'Eliminar ' + g.s.nombre"
                                        @click="$dispatch('eliminar-servicio', { servicio: g.s })">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <template x-for="h in g.hijos" :key="h.id">
                        <tr class="fila-hijo" :class="{ 'fila--inactiva': !h.activo }">
                            <td>
                                <div class="celda-servicio">
                                    <span class="servicio-nombre" x-text="h.nombre"></span>
                                    <span class="servicio-marca" x-show="!h.activo">Inactivo</span>
                                    <span class="servicio-sub" x-show="h.descripcion" x-text="h.descripcion" :title="h.descripcion"></span>
                                </div>
                            </td>
                            <td x-html="celdaCodigo(h)"></td>
                            <td x-html="celdaAsesores(h)"></td>
                            <td x-html="celdaCola(h.en_cola)"></td>
                            <td x-html="celdaTv(h)"></td>
                            <td x-html="celdaPrioridad(h)"></td>
                            <td class="celda-acciones">
                                <div class="acciones-fila">
                                    <button type="button" class="accion-icono" title="Editar" :aria-label="'Editar ' + h.nombre"
                                            @click="$dispatch('abrir-servicio', { modo: 'editar', servicio: h })">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>
                                    <button type="button" class="accion-icono accion-icono--peligro" title="Eliminar" :aria-label="'Eliminar ' + h.nombre"
                                            @click="$dispatch('eliminar-servicio', { servicio: h })">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </template>
        </table>
        <p class="vacio-servicios" x-show="!visibles().length" x-cloak>
            <span x-text="secciones.length ? 'Ningún servicio coincide.' : 'Todavía no hay servicios.'"></span>
            <button type="button" class="enlace-panel" x-show="secciones.length" @click="filtro = 'todos'; buscar = ''">Quitar filtros</button>
        </p>
    </div>

</div>

<script>
document.addEventListener('alpine:init', () => {
    const SERVICIOS_URL = @json(route('admin.servicios'));
    const ASIGNACION_URL = @json(route('admin.asignacion-servicios'));
    const TOKEN = () => document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const CODIGO_VALIDO = /^[A-Z]{1,10}$/;
    const normal = t => String(t ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    const esc = t => String(t ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const AVISO = 'aviso-servicios';

    // Lo que se ve tras guardar y recargar: se deja escrito antes de recargar.
    const recargarCon = texto => { try { sessionStorage.setItem(AVISO, texto); } catch (e) {} location.reload(); };

    Alpine.data('serviciosVista', (secciones, busquedaInicial) => ({
        secciones,
        filtro: 'todos',
        buscar: busquedaInicial || '',
        aviso: '',
        filtros: [
            { clave: 'todos', rotulo: 'Todos' },
            { clave: 'inactivos', rotulo: 'Inactivos' },
            { clave: 'ocultos', rotulo: 'Ocultos en el TV' },
            { clave: 'sin_asesor', rotulo: 'Sin asesor' },
        ],
        init() {
            this.$el.querySelectorAll('[data-esqueleto]').forEach(e => e.remove());
            try {
                const texto = sessionStorage.getItem(AVISO);
                if (texto) { sessionStorage.removeItem(AVISO); this.mostrarAviso(texto); }
            } catch (e) {}
        },
        mostrarAviso(texto) { this.aviso = texto; clearTimeout(this._aviso); this._aviso = setTimeout(() => this.aviso = '', 4000); },
        // Lo que recibe turnos del kiosco: un subservicio o una sección sin subservicios.
        esHoja(s) { return !(s.hijos && s.hijos.length); },
        todos() { return this.secciones.flatMap(s => [s, ...s.hijos]); },
        pasa(s, clave) {
            if (clave === 'inactivos') return !s.activo;
            if (clave === 'ocultos') return s.ocultar_turno;
            if (clave === 'sin_asesor') return this.esHoja(s) && s.activo && s.asesores === 0;
            return true;
        },
        contar(clave) { return this.todos().filter(s => this.pasa(s, clave)).length; },
        coincide(s, q) { return !q || [s.nombre, s.codigo, s.descripcion].some(v => normal(v).includes(q)); },
        // Filtra sin romper el árbol: si solo coinciden subservicios, su sección se muestra atenuada como contexto.
        visibles() {
            const q = normal(this.buscar.trim());
            return this.secciones.map(s => {
                // Si coincide el nombre o el código de la sección, se ven todos sus subservicios; por la descripción, no.
                const seccionCoincide = q && [s.nombre, s.codigo].some(v => normal(v).includes(q));
                const hijos = s.hijos.filter(h => this.pasa(h, this.filtro) && (this.coincide(h, q) || seccionCoincide));
                const propia = this.pasa(s, this.filtro) && this.coincide(s, q);
                return (propia || hijos.length) ? { s, hijos, contexto: !propia } : null;
            }).filter(Boolean);
        },
        celdaCodigo(s) {
            if (!s.codigo) return '<span class="texto-error">Sin código</span>';
            if (!CODIGO_VALIDO.test(s.codigo)) {
                return '<span class="codigo-ticket texto-alerta" title="La voz del TV no lee bien este código: usa solo letras">' + esc(s.codigo) + ' ⚠</span>';
            }
            return '<span class="codigo-ticket">' + esc(s.codigo) + '</span>';
        },
        celdaAsesores(s) {
            const url = ASIGNACION_URL + '?servicio=' + s.id;
            if (!s.activo) return '<span class="texto-mudo">' + s.asesores + '</span>';
            if (s.asesores === 0) return '<a class="sin-asesor" href="' + url + '" title="Ningún asesor tiene asignado este servicio">Nadie · Asignar</a>';
            return '<a class="enlace-cifra" href="' + url + '" title="Ver quién lo atiende">' + s.asesores + '</a>';
        },
        vacio() { return '<span class="texto-mudo">—</span>'; },
        celdaCola(n) { return n > 0 ? '<b>' + n + '</b>' : '<span class="texto-mudo">0</span>'; },
        celdaTv(s) {
            return s.ocultar_turno
                ? '<span class="texto-alerta" title="No sale en el TV ni se llama solo; se llama por código">Oculto</span>'
                : '<span class="texto-mudo">Visible</span>';
        },
        celdaPrioridad(s) { return s.requiere_priorizacion ? '<span title="El kiosco pregunta Normal o Alta">Normal / Alta</span>' : '<span class="texto-mudo">—</span>'; },
    }));

    function enviar(url, datos, metodo) {
        const cuerpo = new FormData();
        Object.entries(datos).forEach(([k, v]) => cuerpo.append(k, typeof v === 'boolean' ? (v ? '1' : '0') : (v ?? '')));
        if (metodo) cuerpo.append('_method', metodo);
        return fetch(url, { method: 'POST', body: cuerpo, headers: { 'X-CSRF-TOKEN': TOKEN(), 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } })
            .then(async r => ({ ok: r.ok, estado: r.status, datos: await r.json().catch(() => ({})) }));
    }
    const mensajeDe = (estado, datos, porDefecto) => estado === 419 ? 'La sesión expiró. Recarga la página e inténtalo de nuevo.' : (datos.message || porDefecto);
    const datosDe = s => ({
        nombre: s.nombre || '', codigo: s.codigo || '', servicio_padre_id: s.padre_id ? String(s.padre_id) : '',
        orden: s.orden ?? '', activo: !!s.activo, ocultar_turno: !!s.ocultar_turno,
        requiere_priorizacion: !!s.requiere_priorizacion, descripcion: s.descripcion || '',
    });

    Alpine.data('formularioServicio', secciones => ({
        abierto: false, modo: 'crear', id: null, original: {}, guardando: false, errores: {},
        datos: datosDe({ activo: true }),
        abrir({ modo, servicio, padreId }) {
            this.modo = modo; this.errores = {}; this.guardando = false;
            this.id = servicio ? servicio.id : null;
            this.original = servicio || {};
            this.datos = servicio ? datosDe(servicio) : datosDe({ activo: true, padre_id: padreId || null });
            this.abierto = true;
            this.$nextTick(() => this.$refs.primero && this.$refs.primero.focus());
        },
        cerrar() { if (!this.guardando) this.abierto = false; },
        titulo() {
            if (this.modo === 'editar') return 'Editar servicio';
            const padre = secciones.find(s => String(s.id) === this.datos.servicio_padre_id);
            return padre ? 'Nuevo subservicio de ' + padre.nombre : 'Nuevo servicio';
        },
        tieneHijos() { return !!(this.original.hijos && this.original.hijos.length); },
        tieneHijosActivos() { return !!(this.original.hijos && this.original.hijos.some(h => h.activo)); },
        padresPosibles() { return secciones.filter(s => s.id !== this.id && !s.padre_id); },
        codigoValido() { return CODIGO_VALIDO.test(this.datos.codigo.trim().toUpperCase()); },
        avisoUbicacion() { return this.modo === 'editar' && this.original.ubicacion && this.datos.nombre.trim() !== this.original.nombre; },
        guardar() {
            this.guardando = true; this.errores = {};
            const url = this.modo === 'crear' ? SERVICIOS_URL : SERVICIOS_URL + '/' + this.id;
            enviar(url, this.datos, this.modo === 'crear' ? null : 'PUT').then(({ ok, estado, datos }) => {
                if (ok && datos.success !== false) { recargarCon(this.modo === 'crear' ? 'Servicio creado.' : 'Cambios guardados.'); return; }
                this.guardando = false;
                const e = datos.errors || {};
                this.errores = Object.fromEntries(Object.entries(e).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]));
                if (!Object.keys(this.errores).length) this.errores = { general: mensajeDe(estado, datos, 'No se pudo guardar el servicio.') };
            }).catch(() => { this.guardando = false; this.errores = { general: 'No hay conexión con el servidor. Inténtalo de nuevo.' }; });
        },
    }));

    Alpine.data('eliminarServicio', () => ({
        abierto: false, servicio: null, impacto: null, cargando: false, trabajando: false, error: '',
        abrir({ servicio }) {
            this.servicio = servicio; this.impacto = null; this.error = ''; this.trabajando = false; this.cargando = true; this.abierto = true;
            fetch(SERVICIOS_URL + '/' + servicio.id, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' })
                .then(r => r.ok ? r.json() : Promise.reject(r.status))
                .then(d => { this.impacto = d.impacto; })
                .catch(e => { this.error = e === 419 ? mensajeDe(419, {}) : 'No se pudo revisar el servicio. Inténtalo de nuevo.'; })
                .finally(() => { this.cargando = false; });
        },
        cerrar() { if (!this.trabajando) this.abierto = false; },
        conHistorial() { return !!this.impacto && (this.impacto.turnos > 0 || this.impacto.historial > 0); },
        puedeEliminar() { return !!this.impacto && !this.impacto.hijos && !this.conHistorial(); },
        puedeDesactivar() { return !!this.impacto && !this.impacto.hijos && this.conHistorial() && this.servicio.activo; },
        miles(n) { return Number(n).toLocaleString('es-CO'); },
        confirmar() {
            this.trabajando = true; this.error = '';
            fetch(SERVICIOS_URL + '/' + this.servicio.id, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': TOKEN(), 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } })
                .then(async r => ({ ok: r.ok, estado: r.status, datos: await r.json().catch(() => ({})) }))
                .then(({ ok, estado, datos }) => {
                    if (ok && datos.success !== false) { recargarCon('Servicio eliminado.'); return; }
                    this.trabajando = false; this.error = mensajeDe(estado, datos, 'No se pudo eliminar el servicio.');
                })
                .catch(() => { this.trabajando = false; this.error = 'No hay conexión con el servidor. Inténtalo de nuevo.'; });
        },
        desactivar() {
            this.trabajando = true; this.error = '';
            enviar(SERVICIOS_URL + '/' + this.servicio.id, { ...datosDe(this.servicio), activo: false }, 'PUT').then(({ ok, estado, datos }) => {
                if (ok && datos.success !== false) { recargarCon('Servicio desactivado: ya no sale en el kiosco.'); return; }
                this.trabajando = false;
                const e = datos.errors ? Object.values(datos.errors)[0] : null;
                this.error = e ? (Array.isArray(e) ? e[0] : e) : mensajeDe(estado, datos, 'No se pudo desactivar el servicio.');
            }).catch(() => { this.trabajando = false; this.error = 'No hay conexión con el servidor. Inténtalo de nuevo.'; });
        },
    }));
});
</script>

@push('estilos')
<style>
[x-cloak] { display: none !important; }
/* Los modales van en envoltorios sin caja: space-y-4 no les da margen */
.envoltorio-modal { display: contents; }
.servicios-vista { font-variant-numeric: tabular-nums; }

/* Árbol: la sección en negrita; sus subservicios sangrados con una guía fina a la izquierda */
.tabla-servicios td { height: 2.1875rem; padding-top: .2rem; padding-bottom: .2rem; white-space: nowrap; }
/* max-width: 0 deja que la primera columna recorte la descripción en vez de ensanchar la tabla */
.tabla-servicios th:first-child, .tabla-servicios td:first-child { width: 46%; }
.tabla-servicios td:first-child { max-width: 0; }
.tabla-servicios .grupo-servicio + .grupo-servicio .fila-seccion > td { border-top-color: #dfe5ee; }
.celda-servicio { display: flex; align-items: baseline; gap: .6rem; min-width: 0; overflow: hidden; }
.servicio-nombre { flex: 0 0 auto; max-width: 100%; overflow: hidden; text-overflow: ellipsis; font-weight: 500; color: #111827; white-space: nowrap; }
.fila-seccion .servicio-nombre { font-weight: 650; color: #0f2547; }
.servicio-sub { flex: 0 1 auto; min-width: 0; font-size: .75rem; color: #6b7280; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.servicio-sub:has(svg) { display: inline-flex; align-items: center; gap: .3rem; }
.servicio-sub svg { flex-shrink: 0; align-self: center; }
.servicio-marca { font-size: .6875rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; white-space: nowrap; }
.fila-hijo > td:first-child { position: relative; padding-left: 2.5rem; }
.fila-hijo > td:first-child::before { content: ''; position: absolute; left: 1.4rem; top: 0; bottom: 0; width: 1px; background: #dbe2ec; }
.fila--inactiva .servicio-nombre, .fila--inactiva .codigo-ticket { color: #6b7280; }
.fila--contexto > td { background: #fbfcfe; }
.fila--contexto .servicio-nombre { color: #6b7280; }
.codigo-ticket { font-weight: 700; letter-spacing: .06em; color: #0f2547; }
.sin-asesor { display: inline-block; padding: .15rem .45rem; border-radius: .375rem; font-size: .75rem; font-weight: 600;
              background: #ffe2e2; color: #9f0712; white-space: nowrap; }
.sin-asesor:hover, .enlace-cifra:hover { text-decoration: underline; }
.enlace-cifra { color: #064b9e; font-weight: 600; }
.sin-asesor:focus-visible, .enlace-cifra:focus-visible { outline: 2px solid #064b9e; outline-offset: 2px; }
.tabla-servicios .celda-acciones { width: 1%; white-space: nowrap; padding-right: .75rem; }
.acciones-fila { display: flex; justify-content: flex-end; gap: .125rem; opacity: 0; transition: opacity .15s ease; }
.tabla-servicios tr:hover .acciones-fila, .tabla-servicios tr:focus-within .acciones-fila { opacity: 1; }
@media (hover: none) { .acciones-fila { opacity: 1; } }
.fila-esqueleto td { height: 2.1875rem; }
.vacio-servicios { padding: 2rem; text-align: center; font-size: .875rem; color: #6b7280; display: flex; justify-content: center; gap: .5rem; }

/* Formulario */
.form-panel__fila--nombre { grid-template-columns: minmax(0, 1fr) 9rem; }
@media (max-width: 639px) { .form-panel__fila--nombre { grid-template-columns: minmax(0, 1fr); } }
.form-ayuda--alerta { color: #92400e; }
.form-casillas { display: flex; flex-direction: column; gap: .55rem; padding: .15rem 0; }
.form-casilla .form-ayuda { display: block; margin-top: .1rem; }
.impacto-esqueleto { display: flex; flex-direction: column; gap: .6rem; padding: .25rem 0 .5rem; }
</style>
@endpush
@endsection
