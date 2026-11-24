@extends('layouts.admin')

@section('title', 'Soporte')

@if ($contacto)
@section('acciones_pagina')
    <p class="soporte-urgencias">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 4h3.5l1.5 4-2 1.5a11 11 0 0 0 6.5 6.5l1.5-2 4 1.5V19a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z"/></svg>
        <span>Urgencias</span> <b>{{ $contacto }}</b>
    </p>
@endsection
@endif

@section('content')
{{-- Soporte: a la izquierda la solicitud nueva (superficie plana); a la derecha, en la columna lateral elevada (como la Cola
     del Inicio), las registradas con número y estado. Se guardan en el servidor (App\Services\SoporteService); antes solo
     quedaban en laravel.log y nadie las veía. --}}
@php
    // Lo que Blade ya sabe se escribe de una vez, para que nada salga vacío ni salte mientras arranca Alpine
    $nSolicitudes = count($solicitudes);
    $nAbiertas = count(array_filter($solicitudes, fn ($s) => ($s['estado'] ?? null) !== 'resuelta'));
    $filtrosSoporte = ['abiertas' => ['Abiertas', $nAbiertas], 'resueltas' => ['Resueltas', $nSolicitudes - $nAbiertas], 'todas' => ['Todas', $nSolicitudes]];
    $urgenciasSoporte = ['baja' => 'Baja', 'media' => 'Media', 'alta' => 'Alta', 'critica' => 'Crítica'];
