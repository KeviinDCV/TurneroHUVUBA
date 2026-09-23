/*!
 * sileo.js — toasts del Turnero HUV, sin React.
 * Port a JavaScript sin dependencias de Sileo 0.1.5 (https://github.com/hiaaryan/sileo), de hiaaryan. Licencia MIT:
 *
 * Copyright (c) hiaaryan
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit
 * persons to whom the Software is furnished to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
 * Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
/*
 * La misma API de Sileo (ver https://sileo.aaryan.design/docs):
 *   sileo.success({ title: 'Usuario creado' })
 *   sileo.error({ title: 'No se guardó', description: 'No hay conexión con el servidor.' })
 *   sileo.success({ title: 'Servicio asignado', description: '…', button: { title: 'Deshacer', onClick() { … } } })
 *   sileo.promise(fetch(…), { loading: { title: 'Guardando…' }, success: { title: 'Guardado' }, error: { title: 'No se guardó' } })
 *   sileo.warning / info / action / show({ type }) · sileo.dismiss(id) · sileo.clear() · sileo.toaster({ position, offset, options })
 * Como en Sileo, sin `id` todos comparten uno: el nuevo toast reemplaza al anterior transformándose en él.
 *
 * Diferencias con el original:
 *  - Sin React ni motion: la píldora y el cuerpo del SVG se animan con resortes propios (requestAnimationFrame) con el
 *    mismo rebote (bounce .25 → amortiguación .75, ~600 ms).
 *  - Colores de estado aclarados para leerse (≥ 4,5:1) sobre el azul del turnero (#0f2547, el relleno por defecto aquí).
 *  - Títulos en mayúscula inicial normal (Sileo pone mayúscula a cada palabra) y posición por defecto abajo a la derecha,
 *    donde estaba el aviso anterior (arriba está la barra fija del panel).
 *  - La descripción plegada se oculta con visibility y el texto completo va además para lectores de pantalla; el foco
 *    del teclado hace lo mismo que el puntero (expande y pausa); Escape lo cierra; mientras el puntero está encima no se
 *    pliega solo. Al descartar y volver a mostrar un toast con el mismo id enseguida, el nuevo ya no desaparece.
 * Propio del turnero: sileo.recargarCon(opciones) recarga la página y muestra el toast al volver (solo texto: sin button).
 */
