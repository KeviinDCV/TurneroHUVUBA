@extends('layouts.admin')

@section('title', 'Inicio')
@section('titulo_pagina', config('panel.unidad_nombre', 'Inicio'))
@section('guia_pagina')
    Así está la sala · <span id="sala-hora">{{ now()->locale('es')->isoFormat('dddd D [de] MMMM, h:mm a') }}</span>
@endsection
@section('acciones_pagina')
    {{-- Herramientas del sistema: en voz baja, arriba a la derecha; el rojo vive solo en la confirmación --}}
    <button type="button" onclick="showCleanSessionsOptions()" id="cleanSessionsBtn" class="btn-secundario">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12a9 9 0 0 1 15.5-6.2L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-15.5 6.2L3 16"/><path d="M3 21v-5h5"/></svg>
        Limpiar sesiones
    </button>
    <button type="button" onclick="showEmergencyTurnosOptions()" id="emergencyTurnosBtn" class="btn-secundario">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="m21.7 18-8-14a2 2 0 0 0-3.4 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
        Emergencia de turnos
    </button>
@endsection
@section('content')
@php
    // Todo sale de App\Services\TableroService (el mismo cálculo que refresca GET /api/admin/tablero).
    $r = $tablero['resumen'];
    $esperaMax = $r['espera_max'];
@endphp

                {{-- «La sala» (2026-11): los módulos vistos desde arriba y, al lado, la cola de los que más llevan esperando.
                     Todo sale de App\Services\TableroService; la primera pintura usa $tablero y cada 15 s se refresca. --}}
                <div class="sala">
                    <div class="sala__principal">
                        <div class="sala-cifras">
                            <p class="sala-cifra"><span><b id="cifra-espera" class="sala-cifra__n">{{ $r['en_espera'] }}</b> en espera</span><span id="cifra-espera-mas" class="sala-cifra__mas"></span></p>
                            <p class="sala-cifra"><span><b id="cifra-larga" class="sala-cifra__n {{ ($esperaMax['supera_umbral'] ?? false) ? 'is-alerta' : '' }}">{{ $esperaMax ? $esperaMax['minutos'] . ' min' : '—' }}</b> espera máx.</span><span id="cifra-larga-mas" class="sala-cifra__mas"></span></p>
                            <p class="sala-cifra"><span><b id="cifra-atendidos" class="sala-cifra__n">{{ $r['atendidos_hoy'] }}</b> atendidos hoy</span><span id="cifra-atendidos-mas" class="sala-cifra__mas"></span></p>
                            <p class="sala-cifra"><span><b id="cifra-modulos" class="sala-cifra__n">{{ $r['modulos']['atendiendo'] ?? 0 }} de {{ $r['modulos']['abiertos'] ?? 0 }}</b> atendiendo</span><span id="cifra-modulos-mas" class="sala-cifra__mas"></span></p>
                        </div>

                        <section class="sala-seccion" aria-labelledby="titulo-modulos">
                            <h2 id="titulo-modulos" class="sala-titulo">Módulos</h2>
                            <div id="sala-modulos" class="sala-modulos"></div>
                            <p id="sala-cerrados" class="sala-nota" hidden></p>
                            <p id="sala-sin-modulo" class="sala-nota" hidden></p>
                        </section>

                        <section class="sala-seccion" aria-labelledby="titulo-servicios">
                            <h2 id="titulo-servicios" class="sala-titulo">Por servicio</h2>
                            <div id="sala-servicios" class="sala-servicios superficie"></div>
                        </section>
                    </div>

                    <aside class="sala-cola" aria-labelledby="titulo-cola">
                        <h2 id="titulo-cola" class="sala-cola__titulo">Cola</h2>
                        <p class="sala-cola__sub">Los que más llevan esperando</p>
                        <ol id="sala-cola-lista" class="sala-cola__lista"></ol>
                        <a id="sala-cola-todos" href="{{ route('admin.turnos') }}" class="enlace-panel sala-cola__todos">Ver los turnos</a>
                    </aside>
                </div>

{{-- Modales del Inicio (2026-11): mismo lenguaje del panel (.modal-panel del layout). Las opciones son tarjetas
     con su radio; la elegida se marca en azul (:has) y la acción que borra es la única en rojo. --}}
@php
    $iconoCerrar = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>';
@endphp

<!-- Modal: limpiar sesiones -->
<div id="cleanSessionsModal" class="modal-panel hidden" role="dialog" aria-modal="true" aria-labelledby="cleanSessionsTitle"
     onclick="if (event.target === this) closeCleanSessionsModal()">
    <div class="modal-panel__caja inicio-modal" tabindex="-1">
        <div class="modal-panel__cabeza">
            <h2 id="cleanSessionsTitle">Limpiar sesiones</h2>
            <button type="button" class="accion-icono" onclick="closeCleanSessionsModal()" aria-label="Cerrar">{!! $iconoCerrar !!}</button>
        </div>
        <div class="form-panel">
            <p class="form-texto">Elija qué sesiones cerrar. Los módulos de esas personas quedan libres para que otro asesor los tome.</p>

            <fieldset class="opciones">
                <legend class="sr-only">Qué sesiones cerrar</legend>
                <label class="opcion" for="cleanExpired">
                    <input type="radio" name="cleanOption" value="expired" id="cleanExpired" onchange="selectCleanOption('expired')">
                    <span class="opcion__texto">
                        <span class="opcion__titulo">Solo las vencidas</span>
                        <span class="opcion__ayuda">Las que llevan más de 15 minutos sin actividad o ya no existen.</span>
                    </span>
                </label>
                <label class="opcion" for="cleanAll">
                    <input type="radio" name="cleanOption" value="all" id="cleanAll" onchange="selectCleanOption('all')">
                    <span class="opcion__texto">
                        <span class="opcion__titulo">Todas</span>
                        <span class="opcion__ayuda"><b class="opcion__cuidado">Cierra también la de los asesores que estén atendiendo.</b> La suya se mantiene.</span>
                    </span>
                </label>
                <div class="opcion-grupo">
                    <label class="opcion" for="cleanSpecific">
                        <input type="radio" name="cleanOption" value="specific" id="cleanSpecific" onchange="selectCleanOption('specific')">
                        <span class="opcion__texto">
                            <span class="opcion__titulo">La de una persona</span>
                            <span class="opcion__ayuda">Elíjala en la lista de conectados.</span>
                        </span>
                    </label>

                    <!-- Personas conectadas (se cargan al elegir la opción) -->
                    <div id="usersList" class="personas hidden">
                        <div id="usersListContent" class="personas__lista" role="radiogroup" aria-label="Personas conectadas">
                            @foreach ([[45, 30], [38, 26], [52, 34]] as [$a, $b])
                                <div class="usuario-esqueleto" aria-hidden="true"><span class="esqueleto" style="width: {{ $a }}%"></span><span class="esqueleto" style="width: {{ $b }}%"></span></div>
                            @endforeach
                        </div>
                        <p id="selectedUserIndicator" class="personas__elegida hidden" aria-live="polite">Se cerrará la sesión de <b id="selectedUserName"></b>.</p>
                    </div>
                </div>
            </fieldset>

            <div class="modal-panel__pie">
                <button type="button" onclick="closeCleanSessionsModal()" class="btn-secundario">Cancelar</button>
                <button type="button" onclick="confirmCleanSessions()" id="confirmCleanBtn" disabled class="btn-peligro">Limpiar sesiones</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: emergencia de turnos -->
