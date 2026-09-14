@extends('layouts.admin')

@section('title', 'Reportes')

@section('content')
{{-- Informe de turnos en una sola vista: periodo, qué incluir y formato. Se descarga con fetch para saber de verdad
     cuándo terminó (o por qué falló). ReportesController::generarReporte arma el Excel o el PDF. --}}
<div class="reportes-vista max-w-7xl mx-auto" x-data="informeTurnos(@js($secciones), @js($asesores), @js($inicial))">
    <h1 class="sr-only">Reportes</h1>

    <form class="informe-rejilla" @submit.prevent="generar()" novalidate>
        <div class="informe-columna">
            <!-- Periodo -->
            <section class="superficie bloque-informe">
                <h2 class="bloque-informe__titulo">Periodo</h2>
                <div class="filtros-rapidos atajos" role="group" aria-label="Atajos de periodo">
                    @foreach (['hoy' => 'Hoy', 'ayer' => 'Ayer', 'semana' => 'Esta semana', 'semana_pasada' => 'Semana pasada', 'mes' => 'Este mes', 'mes_anterior' => 'Mes anterior'] as $clave => $rotulo)
                        <button type="button" class="filtro-rapido" :aria-pressed="(atajo === '{{ $clave }}').toString()" @click="elegirAtajo('{{ $clave }}')">{{ $rotulo }}</button>
                    @endforeach
                </div>
                <div class="form-panel__fila">
                    <label class="form-campo">Desde
                        <input type="date" class="campo" x-model="desde" :max="hoy()" @input="atajo = atajoDe()" required>
                    </label>
                    <label class="form-campo">Hasta
                        <input type="date" class="campo" x-model="hasta" :max="hoy()" :min="desde" @input="atajo = atajoDe()" required>
                    </label>
                </div>
                <p class="form-ayuda" :class="{ 'form-error': !periodoValido() }" x-text="textoPeriodo()"></p>
            </section>

            <!-- Formato -->
            <section class="superficie bloque-informe">
                <h2 class="bloque-informe__titulo">Formato</h2>
                <div class="segmentado" role="radiogroup" aria-label="Formato del archivo">
                    <button type="button" role="radio" :aria-checked="(formato === 'excel').toString()" @click="formato = 'excel'">Excel</button>
                    <button type="button" role="radio" :aria-checked="(formato === 'pdf').toString()" @click="formato = 'pdf'">PDF</button>
                </div>
                <p class="form-ayuda" x-show="formato === 'excel'">Hojas: Resumen, Detalle de turnos, Por servicio, Por asesor y una hoja por asesor con sus turnos.</p>
                <p class="form-ayuda" x-show="formato === 'pdf'" x-cloak>Resumen, tablas por servicio y por asesor, y los 50 turnos más recientes (el Excel trae todos).</p>
            </section>

            <!-- Resumen y acción: siempre a la vista -->
            <div class="superficie bloque-informe bloque-informe--accion">
                <p class="resumen-informe">
                    <b>Informe de turnos</b> · {{ config('panel.unidad_nombre', 'Turnero') }}<br>
                    <span x-text="textoPeriodoCorto()"></span> · <span x-text="textoAlcance()"></span> · <span x-text="formato === 'excel' ? 'Excel' : 'PDF'"></span>
                </p>
                <button type="submit" class="btn-primario btn-generar" :disabled="!listo() || trabajando">
                    <span x-text="trabajando ? 'Generando…' : 'Generar informe'"></span>
                </button>
                <p class="estado-informe" :class="'estado-informe--' + (estado ? estado.tipo : '')" role="status" aria-live="polite" x-show="estado" x-cloak x-text="estado && estado.texto"></p>
                <p class="form-ayuda" x-show="!listo() && !trabajando" x-text="faltante()"></p>
            </div>
        </div>

        <!-- Qué incluir -->
        <section class="superficie bloque-informe bloque-informe--alcance">
            <h2 class="bloque-informe__titulo">Qué incluir</h2>
            <div class="segmentado segmentado--ancho" role="radiogroup" aria-label="Alcance del informe">
                <button type="button" role="radio" :aria-checked="(alcance === 'todo').toString()" @click="alcance = 'todo'">Todo el turnero</button>
                <button type="button" role="radio" :aria-checked="(alcance === 'servicios').toString()" @click="alcance = 'servicios'">Por servicio</button>
                <button type="button" role="radio" :aria-checked="(alcance === 'asesores').toString()" @click="alcance = 'asesores'">Por asesor</button>
            </div>
            <p class="form-ayuda" x-text="{ todo: 'Todos los turnos del periodo, de todos los servicios y asesores.',
                servicios: 'Solo los turnos de los servicios marcados. Marcar una sección incluye todos sus subservicios.',
                asesores: 'Solo los turnos que llamaron o atendieron los asesores marcados (no incluye los que nadie llamó).' }[alcance]"></p>

            <!-- Servicios agrupados por sección -->
            <div class="selector" x-show="alcance === 'servicios'" x-cloak>
                <div class="selector__barra">
                    <input type="search" class="campo" placeholder="Buscar servicio" x-model="buscarServicio" aria-label="Buscar servicio">
                    <span class="selector__cuenta" x-text="serviciosMarcados().length + ' de ' + hojas().length"></span>
                    <button type="button" class="enlace-panel" @click="marcarServicios(true)">Todos</button>
                    <button type="button" class="enlace-panel" @click="marcarServicios(false)">Ninguno</button>
                </div>
                <ul class="selector__lista">
                    <template x-for="s in seccionesVisibles()" :key="s.id">
                        <li>
                            <label class="selector__opcion selector__opcion--seccion">
                                <input type="checkbox" :checked="estadoSeccion(s) === 'todo'" x-effect="$el.indeterminate = estadoSeccion(s) === 'parte'" @change="alternarSeccion(s, $event.target.checked)">
                                <span x-text="s.nombre"></span><span class="texto-mudo" x-show="!s.activo"> · inactivo</span>
                                <span class="selector__nota" x-show="s.hijos.length" x-text="contarMarcados(s) + ' de ' + s.hijos.length"></span>
                            </label>
                            <ul x-show="s.hijos.length">
                                <template x-for="h in hijosVisibles(s)" :key="h.id">
                                    <li><label class="selector__opcion">
                                        <input type="checkbox" :value="h.id" x-model.number="servicios">
                                        <span x-text="h.nombre"></span><span class="texto-mudo" x-show="!h.activo"> · inactivo</span>
                                    </label></li>
                                </template>
                            </ul>
                        </li>
                    </template>
                </ul>
            </div>

            <!-- Asesores -->
            <div class="selector" x-show="alcance === 'asesores'" x-cloak>
                <div class="selector__barra">
                    <input type="search" class="campo" placeholder="Buscar asesor" x-model="buscarAsesor" aria-label="Buscar asesor">
                    <span class="selector__cuenta" x-text="usuarios.length + ' de ' + asesores.length"></span>
                    <button type="button" class="enlace-panel" @click="usuarios = asesores.map(a => a.id)">Todos</button>
                    <button type="button" class="enlace-panel" @click="usuarios = []">Ninguno</button>
                </div>
                <ul class="selector__lista selector__lista--columnas">
                    <template x-for="a in asesoresVisibles()" :key="a.id">
                        <li><label class="selector__opcion">
                            <input type="checkbox" :value="a.id" x-model.number="usuarios">
                            <span x-text="a.nombre"></span> <span class="texto-mudo" x-text="a.usuario"></span>
                        </label></li>
                    </template>
                </ul>
            </div>

            <div class="alcance-todo" x-show="alcance === 'todo'">
                <b x-text="hojas().length"></b> servicios y <b x-text="asesores.length"></b> asesores entran en el informe.
            </div>
        </section>
    </form>