(function () {
    'use strict';
    if (window.sileo) return;

    /* --------------------------------- Constantes (las de Sileo) --------------------------------- */

    const HEIGHT = 40, WIDTH = 350, DEFAULT_ROUNDNESS = 16;
    const DURATION_MS = 600, DEFAULT_TOAST_DURATION = 6000;
    const EXIT_DURATION = DEFAULT_TOAST_DURATION * 0.1;
    const AUTO_EXPAND_DELAY = DEFAULT_TOAST_DURATION * 0.025;
    const AUTO_COLLAPSE_DELAY = DEFAULT_TOAST_DURATION - 2000;
    const BLUR_RATIO = 0.5, PILL_PADDING = 10, MIN_EXPAND_RATIO = 2.25;
    const SWAP_COLLAPSE_MS = 200, HEADER_EXIT_MS = DURATION_MS * 0.7;
    const SWIPE_DISMISS = 30, SWIPE_MAX = 20;
    const SVG_NS = 'http://www.w3.org/2000/svg';
    const PENDIENTE = 'sileo-al-recargar';

    // Resortes: k = ω², c = 2ζω (masa 1). Con rebote: ζ .75 y pico a ~270 ms, como la curva linear() de Sileo.
    const RESORTE = { k: 17.6 * 17.6, c: 2 * 0.75 * 17.6 };
    const RESORTE_SIN_REBOTE = { k: 11 * 11, c: 2 * 11 };
    const MENOS_MOVIMIENTO = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;

    const ICONO = (cuerpo, extra) => '<svg xmlns="' + SVG_NS + '" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
        + 'stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"' + (extra || '') + '>' + cuerpo + '</svg>';
    const ICONOS = {
        success: ICONO('<path d="M20 6 9 17l-5-5"/>'),
        loading: ICONO('<path d="M21 12a9 9 0 1 1-6.219-8.56"/>', ' data-sileo-icon="spin"'),
        error: ICONO('<path d="M18 6 6 18"/><path d="m6 6 12 12"/>'),
        warning: ICONO('<circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/>'),
        info: ICONO('<circle cx="12" cy="12" r="10"/><path d="m4.93 4.93 4.24 4.24"/><path d="m14.83 9.17 4.24-4.24"/>'
            + '<path d="m14.83 14.83 4.24 4.24"/><path d="m9.17 14.83-4.24 4.24"/><circle cx="12" cy="12" r="4"/>'),
        action: ICONO('<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>'),
    };
    const TITULOS = { success: 'Listo', loading: 'Cargando…', error: 'Error', warning: 'Atención', info: 'Información', action: 'Acción' };

    /* ------------------------------------------ Estilos ------------------------------------------ */

    const ESTILOS = `
:root {
    --sileo-spring-easing: cubic-bezier(.34, 1.36, .64, 1);
    --sileo-duration: 600ms;
    --sileo-height: 40px;
    --sileo-width: 350px;
    --sileo-state-success: oklch(0.8 0.19 148);
    --sileo-state-loading: oklch(0.8 0 0);
    --sileo-state-error: oklch(0.74 0.17 22);
    --sileo-state-warning: oklch(0.85 0.16 86);
    --sileo-state-info: oklch(0.8 0.12 237);
    --sileo-state-action: oklch(0.78 0.13 259);
    --sileo-description: rgba(255, 255, 255, .72);
    --sileo-focus: #a9c7f5;
}
@supports (transition-timing-function: linear(0, 1)) {
    :root {
        --sileo-spring-easing: linear(0, 0.002 0.6%, 0.007 1.2%, 0.015 1.8%, 0.026 2.4%, 0.041 3.1%, 0.06 3.8%, 0.108 5.3%,
            0.157 6.6%, 0.214 8%, 0.467 13.7%, 0.577 16.3%, 0.631 17.7%, 0.682 19.1%, 0.73 20.5%, 0.771 21.8%, 0.808 23.1%,
            0.844 24.5%, 0.874 25.8%, 0.903 27.2%, 0.928 28.6%, 0.952 30.1%, 0.972 31.6%, 0.988 33.1%, 1.01 35.7%, 1.025 38.5%,
            1.034 41.6%, 1.038 45%, 1.035 50.1%, 1.012 64.2%, 1.003 73%, 0.999 83.7%, 1);
    }
}
[data-sileo-viewport] {
    position: fixed; z-index: 60; display: flex; gap: .75rem; padding: .75rem; pointer-events: none;
    max-width: calc(100vw - 1.5rem); contain: layout style;
}
[data-sileo-viewport][data-position^="top"] { top: 0; flex-direction: column-reverse; }
[data-sileo-viewport][data-position^="bottom"] { bottom: 0; flex-direction: column; }
[data-sileo-viewport][data-position$="left"] { left: 0; align-items: flex-start; }
[data-sileo-viewport][data-position$="right"] { right: 0; align-items: flex-end; }
[data-sileo-viewport][data-position$="center"] { left: 50%; transform: translateX(-50%); align-items: center; }

[data-sileo-toast] {
    position: relative; cursor: default; pointer-events: auto; touch-action: none; border: 0; background: transparent;
    padding: 0; width: var(--sileo-width); height: var(--_h, var(--sileo-height)); opacity: 0;
    transform: translateZ(0) scale(.95); transform-origin: center; contain: layout style; overflow: visible; outline: none;
    font-family: inherit; text-align: left;
}
[data-sileo-toast][data-ready="true"] {
    opacity: 1; transform: translateZ(0) scale(1);
    transition: transform calc(var(--sileo-duration) * .66) var(--sileo-spring-easing),
        opacity calc(var(--sileo-duration) * .66) var(--sileo-spring-easing),
        margin-bottom calc(var(--sileo-duration) * .66) var(--sileo-spring-easing),
        margin-top calc(var(--sileo-duration) * .66) var(--sileo-spring-easing),
        height var(--sileo-duration) var(--sileo-spring-easing);
}
[data-sileo-viewport][data-position^="top"] [data-sileo-toast]:not([data-ready="true"]) { transform: translateY(-6px) scale(.95); margin-bottom: calc(-1 * (var(--sileo-height) + .75rem)); }
[data-sileo-viewport][data-position^="bottom"] [data-sileo-toast]:not([data-ready="true"]) { transform: translateY(6px) scale(.95); margin-top: calc(-1 * (var(--sileo-height) + .75rem)); }
[data-sileo-toast][data-ready="true"][data-exiting="true"] { opacity: 0; pointer-events: none; }
[data-sileo-viewport][data-position^="top"] [data-sileo-toast][data-ready="true"][data-exiting="true"] { transform: translateY(-6px) scale(.95); }
[data-sileo-viewport][data-position^="bottom"] [data-sileo-toast][data-ready="true"][data-exiting="true"] { transform: translateY(6px) scale(.95); }

[data-sileo-canvas] { position: absolute; left: 0; right: 0; pointer-events: none; transform: translateZ(0); contain: layout style; overflow: visible; }
[data-sileo-canvas][data-edge="top"] { bottom: 0; transform: scaleY(-1) translateZ(0); }
[data-sileo-canvas][data-edge="bottom"] { top: 0; }
[data-sileo-svg] { display: block; overflow: visible; }

[data-sileo-header] {
    position: absolute; z-index: 20; display: flex; align-items: center; padding: .5rem; height: var(--sileo-height);
    overflow: hidden; left: var(--_px, 0px); transform: var(--_ht); max-width: var(--_pw); border-radius: 16px;
}
[data-sileo-toast][data-ready="true"] [data-sileo-header] {
    transition: transform var(--sileo-duration) var(--sileo-spring-easing), left var(--sileo-duration) var(--sileo-spring-easing),
        max-width var(--sileo-duration) var(--sileo-spring-easing);
}
[data-sileo-header][data-edge="top"] { bottom: 0; }
[data-sileo-header][data-edge="bottom"] { top: 0; }
[data-sileo-toast]:focus-visible [data-sileo-header] { outline: 2px solid var(--sileo-focus); outline-offset: -2px; }
[data-sileo-header-stack] { position: relative; display: inline-flex; align-items: center; height: 100%; }
[data-sileo-header-inner] { display: flex; align-items: center; gap: .5rem; white-space: nowrap; opacity: 1; filter: blur(0); transform: translateZ(0); }
[data-sileo-header-inner][data-layer="current"] { position: relative; z-index: 1; animation: sileo-header-enter var(--sileo-duration) var(--sileo-spring-easing) both; }
[data-sileo-header-inner][data-layer="prev"] { position: absolute; left: 0; top: 0; z-index: 0; pointer-events: none; }
[data-sileo-header-inner][data-exiting="true"] { will-change: opacity, filter; animation: sileo-header-exit calc(var(--sileo-duration) * .7) ease forwards; }

[data-sileo-badge] {
    display: flex; height: 24px; width: 24px; flex-shrink: 0; align-items: center; justify-content: center; padding: 2px;
    box-sizing: border-box; border-radius: 9999px; color: var(--sileo-tone, currentColor); background-color: var(--sileo-tone-bg, transparent);
}
[data-sileo-badge] svg { display: block; }
[data-sileo-title] { font-size: .825rem; line-height: 1rem; font-weight: 500; color: var(--sileo-tone, currentColor); }

:is([data-sileo-badge], [data-sileo-title], [data-sileo-button])[data-state] { --_c: var(--sileo-state-success); }
:is([data-sileo-badge], [data-sileo-title], [data-sileo-button])[data-state="loading"] { --_c: var(--sileo-state-loading); }
:is([data-sileo-badge], [data-sileo-title], [data-sileo-button])[data-state="error"] { --_c: var(--sileo-state-error); }
:is([data-sileo-badge], [data-sileo-title], [data-sileo-button])[data-state="warning"] { --_c: var(--sileo-state-warning); }
:is([data-sileo-badge], [data-sileo-title], [data-sileo-button])[data-state="info"] { --_c: var(--sileo-state-info); }
:is([data-sileo-badge], [data-sileo-title], [data-sileo-button])[data-state="action"] { --_c: var(--sileo-state-action); }
:is([data-sileo-badge], [data-sileo-title])[data-state] { --sileo-tone: var(--_c); --sileo-tone-bg: color-mix(in oklch, var(--_c) 20%, transparent); }

[data-sileo-content] { position: absolute; left: 0; z-index: 10; width: 100%; pointer-events: none; opacity: var(--_co, 0); }
[data-sileo-content]:not([data-visible="true"]) { visibility: hidden; }
[data-sileo-toast][data-ready="true"] [data-sileo-content] {
    transition: opacity calc(var(--sileo-duration) * .08) ease calc(var(--sileo-duration) * .04), visibility 0s linear calc(var(--sileo-duration) * .12);
}
[data-sileo-toast][data-ready="true"] [data-sileo-content][data-visible="true"] {
    transition: opacity calc(var(--sileo-duration) * .6) ease calc(var(--sileo-duration) * .3), visibility 0s;
}
[data-sileo-content][data-edge="top"] { top: 0; }
[data-sileo-content][data-edge="bottom"] { top: var(--sileo-height); }
[data-sileo-content][data-visible="true"] { pointer-events: auto; }
[data-sileo-description] { width: 100%; text-align: left; padding: 1rem; font-size: .875rem; line-height: 1.25rem; color: var(--sileo-description); }

[data-sileo-button] {
    display: flex; align-items: center; justify-content: center; height: 1.75rem; padding: 0 .625rem; margin-top: .75rem;
    border-radius: 9999px; border: 0; font: inherit; font-size: .75rem; font-weight: 600; cursor: pointer;
    color: var(--sileo-btn-color, currentColor); background-color: var(--sileo-btn-bg, transparent); transition: background-color 150ms ease;
}
[data-sileo-button]:hover { background-color: var(--sileo-btn-bg-hover, transparent); }
[data-sileo-button]:focus-visible { outline: 2px solid var(--sileo-focus); outline-offset: 2px; }
[data-sileo-button][data-state] {
    --sileo-btn-color: var(--_c); --sileo-btn-bg: color-mix(in oklch, var(--_c) 15%, transparent);
    --sileo-btn-bg-hover: color-mix(in oklch, var(--_c) 25%, transparent);
}

[data-sileo-sr] { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
[data-sileo-icon="spin"] { animation: sileo-spin 1s linear infinite; }
@keyframes sileo-spin { to { transform: rotate(360deg); } }
@keyframes sileo-header-enter { from { opacity: 0; filter: blur(6px); } to { opacity: 1; filter: blur(0); } }
@keyframes sileo-header-exit { from { opacity: 1; filter: blur(0); } to { opacity: 0; filter: blur(6px); } }
@media (prefers-reduced-motion: no-preference) {
    [data-sileo-toast][data-ready="true"]:hover, [data-sileo-toast][data-ready="true"][data-exiting="true"] { will-change: transform, opacity, height; }
}
@media (prefers-reduced-motion: reduce) {
    [data-sileo-viewport], [data-sileo-viewport] *, [data-sileo-viewport] *::before, [data-sileo-viewport] *::after {
        animation-duration: .01ms; animation-iteration-count: 1; transition-duration: .01ms;
    }
}`;

    function inyectarEstilos() {
        if (document.getElementById('sileo-estilos')) return;
        const estilo = document.createElement('style');
        estilo.id = 'sileo-estilos';
        estilo.textContent = ESTILOS;
        (document.head || document.documentElement).appendChild(estilo);
    }

    /* ------------------------------------------ Resortes ----------------------------------------- */

    const activos = new Set();
    let cuadro = 0, antes = 0;
    function bucle(t) {
        const dt = antes ? Math.min((t - antes) / 1000, 0.05) : 1 / 60;
        antes = t;
        activos.forEach(r => r.paso(dt));
        if (activos.size) cuadro = requestAnimationFrame(bucle);
        else { cuadro = 0; antes = 0; }
    }

    // Un atributo del SVG que va hacia su meta con física de resorte y conserva la velocidad si la meta cambia en el camino.
    class Resorte {
        constructor(el, attr, valor, min, max) {
            this.el = el; this.attr = attr; this.valor = valor; this.meta = valor; this.vel = 0;
            this.min = min ?? -Infinity; this.max = max ?? Infinity; this.cfg = RESORTE;
            this.pintar();
        }
        a(meta, cfg) {
            if (meta === this.meta && !activos.has(this)) return;
            this.meta = meta;
            if (!cfg || (MENOS_MOVIMIENTO && MENOS_MOVIMIENTO.matches)) {
                this.valor = meta; this.vel = 0; activos.delete(this); this.pintar();
                return;
            }
            this.cfg = cfg;
            activos.add(this);
            if (!cuadro) cuadro = requestAnimationFrame(bucle);
        }
        paso(dt) {
            const k = this.cfg.k, c = this.cfg.c, n = Math.max(1, Math.ceil(dt / 0.004)), h = dt / n;
            for (let i = 0; i < n; i++) {
                this.vel += (-k * (this.valor - this.meta) - c * this.vel) * h;
                this.valor += this.vel * h;
            }
            if (Math.abs(this.vel) < 0.01 && Math.abs(this.valor - this.meta) < 0.01) {
                this.valor = this.meta; this.vel = 0; activos.delete(this);
            }
            this.pintar();
        }
        pintar() { this.el.setAttribute(this.attr, Math.min(this.max, Math.max(this.min, this.valor)).toFixed(2)); }
        detener() { activos.delete(this); }
    }

    /* ------------------------------------------ Utilidades ---------------------------------------- */

    const pillAlign = pos => pos.includes('right') ? 'right' : pos.includes('center') ? 'center' : 'left';
    const expandDir = pos => pos.startsWith('top') ? 'bottom' : 'top';
    function crear(etiqueta, atributos, ns) {
        const el = ns ? document.createElementNS(ns, etiqueta) : document.createElement(etiqueta);
        for (const k in atributos) if (atributos[k] != null) el.setAttribute(k, atributos[k]);
        return el;
    }
    const vista = item => {
        const state = item.state || 'success';
        return {
            title: item.title ?? TITULOS[state], description: item.description, state, icon: item.icon,
            styles: item.styles || {}, button: item.button, fill: item.fill || '#FFFFFF',
        };
    };
    const tieneDescripcion = v => Boolean(v.description) || Boolean(v.button);

    /* ------------------------------------------- Toast -------------------------------------------- */

    let filtros = 0;

    class Toast {
        constructor(item) {
            this.id = item.id;
            this.filterId = 'sileo-gooey-' + (++filtros);
            this.expand = expandDir(item.position);
            this.alinear = pillAlign(item.position);
            this.roundness = Math.max(0, item.roundness ?? DEFAULT_ROUNDNESS);
            this.blur = this.roundness * BLUR_RATIO;
            this.view = vista(item);
            this.refreshKey = item.instanceId;
            this.autoExp = item.autoExpandDelayMs;
            this.autoCol = item.autoCollapseDelayMs;
            this.expandido = false; this.listo = false; this.saliendo = false; this.puede = true;
            this.raton = false; this.foco = false; this.sobre = false; this.abierto = false; this.pendiente = null;
            this.anchoPildora = 0; this.altoContenido = 0; this.congelado = HEIGHT * MIN_EXPAND_RATIO;
            this.construir();
        }

        construir() {
            const v = this.view, r = this.roundness;
            this.root = crear('div', {
                'data-sileo-toast': '', 'data-ready': 'false', 'data-expanded': 'false', 'data-exiting': 'false',
                'data-edge': this.expand, 'data-position': this.alinear, 'data-state': v.state, tabindex: '0',
            });
            if (v.state === 'error') this.root.setAttribute('role', 'alert');

            // Lienzo: la píldora y el cuerpo, fundidos en una sola forma por el filtro "gooey"
            this.canvas = crear('div', { 'data-sileo-canvas': '', 'data-edge': this.expand });
            this.canvas.style.filter = 'url(#' + this.filterId + ')';
            this.svg = crear('svg', { 'data-sileo-svg': '', width: WIDTH, height: HEIGHT, viewBox: '0 0 ' + WIDTH + ' ' + HEIGHT, 'aria-hidden': 'true', focusable: 'false' }, SVG_NS);
            this.svg.innerHTML = '<defs><filter id="' + this.filterId + '" x="-20%" y="-20%" width="140%" height="140%" color-interpolation-filters="sRGB">'
                + '<feGaussianBlur in="SourceGraphic" stdDeviation="' + this.blur + '" result="blur"/>'
                + '<feColorMatrix in="blur" mode="matrix" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 20 -10" result="goo"/>'
                + '<feComposite in="SourceGraphic" in2="goo" operator="atop"/></filter></defs>';
            this.pildora = crear('rect', { 'data-sileo-pill': '', rx: r, ry: r, fill: v.fill }, SVG_NS);
            this.cuerpo = crear('rect', { 'data-sileo-body': '', y: HEIGHT, width: WIDTH, rx: r, ry: r, fill: v.fill }, SVG_NS);
            this.svg.append(this.pildora, this.cuerpo);
            this.canvas.appendChild(this.svg);
            this.p = { x: new Resorte(this.pildora, 'x', 0), w: new Resorte(this.pildora, 'width', HEIGHT, 0), h: new Resorte(this.pildora, 'height', HEIGHT, 0) };
            this.b = { h: new Resorte(this.cuerpo, 'height', 0, 0), o: new Resorte(this.cuerpo, 'opacity', 0, 0, 1) };

            // Encabezado: insignia + título (al cambiar de estado, el anterior se va desenfocándose)
            this.header = crear('div', { 'data-sileo-header': '', 'data-edge': this.expand });
            this.stack = crear('div', { 'data-sileo-header-stack': '' });
            this.inner = this.capaEncabezado(v, 'current');
            this.stack.appendChild(this.inner);
            this.header.appendChild(this.stack);
            this.claveEncabezado = v.state + '-' + v.title;

            // Texto completo para lectores de pantalla (la descripción visible se oculta al plegarse)
            this.sr = crear('span', { 'data-sileo-sr': '' });
            this.root.append(this.canvas, this.header, this.sr);
            this.pintarContenido();
            this.eventos();
        }

        capaEncabezado(v, capa) {
            const inner = crear('div', { 'data-sileo-header-inner': '', 'data-layer': capa });
            const insignia = crear('div', { 'data-sileo-badge': '', 'data-state': v.state });
            if (v.styles.badge) insignia.className = v.styles.badge;
            if (v.icon == null) insignia.innerHTML = ICONOS[v.state] || ICONOS.success;
            else if (v.icon instanceof Node) insignia.appendChild(v.icon.cloneNode(true));
            else insignia.innerHTML = String(v.icon); // solo íconos escritos en el código, nunca texto del usuario
            const titulo = crear('span', { 'data-sileo-title': '', 'data-state': v.state });
            if (v.styles.title) titulo.className = v.styles.title;
            titulo.textContent = v.title;
            inner.append(insignia, titulo);
            return inner;
        }

        pintarContenido() {
            const v = this.view;
            this.sr.textContent = v.title + (typeof v.description === 'string' && v.description ? '. ' + v.description : '');
            if (!tieneDescripcion(v)) {
                if (this.contenido) {
                    this.observadorContenido.disconnect();
                    this.contenido.remove();
                    this.contenido = this.desc = null;
                    this.altoContenido = 0;
                }
                return;
            }
            if (!this.contenido) {
                this.contenido = crear('div', { 'data-sileo-content': '', 'data-edge': this.expand, 'data-visible': 'false' });
                this.desc = crear('div', { 'data-sileo-description': '' });
                this.contenido.appendChild(this.desc);
                this.root.appendChild(this.contenido);
                this.observadorContenido = new ResizeObserver(() => {
                    cancelAnimationFrame(this.rafContenido);
                    this.rafContenido = requestAnimationFrame(() => this.medirContenido());
                });
                this.observadorContenido.observe(this.desc);
            }
            this.desc.className = v.styles.description || '';
            this.desc.textContent = '';
            if (v.description instanceof Node) this.desc.appendChild(v.description);
            else if (v.description) {
                const texto = crear('span', { 'aria-hidden': 'true' }); // ya lo lee el texto para lectores de pantalla
                texto.textContent = String(v.description);
                this.desc.appendChild(texto);
            }
            if (v.button) {
                const boton = crear('button', { type: 'button', 'data-sileo-button': '', 'data-state': v.state });
                if (v.styles.button) boton.className = v.styles.button;
                boton.textContent = v.button.title;
                boton.addEventListener('click', e => { e.preventDefault(); e.stopPropagation(); if (v.button.onClick) v.button.onClick(); });
                this.desc.appendChild(boton);
            }
            this.medirContenido();
        }

        eventos() {
            const r = this.root;
            const actualizarSobre = () => {
                const sobre = this.raton || this.foco;
                if (sobre === this.sobre) return;
                this.sobre = sobre;
                if (sobre) { entrar(this.id); if (tieneDescripcion(this.view)) this.setExpandido(true); }
                else { salir(); this.setExpandido(false); }
            };
            r.addEventListener('mouseenter', () => { this.raton = true; actualizarSobre(); });
            r.addEventListener('mouseleave', () => { this.raton = false; actualizarSobre(); });
            r.addEventListener('focusin', () => { this.foco = true; actualizarSobre(); });
            r.addEventListener('focusout', e => { if (r.contains(e.relatedTarget)) return; this.foco = false; actualizarSobre(); });
            r.addEventListener('keydown', e => { if (e.key === 'Escape') dismissToast(this.id); });
            r.addEventListener('transitionend', e => {
                if (e.propertyName !== 'height' && e.propertyName !== 'transform') return;
                if (!this.abierto && this.pendiente) this.aplicarPendiente();
            });

            // Deslizar hacia arriba o abajo para descartarlo
            let inicio = null;
            const mover = e => {
                if (inicio === null) return;
                const dy = e.clientY - inicio;
                r.style.transform = 'translateY(' + (dy > 0 ? 1 : -1) * Math.min(Math.abs(dy), SWIPE_MAX) + 'px)';
            };
            const soltar = e => {
                if (inicio === null) return;
                const dy = e.clientY - inicio;
                inicio = null;
                r.style.transform = '';
                r.removeEventListener('pointermove', mover);
                r.removeEventListener('pointerup', soltar);
                r.removeEventListener('pointercancel', soltar);
                if (Math.abs(dy) > SWIPE_DISMISS) dismissToast(this.id);
            };
            r.addEventListener('pointerdown', e => {
                if (this.saliendo || e.target.closest('[data-sileo-button]')) return;
                inicio = e.clientY;
                r.setPointerCapture(e.pointerId);
                r.addEventListener('pointermove', mover, { passive: true });
                r.addEventListener('pointerup', soltar, { passive: true });
                r.addEventListener('pointercancel', soltar, { passive: true });
            });
        }

        // Ya en la página: medir, pintar sin animación y, en el siguiente cuadro, entrar con animación.
        montar() {
            this.observadorPildora = new ResizeObserver(() => {
                cancelAnimationFrame(this.rafPildora);
                this.rafPildora = requestAnimationFrame(() => this.medirPildora());
            });
            this.observadorPildora.observe(this.inner);
            this.medirPildora();
            if (this.desc) this.medirContenido();
            this.pintar();
            void this.root.getBoundingClientRect();
            this.rafListo = requestAnimationFrame(() => {
                this.listo = true;
                this.root.setAttribute('data-ready', 'true');
                this.pintar();
            });
            this.autopiloto();
        }

        medirPildora() {
            if (!this.root.isConnected) return;
            if (this.padHeader == null) {
                const cs = getComputedStyle(this.header);
                this.padHeader = parseFloat(cs.paddingLeft) + parseFloat(cs.paddingRight);
            }
            const w = this.inner.scrollWidth + this.padHeader + PILL_PADDING;
            if (w > PILL_PADDING && w !== this.anchoPildora) { this.anchoPildora = w; this.pintar(); }
        }

        medirContenido() {
            if (!this.desc || !this.root.isConnected) return;
            const h = this.desc.scrollHeight;
            if (h !== this.altoContenido) { this.altoContenido = h; this.pintar(); }
        }

        pintar() {
            const v = this.view, conDescripcion = tieneDescripcion(v);
            const open = conDescripcion && this.expandido && v.state !== 'loading';
            const minExpanded = HEIGHT * MIN_EXPAND_RATIO;
            const raw = conDescripcion ? Math.max(minExpanded, HEIGHT + this.altoContenido) : minExpanded;
            if (open) this.congelado = raw;
            const expanded = open ? raw : this.congelado;
            const svgHeight = conDescripcion ? Math.max(expanded, minExpanded) : HEIGHT;
            const expandedContent = Math.max(0, expanded - HEIGHT);
            const pw = Math.min(WIDTH, Math.max(this.anchoPildora || HEIGHT, HEIGHT));
            const px = this.alinear === 'right' ? WIDTH - pw : this.alinear === 'center' ? (WIDTH - pw) / 2 : 0;

            this.svg.setAttribute('height', svgHeight);
            this.svg.setAttribute('viewBox', '0 0 ' + WIDTH + ' ' + svgHeight);
            const resortePildora = this.listo ? RESORTE : null;
            this.p.x.a(px, resortePildora);
            this.p.w.a(pw, resortePildora);
            this.p.h.a(open ? HEIGHT + this.blur * 3 : HEIGHT, resortePildora);
            const resorteCuerpo = open ? RESORTE : RESORTE_SIN_REBOTE;
            this.b.h.a(open ? expandedContent : 0, resorteCuerpo);
            this.b.o.a(open ? 1 : 0, resorteCuerpo);

            const s = this.root.style;
            s.setProperty('--_h', (open ? expanded : HEIGHT) + 'px');
            s.setProperty('--_pw', pw + 'px');
            s.setProperty('--_px', px + 'px');
            s.setProperty('--_ht', 'translateY(' + (open ? (this.expand === 'bottom' ? 3 : -3) : 0) + 'px) scale(' + (open ? 0.9 : 1) + ')');
            s.setProperty('--_co', open ? '1' : '0');
            this.root.setAttribute('data-expanded', String(open));
            if (this.contenido) this.contenido.setAttribute('data-visible', String(open));
            this.abierto = open;
        }

        setExpandido(valor) {
            if (valor === this.expandido) return;
            this.expandido = valor;
            this.pintar();
        }

        permite() { return this.view.state === 'loading' ? false : this.puede; }

        // Se abre solo al aparecer y se pliega antes de irse (autopilot de Sileo)
        autopiloto() {
            clearTimeout(this.tExpandir); clearTimeout(this.tPlegar);
            if (!tieneDescripcion(this.view)) return;
            if (this.saliendo || !this.permite()) { this.setExpandido(false); return; }
            if (this.autoExp == null && this.autoCol == null) return;
            const expandir = this.autoExp || 0, plegar = this.autoCol || 0;
            if (expandir > 0) this.tExpandir = setTimeout(() => this.setExpandido(true), expandir);
            else this.setExpandido(true);
            if (plegar > 0) this.tPlegar = setTimeout(() => { if (!this.sobre) this.setExpandido(false); }, plegar);
        }

        setPuedeExpandir(valor) {
            if (valor === this.puede) return;
            this.puede = valor;
            this.autopiloto();
        }

        setSaliendo(valor) {
            if (valor === this.saliendo) return;
            this.saliendo = valor;
            this.root.setAttribute('data-exiting', String(valor));
            this.autopiloto();
        }

        // Mismo id con contenido nuevo: si está abierto, primero se pliega y luego cambia.
        actualizar(item) {
            this.autoExp = item.autoExpandDelayMs;
            this.autoCol = item.autoCollapseDelayMs;
            if (item.instanceId === this.refreshKey) return;
            this.refreshKey = item.instanceId;
            clearTimeout(this.tCambio);
            const siguiente = vista(item);
            if (this.abierto) {
                this.pendiente = siguiente;
                this.setExpandido(false);
                this.tCambio = setTimeout(() => this.aplicarPendiente(), SWAP_COLLAPSE_MS);
            } else {
                this.pendiente = null;
                this.aplicar(siguiente);
            }
        }

        aplicarPendiente() {
            clearTimeout(this.tCambio);
            const p = this.pendiente;
            if (!p) return;
            this.pendiente = null;
            this.aplicar(p);
        }

        aplicar(v) {
            this.view = v;
            this.root.setAttribute('data-state', v.state);
            if (v.state === 'error') this.root.setAttribute('role', 'alert'); else this.root.removeAttribute('role');
            this.pildora.setAttribute('fill', v.fill);
            this.cuerpo.setAttribute('fill', v.fill);
            const clave = v.state + '-' + v.title;
            if (clave !== this.claveEncabezado) {
                this.claveEncabezado = clave;
                this.stack.querySelectorAll('[data-layer="prev"]').forEach(e => e.remove());
                const previo = this.inner;
                previo.setAttribute('data-layer', 'prev');
                previo.setAttribute('data-exiting', 'true');
                this.inner = this.capaEncabezado(v, 'current');
                this.stack.insertBefore(this.inner, previo);
                clearTimeout(this.tEncabezado);
                this.tEncabezado = setTimeout(() => previo.remove(), HEADER_EXIT_MS);
                if (this.observadorPildora) { this.observadorPildora.disconnect(); this.observadorPildora.observe(this.inner); }
            } else {
                this.inner.replaceChildren(...this.capaEncabezado(v, 'current').childNodes);
            }
            this.pintarContenido();
            this.medirPildora();
            this.pintar();
            this.autopiloto();
        }

        destruir() {
            clearTimeout(this.tExpandir); clearTimeout(this.tPlegar); clearTimeout(this.tCambio); clearTimeout(this.tEncabezado);
            cancelAnimationFrame(this.rafListo); cancelAnimationFrame(this.rafPildora); cancelAnimationFrame(this.rafContenido);
            if (this.observadorPildora) this.observadorPildora.disconnect();
            if (this.observadorContenido) this.observadorContenido.disconnect();
            [this.p.x, this.p.w, this.p.h, this.b.h, this.b.o].forEach(r => r.detener());
            if (this.sobre) salir();
            this.root.remove();
        }
    }

    /* ----------------------------------- Estado global (Toaster) ----------------------------------- */

    const store = { toasts: [], position: 'bottom-right', options: { fill: '#0f2547' }, offset: undefined };
    const vistas = new Map();
    const viewports = new Map();
    const timers = new Map();
    let sobre = false, activeId, ultimoLatest;
    let idCounter = 0;
    const generateId = () => (++idCounter) + '-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 8);
    const timeoutKey = t => t.id + ':' + t.instanceId;

    function update(fn) {
        store.toasts = fn(store.toasts);
        render();
    }

    function viewport(pos) {
        let v = viewports.get(pos);
        if (!v) {
            v = crear('section', { 'data-sileo-viewport': '', 'data-position': pos, 'aria-live': 'polite', 'aria-label': 'Notificaciones' });
            aplicarOffset(v, pos);
            document.body.appendChild(v);
            viewports.set(pos, v);
        }
        return v;
    }

    function aplicarOffset(v, pos) {
        const o = store.offset;
        v.style.top = v.style.bottom = v.style.left = v.style.right = '';
        if (o === undefined) return;
        const c = typeof o === 'object' ? o : { top: o, right: o, bottom: o, left: o };
        const px = x => typeof x === 'number' ? x + 'px' : x;
        if (pos.startsWith('top') && c.top) v.style.top = px(c.top);
        if (pos.startsWith('bottom') && c.bottom) v.style.bottom = px(c.bottom);
        if (pos.endsWith('left') && c.left) v.style.left = px(c.left);
        if (pos.endsWith('right') && c.right) v.style.right = px(c.right);
    }

    let esperandoBody = false;
    function render() {
        if (!document.body) {
            if (!esperandoBody) { esperandoBody = true; document.addEventListener('DOMContentLoaded', () => { esperandoBody = false; render(); }); }
            return;
        }
        const ids = new Set(store.toasts.map(t => t.id));
        for (const [id, v] of vistas) if (!ids.has(id)) { v.destruir(); vistas.delete(id); }
        for (const item of store.toasts) {
            let v = vistas.get(item.id);
            if (!v) {
                v = new Toast(item);
                vistas.set(item.id, v);
                viewport(item.position).appendChild(v.root);
                v.montar();
            } else {
                v.actualizar(item);
            }
            v.setSaliendo(!!item.exiting);
        }

        // Solo el último (o el que está bajo el puntero) se abre
        let latest;
        for (let i = store.toasts.length - 1; i >= 0; i--) if (!store.toasts[i].exiting) { latest = store.toasts[i].id; break; }
        if (latest !== ultimoLatest) { ultimoLatest = latest; activeId = latest; }
        vistas.forEach((v, id) => v.setPuedeExpandir(activeId === undefined || activeId === id));

        const claves = new Set(store.toasts.map(timeoutKey));
        for (const [k, t] of timers) if (!claves.has(k)) { clearTimeout(t); timers.delete(k); }
        schedule();
    }

    function schedule() {
        if (sobre) return;
        for (const item of store.toasts) {
            if (item.exiting || item.duration === null) continue;
            const k = timeoutKey(item);
            if (timers.has(k)) continue;
            const dur = item.duration ?? DEFAULT_TOAST_DURATION;
            if (dur <= 0) continue;
            timers.set(k, setTimeout(() => dismissToast(item.id), dur));
        }
    }

    // Con el puntero (o el foco) encima, ningún toast se va; al salir, cada uno vuelve a contar su tiempo.
    function entrar(id) {
        if (activeId !== id) { activeId = id; vistas.forEach((v, i) => v.setPuedeExpandir(i === id)); }
        if (sobre) return;
        sobre = true;
        timers.forEach(t => clearTimeout(t));
        timers.clear();
    }
    function salir() {
        if (activeId !== ultimoLatest) { activeId = ultimoLatest; vistas.forEach((v, i) => v.setPuedeExpandir(activeId === undefined || activeId === i)); }
        if (!sobre) return;
        sobre = false;
        schedule();
    }

    function dismissToast(id) {
        const item = store.toasts.find(t => t.id === id);
        if (!item || item.exiting) return;
        update(prev => prev.map(t => t.id === id ? Object.assign({}, t, { exiting: true }) : t));
        // Solo quita el que salía: si en ese medio segundo llegó otro con el mismo id, se queda.
        setTimeout(() => update(prev => prev.filter(t => !(t.id === id && t.exiting))), EXIT_DURATION);
    }

    function resolveAutopilot(opts, duration) {
        if (opts.autopilot === false || !duration || duration <= 0) return {};
        const cfg = typeof opts.autopilot === 'object' ? opts.autopilot : undefined;
        const clamp = v => Math.min(duration, Math.max(0, v));
        return { expandDelayMs: clamp(cfg?.expand ?? AUTO_EXPAND_DELAY), collapseDelayMs: clamp(cfg?.collapse ?? AUTO_COLLAPSE_DELAY) };
    }

    const mergeOptions = options => Object.assign({}, store.options, options, { styles: Object.assign({}, store.options?.styles, options.styles) });

    function buildItem(merged, id, fallbackPosition) {
        const duration = merged.duration ?? DEFAULT_TOAST_DURATION;
        const auto = resolveAutopilot(merged, duration);
        return Object.assign({}, merged, {
            id, instanceId: generateId(), exiting: false,
            position: merged.position ?? fallbackPosition ?? store.position,
            autoExpandDelayMs: auto.expandDelayMs, autoCollapseDelayMs: auto.collapseDelayMs,
        });
    }

    function createToast(options) {
        const live = store.toasts.filter(t => !t.exiting);
        const merged = mergeOptions(options);
        const id = merged.id ?? 'sileo-default';
        const prev = live.find(t => t.id === id);
        const item = buildItem(merged, id, prev?.position);
        if (prev) update(p => p.map(t => t.id === id ? item : t));
        else update(p => [...p.filter(t => t.id !== id), item]);
        return { id, duration: merged.duration ?? DEFAULT_TOAST_DURATION };
    }

    function updateToast(id, options) {
        const existing = store.toasts.find(t => t.id === id);
        if (!existing) return;
        const item = buildItem(mergeOptions(options), id, existing.position);
        update(prev => prev.map(t => t.id === id ? item : t));
    }

    /* ---------------------------------------------- API --------------------------------------------- */

    const conEstado = state => (opts = {}) => createToast(Object.assign({}, opts, { state })).id;

    const sileo = {
        show: (opts = {}) => createToast(Object.assign({}, opts, { state: opts.type })).id,
        success: conEstado('success'),
        error: conEstado('error'),
        warning: conEstado('warning'),
        info: conEstado('info'),
        action: conEstado('action'),

        promise(promesa, opts) {
            const { id } = createToast(Object.assign({}, opts.loading, { state: 'loading', duration: null, position: opts.position }));
            const p = typeof promesa === 'function' ? promesa() : promesa;
            p.then(datos => {
                if (opts.action) {
                    const a = typeof opts.action === 'function' ? opts.action(datos) : opts.action;
                    updateToast(id, Object.assign({}, a, { state: 'action', id }));
                } else {
                    const s = typeof opts.success === 'function' ? opts.success(datos) : opts.success;
                    updateToast(id, Object.assign({}, s, { state: 'success', id }));
                }
            }).catch(err => {
                const e = typeof opts.error === 'function' ? opts.error(err) : opts.error;
                updateToast(id, Object.assign({}, e, { state: 'error', id }));
            });
            return p;
        },

        dismiss: dismissToast,

        clear(position) { update(prev => position ? prev.filter(t => t.position !== position) : []); },

        // Equivale a las props de <Toaster position offset options />
        toaster(cfg = {}) {
            if (cfg.position) store.position = cfg.position;
            if (cfg.options) store.options = Object.assign({}, store.options, cfg.options);
            if ('offset' in cfg) { store.offset = cfg.offset; viewports.forEach(aplicarOffset); }
        },

        // Propio del turnero: guardar y recargar la página, y mostrar el toast al volver.
        recargarCon(opciones) {
            try { sessionStorage.setItem(PENDIENTE, JSON.stringify(opciones || {})); } catch (e) {}
            location.reload();
        },
    };

    function iniciar() {
        viewport(store.position); // la región viva ya existe cuando llega el primer toast
        let pendiente = null;
        try {
            pendiente = JSON.parse(sessionStorage.getItem(PENDIENTE) || 'null');
            sessionStorage.removeItem(PENDIENTE);
        } catch (e) {}
        if (pendiente) createToast(Object.assign({}, pendiente, { state: pendiente.type || 'success' }));
    }

    inyectarEstilos();
    window.sileo = sileo;
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar);
    else iniciar();
})();