<div id="emergencyTurnosModal" class="modal-panel hidden" role="dialog" aria-modal="true" aria-labelledby="emergencyTurnosTitle"
     onclick="if (event.target === this) closeEmergencyTurnosModal()">
    <div class="modal-panel__caja inicio-modal" tabindex="-1">
        <div class="modal-panel__cabeza">
            <h2 id="emergencyTurnosTitle">Emergencia de turnos</h2>
            <button type="button" class="accion-icono" onclick="closeEmergencyTurnosModal()" aria-label="Cerrar">{!! $iconoCerrar !!}</button>
        </div>
        <div class="form-panel">
            <div class="aviso aviso--error" role="alert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.7 18-8-14a2 2 0 0 0-3.4 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                <p>Úselo solo ante un error grave del sistema. Los turnos que se eliminan <b>no se pueden recuperar</b>.</p>
            </div>

            <fieldset class="opciones">
                <legend class="sr-only">Qué turnos eliminar</legend>
                <label class="opcion" for="deletePending">
                    <input type="radio" name="turnosOption" value="pending" id="deletePending" onchange="selectTurnosOption('pending')">
                    <span class="opcion__texto">
                        <span class="opcion__titulo">Los que esperan hoy</span>
                        <span class="opcion__ayuda">Pendientes y aplazados del día. Los atendidos se conservan para los informes.</span>
                    </span>
                </label>
                <label class="opcion" for="deleteToday">
                    <input type="radio" name="turnosOption" value="today" id="deleteToday" onchange="selectTurnosOption('today')">
                    <span class="opcion__texto">
                        <span class="opcion__titulo">Todos los de hoy</span>
                        <span class="opcion__ayuda"><b class="opcion__cuidado">También los atendidos: se pierden las cifras del día.</b></span>
                    </span>
                </label>
                <div class="opcion-grupo">
                    <label class="opcion" for="deleteService">
                        <input type="radio" name="turnosOption" value="service" id="deleteService" onchange="selectTurnosOption('service')">
                        <span class="opcion__texto">
                            <span class="opcion__titulo">Los de un servicio</span>
                            <span class="opcion__ayuda">Todos los de hoy de ese servicio: en espera, aplazados y atendidos.</span>
                        </span>
                    </label>
                    <div id="serviceSelector" class="opcion__extra hidden">
                        <label for="servicioSelect" class="form-campo">
                            Servicio
                            <select id="servicioSelect" class="campo">
                                <option value="">Cargando servicios…</option>
                            </select>
                        </label>
                    </div>
                </div>
            </fieldset>

            <div class="modal-panel__pie">
                <button type="button" onclick="closeEmergencyTurnosModal()" class="btn-secundario">Cancelar</button>
                <button type="button" onclick="confirmEmergencyTurnos()" id="confirmTurnosBtn" disabled class="btn-peligro">Eliminar turnos</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: estadísticas del asesor (Alpine.js; se abre con el evento open-estadisticas-modal) -->
<div x-data="estadisticasUsuarioModal()" x-cloak @keydown.escape.window="isOpen = false">
    <div x-show="isOpen" x-transition.opacity class="modal-panel" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="estadisticasTitle">
        <div @click.away="isOpen = false" class="modal-panel__caja modal-panel__caja--ancha">
            <div class="modal-panel__cabeza">
                <div class="est-cabeza">
                    <h2 id="estadisticasTitle" x-text="usuarioNombre || 'Estadísticas del asesor'">Estadísticas del asesor</h2>
                    <p class="form-ayuda">Estadísticas del asesor en el período elegido</p>
                </div>
                <button type="button" class="accion-icono" @click="isOpen = false" aria-label="Cerrar">{!! $iconoCerrar !!}</button>
            </div>

            <div class="form-panel">
                <div class="est-periodo">
                    <div class="filtros-rapidos filtros-rapidos--papel" role="group" aria-label="Período rápido">
                        <button type="button" class="filtro-rapido" :aria-pressed="(rangoDe() === 'hoy').toString()" @click="setFechaRapida('hoy')">Hoy</button>
                        <button type="button" class="filtro-rapido" :aria-pressed="(rangoDe() === 'ayer').toString()" @click="setFechaRapida('ayer')">Ayer</button>
                        <button type="button" class="filtro-rapido" :aria-pressed="(rangoDe() === 'semana').toString()" @click="setFechaRapida('semana')">Últimos 7 días</button>
                        <button type="button" class="filtro-rapido" :aria-pressed="(rangoDe() === 'mes').toString()" @click="setFechaRapida('mes')">Último mes</button>
                    </div>
                    <form class="est-fechas" @submit.prevent="cargarEstadisticas()">
                        <label class="form-campo">Desde
                            <input type="date" class="campo" x-model="fechaInicio" :max="fechaFin">
                        </label>
                        <label class="form-campo">Hasta
                            <input type="date" class="campo" x-model="fechaFin" :min="fechaInicio">
                        </label>
                        <button type="submit" class="btn-secundario">Consultar</button>
                    </form>
                </div>

                <!-- Mientras cargan: la forma de las cifras, los tiempos y la tabla de turnos -->
                <div x-show="loading" class="est-esqueleto" role="status">
                    <span class="sr-only">Cargando estadísticas…</span>
                    <div class="est-esqueleto__fila est-esqueleto__fila--4" aria-hidden="true">
                        <span class="esqueleto esqueleto--bloque"></span><span class="esqueleto esqueleto--bloque"></span>
                        <span class="esqueleto esqueleto--bloque"></span><span class="esqueleto esqueleto--bloque"></span>
                    </div>
                    <div class="est-esqueleto__fila est-esqueleto__fila--3" aria-hidden="true">
                        <span class="esqueleto esqueleto--bloque-bajo"></span><span class="esqueleto esqueleto--bloque-bajo"></span><span class="esqueleto esqueleto--bloque-bajo"></span>
                    </div>
                    <span class="esqueleto" style="width: 10rem" aria-hidden="true"></span>
                    <div class="est-esqueleto__tabla" aria-hidden="true">
                        <span class="esqueleto"></span><span class="esqueleto"></span><span class="esqueleto"></span><span class="esqueleto"></span>
                    </div>
                </div>

                <!-- Contenido de las estadísticas (lo pinta renderizarEstadisticas) -->
                <div x-show="!loading" id="estadisticas-contenido" class="est-contenido" aria-live="polite"></div>
            </div>
        </div>
    </div>
</div>

<script>
let selectedCleanOption = null;
let selectedUserId = null;

// Peticiones de Inicio: piden JSON y reconocen la sesión cerrada (HTTP 419/401 o redirección al acceso).
// Antes se leía como JSON la página HTML de "sesión expirada" y salía "Unexpected token '<'".
const CSRF_INICIO = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
const USUARIO_ACTUAL_ID = @json(auth()->id());
class SesionCerrada extends Error {}
function pedirJson(url, opciones = {}) {
    const cabeceras = Object.assign({ 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, opciones.headers);
    if ((opciones.method || 'GET') !== 'GET') cabeceras['X-CSRF-TOKEN'] = CSRF_INICIO;
    return fetch(url, Object.assign({}, opciones, { headers: cabeceras })).then(r => {
        if (r.status === 401 || r.status === 419) throw new SesionCerrada();
        if (!(r.headers.get('content-type') || '').includes('application/json')) {
            if (r.redirected) throw new SesionCerrada();
            throw new Error('el servidor respondió HTTP ' + r.status);
        }
        if (opciones.exigirOk && !r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
    });
}
// Con la sesión cerrada se detiene la actualización y se ofrece recargar (la página lleva al acceso).
function mostrarSesionCerrada() {
    if (autoUpdateInterval) { clearInterval(autoUpdateInterval); autoUpdateInterval = null; }
    // Se queda abierto (sin irse solo) hasta que recarguen: la página ya no puede actualizarse.
    const unDia = 24 * 60 * 60 * 1000;
    sileo.error({
        title: 'Sesión cerrada',
        description: 'Tu sesión se cerró (por inactividad o porque se limpiaron las sesiones). Recarga la página para volver a entrar.',
        button: { title: 'Recargar página', onClick: () => location.reload() },
        duration: unDia, autopilot: { expand: 150, collapse: unDia },
    });
}

// Abrir y cerrar los modales del Inicio: el foco entra a la caja y vuelve al botón que lo abrió.
let focoAntesDelModal = null;
function abrirModalInicio(id) {
    focoAntesDelModal = document.activeElement;
    const modal = document.getElementById(id);
    modal.classList.remove('hidden');
    modal.querySelector('.modal-panel__caja')?.focus({ preventScroll: true });
}
function cerrarModalInicio(id) {
    const modal = document.getElementById(id);
    if (!modal || modal.classList.contains('hidden')) return;
    modal.classList.add('hidden');
    if (focoAntesDelModal && document.contains(focoAntesDelModal)) focoAntesDelModal.focus({ preventScroll: true });
    focoAntesDelModal = null;
}
// Escape cierra el modal que esté abierto (el de estadísticas lo cierra Alpine)
document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    cerrarModalInicio('cleanSessionsModal');
    cerrarModalInicio('emergencyTurnosModal');
});

function showCleanSessionsOptions() {
    // Resetear selecciones
    selectedCleanOption = null;
    selectedUserId = null;
    const confirmBtn = document.getElementById('confirmCleanBtn');
    confirmBtn.disabled = true;
    confirmBtn.textContent = 'Limpiar sesiones';
    document.querySelectorAll('input[name="cleanOption"]').forEach(radio => { radio.checked = false; });
    document.getElementById('usersList').classList.add('hidden');
    document.getElementById('selectedUserIndicator').classList.add('hidden');
    abrirModalInicio('cleanSessionsModal');
}

