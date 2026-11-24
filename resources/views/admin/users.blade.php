@extends('layouts.admin')

@section('title', 'Usuarios')

@section('acciones_pagina')
    {{-- La acción principal de la vista, arriba a la derecha. El formulario escucha el evento en window. --}}
    <button type="button" class="btn-primario" @click="$dispatch('abrir-usuario', { modo: 'crear' })">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
        Nuevo usuario
    </button>
@endsection

@section('content')
{{-- Usuarios en una sola vista (AdminController::users): todos, con lo que sirve para operar (rol, servicios
     asignados, conexión y, en huvuba, auto-llamado). Crear, editar y eliminar van por fetch en ventanas propias. --}}
<div class="usuarios-vista" x-data="usuariosVista(@js($filas), @js($search))">

    <!-- Crear / editar -->
    <div class="envoltorio-modal" x-data="formularioUsuario(@js($autoLlamado))" @abrir-usuario.window="abrir($event.detail)" @keydown.escape.window="cerrar()">
        <div class="modal-panel" x-show="abierto" x-cloak x-transition.opacity @click.self="cerrarSiLimpio()">
            <div class="modal-panel__caja" role="dialog" aria-modal="true" aria-labelledby="t-usuario">
                <div class="modal-panel__cabeza">
                    <h2 id="t-usuario" x-text="modo === 'crear' ? 'Nuevo usuario' : 'Editar usuario'"></h2>
                    <button type="button" class="accion-icono" aria-label="Cerrar" @click="cerrar()">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>
                </div>
                <form class="form-panel form-usuario" @submit.prevent="guardar()" novalidate autocomplete="off">
                    <div class="aviso aviso--info" role="status" x-show="modo === 'editar' && original.desactivada">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><rect x="4.5" y="11" width="15" height="10" rx="2"/><path d="M8 11V7.5a4 4 0 0 1 8 0V11"/></svg>
                        <p><b>Cuenta desactivada desde el <span x-text="desactivadaDesde()"></span>.</b>
                            No puede iniciar sesión. Sus turnos e historial se conservan.</p>
                    </div>

                    <div class="form-grupo" role="group" aria-labelledby="g-identidad">
                        <h3 id="g-identidad" class="form-grupo__titulo">Identidad</h3>
                        <label class="form-campo">Nombre completo
                            <input type="text" class="campo" x-model="datos.nombre_completo" x-ref="primero" maxlength="255" autocomplete="off" :aria-invalid="errores.nombre_completo ? 'true' : null">
                            <small x-show="errores.nombre_completo" x-text="errores.nombre_completo"></small>
                        </label>
                        <div class="form-panel__fila">
                            <label class="form-campo"><span class="form-rotulo">Cédula <span class="form-opcional">(opcional)</span></span>
                                <input type="text" class="campo" x-model="datos.cedula" inputmode="numeric" maxlength="20" autocomplete="off" :aria-invalid="errores.cedula ? 'true' : null">
                                <small x-show="errores.cedula" x-text="errores.cedula"></small>
                            </label>
                            <label class="form-campo"><span class="form-rotulo">Correo <span class="form-opcional">(opcional)</span></span>
                                <input type="email" class="campo" x-model="datos.correo_electronico" maxlength="255" autocomplete="off" :aria-invalid="errores.correo_electronico ? 'true' : null">
                                <small x-show="errores.correo_electronico" x-text="errores.correo_electronico"></small>
                            </label>
                        </div>
                    </div>

                    <div class="form-grupo" role="group" aria-labelledby="g-acceso">
                        <h3 id="g-acceso" class="form-grupo__titulo">Acceso</h3>
                        <div class="form-panel__fila">
                            <label class="form-campo">Usuario
                                <input type="text" class="campo" x-model="datos.nombre_usuario" maxlength="255" autocomplete="off" autocapitalize="off" spellcheck="false" :aria-invalid="errores.nombre_usuario ? 'true' : null">
                                <small x-show="errores.nombre_usuario" x-text="errores.nombre_usuario"></small>
                            </label>
                            <div class="form-campo"><span id="r-rol">Rol</span>
                                <div class="segmentado" role="radiogroup" aria-labelledby="r-rol"
                                     @keydown.arrow-right.prevent="datos.rol = datos.rol === 'Asesor' ? 'Administrador' : 'Asesor'; $nextTick(() => $el.querySelector('[aria-checked=true]').focus())"
                                     @keydown.arrow-left.prevent="datos.rol = datos.rol === 'Asesor' ? 'Administrador' : 'Asesor'; $nextTick(() => $el.querySelector('[aria-checked=true]').focus())">
                                    <button type="button" role="radio" :aria-checked="(datos.rol === 'Asesor').toString()" :tabindex="datos.rol === 'Asesor' ? 0 : -1" @click="datos.rol = 'Asesor'">Asesor</button>
                                    <button type="button" role="radio" :aria-checked="(datos.rol === 'Administrador').toString()" :tabindex="datos.rol === 'Administrador' ? 0 : -1" @click="datos.rol = 'Administrador'">Administrador</button>
                                </div>
                                <small x-show="errores.rol" x-text="errores.rol"></small>
                            </div>
                        </div>
                        <div class="fila-clave" x-show="modo === 'editar' && !cambiarClave">
                            <div class="fila-clave__textos">
                                <p class="fila-clave__titulo">Contraseña</p>
                                <p class="form-ayuda">No hay recuperación por correo: si la olvida, asígnele una nueva aquí.</p>
                            </div>
                            <button type="button" class="btn-secundario enlace-contrasena" @click="cambiarClave = true; $nextTick(() => $refs.clave && $refs.clave.focus())">Cambiar contraseña</button>
                        </div>
                        <div class="form-panel__fila" x-show="modo === 'crear' || cambiarClave">
                            <label class="form-campo"><span x-text="modo === 'crear' ? 'Contraseña' : 'Nueva contraseña'"></span>
                                <input type="password" class="campo" x-model="datos.password" x-ref="clave" autocomplete="new-password" :aria-invalid="errores.password ? 'true' : null">
                                <small x-show="errores.password" x-text="errores.password"></small>
                            </label>
                            <label class="form-campo">Confirmar contraseña
                                <input type="password" class="campo" x-model="datos.password_confirmation" autocomplete="new-password">
                            </label>
                        </div>
                        <p class="form-ayuda" x-show="modo === 'crear'">Opcional: sin contraseña no podrá iniciar sesión hasta que se la asigne.</p>
                    </div>

                    <template x-if="autoLlamado && datos.rol === 'Asesor'">
                        <div class="form-grupo" role="group" aria-labelledby="g-operacion">
                            <h3 id="g-operacion" class="form-grupo__titulo">Operación</h3>
                            <div class="fila-auto">
                                <label class="form-casilla"><input type="checkbox" x-model="datos.auto_llamado_activo">
                                    <span class="form-casilla__textos">
                                        <span class="form-casilla__titulo">Auto-llamado de turnos</span>
                                        <span class="form-ayuda">Llama el siguiente turno solo, tras un tiempo sin atender.</span>
                                    </span>
                                </label>
                                <label class="minutos-auto" x-show="datos.auto_llamado_activo"><span>Cada</span>
                                    <input type="number" class="campo" min="1" max="60" x-model.number="datos.auto_llamado_minutos"> <span>min</span>
                                </label>
                            </div>
                        </div>
                    </template>

                    <div class="aviso aviso--error" role="alert" x-show="errores.general">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7.5v5.5"/><path d="M12 16.5h.01"/></svg>
                        <p x-text="errores.general"></p>
                    </div>
                    <div class="modal-panel__pie modal-panel__pie--fijo pie-usuario">
                        <div class="pie-usuario__cuenta">
                            <button type="button" class="accion-texto" x-show="modo === 'editar' && !original.yo && !original.desactivada" @click="pedirDesactivar()">Desactivar cuenta</button>
                            <button type="button" class="accion-texto" x-show="modo === 'editar' && original.desactivada" :disabled="guardando" @click="reactivar()">Reactivar cuenta</button>
                            <button type="button" class="accion-texto accion-texto--peligro" x-show="modo === 'editar' && !original.yo" @click="pedirEliminar()">Eliminar usuario</button>
                        </div>
                        <button type="button" class="btn-secundario" @click="cerrar()">Cancelar</button>
                        <button type="submit" class="btn-primario" :disabled="guardando" :aria-busy="guardando ? 'true' : null" x-text="guardando ? 'Guardando…' : (modo === 'crear' ? 'Crear usuario' : 'Guardar cambios')"></button>
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
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="form-panel">
                    <div x-show="cargando" class="impacto-esqueleto" role="status">
                        <span class="sr-only">Revisando qué depende de este usuario…</span>
                        <span class="esqueleto esqueleto--bloque-bajo" aria-hidden="true"></span>
                        <span class="esqueleto" style="width: 88%" aria-hidden="true"></span>
                        <span class="esqueleto" style="width: 64%" aria-hidden="true"></span>
                    </div>
                    <template x-if="!cargando && impacto">
                        <div class="impacto">
                            <div class="aviso aviso--error" role="alert" x-show="impacto?.turnos > 0">
                                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="m21.7 18-8-14a2 2 0 0 0-3.4 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                                <p><b>Llamó o atendió <span class="cifra" x-text="miles(impacto?.turnos)"></span> turnos.</b>
                                    Si lo elimina, esos turnos quedarán sin asesor en Reportes y Gráficos<span x-show="impacto?.canal > 0">, y se borrarán sus
                                    <b class="cifra" x-text="miles(impacto?.canal)"></b> registros de canales no presenciales</span>. No se puede deshacer.</p>
                            </div>
                            <p x-show="impacto?.turnos > 0 && !u.desactivada" class="form-texto">Si solo quiere que no vuelva a entrar, mejor
                                <button type="button" class="enlace-panel" @click="desactivarEnSuLugar()">desactive la cuenta</button>: conserva su nombre en los informes y se puede reactivar.</p>
                            <p x-show="!impacto?.turnos" class="form-texto">Se eliminará la cuenta<span x-show="impacto?.servicios > 0"> y sus <b class="cifra" x-text="impacto?.servicios"></b> servicios asignados</span><span
                                x-show="impacto?.canal > 0">, y se borrarán sus <b class="cifra" x-text="miles(impacto?.canal)"></b> registros de canales no presenciales</span>. No se puede deshacer.</p>
                            <label class="form-campo" x-show="impacto?.turnos > 0">
                                <span>Escriba <span class="codigo-confirmar" x-text="u.usuario"></span> para confirmar</span>
                                <input type="text" class="campo" x-model="confirmacion" autocomplete="off" autocapitalize="off" spellcheck="false">
                            </label>
                        </div>
                    </template>
                    <div class="aviso aviso--error" role="alert" x-show="error">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7.5v5.5"/><path d="M12 16.5h.01"/></svg>
                        <p x-text="error"></p>
                    </div>
                    <div class="modal-panel__pie">
                        <button type="button" class="btn-secundario" x-ref="cancelar" @click="cerrar()">Cancelar</button>
                        <button type="button" class="btn-peligro" :disabled="!puedeEliminar() || trabajando" :aria-busy="trabajando ? 'true' : null" @click="confirmar()" x-text="trabajando ? 'Eliminando…' : 'Eliminar usuario'"></button>
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
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="form-panel">
                    <template x-if="u">
                        <div class="impacto">
                            <ul class="consecuencias">
                                <li>
                                    <svg fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><rect x="4.5" y="11" width="15" height="10" rx="2"/><path d="M8 11V7.5a4 4 0 0 1 8 0V11"/></svg>
                                    <span><b>No podrá iniciar sesión.</b></span>
                                </li>
                                <li>
                                    <svg fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                    <span>Sus turnos, su historial y sus servicios asignados se conservan, y sigue apareciendo con su nombre en Reportes y Gráficos.</span>
                                </li>
                            </ul>
                            <div class="aviso aviso--alerta" role="alert" x-show="u.conectado">
                                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="m21.7 18-8-14a2 2 0 0 0-3.4 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                                <p><b>Ahora está conectado<span x-show="u.conectado && u.conectado.modulo"> en el módulo <span class="cifra" x-text="u.conectado && u.conectado.modulo"></span></span>.</b>
                                    Su sesión se cerrará y el módulo quedará libre.</p>
                            </div>
                            <p class="form-ayuda">Puede reactivarla cuando quiera desde Editar.</p>
                        </div>
                    </template>
                    <div class="aviso aviso--error" role="alert" x-show="error">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7.5v5.5"/><path d="M12 16.5h.01"/></svg>
                        <p x-text="error"></p>
                    </div>
                    <div class="modal-panel__pie">
                        <button type="button" class="btn-secundario" x-ref="cancelar" @click="cerrar()">Cancelar</button>
                        <button type="button" class="btn-peligro" :disabled="trabajando" :aria-busy="trabajando ? 'true' : null" @click="confirmar()" x-text="trabajando ? 'Desactivando…' : 'Desactivar cuenta'"></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros rápidos (por rol y por situación) y búsqueda en una sola fila -->
    <div class="barra-vista">
        <div class="filtros-rapidos" role="group" aria-label="Filtrar usuarios">
            @foreach ([3.6, 5.6, 7.4, 6.4, 6.8] as $i => $ancho)
                <span class="filtro-rapido {{ $i === 3 ? 'is-grupo' : '' }}" data-esqueleto aria-hidden="true"><span class="esqueleto" style="width: {{ $ancho }}rem"></span></span>
            @endforeach
            <template x-for="f in filtros" :key="f.clave">
                <button type="button" class="filtro-rapido" x-show="f.clave !== 'desactivadas' || contar('desactivadas') > 0"
                        :class="{ 'is-grupo': f.clave === 'conectados', 'is-alerta': f.clave === 'sin' && contar('sin') > 0 }"
                        :aria-pressed="(filtro === f.clave).toString()" @click="filtro = f.clave">
                    <span x-text="f.rotulo"></span> <span class="filtro-rapido__n" x-text="contar(f.clave)"></span>
                </button>
            </template>
        </div>
        <div class="buscador">
            <svg class="buscador__icono" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" x-model.debounce.150ms="buscar" class="campo" placeholder="Nombre, usuario, cédula o correo" aria-label="Buscar usuario">
        </div>
    </div>

    <div class="superficie tabla-envoltura">
        <table class="tabla-panel tabla-usuarios">
            <thead>
                <tr>
                    <th scope="col">Nombre y usuario</th>
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
                        <td>
                            <span class="esqueleto" style="width: {{ [11, 9, 12.5, 10][$i % 4] }}rem"></span>
                            <span class="esqueleto esqueleto-meta" style="width: {{ [7, 8, 6.5, 7.5][$i % 4] }}rem"></span>
                        </td>
                        <td><span class="esqueleto" style="width: {{ $i === 0 ? 6.5 : 3.5 }}rem"></span></td>
                        <td><span class="esqueleto-codigos">@for ($c = 0; $c < [2, 4, 3, 1][$i % 4]; $c++)<span class="esqueleto esqueleto-codigo"></span>@endfor</span></td>
                        <td><span class="esqueleto esqueleto--etiqueta" style="width: {{ [8.5, 6, 9.5, 5.5][$i % 4] }}rem"></span></td>
                        @if ($autoLlamado)<td><span class="esqueleto" style="width: 1rem"></span></td>@endif
                        <td></td>
                    </tr>
                @endfor
            </tbody>
            <tbody>
                <template x-for="u in visibles()" :key="u.id">
                    <tr :class="u.desactivada ? 'fila-desactivada' : ''">
                        <td>
                            <div class="usuario">
                                <span class="usuario__linea">
                                    <span class="usuario__nombre" x-text="u.nombre"></span>
                                    <span class="marca-yo" x-show="u.yo">Su cuenta</span>
                                </span>
                                <span class="usuario__meta">
                                    <span class="usuario__login" x-text="u.usuario"></span><span class="usuario__cedula" x-show="u.cedula" x-text="u.cedula"></span>
                                </span>
                            </div>
                        </td>
                        <td><span class="rol" :class="u.rol === 'Administrador' ? 'rol--admin' : ''" x-text="u.rol"></span></td>
                        <td>
                            <template x-if="u.rol !== 'Asesor'"><span class="texto-mudo">—</span></template>
                            <template x-if="u.rol === 'Asesor' && !u.desactivada && !u.servicios.length">
                                <a class="etiqueta-asignar" :href="asignacionUrl(u)" title="No atiende ningún servicio">Sin servicios · Asignar</a>
                            </template>
                            <template x-if="u.rol === 'Asesor' && !u.desactivada && u.servicios.length">
                                <a class="codigos" :href="asignacionUrl(u)" :title="u.servicios.map(s => s.nombre).join(', ')"
                                   :aria-label="'Servicios: ' + u.servicios.map(s => s.nombre).join(', ') + '. Cambiar la asignación'">
                                    <template x-for="(s, i) in u.servicios" :key="i"><span class="codigo-tecla" x-text="s.codigo || '?'"></span></template>
                                </a>
                            </template>
                            <template x-if="u.rol === 'Asesor' && u.desactivada">
                                <span class="codigos codigos--apagados">
                                    <template x-for="(s, i) in u.servicios" :key="i"><span class="codigo-tecla" x-text="s.codigo || '?'"></span></template>
                                    <span class="texto-mudo" x-show="!u.servicios.length">—</span>
                                </span>
                            </template>
                        </td>
                        <td>
                            <template x-if="u.desactivada">
                                <span class="actividad"><span class="estado-chip estado-chip--neutro">Desactivada</span><span class="actividad__detalle" x-text="desde(u)"></span></span>
                            </template>
                            <template x-if="!u.desactivada && u.conectado">
                                <span class="actividad"><span class="estado-chip" :class="'estado-chip--' + estado(u)[1]" x-text="estado(u)[0]"></span><span class="actividad__detalle" x-text="u.conectado.modulo ? 'Módulo ' + u.conectado.modulo : 'Sin módulo'"></span></span>
                            </template>
                            <template x-if="!u.desactivada && !u.conectado">
                                <span class="texto-mudo" x-text="actividad(u)"></span>
                            </template>
                        </td>
                        @if ($autoLlamado)
                            <td><span class="auto-llamado" x-show="u.auto_llamado" x-text="'Cada ' + u.auto_llamado_minutos + ' min'"></span><span class="texto-mudo" x-show="!u.auto_llamado">—</span></td>
                        @endif
                        <td class="celda-acciones">
                            <button type="button" class="accion-icono" title="Editar" :aria-label="'Editar a ' + u.nombre"
                                    @click="$dispatch('abrir-usuario', { modo: 'editar', usuario: u })">
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h8"/><path d="M16.4 3.6a2.1 2.1 0 1 1 3 3L7.5 18.5 3.5 19.5l1-4Z"/></svg>
                            </button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
        <div class="vacio" x-show="!visibles().length" x-cloak>
            <p class="vacio__titulo" x-text="buscar.trim() ? 'Ningún usuario coincide con «' + buscar.trim() + '»' : 'No hay usuarios en este filtro'"></p>
            <p class="vacio__texto">Revise la búsqueda o vuelva a ver todos los usuarios.</p>
            <button type="button" class="enlace-panel" @click="filtro = 'todos'; buscar = ''">Quitar filtros</button>
        </div>
    </div>
    <p class="sr-only" role="status" x-text="visibles().length === 1 ? '1 usuario' : visibles().length + ' usuarios'"></p>
