@extends('layouts.admin')

@section('title', 'Turnos')

@section('content')
{{-- Turnos de hoy. Primera pintura con los datos que trae la página ($datos) y refresco cada 5 s de
     GET /api/admin/turnos-hoy con los mismos filtros y la misma página (AdminController::turnosHoyDatos). --}}
<div class="turnos-vista max-w-7xl mx-auto space-y-5">
    <h1 class="sr-only">Turnos de hoy</h1>

    <!-- Estados del día: cada mosaico filtra la tabla -->
    <div class="estados" role="group" aria-label="Filtrar por estado">
        @foreach ([['pendiente', 'En espera'], ['llamado', 'En atención'], ['atendido', 'Atendidos'], ['aplazado', 'Aplazados'], ['cancelado', 'Cancelados'], ['', 'Todos']] as [$clave, $rotulo])
            <button type="button" class="estado-tile" data-estado="{{ $clave }}" aria-pressed="false">
                <span class="estado-tile__n" data-conteo="{{ $clave !== '' ? $clave : 'total' }}">{{ $datos['conteos'][$clave !== '' ? $clave : 'total'] }}</span>
                <span class="estado-tile__rotulo"><span class="estado-punto estado-punto--{{ $clave !== '' ? $clave : 'todos' }}" aria-hidden="true"></span>{{ $rotulo }}</span>
            </button>
        @endforeach
    </div>

    <div class="bg-white border border-gray-200 rounded-xl shadow-sm">
        <!-- Filtros: se aplican al escribir o al elegir -->
        <div class="filtros">
            <div class="filtro filtro--buscar">
                <label for="filtro-search" class="filtro__rotulo">Buscar</label>
                <div class="buscador">
                    <svg class="buscador__icono" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 110-15 7.5 7.5 0 010 15z"></path></svg>
                    <input id="filtro-search" type="search" autocomplete="off" placeholder="Turno (C-093), servicio o asesor" class="campo">
                </div>
            </div>
            <div class="filtro">
                <label for="filtro-servicio" class="filtro__rotulo">Servicio</label>
                <select id="filtro-servicio" class="campo">
                    <option value="">Todos</option>
                    @foreach ($servicios as $s)
                        <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filtro">
                <label for="filtro-asesor" class="filtro__rotulo">Asesor</label>
                <select id="filtro-asesor" class="campo">
                    <option value="">Todos</option>
                    @foreach ($asesores as $a)
                        <option value="{{ $a->id }}">{{ $a->nombre_completo }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" id="filtro-limpiar" class="boton-limpiar" hidden>Quitar filtros</button>
        </div>

        <div class="overflow-x-auto">
            <table class="dashboard-table tabla-turnos w-full divide-y divide-gray-200">
                <thead>
                    <tr class="text-gray-600">
                        <th scope="col" class="py-3 px-4 text-left text-xs font-semibold uppercase tracking-wide">Turno</th>
                        <th scope="col" class="py-3 px-4 text-left text-xs font-semibold uppercase tracking-wide">Servicio</th>
                        <th scope="col" class="py-3 px-4 text-left text-xs font-semibold uppercase tracking-wide">Estado</th>
                        <th scope="col" class="py-3 px-4 text-left text-xs font-semibold uppercase tracking-wide">Atiende</th>
                        <th scope="col" class="py-3 px-4 text-left text-xs font-semibold uppercase tracking-wide">Llegó</th>
                        <th scope="col" class="py-3 px-4 text-left text-xs font-semibold uppercase tracking-wide">Espera</th>
                        <th scope="col" class="py-3 px-4 text-left text-xs font-semibold uppercase tracking-wide">Atención</th>
                    </tr>
                </thead>
                <tbody id="turnos-tbody" class="divide-y divide-gray-200 bg-white"></tbody>
            </table>
        </div>

        <nav id="turnos-paginacion" class="paginacion" aria-label="Páginas de turnos"></nav>
    </div>
</div>

<script>
(function () {
    const API = @json(route('api.admin.turnos-hoy'));
    const ESTADOS = {
        pendiente: ['En espera', 'bg-yellow-100 text-yellow-800'],
        llamado: ['En atención', 'bg-blue-100 text-blue-800'],
        atendido: ['Atendido', 'bg-green-100 text-green-800'],
        aplazado: ['Aplazado', 'bg-orange-100 text-orange-800'],
        cancelado: ['Cancelado', 'bg-red-100 text-red-800'],
    };

    const url = new URLSearchParams(location.search);
    const estadoFiltro = { estado: url.get('estado') || '', servicio: url.get('servicio') || '', asesor: url.get('asesor') || '',
                           search: url.get('search') || '', page: parseInt(url.get('page') || '1', 10) || 1 };

    const $ = id => document.getElementById(id);
    const esc = t => String(t ?? '').replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
    const gris = t => '<span class="text-gray-500">' + t + '</span>';

    function fila(t) {
        const [texto, colores] = ESTADOS[t.estado] || [t.estado, 'bg-gray-100 text-gray-800'];
        const obs = t.observaciones ? '<span class="nota" title="' + esc(t.observaciones) + '">' + esc(t.observaciones) + '</span>' : '';
        const atiende = (t.modulo !== null || t.asesor)
            ? (t.modulo !== null ? '<span class="font-medium text-gray-900">Módulo ' + esc(t.modulo) + '</span>' : '')
              + (t.asesor ? '<span class="nota" title="' + esc(t.asesor) + '">' + esc(t.asesor) + '</span>' : '')
            : gris('—');
        let espera = gris('—');
        if (t.espera_min !== null) {
            espera = t.espera_alerta
                ? '<span class="cifra-alerta" title="Pasó el límite de ' + esc(t.espera_limite) + ' min">' + esc(t.espera_min) + ' min</span>'
                : (['pendiente', 'aplazado'].includes(t.estado) ? esc(t.espera_min) + ' min' : gris(esc(t.espera_min) + ' min'));
        }
        const atencion = t.duracion ? esc(t.duracion)
            : (t.en_atencion_min !== null ? '<span class="en-curso">En curso · ' + esc(t.en_atencion_min) + ' min</span>' : gris('—'));

        return '<tr class="hover:bg-gray-50">'
            + '<td class="py-3 px-4 whitespace-nowrap"><span class="codigo">' + esc(t.codigo) + '</span>'
            + (t.prioritario ? ' <span class="dashboard-badge px-2 py-1 rounded-md text-xs font-medium bg-red-100 text-red-800">Prioritario</span>' : '') + '</td>'
            + '<td class="py-3 px-4 text-sm text-gray-900">' + esc(t.servicio || '—') + '</td>'
            + '<td class="py-3 px-4"><span class="dashboard-badge px-2 py-1 rounded-md text-xs font-medium ' + colores + '">' + texto + '</span>' + obs + '</td>'
            + '<td class="py-3 px-4 text-sm celda-atiende">' + atiende + '</td>'
            + '<td class="py-3 px-4 whitespace-nowrap text-sm text-gray-900">' + esc(t.llego || '—') + '</td>'
            + '<td class="py-3 px-4 whitespace-nowrap text-sm text-gray-900">' + espera + '</td>'
            + '<td class="py-3 px-4 whitespace-nowrap text-sm text-gray-900">' + atencion + '</td>'
            + '</tr>';
    }

    function paginas(m) {
        if (!m.total) return '';
        const botones = [];
        const ir = (p, etiqueta, activo, deshabilitado, aria) => botones.push(
            '<button type="button" class="pag' + (activo ? ' pag--activa' : '') + '" data-pagina="' + p + '"'
            + (deshabilitado ? ' disabled' : '') + (activo ? ' aria-current="page"' : '') + (aria ? ' aria-label="' + aria + '"' : '') + '>' + etiqueta + '</button>');
        ir(m.pagina - 1, '‹ Anterior', false, m.pagina <= 1);
        const vistos = new Set([1, m.ultima, m.pagina - 1, m.pagina, m.pagina + 1].filter(p => p >= 1 && p <= m.ultima));
        let previo = 0;
        [...vistos].sort((a, b) => a - b).forEach(p => {
            if (p - previo > 1) botones.push('<span class="pag-hueco">…</span>');
            ir(p, p, p === m.pagina, false, 'Página ' + p);
            previo = p;
        });
        ir(m.pagina + 1, 'Siguiente ›', false, m.pagina >= m.ultima);
        return '<p class="pag-resumen">Mostrando <b>' + m.desde + '–' + m.hasta + '</b> de <b>' + m.total + '</b></p>'
            + (m.ultima > 1 ? '<div class="pag-botones">' + botones.join('') + '</div>' : '');
    }

    function hayFiltros() { return estadoFiltro.estado || estadoFiltro.servicio || estadoFiltro.asesor || estadoFiltro.search; }

    function pintar(d) {
        Object.entries(d.conteos).forEach(([k, n]) => {
            const el = document.querySelector('[data-conteo="' + k + '"]');
            if (el) el.textContent = n;
        });
        document.querySelectorAll('.estado-tile').forEach(b => b.setAttribute('aria-pressed', String(b.dataset.estado === estadoFiltro.estado)));
        const cuerpo = $('turnos-tbody');
        if (d.turnos.length) {
            cuerpo.innerHTML = d.turnos.map(fila).join('');
        } else {
            const motivo = estadoFiltro.search ? 'Ningún turno coincide con «' + esc(estadoFiltro.search) + '».'
                : (hayFiltros() ? 'Ningún turno coincide con los filtros.' : 'Hoy todavía no hay turnos.');
            cuerpo.innerHTML = '<tr><td colspan="7" class="py-8 px-4 text-center text-sm text-gray-500">' + motivo
                + (hayFiltros() ? ' <button type="button" class="enlace-accion" data-limpiar>Quitar filtros</button>' : '') + '</td></tr>';
        }
        $('turnos-paginacion').innerHTML = paginas(d.meta);
        $('filtro-limpiar').hidden = !hayFiltros();
    }

    function consulta() {
        const p = new URLSearchParams();
        Object.entries(estadoFiltro).forEach(([k, v]) => { if (v && !(k === 'page' && v === 1)) p.set(k, v); });
        return p;
    }

    let pidiendo = null;
    function actualizar() {
        const p = consulta();
        history.replaceState(null, '', location.pathname + (p.toString() ? '?' + p : ''));
        if (pidiendo) pidiendo.abort();
        pidiendo = new AbortController();
        return fetch(API + '?' + p, { headers: { Accept: 'application/json' }, cache: 'no-store', signal: pidiendo.signal })
            .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(d => {
                // Si la página quedó fuera de rango (p. ej. tras filtrar), se vuelve a la última que existe.
                if (d.meta.total && estadoFiltro.page > d.meta.ultima) { estadoFiltro.page = d.meta.ultima; return actualizar(); }
                pintar(d);
            })
            .catch(e => { if (e.name !== 'AbortError') console.warn('No se pudo actualizar Turnos:', e); });
    }

    function cambiar(k, v) { estadoFiltro[k] = v; estadoFiltro.page = 1; actualizar(); }

    // Controles
    $('filtro-search').value = estadoFiltro.search;
    $('filtro-servicio').value = estadoFiltro.servicio;
    $('filtro-asesor').value = estadoFiltro.asesor;
    let espera = null;
    // Si se escribe un código de turno (C-093, c93, K 16), se busca en todos los estados: se quiere ESE turno.
    const ES_CODIGO = /^[A-Za-z][A-Za-z\-]{0,9}?\s*-?\s*\d{1,4}$/;
    $('filtro-search').addEventListener('input', e => {
        clearTimeout(espera);
        espera = setTimeout(() => {
            const v = e.target.value.trim();
            if (ES_CODIGO.test(v)) estadoFiltro.estado = '';
            cambiar('search', v);
        }, 300);
    });
    $('filtro-servicio').addEventListener('change', e => cambiar('servicio', e.target.value));
    $('filtro-asesor').addEventListener('change', e => cambiar('asesor', e.target.value));
    document.querySelectorAll('.estado-tile').forEach(b => b.addEventListener('click', () => cambiar('estado', b.dataset.estado)));
    function limpiar() {
        Object.assign(estadoFiltro, { estado: '', servicio: '', asesor: '', search: '', page: 1 });
        $('filtro-search').value = ''; $('filtro-servicio').value = ''; $('filtro-asesor').value = '';
        actualizar();
    }
    $('filtro-limpiar').addEventListener('click', limpiar);
    document.querySelector('.turnos-vista').addEventListener('click', e => {
        const pag = e.target.closest('[data-pagina]');
        if (pag && !pag.disabled) { estadoFiltro.page = parseInt(pag.dataset.pagina, 10); actualizar(); }
        if (e.target.closest('[data-limpiar]')) limpiar();
    });

    // Primera pintura con los datos de la página; luego, refresco cada 5 s con la pestaña visible.
    pintar(@js($datos));
    setInterval(() => { if (!document.hidden) actualizar(); }, 5000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) actualizar(); });
})();
</script>