function selectCleanOption(option) {
    selectedCleanOption = option;
    selectedUserId = null;

    // Marcar el radio correspondiente (también si se llama desde fuera del radio)
    document.getElementById('clean' + option.charAt(0).toUpperCase() + option.slice(1)).checked = true;

    const confirmBtn = document.getElementById('confirmCleanBtn');
    document.getElementById('selectedUserIndicator').classList.add('hidden');

    if (option === 'specific') {
        // Lista de conectados, siempre recién pedida; el botón espera a que elijan a alguien
        document.getElementById('usersList').classList.remove('hidden');
        loadActiveUsers();
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Limpiar sesión';
    } else {
        document.getElementById('usersList').classList.add('hidden');
        confirmBtn.disabled = false;
        confirmBtn.textContent = option === 'all' ? 'Cerrar todas las sesiones' : 'Limpiar vencidas';
    }
}

// Estado de cada persona conectada, con palabra y color (DESIGN.md: Estado con Palabras)
function estadoPersona(status) {
    const s = String(status || '').toUpperCase();
    const tono = s === 'DISPONIBLE' ? 'ok' : (s === 'OCUPADO' ? 'alerta' : 'neutro');
    const texto = s ? s.charAt(0) + s.slice(1).toLowerCase() : 'Sin estado';
    return '<span class="estado-chip estado-chip--' + tono + '">' + escHtml(texto) + '</span>';
}

function loadActiveUsers() {
    pedirJson('{{ route("api.admin.usuarios-activos") }}', { exigirOk: true })
        .then(todos => {
            // La sesión propia no se limpia desde aquí (dejaría esta página sin sesión)
            const users = todos.filter(u => Number(u.id) !== Number(USUARIO_ACTUAL_ID));
            const container = document.getElementById('usersListContent');

            if (users.length === 0) {
                container.innerHTML = '<p class="personas__vacio">No hay nadie más conectado.</p>';
                return;
            }

            // Preservar la selección actual antes de regenerar
            const currentSelection = selectedUserId;

            container.innerHTML = users.map(user => {
                const isSelected = currentSelection && currentSelection == user.id;
                return '<label class="persona" data-user-id="' + escHtml(user.id) + '">'
                    + '<input type="radio" name="selectedUser" value="' + escHtml(user.id) + '"' + (isSelected ? ' checked' : '') + '>'
                    + '<span class="persona__texto">'
                    +   '<span class="persona__nombre">' + escHtml(user.name) + '</span>'
                    +   '<span class="persona__detalle">@' + escHtml(user.nombre_usuario) + ' · ' + escHtml(user.rol)
                    +     (user.caja ? ' · Módulo ' + escHtml(user.caja) : '') + '</span>'
                    + '</span>'
                    + '<span class="persona__estado">' + estadoPersona(user.status)
                    +   '<span class="persona__tiempo">' + escHtml(user.tiempo_sesion) + '</span></span>'
                    + '</label>';
            }).join('');

            container.querySelectorAll('input[name="selectedUser"]').forEach(radio => {
                radio.addEventListener('change', function () {
                    if (this.checked) {
                        const userContainer = this.closest('[data-user-id]');
                        selectUser(parseInt(this.value, 10), userContainer.querySelector('.persona__nombre').textContent, userContainer);
                    }
                });
            });

            // Restaurar la selección si la persona sigue conectada
            if (currentSelection) {
                const selectedUser = users.find(u => u.id == currentSelection);
                if (selectedUser) selectUser(Number(selectedUser.id), selectedUser.name);
            }
        })
        .catch(error => {
            if (error instanceof SesionCerrada) { closeCleanSessionsModal(); mostrarSesionCerrada(); return; }
            console.error('Error cargando usuarios:', error);
            document.getElementById('usersListContent').innerHTML = '<p class="personas__vacio texto-error">No se pudo cargar la lista. Elija la opción otra vez para reintentar.</p>';
        });
}

// La persona elegida se marca sola (CSS :has en el radio); aquí se habilita el botón y se nombra a quién se cierra.
function selectUser(userId, userName, element) {
    selectedUserId = userId;
    const confirmBtn = document.getElementById('confirmCleanBtn');
    confirmBtn.disabled = false;
    confirmBtn.textContent = 'Cerrar la sesión de ' + userName;
    document.getElementById('selectedUserName').textContent = userName;
    document.getElementById('selectedUserIndicator').classList.remove('hidden');
}

function closeCleanSessionsModal() {
    cerrarModalInicio('cleanSessionsModal');
}

function confirmCleanSessions() {
    if (!selectedCleanOption) {
        sileo.warning({ title: 'Elige una opción de limpieza' });
        return;
    }

    if (selectedCleanOption === 'specific' && !selectedUserId) {
        sileo.warning({ title: 'Elige un usuario' });
        return;
    }

    const btn = document.getElementById('cleanSessionsBtn');
    const originalText = btn.innerHTML;

    // Deshabilitar botón y mostrar loading
    btn.disabled = true;
    btn.textContent = 'Limpiando…';

    // Cerrar modal de confirmación
    closeCleanSessionsModal();

    // Preparar datos para enviar
    const requestData = {
        option: selectedCleanOption
    };

    if (selectedCleanOption === 'specific') {
        requestData.user_id = selectedUserId;
    }

    // Determinar la ruta según la opción
    let route = '{{ route("admin.clean-sessions") }}';
    if (selectedCleanOption === 'all') {
        route = '{{ route("admin.clean-all-sessions") }}';
    } else if (selectedCleanOption === 'specific') {
        route = '{{ route("admin.clean-user-session") }}';
    }

    pedirJson(route, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(requestData)
    })
    .then(data => {
        // Restaurar botón
        btn.disabled = false;
        btn.innerHTML = originalText;

        // Mostrar resultado
        showResult(data, { ok: 'Sesiones limpiadas', error: 'No se limpiaron las sesiones' });

        // Actualizar la tabla de usuarios activos
        actualizarUsuariosActivos();
    })
    .catch(error => {
        // Restaurar botón
        btn.disabled = false;
        btn.innerHTML = originalText;

        if (error instanceof SesionCerrada) { mostrarSesionCerrada(); return; }
        showResult({
            success: false,
            message: 'No se pudo completar la limpieza (' + error.message + '). Inténtalo de nuevo.'
        }, { ok: 'Sesiones limpiadas', error: 'No se limpiaron las sesiones' });
    });
}

// Resultado de "Limpiar sesiones" o de "Emergencia de turnos": toast (sileo.js) con el mensaje y el detalle.
function showResult(data, titulos) {
    const d = data.data || {};
    const detalle = [];
    if (d.usuarios_limpiados !== undefined) detalle.push('Usuarios limpiados: ' + d.usuarios_limpiados);
    if (d.usuario_limpiado !== undefined) detalle.push('Usuario: ' + d.usuario_limpiado);
    if (d.cajas_liberadas !== undefined) detalle.push('Cajas liberadas: ' + d.cajas_liberadas);
    if (d.sesiones_expiradas_eliminadas !== undefined) detalle.push('Sesiones expiradas eliminadas: ' + d.sesiones_expiradas_eliminadas);
    if (d.sesiones_eliminadas !== undefined) detalle.push('Sesiones eliminadas: ' + d.sesiones_eliminadas);
    if (d.sesion_eliminada !== undefined) detalle.push('Sesión eliminada: ' + (d.sesion_eliminada ? 'sí' : 'no'));
    const description = [data.message, detalle.join(' · ')].filter(Boolean).join('\n');
    if (data.success) sileo.success({ title: titulos.ok, description, duration: 8000 });
    else sileo.error({ title: titulos.error, description, duration: 8000 });
}

// ===== Inicio: cifras, cola por servicio y asesores conectados =====
// Un solo origen de datos: App\Services\TableroService. La primera pintura usa los datos que ya trae
// la página y cada 15 s se piden de nuevo a /api/admin/tablero (en pausa con la pestaña oculta).
const TABLERO_URL = @json(route('api.admin.tablero'));
const ASIGNACION_URL = @json(route('admin.asignacion-servicios'));
let autoUpdateInterval = null;

