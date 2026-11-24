@extends('layouts.admin')

@section('title', 'Sistema de voz')

@php
    // Valores con respaldo: VoiceService::VOICE_CONFIG solo trae model, language, quality y speaker.
    $confVoz = $systemInfo['voice_config'] ?? [];
    $piperOk = !empty($systemInfo['piper_available']);
    $modeloOk = !empty($systemInfo['model_available']);
    $existentes = (int) ($fileStatus['existing'] ?? 0);
    $revisados = (int) ($fileStatus['total_checked'] ?? 0);
    $faltan = (int) ($fileStatus['missing'] ?? 0);
    $porcentaje = (int) round((float) ($fileStatus['percentage'] ?? 0));
    $faltantes = array_values($fileStatus['missing_files'] ?? []);
    $carpetaVoz = $systemInfo['voice_directory'] ?? public_path('audio/turnero/voice');
@endphp

@section('acciones_pagina')
    <button type="button" class="btn-secundario" onclick="checkSystemStatus()">
        <svg class="voz-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 0 1 15.5-6.2L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-15.5 6.2L3 16"/><path d="M3 21v-5h5"/></svg>
        Actualizar estado
    </button>
    <button type="button" class="btn-secundario" onclick="testAudio('complete')">
        <svg class="voz-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 5 6 9H3v6h3l5 4z"/><path d="M15.5 8.5a5 5 0 0 1 0 7"/><path d="M18.5 5.5a9 9 0 0 1 0 13"/></svg>
        Probar llamado
    </button>
    <button type="button" class="btn-secundario" onclick="generateMissingFiles()">
        <svg class="voz-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
        Generar faltantes
    </button>
@endsection

@section('content')
{{-- Sistema de voz (GET /voice/admin): estado de Piper y de los archivos que dice el TV, una prueba y el generador.
     No está en el menú; se llega por la dirección. --}}