</div>

<script>
document.addEventListener('alpine:init', () => {
    const USUARIOS_URL = @json(route('admin.users'));
    const ASIGNACION_URL = @json(route('admin.asignacion-servicios'));
    const TOKEN = () => document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    // Estado del módulo: la palabra y el tono del chip (los mismos del Inicio).
    const ESTADOS = { atendiendo: ['Atendiendo', 'ok'], libre: ['Libre', 'info'], descanso: ['En descanso', 'alerta'], canal: ['Canal no presencial', 'neutro'] };
    const normal = t => String(t ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    // Lo que se ve tras guardar y recargar: sileo lo guarda y lo muestra al volver.
    const recargarCon = opciones => sileo.recargarCon(opciones);
    const SIN_CONEXION = 'No hay conexión con el servidor. Inténtelo de nuevo.';
    const mensajeDe = (estado, datos, porDefecto) => estado === 419 ? 'La sesión expiró. Recargue la página e inténtelo de nuevo.' : (datos.message || porDefecto);
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
        estado(u) { return ESTADOS[u.conectado && u.conectado.estado] || ['Conectado', 'neutro']; },
        desde(u) { return 'desde el ' + fechaCorta(u.desactivada); },
        actividad(u) {
            if (u.desactivada) return 'Desactivada el ' + fechaCorta(u.desactivada);
            if (u.conectado) return (u.conectado.modulo ? 'Módulo ' + u.conectado.modulo : 'Sin módulo') + ' · ' + this.estado(u)[0];
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
                .catch(() => { this.guardando = false; this.errores = { general: SIN_CONEXION }; });
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
                .catch(() => { this.guardando = false; this.errores = { general: SIN_CONEXION }; });
        },
    }));

    Alpine.data('eliminarUsuario', () => ({
        abierto: false, u: null, impacto: null, cargando: false, trabajando: false, error: '', confirmacion: '',
        abrir({ usuario }) {
            this.u = usuario; this.impacto = null; this.error = ''; this.confirmacion = ''; this.trabajando = false; this.cargando = true; this.abierto = true;
            this.$nextTick(() => this.$refs.cancelar && this.$refs.cancelar.focus());
            fetch(USUARIOS_URL + '/' + usuario.id, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' })
                .then(r => r.ok ? r.json() : Promise.reject(r.status))
                .then(d => { this.impacto = d.impacto; })
                .catch(e => { this.error = e === 419 ? mensajeDe(419, {}) : 'No se pudo revisar el usuario. Inténtelo de nuevo.'; })
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
                .catch(() => { this.trabajando = false; this.error = SIN_CONEXION; });
        },
    }));

    Alpine.data('desactivarUsuario', () => ({
        abierto: false, u: null, trabajando: false, error: '',
        abrir({ usuario }) {
            this.u = usuario; this.error = ''; this.trabajando = false; this.abierto = true;
            this.$nextTick(() => this.$refs.cancelar && this.$refs.cancelar.focus());
        },
        cerrar() { if (!this.trabajando) this.abierto = false; },
        confirmar() {
            this.trabajando = true; this.error = '';
            accion(USUARIOS_URL + '/' + this.u.id + '/desactivar')
                .then(({ ok, estado, datos }) => {
                    if (ok && datos.success !== false) { recargarCon({ title: 'Cuenta desactivada', description: 'No podrá iniciar sesión. Sus turnos y su historial se conservan.' }); return; }
                    this.trabajando = false; this.error = mensajeDe(estado, datos, 'No se pudo desactivar la cuenta.');
                })
                .catch(() => { this.trabajando = false; this.error = SIN_CONEXION; });
        },
    }));
});
</script>