function escHtml(t) {
    return String(t ?? '').replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
}
function cifra(n) { return '<span class="font-semibold text-gray-700">' + escHtml(n) + '</span>'; }
function ponerTexto(id, v) { const el = document.getElementById(id); if (el) el.textContent = v; }
function ponerHtml(id, v) { const el = document.getElementById(id); if (el) el.innerHTML = v; }

function plural(n, uno, varios) { return n + ' ' + (n === 1 ? uno : varios); }

function pintarCifras(t) {
    const r = t.resumen;
    ponerTexto('cifra-espera', r.en_espera);
    ponerTexto('cifra-espera-mas', [
        plural(r.prioritarios, 'prioritario', 'prioritarios'),
        plural(r.aplazados, 'aplazado', 'aplazados'),
    ].join(' · '));

    const em = r.espera_max;
    const larga = document.getElementById('cifra-larga');
    if (larga) {
        larga.textContent = em ? em.minutos + ' min' : '—';
        larga.classList.toggle('is-alerta', !!(em && em.supera_umbral));
        larga.title = em && em.supera_umbral ? 'Pasó el límite de ' + (em.prioritario ? t.umbrales.prioritario : t.umbrales.espera) + ' min' : '';
    }
    ponerTexto('cifra-larga-mas', em ? em.turno + (em.prioritario ? ' prioritario' : '') + ' · ' + (em.servicio || '') : 'Nadie en espera');

    ponerTexto('cifra-atendidos', r.atendidos_hoy);
    ponerTexto('cifra-atendidos-mas', [(r.ultima_hora ? r.ultima_hora.finalizaron : 0) + ' en la última hora'].concat(r.transferidos_hoy ? [plural(r.transferidos_hoy, 'transferido', 'transferidos')] : []).join(' · '));

    const m = r.modulos || {};
    ponerTexto('cifra-modulos', (m.atendiendo || 0) + ' de ' + (m.abiertos || 0));
    ponerTexto('cifra-modulos-mas', [plural(m.libres || 0, 'libre', 'libres'), (m.descanso || 0) + ' en descanso'].concat(m.canal ? [m.canal + ' en canal'] : []).join(' · '));

    if (t.generado) {
        const hora = new Date(t.generado);
        if (!isNaN(hora)) {
            ponerTexto('sala-hora', new Intl.DateTimeFormat('es-CO', {
                weekday: 'long', day: 'numeric', month: 'long', hour: 'numeric', minute: '2-digit', timeZone: 'America/Bogota',
            }).format(hora).replace(/^(\p{L}+),/u, '$1'));
        }
    }
}

// Estado de cada módulo: texto, color del chip y del número
const ESTADOS_MODULO = {
    atendiendo: ['Atendiendo', 'ok'],
    libre: ['Libre', 'info'],
    descanso: ['En descanso', 'alerta'],
    canal: ['Canal no presencial', 'neutro'],
};

function pintarModulos(t) {
    const caja = document.getElementById('sala-modulos');
    if (!caja) return;
    const abiertos = t.modulos.filter(m => m.asesor);
    const cerrados = t.modulos.filter(m => !m.asesor && m.estado === 'cerrado');
    const limite = t.umbrales.atencion;

    caja.innerHTML = abiertos.length ? abiertos.map(m => {
        const [texto, tono] = ESTADOS_MODULO[m.estado] || [m.estado, 'neutro'];
        let pie;
        if (m.turno) {
            const larga = m.turno.minutos !== null && m.turno.minutos > limite;
            pie = '<b class="sala-modulo__turno">' + escHtml(m.turno.codigo) + '</b>'
                + '<span class="sala-modulo__min' + (larga ? ' is-alerta' : '') + '"' + (larga ? ' title="Pasó el límite de atención de ' + limite + ' min"' : '') + '>'
                + (m.turno.minutos !== null ? escHtml(m.turno.minutos) + ' min' : '') + '</span>';
        } else {
            pie = '<span class="sala-modulo__vacio">' + (m.estado === 'descanso' ? 'En descanso' : (m.estado === 'canal' ? 'En canal no presencial' : 'Sin turno')) + '</span>';
        }
        return '<button type="button" class="sala-modulo sala-modulo--' + tono + '" data-asesor-id="' + escHtml(m.asesor.id) + '" data-asesor-nombre="' + escHtml(m.asesor.nombre) + '"'
            + ' title="Ver estadísticas de ' + escHtml(m.asesor.nombre) + (m.ubicacion ? ' · ' + escHtml(m.ubicacion) : '') + '">'
            + '<span class="sala-modulo__cabeza"><span class="sala-modulo__num">' + escHtml(String(m.numero).padStart(2, '0')) + '</span>'
            + '<span class="estado-chip estado-chip--' + tono + '">' + texto + '</span></span>'
            + '<span class="sala-modulo__asesor">' + escHtml(m.asesor.nombre) + '</span>'
            + '<span class="sala-modulo__hoy">' + plural(m.atendidos_hoy || 0, 'atendido hoy', 'atendidos hoy') + '</span>'
            + '<span class="sala-modulo__pie">' + pie + '</span>'
            + '</button>';
    }).join('') : '<p class="sala-vacio">Ningún módulo tiene asesor en este momento.</p>';

    const nota = document.getElementById('sala-cerrados');
    if (nota) {
        nota.hidden = !cerrados.length;
        nota.innerHTML = cerrados.length
            ? '<span class="sala-nota__titulo">Sin asesor</span> ' + cerrados.map(m => '<span class="sala-nota__num" title="' + escHtml(m.nombre || '') + '">' + escHtml(m.numero) + '</span>').join('')
            : '';
    }

    // Asesores conectados que no ocupan ningún módulo (o que están en canal sin módulo)
    const sinModulo = t.asesores.filter(a => a.modulo === null);
    const otra = document.getElementById('sala-sin-modulo');
    if (otra) {
        otra.hidden = !sinModulo.length;
        otra.innerHTML = sinModulo.length
            ? '<span class="sala-nota__titulo">Conectados sin módulo</span> ' + sinModulo.map(a =>
                '<button type="button" class="sala-nota__persona" data-asesor-id="' + escHtml(a.id) + '" data-asesor-nombre="' + escHtml(a.nombre) + '">'
                + escHtml(a.nombre) + (a.estado === 'canal' ? ' · canal' : '') + '</button>').join('')
            : '';
    }
}

function pintarServicios(t) {
    const caja = document.getElementById('sala-servicios');
    if (!caja) return;
    if (!t.servicios.length) {
        caja.innerHTML = '<p class="sala-vacio">Hoy no hay turnos en cola ni atendidos.</p>';
        return;
    }
    const tope = Math.max(1, ...t.servicios.map(s => s.en_cola));
    caja.innerHTML = '<div class="sala-servicio sala-servicio--cabeza" aria-hidden="true"><span>Servicio</span><span></span><span>En cola</span><span>Espera máx.</span><span>Cubren</span></div>'
        + t.servicios.map(s => {
            const espera = s.espera_max_min === null || s.espera_max_min === undefined ? '—' : s.espera_max_min + ' min';
            const cubren = s.sin_cobertura
                ? '<a href="' + ASIGNACION_URL + '?servicio=' + encodeURIComponent(s.id) + '" class="etiqueta-asignar" title="Ningún asesor conectado puede atender este servicio">Nadie · Asignar</a>'
                : escHtml(s.cubren);
            return '<div class="sala-servicio' + (s.sin_cobertura ? ' is-sin-cobertura' : '') + '">'
                + '<span class="sala-servicio__nombre" title="' + escHtml(s.nombre) + '">' + escHtml(s.nombre)
                + (s.prioritarios > 0 ? ' <span class="sala-servicio__prio">' + escHtml(s.prioritarios) + ' prior.</span>' : '') + '</span>'
                + '<span class="sala-servicio__pista"><i style="width:' + Math.round(s.en_cola / tope * 100) + '%"></i></span>'
                + '<span class="sala-servicio__n">' + escHtml(s.en_cola) + '</span>'
                + '<span class="sala-servicio__espera' + (s.alerta ? ' is-alerta' : '') + '"' + (s.alerta ? ' title="' + escHtml(s.alerta_motivo) + '"' : '') + '>' + escHtml(espera) + '</span>'
                + '<span class="sala-servicio__cubren">' + cubren + '</span>'
                + '</div>';
        }).join('');
}