<div class="voz-vista">
    <!-- Estado en cuatro piezas, como las cifras del Inicio -->
    <div class="voz-cifras">
        <div class="voz-cifra">
            <span class="voz-cifra__fila"><span class="voz-cifra__nombre">Piper TTS</span>
                <span class="estado-chip estado-chip--chico {{ $piperOk ? 'estado-chip--ok' : 'estado-chip--error' }}">{{ $piperOk ? 'Disponible' : 'No disponible' }}</span></span>
            <span class="voz-cifra__mas">{{ $piperOk && !empty($systemInfo['piper_version']) ? \Illuminate\Support\Str::limit($systemInfo['piper_version'], 60) : 'El motor que genera la voz' }}</span>
        </div>
        <div class="voz-cifra">
            <span class="voz-cifra__fila"><span class="voz-cifra__nombre">Modelo de voz</span>
                <span class="estado-chip estado-chip--chico {{ $modeloOk ? 'estado-chip--ok' : 'estado-chip--error' }}">{{ $modeloOk ? 'Disponible' : 'No disponible' }}</span></span>
            <span class="voz-cifra__mas">{{ $confVoz['model'] ?? 'es_MX-claude-high' }}</span>
        </div>
        <div class="voz-cifra">
            <span class="voz-cifra__fila"><span class="voz-cifra__cuenta"><b class="voz-cifra__n">{{ $existentes }}</b> de {{ $revisados }} archivos</span>
                <span class="estado-chip estado-chip--chico {{ $faltan ? 'estado-chip--alerta' : 'estado-chip--ok' }}">{{ $faltan ? 'Incompletos' : 'Completos' }}</span></span>
            <span class="voz-cifra__avance">
                <span class="voz-cifra__mas">{{ $porcentaje }} % · {{ $faltan === 1 ? 'falta 1' : 'faltan ' . $faltan }}</span>
                <span class="voz-barra" role="img" aria-label="{{ $porcentaje }} % de los archivos de voz"><i style="width: {{ max(0, min(100, $porcentaje)) }}%"></i></span>
            </span>
        </div>
        <div class="voz-cifra">
            <span class="voz-cifra__fila"><span class="voz-cifra__nombre">Carpeta</span>
                <button type="button" class="enlace-panel voz-cifra__enlace" onclick="openFileManager()">Ver ruta</button></span>
            <span class="voz-cifra__mas voz-cifra__ruta">public/audio/turnero/voice</span>
        </div>
    </div>

    <div class="voz-rejilla vista-lateral">
        <!-- Generador de un archivo -->
        <section class="superficie voz-bloque" aria-labelledby="t-voz-generar-uno">
            <header>
                <h2 id="t-voz-generar-uno" class="voz-bloque__titulo">Generar un archivo</h2>
                <p class="voz-bloque__sub">Piper lo graba en la carpeta elegida y lo reproduce aquí al terminar.</p>
            </header>
            <form id="customAudioForm" class="voz-form" novalidate>
                <div class="form-campo">
                    <label for="customText">Texto que dirá la voz</label>
                    <input type="text" id="customText" name="text" class="campo" maxlength="100" placeholder="Ej.: Turno" required>
                </div>
                <div class="form-panel__fila">
                    <div class="form-campo">
                        <label for="customFilename">Nombre del archivo</label>
                        <input type="text" id="customFilename" name="filename" class="campo" maxlength="50" placeholder="Ej.: turno.mp3" aria-describedby="customFilename-ayuda" required>
                        <span id="customFilename-ayuda" class="form-ayuda">Letras, números, guiones y la terminación .mp3.</span>
                    </div>
                    <div class="form-campo">
                        <label for="customCategory">Categoría</label>
                        <select id="customCategory" name="category" class="campo" required>
                            <option value="frases">Frases</option>
                            <option value="numeros">Números</option>
                            <option value="letras">Letras</option>
                            <option value="custom">Personalizado</option>
                        </select>
                    </div>
                </div>
                <p class="form-error" id="customAudioError" role="alert" hidden></p>
                <div class="voz-form__pie">
                    <button type="submit" class="btn-primario" id="customAudioBtn">Generar archivo</button>
                </div>
            </form>
        </section>

        <!-- Configuración y lo que falta: la columna lateral, la única pieza elevada de la vista -->
        <section class="pieza-elevada vista-lateral__lado voz-bloque" aria-labelledby="t-voz-config">
            <header>
                <h2 id="t-voz-config" class="voz-bloque__titulo">Configuración de la voz</h2>
            </header>
            <dl class="voz-datos">
                <div><dt>Modelo</dt><dd>{{ $confVoz['model'] ?? '—' }}</dd></div>
                <div><dt>Idioma</dt><dd>{{ $confVoz['language'] ?? '—' }}</dd></div>
                <div><dt>Calidad</dt><dd>{{ $confVoz['quality'] ?? '—' }}</dd></div>
                <div><dt>Voz</dt><dd>{{ $confVoz['speaker'] ?? '—' }}</dd></div>
            </dl>

            <h3 class="voz-bloque__subtitulo">Archivos que faltan</h3>
            @if (count($faltantes) > 0)
                <ul class="voz-faltantes">
                    @foreach (array_slice($faltantes, 0, 5) as $archivo)
                        <li><code>{{ $archivo }}</code></li>
                    @endforeach
                </ul>
                @if ($faltan > 5)
                    <p class="voz-bloque__sub">Y {{ $faltan - 5 }} más. «Generar faltantes» los crea todos.</p>
                @endif
            @else
                <p class="voz-completos"><span class="punto punto--ok" aria-hidden="true"></span> Están todos los archivos básicos</p>
            @endif
        </section>
    </div>
</div>