</div>

<script>
document.addEventListener('alpine:init', () => {
    const URL_GENERAR = @json(route('admin.reportes.generar'));
    const TOKEN = () => document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    const normal = t => String(t ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();
    // Fechas LOCALES (new Date('AAAA-MM-DD') es UTC y en Bogotá cae en el día anterior).
    const aFecha = t => { const [a, m, d] = t.split('-').map(Number); return new Date(a, m - 1, d); };
    const aTexto = f => f.getFullYear() + '-' + String(f.getMonth() + 1).padStart(2, '0') + '-' + String(f.getDate()).padStart(2, '0');
    const sumar = (f, dias) => { const x = new Date(f); x.setDate(x.getDate() + dias); return x; };
    const nombreDe = cabecera => {
        const m = /filename\*?=(?:UTF-8'')?"?([^";]+)"?/i.exec(cabecera || '');
        return m ? decodeURIComponent(m[1]) : null;
    };

    Alpine.data('informeTurnos', (secciones, asesores, inicial) => ({
        secciones, asesores,
        desde: '', hasta: '', atajo: 'mes',
        formato: 'excel',
        alcance: 'todo',
        servicios: [],
        usuarios: [],
        buscarServicio: '', buscarAsesor: '',
        trabajando: false,
        estado: null,
        init() {
            if (inicial.desde && inicial.hasta) { this.desde = inicial.desde; this.hasta = inicial.hasta; this.atajo = this.atajoDe(); }
            else this.elegirAtajo('mes');
            // Desde Gráficos con un servicio: ese servicio (y sus subservicios) ya marcado.
            if (inicial.servicio) {
                const s = this.secciones.find(s => s.id === inicial.servicio);
                const ids = s ? (s.hijos.length ? s.hijos.map(h => h.id) : [s.id]) : [inicial.servicio];
                if (this.hojas().some(h => ids.includes(h.id))) { this.alcance = 'servicios'; this.servicios = ids; }
            }
        },
        hoy() { return aTexto(new Date()); },
        rango(clave) {
            const h = new Date(); h.setHours(0, 0, 0, 0);
            const lunes = sumar(h, -((h.getDay() + 6) % 7));
            return {
                hoy: [h, h], ayer: [sumar(h, -1), sumar(h, -1)],
                semana: [lunes, h], semana_pasada: [sumar(lunes, -7), sumar(lunes, -1)],
                mes: [new Date(h.getFullYear(), h.getMonth(), 1), h],
                mes_anterior: [new Date(h.getFullYear(), h.getMonth() - 1, 1), new Date(h.getFullYear(), h.getMonth(), 0)],
            }[clave];
        },
        elegirAtajo(clave) { const [d, h] = this.rango(clave); this.desde = aTexto(d); this.hasta = aTexto(h); this.atajo = clave; },
        atajoDe() {
            return ['hoy', 'ayer', 'semana', 'semana_pasada', 'mes', 'mes_anterior']
                .find(c => { const [d, h] = this.rango(c); return aTexto(d) === this.desde && aTexto(h) === this.hasta; }) || null;
        },
        periodoValido() { return this.desde && this.hasta && this.desde <= this.hasta && this.hasta <= this.hoy(); },
        dias() { return this.periodoValido() ? Math.round((aFecha(this.hasta) - aFecha(this.desde)) / 864e5) + 1 : 0; },
        fecha(t, anio = true) { const f = aFecha(t); return f.getDate() + ' ' + MESES[f.getMonth()] + (anio ? ' ' + f.getFullYear() : ''); },
        textoPeriodoCorto() {
            if (!this.periodoValido()) return 'Periodo sin definir';
            if (this.desde === this.hasta) return this.fecha(this.desde);
            const d = aFecha(this.desde), h = aFecha(this.hasta);
            if (d.getFullYear() === h.getFullYear() && d.getMonth() === h.getMonth()) return d.getDate() + ' – ' + this.fecha(this.hasta);
            return this.fecha(this.desde, d.getFullYear() !== h.getFullYear()) + ' – ' + this.fecha(this.hasta);
        },
        textoPeriodo() {
            if (!this.desde || !this.hasta) return 'Elige las dos fechas.';
            if (this.desde > this.hasta) return 'La fecha final es anterior a la inicial.';
            if (this.hasta > this.hoy()) return 'El periodo no puede terminar después de hoy.';
            return this.dias() === 1 ? 'Un día.' : this.dias() + ' días, del ' + this.fecha(this.desde, false) + ' al ' + this.fecha(this.hasta) + '.';
        },

        // ---- servicios (se envían los subservicios; una sección sin subservicios va sola)
        hojas() { return this.secciones.flatMap(s => s.hijos.length ? s.hijos : [s]); },
        idsDe(s) { return s.hijos.length ? s.hijos.map(h => h.id) : [s.id]; },
        contarMarcados(s) { return this.idsDe(s).filter(id => this.servicios.includes(id)).length; },
        estadoSeccion(s) { const n = this.contarMarcados(s), t = this.idsDe(s).length; return n === 0 ? 'nada' : n === t ? 'todo' : 'parte'; },
        alternarSeccion(s, marcar) {
            const ids = this.idsDe(s);
            this.servicios = marcar ? [...new Set([...this.servicios, ...ids])] : this.servicios.filter(id => !ids.includes(id));
        },
        marcarServicios(marcar) { this.servicios = marcar ? this.hojas().map(h => h.id) : []; },
        serviciosMarcados() { return this.servicios; },
        seccionesVisibles() {
            const q = normal(this.buscarServicio);
            return q ? this.secciones.filter(s => normal(s.nombre).includes(q) || s.hijos.some(h => normal(h.nombre).includes(q))) : this.secciones;
        },
        hijosVisibles(s) {
            const q = normal(this.buscarServicio);
            return !q || normal(s.nombre).includes(q) ? s.hijos : s.hijos.filter(h => normal(h.nombre).includes(q));
        },
        asesoresVisibles() {
            const q = normal(this.buscarAsesor);
            return q ? this.asesores.filter(a => normal(a.nombre + ' ' + a.usuario).includes(q)) : this.asesores;
        },

        textoAlcance() {
            if (this.alcance === 'servicios') return this.servicios.length ? this.servicios.length + ' servicio(s)' : 'sin servicios';
            if (this.alcance === 'asesores') return this.usuarios.length ? this.usuarios.length + ' asesor(es)' : 'sin asesores';
            return 'todo el turnero';
        },
        listo() {
            return this.periodoValido() && (this.alcance === 'todo' || (this.alcance === 'servicios' ? this.servicios.length : this.usuarios.length) > 0);
        },
        faltante() {
            if (!this.periodoValido()) return 'Revisa el periodo.';
            if (this.alcance === 'servicios') return 'Marca al menos un servicio.';
            if (this.alcance === 'asesores') return 'Marca al menos un asesor.';
            return '';
        },

        // Se pide el archivo por fetch: así se sabe cuándo terminó y, si falla, por qué.
        generar() {
            if (!this.listo() || this.trabajando) return;
            this.trabajando = true;
            this.estado = { tipo: 'trabajando', texto: 'Generando el informe' + (this.dias() > 31 ? '; con un periodo largo puede tardar un poco.' : '…') };
            const cuerpo = new FormData();
            cuerpo.append('fecha_inicio', this.desde);
            cuerpo.append('fecha_fin', this.hasta);
            cuerpo.append('formato', this.formato);
            cuerpo.append('alcance', this.alcance);
            if (this.alcance === 'servicios') this.servicios.forEach(id => cuerpo.append('servicios[]', id));
            if (this.alcance === 'asesores') this.usuarios.forEach(id => cuerpo.append('usuarios[]', id));
            fetch(URL_GENERAR, { method: 'POST', body: cuerpo, headers: { 'X-CSRF-TOKEN': TOKEN(), 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } })
                .then(async r => {
                    const tipo = r.headers.get('Content-Type') || '';
                    if (!r.ok || tipo.includes('application/json') || tipo.includes('text/html')) {
                        const d = await r.json().catch(() => ({}));
                        const primero = d.errors ? Object.values(d.errors)[0] : null;
                        throw new Error(r.status === 419 ? 'La sesión expiró. Recarga la página e inténtalo de nuevo.'
                            : (primero ? (Array.isArray(primero) ? primero[0] : primero) : (d.message || 'No se pudo generar el informe (error ' + r.status + ').')));
                    }
                    const archivo = await r.blob();
                    const nombre = nombreDe(r.headers.get('Content-Disposition')) || ('informe_turnos.' + (this.formato === 'excel' ? 'xlsx' : 'pdf'));
                    const enlace = document.createElement('a');
                    enlace.href = URL.createObjectURL(archivo);
                    enlace.download = nombre;
                    document.body.appendChild(enlace); enlace.click(); enlace.remove();
                    setTimeout(() => URL.revokeObjectURL(enlace.href), 60000);
                    this.estado = { tipo: 'ok', texto: 'Listo: ' + nombre };
                })
                .catch(e => { this.estado = { tipo: 'error', texto: e instanceof TypeError ? 'No hay conexión con el servidor. Inténtalo de nuevo.' : e.message }; })
                .finally(() => { this.trabajando = false; });
        },
    }));
});
</script>

