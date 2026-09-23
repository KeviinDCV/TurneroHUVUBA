@extends('layouts.admin')

@section('title', 'Usuarios')

@section('content')
{{-- Usuarios en una sola vista (AdminController::users): todos, con lo que sirve para operar (rol, servicios
     asignados, conexión y, en huvuba, auto-llamado). Crear, editar y eliminar van por fetch en ventanas propias. --}}
<div class="usuarios-vista max-w-7xl mx-auto space-y-4" x-data="usuariosVista(@js($filas), @js($search))">
    <h1 class="sr-only">Usuarios</h1>

    <!-- Crear / editar -->
    <div class="envoltorio-modal" x-data="formularioUsuario(@js($autoLlamado))" @abrir-usuario.window="abrir($event.detail)" @keydown.escape.window="cerrar()">
        <div class="modal-panel" x-show="abierto" x-cloak x-transition.opacity @click.self="cerrarSiLimpio()">
            <div class="modal-panel__caja" role="dialog" aria-modal="true" aria-labelledby="t-usuario">
                <div class="modal-panel__cabeza">
                    <h2 id="t-usuario" x-text="modo === 'crear' ? 'Nuevo usuario' : 'Editar usuario'"></h2>
                    <button type="button" class="accion-icono" aria-label="Cerrar" @click="cerrar()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <form class="form-panel" @submit.prevent="guardar()" novalidate autocomplete="off">
                    <p class="aviso-desactivada" x-show="modo === 'editar' && original.desactivada">Cuenta desactivada desde el <span x-text="desactivadaDesde()"></span>:
                        no puede iniciar sesión. Sus turnos e historial se conservan.</p>
                    <p class="form-seccion">Identidad</p>
                    <label class="form-campo">Nombre completo
                        <input type="text" class="campo" x-model="datos.nombre_completo" x-ref="primero" maxlength="255" autocomplete="off">
                        <small x-show="errores.nombre_completo" x-text="errores.nombre_completo"></small>
                    </label>
                    <div class="form-panel__fila">
                        <label class="form-campo"><span class="form-rotulo">Cédula <span class="form-opcional">(opcional)</span></span>
                            <input type="text" class="campo" x-model="datos.cedula" inputmode="numeric" maxlength="20" autocomplete="off">
                            <small x-show="errores.cedula" x-text="errores.cedula"></small>
                        </label>
                        <label class="form-campo"><span class="form-rotulo">Correo <span class="form-opcional">(opcional)</span></span>
                            <input type="email" class="campo" x-model="datos.correo_electronico" maxlength="255" autocomplete="off">
                            <small x-show="errores.correo_electronico" x-text="errores.correo_electronico"></small>
                        </label>
                    </div>

                    <p class="form-seccion">Acceso</p>
                    <div class="form-panel__fila">
                        <label class="form-campo">Usuario
                            <input type="text" class="campo" x-model="datos.nombre_usuario" maxlength="255" autocomplete="off" autocapitalize="off" spellcheck="false">
                            <small x-show="errores.nombre_usuario" x-text="errores.nombre_usuario"></small>
                        </label>
                        <div class="form-campo">Rol
                            <div class="segmentado segmentado--ancho" role="radiogroup" aria-label="Rol">
                                <button type="button" role="radio" :aria-checked="(datos.rol === 'Asesor').toString()" @click="datos.rol = 'Asesor'">Asesor</button>
                                <button type="button" role="radio" :aria-checked="(datos.rol === 'Administrador').toString()" @click="datos.rol = 'Administrador'">Administrador</button>
                            </div>
                            <small x-show="errores.rol" x-text="errores.rol"></small>
                        </div>
                    </div>
                    <button type="button" class="enlace-panel enlace-contrasena" x-show="modo === 'editar' && !cambiarClave" @click="cambiarClave = true">Cambiar contraseña</button>
                    <div class="form-panel__fila" x-show="modo === 'crear' || cambiarClave">
                        <label class="form-campo"><span x-text="modo === 'crear' ? 'Contraseña' : 'Nueva contraseña'"></span>
                            <input type="password" class="campo" x-model="datos.password" autocomplete="new-password">
                            <small x-show="errores.password" x-text="errores.password"></small>
                        </label>
                        <label class="form-campo">Confirmar contraseña
                            <input type="password" class="campo" x-model="datos.password_confirmation" autocomplete="new-password">
                        </label>
                    </div>
                    <p class="form-ayuda" x-show="modo === 'crear'">Opcional: sin contraseña no podrá iniciar sesión hasta que se la asignes.</p>

                    <template x-if="autoLlamado && datos.rol === 'Asesor'">
                        <div>
                            <p class="form-seccion">Operación</p>
                            <div class="fila-auto">
                                <label class="form-casilla"><input type="checkbox" x-model="datos.auto_llamado_activo">
                                    <span>Auto-llamado de turnos <span class="form-ayuda">Llama el siguiente turno solo, tras un tiempo sin atender.</span></span>
                                </label>
                                <label class="minutos-auto" x-show="datos.auto_llamado_activo">cada
                                    <input type="number" class="campo" min="1" max="60" x-model.number="datos.auto_llamado_minutos"> min
                                </label>
                            </div>
                        </div>
                    </template>

                    <p class="form-error" x-show="errores.general" x-text="errores.general"></p>
                    <div class="modal-panel__pie modal-panel__pie--separado">
                        <button type="button" class="enlace-panel" x-show="modo === 'editar' && !original.yo && !original.desactivada" @click="pedirDesactivar()">Desactivar cuenta</button>
                        <button type="button" class="enlace-panel" x-show="modo === 'editar' && original.desactivada" :disabled="guardando" @click="reactivar()">Reactivar cuenta</button>
                        <button type="button" class="enlace-peligro" x-show="modo === 'editar' && !original.yo" @click="pedirEliminar()">Eliminar usuario</button>
                        <span class="espaciador"></span>
                        <button type="button" class="btn-secundario" @click="cerrar()">Cancelar</button>
                        <button type="submit" class="btn-primario" :disabled="guardando" x-text="guardando ? 'Guardando…' : (modo === 'crear' ? 'Crear usuario' : 'Guardar cambios')"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Eliminar: primero se mira qué depende del usuario -->
    <div class="envoltorio-modal" x-data="eliminarUsuario()" @eliminar-usuario.window="abrir($event.detail)" @keydown.escape.window="cerrar()">
        <div class="modal-panel" x-show="abierto" x-cloak x-transition.opacity @click.self="cerrar()">
            <div class="modal-panel__caja modal-panel__caja--angosta" role="dialog" aria-modal="true" aria-labelledby="t-eliminar-usuario">
                <div class="modal-panel__cabeza">
                    <h2 id="t-eliminar-usuario" x-text="u ? 'Eliminar a ' + u.nombre : ''"></h2>
                    <button type="button" class="accion-icono" aria-label="Cerrar" @click="cerrar()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="form-panel">
                    <div x-show="cargando" class="impacto-esqueleto" role="status">
                        <span class="sr-only">Revisando qué depende de este usuario…</span>
                        <span class="esqueleto" style="width: 92%" aria-hidden="true"></span>
                        <span class="esqueleto" style="width: 76%" aria-hidden="true"></span>
                        <span class="esqueleto" style="width: 58%" aria-hidden="true"></span>
                    </div>
                    <template x-if="!cargando && impacto">
                        <div class="form-texto space-y-2">
                            <p x-show="impacto.turnos > 0">Llamó o atendió <b x-text="miles(impacto.turnos)"></b> turnos. Si lo eliminas, esos turnos quedarán
                                <b>sin asesor</b> en Reportes y Gráficos<span x-show="impacto.canal > 0">, y se borran sus <b x-text="miles(impacto.canal)"></b> registros de canales no presenciales</span>.</p>
                            <p x-show="impacto.turnos > 0 && !u.desactivada" class="texto-mudo">Si solo quieres que no vuelva a entrar, mejor
                                <button type="button" class="enlace-panel" @click="desactivarEnSuLugar()">desactiva la cuenta</button>: conserva su nombre en los informes y se puede reactivar.</p>
                            <p x-show="!impacto.turnos">Se eliminará la cuenta<span x-show="impacto.servicios > 0"> y sus <b x-text="impacto.servicios"></b> servicios asignados</span><span
                                x-show="impacto.canal > 0">, y se borran sus <b x-text="miles(impacto.canal)"></b> registros de canales no presenciales</span>. No se puede deshacer.</p>
                            <label class="form-campo" x-show="impacto.turnos > 0">
                                <span>Escribe <b x-text="u.usuario"></b> para confirmar</span>
                                <input type="text" class="campo" x-model="confirmacion" autocomplete="off" spellcheck="false">
                            </label>
                        </div>
                    </template>
                    <p class="form-error" x-show="error" x-text="error"></p>
                    <div class="modal-panel__pie">
                        <button type="button" class="btn-secundario" @click="cerrar()">Cancelar</button>
                        <button type="button" class="btn-peligro" :disabled="!puedeEliminar() || trabajando" @click="confirmar()" x-text="trabajando ? 'Eliminando…' : 'Eliminar'"></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Desactivar: la cuenta no entra, pero conserva su historial y su nombre en los informes -->
    <div class="envoltorio-modal" x-data="desactivarUsuario()" @desactivar-usuario.window="abrir($event.detail)" @keydown.escape.window="cerrar()">
        <div class="modal-panel" x-show="abierto" x-cloak x-transition.opacity @click.self="cerrar()">
            <div class="modal-panel__caja modal-panel__caja--angosta" role="dialog" aria-modal="true" aria-labelledby="t-desactivar-usuario">
                <div class="modal-panel__cabeza">
                    <h2 id="t-desactivar-usuario" x-text="u ? 'Desactivar la cuenta de ' + u.nombre : ''"></h2>
                    <button type="button" class="accion-icono" aria-label="Cerrar" @click="cerrar()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="form-panel">
                    <template x-if="u">
                        <div class="form-texto space-y-2">
                            <p>No podrá iniciar sesión. Sus turnos, su historial y sus servicios asignados se conservan, y sigue apareciendo con su nombre en Reportes y Gráficos.</p>
                            <p x-show="u.conectado">Ahora está conectado<span x-show="u.conectado && u.conectado.modulo"> en el módulo <b x-text="u.conectado && u.conectado.modulo"></b></span>: su sesión se cerrará y el módulo quedará libre.</p>
                            <p class="texto-mudo">Puedes reactivarla cuando quieras desde Editar.</p>
                        </div>
                    </template>
                    <p class="form-error" x-show="error" x-text="error"></p>
                    <div class="modal-panel__pie">
                        <button type="button" class="btn-secundario" @click="cerrar()">Cancelar</button>
                        <button type="button" class="btn-peligro" :disabled="trabajando" @click="confirmar()" x-text="trabajando ? 'Desactivando…' : 'Desactivar cuenta'"></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros rápidos, búsqueda y alta en una sola fila -->
    <div class="barra-vista">
        <div class="filtros-rapidos" role="group" aria-label="Filtrar usuarios">
            @foreach ([3.25, 4.5, 7, 6, 7] as $ancho)
                <span class="filtro-rapido" data-esqueleto aria-hidden="true"><span class="esqueleto" style="width: {{ $ancho }}rem"></span></span>
            @endforeach
            <template x-for="f in filtros" :key="f.clave">
                <button type="button" class="filtro-rapido" x-show="f.clave !== 'desactivadas' || contar('desactivadas') > 0" :aria-pressed="(filtro === f.clave).toString()" @click="filtro = f.clave">
                    <span x-text="f.rotulo"></span> <span class="filtro-rapido__n" x-text="contar(f.clave)"></span>
                </button>
            </template>
        </div>
        <div class="buscador">
            <svg class="buscador__icono" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 110-15 7.5 7.5 0 010 15z"></path></svg>
            <input type="search" x-model.debounce.150ms="buscar" class="campo" placeholder="Nombre, usuario, cédula o correo" aria-label="Buscar usuario">
        </div>
        <button type="button" class="btn-primario" @click="$dispatch('abrir-usuario', { modo: 'crear' })">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Nuevo usuario
        </button>
    </div>

    <div class="superficie overflow-x-auto">
        <table class="tabla-panel tabla-usuarios">
            <thead>
                <tr>
                    <th scope="col">Nombre</th>
                    <th scope="col">Usuario</th>
                    <th scope="col">Rol</th>
                    <th scope="col">Servicios</th>
                    <th scope="col">Actividad</th>
                    @if ($autoLlamado)<th scope="col">Auto-llamado</th>@endif
                    <th scope="col"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            {{-- Mientras arranca Alpine: la forma de las filas (init() las quita) --}}
            <tbody data-esqueleto aria-hidden="true">
                @for ($i = 0; $i < min(count($filas), 14); $i++)
                    <tr class="fila-esqueleto">
                        <td><span class="esqueleto" style="width: {{ [11, 9, 12.5, 10][$i % 4] }}rem"></span></td>
                        <td><span class="esqueleto" style="width: 5.5rem"></span></td>
                        <td><span class="esqueleto" style="width: 3.5rem"></span></td>
                        <td><span class="esqueleto" style="width: {{ [5, 3, 6, 4][$i % 4] }}rem"></span></td>
                        <td><span class="esqueleto" style="width: 6.5rem"></span></td>
                        @if ($autoLlamado)<td><span class="esqueleto" style="width: 3rem"></span></td>@endif
                        <td></td>
                    </tr>
                @endfor
            </tbody>
            <tbody>
                <template x-for="u in visibles()" :key="u.id">
                    <tr :class="u.desactivada ? 'fila-desactivada' : ''">
                        <td>
                            <span class="usuario-nombre" x-text="u.nombre"></span>
                            <span class="marca-yo" x-show="u.yo">Tú</span>
                            <span class="marca-desactivada" x-show="u.desactivada">Desactivada</span>
                            <span class="usuario-cedula" x-show="u.cedula" x-text="u.cedula"></span>
                        </td>
                        <td class="usuario-login" x-text="u.usuario"></td>
                        <td><span :class="u.rol === 'Administrador' ? 'rol-admin' : 'texto-mudo'" x-text="u.rol"></span></td>
                        <td>
                            <template x-if="u.rol !== 'Asesor'"><span class="texto-mudo">—</span></template>
                            <template x-if="u.rol === 'Asesor' && !u.desactivada && !u.servicios.length">
                                <a class="sin-asesor" :href="asignacionUrl(u)" title="No atiende ningún servicio">Ninguno · Asignar</a>
                            </template>
                            <template x-if="u.rol === 'Asesor' && !u.desactivada && u.servicios.length">
                                <a class="codigos-servicio" :href="asignacionUrl(u)" :title="u.servicios.map(s => s.nombre).join(', ')"
                                   x-text="u.servicios.map(s => s.codigo || '?').join(' · ')"></a>
                            </template>
                            <template x-if="u.rol === 'Asesor' && u.desactivada">
                                <span class="texto-mudo" x-text="u.servicios.length ? u.servicios.map(s => s.codigo || '?').join(' · ') : '—'"></span>
                            </template>
                        </td>
                        <td>
                            <span class="actividad" :class="u.conectado ? 'actividad--' + u.conectado.estado : ''">
                                <span class="actividad__punto" x-show="u.conectado" aria-hidden="true"></span>
                                <span x-text="actividad(u)"></span>
                            </span>
                        </td>
                        @if ($autoLlamado)
                            <td><span x-show="u.auto_llamado" x-text="'Cada ' + u.auto_llamado_minutos + ' min'"></span><span class="texto-mudo" x-show="!u.auto_llamado">—</span></td>
                        @endif
                        <td class="celda-acciones">
                            <button type="button" class="accion-icono" title="Editar" :aria-label="'Editar a ' + u.nombre"
                                    @click="$dispatch('abrir-usuario', { modo: 'editar', usuario: u })">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
        <p class="vacio-usuarios" x-show="!visibles().length" x-cloak>
            Ningún usuario coincide. <button type="button" class="enlace-panel" @click="filtro = 'todos'; buscar = ''">Quitar filtros</button>
        </p>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    const USUARIOS_URL = @json(route('admin.users'));
    const ASIGNACION_URL = @json(route('admin.asignacion-servicios'));
    const TOKEN = () => document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    const ESTADOS = { atendiendo: 'Atendiendo', libre: 'Libre', descanso: 'En descanso', canal: 'Canal no presencial' };
    const normal = t => String(t ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    // Lo que se ve tras guardar y recargar: sileo lo guarda y lo muestra al volver.
    const recargarCon = opciones => sileo.recargarCon(opciones);
    const mensajeDe = (estado, datos, porDefecto) => estado === 419 ? 'La sesión expiró. Recarga la página e inténtalo de nuevo.' : (datos.message || porDefecto);
    // Desactivar / reactivar: POST sin cuerpo, con la misma respuesta { ok, estado, datos } del resto de la vista.
    const accion = url => fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': TOKEN(), 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } })
        .then(async r => ({ ok: r.ok, estado: r.status, datos: await r.json().catch(() => ({})) }));
    const fechaCorta = iso => { const f = new Date(iso); return f.getDate() + ' ' + MESES[f.getMonth()] + (f.getFullYear() !== new Date().getFullYear() ? ' ' + f.getFullYear() : ''); };

    Alpine.data('usuariosVista', (filas, busquedaInicial) => ({
        filas,
        filtro: 'todos',
        buscar: busquedaInicial || '',
        filtros: [
            { clave: 'todos', rotulo: 'Todos' },
            { clave: 'asesores', rotulo: 'Asesores' },
            { clave: 'admins', rotulo: 'Administradores' },
            { clave: 'conectados', rotulo: 'Conectados' },
            { clave: 'sin', rotulo: 'Sin servicios' },
            { clave: 'desactivadas', rotulo: 'Desactivadas' },
        ],
        init() {
            this.$el.querySelectorAll('[data-esqueleto]').forEach(e => e.remove());
        },
        pasa(u, clave) {
            if (clave === 'desactivadas') return !!u.desactivada;
            if (clave === 'todos') return true;
            if (u.desactivada) return false; // las cuentas desactivadas solo salen en Todos y en Desactivadas
            if (clave === 'asesores') return u.rol === 'Asesor';
            if (clave === 'admins') return u.rol === 'Administrador';
            if (clave === 'conectados') return !!u.conectado;
            if (clave === 'sin') return u.rol === 'Asesor' && !u.servicios.length;
            return true;
        },
        contar(clave) { return this.filas.filter(u => this.pasa(u, clave)).length; },
        visibles() {
            const q = normal(this.buscar.trim());
            return this.filas.filter(u => this.pasa(u, this.filtro) && (!q || [u.nombre, u.usuario, u.cedula, u.correo].some(v => normal(v).includes(q))));
        },
        asignacionUrl(u) { return ASIGNACION_URL + '?asesor=' + u.id; },
        actividad(u) {
            if (u.desactivada) return 'Desactivada el ' + fechaCorta(u.desactivada);
            if (u.conectado) return 'Módulo ' + u.conectado.modulo + ' · ' + (ESTADOS[u.conectado.estado] || 'Conectado');
            if (!u.ultima_actividad) return 'Sin actividad';
            const f = new Date(u.ultima_actividad), min = Math.round((Date.now() - f) / 60000);
            if (min < 2) return 'Hace un momento';
            if (min < 60) return 'Hace ' + min + ' min';
            if (min < 24 * 60) return 'Hace ' + Math.round(min / 60) + ' h';
            if (min < 48 * 60) return 'Ayer';
            return f.getDate() + ' ' + MESES[f.getMonth()] + (f.getFullYear() !== new Date().getFullYear() ? ' ' + f.getFullYear() : '');
        },
    }));

    const vacio = () => ({ nombre_completo: '', cedula: '', correo_electronico: '', nombre_usuario: '', rol: 'Asesor',
                           password: '', password_confirmation: '', auto_llamado_activo: false, auto_llamado_minutos: 10 });

    Alpine.data('formularioUsuario', (autoLlamado) => ({
        autoLlamado,
        abierto: false, modo: 'crear', id: null, original: {}, guardando: false, errores: {}, cambiarClave: false,
        datos: vacio(), inicial: '',
        abrir({ modo, usuario }) {
            this.modo = modo; this.errores = {}; this.guardando = false; this.cambiarClave = false;
            this.id = usuario ? usuario.id : null;
            this.original = usuario || {};
            this.datos = usuario ? {
                ...vacio(), nombre_completo: usuario.nombre || '', cedula: usuario.cedula || '', correo_electronico: usuario.correo || '',
                nombre_usuario: usuario.usuario || '', rol: usuario.rol || 'Asesor',
                auto_llamado_activo: !!usuario.auto_llamado, auto_llamado_minutos: usuario.auto_llamado_minutos || 10,
            } : vacio();
            this.inicial = JSON.stringify(this.datos);
            this.abierto = true;
            this.$nextTick(() => this.$refs.primero && this.$refs.primero.focus());
        },
        sucio() { return JSON.stringify(this.datos) !== this.inicial; },
        cerrar() { if (!this.guardando) this.abierto = false; },
        // Clic fuera: solo cierra si no hay nada escrito (antes se perdía el formulario sin aviso).
        cerrarSiLimpio() { if (!this.sucio()) this.cerrar(); },
        pedirEliminar() { this.abierto = false; this.$dispatch('eliminar-usuario', { usuario: this.original }); },
        pedirDesactivar() { this.abierto = false; this.$dispatch('desactivar-usuario', { usuario: this.original }); },
        desactivadaDesde() { return this.original.desactivada ? fechaCorta(this.original.desactivada) : ''; },
        reactivar() {
            this.guardando = true; this.errores = {};
            accion(USUARIOS_URL + '/' + this.id + '/reactivar')
                .then(({ ok, estado, datos }) => {
                    if (ok && datos.success !== false) { recargarCon({ title: 'Cuenta reactivada', description: 'Ya puede iniciar sesión con su misma contraseña.' }); return; }
                    this.guardando = false; this.errores = { general: mensajeDe(estado, datos, 'No se pudo reactivar la cuenta.') };
                })
                .catch(() => { this.guardando = false; this.errores = { general: 'No hay conexión con el servidor. Inténtalo de nuevo.' }; });
        },
        guardar() {
            this.guardando = true; this.errores = {};
            const d = this.datos, cuerpo = new FormData();
            ['nombre_completo', 'cedula', 'correo_electronico', 'nombre_usuario', 'rol'].forEach(k => cuerpo.append(k, (d[k] ?? '').toString().trim()));
            if ((this.modo === 'crear' || this.cambiarClave) && d.password) {
                cuerpo.append('password', d.password);
                cuerpo.append('password_confirmation', d.password_confirmation);
            }
            if (this.autoLlamado) {
                if (d.rol === 'Asesor' && d.auto_llamado_activo) cuerpo.append('auto_llamado_activo', '1');
                cuerpo.append('auto_llamado_minutos', Math.max(1, Math.min(60, parseInt(d.auto_llamado_minutos, 10) || 10)));
            }
            if (this.modo === 'editar') cuerpo.append('_method', 'PUT');
            const url = this.modo === 'crear' ? USUARIOS_URL : USUARIOS_URL + '/' + this.id;
            fetch(url, { method: 'POST', body: cuerpo, headers: { 'X-CSRF-TOKEN': TOKEN(), 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } })
                .then(async r => ({ ok: r.ok, estado: r.status, datos: await r.json().catch(() => ({})) }))
                .then(({ ok, estado, datos }) => {
                    if (ok && datos.success !== false) { recargarCon({ title: this.modo === 'crear' ? 'Usuario creado' : 'Cambios guardados' }); return; }
                    this.guardando = false;
                    const e = datos.errors || {};
                    this.errores = Object.fromEntries(Object.entries(e).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]));
                    if (!Object.keys(this.errores).length) this.errores = { general: mensajeDe(estado, datos, 'No se pudo guardar el usuario.') };
                })
                .catch(() => { this.guardando = false; this.errores = { general: 'No hay conexión con el servidor. Inténtalo de nuevo.' }; });
        },
    }));

    Alpine.data('eliminarUsuario', () => ({
        abierto: false, u: null, impacto: null, cargando: false, trabajando: false, error: '', confirmacion: '',
        abrir({ usuario }) {
            this.u = usuario; this.impacto = null; this.error = ''; this.confirmacion = ''; this.trabajando = false; this.cargando = true; this.abierto = true;
            fetch(USUARIOS_URL + '/' + usuario.id, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' })
                .then(r => r.ok ? r.json() : Promise.reject(r.status))
                .then(d => { this.impacto = d.impacto; })
                .catch(e => { this.error = e === 419 ? mensajeDe(419, {}) : 'No se pudo revisar el usuario. Inténtalo de nuevo.'; })
                .finally(() => { this.cargando = false; });
        },
        cerrar() { if (!this.trabajando) this.abierto = false; },
        desactivarEnSuLugar() { this.abierto = false; this.$dispatch('desactivar-usuario', { usuario: this.u }); },
        miles(n) { return Number(n).toLocaleString('es-CO'); },
        puedeEliminar() { return !!this.impacto && (!this.impacto.turnos || this.confirmacion.trim() === this.u.usuario); },
        confirmar() {
            this.trabajando = true; this.error = '';
            const cuerpo = new FormData(); cuerpo.append('_method', 'DELETE'); cuerpo.append('confirmar', this.confirmacion.trim());
            fetch(USUARIOS_URL + '/' + this.u.id, { method: 'POST', body: cuerpo, headers: { 'X-CSRF-TOKEN': TOKEN(), 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } })
                .then(async r => ({ ok: r.ok, estado: r.status, datos: await r.json().catch(() => ({})) }))
                .then(({ ok, estado, datos }) => {
                    if (ok && datos.success !== false) { recargarCon({ title: 'Usuario eliminado' }); return; }
                    this.trabajando = false; this.error = mensajeDe(estado, datos, 'No se pudo eliminar el usuario.');
                })
                .catch(() => { this.trabajando = false; this.error = 'No hay conexión con el servidor. Inténtalo de nuevo.'; });
        },
    }));

    Alpine.data('desactivarUsuario', () => ({
        abierto: false, u: null, trabajando: false, error: '',
        abrir({ usuario }) { this.u = usuario; this.error = ''; this.trabajando = false; this.abierto = true; },
        cerrar() { if (!this.trabajando) this.abierto = false; },
        confirmar() {
            this.trabajando = true; this.error = '';
            accion(USUARIOS_URL + '/' + this.u.id + '/desactivar')
                .then(({ ok, estado, datos }) => {
                    if (ok && datos.success !== false) { recargarCon({ title: 'Cuenta desactivada', description: 'No podrá iniciar sesión. Sus turnos y su historial se conservan.' }); return; }
                    this.trabajando = false; this.error = mensajeDe(estado, datos, 'No se pudo desactivar la cuenta.');
                })
                .catch(() => { this.trabajando = false; this.error = 'No hay conexión con el servidor. Inténtalo de nuevo.'; });
        },
    }));
});
</script>