<!-- Modal: confirmar la generación de los archivos que faltan -->
<div x-data="{ abierto: false, generando: false }"
     @voz-confirmar.window="abierto = true; $nextTick(() => $refs.cancelar && $refs.cancelar.focus())">
    <div class="modal-panel" x-show="abierto" x-cloak x-transition.opacity
         @click.self="!generando && (abierto = false)" @keydown.escape.window="abierto && !generando && (abierto = false)">
        <div class="modal-panel__caja modal-panel__caja--angosta" role="dialog" aria-modal="true" aria-labelledby="t-voz-generar" aria-describedby="d-voz-generar">
            <div class="modal-panel__cabeza">
                <h2 id="t-voz-generar">Generar archivos faltantes</h2>
                <button type="button" class="accion-icono" aria-label="Cerrar" :disabled="generando" @click="abierto = false">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
            <div class="form-panel">
                <p id="d-voz-generar" class="form-texto">
                    @if ($faltan)
                        Piper genera {{ $faltan === 1 ? 'el archivo de voz que falta' : 'los ' . $faltan . ' archivos de voz que faltan' }}. Puede tardar varios minutos: no cierre esta página mientras trabaja.
                    @else
                        No falta ningún archivo básico: Piper solo graba los que no existen, así que no hay nada que generar.
                    @endif
                </p>
                @unless ($piperOk && $modeloOk)
                    <div class="aviso aviso--alerta" role="alert">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.7 18-8-14a2 2 0 0 0-3.4 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                        <p>{{ $piperOk ? 'El modelo de voz' : 'Piper TTS' }} no está disponible en este servidor, así que no se podrá generar ningún archivo.</p>
                    </div>
                @endunless
                <div class="modal-panel__pie">
                    @if ($faltan && $piperOk && $modeloOk)
                        <button type="button" class="btn-secundario" x-ref="cancelar" :disabled="generando" @click="abierto = false">Cancelar</button>
                        <button type="button" class="btn-primario" :disabled="generando" :aria-busy="generando.toString()"
                                @click="generando = true; confirmarGeneracion().finally(() => { generando = false; abierto = false; })"
                                x-text="generando ? 'Generando…' : 'Generar'">Generar</button>
                    @else
                        {{-- Nada que generar, o falta Piper o el modelo: no se ofrece una acción que no puede hacer nada --}}
                        <button type="button" class="btn-secundario" x-ref="cancelar" @click="abierto = false">Entendido</button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Funciones de la administración del sistema de voz (los nombres los usan los botones de arriba)
const RUTAS_VOZ = {
    prueba: @json(route('voice.test-audio')),
    faltantes: @json(route('voice.generate-missing')),
    especifico: @json(route('voice.generate-specific')),
};
const CARPETA_VOZ = @json($carpetaVoz);
const cabecerasVoz = () => ({
    'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
});
const leerVoz = r => r.json().catch(() => ({})).then(datos => ({ ok: r.ok, estado: r.status, datos }));
const errorVoz = (estado, datos, porDefecto) => estado === 419 ? 'La sesión expiró. Recargue la página e inténtelo de nuevo.'
    : (datos && datos.errors ? Object.values(datos.errors)[0][0] : (datos && datos.message) || porDefecto);

function testAudio(type, value = null) {
    fetch(RUTAS_VOZ.prueba, { method: 'POST', headers: cabecerasVoz(), body: JSON.stringify({ type: type, value: value }) })
        .then(leerVoz)
        .then(({ ok, estado, datos }) => {
            const archivos = Object.values((datos && datos.audio_files) || {});
            if (ok && datos.success && archivos.length) {
                playAudioSequence(archivos);
                sileo.info({ title: 'Reproduciendo el llamado de prueba' });
            } else if (ok) {
                sileo.warning({ title: 'No hay audios para reproducir', description: 'Genere primero los archivos que faltan.' });
            } else {
                sileo.error({ title: 'No se pudo probar la voz', description: errorVoz(estado, datos, 'Error ' + estado + '.') });
            }
        })
        .catch(() => sileo.error({ title: 'No se pudo probar la voz', description: 'No hay conexión con el servidor.' }));
}

