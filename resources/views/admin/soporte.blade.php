@extends('layouts.admin')

@section('title', 'Soporte')

@section('content')
{{-- Soporte: a la izquierda la solicitud nueva; a la derecha las registradas, con número y estado. Se guardan en el
     servidor (App\Services\SoporteService); antes solo quedaban en laravel.log y nadie las veía. --}}
<div class="soporte-vista max-w-7xl mx-auto space-y-4" x-data="soporteVista(@js($solicitudes), @js($lugares), @js($donde))">
    <h1 class="sr-only">Soporte</h1>

    <!-- Detalle de una solicitud, con su estado -->
    <div class="envoltorio-modal" @keydown.escape.window="cerrarDetalle()">
        <div class="modal-panel" x-show="detalle" x-cloak x-transition.opacity @click.self="cerrarDetalle()">
            <div class="modal-panel__caja" role="dialog" aria-modal="true" aria-labelledby="t-detalle">
                <template x-if="detalle">
                    <div>
                        <div class="modal-panel__cabeza">
                            <h2 id="t-detalle"><span class="numero-solicitud" x-text="detalle.numero"></span> <span x-text="detalle.asunto"></span></h2>
                            <button type="button" class="accion-icono" aria-label="Cerrar" @click="cerrarDetalle()">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        <div class="form-panel detalle-solicitud">
                            <p class="detalle-meta" x-text="metaLarga(detalle)"></p>
                            <div class="detalle-bloque"><p class="detalle-rotulo">Descripción</p><p class="detalle-texto" x-text="detalle.descripcion"></p></div>
                            <div class="detalle-bloque" x-show="detalle.pasos"><p class="detalle-rotulo">Pasos para que pase</p><p class="detalle-texto" x-text="detalle.pasos"></p></div>
                            <div class="detalle-bloque" x-show="detalle.esperado"><p class="detalle-rotulo">Qué se esperaba</p><p class="detalle-texto" x-text="detalle.esperado"></p></div>
                            <a class="detalle-captura" x-show="detalle.url_adjunto" :href="detalle.url_adjunto" target="_blank" rel="noopener" title="Abrir la captura">
                                <img :src="detalle.url_adjunto" alt="Captura adjunta" loading="lazy">
                            </a>
                            <p class="detalle-navegador" x-show="detalle.navegador" x-text="'Navegador: ' + detalle.navegador"></p>

                            <div class="detalle-estado">
                                <p class="detalle-rotulo">Estado</p>
                                <div class="segmentado" role="radiogroup" aria-label="Estado de la solicitud">
                                    <template x-for="e in estados" :key="e.clave">
                                        <button type="button" role="radio" :aria-checked="(edicion.estado === e.clave).toString()" @click="edicion.estado = e.clave" x-text="e.rotulo"></button>
                                    </template>
                                </div>
                                <label class="form-campo"><span class="form-rotulo">Respuesta o nota <span class="form-opcional">(opcional)</span></span>
                                    <textarea class="campo campo--area" rows="2" maxlength="1000" x-model="edicion.nota" placeholder="Qué se hizo, o qué falta"></textarea>
                                </label>
                                <p class="form-ayuda" x-show="detalle.actualizada" x-text="'Última actualización: ' + fecha(detalle.actualizada) + (detalle.actualizada_por ? ' por ' + detalle.actualizada_por : '')"></p>
                                <p class="form-error" x-show="errorDetalle" x-text="errorDetalle"></p>
                            </div>
                            <div class="modal-panel__pie">
                                <button type="button" class="btn-secundario" @click="cerrarDetalle()">Cerrar</button>
                                <button type="button" class="btn-primario" :disabled="guardandoEstado || !estadoCambiado()" @click="guardarEstado()" x-text="guardandoEstado ? 'Guardando…' : 'Guardar estado'"></button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <div class="soporte-rejilla">
        <!-- Solicitud nueva -->
        <section class="superficie bloque-soporte" aria-labelledby="t-nueva">
            <h2 id="t-nueva" class="bloque-soporte__titulo">Nueva solicitud</h2>
            <form class="form-soporte" @submit.prevent="enviar()" @paste="pegar($event)" novalidate>
                <div class="form-campo">¿Qué pasa?
                    <div class="segmentado segmentado--ancho" role="radiogroup" aria-label="Tipo de solicitud">
                        <button type="button" role="radio" :aria-checked="(f.tipo === 'error').toString()" @click="f.tipo = 'error'">Algo falla</button>
                        <button type="button" role="radio" :aria-checked="(f.tipo === 'mejora').toString()" @click="f.tipo = 'mejora'">Quiero proponer un cambio</button>
                        <button type="button" role="radio" :aria-checked="(f.tipo === 'otro').toString()" @click="f.tipo = 'otro'">Otra consulta</button>
                    </div>
                </div>

                <div class="form-panel__fila fila-urgencia">
                    <div class="form-campo">
                        <span class="rotulo-urgencia">Urgencia <span class="form-ayuda" :class="{ 'form-ayuda--alerta': f.urgencia === 'critica' }" x-text="'· ' + urgencias.find(u => u.clave === f.urgencia).ayuda"></span></span>
                        <div class="segmentado segmentado--ancho" role="radiogroup" aria-label="Urgencia">
                            <template x-for="u in urgencias" :key="u.clave">
                                <button type="button" role="radio" :aria-checked="(f.urgencia === u.clave).toString()" @click="f.urgencia = u.clave" x-text="u.rotulo"></button>
                            </template>
                        </div>
                    </div>
                    <label class="form-campo">¿Dónde pasó?
                        <select class="campo" x-model="f.donde">
                            <option value="">Sin indicar</option>
                            @foreach ($lugares as $clave => $nombre)
                                <option value="{{ $clave }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                        <small x-show="errores.donde" x-text="errores.donde"></small>
                    </label>
                </div>
                <p class="aviso-critico" x-show="f.urgencia === 'critica'" x-cloak>
                    @if ($contacto)
                        Si no se pueden sacar, llamar o mostrar turnos, además de enviarla avisa ya: <b>{{ $contacto }}</b>.
                    @else
                        Si no se pueden sacar, llamar o mostrar turnos, además de enviarla avisa de inmediato a Innovación y Desarrollo.
                    @endif
                </p>

                <label class="form-campo">Asunto
                    <input type="text" class="campo" maxlength="255" x-model="f.asunto" x-ref="asunto"
                           :placeholder="f.tipo === 'error' ? 'Ej.: El TV no muestra el turno llamado' : 'Resumen en una línea'">
                    <small x-show="errores.asunto" x-text="errores.asunto"></small>
                </label>
                <label class="form-campo">Descripción
                    <textarea class="campo campo--area" rows="3" maxlength="2000" x-model="f.descripcion"
                              :placeholder="f.tipo === 'error' ? 'Qué pasó, a quién le afecta y desde cuándo.' : f.tipo === 'mejora' ? 'Qué te gustaría que cambiara y para qué serviría.' : 'Cuéntanos tu consulta.'"></textarea>
                    <span class="contador" x-text="f.descripcion.length + ' / 2000'"></span>
                    <small x-show="errores.descripcion" x-text="errores.descripcion"></small>
                </label>

                <div class="form-panel__fila" x-show="f.tipo === 'error'">
                    <label class="form-campo"><span class="form-rotulo">Pasos para que pase <span class="form-opcional">(opcional)</span></span>
                        <textarea class="campo campo--area" rows="2" maxlength="1000" x-model="f.pasos" placeholder="1. Entrar a Turnos&#10;2. Buscar C-093&#10;3. …"></textarea>
                        <small x-show="errores.pasos_reproducir" x-text="errores.pasos_reproducir"></small>
                    </label>
                    <label class="form-campo"><span class="form-rotulo">¿Qué esperabas que pasara? <span class="form-opcional">(opcional)</span></span>
                        <textarea class="campo campo--area" rows="2" maxlength="1000" x-model="f.esperado"></textarea>
                        <small x-show="errores.comportamiento_esperado" x-text="errores.comportamiento_esperado"></small>
                    </label>
                </div>

                <!-- Captura: elegir, arrastrar o pegar (Ctrl+V) -->
                <div class="form-campo"><span class="form-rotulo">Captura de pantalla <span class="form-opcional">(opcional)</span></span>
                    <label class="captura" :class="{ 'captura--encima': encima }" x-show="!f.captura"
                           @dragover.prevent="encima = true" @dragleave="encima = false" @drop.prevent="encima = false; elegirCaptura($event.dataTransfer.files[0])">
                        <input type="file" class="sr-only" accept=".jpg,.jpeg,.png,.webp" @change="elegirCaptura($event.target.files[0])">
                        <span>Pega aquí una captura con <b>Ctrl+V</b>, arrástrala o <b>elige una imagen</b> (hasta 5 MB).</span>
                    </label>
                    <div class="captura-elegida" x-show="f.captura" x-cloak>
                        <img :src="f.vista" alt="Vista previa de la captura">
                        <span class="texto-mudo" x-text="f.captura ? f.captura.name + ' · ' + kb(f.captura.size) : ''"></span>
                        <button type="button" class="enlace-panel" @click="quitarCaptura()">Quitar</button>
                    </div>
                    <small x-show="errores.captura" x-text="errores.captura"></small>
                </div>

                <p class="form-error" x-show="errores.general" x-text="errores.general"></p>
                <p class="confirmacion" role="status" x-show="confirmacion" x-cloak x-text="confirmacion"></p>
                <div class="pie-soporte">
                    <span class="remitente">Se enviará como <b>{{ $user->nombre_completo }}</b>{{ $user->correo_electronico ? ' · ' . $user->correo_electronico : '' }}</span>
                    <button type="submit" class="btn-primario" :disabled="enviando" x-text="enviando ? 'Enviando…' : 'Enviar solicitud'"></button>
                </div>
            </form>
        </section>

        <!-- Solicitudes registradas -->
        <section class="superficie bloque-soporte" aria-labelledby="t-solicitudes">
            <div class="bloque-soporte__cabeza">
                <h2 id="t-solicitudes" class="bloque-soporte__titulo">Solicitudes</h2>
                <div class="filtros-rapidos filtros-solicitudes" role="group" aria-label="Filtrar solicitudes">
                    <template x-for="fl in filtros" :key="fl.clave">
                        <button type="button" class="filtro-rapido" :aria-pressed="(filtro === fl.clave).toString()" @click="filtro = fl.clave">
                            <span x-text="fl.rotulo"></span> <span class="filtro-rapido__n" x-text="contar(fl.clave)"></span>
                        </button>
                    </template>
                </div>
            </div>
            @if ($contacto)
                <p class="contacto-urgencias">Urgencias: <b>{{ $contacto }}</b></p>
            @endif
            {{-- Mientras arranca Alpine: la forma de la lista (init() la quita) --}}
            <ul class="lista-solicitudes" data-esqueleto aria-hidden="true">
                @for ($i = 0; $i < min(count($solicitudes), 6); $i++)
                    <li class="solicitud solicitud--esqueleto"><span class="esqueleto" style="width: {{ [70, 55, 80][$i % 3] }}%"></span><span class="esqueleto" style="width: {{ [45, 60, 38][$i % 3] }}%"></span></li>
                @endfor
            </ul>
            <ul class="lista-solicitudes">
                <template x-for="s in visibles()" :key="s.id">
                    <li>
                        <button type="button" class="solicitud" :class="{ 'solicitud--nueva': s.id === resaltada }" @click="abrirDetalle(s)">
                            <span class="solicitud__fila">
                                <span class="numero-solicitud" x-text="s.numero"></span>
                                <span class="solicitud__asunto" x-text="s.asunto"></span>
                                <span class="estado-solicitud" :class="'estado-solicitud--' + s.estado" x-text="textoEstado(s.estado)"></span>
                            </span>
                            <span class="solicitud__meta">
                                <span :class="{ 'urgencia--alta': s.urgencia === 'alta', 'urgencia--critica': s.urgencia === 'critica' }" x-text="textoUrgencia(s.urgencia)"></span>
                                <span x-text="' · ' + textoTipo(s.tipo) + (s.donde_texto ? ' · ' + s.donde_texto : '') + ' · ' + (s.usuario.nombre || s.usuario.usuario) + ' · ' + hace(s.creada)"></span>
                                <span x-show="s.url_adjunto" title="Tiene captura"> · captura</span>
                            </span>
                        </button>
                    </li>
                </template>
            </ul>
            <p class="vacio-solicitudes" x-show="!visibles().length" x-cloak
               x-text="solicitudes.length ? 'No hay solicitudes en este grupo.' : 'Todavía no hay solicitudes. La primera que envíes aparecerá aquí con su número.'"></p>
        </section>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    const URL_SOPORTE = @json(route('admin.soporte'));
    const TOKEN = () => document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    const TIPOS = { error: 'Algo falla', mejora: 'Propuesta de cambio', otro: 'Consulta' };
    const ESTADOS = { nueva: 'Nueva', en_curso: 'En curso', resuelta: 'Resuelta' };
    const mensajeDe = (estado, datos, porDefecto) => estado === 419 ? 'La sesión expiró. Recarga la página e inténtalo de nuevo.'
        : estado === 413 ? 'La captura es demasiado grande para el servidor.' : (datos.message || porDefecto);
    const vacio = donde => ({ tipo: 'error', urgencia: 'media', donde: donde || '', asunto: '', descripcion: '', pasos: '', esperado: '', captura: null, vista: null });

    Alpine.data('soporteVista', (solicitudes, lugares, donde) => ({
        solicitudes,
        f: vacio(donde),
        errores: {}, enviando: false, confirmacion: '', encima: false,
        filtro: 'abiertas',
        detalle: null, edicion: { estado: 'nueva', nota: '' }, guardandoEstado: false, errorDetalle: '',
        resaltada: null,
        urgencias: [
            { clave: 'baja', rotulo: 'Baja', ayuda: 'Puede esperar: no afecta la atención.' },
            { clave: 'media', rotulo: 'Media', ayuda: 'Molesta, pero se puede seguir trabajando.' },
            { clave: 'alta', rotulo: 'Alta', ayuda: 'Afecta la atención de un servicio o de un módulo.' },
            { clave: 'critica', rotulo: 'Crítica', ayuda: 'No se pueden sacar, llamar o mostrar turnos.' },
        ],
        estados: [{ clave: 'nueva', rotulo: 'Nueva' }, { clave: 'en_curso', rotulo: 'En curso' }, { clave: 'resuelta', rotulo: 'Resuelta' }],
        filtros: [{ clave: 'abiertas', rotulo: 'Abiertas' }, { clave: 'resueltas', rotulo: 'Resueltas' }, { clave: 'todas', rotulo: 'Todas' }],
        init() { this.$el.querySelectorAll('[data-esqueleto]').forEach(e => e.remove()); },

        // ---- textos
        textoTipo(t) { return TIPOS[t] || t; },
        textoEstado(e) { return ESTADOS[e] || e; },
        textoUrgencia(u) { return { baja: 'Baja', media: 'Media', alta: 'Alta', critica: 'Crítica' }[u] || u; },
        kb(b) { return b < 1024 * 1024 ? Math.round(b / 1024) + ' KB' : (b / 1024 / 1024).toFixed(1).replace('.', ',') + ' MB'; },
        fecha(iso) { const f = new Date(iso); return f.getDate() + ' ' + MESES[f.getMonth()] + ' ' + f.getFullYear() + ', ' + String(f.getHours()).padStart(2, '0') + ':' + String(f.getMinutes()).padStart(2, '0'); },
        hace(iso) {
            const f = new Date(iso), min = Math.round((Date.now() - f) / 60000);
            if (min < 2) return 'hace un momento';
            if (min < 60) return 'hace ' + min + ' min';
            if (min < 24 * 60) return 'hace ' + Math.round(min / 60) + ' h';
            if (min < 48 * 60) return 'ayer';
            return f.getDate() + ' ' + MESES[f.getMonth()] + (f.getFullYear() !== new Date().getFullYear() ? ' ' + f.getFullYear() : '');
        },
        metaLarga(s) {
            return this.textoTipo(s.tipo) + ' · urgencia ' + this.textoUrgencia(s.urgencia).toLowerCase() + (s.donde_texto ? ' · ' + s.donde_texto : '')
                + ' · ' + (s.usuario.nombre || s.usuario.usuario) + (s.usuario.correo ? ' (' + s.usuario.correo + ')' : '') + ' · ' + this.fecha(s.creada);
        },

        // ---- lista
        pasa(s, clave) { return clave === 'abiertas' ? s.estado !== 'resuelta' : clave === 'resueltas' ? s.estado === 'resuelta' : true; },
        contar(clave) { return this.solicitudes.filter(s => this.pasa(s, clave)).length; },
        visibles() { return this.solicitudes.filter(s => this.pasa(s, this.filtro)); },
        abrirDetalle(s) { this.detalle = s; this.edicion = { estado: s.estado, nota: s.nota || '' }; this.errorDetalle = ''; },
        cerrarDetalle() { if (!this.guardandoEstado) this.detalle = null; },
        estadoCambiado() { return this.detalle && (this.edicion.estado !== this.detalle.estado || (this.edicion.nota || '') !== (this.detalle.nota || '')); },
        guardarEstado() {
            const s = this.detalle; this.guardandoEstado = true; this.errorDetalle = '';
            const cuerpo = new FormData(); cuerpo.append('estado', this.edicion.estado); cuerpo.append('nota', this.edicion.nota || '');
            fetch(URL_SOPORTE + '/' + s.id + '/estado', { method: 'POST', body: cuerpo, headers: { 'X-CSRF-TOKEN': TOKEN(), 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } })
                .then(async r => ({ ok: r.ok, estado: r.status, datos: await r.json().catch(() => ({})) }))
                .then(({ ok, estado, datos }) => {
                    if (!ok || !datos.solicitud) throw new Error(mensajeDe(estado, datos, 'No se pudo guardar el estado.'));
                    this.solicitudes = this.solicitudes.map(x => x.id === s.id ? datos.solicitud : x);
                    this.detalle = null;
                    sileo.success({ title: s.numero + ': ' + this.textoEstado(datos.solicitud.estado).toLowerCase() });
                })
                .catch(e => { this.errorDetalle = e instanceof TypeError ? 'No hay conexión con el servidor.' : e.message; })
                .finally(() => { this.guardandoEstado = false; });
        },

        // ---- captura
        elegirCaptura(archivo) {
            if (!archivo) return;
            this.errores = { ...this.errores, captura: null };
            if (!/^image\/(png|jpe?g|webp)$/.test(archivo.type)) { this.errores = { ...this.errores, captura: 'La captura debe ser una imagen JPG, PNG o WEBP.' }; return; }
            if (archivo.size > 5 * 1024 * 1024) { this.errores = { ...this.errores, captura: 'La captura pesa ' + this.kb(archivo.size) + '; el máximo es 5 MB.' }; return; }
            this.quitarCaptura();
            this.f.captura = archivo;
            this.f.vista = URL.createObjectURL(archivo);
        },
        pegar(e) {
            const item = [...(e.clipboardData ? e.clipboardData.items : [])].find(i => i.kind === 'file' && i.type.startsWith('image/'));
            if (!item) return;
            e.preventDefault();
            const b = item.getAsFile();
            this.elegirCaptura(new File([b], 'captura-' + Date.now() + '.' + (b.type.split('/')[1] || 'png').replace('jpeg', 'jpg'), { type: b.type }));
        },
        quitarCaptura() { if (this.f.vista) URL.revokeObjectURL(this.f.vista); this.f.captura = null; this.f.vista = null; },

        // ---- envío
        enviar() {
            this.errores = {}; this.confirmacion = '';
            if (!this.f.asunto.trim()) this.errores.asunto = 'Escribe el asunto.';
            if (!this.f.descripcion.trim()) this.errores.descripcion = 'Cuéntanos qué pasa.';
            if (Object.keys(this.errores).length) { this.errores = { ...this.errores }; return; }
            this.enviando = true;
            const f = this.f, cuerpo = new FormData();
            cuerpo.append('tipo_solicitud', f.tipo); cuerpo.append('prioridad', f.urgencia); cuerpo.append('donde', f.donde);
            cuerpo.append('asunto', f.asunto.trim()); cuerpo.append('descripcion', f.descripcion.trim());
            if (f.tipo === 'error') { cuerpo.append('pasos_reproducir', f.pasos.trim()); cuerpo.append('comportamiento_esperado', f.esperado.trim()); }
            if (f.captura) cuerpo.append('captura', f.captura);
            fetch(URL_SOPORTE, { method: 'POST', body: cuerpo, headers: { 'X-CSRF-TOKEN': TOKEN(), 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } })
                .then(async r => ({ ok: r.ok, estado: r.status, datos: await r.json().catch(() => ({})) }))
                .then(({ ok, estado, datos }) => {
                    if (ok && datos.solicitud) {
                        const s = datos.solicitud;
                        this.solicitudes = [s, ...this.solicitudes];
                        this.filtro = 'abiertas';
                        this.resaltada = s.id; setTimeout(() => this.resaltada = null, 3000);
                        this.quitarCaptura();
                        this.f = vacio(donde);
                        this.confirmacion = 'Solicitud ' + s.numero + ' registrada. Aparece en la lista de la derecha' + (datos.avisado ? ' y se avisó por correo al equipo.' : '.');
                        return;
                    }
                    const e = datos.errors || {};
                    this.errores = Object.fromEntries(Object.entries(e).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]));
                    if (!Object.keys(this.errores).length) this.errores = { general: mensajeDe(estado, datos, 'No se pudo enviar la solicitud.') };
                })
                .catch(() => { this.errores = { general: 'No hay conexión con el servidor. Tu texto sigue aquí: inténtalo de nuevo.' }; })
                .finally(() => { this.enviando = false; });
        },
    }));
});
</script>

