@extends('layouts.admin')

@section('title', 'Turnos')

@section('content')
{{-- Turnos de hoy. Primera pintura con los datos que trae la página ($datos) y refresco cada 5 s de
     GET /api/admin/turnos-hoy con los mismos filtros y la misma página (AdminController::turnosHoyDatos).
     Vista de solo lectura: no hay acciones por turno ni ventanas (modales). --}}
<div class="turnos-vista">

    {{-- Barra de la vista: estados del día a la izquierda (cada pastilla filtra la tabla; el tono del punto es el
         mismo del estado en la tabla) y el buscador a la derecha. --}}
    @php $estadoActual = $datos['filtros']['estado'] ?? ''; @endphp
    <div class="barra-vista">
        <div class="estados filtros-rapidos" role="group" aria-label="Filtrar por estado">
            @foreach ([['', 'Todos', null], ['pendiente', 'En espera', 'info'], ['llamado', 'En atención', 'ok'], ['atendido', 'Atendidos', 'neutro'], ['aplazado', 'Aplazados', 'alerta'], ['cancelado', 'Cancelados', 'error']] as [$clave, $rotulo, $tono])
                <button type="button" class="estado-tile filtro-rapido" data-estado="{{ $clave }}" aria-pressed="{{ $estadoActual === $clave ? 'true' : 'false' }}">
                    @if ($tono)<span class="punto punto--{{ $tono }}" aria-hidden="true"></span>@endif
                    <span>{{ $rotulo }}</span>
                    <span class="filtro-rapido__n" data-conteo="{{ $clave !== '' ? $clave : 'total' }}">{{ $datos['conteos'][$clave !== '' ? $clave : 'total'] }}</span>
                </button>
            @endforeach
        </div>
        <div class="buscador">
            <svg class="buscador__icono" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15z"/></svg>
            <input id="filtro-search" type="search" autocomplete="off" placeholder="Turno (C-093), servicio o asesor" aria-label="Buscar turno" class="campo">
        </div>
    </div>

    <section class="superficie turnos-panel" aria-label="Turnos de hoy">
        {{-- Filtros: se aplican al elegir. «Quitar filtros» aparece al final de esta fila, a la derecha:
             así no empuja el buscador ni mueve los campos mientras se usan. --}}
        <div class="filtros">
            <div class="filtro">
                <label for="filtro-servicio" class="rotulo-barra">Servicio</label>
                <select id="filtro-servicio" class="campo">
                    <option value="">Todos los servicios</option>
                    @foreach ($servicios as $s)
                        <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filtro">
                <label for="filtro-asesor" class="rotulo-barra">Asesor</label>
                <select id="filtro-asesor" class="campo">
                    <option value="">Todos los asesores</option>
                    @foreach ($asesores as $a)
                        <option value="{{ $a->id }}">{{ $a->nombre_completo }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" id="filtro-limpiar" class="boton-limpiar" hidden>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                Quitar filtros
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="tabla-panel tabla-turnos">
                {{-- Anchos fijos para el código, el estado y las cifras; el servicio y quién atiende se reparten el resto y se recortan con «…» --}}
                <colgroup>
                    <col class="col-turno"><col class="col-servicio"><col class="col-estado"><col><col class="col-llego"><col class="col-espera"><col class="col-atencion">
                </colgroup>
                <thead>
                    <tr>
                        <th scope="col">Turno</th>
                        <th scope="col">Servicio</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Atiende</th>
                        <th scope="col">Llegó</th>
                        <th scope="col">Espera</th>
                        <th scope="col">Atención</th>
                    </tr>
                </thead>
                <tbody id="turnos-tbody"></tbody>
            </table>
        </div>

        <nav id="turnos-paginacion" class="paginacion" aria-label="Páginas de turnos"></nav>
    </section>
</div>

<script>
(function () {
    const API = @json(route('api.admin.turnos-hoy'));
    // Estado del turno: palabra y tono del estado-chip (el mismo tono del punto en las pastillas de arriba)
    const ESTADOS = {
        pendiente: ['En espera', 'info'],
        llamado: ['En atención', 'ok'],
        atendido: ['Atendido', 'neutro'],
        aplazado: ['Aplazado', 'alerta'],
        cancelado: ['Cancelado', 'error'],
    };
    const ICONO = {
        anterior: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>',
        siguiente: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>',
    };

    const url = new URLSearchParams(location.search);
    let porPagina = 25;     // se ajusta a las filas que caben en la pantalla (filasQueCaben)
    let altoFila = 41;      // alto medido de una fila: los esqueletos usan el mismo y la tabla no salta
    const estadoFiltro = { estado: url.get('estado') || '', servicio: url.get('servicio') || '', asesor: url.get('asesor') || '',
                           search: url.get('search') || '', page: parseInt(url.get('page') || '1', 10) || 1 };

    const $ = id => document.getElementById(id);
    const esc = t => String(t ?? '').replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
    const mudo = t => '<span class="texto-mudo">' + t + '</span>';

    // Cada fila ocupa una sola línea (módulo y asesor juntos): todas miden lo mismo y la paginación cabe en la pantalla.
    // Solo un turno transferido lleva una segunda línea, bajo su servicio, con la nota (observaciones: «Transferido a/desde …»).
    function fila(t) {
        const [texto, tono] = ESTADOS[t.estado] || [t.estado, 'neutro'];
        const obs = t.observaciones
            ? '<span class="nota" title="' + esc(t.observaciones) + '">' + esc(t.observaciones) + '</span>'
            : '';
        const atiende = (t.modulo !== null || t.asesor)
            ? '<span class="atiende">'
              + (t.modulo !== null ? '<span class="atiende__modulo">Módulo ' + esc(t.modulo) + '</span>' : '')
              + (t.modulo !== null && t.asesor ? '<span class="atiende__sep" aria-hidden="true">·</span>' : '')
              + (t.asesor ? '<span class="atiende__asesor" title="' + esc(t.asesor) + '">' + esc(t.asesor) + '</span>' : '')
              + '</span>'
            : mudo('—');
        let espera = mudo('—');
        if (t.espera_min !== null) {
            const min = esc(t.espera_min) + ' min';
            if (t.espera_alerta) {
                // Espera larga: la cifra en rojo y, al lado, el motivo escrito (no solo el color)
                espera = '<span class="espera espera--alerta" title="Pasó el límite de ' + esc(t.espera_limite) + ' min">' + min
                    + '<span class="espera__motivo">límite ' + esc(t.espera_limite) + ' min</span></span>';
            } else {
                espera = ['pendiente', 'aplazado'].includes(t.estado) ? '<span class="espera">' + min + '</span>' : mudo(min);
            }
        }
        const atencion = t.duracion ? esc(t.duracion)
            : (t.en_atencion_min !== null ? '<span class="en-curso">En curso · ' + esc(t.en_atencion_min) + ' min</span>' : mudo('—'));

        return '<tr>'
            + '<td class="celda-turno"><span class="turno"><span class="codigo">' + esc(t.codigo) + '</span>'
            + (t.prioritario ? '<span class="estado-chip estado-chip--alerta estado-chip--chico">Prioritario</span>' : '') + '</span></td>'
            + '<td class="celda-servicio"><span class="servicio">'
            + (t.servicio ? '<span class="servicio__nombre" title="' + esc(t.servicio) + '">' + esc(t.servicio) + '</span>' : mudo('—')) + obs + '</span></td>'
            + '<td class="celda-estado"><span class="estado-chip estado-chip--' + tono + '">' + esc(texto) + '</span></td>'
            + '<td class="celda-atiende">' + atiende + '</td>'
            + '<td class="celda-cifra">' + (t.llego ? esc(t.llego) : mudo('—')) + '</td>'
            + '<td class="celda-cifra">' + espera + '</td>'
            + '<td class="celda-cifra">' + atencion + '</td>'
            + '</tr>';
    }

    function paginas(m) {
        if (!m.total) return '';
        const botones = [];
        const ir = (p, etiqueta, activo, deshabilitado, aria, clase) => botones.push(
            '<button type="button" class="pag' + (activo ? ' pag--activa' : '') + (clase ? ' ' + clase : '') + '" data-pagina="' + p + '"'
            + (deshabilitado ? ' disabled' : '') + (activo ? ' aria-current="page"' : '') + (aria ? ' aria-label="' + aria + '"' : '') + '>' + etiqueta + '</button>');
        ir(m.pagina - 1, ICONO.anterior + '<span>Anterior</span>', false, m.pagina <= 1, '', 'pag--paso');
        const vistos = new Set([1, m.ultima, m.pagina - 1, m.pagina, m.pagina + 1].filter(p => p >= 1 && p <= m.ultima));
        let previo = 0;
        [...vistos].sort((a, b) => a - b).forEach(p => {
            if (p - previo > 1) botones.push('<span class="pag-hueco" aria-hidden="true">…</span>');
            ir(p, p, p === m.pagina, false, 'Página ' + p);
            previo = p;
        });
        ir(m.pagina + 1, '<span>Siguiente</span>' + ICONO.siguiente, false, m.pagina >= m.ultima, '', 'pag--paso');
        return '<p class="pag-resumen">Mostrando <b>' + m.desde + '–' + m.hasta + '</b> de <b>' + m.total + '</b></p>'
            + (m.ultima > 1 ? '<div class="pag-botones">' + botones.join('') + '</div>' : '');
    }

    function hayFiltros() { return estadoFiltro.estado || estadoFiltro.servicio || estadoFiltro.asesor || estadoFiltro.search; }

    // Vacío o error de red: la pieza .vacio del layout a todo el ancho de la tabla, con su salida si aplica
    const filaAviso = (titulo, texto, accion) => '<tr class="fila-aviso"><td colspan="7"><div class="vacio">'
        + '<p class="vacio__titulo">' + titulo + '</p><p class="vacio__texto">' + texto + '</p>' + (accion || '') + '</div></td></tr>';

    // Forma de las filas mientras llega la página pedida: mismas columnas y mismo alto que las reales.
    function esqueletos() {
        const anchos = i => ['3.5rem', ['9rem', '7rem', '10.5rem', '8rem', '6.5rem'][i % 5], '5.25rem',
                             ['11rem', '8rem', '12.5rem', '9.5rem'][i % 4], '2.75rem', '3.25rem', '3.5rem'];
        $('turnos-tbody').innerHTML = Array.from({ length: porPagina }, (_, i) =>
            '<tr class="fila-esqueleto" aria-hidden="true" style="height:' + altoFila + 'px">'
            + anchos(i).map((w, j) => '<td><span class="esqueleto' + (j === 2 ? ' esqueleto--etiqueta' : '') + '" style="width:' + w + '"></span></td>').join('')
            + '</tr>').join('');
        document.querySelector('.tabla-turnos').setAttribute('aria-busy', 'true');
    }

    function pintar(d) {
        document.querySelector('.tabla-turnos').removeAttribute('aria-busy');
        Object.entries(d.conteos).forEach(([k, n]) => {
            const el = document.querySelector('[data-conteo="' + k + '"]');
            if (el) el.textContent = n;
        });
        document.querySelectorAll('.estado-tile').forEach(b => b.setAttribute('aria-pressed', String(b.dataset.estado === estadoFiltro.estado)));
        const cuerpo = $('turnos-tbody');
        if (d.turnos.length) {
            cuerpo.innerHTML = d.turnos.map(fila).join('');
        } else {
            const otros = estadoFiltro.estado || estadoFiltro.servicio || estadoFiltro.asesor;
            const [titulo, motivo] = estadoFiltro.search
                ? ['Ningún turno coincide', 'No hay turnos de hoy que coincidan con «' + esc(estadoFiltro.search) + '»' + (otros ? ' y los demás filtros.' : '.')]
                : (hayFiltros() ? ['Ningún turno coincide', 'No hay turnos de hoy con los filtros elegidos.']
                                : ['Hoy todavía no hay turnos', 'Aparecerán aquí en cuanto se saque el primero; la lista se actualiza sola.']);
            cuerpo.innerHTML = filaAviso(titulo, motivo, hayFiltros() ? '<button type="button" class="enlace-panel" data-limpiar>Quitar filtros</button>' : '');
        }
        $('turnos-paginacion').innerHTML = paginas(d.meta);
        ultimaMeta = d.meta;
        $('filtro-limpiar').hidden = !hayFiltros();
    }

    function consulta() {
        const p = new URLSearchParams();
        Object.entries(estadoFiltro).forEach(([k, v]) => { if (v && !(k === 'page' && v === 1)) p.set(k, v); });
        return p;
    }

    let pidiendo = null, esperaEsqueleto = null, ultimaMeta = null;
    function actualizar(opciones = {}) {
        const p = consulta();
        history.replaceState(null, '', location.pathname + (p.toString() ? '?' + p : ''));
        if (pidiendo) pidiendo.abort();
        pidiendo = new AbortController();
        // Al paginar o filtrar, si el servidor tarda, la tabla muestra la forma de las filas y no datos que ya no aplican.
        clearTimeout(esperaEsqueleto);
        if (opciones.esqueleto) esperaEsqueleto = setTimeout(esqueletos, 150);
        p.set('per_page', porPagina);
        return fetch(API + '?' + p, { headers: { Accept: 'application/json' }, cache: 'no-store', signal: pidiendo.signal })
            .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(d => {
                // Si la página quedó fuera de rango (p. ej. tras filtrar), se vuelve a la última que existe.
                if (d.meta.total && estadoFiltro.page > d.meta.ultima) { estadoFiltro.page = d.meta.ultima; return actualizar(opciones); }
                clearTimeout(esperaEsqueleto);
                pintar(d);
            })
            .catch(e => {
                if (e.name === 'AbortError') return;
                clearTimeout(esperaEsqueleto);
                console.warn('No se pudo actualizar Turnos:', e);
                // Si se pidió otra vista (filtro o página) y no llegó, nada de lo de antes queda a la vista:
                // pastillas, «Quitar filtros» y paginación quedan como lo pedido y el aviso ocupa la tabla.
                if (opciones.esqueleto || $('turnos-tbody').querySelector('.fila-esqueleto')) {
                    document.querySelector('.tabla-turnos').removeAttribute('aria-busy');
                    document.querySelectorAll('.estado-tile').forEach(b => b.setAttribute('aria-pressed', String(b.dataset.estado === estadoFiltro.estado)));
                    $('turnos-tbody').innerHTML = filaAviso('No se pudo cargar la lista', 'Se reintentará en unos segundos.');
                    $('turnos-paginacion').innerHTML = '';
                    $('filtro-limpiar').hidden = !hayFiltros();
                }
            });
    }

    function cambiar(k, v) { estadoFiltro[k] = v; estadoFiltro.page = 1; actualizar({ esqueleto: true }); }

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
        actualizar({ esqueleto: true });
    }
    $('filtro-limpiar').addEventListener('click', limpiar);
    document.querySelector('.turnos-vista').addEventListener('click', e => {
        const pag = e.target.closest('[data-pagina]');
        if (pag && !pag.disabled) {
            estadoFiltro.page = parseInt(pag.dataset.pagina, 10);
            if (ultimaMeta) {   // la página pedida queda marcada ya, sin esperar al servidor
                const desde = (estadoFiltro.page - 1) * porPagina + 1;
                $('turnos-paginacion').innerHTML = paginas({ ...ultimaMeta, pagina: estadoFiltro.page, desde, hasta: Math.min(ultimaMeta.total, desde + porPagina - 1) });
            }
            actualizar({ esqueleto: true });
        }
        if (e.target.closest('[data-limpiar]')) limpiar();
    });

    // Cuántas filas caben entre el encabezado de la tabla y el borde de la pantalla, dejando la paginación a la vista.
    function filasQueCaben() {
        const tabla = document.querySelector('.tabla-turnos');
        const fila = tabla.querySelector('tbody tr');
        const alto = fila && fila.cells.length > 1 ? fila.getBoundingClientRect().height : 41;
        altoFila = alto;
        const inicio = tabla.querySelector('thead').getBoundingClientRect().bottom + window.scrollY;
        const pie = $('turnos-paginacion').getBoundingClientRect().height || 60;
        return Math.max(5, Math.min(50, Math.floor((window.innerHeight - inicio - pie - 24) / alto)));
    }
    function ajustarPagina() {
        const n = filasQueCaben();
        if (n === porPagina) return;
        const primero = (estadoFiltro.page - 1) * porPagina;          // conserva el primer turno visible
        porPagina = n;
        estadoFiltro.page = Math.floor(primero / n) + 1;
        const filas = $('turnos-tbody').rows;                            // sin salto: se recortan ya
        while (filas.length > n && filas[filas.length - 1].cells.length > 1) filas[filas.length - 1].remove();
        actualizar();
    }
    let redimension = null;
    window.addEventListener('resize', () => { clearTimeout(redimension); redimension = setTimeout(ajustarPagina, 250); });

    // Primera pintura con los datos de la página; luego, refresco cada 5 s con la pestaña visible.
    pintar(@js($datos));
    // Se mide con la página ya armada y, al final, con las fuentes cargadas.
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ajustarPagina); else ajustarPagina();
    window.addEventListener('load', ajustarPagina);
    setInterval(() => { if (!document.hidden) actualizar(); }, 5000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) actualizar(); });
})();
</script>