@push('estilos')
<style>
[x-cloak] { display: none !important; }
.envoltorio-modal { display: contents; }

/* ===== Usuarios (2026-11): el mismo mundo que «La sala» (ver DESIGN.md) ===== */
.usuarios-vista { display: flex; flex-direction: column; gap: 1rem; font-variant-numeric: tabular-nums; }
.usuarios-vista .buscador { flex-basis: 14rem; }

/* Filtros: por rol y, tras una línea fina, por situación. «Sin servicios» avisa en rojo con .is-alerta (receta del layout). */
.usuarios-vista .filtro-rapido.is-grupo { position: relative; margin-left: .75rem; }
.usuarios-vista .filtro-rapido.is-grupo::before {
    content: ""; position: absolute; left: -.5625rem; top: .625rem; bottom: .625rem; width: 1px; background: var(--corte); pointer-events: none;
}

/* Tabla: el texto arranca a 1rem del borde, como en todas las tablas del panel */
.tabla-envoltura { overflow-x: auto; }
.tabla-usuarios tbody td { height: 3.5rem; padding-top: .5rem; padding-bottom: .5rem; }
.tabla-usuarios th:nth-child(1) { width: 32%; }
.tabla-usuarios td:nth-child(4) { white-space: nowrap; }

.usuario { display: flex; flex-direction: column; gap: .125rem; min-width: 0; }
.usuario__linea { display: flex; align-items: center; gap: .5rem; min-width: 0; }
.usuario__nombre { font-weight: 600; color: var(--tinta); line-height: 1.3; }
.usuario__meta { font-size: .78125rem; line-height: 1.35; color: var(--tinta-tenue); white-space: nowrap; }
.usuario__login { color: var(--tinta-suave); font-weight: 500; }
.usuario__cedula::before { content: "·"; margin: 0 .375rem; color: var(--tinta-tenue); }
.marca-yo {
    flex: none; padding: .0625rem .4375rem; border-radius: .375rem; background: var(--azul-claro);
    font-size: .75rem; font-weight: 600; line-height: 1.35; color: var(--info);
}