function playAudioSequence(audioFiles) {
    let currentIndex = 0;

    function playNext() {
        if (currentIndex < audioFiles.length) {
            const audio = new Audio(audioFiles[currentIndex]);
            audio.onended = () => {
                currentIndex++;
                setTimeout(playNext, 300); // Pausa de 300ms entre archivos
            };
            audio.onerror = () => {
                console.error('Error reproduciendo:', audioFiles[currentIndex]);
                currentIndex++;
                setTimeout(playNext, 100);
            };
            audio.play();
        }
    }

    playNext();
}

// Abre la confirmación (modal); la generación la hace confirmarGeneracion()
function generateMissingFiles() {
    window.dispatchEvent(new CustomEvent('voz-confirmar'));
}

function confirmarGeneracion() {
    return fetch(RUTAS_VOZ.faltantes, { method: 'POST', headers: cabecerasVoz() })
        .then(leerVoz)
        .then(({ ok, estado, datos }) => {
            if (ok && datos.success) { sileo.recargarCon({ title: 'Archivos de voz generados', description: datos.message }); return; }
            const detalle = datos && datos.results && datos.results.errors && datos.results.errors.length ? datos.results.errors[0] : null;
            sileo.error({ title: 'No se generaron todos los archivos', description: detalle || errorVoz(estado, datos, 'Error ' + estado + '.') });
        })
        .catch(() => sileo.error({ title: 'No se generaron los archivos', description: 'No hay conexión con el servidor.' }));
}

function checkSystemStatus() {
    location.reload();
}

function openFileManager() {
    sileo.info({ title: 'Carpeta de los archivos de voz', description: CARPETA_VOZ });
}

// Formulario personalizado
document.getElementById('customAudioForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const boton = document.getElementById('customAudioBtn');
    const aviso = document.getElementById('customAudioError');
    const data = Object.fromEntries(new FormData(this));
    aviso.hidden = true;
    if (!data.text.trim() || !data.filename.trim()) {
        aviso.textContent = 'Escriba el texto y el nombre del archivo.';
        aviso.hidden = false;
        return;
    }
    boton.disabled = true; boton.setAttribute('aria-busy', 'true'); boton.textContent = 'Generando…';

    fetch(RUTAS_VOZ.especifico, { method: 'POST', headers: cabecerasVoz(), body: JSON.stringify(data) })
        .then(leerVoz)
        .then(({ ok, estado, datos }) => {
            if (ok && datos.success) {
                sileo.success({ title: 'Archivo generado', description: datos.message });
                if (datos.audio_url) new Audio(datos.audio_url).play();
                return;
            }
            aviso.textContent = errorVoz(estado, datos, 'No se pudo generar el archivo.');
            aviso.hidden = false;
        })
        .catch(() => { aviso.textContent = 'No hay conexión con el servidor. Inténtelo de nuevo.'; aviso.hidden = false; })
        .finally(() => { boton.disabled = false; boton.removeAttribute('aria-busy'); boton.textContent = 'Generar archivo'; });
});
</script>

@push('estilos')
<style>
/* ===== Sistema de voz «La sala» (2026-11): mismo lenguaje que el Inicio. Solo medidas propias;
   colores, avisos, chips, botones y la pieza elevada salen del layout. ===== */
/* Cifras tabulares solo en conteos, porcentajes y archivos: en todo el bloque ensanchaban el guion de «es_MX-claude-high» */
.voz-vista { display: flex; flex-direction: column; gap: 1.5rem; }
.voz-ico { width: 1rem; height: 1rem; flex: none; }