@push('estilos')
<style>
/* ===== Turnos de hoy (2026-11, «La sala»): pastillas de estado, filtros con su etiqueta y una tabla de una línea
   por turno. Todo plano con roce; sin pieza elevada (la tabla es el contenido). Ver DESIGN.md. ===== */
.turnos-vista { display: flex; flex-direction: column; gap: 1rem; min-width: 0; font-variant-numeric: tabular-nums; }

/* Pastillas de estado: la pieza .filtro-rapido del layout con el .punto del tono de cada estado (también elegida) */
.estado-tile { white-space: nowrap; }

/* Filtros del panel: cada campo con su rótulo escrito a la izquierda (.rotulo-barra, DESIGN.md: Etiqueta Visible);
   no se estiran a lo ancho y «Quitar filtros» queda al final, a la derecha */
.filtros { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem 1.25rem; padding: .875rem 1rem .5rem; }
.filtro { position: relative; display: flex; align-items: center; gap: .625rem; min-width: 0; flex: 0 1 20rem; }
.filtro > .campo { flex: 1 1 auto; min-width: 0; }

.boton-limpiar {
    margin-left: auto; display: inline-flex; align-items: center; gap: .375rem; height: 2.5rem; padding: 0 .75rem; border-radius: .625rem;
    font-size: .875rem; font-weight: 600; color: var(--tinta-2); background: transparent; cursor: pointer; white-space: nowrap;
    transition-property: background-color, color; transition-duration: .15s; transition-timing-function: ease-out;
}
.boton-limpiar svg { width: 16px; height: 16px; color: var(--icono); }
.boton-limpiar:hover { background: #eef3fa; color: var(--azul); }
.boton-limpiar:hover svg { color: var(--azul); }
.boton-limpiar:focus-visible { outline: 2px solid var(--azul); outline-offset: 2px; }
.boton-limpiar[hidden] { display: none; }

/* Tabla: una línea por turno; el código manda */
.tabla-turnos { table-layout: fixed; min-width: 60rem; }
.col-turno { width: 11.25rem; } .col-servicio { width: 16.5%; } .col-estado { width: 8.25rem; }
.col-llego { width: 7rem; } .col-espera { width: 10.5rem; } .col-atencion { width: 10.25rem; }
.tabla-turnos th, .tabla-turnos td { overflow: hidden; text-overflow: ellipsis; padding-left: .875rem; padding-right: .875rem; }
.tabla-turnos th:first-child, .tabla-turnos td:first-child { padding-left: 1rem; }
.tabla-turnos th:last-child, .tabla-turnos td:last-child { padding-right: 1rem; }
.tabla-turnos td { white-space: nowrap; padding-top: .5625rem; padding-bottom: .5625rem; }
.tabla-turnos .fila-esqueleto td { padding-top: 0; padding-bottom: 0; }
.tabla-turnos tbody tr.fila-esqueleto:hover > td, .tabla-turnos tbody tr.fila-aviso:hover > td { background: transparent; }
/* Los envoltorios en línea van al medio: alineados a la base, la fila crecía 2 px y dejaba de caber una */
.turno, .servicio, .celda-estado .estado-chip, .atiende, .espera--alerta { vertical-align: middle; }
.turno { display: inline-flex; flex-wrap: wrap; align-items: center; gap: .125rem .5rem; max-width: 100%; }
.codigo { font-size: .9375rem; font-weight: 650; letter-spacing: .01em; color: var(--tinta); font-variant-numeric: tabular-nums; }
.servicio { display: inline-flex; flex-direction: column; gap: 0; max-width: 100%; }
.servicio__nombre { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--tinta); }
.nota {
    display: block; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    font-size: .75rem; line-height: 1.35; color: var(--tinta-suave);
}