<style>
.turnos-vista { font-variant-numeric: tabular-nums; }

/* Mosaicos de estado: el mismo material que las tarjetas del Inicio; el elegido lleva el azul institucional. */
.estados { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: .75rem; }
@media (max-width: 1023px) { .estados { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
@media (max-width: 639px) { .estados { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
.estado-tile {
    display: flex; flex-direction: column; align-items: flex-start; gap: .25rem;
    background: #ffffff; border: 1px solid transparent; border-radius: .75rem; padding: .75rem 1rem;
    box-shadow: 0 1px 2px rgba(16, 24, 40, .06); cursor: pointer; text-align: left;
    transition: box-shadow .15s ease, border-color .15s ease, background-color .15s ease;
}
.estado-tile:hover { border-color: #cdd9ec; box-shadow: 0 6px 18px -10px rgba(16, 24, 40, .18); }
.estado-tile[aria-pressed="true"] { background: #e6f1fb; border-color: #064b9e; }
.estado-tile:focus-visible { outline: 2px solid #064b9e; outline-offset: 2px; }
.estado-tile__n { font-size: 1.5rem; font-weight: 700; line-height: 1.1; color: #0f2547; }
.estado-tile__rotulo { display: flex; align-items: center; gap: .4rem; font-size: .75rem; font-weight: 600; color: #4b5563; }
.estado-punto { width: .5rem; height: .5rem; border-radius: 9999px; flex-shrink: 0; }
.estado-punto--pendiente { background: #d08700; } .estado-punto--llamado { background: #155dfc; }
.estado-punto--atendido { background: #00a63e; } .estado-punto--aplazado { background: #f54900; }
.estado-punto--cancelado { background: #e7000b; } .estado-punto--todos { background: #072449; }

/* Filtros */
.filtros { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1.2fr) minmax(0, 1.2fr) auto; gap: .75rem; align-items: end; padding: 1rem; }
@media (max-width: 1023px) { .filtros { grid-template-columns: 1fr 1fr; } .filtro--buscar { grid-column: 1 / -1; } }
.filtro__rotulo { display: block; font-size: .75rem; font-weight: 600; color: #374151; margin-bottom: .25rem; }
.campo {
    width: 100%; height: 2.5rem; padding: 0 .75rem; font-size: .875rem; color: #111827; background: #ffffff;
    border: 1px solid #d1d5db; border-radius: .5rem;
}
.campo:focus { outline: none; border-color: #064b9e; box-shadow: 0 0 0 3px rgba(6, 75, 158, .18); }
.buscador { position: relative; }
.buscador .campo { padding-left: 2.25rem; }
.buscador__icono { position: absolute; left: .75rem; top: 50%; width: 1rem; height: 1rem; transform: translateY(-50%); color: #6b7280; pointer-events: none; }
.boton-limpiar { height: 2.5rem; padding: 0 .9rem; border-radius: .5rem; font-size: .875rem; font-weight: 500; color: #064b9e; background: #e6f1fb; cursor: pointer; }
.boton-limpiar:hover { background: #d6e6f8; }
.boton-limpiar:focus-visible { outline: 2px solid #064b9e; outline-offset: 2px; }

/* Tabla */
.tabla-turnos thead tr { background: #f6f8fc; }
.tabla-turnos th { color: #5f6b80; }
.codigo { font-weight: 700; color: #111827; letter-spacing: .01em; }
.nota { display: block; max-width: 16rem; margin-top: .25rem; font-size: .75rem; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.celda-atiende { min-width: 9rem; }
.cifra-alerta { color: var(--color-red-700, #c10007); font-weight: 600; }
.en-curso { color: #064b9e; font-weight: 500; }
.enlace-accion { color: #064b9e; font-weight: 500; }
.enlace-accion:hover { text-decoration: underline; }

/* Paginación */
.paginacion { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; padding: .875rem 1rem; border-top: 1px solid #e5e7eb; }
.pag-resumen { font-size: .875rem; color: #4b5563; }
.pag-resumen b { color: #111827; font-weight: 600; }
.pag-botones { display: flex; align-items: center; gap: .25rem; }
.pag { min-width: 2.25rem; height: 2.25rem; padding: 0 .6rem; border-radius: .5rem; font-size: .875rem; color: #374151; background: transparent; cursor: pointer; }
.pag:hover:not(:disabled) { background: #eef1f6; }
.pag--activa { background: #064b9e; color: #ffffff; font-weight: 600; }
.pag--activa:hover:not(:disabled) { background: #053d7a; }
.pag:disabled { color: #9ca3af; cursor: default; }
.pag:focus-visible { outline: 2px solid #064b9e; outline-offset: 2px; }
.pag-hueco { padding: 0 .25rem; color: #9ca3af; }

@media (min-width: 768px) and (max-height: 799px) {
    .tabla-turnos th, .tabla-turnos td { padding-top: .5rem; padding-bottom: .5rem; }
    .estado-tile { padding: .6rem .9rem; }
}
</style>
@endsection