.rol { color: var(--tinta-suave); }
.rol--admin { color: var(--tinta); font-weight: 600; }

/* Servicios: los códigos como teclas (.codigo-tecla del layout); el grupo entero lleva a Asignación.
   «Sin servicios · Asignar» es la .etiqueta-asignar del layout. */
.codigos { display: inline-flex; flex-wrap: wrap; gap: .25rem; max-width: 16rem; border-radius: .5rem; text-decoration: none; vertical-align: middle; }
a.codigos .codigo-tecla { transition-property: background-color, color, box-shadow; transition-duration: .15s; transition-timing-function: ease-out; }
a.codigos:hover .codigo-tecla { background: var(--azul-claro); color: var(--azul); box-shadow: inset 0 0 0 1px var(--info-borde); }
.codigos:focus-visible { outline: 2px solid var(--azul); outline-offset: 2px; }
.codigos--apagados .codigo-tecla { background: transparent; color: var(--tinta-tenue); }

/* Actividad: el estado con su palabra (los mismos tonos del Inicio) y dónde */
.actividad { display: inline-flex; align-items: center; gap: .5rem; white-space: nowrap; }
.actividad__detalle { font-size: .78125rem; color: var(--tinta-tenue); }
.auto-llamado { color: var(--tinta-2); white-space: nowrap; }

