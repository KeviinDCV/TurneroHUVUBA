@extends('layouts.admin')

@section('title', 'Config TV')

@section('content')
{{-- Config TV en una sola vista: a la izquierda la cinta de avisos (con vista previa), a la derecha la lista de
     reproducción. Todo se guarda por fetch, sin recargar; los errores se ven junto a lo que falló. --}}
<div class="tv-vista max-w-7xl mx-auto space-y-4"
     x-data="configTv(@js(['ticker_message' => $tvConfig->ticker_message, 'ticker_speed' => (int) $tvConfig->ticker_speed, 'ticker_enabled' => (bool) $tvConfig->ticker_enabled]), @js($multimedia))">
    <h1 class="sr-only">Configuración del TV</h1>
    <div class="aviso-flotante" :class="{ 'aviso-flotante--error': aviso && aviso.error }" role="status" x-show="aviso" x-transition.opacity x-cloak>
        <span x-text="aviso && aviso.texto"></span>
    </div>

    <!-- Confirmar eliminación de un archivo -->
    <div class="envoltorio-modal" @keydown.escape.window="porEliminar = null">
        <div class="modal-panel" x-show="porEliminar" x-cloak x-transition.opacity @click.self="porEliminar = null">
            <div class="modal-panel__caja modal-panel__caja--angosta" role="dialog" aria-modal="true" aria-labelledby="t-eliminar-medio">
                <div class="modal-panel__cabeza">
                    <h2 id="t-eliminar-medio">Eliminar archivo</h2>
                    <button type="button" class="accion-icono" aria-label="Cerrar" @click="porEliminar = null">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="form-panel">
                    <p class="form-texto">Se quita del TV y se borra del servidor <b x-text="porEliminar && porEliminar.nombre"></b>. No se puede deshacer.
                        <span x-show="porEliminar && porEliminar.activo" class="texto-mudo"> Si solo quieres sacarlo un tiempo, apaga «En pantalla».</span></p>
                    <div class="modal-panel__pie">
                        <button type="button" class="btn-secundario" @click="porEliminar = null">Cancelar</button>
                        <button type="button" class="btn-peligro" :disabled="eliminando" @click="eliminar()" x-text="eliminando ? 'Eliminando…' : 'Eliminar'"></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tv-rejilla">
        <!-- Cinta de avisos -->
        <section class="superficie bloque-tv" aria-labelledby="t-cinta">
            <header class="bloque-tv__cabeza">
                <h2 id="t-cinta">Cinta de avisos</h2>
                <label class="interruptor">
                    <input type="checkbox" role="switch" x-model="cinta.ticker_enabled" :aria-checked="cinta.ticker_enabled.toString()">
                    <span class="interruptor__pista" aria-hidden="true"></span>
                    <span x-text="cinta.ticker_enabled ? 'Al aire' : 'Apagada'"></span>
                </label>
            </header>

            <!-- Vista previa con el mismo azul y el mismo movimiento del TV -->
            <div class="cinta-previa" :class="{ 'cinta-previa--apagada': !cinta.ticker_enabled }" aria-hidden="true">
                <template x-for="v in [vueltaPrevia]" :key="v">
                    <div class="cinta-previa__contenido" :style="'animation-duration:' + cinta.ticker_speed + 's; animation-delay:-' + (cinta.ticker_speed * .2) + 's'">
                        <span class="cinta-previa__texto" x-text="cinta.ticker_message || 'Escribe el mensaje de la cinta…'"></span>
                    </div>
                </template>
            </div>
            <p class="form-ayuda" x-show="!cinta.ticker_enabled">La cinta está apagada: el TV no la muestra.</p>

            <label class="form-campo">Mensaje
                <textarea class="campo campo--area campo-cinta" rows="4" maxlength="1000" x-model="cinta.ticker_message"></textarea>
                <span class="contador" :class="{ 'contador--limite': cinta.ticker_message.length > 950 }" x-text="cinta.ticker_message.length + ' / 1000'"></span>
                <small x-show="errores.ticker_message" x-text="errores.ticker_message"></small>
            </label>

            <div class="form-campo">
                <span class="fila-rotulo"><span>Duración de una vuelta</span> <b x-text="cinta.ticker_speed + ' s'"></b></span>
                <input type="range" class="rango" min="10" max="120" step="5" x-model.number="cinta.ticker_speed" @change="vueltaPrevia++" aria-label="Duración de una vuelta en segundos">
                <span class="rango-extremos"><span>Rápida</span><span>Lenta</span></span>
                <span class="form-ayuda">Tiempo que tarda el mensaje en cruzar la pantalla. Con mensajes largos, súbela para que se alcance a leer.</span>
                <small x-show="errores.ticker_speed" x-text="errores.ticker_speed"></small>
            </div>

            <div class="bloque-tv__pie">
                <a class="enlace-panel" href="{{ route('tv.display') }}" target="_blank" rel="noopener">Ver el TV ↗</a>
                <span class="espaciador"></span>
                <button type="button" class="btn-secundario" :disabled="!cintaCambiada() || guardandoCinta" @click="descartarCinta()">Descartar cambios</button>
                <button type="button" class="btn-primario" :disabled="!cintaCambiada() || guardandoCinta" @click="guardarCinta()" x-text="guardandoCinta ? 'Guardando…' : 'Guardar'"></button>
            </div>
            <p class="estado-guardado" :class="{ 'estado-guardado--error': estadoCinta && estadoCinta.error }" role="status" x-show="estadoCinta" x-cloak x-text="estadoCinta && estadoCinta.texto"></p>
        </section>

        <!-- Lista de reproducción -->
        <section class="superficie bloque-tv" aria-labelledby="t-lista">
            <header class="bloque-tv__cabeza">
                <div>
                    <h2 id="t-lista">Lista de reproducción</h2>
                    <p class="bloque-tv__resumen" x-text="resumenLista()"></p>
                </div>
                <button type="button" class="btn-primario" @click="abrirSubida()" x-show="!subida.abierta">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Subir archivo
                </button>
            </header>

            <!-- Subida: en la misma página, con los errores a la vista -->
            <form class="subida" x-show="subida.abierta" x-cloak @submit.prevent="subir()" novalidate>
                <label class="subida__zona" :class="{ 'subida__zona--encima': subida.encima, 'subida__zona--lista': subida.archivo }"
                       @dragover.prevent="subida.encima = true" @dragleave="subida.encima = false" @drop.prevent="subida.encima = false; elegirArchivo($event.dataTransfer.files[0])">
                    <input type="file" class="sr-only" x-ref="archivo" accept=".jpg,.jpeg,.png,.gif,.webp,.mp4,.webm" @change="elegirArchivo($event.target.files[0])">
                    <template x-if="!subida.archivo">
                        <span class="subida__invitacion">Arrastra aquí una imagen o un video, o <b>elige un archivo</b>.<br>
                            <span class="texto-mudo">JPG, PNG, GIF o WEBP · MP4 o WEBM · hasta 500 MB</span></span>
                    </template>
                    <template x-if="subida.archivo">
                        <span class="subida__elegido">
                            <img x-show="subida.tipo === 'imagen'" :src="subida.vista" alt="">
                            <span class="subida__icono-video" x-show="subida.tipo === 'video'" aria-hidden="true">▶</span>
                            <span><b x-text="subida.archivo.name"></b><br><span class="texto-mudo" x-text="(subida.tipo === 'video' ? 'Video' : 'Imagen') + ' · ' + tamano(subida.archivo.size) + (subida.tipo === 'video' && subida.duracion ? ' · ' + duracion(subida.duracion) : '')"></span></span>
                        </span>
                    </template>
                </label>
                <small class="form-error" role="alert" x-show="subida.errores.archivo" x-text="subida.errores.archivo"></small>
                <div class="form-panel__fila subida__campos">
                    <label class="form-campo">Nombre
                        <input type="text" class="campo" x-model="subida.nombre" maxlength="255">
                        <small x-show="subida.errores.nombre" x-text="subida.errores.nombre"></small>
                    </label>
                    <label class="form-campo" x-show="subida.tipo !== 'video'">Segundos en pantalla
                        <input type="number" class="campo" min="1" max="300" x-model.number="subida.duracion">
                        <small x-show="subida.errores.duracion" x-text="subida.errores.duracion"></small>
                    </label>
                    <div class="form-campo" x-show="subida.tipo === 'video'">Duración
                        <span class="dato-fijo" x-text="subida.duracion ? duracion(subida.duracion) + ' (la del video)' : 'Leyendo el video…'"></span>
                    </div>
                </div>
                <div class="progreso" x-show="subida.enviando" role="progressbar" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="subida.progreso">
                    <span class="progreso__barra" :style="'width:' + subida.progreso + '%'"></span>
                </div>
                <p class="form-ayuda" x-show="subida.enviando" x-text="subida.progreso < 100 ? 'Subiendo… ' + subida.progreso + ' %' : 'Guardando en el servidor…'"></p>
                <p class="form-error" role="alert" x-show="subida.errores.general" x-text="subida.errores.general"></p>
                <div class="modal-panel__pie">
                    <button type="button" class="btn-secundario" @click="cerrarSubida()" x-text="subida.enviando ? 'Cancelar subida' : 'Cancelar'"></button>
                    <button type="submit" class="btn-primario" :disabled="!subidaLista() || subida.enviando">Subir</button>
                </div>
            </form>

            <p class="vacio-lista" x-show="!lista.length" x-cloak>La lista está vacía: el TV muestra el logo del hospital.</p>
            <ol class="lista-medios" @dragover.prevent>
                <template x-for="(m, i) in lista" :key="m.id">
                    <li class="medio" :class="{ 'medio--pausado': !m.activo, 'medio--nuevo': m.id === resaltado, 'medio--antes': destino === i && arrastrado !== null && arrastrado > i, 'medio--despues': destino === i && arrastrado !== null && arrastrado < i }"
                        draggable="true" @dragstart="arrastrado = i; $event.dataTransfer.effectAllowed = 'move'" @dragend="arrastrado = null; destino = null"
                        @dragover.prevent="destino = i" @drop.prevent="soltar(i)">
                        <span class="medio__asa" title="Arrastra para cambiar el orden" aria-hidden="true">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>
                        </span>
                        <span class="medio__orden" x-text="i + 1"></span>
                        <span class="medio__miniatura" :class="'medio__miniatura--' + m.tipo">
                            <template x-if="m.tipo === 'imagen'"><img :src="m.url" alt="" loading="lazy" x-on:error="$el.remove()"></template>
                            <span class="medio__play" x-show="m.tipo === 'video'" aria-hidden="true">▶</span>
                        </span>
                        <div class="medio__info">
                            <p class="medio__nombre" x-text="m.nombre"></p>
                            <p class="medio__meta" x-text="meta(m)"></p>
                            <p class="medio__aviso" x-show="m.formato !== 'ok'" x-text="m.formato === 'no'
                                ? 'El TV no puede reproducir ' + m.extension.toUpperCase() + ': lo salta. Súbelo en MP4.'
                                : 'MOV solo se reproduce si viene en H.264; mejor súbelo en MP4.'"></p>
                        </div>
                        <label class="interruptor interruptor--fila" :title="m.activo ? 'Se muestra en el TV' : 'Pausado: no se muestra'">
                            <input type="checkbox" role="switch" :checked="m.activo" :aria-checked="m.activo.toString()" :aria-label="'Mostrar ' + m.nombre + ' en el TV'" @change="alternar(m)">
                            <span class="interruptor__pista" aria-hidden="true"></span>
                            <span class="interruptor__texto" x-text="m.activo ? 'En pantalla' : 'Pausado'"></span>
                        </label>
                        <div class="medio__acciones">
                            <button type="button" class="accion-icono" :disabled="i === 0" :aria-label="'Subir ' + m.nombre" title="Subir" @click="mover(i, -1)">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                            </button>
                            <button type="button" class="accion-icono" :disabled="i === lista.length - 1" :aria-label="'Bajar ' + m.nombre" title="Bajar" @click="mover(i, 1)">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <button type="button" class="accion-icono accion-icono--peligro" :aria-label="'Eliminar ' + m.nombre" title="Eliminar" @click="porEliminar = m">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </li>
                </template>
            </ol>
            <p class="estado-orden" role="status" x-text="estadoOrden"></p>
        </section>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    const URL_CINTA = @json(route('admin.tv-config.update'));
    const URL_SUBIR = @json(route('admin.tv-config.multimedia.store'));
    const URL_ORDEN = @json(route('admin.tv-config.multimedia.order'));
    const URL_MEDIO = @json(url('tv-config/multimedia'));
    const TOKEN = () => document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const CABECERAS = () => ({ 'X-CSRF-TOKEN': TOKEN(), 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' });
    const decimal = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 1 });
    const IMAGENES = ['jpg', 'jpeg', 'png', 'gif', 'webp'], VIDEOS = ['mp4', 'webm'];
    const primerError = datos => { const e = datos && datos.errors ? Object.values(datos.errors)[0] : null; return e ? (Array.isArray(e) ? e[0] : e) : null; };
    const mensajeDe = (estado, datos, porDefecto) => estado === 419 ? 'La sesión expiró. Recarga la página e inténtalo de nuevo.'
        : estado === 413 ? 'El archivo es demasiado grande para el servidor.' : (primerError(datos) || (datos && datos.message) || porDefecto);
    const subidaVacia = () => ({ abierta: false, archivo: null, tipo: null, vista: null, nombre: '', duracion: 10, enviando: false, progreso: 0, encima: false, errores: {}, xhr: null });

    Alpine.data('configTv', (config, multimedia) => ({
        cinta: { ...config },
        guardada: { ...config },
        guardandoCinta: false, estadoCinta: null, errores: {}, vueltaPrevia: 0,
        lista: multimedia,
        subida: subidaVacia(),
        porEliminar: null, eliminando: false,
        arrastrado: null, destino: null, resaltado: null, estadoOrden: '',
        aviso: null,

        avisar(texto, error = false) { this.aviso = { texto, error }; clearTimeout(this._aviso); this._aviso = setTimeout(() => this.aviso = null, error ? 6000 : 3500); },
        duracion(seg) {
            seg = Math.round(seg || 0);
            if (seg < 60) return seg + ' s';
            const m = Math.floor(seg / 60), s = seg % 60;
            return m + ' min' + (s ? ' ' + s + ' s' : '');
        },
        tamano(b) {
            if (!b) return '—';
            const u = ['B', 'KB', 'MB', 'GB']; let i = 0; while (b >= 1024 && i < u.length - 1) { b /= 1024; i++; }
            return decimal.format(b) + ' ' + u[i];
        },
        meta(m) { return (m.tipo === 'video' ? 'Video' : 'Imagen') + ' · ' + this.duracion(m.duracion) + ' · ' + this.tamano(m.tamano) + (m.activo ? '' : ' · no se muestra'); },
        resumenLista() {
            const al = this.lista.filter(m => m.activo && m.formato !== 'no');
            if (!this.lista.length) return 'Sin archivos';
            if (!al.length) return 'Nada en pantalla: el TV muestra el logo del hospital';
            return al.length + ' en pantalla · la ronda completa dura ' + this.duracion(al.reduce((s, m) => s + (m.duracion || 0), 0));
        },

        // ---- cinta
        cintaCambiada() { return JSON.stringify(this.cinta) !== JSON.stringify(this.guardada); },
        descartarCinta() { this.cinta = { ...this.guardada }; this.errores = {}; this.estadoCinta = null; this.vueltaPrevia++; },
        guardarCinta() {
            this.guardandoCinta = true; this.errores = {}; this.estadoCinta = null;
            const cuerpo = new FormData();
            cuerpo.append('ticker_message', this.cinta.ticker_message);
            cuerpo.append('ticker_speed', this.cinta.ticker_speed);
            if (this.cinta.ticker_enabled) cuerpo.append('ticker_enabled', '1');
            fetch(URL_CINTA, { method: 'POST', body: cuerpo, headers: CABECERAS() })
                .then(async r => ({ ok: r.ok, estado: r.status, datos: await r.json().catch(() => ({})) }))
                .then(({ ok, estado, datos }) => {
                    if (ok && datos.success !== false) {
                        this.guardada = { ...this.cinta };
                        this.estadoCinta = { texto: 'Guardado. El TV lo mostrará en menos de un minuto.' };
                        return;
                    }
                    const e = datos.errors || {};
                    this.errores = Object.fromEntries(Object.entries(e).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]));
                    if (!Object.keys(this.errores).length) this.estadoCinta = { texto: mensajeDe(estado, datos, 'No se pudo guardar la cinta.'), error: true };
                })
                .catch(() => { this.estadoCinta = { texto: 'No hay conexión con el servidor. Inténtalo de nuevo.', error: true }; })
                .finally(() => { this.guardandoCinta = false; });
        },

        // ---- lista: encender/apagar, ordenar y eliminar sin recargar
        alternar(m) {
            m.activo = !m.activo;
            fetch(URL_MEDIO + '/' + m.id + '/toggle', { method: 'POST', headers: CABECERAS() })
                .then(async r => ({ ok: r.ok, estado: r.status, datos: await r.json().catch(() => ({})) }))
                .then(({ ok, estado, datos }) => {
                    if (!ok || datos.success === false) throw new Error(mensajeDe(estado, datos, 'No se pudo cambiar el estado.'));
                    m.activo = !!datos.activo;
                    this.avisar(m.nombre + (m.activo ? ' vuelve al TV.' : ' queda pausado.'));
                })
                .catch(e => { m.activo = !m.activo; this.avisar(e instanceof TypeError ? 'No hay conexión con el servidor.' : e.message, true); });
        },
        mover(i, delta) {
            const j = i + delta;
            if (j < 0 || j >= this.lista.length) return;
            const l = this.lista.slice(); [l[i], l[j]] = [l[j], l[i]]; this.lista = l;
            this.guardarOrden();
        },
        soltar(i) {
            if (this.arrastrado === null || this.arrastrado === i) { this.arrastrado = this.destino = null; return; }
            const l = this.lista.slice(); const [m] = l.splice(this.arrastrado, 1); l.splice(i, 0, m); this.lista = l;
            this.arrastrado = this.destino = null;
            this.guardarOrden();
        },
        // Se guarda solo, un momento después del último cambio; el estado se ve bajo la lista.
        guardarOrden() {
            this.estadoOrden = 'Guardando el orden…';
            clearTimeout(this._orden);
            this._orden = setTimeout(() => {
                fetch(URL_ORDEN, { method: 'POST', headers: { ...CABECERAS(), 'Content-Type': 'application/json' },
                    body: JSON.stringify({ items: this.lista.map((m, k) => ({ id: m.id, orden: k + 1 })) }) })
                    .then(async r => ({ ok: r.ok, estado: r.status, datos: await r.json().catch(() => ({})) }))
                    .then(({ ok, estado, datos }) => {
                        if (!ok || datos.success === false) throw new Error(mensajeDe(estado, datos, 'No se pudo guardar el orden.'));
                        this.lista.forEach((m, k) => m.orden = k + 1);
                        this.estadoOrden = 'Orden guardado.';
                    })
                    .catch(e => { this.estadoOrden = ''; this.avisar((e instanceof TypeError ? 'No hay conexión con el servidor.' : e.message) + ' Recarga para ver el orden real.', true); });
            }, 500);
        },
        eliminar() {
            const m = this.porEliminar; if (!m) return;
            this.eliminando = true;
            fetch(URL_MEDIO + '/' + m.id, { method: 'DELETE', headers: CABECERAS() })
                .then(async r => ({ ok: r.ok, estado: r.status, datos: await r.json().catch(() => ({})) }))
                .then(({ ok, estado, datos }) => {
                    if (!ok || datos.success === false) throw new Error(mensajeDe(estado, datos, 'No se pudo eliminar el archivo.'));
                    this.lista = this.lista.filter(x => x.id !== m.id);
                    this.porEliminar = null;
                    this.avisar('Archivo eliminado.');
                })
                .catch(e => { this.avisar(e instanceof TypeError ? 'No hay conexión con el servidor.' : e.message, true); })
                .finally(() => { this.eliminando = false; });
        },

        // ---- subida
        abrirSubida() { this.subida = { ...subidaVacia(), abierta: true }; },
        cerrarSubida() {
            if (this.subida.xhr) this.subida.xhr.abort();
            if (this.subida.vista) URL.revokeObjectURL(this.subida.vista);
            this.subida = subidaVacia();
        },
        elegirArchivo(archivo) {
            if (!archivo) return;
            const ext = (archivo.name.split('.').pop() || '').toLowerCase();
            const s = this.subida;
            if (s.vista) URL.revokeObjectURL(s.vista);
            Object.assign(s, { archivo: null, tipo: null, vista: null, errores: {} });
            if (!IMAGENES.includes(ext) && !VIDEOS.includes(ext)) {
                s.errores = { archivo: ext === 'avi' || ext === 'mov'
                    ? 'El TV no reproduce ' + ext.toUpperCase() + ' de forma fiable. Conviértelo a MP4 y vuelve a intentarlo.'
                    : 'Formato no admitido. Usa JPG, PNG, GIF o WEBP para imágenes y MP4 o WEBM para videos.' };
                return;
            }
            if (archivo.size > 500 * 1024 * 1024) { s.errores = { archivo: 'El archivo pesa ' + this.tamano(archivo.size) + '; el máximo es 500 MB.' }; return; }
            s.archivo = archivo;
            s.tipo = IMAGENES.includes(ext) ? 'imagen' : 'video';
            s.vista = URL.createObjectURL(archivo);
            if (!s.nombre) s.nombre = archivo.name.replace(/\.[^.]+$/, '').replace(/[_-]+/g, ' ').trim();
            if (s.tipo === 'imagen') { s.duracion = s.duracion && s.duracion <= 300 ? s.duracion : 10; return; }
            // Video: se lee su duración real; si este navegador no lo puede abrir, el TV (también Chrome) tampoco.
            s.duracion = null;
            const v = document.createElement('video');
            v.preload = 'metadata';
            v.onloadedmetadata = () => { s.duracion = Math.max(1, Math.round(v.duration)); };
            v.onerror = () => { s.archivo = null; s.errores = { archivo: 'Este video no se puede reproducir en el navegador, así que el TV tampoco podrá. Conviértelo a MP4 (H.264).' }; };
            v.src = s.vista;
        },
        subidaLista() { const s = this.subida; return s.archivo && s.nombre.trim() && s.duracion >= 1 && (s.tipo === 'video' || s.duracion <= 300); },
        subir() {
            if (!this.subidaLista()) return;
            const s = this.subida;
            s.enviando = true; s.progreso = 0; s.errores = {};
            const cuerpo = new FormData();
            cuerpo.append('archivo', s.archivo);
            cuerpo.append('nombre', s.nombre.trim());
            cuerpo.append('duracion', s.duracion);
            const xhr = new XMLHttpRequest();
            s.xhr = xhr;
            xhr.upload.onprogress = e => { if (e.lengthComputable) s.progreso = Math.round(e.loaded / e.total * 100); };
            xhr.onload = () => {
                let datos = {}; try { datos = JSON.parse(xhr.responseText); } catch (e) {}
                s.enviando = false; s.xhr = null;
                if (xhr.status >= 200 && xhr.status < 300 && datos.success !== false && datos.multimedia) {
                    this.lista = [...this.lista, datos.multimedia];
                    this.resaltado = datos.multimedia.id; setTimeout(() => this.resaltado = null, 2500);
                    this.cerrarSubida();
                    this.avisar('Subido. El TV lo mostrará en su próxima ronda.');
                    return;
                }
                const e = datos.errors || {};
                s.errores = Object.fromEntries(Object.entries(e).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]));
                if (!Object.keys(s.errores).length) s.errores = { general: mensajeDe(xhr.status, datos, 'No se pudo subir el archivo (error ' + xhr.status + ').') };
            };
            xhr.onerror = () => { s.enviando = false; s.xhr = null; s.errores = { general: 'Se perdió la conexión durante la subida. Inténtalo de nuevo.' }; };
            xhr.ontimeout = () => { s.enviando = false; s.xhr = null; s.errores = { general: 'La subida tardó demasiado. Prueba con un archivo más liviano.' }; };
            xhr.open('POST', URL_SUBIR);
            Object.entries(CABECERAS()).forEach(([k, v]) => xhr.setRequestHeader(k, v));
            xhr.timeout = 600000;
            xhr.send(cuerpo);
        },
    }));
});
</script>