.atiende { display: inline-flex; align-items: baseline; gap: .375rem; max-width: 100%; }
.atiende__modulo { flex: none; font-weight: 600; color: var(--tinta); }
.atiende__sep { flex: none; color: var(--corte); }
.atiende__asesor { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--tinta-suave); }

.celda-cifra { font-variant-numeric: tabular-nums; }
.espera { font-weight: 550; color: var(--tinta); }
.espera--alerta { display: inline-flex; align-items: baseline; gap: .4375rem; color: var(--error); font-weight: 650; }
.espera__motivo { font-size: .75rem; font-weight: 500; color: var(--error-texto); }
.en-curso { font-weight: 550; color: var(--ok); }

/* Vacío y error de red: la pieza .vacio del layout pone el aire; la celda no suma relleno
   (0,2,1: le gana al relleno de la primera y la última celda de arriba) */
.tabla-turnos .fila-aviso td { padding: 0; white-space: normal; }

/* Paginación */
.paginacion {
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem;
    padding: .625rem 1rem; border-top: 1px solid var(--linea);
}
.paginacion:empty { display: none; }
.pag-resumen { margin: 0; font-size: .8125rem; color: var(--tinta-suave); }
.pag-resumen b { color: var(--tinta); font-weight: 600; }
.pag-botones { display: flex; align-items: center; gap: .25rem; }
.pag {
    position: relative; display: inline-flex; align-items: center; justify-content: center; gap: .25rem; min-width: 2.5rem; height: 2.5rem; padding: 0 .625rem;
    border-radius: .625rem; font-size: .875rem; font-weight: 550; color: var(--tinta-2); background: transparent; cursor: pointer;
    font-variant-numeric: tabular-nums; transition-property: background-color, color; transition-duration: .15s; transition-timing-function: ease-out;
}
.pag svg { width: 16px; height: 16px; flex: none; }
.pag--paso { padding: 0 .75rem; }
.pag:hover:not(:disabled) { background: #eef3fa; color: var(--azul); }
.pag--activa, .pag--activa:hover:not(:disabled) { background: var(--azul-claro); color: var(--azul); font-weight: 650; }
.pag:disabled { color: var(--tinta-tenue); opacity: .55; cursor: default; }
.pag:focus-visible { outline: 2px solid var(--azul); outline-offset: 2px; }
.pag-hueco { padding: 0 .25rem; color: var(--tinta-tenue); }
@media (max-width: 639px) { .pag--paso span { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0, 0, 0, 0); } }

@media (prefers-reduced-motion: reduce) { .boton-limpiar, .pag { transition: none; } }
</style>
@endpush
@endsection