.tabla-usuarios .celda-acciones { width: 1%; padding-right: .75rem; text-align: right; }

/* Cuenta desactivada: la fila baja de tono, sin perder contraste */
.fila-desactivada .usuario__nombre, .fila-desactivada .rol--admin, .fila-desactivada .usuario__login { color: var(--tinta-tenue); }

/* Esqueleto de carga: misma forma y alto que las filas reales */
.fila-esqueleto td { vertical-align: middle; }
.esqueleto-meta { height: .625rem; margin-top: .4375rem; }
.esqueleto-codigos { display: flex; gap: .25rem; }
.esqueleto-codigo { width: 1.75rem; height: 1.5rem; border-radius: .375rem; }

/* Vacío: el .vacio del layout tal cual; el filete de arriba ya lo pone la cabecera de la tabla */

/* ===== Ventanas: caja, cabeza, entrada, campos, segmentado y casilla son las del layout ===== */
/* Grupos del formulario: un título corto y una línea fina entre ellos */
.form-grupo { display: flex; flex-direction: column; gap: .75rem; min-width: 0; }
.form-grupo ~ .form-grupo { padding-top: 1rem; border-top: 1px solid var(--linea-suave); }
.form-grupo__titulo { font-size: .875rem; font-weight: 650; color: var(--tinta); }