@push('estilos')
<style>
[x-cloak] { display: none !important; }
.envoltorio-modal { display: contents; }
.tv-vista { font-variant-numeric: tabular-nums; }
.tv-rejilla { display: grid; grid-template-columns: minmax(0, 5fr) minmax(0, 7fr); gap: .75rem; align-items: start; }
@media (max-width: 1023px) { .tv-rejilla { grid-template-columns: minmax(0, 1fr); } }
.bloque-tv { display: flex; flex-direction: column; gap: .8rem; padding: .9rem 1.1rem 1rem; min-width: 0; }
.bloque-tv__cabeza { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
.bloque-tv__cabeza h2 { font-size: .9375rem; font-weight: 650; color: #0f2547; }
.bloque-tv__resumen { margin-top: .1rem; font-size: .8125rem; color: #6b7280; }
.bloque-tv__pie { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
.espaciador { flex: 1; }

/* Interruptor con el azul institucional */
.interruptor { display: inline-flex; align-items: center; gap: .5rem; font-size: .8125rem; font-weight: 600; color: #374151; cursor: pointer; white-space: nowrap; }
.interruptor input { position: absolute; opacity: 0; width: 1px; height: 1px; }
.interruptor__pista { position: relative; width: 2.1rem; height: 1.2rem; border-radius: 9999px; background: #c9d2df; transition: background-color .15s ease; flex-shrink: 0; }
.interruptor__pista::after { content: ''; position: absolute; top: .15rem; left: .15rem; width: .9rem; height: .9rem; border-radius: 9999px; background: #ffffff;
                             box-shadow: 0 1px 2px rgba(16, 24, 40, .25); transition: transform .15s ease; }
.interruptor input:checked + .interruptor__pista { background: #064b9e; }
.interruptor input:checked + .interruptor__pista::after { transform: translateX(.9rem); }
.interruptor input:focus-visible + .interruptor__pista { outline: 2px solid #064b9e; outline-offset: 2px; }

/* Vista previa de la cinta: mismo azul, mismo movimiento que el TV */
.cinta-previa { position: relative; overflow: hidden; white-space: nowrap; height: 2.5rem; border-radius: .5rem;
                background: linear-gradient(135deg, #064b9e 0%, #0a5fb4 100%); display: flex; align-items: center; }
.cinta-previa--apagada { filter: grayscale(1); opacity: .45; }
.cinta-previa__contenido { display: inline-block; padding-left: 100%; animation: cinta-previa 35s linear infinite; }
.cinta-previa__texto { color: #ffffff; font-weight: 600; font-size: .95rem; letter-spacing: .3px; text-shadow: 0 1px 2px rgba(0, 0, 0, .3); }
@keyframes cinta-previa { 0% { transform: translateX(100%); } 15% { transform: translateX(0%); } 100% { transform: translateX(-100%); } }
@media (prefers-reduced-motion: reduce) { .cinta-previa__contenido { animation: none; padding-left: .75rem; } }
.campo-cinta { min-height: 6rem; line-height: 1.5; }
.contador { align-self: flex-end; font-size: .75rem; font-weight: 500; color: #6b7280; }
.contador--limite { color: #b45309; }
.fila-rotulo { display: flex; justify-content: space-between; }
.fila-rotulo b { color: #064b9e; }
.rango { width: 100%; accent-color: #064b9e; }
.rango-extremos { display: flex; justify-content: space-between; font-size: .75rem; font-weight: 400; color: #6b7280; margin-top: -.2rem; }
.estado-guardado { font-size: .8125rem; font-weight: 500; padding: .45rem .7rem; border-radius: .45rem; background: #e4faec; color: #005d38; }
.estado-guardado--error { background: #ffefed; color: #901e1c; }

/* Subida */
.subida { display: flex; flex-direction: column; gap: .7rem; padding: .9rem; border-radius: .6rem; background: #f6f8fc; }
.subida__zona { display: flex; align-items: center; justify-content: center; min-height: 5.5rem; padding: .9rem; border: 1.5px dashed #b9c3d3; border-radius: .55rem;
                background: #ffffff; text-align: center; font-size: .875rem; color: #374151; cursor: pointer; }
.subida__zona:hover, .subida__zona--encima { border-color: #064b9e; background: #eef4fc; }
.subida__zona--lista { justify-content: flex-start; text-align: left; border-style: solid; }
.subida__zona b { color: #064b9e; }
.subida__elegido { display: flex; align-items: center; gap: .8rem; min-width: 0; }
.subida__elegido img { width: 4.5rem; height: 3rem; object-fit: cover; border-radius: .35rem; background: #eef1f6; }
.subida__icono-video { display: grid; place-items: center; width: 4.5rem; height: 3rem; border-radius: .35rem; background: #0f2547; color: #ffffff; font-size: 1rem; }
.subida__campos { grid-template-columns: minmax(0, 1fr) 11rem; }
.dato-fijo { display: flex; align-items: center; height: 2.5rem; font-weight: 500; color: #374151; }
.progreso { height: .45rem; border-radius: 9999px; background: #e3e8f0; overflow: hidden; }
.progreso__barra { display: block; height: 100%; background: #064b9e; transition: width .2s ease; }

/* Lista de reproducción */
.lista-medios { display: flex; flex-direction: column; }
.medio { position: relative; display: grid; grid-template-columns: auto 1.5rem 4.5rem minmax(0, 1fr) auto auto; align-items: center; gap: .7rem;
         padding: .55rem .25rem; border-top: 1px solid #eef1f6; background: #ffffff; }
.medio:first-child { border-top: 0; }
.medio--pausado .medio__miniatura, .medio--pausado .medio__nombre { opacity: .5; }
.medio--pausado .medio__miniatura { filter: grayscale(1); }
.medio--nuevo { background: #eef4fc; transition: background-color 1.5s ease; }
.medio--antes::before, .medio--despues::after { content: ''; position: absolute; left: 0; right: 0; height: 2px; background: #064b9e; }
.medio--antes::before { top: -1px; }
.medio--despues::after { bottom: -1px; }
.medio__asa { color: #b0b8c6; cursor: grab; }
.medio__orden { font-size: 1.1rem; font-weight: 700; color: #0f2547; text-align: center; }
.medio__miniatura { position: relative; display: grid; place-items: center; width: 4.5rem; height: 2.75rem; border-radius: .35rem; overflow: hidden; background: #eef1f6; }
.medio__miniatura img { width: 100%; height: 100%; object-fit: cover; }
.medio__miniatura--video { background: #0f2547; }
.medio__play { color: #ffffff; font-size: .9rem; }
.medio__nombre { font-size: .875rem; font-weight: 600; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.medio__meta { font-size: .75rem; color: #6b7280; }
.medio__aviso { font-size: .75rem; font-weight: 600; color: #b45309; }
.interruptor--fila { min-width: 7.5rem; }
.interruptor--fila .interruptor__texto { font-weight: 500; color: #4b5563; }
.medio__acciones { display: flex; gap: .1rem; }
.medio__acciones .accion-icono:disabled { opacity: .3; cursor: default; }
.estado-orden { min-height: 1rem; font-size: .75rem; color: #6b7280; }
.vacio-lista { padding: 1.5rem; text-align: center; font-size: .875rem; color: #6b7280; }
</style>
@endpush
@endsection