function pintarColaTurnos(t) {
    const lista = document.getElementById('sala-cola-lista');
    if (!lista) return;
    const cola = t.cola || [];
    lista.innerHTML = cola.length ? cola.map(c =>
        '<li class="sala-turno' + (c.supera_umbral ? ' is-alerta' : '') + '">'
        + '<span class="sala-turno__codigo">' + escHtml(c.codigo) + '</span>'
        + '<span class="sala-turno__min"' + (c.supera_umbral ? ' title="Pasó el límite de ' + (c.prioritario ? t.umbrales.prioritario : t.umbrales.espera) + ' min"' : '') + '>'
        + (c.minutos !== null ? escHtml(c.minutos) + ' min' : '—') + '</span>'
        + '<span class="sala-turno__servicio">' + escHtml(c.servicio || '') + '</span>'
        + ((c.prioritario || c.aplazado) ? '<span class="sala-turno__marcas">'
            + (c.prioritario ? '<span class="estado-chip estado-chip--alerta">Prioritario</span>' : '')
            + (c.aplazado ? '<span class="estado-chip estado-chip--neutro">Aplazado</span>' : '') + '</span>' : '')
        + '</li>'
    ).join('') : '<li class="sala-cola__vacia">No hay nadie esperando.</li>';

    const todos = document.getElementById('sala-cola-todos');
    if (todos) {
        const n = t.resumen.en_espera;
        todos.hidden = n === 0;
        todos.textContent = n > cola.length ? 'Ver los ' + n + ' turnos en espera' : 'Ver los turnos';
    }
}

// Clic en un módulo o en un asesor sin módulo: estadísticas del asesor. Delegado y con data-*, sin interpolar
// el nombre dentro de JavaScript en línea (un apóstrofo en el nombre no rompe nada).
document.querySelector('.sala')?.addEventListener('click', e => {
    const el = e.target.closest('[data-asesor-id]');
    if (el) abrirModalEstadisticas(parseInt(el.dataset.asesorId, 10), el.dataset.asesorNombre);
});

function pintarTablero(t) {
    pintarCifras(t);
    pintarModulos(t);
    pintarServicios(t);
    pintarColaTurnos(t);
}

function actualizarTablero() {
    return pedirJson(TABLERO_URL, { cache: 'no-store', exigirOk: true })
        .then(pintarTablero)
        .catch(err => {
            // Sin sesión, las cifras en pantalla dejan de ser reales: se avisa una vez en lugar de seguir mostrándolas
            if (err instanceof SesionCerrada) { if (autoUpdateInterval) mostrarSesionCerrada(); return; }
            console.warn('No se pudo actualizar el Inicio:', err);
        });
}

// Los modales (limpiar sesiones, emergencia) siguen llamando a estos nombres después de actuar.
const actualizarUsuariosActivos = actualizarTablero;
const actualizarTurnosPorServicio = actualizarTablero;
const actualizarTurnosPorAsesor = actualizarTablero;
const actualizarTurnosEnCola = actualizarTablero;

function startAutoUpdate() {
    if (autoUpdateInterval) clearInterval(autoUpdateInterval);
    autoUpdateInterval = setInterval(() => { if (!document.hidden) actualizarTablero(); }, 15000);
}
document.addEventListener('visibilitychange', () => { if (!document.hidden) actualizarTablero(); });

pintarTablero(@js($tablero));

// Variables para el modal de emergencia de turnos
let selectedTurnosOption = null;
let selectedServiceId = null;

// Funciones para el modal de emergencia de turnos
function showEmergencyTurnosOptions() {
    // Resetear selecciones
    selectedTurnosOption = null;
    selectedServiceId = null;
    const confirmBtn = document.getElementById('confirmTurnosBtn');
    confirmBtn.disabled = true;
    confirmBtn.textContent = 'Eliminar turnos';
    document.querySelectorAll('input[name="turnosOption"]').forEach(radio => { radio.checked = false; });
    document.getElementById('serviceSelector').classList.add('hidden');
    abrirModalInicio('emergencyTurnosModal');
    loadServiciosForEmergency();
}

function closeEmergencyTurnosModal() {
    cerrarModalInicio('emergencyTurnosModal');
}

function selectTurnosOption(option) {
    selectedTurnosOption = option;

    // Marcar el radio correspondiente
    document.getElementById('delete' + option.charAt(0).toUpperCase() + option.slice(1)).checked = true;

    // Mostrar/ocultar el selector de servicio
    const serviceSelector = document.getElementById('serviceSelector');
    if (option === 'service') {
        serviceSelector.classList.remove('hidden');
        selectedServiceId = document.getElementById('servicioSelect').value || null;
    } else {
        serviceSelector.classList.add('hidden');
        selectedServiceId = null;
    }

    updateTurnosConfirmButton();
}

function updateTurnosConfirmButton() {
    const confirmBtn = document.getElementById('confirmTurnosBtn');
    const canConfirm = selectedTurnosOption && (selectedTurnosOption !== 'service' || selectedServiceId);
    confirmBtn.disabled = !canConfirm;
    confirmBtn.textContent = !canConfirm ? 'Eliminar turnos' : ({
        pending: 'Eliminar los que esperan',
        today: 'Eliminar todos los de hoy',
        service: 'Eliminar los del servicio',
    })[selectedTurnosOption];
}

function loadServiciosForEmergency() {
    pedirJson('/api/servicios-activos', { exigirOk: true })
        .then(servicios => {
            const select = document.getElementById('servicioSelect');
            select.innerHTML = '<option value="">Elija un servicio</option>';

            servicios.forEach(servicio => {
                const option = document.createElement('option');
                option.value = servicio.id;
                option.textContent = servicio.nombre;
                select.appendChild(option);
            });
        })
        .catch(error => {
            if (error instanceof SesionCerrada) { closeEmergencyTurnosModal(); mostrarSesionCerrada(); return; }
            console.error('Error cargando servicios:', error);
            const select = document.getElementById('servicioSelect');
            select.innerHTML = '<option value="">No se pudieron cargar los servicios</option>';
        });
}

// Event listener para el selector de servicio
document.addEventListener('DOMContentLoaded', function() {
    const servicioSelect = document.getElementById('servicioSelect');
    if (servicioSelect) {
        servicioSelect.addEventListener('change', function() {
            selectedServiceId = this.value;
            updateTurnosConfirmButton();
        });
    }
});

function confirmEmergencyTurnos() {
    if (!selectedTurnosOption) return;

    const confirmBtn = document.getElementById('confirmTurnosBtn');
    const originalText = confirmBtn.textContent;

    // Deshabilitar botón y mostrar loading
    confirmBtn.disabled = true;
    confirmBtn.textContent = 'Eliminando…';

    // Preparar datos de la petición
    const requestData = {
        option: selectedTurnosOption
    };

    if (selectedTurnosOption === 'service' && selectedServiceId) {
        requestData.service_id = selectedServiceId;
    }

    // Determinar la ruta
    const route = '/admin/emergency-turnos';

    pedirJson(route, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(requestData)
    })
    .then(data => {
        // Restaurar botón
        confirmBtn.disabled = false;
        confirmBtn.textContent = originalText;

        // Cerrar modal
        closeEmergencyTurnosModal();

        // Mostrar resultado
        showResult(data, { ok: 'Turnos eliminados', error: 'No se eliminaron los turnos' });

        // Actualizar las estadísticas
        actualizarTurnosPorServicio();
        actualizarTurnosPorAsesor();
        actualizarTurnosEnCola();
    })
    .catch(error => {
        console.error('Error:', error);

        // Restaurar botón
        confirmBtn.disabled = false;
        confirmBtn.textContent = originalText;

        if (error instanceof SesionCerrada) { closeEmergencyTurnosModal(); mostrarSesionCerrada(); return; }
        showResult({
            success: false,
            message: 'No se pudo completar la acción (' + error.message + '). Inténtalo de nuevo.'
        }, { ok: 'Turnos eliminados', error: 'No se eliminaron los turnos' });
    });
}

// Iniciar actualización automática
startAutoUpdate();

// ===== MODAL DE ESTADÍSTICAS DE USUARIO (Alpine.js) =====
function abrirModalEstadisticas(userId, nombreUsuario) {
    window.dispatchEvent(new CustomEvent('open-estadisticas-modal', {
        detail: { userId, nombreUsuario }
    }));
}

// AAAA-MM-DD en la hora local del equipo: la fecha en UTC, de noche, ya es la de mañana.
function fechaLocalISO(d = new Date()) {
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
}