@endphp
<div class="soporte-vista" x-data="soporteVista(@js($solicitudes), @js($lugares), @js($donde))">

    <!-- Detalle de una solicitud, con su estado -->
    <div class="envoltorio-modal" @keydown.escape.window="cerrarDetalle()">
        <div class="modal-panel" x-show="detalle" x-cloak x-transition.opacity @click.self="cerrarDetalle()">
            <div class="modal-panel__caja detalle-caja" role="dialog" aria-modal="true" aria-labelledby="t-detalle" @keydown.tab="atraparFoco($event)">
                <template x-if="detalle">
                    <div class="detalle-marco">
                        <div class="modal-panel__cabeza detalle-cabeza">
                            <h2 id="t-detalle"><span class="detalle-numero" x-text="detalle.numero"></span> <span x-text="detalle.asunto"></span></h2>
                            <button type="button" id="detalle-cerrar" class="accion-icono" aria-label="Cerrar" @click="cerrarDetalle()">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true"><path d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <div class="detalle-cuerpo">
                            <dl class="detalle-datos">
                                <div><dt>Tipo</dt><dd x-text="textoTipo(detalle.tipo)"></dd></div>
                                <div><dt>Urgencia</dt><dd :class="'urgencia--' + detalle.urgencia" x-text="textoUrgencia(detalle.urgencia)"></dd></div>
                                <div><dt>Dónde</dt><dd x-text="detalle.donde_texto || 'Sin indicar'"></dd></div>
                                <div><dt>Registrada</dt><dd x-text="fecha(detalle.creada)"></dd></div>
                                <div class="detalle-datos__ancho"><dt>Reportó</dt>
                                    <dd><span x-text="persona(detalle)"></span><span class="detalle-correo" x-show="detalle.usuario.correo" x-text="detalle.usuario.correo"></span></dd>
                                </div>
                            </dl>

                            <section class="detalle-bloque">
                                <h3>Descripción</h3>
                                <p x-text="detalle.descripcion"></p>
                            </section>
                            <section class="detalle-bloque" x-show="detalle.pasos">
                                <h3>Pasos para que pase</h3>
                                <p x-text="detalle.pasos"></p>
                            </section>
                            <section class="detalle-bloque" x-show="detalle.esperado">
                                <h3>Qué se esperaba</h3>
                                <p x-text="detalle.esperado"></p>
                            </section>

                            <figure class="detalle-captura" x-show="detalle.url_adjunto">
                                <a class="detalle-captura__marco" :href="detalle.url_adjunto" target="_blank" rel="noopener" tabindex="-1" aria-hidden="true">
                                    <img :src="detalle.url_adjunto" alt="" loading="lazy">
                                </a>
                                <figcaption>
                                    <a class="enlace-panel detalle-captura__enlace" :href="detalle.url_adjunto" target="_blank" rel="noopener">
                                        Abrir la captura en tamaño completo
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 4h6v6M20 4l-9 9M18 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4"/></svg>
                                        <span class="sr-only">(se abre en otra pestaña)</span>
                                    </a>
                                </figcaption>
                            </figure>
                            <p class="detalle-navegador" x-show="detalle.navegador" x-text="'Navegador: ' + detalle.navegador"></p>

                            <div class="detalle-estado">
                                <div class="form-campo">
                                    <span id="rotulo-estado">Estado</span>
                                    <div class="segmentado" role="radiogroup" aria-labelledby="rotulo-estado">
                                        <template x-for="e in estados" :key="e.clave">
                                            <button type="button" role="radio" :aria-checked="(edicion.estado === e.clave).toString()" @click="edicion.estado = e.clave">
                                                <span class="punto" :class="'punto--' + chipEstado(e.clave)" aria-hidden="true"></span><span x-text="e.rotulo"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                                <label class="form-campo"><span>Respuesta o nota <span class="form-opcional">(opcional)</span></span>
                                    <textarea class="campo campo--area" rows="3" maxlength="1000" x-model="edicion.nota" placeholder="Qué se hizo, o qué falta"></textarea>
                                </label>
                                <p class="form-ayuda" x-show="detalle.actualizada" x-text="'Última actualización: ' + fecha(detalle.actualizada) + (detalle.actualizada_por ? ' por ' + detalle.actualizada_por : '')"></p>
                                <p class="aviso aviso--error" role="alert" x-show="errorDetalle">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7.5v5.5M12 16.5v.01"/></svg>
                                    <span x-text="errorDetalle"></span>
                                </p>
                            </div>
                        </div>

                        <div class="modal-panel__pie detalle-pie">
                            <button type="button" class="btn-secundario" @click="cerrarDetalle()">Cerrar</button>
                            <button type="button" class="btn-primario" :disabled="guardandoEstado || !estadoCambiado()" :aria-busy="guardandoEstado.toString()" @click="guardarEstado()" x-text="guardandoEstado ? 'Guardando…' : 'Guardar estado'"></button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <div class="soporte-rejilla vista-lateral">
        <!-- Solicitud nueva: superficie plana -->
        <section class="superficie soporte-nueva" aria-labelledby="t-nueva">
            <h2 id="t-nueva" class="soporte-titulo">Nueva solicitud</h2>
            <p class="soporte-sub">Cuéntenos qué pasó o qué cambiaría. Queda con un número y aquí puede seguir su estado.</p>

            <form class="form-soporte" @submit.prevent="enviar()" @paste="pegar($event)" novalidate>
                <div class="form-campo">
                    <span id="rotulo-tipo">¿Qué pasa?</span>
                    <div class="tipos" role="radiogroup" aria-labelledby="rotulo-tipo">
                        <button type="button" class="tipo" role="radio" aria-checked="true" :aria-checked="(f.tipo === 'error').toString()" @click="f.tipo = 'error'">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 4.2 2.8 17.2A2 2 0 0 0 4.5 20h15a2 2 0 0 0 1.7-2.8L13.7 4.2a2 2 0 0 0-3.4 0Z"/><path d="M12 9.5v4M12 16.5v.01"/></svg>
                            <span class="tipo__nombre">Algo falla</span>
                            <span class="tipo__ayuda">Un error o un bloqueo</span>
                        </button>
                        <button type="button" class="tipo" role="radio" aria-checked="false" :aria-checked="(f.tipo === 'mejora').toString()" @click="f.tipo = 'mejora'">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.5 18h5M10.5 21h3"/><path d="M12 3a6 6 0 0 0-3.6 10.8c.6.5 1.1 1.2 1.1 2V16h5v-.2c0-.8.5-1.5 1.1-2A6 6 0 0 0 12 3Z"/></svg>
                            <span class="tipo__nombre">Proponer un cambio</span>
                            <span class="tipo__ayuda">Una mejora al trabajo</span>
                        </button>
                        <button type="button" class="tipo" role="radio" aria-checked="false" :aria-checked="(f.tipo === 'otro').toString()" @click="f.tipo = 'otro'">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9.6 9.4a2.5 2.5 0 1 1 3.3 2.4c-.5.2-.9.6-.9 1.2v.4M12 16.5v.01"/></svg>
                            <span class="tipo__nombre">Otra consulta</span>
                            <span class="tipo__ayuda">Una duda o un pedido</span>
                        </button>
                    </div>
                </div>

                <div class="fila-urgencia">
                    <div class="form-campo">
                        <div class="rotulo-fila rotulo-fila--junto">
                            <span id="rotulo-urgencia">Urgencia</span>
                            <span id="ayuda-urgencia" class="form-ayuda urgencia-ayuda" :class="{ 'is-critica': f.urgencia === 'critica' }" x-text="urgencias.find(u => u.clave === f.urgencia).ayuda">Molesta, pero se puede seguir trabajando.</span>
                        </div>
                        <div class="segmentado" role="radiogroup" aria-labelledby="rotulo-urgencia" aria-describedby="ayuda-urgencia">
                            @foreach ($urgenciasSoporte as $clave => $rotulo)
                                <button type="button" role="radio" class="urg--{{ $clave }}" aria-checked="{{ $clave === 'media' ? 'true' : 'false' }}" :aria-checked="(f.urgencia === '{{ $clave }}').toString()" @click="f.urgencia = '{{ $clave }}'">{{ $rotulo }}</button>
                            @endforeach
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
                <p class="aviso aviso--alerta" role="alert" x-show="f.urgencia === 'critica'" x-cloak>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 4.2 2.8 17.2A2 2 0 0 0 4.5 20h15a2 2 0 0 0 1.7-2.8L13.7 4.2a2 2 0 0 0-3.4 0Z"/><path d="M12 9.5v4M12 16.5v.01"/></svg>
                    <span>
                        @if ($contacto)
                            Si no se pueden sacar, llamar o mostrar turnos, además de enviarla avise de inmediato: <b>{{ $contacto }}</b>.
                        @else
                            Si no se pueden sacar, llamar o mostrar turnos, además de enviarla avise de inmediato a Innovación y Desarrollo.
                        @endif
                    </span>
                </p>

                <div class="form-campo">
                    <label for="sop-asunto">Asunto</label>
                    <input type="text" id="sop-asunto" class="campo" maxlength="255" x-model="f.asunto" x-ref="asunto"
                           :aria-invalid="errores.asunto ? 'true' : null" aria-describedby="sop-asunto-error"
                           placeholder="Ej.: El TV no muestra el turno llamado" :placeholder="f.tipo === 'error' ? 'Ej.: El TV no muestra el turno llamado' : 'Resumen en una línea'">
                    <small id="sop-asunto-error" x-show="errores.asunto" x-text="errores.asunto"></small>
                </div>
                <div class="form-campo">
                    <div class="rotulo-fila">
                        <label for="sop-descripcion">Descripción</label>
                        <span id="sop-descripcion-n" class="contador" x-text="f.descripcion.length + ' / 2000'">0 / 2000</span>
                    </div>
                    <textarea id="sop-descripcion" class="campo campo--area" rows="3" maxlength="2000" x-model="f.descripcion"
                              :aria-invalid="errores.descripcion ? 'true' : null" aria-describedby="sop-descripcion-error sop-descripcion-n"
                              placeholder="Qué pasó, a quién le afecta y desde cuándo." :placeholder="f.tipo === 'error' ? 'Qué pasó, a quién le afecta y desde cuándo.' : f.tipo === 'mejora' ? 'Qué le gustaría que cambiara y para qué serviría.' : 'Cuéntenos su consulta.'"></textarea>
                    <small id="sop-descripcion-error" x-show="errores.descripcion" x-text="errores.descripcion"></small>
                </div>

                <div class="fila-pasos" x-show="f.tipo === 'error'">
                    <label class="form-campo"><span>Pasos para que pase <span class="form-opcional">(opcional)</span></span>
                        <textarea class="campo campo--area" rows="2" maxlength="1000" x-model="f.pasos" placeholder="1. Entrar a Turnos&#10;2. Buscar C-093"></textarea>
                        <small x-show="errores.pasos_reproducir" x-text="errores.pasos_reproducir"></small>
                    </label>
                    <label class="form-campo"><span>¿Qué esperaba que pasara? <span class="form-opcional">(opcional)</span></span>
                        <textarea class="campo campo--area" rows="2" maxlength="1000" x-model="f.esperado"></textarea>
                        <small x-show="errores.comportamiento_esperado" x-text="errores.comportamiento_esperado"></small>
                    </label>
                </div>

                <!-- Captura: elegir, arrastrar o pegar (Ctrl+V) -->
                <div class="form-campo"><span>Captura de pantalla <span class="form-opcional">(opcional)</span></span>
                    <label class="captura" :class="{ 'captura--encima': encima }" x-show="!f.captura"
                           @dragover.prevent="encima = true" @dragleave="encima = false" @drop.prevent="encima = false; elegirCaptura($event.dataTransfer.files[0])">
                        <input type="file" class="sr-only" accept=".jpg,.jpeg,.png,.webp" @change="elegirCaptura($event.target.files[0])">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2.5"/><circle cx="8.75" cy="9.25" r="1.5"/><path d="m21 15.5-4.6-4.6a1.5 1.5 0 0 0-2.1 0L5.5 20"/></svg>
                        <span class="captura__texto">
                            <span>Pegue una captura con <kbd>Ctrl</kbd>+<kbd>V</kbd>, arrástrela aquí o <span class="captura__elegir">elija una imagen</span></span>
                            <span class="captura__nota">JPG, PNG o WEBP · hasta 5 MB</span>
                        </span>
                    </label>
                    <div class="captura-elegida" x-show="f.captura" x-cloak>
                        <img :src="f.vista" alt="Vista previa de la captura">
                        <span class="captura-elegida__datos">
                            <span class="captura-elegida__nombre" x-text="f.captura ? f.captura.name : ''"></span>
                            <span class="captura-elegida__peso" x-text="f.captura ? kb(f.captura.size) : ''"></span>
                        </span>
                        <button type="button" class="btn-secundario btn-chico" @click="quitarCaptura()">Quitar</button>
                    </div>
                    <small x-show="errores.captura" x-text="errores.captura"></small>
                </div>

                <p class="aviso aviso--error" role="alert" x-show="errores.general" x-cloak>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7.5v5.5M12 16.5v.01"/></svg>
                    <span x-text="errores.general"></span>
                </p>
                <p class="aviso aviso--ok" role="status" x-show="confirmacion" x-cloak>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8.5 12.25 2.5 2.5 4.75-5"/></svg>
                    <span x-text="confirmacion"></span>
                </p>
                <div class="pie-soporte">
                    <span class="remitente">Se enviará como <b>{{ $user->nombre_completo }}</b>{{ $user->correo_electronico ? ' · ' . $user->correo_electronico : '' }}</span>
                    <button type="submit" class="btn-primario" :disabled="enviando" :aria-busy="enviando.toString()" x-text="enviando ? 'Enviando…' : 'Enviar solicitud'"></button>
                </div>
            </form>
        </section>

        <!-- Solicitudes registradas: la columna lateral elevada -->
        <section class="pieza-elevada vista-lateral__lado soporte-lista" aria-labelledby="t-solicitudes">
            <h2 id="t-solicitudes" class="soporte-titulo">Solicitudes</h2>
            <p class="soporte-sub">Ábrala para ver el detalle o cambiar su estado.</p>
            <div class="filtros-rapidos filtros-rapidos--papel filtros-rapidos--compacto filtros-solicitudes" role="group" aria-label="Filtrar solicitudes">
                @foreach ($filtrosSoporte as $clave => [$rotulo, $n])
                    <button type="button" class="filtro-rapido" aria-pressed="{{ $clave === 'abiertas' ? 'true' : 'false' }}" :aria-pressed="(filtro === '{{ $clave }}').toString()" @click="filtro = '{{ $clave }}'">
                        <span>{{ $rotulo }}</span> <span class="filtro-rapido__n" x-text="contar('{{ $clave }}')">{{ $n }}</span>
                    </button>
                @endforeach
            </div>

            {{-- Mientras arranca Alpine: la forma de la lista con el filtro de entrada, «Abiertas» (init() la quita). Cada fila
                 mide lo mismo que una real con el asunto en dos líneas: número y estado arriba, dos renglones de asunto y los datos --}}
            <ul class="lista-solicitudes" data-esqueleto aria-hidden="true">
                @for ($i = 0; $i < min($nAbiertas, 6); $i++)
                    <li><span class="solicitud solicitud--esqueleto">
                        <span class="solicitud__numero"><span class="esqueleto" style="width: 2.75rem"></span></span>
                        <span class="solicitud__lado"><span class="esqueleto" style="width: 3.25rem"></span><span class="esqueleto esqueleto--etiqueta" style="width: {{ [4.25, 5, 4.25][$i % 3] }}rem"></span></span>
                        <span class="solicitud__cuerpo">
                            <span class="solicitud__asunto"><span class="esqueleto" style="width: {{ [94, 88, 96][$i % 3] }}%"></span><span class="esqueleto" style="width: {{ [52, 38, 64][$i % 3] }}%"></span></span>
                            <span class="solicitud__meta"><span class="esqueleto" style="width: {{ [56, 64, 48][$i % 3] }}%"></span></span>
                        </span>
                    </span></li>
                @endfor
            </ul>
            <ul class="lista-solicitudes">
                <template x-for="s in visibles()" :key="s.id">
                    <li>
                        <button type="button" class="solicitud" :class="{ 'solicitud--nueva': s.id === resaltada }" @click="abrirDetalle(s)">
                            <span class="solicitud__numero" x-text="s.numero"></span>
                            <span class="solicitud__lado">
                                <span class="solicitud__hace" x-text="hace(s.creada)"></span>
                                <span class="estado-chip" :class="'estado-chip--' + chipEstado(s.estado)" x-text="textoEstado(s.estado)"></span>
                            </span>
                            <span class="solicitud__cuerpo">
                                <span class="solicitud__asunto" x-text="s.asunto"></span>
                                <span class="solicitud__meta">
                                    <span class="solicitud__urgencia" :class="'urgencia--' + s.urgencia" x-text="'Urgencia ' + textoUrgencia(s.urgencia).toLowerCase()"></span>
                                    <span class="solicitud__meta-texto" x-text="' · ' + textoTipo(s.tipo) + (s.donde_texto ? ' · ' + s.donde_texto : '')"></span>
                                    <template x-if="s.url_adjunto">
                                        <span class="solicitud__captura" title="Tiene captura">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2.5"/><circle cx="8.75" cy="9.25" r="1.5"/><path d="m21 15.5-4.6-4.6a1.5 1.5 0 0 0-2.1 0L5.5 20"/></svg>
                                            <span class="sr-only">Tiene captura</span>
                                        </span>
                                    </template>
                                </span>
                            </span>
                        </button>
                    </li>
                </template>
            </ul>
            {{-- Vacío: si con el filtro de entrada no hay nada, Blade ya lo pinta con su texto; si hay, espera a Alpine --}}
            <div class="vacio" x-show="!visibles().length" @if ($nAbiertas) x-cloak @endif>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 13.5h5l1.5 2.5h5l1.5-2.5h5"/><path d="M5.6 5h12.8L21 13.5V18a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 18v-4.5Z"/></svg>
                <p class="vacio__titulo" x-text="textoVacio()[0]">{{ $nSolicitudes ? 'No hay solicitudes abiertas' : 'Todavía no hay solicitudes' }}</p>
                <p class="vacio__texto" x-text="textoVacio()[1]">{{ $nSolicitudes ? 'Todas las registradas están resueltas.' : 'La primera que envíe aparecerá aquí con su número y su estado.' }}</p>
                {{-- La salida: con un filtro sin resultados, volver a todas; sin solicitudes, ir al asunto de la nueva --}}
                <button type="button" class="enlace-panel" x-show="solicitudes.length && filtro !== 'todas'" @click="filtro = 'todas'" @unless ($nSolicitudes) x-cloak @endunless>Ver todas</button>
                <button type="button" class="enlace-panel" x-show="!solicitudes.length" @click="$refs.asunto.focus()" @if ($nSolicitudes) x-cloak @endif>Escribir la primera</button>
            </div>
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
    // Color del estado (estado-chip del layout): nueva = info, en curso = alerta, resuelta = ok
    const CHIP_ESTADO = { nueva: 'info', en_curso: 'alerta', resuelta: 'ok' };
    const mensajeDe = (estado, datos, porDefecto) => estado === 419 ? 'La sesión expiró. Recargue la página e inténtelo de nuevo.'
        : estado === 413 ? 'La captura es demasiado grande para el servidor.' : (datos.message || porDefecto);
    const vacio = donde => ({ tipo: 'error', urgencia: 'media', donde: donde || '', asunto: '', descripcion: '', pasos: '', esperado: '', captura: null, vista: null });

    Alpine.data('soporteVista', (solicitudes, lugares, donde) => ({
        solicitudes,
        f: vacio(donde),
        errores: {}, enviando: false, confirmacion: '', encima: false,
        filtro: 'abiertas',
        detalle: null, edicion: { estado: 'nueva', nota: '' }, guardandoEstado: false, errorDetalle: '',
        resaltada: null, origenFoco: null,
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
        chipEstado(e) { return CHIP_ESTADO[e] || 'neutro'; },
        textoUrgencia(u) { return { baja: 'Baja', media: 'Media', alta: 'Alta', critica: 'Crítica' }[u] || u; },
        persona(s) { return s.usuario.nombre || s.usuario.usuario; },
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
        textoVacio() {
            if (!this.solicitudes.length) return ['Todavía no hay solicitudes', 'La primera que envíe aparecerá aquí con su número y su estado.'];
            if (this.filtro === 'abiertas') return ['No hay solicitudes abiertas', 'Todas las registradas están resueltas.'];
            return ['Ninguna está resuelta todavía', 'Cuando una se marque como resuelta, aparecerá aquí.'];
        },

        // ---- lista
        pasa(s, clave) { return clave === 'abiertas' ? s.estado !== 'resuelta' : clave === 'resueltas' ? s.estado === 'resuelta' : true; },
        contar(clave) { return this.solicitudes.filter(s => this.pasa(s, clave)).length; },
        visibles() { return this.solicitudes.filter(s => this.pasa(s, this.filtro)); },
        abrirDetalle(s) {
            this.origenFoco = document.activeElement;
            this.detalle = s; this.edicion = { estado: s.estado, nota: s.nota || '' }; this.errorDetalle = '';
            this.$nextTick(() => { const b = this.$root.querySelector('#detalle-cerrar'); if (b) b.focus(); });
        },
        cerrarDetalle() {
            if (this.guardandoEstado || !this.detalle) return;
            this.detalle = null;
            const o = this.origenFoco; this.origenFoco = null;
            if (o && o.isConnected) o.focus();
        },
        // El foco no se sale del detalle con Tab mientras está abierto
        atraparFoco(e) {
            const f = [...e.currentTarget.querySelectorAll('button:not([disabled]), a[href]:not([tabindex="-1"]), textarea, input, select')].filter(x => x.offsetParent !== null);
            if (!f.length) return;
            const primero = f[0], ultimo = f[f.length - 1];
            if (e.shiftKey && document.activeElement === primero) { e.preventDefault(); ultimo.focus(); }
            else if (!e.shiftKey && document.activeElement === ultimo) { e.preventDefault(); primero.focus(); }
        },
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
                    const o = this.origenFoco; this.origenFoco = null;
                    this.$nextTick(() => { if (o && o.isConnected) o.focus(); });
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
            if (!this.f.asunto.trim()) this.errores.asunto = 'Escriba el asunto.';
            if (!this.f.descripcion.trim()) this.errores.descripcion = 'Cuéntenos qué pasa.';
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
                        this.confirmacion = 'Solicitud ' + s.numero + ' registrada. Ya aparece en la lista de solicitudes' + (datos.avisado ? ' y se avisó por correo al equipo.' : '.');
                        return;
                    }
                    const e = datos.errors || {};
                    this.errores = Object.fromEntries(Object.entries(e).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]));
                    if (!Object.keys(this.errores).length) this.errores = { general: mensajeDe(estado, datos, 'No se pudo enviar la solicitud.') };
                })
                .catch(() => { this.errores = { general: 'No hay conexión con el servidor. Su texto sigue aquí: inténtelo de nuevo.' }; })
                .finally(() => { this.enviando = false; });
        },
    }));
});
</script>

