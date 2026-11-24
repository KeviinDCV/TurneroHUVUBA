@extends('layouts.admin')

@section('title', 'Config TV')

@section('acciones_pagina')
    <a class="btn-secundario" href="{{ route('tv.display') }}" target="_blank" rel="noopener">
        <svg class="tv-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 4h6v6"/><path d="M20 4l-9 9"/><path d="M18 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4"/></svg>
        Ver el TV<span class="sr-only"> (se abre en otra pestaña)</span>
    </a>
@endsection

@section('content')
{{-- Config TV en una sola vista: a la izquierda la cinta de avisos (con vista previa fiel al TV), a la derecha la lista
     de reproducción (la pieza elevada). Todo se guarda por fetch, sin recargar; los errores se ven junto a lo que falló.
     Subir un archivo y eliminarlo van en modales (.modal-panel). --}}
<div class="tv-vista"
     x-data="configTv(@js(['ticker_message' => $tvConfig->ticker_message, 'ticker_speed' => (int) $tvConfig->ticker_speed, 'ticker_enabled' => (bool) $tvConfig->ticker_enabled]), @js($multimedia))">

    <div class="tv-rejilla">
        <!-- Cinta de avisos -->
        <section class="superficie bloque-tv" aria-labelledby="t-cinta">
            <header class="bloque-tv__cabeza">
                <div>
                    <h2 id="t-cinta" class="bloque-tv__titulo">Cinta de avisos</h2>
                    <p class="bloque-tv__resumen">Corre en la parte de abajo del TV.</p>
                </div>
                <label class="interruptor">
                    <input type="checkbox" role="switch" x-model="cinta.ticker_enabled" :aria-checked="cinta.ticker_enabled.toString()" @checked($tvConfig->ticker_enabled)>
                    <span class="interruptor__pista" aria-hidden="true"></span>
                    <span x-text="cinta.ticker_enabled ? 'Al aire' : 'Apagada'">{{ $tvConfig->ticker_enabled ? 'Al aire' : 'Apagada' }}</span>
                </label>
            </header>

            <!-- Vista previa: el mismo rótulo, el mismo azul y el mismo paso que la cinta del TV -->
            <div class="cinta-previa-bloque">
                <div class="cinta-previa" :class="{ 'cinta-previa--apagada': !cinta.ticker_enabled }" aria-hidden="true">
                    <span class="cinta-previa__rotulo">Avisos</span>
                    <span class="cinta-previa__pista" x-ref="pista"><span class="cinta-previa__contenido" x-ref="previa"></span></span>
                </div>
                <p class="form-ayuda" x-text="cinta.ticker_enabled ? 'Vista previa: pasa al mismo ritmo que en el TV.' : 'La cinta está apagada: el TV no la muestra.'">{{ $tvConfig->ticker_enabled ? 'Vista previa: pasa al mismo ritmo que en el TV.' : 'La cinta está apagada: el TV no la muestra.' }}</p>
            </div>

            <div class="form-campo">
                <label for="cinta-mensaje">Mensaje</label>
                <textarea id="cinta-mensaje" class="campo campo--area campo-cinta" rows="4" maxlength="1000" x-model="cinta.ticker_message"
                          aria-describedby="cinta-contador" :aria-invalid="errores.ticker_message ? 'true' : null">{{ $tvConfig->ticker_message }}</textarea>
                {{-- El mismo conteo de JS (unidades UTF-16), para que el contador no salga vacío antes de Alpine --}}
                <span id="cinta-contador" class="contador" :class="{ 'contador--limite': cinta.ticker_message.length > 950 }" x-text="cinta.ticker_message.length + ' de 1000 caracteres'">{{ intdiv(strlen(mb_convert_encoding((string) $tvConfig->ticker_message, 'UTF-16LE', 'UTF-8')), 2) }} de 1000 caracteres</span>
                <small x-show="errores.ticker_message" x-cloak x-text="errores.ticker_message"></small>
            </div>

            <div class="form-campo">
                <span class="fila-rotulo"><label for="cinta-vuelta">Duración de una vuelta</label> <b x-text="cinta.ticker_speed + ' s'">{{ (int) $tvConfig->ticker_speed }} s</b></span>
                <input id="cinta-vuelta" type="range" class="rango" min="10" max="120" step="5" value="{{ (int) $tvConfig->ticker_speed }}" x-model.number="cinta.ticker_speed" @change="vueltaPrevia++"
                       aria-describedby="cinta-vuelta-ayuda" :aria-valuetext="cinta.ticker_speed + ' segundos'">
                <span class="rango-extremos" aria-hidden="true"><span>Rápida</span><span>Lenta</span></span>
                <span id="cinta-vuelta-ayuda" class="form-ayuda">Tiempo que tarda el mensaje en cruzar la pantalla. Con mensajes largos, súbala para que se alcance a leer.</span>
                <small x-show="errores.ticker_speed" x-cloak x-text="errores.ticker_speed"></small>
            </div>

            <div class="bloque-tv__pie">
                <span class="estado-chip estado-chip--alerta" x-show="cintaCambiada()" x-cloak>Sin guardar</span>
                <span class="espaciador"></span>
                <button type="button" class="btn-secundario" disabled :disabled="!cintaCambiada() || guardandoCinta" @click="descartarCinta()">Descartar cambios</button>
                <button type="button" class="btn-primario" disabled :disabled="!cintaCambiada() || guardandoCinta" :aria-busy="guardandoCinta.toString()" @click="guardarCinta()" x-text="guardandoCinta ? 'Guardando…' : 'Guardar'">Guardar</button>
            </div>
            <p class="aviso" :class="estadoCinta && estadoCinta.error ? 'aviso--error' : 'aviso--ok'" :role="estadoCinta && estadoCinta.error ? 'alert' : 'status'" x-show="estadoCinta" x-cloak>
                <svg x-show="estadoCinta && estadoCinta.error" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7.5v5.5"/><path d="M12 16.5h.01"/></svg>
                <svg x-show="!(estadoCinta && estadoCinta.error)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8.5 12.25 2.5 2.5 4.75-5"/></svg>
                <span x-text="estadoCinta && estadoCinta.texto"></span>
            </p>
        </section>

        <!-- Lista de reproducción: la pieza elevada de la vista -->
        <section class="bloque-tv bloque-tv--lista pieza-elevada" aria-labelledby="t-lista">
            <header class="bloque-tv__cabeza">
                <div>
                    <h2 id="t-lista" class="bloque-tv__titulo">Lista de reproducción</h2>
                    <p class="bloque-tv__resumen" x-text="resumenLista()"><span class="esqueleto" style="width:14rem"></span></p>
                </div>
                <button type="button" class="btn-secundario" x-ref="abrirSubida" @click="abrirSubida()">
                    <svg class="tv-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 15V4"/><path d="m7 9 5-5 5 5"/><path d="M5 15v3a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-3"/></svg>
                    Subir archivo
                </button>
            </header>

            <div class="vacio" x-show="!lista.length" x-cloak>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2.5" y="7" width="19" height="13" rx="2"/><path d="m16.5 2.5-4.5 4.5-4.5-4.5"/></svg>
                <p class="vacio__titulo">Todavía no hay archivos</p>
                <p class="vacio__texto">Mientras la lista esté vacía, el TV muestra el logo del hospital.</p>
                <button type="button" class="enlace-panel" @click="abrirSubida()">Subir el primero</button>
            </div>

            <ol class="lista-medios" @dragover.prevent x-show="lista.length">
                {{-- Esqueleto: una fila con la forma de cada archivo mientras Alpine pinta la lista (init() los quita) --}}
                @foreach ($multimedia as $m)
                    <li class="medio" data-esqueleto aria-hidden="true">
                        <span class="medio__asa"></span>
                        <span class="esqueleto medio__esq-orden"></span>
                        <span class="esqueleto medio__miniatura"></span>
                        <span class="medio__info">
                            <span class="medio__nombre"><span class="esqueleto" style="width:60%"></span></span>
                            <span class="medio__meta"><span class="esqueleto" style="width:40%"></span></span>
                            @if (($m['formato'] ?? 'ok') !== 'ok')
                                <span class="medio__aviso medio__esq-aviso"><span class="esqueleto" style="width:85%"></span><span class="esqueleto" style="width:45%"></span></span>
                            @endif
                        </span>
                        <span class="interruptor--fila"><span class="esqueleto medio__esq-interruptor"></span></span>
                        <span class="medio__acciones medio__esq-acciones"></span>
                    </li>
                @endforeach
                <template x-for="(m, i) in lista" :key="m.id">
                    <li class="medio" :class="{ 'medio--pausado': !m.activo, 'medio--salta': m.formato === 'no', 'medio--nuevo': m.id === resaltado, 'medio--arrastrado': arrastrado === i, 'medio--antes': destino === i && arrastrado !== null && arrastrado > i, 'medio--despues': destino === i && arrastrado !== null && arrastrado < i }"
                        draggable="true" @dragstart="arrastrado = i; $event.dataTransfer.effectAllowed = 'move'" @dragend="arrastrado = null; destino = null"
                        @dragover.prevent="destino = i" @drop.prevent="soltar(i)">
                        <span class="medio__asa" title="Arrastre para cambiar el orden" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>
                        </span>
                        <span class="medio__orden" x-text="i + 1"></span>
                        <span class="medio__miniatura" :class="'medio__miniatura--' + m.tipo">
                            <svg x-show="m.tipo === 'video'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round" aria-hidden="true"><path d="M8 5.5v13l10.5-6.5z"/></svg>
                            <svg x-show="m.tipo !== 'video'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="10" r="1.5"/><path d="m21 15-4.5-4.5L8 19"/></svg>
                            <template x-if="m.tipo === 'imagen'"><img :src="m.url" alt="" loading="lazy" x-on:error="$el.remove()"></template>
                        </span>
                        <div class="medio__info">
                            <p class="medio__nombre" x-text="m.nombre" :title="m.nombre"></p>
                            <p class="medio__meta" x-text="meta(m)"></p>
                            <p class="medio__aviso" x-show="m.formato !== 'ok'">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.9 2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
                                <span x-text="m.formato === 'no'
                                    ? 'El TV no puede reproducir ' + m.extension.toUpperCase() + ' y lo salta. Súbalo en MP4.'
                                    : 'MOV solo se reproduce si viene en H.264; mejor súbalo en MP4.'"></span>
                            </p>
                        </div>
                        <label class="interruptor interruptor--fila" :title="m.activo ? 'Se muestra en el TV' : 'Pausado: no se muestra'">
                            <input type="checkbox" role="switch" :checked="m.activo" :aria-checked="m.activo.toString()" :aria-label="'Mostrar ' + m.nombre + ' en el TV'" @change="alternar(m)">
                            <span class="interruptor__pista" aria-hidden="true"></span>
                            <span class="interruptor__texto" x-text="m.activo ? 'En pantalla' : 'Pausado'"></span>
                        </label>
                        <div class="medio__acciones">
                            <button type="button" class="accion-icono" :disabled="i === 0" :aria-label="'Subir ' + m.nombre + ' en el orden'" title="Subir" @click="mover(i, -1)">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 15 6-6 6 6"/></svg>
                            </button>
                            <button type="button" class="accion-icono" :disabled="i === lista.length - 1" :aria-label="'Bajar ' + m.nombre + ' en el orden'" title="Bajar" @click="mover(i, 1)">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                            </button>
                            <button type="button" class="accion-icono accion-icono--peligro" :aria-label="'Eliminar ' + m.nombre" title="Eliminar" @click="porEliminar = m">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-12"/><path d="M9 7V4h6v3"/></svg>
                            </button>
                        </div>
                    </li>
                </template>
            </ol>

            <footer class="lista-pie" x-show="lista.length > 1">
                <p class="lista-pie__ayuda">Arrastre una fila o use las flechas para cambiar el orden; se guarda solo.</p>
                <p class="estado-orden" role="status" x-text="estadoOrden"></p>
            </footer>
        </section>
    </div>

    <!-- Modal: subir un archivo a la lista -->
    <div class="modal-panel" x-show="subida.abierta" x-cloak x-transition.opacity
         @click.self="!subida.enviando && cerrarSubida()" @keydown.escape.window="subida.abierta && !subida.enviando && cerrarSubida()">
        <form class="modal-panel__caja" role="dialog" aria-modal="true" aria-labelledby="t-subir" aria-describedby="d-subir" @submit.prevent="subir()" novalidate>
            <div class="modal-panel__cabeza">
                <h2 id="t-subir">Subir archivo</h2>
                <button type="button" class="accion-icono" aria-label="Cerrar" x-show="!subida.enviando" @click="cerrarSubida()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
            <div class="form-panel">
                <p id="d-subir" class="form-texto">Queda al final de la lista y el TV lo muestra desde su próxima ronda.</p>

                <label class="subida__zona" :class="{ 'subida__zona--encima': subida.encima, 'subida__zona--lista': subida.archivo, 'subida__zona--error': subida.errores.archivo }"
                       @dragover.prevent="subida.encima = true" @dragleave="subida.encima = false" @drop.prevent="subida.encima = false; elegirArchivo($event.dataTransfer.files[0])">
                    <input type="file" class="sr-only" x-ref="archivo" accept=".jpg,.jpeg,.png,.gif,.webp,.mp4,.webm" :disabled="subida.enviando" @change="elegirArchivo($event.target.files[0])">
                    <template x-if="!subida.archivo">
                        <span class="subida__invitacion">
                            <span class="subida__icono" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 15V4"/><path d="m7 9 5-5 5 5"/><path d="M5 15v3a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-3"/></svg>
                            </span>
                            <span>
                                <span class="subida__titulo">Arrastre aquí una imagen o un video</span>
                                <span class="subida__texto">o <span class="subida__enlace">elija un archivo</span> de este equipo</span>
                                <span class="subida__formatos">JPG, PNG, GIF o WEBP · MP4 o WEBM · hasta 500 MB</span>
                            </span>
                        </span>
                    </template>
                    <template x-if="subida.archivo">
                        <span class="subida__elegido">
                            <span class="medio__miniatura medio__miniatura--grande" :class="'medio__miniatura--' + subida.tipo">
                                <svg x-show="subida.tipo === 'video'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round" aria-hidden="true"><path d="M8 5.5v13l10.5-6.5z"/></svg>
                                <img x-show="subida.tipo === 'imagen'" :src="subida.vista" alt="">
                            </span>
                            <span class="subida__datos">
                                <span class="subida__nombre" x-text="subida.archivo.name"></span>
                                <span class="subida__meta" x-text="(subida.tipo === 'video' ? 'Video' : 'Imagen') + ' · ' + tamano(subida.archivo.size) + (subida.tipo === 'video' && subida.duracion ? ' · ' + duracion(subida.duracion) : '')"></span>
                                <span class="subida__enlace" x-show="!subida.enviando">Cambiar archivo</span>
                            </span>
                        </span>
                    </template>
                </label>
                <small class="form-error" role="alert" x-show="subida.errores.archivo" x-text="subida.errores.archivo"></small>

                <div class="form-panel__fila subida__campos">
                    <div class="form-campo">
                        <label for="subida-nombre">Nombre</label>
                        <input id="subida-nombre" type="text" class="campo" x-model="subida.nombre" maxlength="255" :disabled="subida.enviando" :aria-invalid="subida.errores.nombre ? 'true' : null">
                        <small x-show="subida.errores.nombre" x-text="subida.errores.nombre"></small>
                    </div>
                    <div class="form-campo" x-show="subida.tipo !== 'video'">
                        <label for="subida-segundos">Segundos en pantalla</label>
                        <input id="subida-segundos" type="number" class="campo" min="1" max="300" x-model.number="subida.duracion" :disabled="subida.enviando" aria-describedby="subida-segundos-ayuda" :aria-invalid="subida.errores.duracion ? 'true' : null">
                        <span id="subida-segundos-ayuda" class="form-ayuda">De 1 a 300.</span>
                        <small x-show="subida.errores.duracion" x-text="subida.errores.duracion"></small>
                    </div>
                    <div class="form-campo" x-show="subida.tipo === 'video'">
                        <span>Duración</span>
                        <span class="dato-fijo" x-text="subida.duracion ? duracion(subida.duracion) : 'Leyendo el video…'"></span>
                        <span class="form-ayuda">La del video; se lee del archivo.</span>
                    </div>
                </div>

                <div class="subida__progreso" x-show="subida.enviando">
                    <div class="progreso" role="progressbar" aria-label="Avance de la subida" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="subida.progreso">
                        <span class="progreso__barra" :style="'width:' + subida.progreso + '%'"></span>
                    </div>
                    <p class="form-ayuda" x-text="subida.progreso < 100 ? 'Subiendo… ' + subida.progreso + ' %' : 'Guardando en el servidor…'"></p>
                </div>
                <div class="aviso aviso--error" role="alert" x-show="subida.errores.general" x-cloak>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7.5v5.5"/><path d="M12 16.5h.01"/></svg>
                    <p x-text="subida.errores.general"></p>
                </div>

                <div class="modal-panel__pie">
                    <button type="button" class="btn-secundario" @click="cerrarSubida()" x-text="subida.enviando ? 'Cancelar subida' : 'Cancelar'"></button>
                    <button type="submit" class="btn-primario" :disabled="!subidaLista() || subida.enviando" :aria-busy="subida.enviando.toString()" x-text="subida.enviando ? 'Subiendo…' : 'Subir'"></button>
                </div>
            </div>
        </form>
    </div>

    <!-- Modal: confirmar la eliminación de un archivo -->
    <div class="modal-panel" x-show="porEliminar" x-cloak x-transition.opacity
         @click.self="!eliminando && (porEliminar = null)" @keydown.escape.window="porEliminar && !eliminando && (porEliminar = null)">
        <div class="modal-panel__caja modal-panel__caja--angosta" role="dialog" aria-modal="true" aria-labelledby="t-eliminar-medio" aria-describedby="d-eliminar-medio">
            <div class="modal-panel__cabeza">
                <h2 id="t-eliminar-medio">Eliminar archivo</h2>
                <button type="button" class="accion-icono" aria-label="Cerrar" :disabled="eliminando" @click="porEliminar = null">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
            <div class="form-panel">
                <template x-if="porEliminar">
                    <div class="ficha-medio">
                        <span class="medio__miniatura" :class="'medio__miniatura--' + porEliminar.tipo">
                            <svg x-show="porEliminar.tipo === 'video'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round" aria-hidden="true"><path d="M8 5.5v13l10.5-6.5z"/></svg>
                            <svg x-show="porEliminar.tipo !== 'video'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="10" r="1.5"/><path d="m21 15-4.5-4.5L8 19"/></svg>
                            <template x-if="porEliminar.tipo === 'imagen'"><img :src="porEliminar.url" alt="" x-on:error="$el.remove()"></template>
                        </span>
                        <span class="ficha-medio__datos">
                            <b x-text="porEliminar.nombre"></b>
                            <span x-text="meta(porEliminar)"></span>
                        </span>
                    </div>
                </template>
                <div class="aviso aviso--error" role="alert">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.7 18-8-14a2 2 0 0 0-3.4 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                    <p id="d-eliminar-medio">Se quita del TV y se borra del servidor. <b>No&nbsp;se&nbsp;puede&nbsp;deshacer.</b></p>
                </div>
                <p class="form-texto" x-show="porEliminar && porEliminar.activo">Si solo quiere sacarlo un tiempo, apague «En pantalla» en la lista.</p>
                <div class="modal-panel__pie">
                    <button type="button" class="btn-secundario" x-ref="cancelarEliminar" :disabled="eliminando" @click="porEliminar = null">Cancelar</button>
                    <button type="button" class="btn-peligro" :disabled="eliminando" :aria-busy="eliminando.toString()" @click="eliminar()" x-text="eliminando ? 'Eliminando…' : 'Eliminar'"></button>
                </div>
            </div>
        </div>
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
    const mensajeDe = (estado, datos, porDefecto) => estado === 419 ? 'La sesión expiró. Recargue la página e inténtelo de nuevo.'
        : estado === 413 ? 'El archivo es demasiado grande para el servidor.' : (primerError(datos) || (datos && datos.message) || porDefecto);
    const subidaVacia = () => ({ abierta: false, archivo: null, tipo: null, vista: null, nombre: '', duracion: 10, enviando: false, progreso: 0, encima: false, errores: {}, xhr: null });
    const sinMovimiento = () => window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    // Devuelve el foco a quien abrió un modal, si sigue en la página
    const volverA = el => { if (el && el.isConnected && typeof el.focus === 'function') el.focus(); };

    Alpine.data('configTv', (config, multimedia) => {
        // Fuera del estado reactivo: la animación de la vista previa y a quién devolver el foco
        let animPrevia = null, temporizadorPrevia = null, origenSubida = null, origenEliminar = null;

        return {
        cinta: { ...config },
        guardada: { ...config },
        guardandoCinta: false, estadoCinta: null, errores: {}, vueltaPrevia: 0,
        lista: multimedia,
        subida: subidaVacia(),
        porEliminar: null, eliminando: false,
        arrastrado: null, destino: null, resaltado: null, estadoOrden: '',

        init() {
            // Las filas de esqueleto que pintó Blade se van: desde aquí la lista la pinta Alpine
            this.$el.querySelectorAll('[data-esqueleto]').forEach(e => e.remove());

            // La vista previa se arma como la cinta del TV: el mensaje repetido sin hueco, a su mismo paso
            this.$watch('cinta.ticker_message', () => this.pintarPreviaLuego());
            this.$watch('cinta.ticker_speed', () => this.pintarPreviaLuego());
            this.$watch('vueltaPrevia', () => this.pintarPrevia());
            window.addEventListener('resize', () => this.pintarPreviaLuego());
            this.$nextTick(() => this.pintarPrevia());
            if (document.fonts && document.fonts.ready) document.fonts.ready.then(() => this.pintarPrevia());

            // Foco de los modales: entra al primer control y vuelve a quien lo abrió
            this.$watch('porEliminar', v => {
                if (v) { origenEliminar = document.activeElement; this.$nextTick(() => this.$refs.cancelarEliminar && this.$refs.cancelarEliminar.focus()); }
                else { volverA(origenEliminar); origenEliminar = null; }
            });
        },

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
        meta(m) { return (m.tipo === 'video' ? 'Video' : 'Imagen') + ' · ' + this.duracion(m.duracion) + ' · ' + this.tamano(m.tamano); },
        resumenLista() {
            const al = this.lista.filter(m => m.activo && m.formato !== 'no');
            if (!this.lista.length) return 'Sin archivos';
            if (!al.length) return 'Nada en pantalla: el TV muestra el logo del hospital';
            return al.length + ' en pantalla · la ronda completa dura ' + this.duracion(al.reduce((s, m) => s + (m.duracion || 0), 0));
        },

        // ---- vista previa de la cinta (solo presentación)
        pintarPreviaLuego() { clearTimeout(temporizadorPrevia); temporizadorPrevia = setTimeout(() => this.pintarPrevia(), 120); },
        pintarPrevia() {
            const pista = this.$refs.pista, contenido = this.$refs.previa;
            if (!pista || !contenido) return;
            // Conserva el punto del recorrido para que al cambiar la velocidad no salte al inicio
            let fase = 0;
            if (animPrevia) {
                const t = animPrevia.effect && animPrevia.effect.getTiming ? Number(animPrevia.effect.getTiming().duration) : 0;
                if (t) fase = ((animPrevia.currentTime || 0) % t) / t;
                animPrevia.cancel(); animPrevia = null;
            }
            const mensaje = (this.cinta.ticker_message || '').trim() || 'Escriba el mensaje de la cinta…';
            contenido.textContent = '';
            const vuelta = document.createElement('span');
            vuelta.className = 'cinta-previa__vuelta';
            const texto = document.createElement('span');
            texto.className = 'cinta-previa__texto';
            texto.textContent = mensaje;
            const punto = document.createElement('span');
            punto.className = 'cinta-previa__punto';
            vuelta.append(texto, punto);
            contenido.appendChild(vuelta);
            const ancho = pista.clientWidth, largo = texto.getBoundingClientRect().width, paso = vuelta.getBoundingClientRect().width;
            if (!ancho || !paso || sinMovimiento() || typeof contenido.animate !== 'function') return;
            for (let i = Math.ceil(ancho / paso); i > 0; i--) contenido.appendChild(vuelta.cloneNode(true));
            // El mismo cálculo del TV: cruza el ancho más el mensaje en el 85 % de la duración de una vuelta
            const pxPorSegundo = (ancho + largo) / (0.85 * (Number(this.cinta.ticker_speed) || 30));
            const duracion = Math.max(paso / pxPorSegundo, 1) * 1000;
            animPrevia = contenido.animate([{ transform: 'translateX(0)' }, { transform: 'translateX(' + (-paso) + 'px)' }],
                { duration: duracion, iterations: Infinity, easing: 'linear' });
            animPrevia.currentTime = fase * duracion;
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
                .catch(() => { this.estadoCinta = { texto: 'No hay conexión con el servidor. Inténtelo de nuevo.', error: true }; })
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
                    sileo.success({ title: m.activo ? 'Vuelve al TV' : 'Pausado en el TV', description: m.nombre });
                })
                .catch(e => { m.activo = !m.activo; sileo.error({ title: 'No se cambió el estado', description: e instanceof TypeError ? 'No hay conexión con el servidor.' : e.message }); });
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
                    .catch(e => { this.estadoOrden = ''; sileo.error({ title: 'No se guardó el orden', description: (e instanceof TypeError ? 'No hay conexión con el servidor.' : e.message) + ' Recargue para ver el orden real.' }); });
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
                    sileo.success({ title: 'Archivo eliminado' });
                })
                .catch(e => { sileo.error({ title: 'No se eliminó el archivo', description: e instanceof TypeError ? 'No hay conexión con el servidor.' : e.message }); })
                .finally(() => { this.eliminando = false; });
        },

        // ---- subida
        abrirSubida() {
            origenSubida = document.activeElement;
            this.subida = { ...subidaVacia(), abierta: true };
            this.$nextTick(() => this.$refs.archivo && this.$refs.archivo.focus());
        },
        cerrarSubida() {
            if (this.subida.xhr) this.subida.xhr.abort();
            if (this.subida.vista) URL.revokeObjectURL(this.subida.vista);
            this.subida = subidaVacia();
            volverA(origenSubida); origenSubida = null;
        },
        elegirArchivo(archivo) {
            if (!archivo) return;
            const ext = (archivo.name.split('.').pop() || '').toLowerCase();
            const s = this.subida;
            if (s.vista) URL.revokeObjectURL(s.vista);
            Object.assign(s, { archivo: null, tipo: null, vista: null, errores: {} });
            if (!IMAGENES.includes(ext) && !VIDEOS.includes(ext)) {
                s.errores = { archivo: ext === 'avi' || ext === 'mov'
                    ? 'El TV no reproduce ' + ext.toUpperCase() + ' de forma fiable. Conviértalo a MP4 y vuelva a intentarlo.'
                    : 'Formato no admitido. Use JPG, PNG, GIF o WEBP para imágenes y MP4 o WEBM para videos.' };
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
            v.onerror = () => { s.archivo = null; s.errores = { archivo: 'Este video no se puede reproducir en el navegador, así que el TV tampoco podrá. Conviértalo a MP4 (H.264).' }; };
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
                    sileo.success({ title: 'Archivo subido', description: 'El TV lo mostrará en su próxima ronda.' });
                    return;
                }
                const e = datos.errors || {};
                s.errores = Object.fromEntries(Object.entries(e).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]));
                if (!Object.keys(s.errores).length) s.errores = { general: mensajeDe(xhr.status, datos, 'No se pudo subir el archivo (error ' + xhr.status + ').') };
            };
            xhr.onerror = () => { s.enviando = false; s.xhr = null; s.errores = { general: 'Se perdió la conexión durante la subida. Inténtelo de nuevo.' }; };
            xhr.ontimeout = () => { s.enviando = false; s.xhr = null; s.errores = { general: 'La subida tardó demasiado. Pruebe con un archivo más liviano.' }; };
            xhr.open('POST', URL_SUBIR);
            Object.entries(CABECERAS()).forEach(([k, v]) => xhr.setRequestHeader(k, v));
            xhr.timeout = 600000;
            xhr.send(cuerpo);
        },
        };
    });
});
</script>

