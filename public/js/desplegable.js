/*
 * desplegable.js — desplegables propios del panel en lugar de la lista nativa del navegador.
 *
 * Se monta SOBRE cada <select> (no lo reemplaza): el select real sigue en la página, oculto, con su valor, su name y sus
 * eventos. Así siguen funcionando igual x-model de Alpine, los @change, los formularios y el código que cambia el valor.
 *  - Elegir una opción pone el valor en el select y dispara 'input' y 'change'.
 *  - Si el código cambia el valor, las opciones o el disabled del select, el botón se actualiza solo.
 *  - Teclado: flechas, Inicio/Fin, Enter, Esc, Tab y escribir para saltar. Con más de 8 opciones, un buscador arriba.
 *  - Se salta: <select multiple>, <select size="…">, data-nativo, y las pantallas táctiles (ahí el selector nativo es mejor).
 */
(function () {
    'use strict';

    if (window.desplegable) return;
    if (!window.matchMedia || !window.matchMedia('(pointer: fine)').matches) {
        window.desplegable = { montar: function () {} };
        return;
    }

    var instancias = [];
    var abierta = null;          // la instancia con la lista abierta
    var secuencia = 0;
    var CON_BUSCADOR = 8;        // desde cuántas opciones aparece el buscador
    var NS = 'http://www.w3.org/2000/svg';

    var descValor = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value');
    var descIndice = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'selectedIndex');
    var descMarcada = Object.getOwnPropertyDescriptor(HTMLOptionElement.prototype, 'selected');

    // x-model de Alpine elige con option.selected (no con select.value): el desplegable de ese select se entera al instante
    try {
        Object.defineProperty(HTMLOptionElement.prototype, 'selected', {
            configurable: true,
            enumerable: descMarcada.enumerable,
            get: descMarcada.get,
            set: function (v) {
                descMarcada.set.call(this, v);
                var s = this.parentElement && this.parentElement.closest('select');
                if (s && s.__desplegable) s.__desplegable.pintar();
            }
        });
    } catch (e) { /* queda el ciclo de sincronización */ }

    function icono(d, clase) {
        var svg = document.createElementNS(NS, 'svg');
        svg.setAttribute('viewBox', '0 0 24 24');
        svg.setAttribute('fill', 'none');
        svg.setAttribute('stroke', 'currentColor');
        svg.setAttribute('stroke-width', '2');
        svg.setAttribute('stroke-linecap', 'round');
        svg.setAttribute('stroke-linejoin', 'round');
        svg.setAttribute('aria-hidden', 'true');
        svg.setAttribute('class', clase);
        var p = document.createElementNS(NS, 'path');
        p.setAttribute('d', d);
        svg.appendChild(p);
        return svg;
    }

    // Texto de la <label> del select sin lo que trae dentro (las opciones, ayudas y errores)
    function textoDeEtiqueta(select) {
        var label = select.labels && select.labels[0];
        if (!label) return '';
        var copia = label.cloneNode(true);
        Array.prototype.forEach.call(copia.querySelectorAll('select, button, small, .form-ayuda, .form-error'), function (n) { n.remove(); });
        return copia.textContent.replace(/\s+/g, ' ').trim().replace(/\s*\*$/, '');   // «Estado *» → «Estado»
    }

    function normal(t) {
        return String(t || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();
    }

    function Desplegable(select) {
        var self = this;
        this.select = select;
        this.id = 'desplegable-' + (++secuencia);
        this.opciones = [];      // [{ valor, texto, sangria, deshabilitada, grupo, indice }]
        this.activa = -1;        // posición (en las visibles) resaltada con el teclado
        this.filtro = '';
        this.firma = '';

        // Botón: hereda las clases del select, así se ve igual de ancho y de alto que antes
        var boton = document.createElement('button');
        boton.type = 'button';
        boton.className = (select.className || '') + ' desplegable__boton';
        boton.setAttribute('aria-haspopup', 'listbox');
        boton.setAttribute('aria-expanded', 'false');
        if (select.id) boton.id = select.id + '__boton';
        var etiqueta = select.getAttribute('aria-label') || textoDeEtiqueta(select);
        if (etiqueta) boton.setAttribute('aria-label', etiqueta);
        var texto = document.createElement('span');
        texto.className = 'desplegable__texto';
        boton.appendChild(texto);
        boton.appendChild(icono('m6 9 6 6 6-6', 'desplegable__flecha'));
        this.boton = boton;
        this.texto = texto;

        // Lista (va al <body> para que no la recorte ninguna tabla ni ventana)
        var panel = document.createElement('div');
        panel.className = 'desplegable__panel';
        panel.hidden = true;
        var buscador = document.createElement('input');
        buscador.type = 'search';
        buscador.className = 'desplegable__buscar';
        buscador.placeholder = 'Buscar';
        buscador.setAttribute('aria-label', 'Buscar en la lista');
        buscador.autocomplete = 'off';
        var lista = document.createElement('div');
        lista.className = 'desplegable__lista';
        lista.id = this.id + '__lista';
        lista.setAttribute('role', 'listbox');
        lista.tabIndex = -1;
        var vacio = document.createElement('p');
        vacio.className = 'desplegable__vacio';
        vacio.textContent = 'Sin resultados';
        vacio.hidden = true;
        panel.appendChild(buscador);
        panel.appendChild(lista);
        panel.appendChild(vacio);
        boton.setAttribute('aria-controls', lista.id);
        this.panel = panel;
        this.buscador = buscador;
        this.lista = lista;
        this.vacio = vacio;

        select.classList.add('desplegable__nativo');
        select.tabIndex = -1;
        select.setAttribute('aria-hidden', 'true');
        select.insertAdjacentElement('afterend', boton);
        document.body.appendChild(panel);

        // Si el código cambia el valor del select, el botón lo refleja de inmediato
        try {
            Object.defineProperty(select, 'value', {
                configurable: true,
                get: function () { return descValor.get.call(this); },
                set: function (v) { descValor.set.call(this, v); self.pintar(); }
            });
            Object.defineProperty(select, 'selectedIndex', {
                configurable: true,
                get: function () { return descIndice.get.call(this); },
                set: function (v) { descIndice.set.call(this, v); self.pintar(); }
            });
        } catch (e) { /* sin el atajo, igual se sincroniza en el siguiente ciclo */ }

        // Opciones nuevas (Alpine x-for, código que llena la lista), disabled, selected
        new MutationObserver(function () { self.leer(); self.pintar(); })
            .observe(select, { childList: true, subtree: true, attributes: true, characterData: true, attributeFilter: ['disabled', 'selected', 'label', 'value', 'class'] });

        select.addEventListener('change', function () { self.pintar(); boton.classList.remove('desplegable__boton--invalido'); });
        // Un select obligatorio sin elegir: el navegador lo enfoca para mostrar su aviso; ese foco se deja quieto
        select.addEventListener('invalid', function () {
            self.validando = true;
            setTimeout(function () { self.validando = false; }, 0);
            boton.classList.add('desplegable__boton--invalido');
        });
        // Una <label> que apunta al select lo enfoca: el foco pasa al botón
        select.addEventListener('focus', function () { if (!self.validando) boton.focus(); });

        boton.addEventListener('click', function () { self.alternar(); });
        boton.addEventListener('keydown', function (e) { self.teclaBoton(e); });
        buscador.addEventListener('input', function () { self.filtro = buscador.value; self.dibujarLista(true); });
        buscador.addEventListener('keydown', function (e) { self.teclaLista(e, true); });
        lista.addEventListener('keydown', function (e) { self.teclaLista(e, false); });
        lista.addEventListener('mousedown', function (e) { e.preventDefault(); });   // no robar el foco al buscador
        lista.addEventListener('click', function (e) {
            var fila = e.target.closest('.desplegable__opcion');
            if (fila && !fila.hasAttribute('aria-disabled')) self.elegir(Number(fila.dataset.indice));
        });
        lista.addEventListener('mousemove', function (e) {
            var fila = e.target.closest('.desplegable__opcion');
            if (fila) self.resaltar(Number(fila.dataset.posicion), false);
        });

        this.leer();
        this.pintar();
    }

    Desplegable.prototype.leer = function () {
        var opciones = [];
        Array.prototype.forEach.call(this.select.options, function (op, indice) {
            var crudo = op.textContent || '';
            var sangria = (crudo.match(/^[\s ]*/) || [''])[0].length;
            opciones.push({
                valor: op.value,
                texto: crudo.replace(/\s+/g, ' ').trim() || op.label || '',
                sangria: sangria,
                deshabilitada: op.disabled || (op.parentNode && op.parentNode.tagName === 'OPTGROUP' && op.parentNode.disabled),
                grupo: op.parentNode && op.parentNode.tagName === 'OPTGROUP' ? op.parentNode.label : null,
                indice: indice
            });
        });
        this.opciones = opciones;
        if (abierta === this) this.dibujarLista(false);
    };

    // Texto del botón y estado (también lo llama el ciclo de sincronización)
    Desplegable.prototype.pintar = function () {
        var i = descIndice.get.call(this.select);
        var op = this.select.options[i];
        var texto = op ? (op.textContent || '').replace(/\s+/g, ' ').trim() : '';
        var firma = i + '|' + texto + '|' + this.select.disabled + '|' + this.select.options.length;
        if (firma === this.firma) return;
        this.firma = firma;
        this.texto.textContent = texto || ' ';
        this.boton.classList.toggle('desplegable__boton--vacio', !op || op.value === '');
        this.boton.disabled = this.select.disabled;
        if (this.select.disabled && abierta === this) this.cerrar(false);
        if (this.select.options.length !== this.opciones.length) this.leer();
    };

    Desplegable.prototype.visibles = function () {
        var q = normal(this.filtro);
        return this.opciones.filter(function (o) { return !q || normal(o.texto).indexOf(q) !== -1; });
    };

    Desplegable.prototype.dibujarLista = function (reiniciarResalte) {
        var self = this;
        var visibles = this.visibles();
        var actual = descIndice.get.call(this.select);
        this.lista.textContent = '';
        var grupo = null;
        visibles.forEach(function (o, posicion) {
            if (o.grupo && o.grupo !== grupo) {
                grupo = o.grupo;
                var titulo = document.createElement('div');
                titulo.className = 'desplegable__grupo';
                titulo.textContent = o.grupo;
                self.lista.appendChild(titulo);
            }
            var fila = document.createElement('div');
            fila.className = 'desplegable__opcion';
            fila.id = self.id + '__op' + o.indice;
            fila.setAttribute('role', 'option');
            fila.dataset.indice = o.indice;
            fila.dataset.posicion = posicion;
            if (o.sangria) fila.style.paddingLeft = (0.625 + Math.min(o.sangria, 6) * 0.25) + 'rem';
            if (o.deshabilitada) fila.setAttribute('aria-disabled', 'true');
            var seleccionada = o.indice === actual;
            fila.setAttribute('aria-selected', seleccionada ? 'true' : 'false');
            var t = document.createElement('span');
            t.className = 'desplegable__opcion-texto';
            t.textContent = o.texto || ' ';
            fila.appendChild(t);
            if (seleccionada) fila.appendChild(icono('M20 6 9 17l-5-5', 'desplegable__marca'));
            self.lista.appendChild(fila);
        });
        this.vacio.hidden = visibles.length > 0;
        if (reiniciarResalte || this.activa >= visibles.length) {
            var pos = -1;
            visibles.some(function (o, p) { if (o.indice === actual && !self.filtro) { pos = p; return true; } return false; });
            if (pos < 0) pos = this.siguienteHabilitada(-1, 1, visibles);
            this.resaltar(pos, true);
        } else {
            this.resaltar(this.activa, false);
        }
    };

    Desplegable.prototype.siguienteHabilitada = function (desde, paso, visibles) {
        visibles = visibles || this.visibles();
        for (var p = desde + paso; p >= 0 && p < visibles.length; p += paso) {
            if (!visibles[p].deshabilitada) return p;
        }
        return desde;
    };

    Desplegable.prototype.resaltar = function (posicion, desplazar) {
        var filas = this.lista.querySelectorAll('.desplegable__opcion');
        this.activa = posicion;
        var objetivo = null;
        Array.prototype.forEach.call(filas, function (f) {
            var es = Number(f.dataset.posicion) === posicion;
            f.classList.toggle('desplegable__opcion--activa', es);
            if (es) objetivo = f;
        });
        var duenoDelFoco = this.buscador.hidden ? this.lista : this.buscador;
        if (objetivo) duenoDelFoco.setAttribute('aria-activedescendant', objetivo.id);
        else duenoDelFoco.removeAttribute('aria-activedescendant');
        if (objetivo && desplazar) objetivo.scrollIntoView({ block: 'nearest' });
    };

    Desplegable.prototype.ubicar = function () {
        var r = this.boton.getBoundingClientRect();
        var panel = this.panel;
        panel.style.minWidth = r.width + 'px';
        panel.style.maxWidth = Math.max(r.width, Math.min(26 * 16, window.innerWidth - 16)) + 'px';
        panel.style.left = '0px';
        panel.style.top = '0px';
        var alto = panel.offsetHeight;
        var ancho = panel.offsetWidth;
        var abajo = window.innerHeight - r.bottom - 8;
        var arriba = r.top - 8;
        var top = (abajo < alto && arriba > abajo) ? Math.max(8, r.top - alto - 4) : r.bottom + 4;
        var left = Math.min(r.left, window.innerWidth - ancho - 8);
        panel.style.left = Math.max(8, left) + 'px';
        panel.style.top = top + 'px';
        panel.classList.toggle('desplegable__panel--arriba', top < r.top);
    };

    Desplegable.prototype.abrir = function () {
        if (this.select.disabled) return;
        if (abierta && abierta !== this) abierta.cerrar(false);
        abierta = this;
        this.leer();
        this.filtro = '';
        this.buscador.value = '';
        this.buscador.hidden = this.opciones.length <= CON_BUSCADOR;
        this.panel.hidden = false;
        this.boton.setAttribute('aria-expanded', 'true');
        this.boton.classList.add('desplegable__boton--abierto');
        this.dibujarLista(true);
        this.ubicar();
        (this.buscador.hidden ? this.lista : this.buscador).focus({ preventScroll: true });
        var activa = this.lista.querySelector('.desplegable__opcion--activa');
        if (activa) activa.scrollIntoView({ block: 'nearest' });
    };

    Desplegable.prototype.cerrar = function (devolverFoco) {
        if (abierta !== this) return;
        abierta = null;
        this.panel.hidden = true;
        this.boton.setAttribute('aria-expanded', 'false');
        this.boton.classList.remove('desplegable__boton--abierto');
        if (devolverFoco) this.boton.focus({ preventScroll: true });
    };

    Desplegable.prototype.alternar = function () {
        if (abierta === this) this.cerrar(true); else this.abrir();
    };

    Desplegable.prototype.elegir = function (indice) {
        var op = this.select.options[indice];
        if (!op || op.disabled) return;
        var cambia = descIndice.get.call(this.select) !== indice;
        descIndice.set.call(this.select, indice);
        this.pintar();
        this.cerrar(true);
        if (cambia) {
            this.select.dispatchEvent(new Event('input', { bubbles: true }));
            this.select.dispatchEvent(new Event('change', { bubbles: true }));
        }
    };

    Desplegable.prototype.teclaBoton = function (e) {
        if (['ArrowDown', 'ArrowUp', 'Enter', ' ', 'Spacebar'].indexOf(e.key) !== -1) {
            e.preventDefault();
            this.abrir();
            return;
        }
        // Escribir con la lista cerrada salta a la opción que empieza así (como el select nativo)
        if (e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
            var q = normal(e.key);
            var actual = descIndice.get.call(this.select);
            var orden = this.opciones.slice(actual + 1).concat(this.opciones.slice(0, actual + 1));
            var hallada = orden.filter(function (o) { return !o.deshabilitada && normal(o.texto).indexOf(q) === 0; })[0];
            if (hallada) { e.preventDefault(); this.elegir(hallada.indice); this.boton.focus(); }
        }
    };

    Desplegable.prototype.teclaLista = function (e, desdeBuscador) {
        var visibles = this.visibles();
        switch (e.key) {
            case 'ArrowDown': e.preventDefault(); this.resaltar(this.siguienteHabilitada(this.activa, 1, visibles), true); break;
            case 'ArrowUp': e.preventDefault(); this.resaltar(this.siguienteHabilitada(this.activa, -1, visibles), true); break;
            case 'Home': if (!desdeBuscador) { e.preventDefault(); this.resaltar(this.siguienteHabilitada(-1, 1, visibles), true); } break;
            case 'End': if (!desdeBuscador) { e.preventDefault(); this.resaltar(this.siguienteHabilitada(visibles.length, -1, visibles), true); } break;
            case 'Enter':
                e.preventDefault();
                if (visibles[this.activa] && !visibles[this.activa].deshabilitada) this.elegir(visibles[this.activa].indice);
                break;
            case 'Escape': e.preventDefault(); e.stopPropagation(); this.cerrar(true); break;
            case 'Tab': this.cerrar(false); break;
            default:
                // Sin buscador: escribir salta a la opción que empieza así
                if (!desdeBuscador && e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
                    var q = normal(e.key);
                    var desde = this.activa;
                    var orden = visibles.map(function (o, p) { return p; });
                    orden = orden.slice(desde + 1).concat(orden.slice(0, desde + 1));
                    var p = orden.filter(function (p) { return !visibles[p].deshabilitada && normal(visibles[p].texto).indexOf(q) === 0; })[0];
                    if (p !== undefined) this.resaltar(p, true);
                }
        }
    };

    function montar(raiz) {
        var selects = (raiz || document).querySelectorAll ? (raiz || document).querySelectorAll('select') : [];
        Array.prototype.forEach.call(selects, function (s) {
            if (s.__desplegable || s.multiple || (s.size && s.size > 1) || s.hasAttribute('data-nativo')) return;
            s.__desplegable = new Desplegable(s);
            instancias.push(s.__desplegable);
        });
    }

    // Cerrar al tocar fuera, al desplazar la página (no la lista) o al cambiar el tamaño
    document.addEventListener('mousedown', function (e) {
        if (abierta && !abierta.panel.contains(e.target) && !abierta.boton.contains(e.target)) abierta.cerrar(false);
    }, true);
    window.addEventListener('scroll', function (e) {
        if (abierta && !abierta.panel.contains(e.target)) abierta.cerrar(false);
    }, true);
    window.addEventListener('resize', function () { if (abierta) abierta.cerrar(false); });

    // Red de seguridad para cambios que no pasan por los atajos (p. ej. form.reset()); y limpia los selects que ya no están
    setInterval(function () {
        instancias = instancias.filter(function (d) {
            if (!d.select.isConnected) { d.boton.remove(); d.panel.remove(); return false; }
            d.pintar();
            // Si se cerró la ventana o la sección del botón, su lista no queda flotando
            if (abierta === d && !d.boton.getClientRects().length) d.cerrar(false);
            return true;
        });
    }, 300);

    function iniciar() {
        montar(document);
        // Selects que aparecen después (x-if, ventanas, listas que se pintan con JS)
        new MutationObserver(function (cambios) {
            cambios.forEach(function (c) {
                Array.prototype.forEach.call(c.addedNodes, function (n) {
                    if (n.nodeType !== 1) return;
                    if (n.tagName === 'SELECT') montar(n.parentNode); else if (n.querySelector && n.querySelector('select')) montar(n);
                });
            });
        }).observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar);
    else iniciar();

    window.desplegable = { montar: montar };
})();