@push('estilos')
<style>
.envoltorio-modal { display: contents; }
.soporte-vista { font-variant-numeric: tabular-nums; }
/* Columna lateral del layout (.vista-lateral): el formulario ocupa lo que queda y las solicitudes van en 22rem */
.soporte-rejilla { --lateral: 22rem; }

/* Urgencias, arriba a la derecha de la cabecera: a quién acudir si algo no da espera */
.soporte-urgencias {
    display: flex; align-items: center; gap: .4375rem; margin: 0; padding: .5rem .875rem; border-radius: .625rem;
    background: rgba(255, 255, 255, .72); box-shadow: inset 0 0 0 1px var(--linea); font-size: .8125rem; color: var(--tinta-suave);
}
.soporte-urgencias svg { width: 16px; height: 16px; flex: none; color: var(--icono); }
.soporte-urgencias b { font-weight: 600; color: var(--tinta); }

.soporte-titulo { margin: 0; font-size: 1rem; font-weight: 650; letter-spacing: 0; color: var(--tinta); }
.soporte-sub { margin: .125rem 0 0; font-size: .8125rem; line-height: 1.45; color: var(--tinta-suave); text-wrap: pretty; }

/* ---- Solicitud nueva: superficie plana (la pieza elevada es la columna de solicitudes) */
.soporte-nueva { min-width: 0; padding: 1.25rem 1.25rem 1.125rem; }
.form-soporte { display: flex; flex-direction: column; gap: .875rem; margin-top: 1rem; }
.rotulo-fila { display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: .125rem .75rem; }
.contador { font-size: .75rem; font-weight: 500; color: var(--tinta-tenue); font-variant-numeric: tabular-nums; }
.form-soporte .campo--area { line-height: 1.5; }