// Rangos rápidos del modal de estadísticas: [desde, hasta] en fechas locales
function rangoRapido(tipo) {
    const hoy = new Date();
    const desde = new Date(hoy);
    if (tipo === 'ayer') { desde.setDate(desde.getDate() - 1); return [desde, desde]; }
    if (tipo === 'semana') { desde.setDate(desde.getDate() - 7); return [desde, hoy]; }
    if (tipo === 'mes') { desde.setMonth(desde.getMonth() - 1); return [desde, hoy]; }
    return [hoy, hoy];
}

// Componente Alpine.js para el modal de estadísticas
function estadisticasUsuarioModal() {
    return {
        isOpen: false,
        loading: false,
        usuarioId: null,
        usuarioNombre: '',
        fechaInicio: fechaLocalISO(),
        fechaFin: fechaLocalISO(),

        init() {
            window.addEventListener('open-estadisticas-modal', (event) => {
                const { userId, nombreUsuario } = event.detail;
                this.usuarioId = userId;
                this.usuarioNombre = nombreUsuario;
                this.fechaInicio = fechaLocalISO();
                this.fechaFin = fechaLocalISO();
                this.isOpen = true;
                this.cargarEstadisticas();
            });
        },

        // Qué atajo coincide con las fechas puestas (para marcarlo); null si son otras
        rangoDe() {
            return ['hoy', 'ayer', 'semana', 'mes'].find(tipo => {
                const [desde, hasta] = rangoRapido(tipo);
                return fechaLocalISO(desde) === this.fechaInicio && fechaLocalISO(hasta) === this.fechaFin;
            }) || null;
        },

        setFechaRapida(tipo) {
            const [desde, hasta] = rangoRapido(tipo);
            this.fechaInicio = fechaLocalISO(desde);
            this.fechaFin = fechaLocalISO(hasta);
            this.cargarEstadisticas();
        },

        cargarEstadisticas() {
            if (!this.usuarioId) return;

            this.loading = true;
            const contenedor = document.getElementById('estadisticas-contenido');

            let url = `/api/admin/usuario/${this.usuarioId}/estadisticas`;
            if (this.fechaInicio && this.fechaFin) {
                url += `?fecha_inicio=${encodeURIComponent(this.fechaInicio)}&fecha_fin=${encodeURIComponent(this.fechaFin)}`;
            }

            pedirJson(url, { exigirOk: true })
                .then(data => {
                    this.loading = false;
                    this.renderizarEstadisticas(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.loading = false;
                    contenedor.innerHTML = '<p class="est-vacio texto-error">'
                        + (error instanceof SesionCerrada ? 'Su sesión se cerró. Recargue la página para volver a entrar.' : 'No se pudieron cargar las estadísticas. Intente de nuevo.')
                        + '</p>';
                });
        },

        // Cifras quietas (sin colores por cifra), tiempos, canal no presencial, por servicio y últimos turnos.
        // Todo texto que viene del servidor pasa por escHtml.
        renderizarEstadisticas(data) {
            const contenedor = document.getElementById('estadisticas-contenido');
            const est = data.estadisticas;
            const canal = data.canal_no_presencial || { cantidad_actividades: 0, detalle: [] };
            const porServicio = Object.entries(data.turnos_por_servicio || {});
            const detalle = data.turnos_detalle || [];
            const e = escHtml;

            const cifra = (n, texto) => '<div class="est-cifra"><b>' + e(n) + '</b><span>' + texto + '</span></div>';
            const tiempo = (valor, texto, nota) => '<div class="est-tiempo"><span class="est-tiempo__texto">' + texto + '</span>'
                + '<b>' + e(valor) + '</b>' + (nota ? '<span class="est-tiempo__nota">' + nota + '</span>' : '') + '</div>';

            let html = '<div class="est-cifras">'
                + cifra(est.total_turnos, 'turnos')
                + cifra(est.turnos_atendidos, 'atendidos')
                + cifra(est.turnos_pendientes, 'pendientes')
                + cifra(est.turnos_cancelados, 'cancelados')
                + '</div>'
                + '<div class="est-tiempos">'
                + tiempo(est.tiempo_promedio_atencion, 'Atención promedio', est.atenciones_atipicas ? 'Sin ' + e(est.atenciones_atipicas) + ' de más de 1 h (quedaron abiertas)' : '')
                + tiempo(est.tiempo_total_atencion, 'Tiempo atendiendo', '')
                + tiempo(est.tiempo_promedio_entre_turnos, 'Entre turnos', est.pausas_largas ? e(est.pausas_largas) + ' pausas de más de 1 h, aparte' : '')
                + '</div>';

            if (canal.cantidad_actividades > 0) {
                html += '<section class="est-seccion"><h3>Canal no presencial</h3>'
                    + '<div class="est-cifras est-cifras--2">'
                    + cifra(canal.cantidad_actividades, canal.cantidad_actividades === 1 ? 'actividad' : 'actividades')
                    + cifra(canal.tiempo_total_horas + ' h', 'en total')
                    + '</div>'
                    + (canal.detalle.length ? '<div class="est-tabla"><table class="tabla-panel"><thead><tr><th scope="col">Actividad</th><th scope="col">Inicio</th><th scope="col">Duración</th></tr></thead><tbody>'
                        + canal.detalle.map(a => '<tr><td>' + e(a.actividad) + '</td><td>' + e(a.inicio) + '</td><td>'
                            + (a.duracion_minutos === null ? '<span class="texto-mudo">Abierta de un día a otro (no se suma)</span>' : e(a.duracion_minutos) + ' min')
                            + '</td></tr>').join('')
                        + '</tbody></table></div>' : '')
                    + '</section>';
            }

            if (porServicio.length) {
                html += '<section class="est-seccion"><h3>Por servicio</h3><div class="est-servicios">'
                    + porServicio.map(([servicio, datos]) => {
                        const total = Number(datos.total) || 0;
                        const atendidos = Number(datos.atendidos) || 0;
                        const ancho = total ? Math.round(atendidos / total * 100) : 0;
                        return '<div class="est-servicio"><span class="est-servicio__nombre" title="' + e(servicio) + '">' + e(servicio) + '</span>'
                            + '<span class="est-servicio__pista" aria-hidden="true"><i style="width:' + ancho + '%"></i></span>'
                            + '<span class="est-servicio__n">' + e(atendidos) + ' de ' + e(total) + '</span></div>';
                    }).join('')
                    + '</div></section>';
            }

            html += '<section class="est-seccion"><h3>Últimos turnos atendidos</h3>'
                + (detalle.length
                    ? '<div class="est-tabla"><table class="tabla-panel"><thead><tr><th scope="col">Turno</th><th scope="col">Servicio</th><th scope="col">Hora</th><th scope="col">Duración</th></tr></thead><tbody>'
                        + detalle.map(t => '<tr><td class="est-codigo">' + e(t.codigo) + '</td><td>' + e(t.servicio) + '</td><td>' + e(t.fecha_atencion) + '</td><td>' + e(t.duracion_minutos) + '</td></tr>').join('')
                        + '</tbody></table></div>'
                    : '<p class="est-vacio">No atendió turnos en este período.</p>')
                + '</section>';

            contenedor.innerHTML = html;
        }
    }
}

</script>

@push('estilos')
<style>
/* A11y (WCAG 2.4.7): quitar el outline SOLO para foco de mouse/touch (no feo al hacer
   click), pero CONSERVAR un indicador claro para foco de teclado vía :focus-visible. */
button:focus:not(:focus-visible),
input:focus:not(:focus-visible),
select:focus:not(:focus-visible),
textarea:focus:not(:focus-visible),
.btn:focus:not(:focus-visible),
[role="button"]:focus:not(:focus-visible),
.cursor-pointer:focus:not(:focus-visible),
label:focus:not(:focus-visible),
input[type="radio"]:focus:not(:focus-visible),
input[type="checkbox"]:focus:not(:focus-visible) {
    outline: none;
    box-shadow: none;
}

/* Indicador visible de foco para navegación por teclado */
button:focus-visible,
input:focus-visible,
select:focus-visible,
textarea:focus-visible,
.btn:focus-visible,
[role="button"]:focus-visible,
.cursor-pointer:focus-visible,
label:focus-visible,
input[type="radio"]:focus-visible,
input[type="checkbox"]:focus-visible {
    outline: 2px solid #064b9e;
    outline-offset: 2px;
}

/* ===== «La sala» (2026-11): módulos vistos desde arriba y la cola al lado (ver DESIGN.md) ===== */
.sala { display: grid; grid-template-columns: minmax(0, 1fr) 20rem; gap: 1.5rem; align-items: start; }
@media (max-width: 1279px) { .sala { grid-template-columns: minmax(0, 1fr); } }
.sala__principal { min-width: 0; display: flex; flex-direction: column; gap: 1.5rem; }

/* Cifras del momento: cuatro piezas en una fila, el número manda y el detalle va debajo */
.sala-cifras { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .625rem; }
@media (max-width: 1023px) { .sala-cifras { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
.sala-cifra {
    margin: 0; min-width: 0; display: flex; flex-direction: column; gap: .1875rem; padding: .6875rem 1rem; border-radius: .875rem;
    background: #fff; box-shadow: var(--roce); font-size: .875rem; color: var(--tinta-suave);
}
.sala-cifra > span { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.sala-cifra__n { font-size: 1.1875rem; font-weight: 650; letter-spacing: -.01em; color: var(--tinta); font-variant-numeric: tabular-nums; }
.sala-cifra__n.is-alerta { color: var(--error); }
.sala-cifra__mas { color: var(--tinta-tenue); font-size: .78125rem; font-variant-numeric: tabular-nums; }

.sala-seccion { min-width: 0; }
.sala-titulo { margin: 0 0 .75rem; font-size: 1rem; font-weight: 650; color: var(--tinta); }
.sala-vacio { margin: 0; padding: 1.25rem; color: var(--tinta-tenue); font-size: .875rem; }

/* Módulos: una tarjeta por módulo con asesor */
.sala-modulos { display: grid; grid-template-columns: repeat(auto-fill, minmax(12.5rem, 1fr)); gap: .75rem; }
.sala-modulo {
    display: flex; flex-direction: column; gap: .375rem; min-height: 8.5rem; padding: .875rem 1rem .75rem; text-align: left;
    background: #fff; border-radius: .875rem; box-shadow: var(--roce); cursor: pointer; font: inherit; color: inherit;
    transition-property: box-shadow, scale; transition-duration: .15s; transition-timing-function: ease-out;
}
.sala-modulo:hover { box-shadow: var(--roce), 0 0 0 1px #c6d7f0; }
.sala-modulo:active { scale: .98; }
.sala-modulo:focus-visible { outline: 2px solid var(--azul); outline-offset: 2px; }
.sala-modulo__cabeza { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
.sala-modulo__num { font-size: 1.5rem; line-height: 1; font-weight: 650; letter-spacing: -.02em; color: var(--azul); font-variant-numeric: tabular-nums; }
.sala-modulo--info .sala-modulo__num { color: var(--tinta-tenue); }
.sala-modulo--alerta .sala-modulo__num { color: var(--alerta-tinta); }
.sala-modulo--neutro .sala-modulo__num { color: #6d7f99; }
.sala-modulo__asesor { font-size: .875rem; font-weight: 600; color: var(--tinta); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.sala-modulo__hoy { font-size: .75rem; color: var(--tinta-tenue); font-variant-numeric: tabular-nums; }
.sala-modulo__pie {
    margin-top: auto; display: flex; align-items: baseline; justify-content: space-between; gap: .5rem;
    padding-top: .5rem; border-top: 1px dashed var(--corte);
}
.sala-modulo__turno { font-size: .9375rem; font-weight: 650; color: var(--tinta); font-variant-numeric: tabular-nums; letter-spacing: .01em; }
.sala-modulo__min { font-size: .8125rem; color: var(--tinta-suave); font-variant-numeric: tabular-nums; }
.sala-modulo__min.is-alerta { color: var(--error); font-weight: 650; }
.sala-modulo__vacio { font-size: .8125rem; color: var(--tinta-tenue); }

.sala-nota { margin: .75rem 0 0; display: flex; flex-wrap: wrap; align-items: center; gap: .375rem; font-size: .8125rem; color: var(--tinta-tenue); }
.sala-nota[hidden] { display: none; }
.sala-nota__titulo { margin-right: .25rem; font-weight: 600; color: var(--tinta-suave); }
.sala-nota__num {
    min-width: 1.75rem; height: 1.75rem; padding: 0 .375rem; display: inline-grid; place-items: center; border-radius: .5rem;
    background: rgba(255, 255, 255, .7); box-shadow: inset 0 0 0 1px var(--linea); font-weight: 600; color: var(--tinta-suave); font-variant-numeric: tabular-nums;
}
.sala-nota__persona {
    padding: .25rem .625rem; border-radius: .5rem; background: #fff; box-shadow: var(--roce); font: inherit; font-weight: 550;
    color: var(--tinta-2); cursor: pointer;
}
.sala-nota__persona:hover { color: var(--azul); }

/* Por servicio: barras de la cola, la espera y quién lo cubre */
.sala-servicios { padding: .5rem 1rem .25rem; }
.sala-servicio {
    display: grid; grid-template-columns: minmax(10rem, 1.4fr) minmax(4rem, 1fr) 3.5rem 5.5rem 6.5rem; align-items: center; gap: 1rem;
    padding: .5625rem 0; border-top: 1px solid var(--linea-suave); font-size: .875rem; font-variant-numeric: tabular-nums;
}
.sala-servicio--cabeza { border-top: 0; padding-bottom: .375rem; font-size: .78125rem; font-weight: 550; color: var(--tinta-tenue); }
.sala-servicio > span:nth-child(n+3) { text-align: right; }
.sala-servicio__nombre { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--tinta); }
.sala-servicio__prio { margin-left: .25rem; font-size: .75rem; font-weight: 650; color: var(--alerta-tinta); }
.sala-servicio__pista { height: .5rem; border-radius: 999px; background: #e9eef5; overflow: hidden; }
.sala-servicio__pista i { display: block; height: 100%; border-radius: 999px; background: var(--azul); }
.sala-servicio__n { font-weight: 650; color: var(--tinta); }
.sala-servicio__espera { color: var(--tinta-suave); }
.sala-servicio__espera.is-alerta { color: var(--error); font-weight: 650; }
.sala-servicio.is-sin-cobertura .sala-servicio__pista i { background: var(--error); }
@media (max-width: 767px) {
    .sala-servicio { grid-template-columns: minmax(0, 1fr) 3rem 4.5rem; }
    .sala-servicio__pista, .sala-servicio > span:nth-child(5), .sala-servicio--cabeza > span:nth-child(2), .sala-servicio--cabeza > span:nth-child(5) { display: none; }
}

/* Cola: la única pieza elevada de la vista */
.sala-cola {
    position: sticky; top: 1.5rem; padding: 1.25rem 1.125rem 1rem; background: #fff; border-radius: 18px;
    filter: var(--filo) drop-shadow(0 16px 22px rgba(15, 39, 71, .09));
}
@media (max-width: 1279px) { .sala-cola { position: static; } }
.sala-cola__titulo { margin: 0; font-size: 1rem; font-weight: 650; color: var(--tinta); }
.sala-cola__sub { margin: .125rem 0 .875rem; font-size: .8125rem; color: var(--tinta-suave); }
.sala-cola__lista { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: .5rem; }
.sala-turno {
    display: grid; grid-template-columns: minmax(0, 1fr) auto; column-gap: .75rem; row-gap: .125rem; align-items: baseline;
    padding: .6875rem .875rem; border-radius: .75rem; background: #f5f8fc;
}
.sala-turno__codigo { font-size: 1.0625rem; font-weight: 650; letter-spacing: .01em; color: var(--tinta); font-variant-numeric: tabular-nums; }
.sala-turno__min { font-size: .875rem; font-weight: 650; color: var(--tinta-2); font-variant-numeric: tabular-nums; }
.sala-turno__servicio { grid-column: 1 / -1; font-size: .78125rem; color: var(--tinta-suave); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.sala-turno__marcas { grid-column: 1 / -1; display: flex; gap: .25rem; margin-top: .25rem; }
.sala-turno__marcas .estado-chip { font-size: .6875rem; padding: .0625rem .4375rem; }
.sala-turno.is-alerta { background: #fff; box-shadow: inset 0 0 0 1px #f1c6c2; }
.sala-turno.is-alerta .sala-turno__min { color: var(--error); }
.sala-cola__vacia { padding: 1rem .25rem; font-size: .875rem; color: var(--tinta-tenue); }
.sala-cola__todos { display: inline-block; margin-top: .875rem; font-size: .875rem; }
.sala-cola__todos[hidden] { display: none; }

@media (prefers-reduced-motion: reduce) { .sala-modulo { transition: none; } .sala-modulo:active { scale: none; } }

/* ===== Modales del Inicio (limpiar sesiones, emergencia, estadísticas del asesor) ===== */
.modal-panel.hidden { display: none; }
/* La entrada, la cabeza y el cierre son los del layout (.modal-panel__caja); la caja recibe el foco al abrir */
.inicio-modal { outline: none; }

/* Opciones: tarjetas con su radio; la elegida en azul */
.opciones { margin: 0; padding: 0; border: 0; min-width: 0; display: flex; flex-direction: column; gap: .5rem; }
.opcion {
    display: flex; align-items: flex-start; gap: .75rem; padding: .8125rem 1rem; border-radius: .75rem; cursor: pointer;
    background: #fff; box-shadow: inset 0 0 0 1px var(--linea);
    transition-property: background-color, box-shadow; transition-duration: .15s; transition-timing-function: ease-out;
}
.opcion:hover { background: #f5f8fc; }
.opcion:has(input:checked) { background: var(--azul-claro); box-shadow: inset 0 0 0 1.5px var(--azul); }
.opcion:has(input:focus-visible) { outline: 2px solid var(--azul); outline-offset: 2px; }
.opcion input { width: 1rem; height: 1rem; margin-top: .1875rem; flex: none; accent-color: var(--azul); }
.opcion input:focus-visible { outline: none; }
.opcion__texto { min-width: 0; display: flex; flex-direction: column; gap: .125rem; }
.opcion__titulo { font-size: .875rem; font-weight: 650; color: var(--tinta); }
.opcion__ayuda { font-size: .8125rem; line-height: 1.45; color: var(--tinta-suave); text-wrap: pretty; }
.opcion__cuidado { font-weight: 600; color: var(--error-texto); }
.opcion-grupo { display: flex; flex-direction: column; gap: .5rem; }
.opcion__extra { padding: 0 0 0 2.75rem; }
.opcion__extra.hidden, .personas.hidden, .personas__elegida.hidden { display: none; }

/* Personas conectadas (limpiar la sesión de una sola) */
.personas { padding-left: 2.75rem; display: flex; flex-direction: column; gap: .5rem; }
.personas__lista {
    max-height: 15rem; overflow-y: auto; overscroll-behavior: contain; display: flex; flex-direction: column;
    border-radius: .75rem; box-shadow: inset 0 0 0 1px var(--linea); padding: .25rem;
}
.persona {
    display: grid; grid-template-columns: auto minmax(0, 1fr) auto; align-items: center; gap: .75rem;
    padding: .5625rem .75rem; border-radius: .5rem; cursor: pointer;
    transition-property: background-color; transition-duration: .15s; transition-timing-function: ease-out;
}
.persona:hover { background: #f5f8fc; }
.persona:has(input:checked) { background: var(--azul-claro); }
.persona:has(input:focus-visible) { outline: 2px solid var(--azul); outline-offset: -2px; }
.persona input { width: 1rem; height: 1rem; accent-color: var(--azul); }
.persona input:focus-visible { outline: none; }
.persona__texto { min-width: 0; display: flex; flex-direction: column; }
.persona__nombre { font-size: .875rem; font-weight: 600; color: var(--tinta); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.persona__detalle { font-size: .75rem; color: var(--tinta-tenue); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.persona__estado { display: flex; flex-direction: column; align-items: flex-end; gap: .1875rem; }
.persona__estado .estado-chip { font-size: .6875rem; padding: .0625rem .4375rem; }
.persona__tiempo { font-size: .75rem; color: var(--tinta-tenue); font-variant-numeric: tabular-nums; white-space: nowrap; }
.personas__vacio { margin: 0; padding: .875rem .75rem; font-size: .8125rem; color: var(--tinta-tenue); }
.personas__elegida { margin: 0; font-size: .8125rem; color: var(--tinta-2); }
.personas__elegida b { color: var(--tinta); }


/* Estadísticas del asesor */
.est-cabeza { min-width: 0; display: flex; flex-direction: column; gap: .125rem; }
.est-cabeza h2 { overflow-wrap: anywhere; }
.est-periodo { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: .75rem; }
.est-fechas { display: flex; flex-wrap: wrap; align-items: flex-end; gap: .5rem; }
.est-fechas .form-campo { width: 9.75rem; }
.est-contenido { display: flex; flex-direction: column; gap: 1.25rem; padding-top: .25rem; }
.est-cifras { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .5rem; }
.est-cifras--2 { grid-template-columns: repeat(2, minmax(0, 1fr)); max-width: 22rem; }
.est-cifra { display: flex; flex-direction: column; gap: .125rem; padding: .75rem .875rem; border-radius: .75rem; background: #f5f8fc; }
.est-cifra b { font-size: 1.375rem; line-height: 1.15; font-weight: 650; letter-spacing: -.01em; color: var(--tinta); font-variant-numeric: tabular-nums; }
.est-cifra span { font-size: .8125rem; color: var(--tinta-suave); }
.est-tiempos { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); border-radius: .75rem; box-shadow: inset 0 0 0 1px var(--linea); }
.est-tiempo { min-width: 0; display: flex; flex-direction: column; gap: .125rem; padding: .75rem .875rem; }
.est-tiempo + .est-tiempo { border-left: 1px solid var(--linea); }
.est-tiempo__texto { font-size: .8125rem; color: var(--tinta-suave); }
.est-tiempo b { font-size: 1rem; font-weight: 650; color: var(--tinta); font-variant-numeric: tabular-nums; }
.est-tiempo__nota { font-size: .75rem; line-height: 1.4; color: var(--tinta-tenue); text-wrap: pretty; }
.est-seccion { display: flex; flex-direction: column; gap: .625rem; }
.est-seccion h3 { margin: 0; font-size: .875rem; font-weight: 650; color: var(--tinta); }
.est-servicios { display: flex; flex-direction: column; }
.est-servicio {
    display: grid; grid-template-columns: minmax(0, 1.3fr) minmax(4rem, 1fr) 4.5rem; align-items: center; gap: .875rem;
    padding: .4375rem 0; border-top: 1px solid var(--linea-suave); font-size: .875rem;
}
.est-servicio:first-child { border-top: 0; }
.est-servicio__nombre { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--tinta); }
.est-servicio__pista { height: .4375rem; border-radius: 999px; background: #e9eef5; overflow: hidden; }
.est-servicio__pista i { display: block; height: 100%; border-radius: 999px; background: var(--azul); }
.est-servicio__n { text-align: right; font-weight: 600; color: var(--tinta-2); font-variant-numeric: tabular-nums; white-space: nowrap; }
.est-tabla { max-height: 16rem; overflow-y: auto; overscroll-behavior: contain; border-radius: .75rem; box-shadow: inset 0 0 0 1px var(--linea); }
.est-tabla .tabla-panel th { position: sticky; top: 0; background: #fff; }
.est-tabla .tabla-panel th, .est-tabla .tabla-panel td { padding-left: .875rem; padding-right: .875rem; }
.est-codigo { font-weight: 650; letter-spacing: .01em; }
.est-vacio { margin: 0; padding: .875rem 0; font-size: .875rem; color: var(--tinta-tenue); }
@media (max-width: 639px) {
    .est-cifras { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .est-tiempos { grid-template-columns: minmax(0, 1fr); }
    .est-tiempo + .est-tiempo { border-left: 0; border-top: 1px solid var(--linea); }
    .est-fechas .form-campo { width: auto; flex: 1 1 8rem; }
    .personas, .opcion__extra { padding-left: 0; }
}

/* Esqueletos: modal de estadísticas del asesor y lista de sesiones del modal de limpieza */
.est-esqueleto { display: flex; flex-direction: column; gap: 1rem; padding-block: .25rem; }
.est-esqueleto__fila { display: grid; gap: .5rem; }
.est-esqueleto__fila--4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
.est-esqueleto__fila--3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
@media (max-width: 639px) {
    .est-esqueleto__fila--4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .est-esqueleto__fila--3 { grid-template-columns: minmax(0, 1fr); }
}
.est-esqueleto__tabla { display: flex; flex-direction: column; gap: .8rem; padding: .85rem .75rem; border-radius: .75rem; box-shadow: inset 0 0 0 1px var(--linea); }
.est-esqueleto__tabla .esqueleto:nth-child(odd) { width: 92%; }
.est-esqueleto__tabla .esqueleto:nth-child(even) { width: 74%; }
.usuario-esqueleto { display: flex; flex-direction: column; gap: .5rem; padding: .75rem; }
</style>
@endpush

@endsection