@push('estilos')
<style>
/* ===== Config TV «La sala» (2026-11): la cinta a la izquierda, la lista de reproducción elevada a la derecha ===== */
.tv-vista { font-variant-numeric: tabular-nums; }
.tv-ico { width: 1rem; height: 1rem; flex: none; }
.tv-rejilla { display: grid; grid-template-columns: minmax(0, 5fr) minmax(0, 7fr); gap: 1.5rem; align-items: start; }
@media (max-width: 1099px) { .tv-rejilla { grid-template-columns: minmax(0, 1fr); } }

.bloque-tv { display: flex; flex-direction: column; gap: 1.125rem; min-width: 0; padding: 1.25rem 1.25rem 1.125rem; }
/* La única pieza elevada de la vista (.pieza-elevada del layout); aquí solo su ritmo interno */
.bloque-tv--lista { gap: .875rem; padding: 1.25rem 1.125rem 1rem; }
.bloque-tv__cabeza { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
.bloque-tv__titulo { margin: 0; font-size: 1rem; font-weight: 650; color: var(--tinta); }
.bloque-tv__resumen { margin: .125rem 0 0; font-size: .8125rem; color: var(--tinta-suave); text-wrap: pretty; }
/* El esqueleto del resumen va en línea, para que el renglón tenga ya su alto real */
.bloque-tv__resumen .esqueleto { display: inline-block; vertical-align: middle; }
.bloque-tv__pie { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; padding-top: .25rem; }
.espaciador { flex: 1; }

/* Interruptor: esquinas suaves (no píldora), azul solo cuando está encendido */
.interruptor {
    position: relative; display: inline-flex; align-items: center; gap: .5rem; min-height: 2.5rem; flex: none;
    font-size: .8125rem; font-weight: 600; color: var(--tinta-2); cursor: pointer; white-space: nowrap; user-select: none;
}
.interruptor input { position: absolute; opacity: 0; width: 1px; height: 1px; margin: 0; pointer-events: none; }
.interruptor__pista { position: relative; flex: none; width: 2.25rem; height: 1.25rem; border-radius: .375rem; background: var(--borde); transition: background-color .15s ease-out; }
.interruptor__pista::after {
    content: ''; position: absolute; top: 3px; left: 3px; width: .875rem; height: .875rem; border-radius: 3px; background: #fff;
    box-shadow: 0 1px 1px rgba(15, 39, 71, .2); transition: transform .15s ease-out;
}
.interruptor:hover .interruptor__pista { background: var(--borde-hover); }
.interruptor input:checked + .interruptor__pista { background: var(--azul); }
.interruptor:hover input:checked + .interruptor__pista { background: var(--azul-hover); }
.interruptor input:checked + .interruptor__pista::after { transform: translateX(1rem); }
.interruptor input:focus-visible + .interruptor__pista { outline: 2px solid var(--azul); outline-offset: 2px; }

/* Vista previa de la cinta: la misma pieza del TV (rótulo marino, azul plano, mensaje que se repite con un punto) */
.cinta-previa-bloque { display: flex; flex-direction: column; gap: .5rem; }
.cinta-previa {
    display: flex; align-items: stretch; height: 2.75rem; overflow: hidden; border-radius: .625rem; background: var(--azul);
    transition-property: filter, opacity; transition-duration: .2s; transition-timing-function: ease-out;
}
.cinta-previa--apagada { filter: grayscale(1); opacity: .4; }
.cinta-previa__rotulo {
    flex: none; display: flex; align-items: center; padding: 0 .875rem; background: #072449; color: #fff;
    font-size: .6875rem; font-weight: 800; letter-spacing: .18em; text-transform: uppercase;
}
.cinta-previa__pista { position: relative; flex: 1 1 auto; min-width: 0; overflow: hidden; display: flex; align-items: center; }
.cinta-previa__pista::before, .cinta-previa__pista::after { content: ''; position: absolute; top: 0; bottom: 0; z-index: 1; width: 1.25rem; pointer-events: none; }
.cinta-previa__pista::before { left: 0; background: linear-gradient(90deg, var(--azul), rgba(6, 75, 158, 0)); }
.cinta-previa__pista::after { right: 0; background: linear-gradient(270deg, var(--azul), rgba(6, 75, 158, 0)); }
.cinta-previa__contenido { display: inline-flex; padding-left: .875rem; white-space: nowrap; will-change: transform; }
.cinta-previa__vuelta { display: inline-flex; align-items: center; }
.cinta-previa__texto { color: #fff; font-size: 1.0625rem; font-weight: 500; }
.cinta-previa__punto { flex: none; width: 5px; height: 5px; margin: 0 1.5rem; border-radius: 50%; background: #7fb0ec; }

.form-campo > label { cursor: default; }
.campo-cinta { min-height: 6.5rem; font-size: .875rem; line-height: 1.55; }
.contador { align-self: flex-end; font-size: .75rem; font-weight: 500; color: var(--tinta-tenue); font-variant-numeric: tabular-nums; }
.contador--limite { color: var(--alerta-tinta); font-weight: 600; }
.fila-rotulo { display: flex; justify-content: space-between; align-items: baseline; gap: 1rem; }
.fila-rotulo b { font-weight: 650; color: var(--tinta); font-variant-numeric: tabular-nums; }
.rango { width: 100%; height: 1.5rem; margin: 0; accent-color: var(--azul); cursor: pointer; }
.rango:focus-visible { outline: 2px solid var(--azul); outline-offset: 2px; border-radius: .375rem; }
.rango-extremos { display: flex; justify-content: space-between; margin-top: -.25rem; font-size: .75rem; font-weight: 400; color: var(--tinta-tenue); }

/* Lista de reproducción: filas planas como los turnos de la Cola */
.lista-medios { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: .375rem; }
.medio {
    position: relative; display: grid; grid-template-columns: .875rem 1.25rem 5.5rem minmax(0, 1fr) auto auto; align-items: center; column-gap: .75rem;
    padding: .5rem .5rem .5rem .375rem; border-radius: .75rem; background: #f5f8fc;
    transition: background-color .15s ease-out;
}
.medio:hover { background: #eef3fa; }
.medio--pausado { background: #fff; box-shadow: inset 0 0 0 1px var(--linea); }
.medio--pausado:hover { background: #fafcfe; }
.medio--pausado .medio__miniatura, .medio--pausado .medio__nombre, .medio--pausado .medio__orden { opacity: .55; }
.medio--pausado .medio__miniatura { filter: grayscale(1); }
.medio--salta { background: #fff; box-shadow: inset 0 0 0 1px var(--alerta-borde); }
.medio--nuevo { background: var(--azul-claro); transition: background-color 1.5s ease-out; }
.medio--arrastrado { opacity: .5; }
.medio--antes::before, .medio--despues::after { content: ''; position: absolute; left: .375rem; right: .375rem; height: 2px; border-radius: 1px; background: var(--azul); }
.medio--antes::before { top: -.25rem; }
.medio--despues::after { bottom: -.25rem; }
.medio__asa { display: grid; place-items: center; color: var(--icono); opacity: .6; cursor: grab; }
.medio__asa svg { width: .875rem; height: .875rem; }
.medio:hover .medio__asa { opacity: 1; }
.medio__orden { font-size: .9375rem; font-weight: 650; color: var(--tinta-suave); text-align: center; font-variant-numeric: tabular-nums; }
.medio__miniatura {
    position: relative; display: grid; place-items: center; width: 5.5rem; aspect-ratio: 16 / 9; border-radius: .5rem; overflow: hidden;
    background: #e3e9f2; color: var(--icono);
}
.medio__miniatura svg { width: 1.25rem; height: 1.25rem; }
.medio__miniatura img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.medio__miniatura--video { background: var(--tinta); color: #fff; }
.medio__miniatura--video svg { width: 1.125rem; height: 1.125rem; }
.medio__miniatura--grande { width: 7rem; }
.medio__info { min-width: 0; }
.medio__nombre { margin: 0; font-size: .875rem; font-weight: 600; color: var(--tinta); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.medio__meta { margin: .125rem 0 0; font-size: .78125rem; color: var(--tinta-tenue); font-variant-numeric: tabular-nums; }
.medio__aviso { display: flex; align-items: flex-start; gap: .375rem; margin: .3125rem 0 0; font-size: .78125rem; font-weight: 500; line-height: 1.4; color: var(--alerta-tinta); text-wrap: pretty; }
.medio__aviso svg { width: .9375rem; height: .9375rem; flex: none; margin-top: .0625rem; }
.interruptor--fila { min-width: 7.25rem; }
.interruptor--fila .interruptor__texto { font-weight: 500; color: var(--tinta-suave); }
.medio__acciones { display: flex; gap: .125rem; }
.medio__acciones svg { width: 1rem; height: 1rem; }
.medio__acciones .accion-icono:disabled { opacity: .3; cursor: default; }
.medio__acciones .accion-icono:disabled:hover { background: transparent; color: var(--icono); }

.lista-pie { display: flex; align-items: baseline; justify-content: space-between; gap: 1rem; flex-wrap: wrap; padding: .25rem .375rem 0; }
.lista-pie__ayuda, .estado-orden { margin: 0; font-size: .78125rem; color: var(--tinta-tenue); }
.estado-orden { font-weight: 500; color: var(--tinta-suave); }

/* Esqueleto de una fila: la misma rejilla y el mismo alto que la fila real (la miniatura 16/9 manda) */
.medio[data-esqueleto] { cursor: default; }
.medio[data-esqueleto]:hover { background: #f5f8fc; }
.medio__esq-orden { width: 1rem; height: .875rem; justify-self: center; }
.medio[data-esqueleto] .medio__miniatura { height: auto; border-radius: .5rem; }
/* Cada barra va dentro del renglón real (nombre, meta, aviso), así la fila mide lo mismo que la que la reemplaza */
.medio[data-esqueleto] .medio__nombre, .medio[data-esqueleto] .medio__meta { display: block; }
.medio[data-esqueleto] .medio__info .esqueleto { display: inline-block; vertical-align: middle; height: .6875rem; }
.medio[data-esqueleto] .medio__nombre .esqueleto { height: .875rem; }
.medio__esq-aviso { flex-direction: column; gap: 0; }
.medio__esq-aviso .esqueleto { margin: .203125rem 0; }
.medio__esq-interruptor { width: 2.25rem; height: 1.25rem; border-radius: .375rem; }
.medio__esq-acciones { width: 6.25rem; height: 2rem; }

/* Modal de subida */
.subida__zona {
    position: relative; display: flex; align-items: center; min-height: 6.5rem; padding: 1rem 1.125rem; border-radius: .75rem;
    background: #f5f8fc; box-shadow: inset 0 0 0 1px var(--linea); cursor: pointer;
    transition-property: background-color, box-shadow; transition-duration: .15s; transition-timing-function: ease-out;
}
.subida__zona:hover { box-shadow: inset 0 0 0 1px var(--borde); }
.subida__zona--encima, .subida__zona--encima:hover { background: var(--azul-claro); box-shadow: inset 0 0 0 2px var(--azul); }
.subida__zona--lista { background: #fff; }
.subida__zona--error { box-shadow: inset 0 0 0 1px var(--error); }
.subida__zona:has(input:focus-visible) { outline: 2px solid var(--azul); outline-offset: 2px; }
.subida__invitacion, .subida__elegido { display: flex; align-items: center; gap: .875rem; min-width: 0; }
.subida__icono { flex: none; display: grid; place-items: center; width: 2.75rem; height: 2.75rem; border-radius: .625rem; background: #fff; box-shadow: var(--roce), inset 0 0 0 1px var(--linea); color: var(--tinta-2); }
.subida__icono svg { width: 1.25rem; height: 1.25rem; }
.subida__titulo { display: block; font-size: .875rem; font-weight: 600; color: var(--tinta); }
.subida__texto { display: block; margin-top: .0625rem; font-size: .8125rem; color: var(--tinta-suave); }
.subida__enlace { color: var(--azul); font-weight: 600; text-decoration: underline; text-underline-offset: 3px; text-decoration-thickness: 1px; }
.subida__formatos { display: block; margin-top: .375rem; font-size: .75rem; color: var(--tinta-tenue); }
.subida__datos { display: flex; flex-direction: column; align-items: flex-start; gap: .125rem; min-width: 0; }
.subida__nombre { max-width: 100%; font-size: .875rem; font-weight: 600; color: var(--tinta); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.subida__meta { font-size: .78125rem; color: var(--tinta-tenue); font-variant-numeric: tabular-nums; }
.subida__datos .subida__enlace { margin-top: .25rem; font-size: .8125rem; }
.subida__campos { grid-template-columns: minmax(0, 1fr) 10rem; align-items: start; }
@media (max-width: 639px) { .subida__campos { grid-template-columns: minmax(0, 1fr); } }
.dato-fijo { display: flex; align-items: center; height: 2.5rem; font-size: .875rem; font-weight: 500; color: var(--tinta); font-variant-numeric: tabular-nums; }
.subida__progreso { display: flex; flex-direction: column; gap: .375rem; }
.progreso { height: .375rem; border-radius: 2px; background: #e3e9f2; overflow: hidden; }
.progreso__barra { display: block; height: 100%; background: var(--azul); transition: width .2s ease-out; }

/* Modal de eliminar: la ficha del archivo que se va */
.ficha-medio { display: flex; align-items: center; gap: .875rem; padding: .625rem; border-radius: .75rem; background: #f5f8fc; }
.ficha-medio__datos { display: flex; flex-direction: column; gap: .125rem; min-width: 0; font-size: .78125rem; color: var(--tinta-tenue); font-variant-numeric: tabular-nums; }
.ficha-medio__datos b { font-size: .875rem; font-weight: 600; color: var(--tinta); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* Angosto (tableta o celular): la fila se parte en dos renglones */
@media (max-width: 639px) {
    .bloque-tv__cabeza { flex-wrap: wrap; }
    .medio { grid-template-columns: 1.25rem 4.5rem minmax(0, 1fr) auto; row-gap: .375rem; }
    .medio__asa { display: none; }
    .medio__miniatura { width: 4.5rem; }
    .medio__info { grid-column: 3 / 5; }
    .interruptor--fila { grid-column: 2 / 4; grid-row: 2; min-width: 0; }
    .medio__acciones { grid-column: 4; grid-row: 2; }
}
@media (prefers-reduced-motion: reduce) {
    .interruptor__pista, .interruptor__pista::after, .cinta-previa, .medio, .subida__zona, .progreso__barra { transition: none; }
}
</style>
@endpush
@endsection