@push('estilos')
<style>
[x-cloak] { display: none !important; }
.reportes-vista { font-variant-numeric: tabular-nums; }

/* Dos columnas: periodo, formato y acción a la izquierda; qué incluir a la derecha */
.informe-rejilla { display: grid; grid-template-columns: minmax(0, 5fr) minmax(0, 7fr); gap: .75rem; align-items: start; }
@media (max-width: 1023px) { .informe-rejilla { grid-template-columns: minmax(0, 1fr); } }
.informe-columna { display: flex; flex-direction: column; gap: .75rem; min-width: 0; }
.bloque-informe { display: flex; flex-direction: column; gap: .7rem; padding: .9rem 1.1rem 1rem; min-width: 0; border: 0; margin: 0; }
.bloque-informe__titulo { font-size: .875rem; font-weight: 650; color: #0f2547; }
.atajos .filtro-rapido { height: 2.1rem; padding: 0 .7rem; font-size: .8125rem; }

/* Control segmentado: el elegido con el azul claro institucional */
.segmentado { display: inline-flex; padding: .2rem; gap: .2rem; border-radius: .6rem; background: #eef1f6; align-self: flex-start; }
.segmentado--ancho { align-self: stretch; }
.segmentado--ancho button { flex: 1; }
.segmentado button { height: 2.1rem; padding: 0 1rem; border-radius: .45rem; font-size: .875rem; font-weight: 600; color: #4b5563; cursor: pointer; white-space: nowrap; }
.segmentado button:hover { color: #064b9e; }
.segmentado button[aria-checked="true"] { background: #ffffff; color: #064b9e; box-shadow: 0 1px 2px rgba(16, 24, 40, .12); }
.segmentado button:focus-visible { outline: 2px solid #064b9e; outline-offset: 1px; }

.bloque-informe--accion { gap: .6rem; }
.resumen-informe { font-size: .875rem; line-height: 1.5; color: #374151; }
.resumen-informe b { color: #0f2547; }
.btn-generar { height: 2.75rem; font-size: .9375rem; }
.estado-informe { font-size: .8125rem; font-weight: 500; padding: .45rem .7rem; border-radius: .45rem; }
.estado-informe--trabajando { background: #eef4fc; color: #0f2547; }
.estado-informe--ok { background: #e4faec; color: #005d38; }
.estado-informe--error { background: #ffefed; color: #901e1c; }

/* Qué incluir: la lista ocupa el alto disponible y se desplaza por dentro */
.bloque-informe--alcance { min-height: 100%; }
.selector { display: flex; flex-direction: column; gap: .5rem; min-height: 0; }
.selector__barra { display: flex; align-items: center; gap: .75rem; }
.selector__barra .campo { flex: 1; height: 2.25rem; }
.selector__cuenta { font-size: .8125rem; color: #6b7280; white-space: nowrap; }
.selector__barra .enlace-panel { font-size: .8125rem; }
.selector__lista { max-height: calc(100vh - var(--admin-header-h, 3.25rem) - 17rem); min-height: 10rem; overflow-y: auto; padding: .25rem .1rem;
                   border-top: 1px solid #eef1f6; }
.selector__lista ul { padding-left: 1.6rem; }
.selector__lista--columnas { columns: 2; column-gap: 1.5rem; }
.selector__lista--columnas li { break-inside: avoid; }
.selector__opcion { display: flex; align-items: center; gap: .55rem; padding: .3rem .25rem; border-radius: .35rem; font-size: .875rem; color: #111827; cursor: pointer; }
.selector__opcion:hover { background: #f5f8fc; }
.selector__opcion input { width: 1rem; height: 1rem; accent-color: #064b9e; flex-shrink: 0; }
.selector__opcion--seccion { font-weight: 600; color: #0f2547; }
.selector__nota { margin-left: auto; font-size: .75rem; font-weight: 500; color: #6b7280; }
.alcance-todo { padding: 1rem; border-radius: .5rem; background: #f6f8fc; font-size: .875rem; color: #374151; }
</style>
@endpush
@endsection