/* Cifras: la misma medida que las del Inicio */
.voz-cifras { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); grid-auto-rows: 1fr; gap: .625rem; }
/* Dos por fila antes que en el Inicio: aquí cada pieza lleva nombre y chip en la misma línea (unos 256 px por dentro) */
@media (max-width: 1339px) { .voz-cifras { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 539px) { .voz-cifras { grid-template-columns: minmax(0, 1fr); } }
.voz-cifra {
    min-width: 0; display: flex; flex-direction: column; gap: .1875rem; padding: .6875rem 1rem; border-radius: .875rem;
    background: #fff; box-shadow: var(--roce); font-size: .875rem; color: var(--tinta-suave);
}
/* Alto de la fila = la línea del número de 19 px del Inicio (19 × 1,5 = 28,5 px): las cuatro filas, chips y detalles
   quedan a la misma altura y la pieza mide 72,25 px, como .sala-cifra */
.voz-cifra__fila { display: flex; align-items: center; justify-content: space-between; gap: .5rem; min-height: 1.78125rem; }
.voz-cifra__fila > :first-child { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.voz-cifra__fila > .estado-chip, .voz-cifra__fila > .enlace-panel { flex: none; }
.voz-cifra__nombre { font-size: .875rem; font-weight: 650; color: var(--tinta); }
.voz-cifra__n { font-size: 1.1875rem; font-weight: 650; letter-spacing: -.01em; color: var(--tinta); }
.voz-cifra__cuenta, .voz-cifra__n, .voz-cifra__avance .voz-cifra__mas, .voz-cifra__ruta, .voz-faltantes code { font-variant-numeric: tabular-nums; }
.voz-cifra__mas { font-size: .78125rem; color: var(--tinta-tenue); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.voz-cifra__ruta { letter-spacing: .01em; }
.voz-cifra__enlace { font-size: .8125rem; border-radius: .25rem; }
/* El avance va en la línea del detalle para que la pieza mida lo mismo que las otras tres (unos 72 px, como en el Inicio) */
.voz-cifra__avance { display: flex; align-items: center; gap: .75rem; min-width: 0; }
.voz-cifra__avance .voz-cifra__mas { flex: none; }
.voz-barra { display: block; flex: 1; min-width: 2.5rem; height: .25rem; border-radius: 2px; background: #e9eef5; overflow: hidden; }
.voz-barra i { display: block; height: 100%; background: var(--azul); }

/* Generar un archivo a la izquierda; la configuración es la columna lateral (.vista-lateral del layout, 20rem) */
.voz-bloque { display: flex; flex-direction: column; gap: 1rem; min-width: 0; padding: 1.25rem 1.25rem 1.125rem; }
.voz-bloque__titulo { margin: 0; font-size: 1rem; font-weight: 650; color: var(--tinta); }
.voz-bloque__subtitulo { margin: .25rem 0 0; font-size: .875rem; font-weight: 650; color: var(--tinta); }
.voz-bloque__sub { margin: .125rem 0 0; font-size: .8125rem; color: var(--tinta-suave); text-wrap: pretty; }
.voz-form { display: flex; flex-direction: column; gap: .875rem; }
.voz-form .form-error[hidden] { display: none; }
.voz-form__pie { display: flex; justify-content: flex-end; padding-top: .25rem; }

.voz-datos { margin: 0; display: flex; flex-direction: column; }
.voz-datos > div { display: flex; justify-content: space-between; gap: 1rem; padding: .5rem 0; border-top: 1px solid var(--linea-suave); font-size: .875rem; }
.voz-datos > div:first-child { border-top: 0; padding-top: 0; }
.voz-datos dt { color: var(--tinta-suave); }
.voz-datos dd { margin: 0; font-weight: 600; color: var(--tinta); text-align: right; overflow-wrap: anywhere; }
.voz-faltantes { list-style: none; margin: 0; padding: 0; display: flex; flex-wrap: wrap; gap: .375rem; }
.voz-faltantes code {
    display: inline-block; padding: .25rem .5rem; border-radius: .5rem; background: var(--error-fondo); color: var(--error-texto);
    font-family: inherit; font-size: .75rem; letter-spacing: .01em;
}
.voz-completos { display: flex; align-items: center; gap: .5rem; margin: 0; font-size: .875rem; color: var(--tinta-suave); }
</style>
@endpush
@endsection