@push('estilos')
<style>
[x-cloak] { display: none !important; }
.envoltorio-modal { display: contents; }
.soporte-vista { font-variant-numeric: tabular-nums; }
.soporte-rejilla { display: grid; grid-template-columns: minmax(0, 7fr) minmax(0, 5fr); gap: .75rem; align-items: start; }
@media (max-width: 1023px) { .soporte-rejilla { grid-template-columns: minmax(0, 1fr); } }
.bloque-soporte { display: flex; flex-direction: column; gap: .5rem; padding: .75rem 1.1rem .8rem; min-width: 0; }
.bloque-soporte__titulo { font-size: .9375rem; font-weight: 650; color: #0f2547; }
.bloque-soporte__cabeza { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; }

/* Formulario */
.form-soporte { display: flex; flex-direction: column; gap: .45rem; }
.form-soporte .form-campo { gap: .25rem; }
.segmentado { display: inline-flex; padding: .2rem; gap: .2rem; border-radius: .6rem; background: #eef1f6; }
.segmentado--ancho { display: flex; }
.segmentado--ancho button { flex: 1; }
.segmentado button { height: 2.1rem; padding: 0 .75rem; border-radius: .45rem; font-size: .8125rem; font-weight: 600; color: #4b5563; cursor: pointer; white-space: nowrap; }
.segmentado button:hover { color: #064b9e; }
.segmentado button[aria-checked="true"] { background: #ffffff; color: #064b9e; box-shadow: 0 1px 2px rgba(16, 24, 40, .12); }
.segmentado button:focus-visible { outline: 2px solid #064b9e; outline-offset: 1px; }
.fila-urgencia { grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr); align-items: end; }
.rotulo-urgencia { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rotulo-urgencia .form-ayuda { margin-left: .2rem; }
.form-ayuda--alerta { color: #b7191c; font-weight: 600; }
.aviso-critico { padding: .5rem .75rem; border-radius: .5rem; background: #fff4e5; color: #7a3e00; font-size: .8125rem; }
.form-opcional { font-weight: 400; color: #9ca3af; }
.contador { align-self: flex-end; margin-top: -.1rem; font-size: .75rem; font-weight: 500; color: #6b7280; line-height: 1; }
.captura { display: flex; align-items: center; justify-content: center; min-height: 2.25rem; padding: .35rem; border: 1.5px dashed #b9c3d3; border-radius: .55rem;
           background: #fafbfd; font-size: .8125rem; font-weight: 400; color: #4b5563; text-align: center; cursor: pointer; }
.captura:hover, .captura--encima { border-color: #064b9e; background: #eef4fc; }
.captura b { color: #064b9e; }
.captura-elegida { display: flex; align-items: center; gap: .75rem; font-weight: 400; }
.captura-elegida img { width: 5.5rem; height: 3.5rem; object-fit: cover; border-radius: .35rem; background: #eef1f6; }
.confirmacion { padding: .5rem .75rem; border-radius: .5rem; background: #e4faec; color: #005d38; font-size: .875rem; font-weight: 500; }
.pie-soporte { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
.remitente { font-size: .8125rem; color: #6b7280; }
.remitente b { color: #374151; font-weight: 600; }

/* Lista de solicitudes */
.filtros-solicitudes .filtro-rapido { height: 2rem; padding: 0 .65rem; font-size: .8125rem; box-shadow: none; background: #f3f5f9; }
.filtros-solicitudes .filtro-rapido[aria-pressed="true"] { background: #064b9e; }
.contacto-urgencias { padding: .45rem .7rem; border-radius: .45rem; background: #f6f8fc; font-size: .8125rem; color: #374151; }
.lista-solicitudes { display: flex; flex-direction: column; max-height: calc(100vh - var(--admin-header-h, 3.25rem) - 10rem); overflow-y: auto; }
.solicitud { display: flex; flex-direction: column; gap: .2rem; width: 100%; padding: .6rem .35rem; border-top: 1px solid #eef1f6; text-align: left; cursor: pointer; border-radius: .35rem; }
.lista-solicitudes li:first-child .solicitud { border-top: 0; }
.solicitud:hover { background: #f8fafd; }
.solicitud:focus-visible { outline: 2px solid #064b9e; outline-offset: -2px; }
.solicitud--nueva { background: #eef4fc; }
.solicitud--esqueleto { gap: .5rem; cursor: default; }
.solicitud__fila { display: flex; align-items: baseline; gap: .5rem; min-width: 0; }
.numero-solicitud { font-size: .75rem; font-weight: 700; letter-spacing: .03em; color: #064b9e; white-space: nowrap; }
.solicitud__asunto { flex: 1; min-width: 0; font-size: .875rem; font-weight: 600; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.solicitud__meta { font-size: .75rem; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.urgencia--alta { color: #b45309; font-weight: 600; }
.urgencia--critica { color: #b7191c; font-weight: 700; }
.estado-solicitud { font-size: .75rem; font-weight: 600; white-space: nowrap; }
.estado-solicitud--nueva { color: #064b9e; }
.estado-solicitud--en_curso { color: #b45309; }
.estado-solicitud--resuelta { color: #008236; }
.vacio-solicitudes { padding: 1.5rem .5rem; text-align: center; font-size: .875rem; color: #6b7280; }

/* Detalle */
#t-detalle { display: flex; align-items: baseline; gap: .5rem; }
.detalle-solicitud { gap: .7rem; }
.detalle-meta { font-size: .8125rem; color: #6b7280; }
.detalle-rotulo { font-size: .6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #6b7280; margin-bottom: .15rem; }
.detalle-texto { font-size: .875rem; line-height: 1.5; color: #111827; white-space: pre-line; }
.detalle-captura img { max-height: 12rem; border-radius: .45rem; border: 1px solid #eef1f6; }
.detalle-navegador { font-size: .6875rem; color: #9ca3af; }
.detalle-estado { display: flex; flex-direction: column; gap: .5rem; padding-top: .6rem; border-top: 1px solid #eef1f6; }
</style>
@endpush
@endsection