/* Contraseña al editar: se explica y se cambia a pedido */
.fila-clave { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
.fila-clave__textos { min-width: 0; display: flex; flex-direction: column; gap: .125rem; }
.fila-clave__titulo { font-size: .8125rem; font-weight: 600; color: var(--tinta-2); }
.fila-clave .btn-secundario { flex: none; }

/* Auto-llamado */
.fila-auto { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
.form-casilla__textos { display: flex; flex-direction: column; gap: .125rem; }
.form-casilla__titulo { font-weight: 600; color: var(--tinta-2); }
.minutos-auto { display: inline-flex; align-items: center; gap: .5rem; flex: none; font-size: .875rem; color: var(--tinta-2); white-space: nowrap; }
.minutos-auto .campo { width: 4.5rem; text-align: center; }

/* Pie del formulario: la cuenta a la izquierda, en voz baja; guardar a la derecha. El formulario se desplaza en
   pantallas bajas, así que el pie es el fijo del layout (.modal-panel__pie--fijo), siempre a la mano. */
.pie-usuario { flex-wrap: wrap; align-items: center; }
.pie-usuario__cuenta { display: flex; flex-wrap: wrap; gap: .125rem; margin-right: auto; margin-left: -.625rem; }
.accion-texto {
    height: 2.5rem; padding: 0 .625rem; border-radius: .625rem; font-size: .875rem; font-weight: 600; color: var(--tinta-2); cursor: pointer;
    transition-property: background-color, color; transition-duration: .15s; transition-timing-function: ease-out;
}
.accion-texto:hover { background: #eef3fa; color: var(--tinta); }
.accion-texto--peligro { color: var(--error-texto); }
.accion-texto--peligro:hover { background: var(--error-fondo); color: var(--error-texto); }
.accion-texto:focus-visible { outline: 2px solid var(--azul); outline-offset: 2px; }
.accion-texto:disabled { opacity: .6; cursor: default; }

/* Eliminar y desactivar (los avisos son el .aviso del layout) */
.impacto { display: flex; flex-direction: column; gap: .875rem; }
.impacto-esqueleto { display: flex; flex-direction: column; gap: .625rem; padding: .25rem 0 .5rem; }
.cifra { font-variant-numeric: tabular-nums; }
.form-texto .enlace-panel { display: inline; font-size: inherit; }
.codigo-confirmar {
    padding: .0625rem .375rem; border-radius: .375rem; background: #f5f8fc; box-shadow: inset 0 0 0 1px var(--linea);
    font-weight: 650; color: var(--tinta); letter-spacing: .01em;
}
.consecuencias { display: flex; flex-direction: column; gap: .625rem; margin: 0; padding: 0; list-style: none; }
.consecuencias li { display: flex; align-items: flex-start; gap: .625rem; font-size: .875rem; line-height: 1.5; color: var(--tinta-2); text-wrap: pretty; }
.consecuencias svg { width: 1.125rem; height: 1.125rem; flex: none; margin-top: .125rem; color: var(--icono); }
.consecuencias b { font-weight: 600; color: var(--tinta); }

@media (max-width: 767px) {
    .usuarios-vista .filtro-rapido.is-grupo { margin-left: 0; }
    .usuarios-vista .filtro-rapido.is-grupo::before { display: none; }
}
@media (max-width: 639px) {
    .fila-clave, .fila-auto { flex-direction: column; align-items: stretch; }
    .pie-usuario__cuenta { width: 100%; }
}
</style>
@endpush
@endsection