@push('estilos')
<style>
[x-cloak] { display: none !important; }
.envoltorio-modal { display: contents; }
.usuarios-vista { font-variant-numeric: tabular-nums; }
.usuarios-vista .buscador { flex-basis: 12rem; }

.tabla-usuarios td { height: 2.375rem; padding-top: .25rem; padding-bottom: .25rem; white-space: nowrap; }
.usuario-nombre { font-weight: 600; color: #0f2547; }
.usuario-cedula { margin-left: .5rem; font-size: .75rem; color: #6b7280; }
.marca-yo { margin-left: .4rem; font-size: .6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #064b9e; }
.usuario-login { color: #4b5563; }
.rol-admin { font-weight: 600; color: #064b9e; }
.codigos-servicio { font-weight: 600; letter-spacing: .04em; color: #0f2547; }
.codigos-servicio:hover, .sin-asesor:hover { text-decoration: underline; }
.sin-asesor { display: inline-block; padding: .15rem .45rem; border-radius: .375rem; font-size: .75rem; font-weight: 600; background: #ffe2e2; color: #9f0712; }
.codigos-servicio:focus-visible, .sin-asesor:focus-visible { outline: 2px solid #064b9e; outline-offset: 2px; }
.actividad { display: inline-flex; align-items: center; gap: .4rem; color: #4b5563; }
.actividad__punto { width: .45rem; height: .45rem; border-radius: 9999px; background: #9ca3af; }
.actividad--atendiendo .actividad__punto { background: #d08700; }
.actividad--libre .actividad__punto { background: #00a63e; }
.actividad--descanso .actividad__punto { background: #155dfc; }
.actividad--canal .actividad__punto { background: #f54900; }
.actividad[class*="actividad--"] { color: #111827; font-weight: 500; }
.tabla-usuarios .celda-acciones { width: 1%; padding-right: .75rem; }
.tabla-usuarios .celda-acciones .accion-icono { opacity: 0; transition: opacity .15s ease; }
.tabla-usuarios tr:hover .accion-icono, .tabla-usuarios tr:focus-within .accion-icono { opacity: 1; }
@media (hover: none) { .tabla-usuarios .celda-acciones .accion-icono { opacity: 1; } }
.fila-esqueleto td { height: 2.375rem; }
.vacio-usuarios { padding: 2rem; text-align: center; font-size: .875rem; color: #6b7280; }

/* Formulario */
.form-seccion { margin-top: .2rem; font-size: .6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #6b7280; }
.form-seccion:first-child { margin-top: 0; }
.form-opcional { font-weight: 400; color: #9ca3af; }
.enlace-contrasena { align-self: flex-start; font-size: .8125rem; }
.segmentado { display: inline-flex; padding: .2rem; gap: .2rem; border-radius: .6rem; background: #eef1f6; }
.segmentado--ancho button { flex: 1; }
.segmentado button { height: 2.1rem; padding: 0 .9rem; border-radius: .45rem; font-size: .875rem; font-weight: 600; color: #4b5563; cursor: pointer; }
.segmentado button[aria-checked="true"] { background: #ffffff; color: #064b9e; box-shadow: 0 1px 2px rgba(16, 24, 40, .12); }
.segmentado button:focus-visible { outline: 2px solid #064b9e; outline-offset: 1px; }
.fila-auto { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-top: .5rem; }
.minutos-auto { display: inline-flex; align-items: center; gap: .4rem; font-size: .875rem; color: #374151; white-space: nowrap; }
.minutos-auto .campo { width: 4.5rem; height: 2.25rem; }
.modal-panel__pie--separado { align-items: center; }
.espaciador { flex: 1; }
.enlace-peligro { font-size: .875rem; font-weight: 600; color: #b7191c; cursor: pointer; }
.enlace-peligro:hover { text-decoration: underline; }
.enlace-peligro:focus-visible { outline: 2px solid #b7191c; outline-offset: 2px; }
.impacto-esqueleto { display: flex; flex-direction: column; gap: .6rem; padding: .25rem 0 .5rem; }
/* Cuenta desactivada */
.fila-desactivada .usuario-nombre, .fila-desactivada .usuario-login, .fila-desactivada .rol-admin { color: #6b7280; }
.marca-desactivada { margin-left: .4rem; padding: .05rem .4rem; border-radius: .3rem; font-size: .6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; background: #eef1f6; color: #4b5563; }
.aviso-desactivada { padding: .55rem .75rem; border-radius: .5rem; font-size: .8125rem; line-height: 1.45; background: #eef1f6; color: #374151; }
.modal-panel__pie--separado > .enlace-panel { margin-right: .75rem; font-size: .875rem; font-weight: 600; }
.form-texto .enlace-panel { display: inline; font-size: inherit; }
</style>
@endpush
@endsection