/* ¿Qué pasa?: tres tarjetas-opción con su explicación */
.tipos { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .5rem; }
@media (max-width: 639px) { .tipos { grid-template-columns: minmax(0, 1fr); } }
.tipo {
    display: grid; grid-template-columns: auto minmax(0, 1fr); column-gap: .5rem; row-gap: .125rem; align-items: center; align-content: start;
    padding: .6875rem .875rem .75rem; border-radius: .75rem; text-align: left; font: inherit; cursor: pointer;
    background: #fff; box-shadow: inset 0 0 0 1px var(--linea);
    transition-property: background-color, box-shadow; transition-duration: .15s; transition-timing-function: ease-out;
}
.tipo svg { width: 18px; height: 18px; color: var(--icono); transition: color .15s ease-out; }
.tipo__nombre { min-width: 0; font-size: .875rem; font-weight: 650; line-height: 1.35; color: var(--tinta); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tipo__ayuda { grid-column: 1 / -1; font-size: .78125rem; font-weight: 400; line-height: 1.4; color: var(--tinta-suave); text-wrap: pretty; }
.tipo:hover { background: #f5f8fc; }
.tipo:hover svg { color: var(--tinta-2); }
.tipo[aria-checked="true"] { background: var(--azul-claro); box-shadow: inset 0 0 0 1.5px var(--azul); }
.tipo[aria-checked="true"] svg { color: var(--azul); }
.tipo:focus-visible { outline: 2px solid var(--azul); outline-offset: 2px; }

/* Urgencia: el segmentado es el del layout; solo «Alta» y «Crítica» elegidas toman su tono */
.segmentado button.urg--alta[aria-checked="true"] { color: var(--alerta-tinta); }
.segmentado button.urg--critica[aria-checked="true"] { color: var(--error); }
.fila-urgencia { display: grid; grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr); gap: .75rem 1rem; align-items: start; }
.fila-pasos { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem 1rem; }
@media (max-width: 639px) { .fila-urgencia, .fila-pasos { grid-template-columns: minmax(0, 1fr); } }
.rotulo-fila--junto { justify-content: flex-start; gap: .125rem .625rem; }
.urgencia-ayuda { margin: 0; min-width: 0; }
.urgencia-ayuda.is-critica { color: var(--error-texto); font-weight: 550; }

/* Captura: pegar, arrastrar o elegir */
.captura {
    display: flex; align-items: center; gap: .875rem; padding: .75rem 1rem; border: 1.5px dashed #c3cfe0; border-radius: .625rem;
    background: #f8fafd; font-size: .875rem; font-weight: 400; color: var(--tinta-suave); cursor: pointer;
    transition-property: background-color, border-color; transition-duration: .15s; transition-timing-function: ease-out;
}
.captura svg { width: 24px; height: 24px; flex: none; color: var(--icono); }
.captura__texto { display: flex; flex-direction: column; gap: .1875rem; min-width: 0; line-height: 1.45; }
.captura__elegir { color: var(--azul); font-weight: 600; }
.captura__nota { font-size: .75rem; color: var(--tinta-tenue); }
.captura kbd {
    display: inline-block; padding: 0 .3125rem; border-radius: .3125rem; background: #fff; font: inherit; font-size: .75rem; font-weight: 600;
    line-height: 1.35rem; color: var(--tinta-2); box-shadow: inset 0 0 0 1px var(--linea), 0 1px 0 var(--linea);
}
.captura:hover, .captura--encima { border-color: var(--azul); background: var(--azul-claro); }
.captura:hover svg, .captura--encima svg { color: var(--azul); }
.captura:focus-within { outline: 2px solid var(--azul); outline-offset: 2px; }
.captura-elegida {
    display: flex; align-items: center; gap: .875rem; padding: .5rem; border-radius: .75rem;
    background: #f5f8fc; box-shadow: inset 0 0 0 1px var(--linea-suave); font-weight: 400;
}
.captura-elegida img {
    width: 6rem; height: 3.75rem; object-fit: cover; border-radius: .4375rem; background: #eef1f6; flex: none;
    outline: 1px solid rgba(15, 39, 71, .08); outline-offset: -1px;
}
.captura-elegida__datos { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: .125rem; font-size: .8125rem; }
.captura-elegida__nombre { font-weight: 600; color: var(--tinta); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.captura-elegida__peso { color: var(--tinta-tenue); font-variant-numeric: tabular-nums; }
.btn-chico { height: 2rem; padding: 0 .75rem; font-size: .8125rem; flex: none; }

/* La acción del panel, abajo a la derecha (botón del layout, 40 px) */
.pie-soporte {
    display: flex; align-items: center; justify-content: space-between; gap: .75rem 1rem; flex-wrap: wrap;
    margin-top: .5rem; padding-top: 1.125rem; border-top: 1px solid var(--linea-suave);
}
.remitente { min-width: 0; font-size: .8125rem; color: var(--tinta-tenue); }
.remitente b { color: var(--tinta-2); font-weight: 600; }

/* ---- Solicitudes registradas: la columna lateral elevada (como la Cola del Inicio); la lista se desplaza por dentro.
        Bajo 1100 px el layout la apila y le quita el tope de alto. */
.soporte-lista { min-width: 0; display: flex; flex-direction: column; max-height: calc(100vh - 3rem); padding: 1.25rem 1.125rem .625rem; }
/* Pastillas del layout sobre papel y compactas (.filtros-rapidos--papel, --compacto: en 22rem caben tres con conteos de
   dos cifras), a .875rem del subtítulo como la lista de la Cola. */
.filtros-solicitudes { flex: none; margin-top: .875rem; }

.lista-solicitudes {
    list-style: none; margin: .625rem 0 0; padding: 0; flex: 1 1 auto; min-height: 0; overflow-y: auto;
    overscroll-behavior: contain; scrollbar-width: thin;
}
.lista-solicitudes li + li { border-top: 1px solid var(--linea-suave); }
/* Fila en dos pisos (en 22rem no caben tres columnas): arriba el número, cuándo y el estado; abajo el asunto a todo el
   ancho, en hasta dos líneas, y sus datos */
.solicitud {
    display: grid; grid-template-columns: minmax(0, 1fr) auto; grid-template-areas: "num lado" "cuerpo cuerpo";
    column-gap: .75rem; row-gap: .25rem; align-items: center;
    width: 100%; padding: .75rem .5rem; border-radius: .625rem; text-align: left; font: inherit; color: inherit; cursor: pointer;
    transition: background-color .15s ease-out;
}
.solicitud:hover { background: #f6f9fd; }
.solicitud:focus-visible { outline: 2px solid var(--azul); outline-offset: -2px; }
.solicitud--nueva, .solicitud--nueva:hover { background: #f3f7fd; box-shadow: inset 0 0 0 1px var(--info-borde); }
.solicitud__numero { grid-area: num; min-width: 0; font-size: .8125rem; font-weight: 650; letter-spacing: .01em; color: var(--tinta-suave); white-space: nowrap; }
.solicitud__lado { grid-area: lado; display: flex; align-items: center; gap: .5rem; }
.solicitud__hace { font-size: .75rem; color: var(--tinta-tenue); white-space: nowrap; }
.solicitud__cuerpo { grid-area: cuerpo; min-width: 0; display: flex; flex-direction: column; gap: .1875rem; }
.solicitud__asunto {
    display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; overflow: hidden;
    font-size: .875rem; font-weight: 600; line-height: 1.4; color: var(--tinta); overflow-wrap: anywhere; text-wrap: pretty;
}
.solicitud__meta { display: flex; align-items: center; min-width: 0; font-size: .78125rem; line-height: 1.5; color: var(--tinta-tenue); }
.solicitud__urgencia { flex: none; white-space: nowrap; }
.solicitud__meta-texto { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: pre; }
/* relative: su texto sr-only (absoluto) se queda dentro de la fila y no estira la página */
.solicitud__captura { position: relative; flex: none; display: inline-flex; margin-left: .4375rem; color: var(--icono); }
/* Esqueleto: cada barra ocupa el alto de la línea que reemplaza (estado 22,25 px, asunto 2 × 19,6 px, datos 18,75 px),
   así la fila mide lo mismo que una real con el asunto en dos líneas y la lista no salta al arrancar */
.solicitud--esqueleto { cursor: default; }
.solicitud--esqueleto:hover { background: none; }
.solicitud--esqueleto .esqueleto--etiqueta { height: calc(.78125rem * 1.3 + .375rem); border-radius: 999px; }
.solicitud--esqueleto .solicitud__asunto { display: flex; flex-direction: column; justify-content: space-around; height: 2.8em; }
.solicitud--esqueleto .solicitud__meta { height: 1.5em; }
.urgencia--alta { color: var(--alerta-tinta); font-weight: 600; }
.urgencia--critica { color: var(--error); font-weight: 650; }

/* ---- Detalle de una solicitud (modal): la cabeza, el cierre y la entrada son los del layout; aquí solo el cuerpo se
        desplaza y el pie queda quieto con su filete */
.detalle-caja { max-width: 36rem; display: flex; flex-direction: column; overflow: hidden; }
.detalle-marco { flex: 1 1 auto; min-height: 0; display: flex; flex-direction: column; }
.detalle-cabeza { flex: none; }
.detalle-numero { margin-right: .25rem; font-weight: 600; color: var(--tinta-tenue); font-variant-numeric: tabular-nums; }
.detalle-cuerpo { flex: 1 1 auto; min-height: 0; overflow-y: auto; overscroll-behavior: contain; display: flex; flex-direction: column; gap: 1.125rem; padding: .75rem 1.5rem 1.25rem; }
.detalle-datos { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem 1.25rem; margin: 0; padding: .875rem 1rem; border-radius: .75rem; background: #f5f8fc; }
.detalle-datos > div { min-width: 0; }
.detalle-datos__ancho { grid-column: 1 / -1; }
.detalle-datos dt { font-size: .75rem; color: var(--tinta-tenue); }
.detalle-datos dd { margin: .125rem 0 0; font-size: .875rem; font-weight: 550; color: var(--tinta); overflow-wrap: anywhere; }
.detalle-datos dd.urgencia--alta { color: var(--alerta-tinta); font-weight: 600; }
.detalle-datos dd.urgencia--critica { color: var(--error); font-weight: 650; }
.detalle-correo { margin-left: .5rem; font-weight: 400; color: var(--tinta-suave); }
.detalle-bloque h3 { margin: 0 0 .3125rem; font-size: .8125rem; font-weight: 600; color: var(--tinta-2); }
.detalle-bloque p { margin: 0; font-size: .875rem; line-height: 1.55; color: var(--tinta); white-space: pre-line; overflow-wrap: anywhere; text-wrap: pretty; }
.detalle-captura { margin: 0; display: flex; flex-direction: column; gap: .5rem; }
.detalle-captura__marco { display: block; border-radius: .625rem; background: #f3f6fa; overflow: hidden; }
.detalle-captura__marco img { display: block; width: 100%; max-height: 16rem; object-fit: contain; outline: 1px solid rgba(15, 39, 71, .08); outline-offset: -1px; border-radius: .625rem; }
.detalle-captura__enlace { display: inline-flex; align-items: center; gap: .375rem; font-size: .8125rem; }
.detalle-navegador { margin: 0; font-size: .75rem; line-height: 1.5; color: var(--tinta-tenue); overflow-wrap: anywhere; }
.detalle-estado { display: flex; flex-direction: column; gap: .875rem; padding-top: 1.125rem; border-top: 1px solid var(--linea-suave); }
.detalle-pie { flex: none; padding: .875rem 1.5rem 1.5rem; border-top: 1px solid var(--linea-suave); }

@media (prefers-reduced-motion: reduce) {
    .tipo, .tipo svg, .captura, .solicitud { transition: none; }
}
</style>
@endpush
@endsection
