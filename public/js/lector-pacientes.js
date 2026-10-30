/*
 * lector-pacientes.js — lee en el navegador el Excel (.xlsx) o CSV con los pacientes y saca solo documento y nombre.
 *
 * El archivo no se sube: trae teléfonos, EPS y observaciones médicas que el turnero no necesita (y con 170 MB de XML
 * tampoco conviene procesarlo en el hosting). Se lee por partes, sin librerías, así que sirve para archivos grandes.
 * Las reglas del documento son las mismas de Paciente::claveDocumento en el servidor.
 *
 * Uso: const r = await leerPacientes(archivo, { alProgreso: fraccion => ..., senal: abortController.signal });
 *      r.pacientes = [[documento, nombre], ...]; el resto son cifras para mostrar antes de cargar.
 */
(function () {
    'use strict';

    var DOC_MIN = 4, DOC_MAX = 15;
    var MAX_COMPARTIDA = 300;       // textos más largos que esto no son un documento ni un nombre: no se guardan en memoria
    var FILAS_BUSCANDO_CABECERA = 20;

    class ErrorLector extends Error {}

    function normal(t) {
        return String(t == null ? '' : t).normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
    }

    // ── Columnas: se reconocen por su título; «Tipo de documento» no es el documento
    var RE_DOCUMENTO = /^(?:(?:n|no|nro|num|numero)\s+)?(?:de\s+|del\s+)?(?:documento|doc|cedula|identificacion|cc)(?:\s+(?:de\s+|del\s+)?(?:identidad|identificacion|paciente|usuario|afiliado))?$/;
    // El nombre se elige por lo que dice el título, no por su posición: «Usuario» puede ser quien agendó la cita
    var NOMBRE_COMPLETO = ['paciente', 'nombre del paciente', 'nombre paciente', 'nombre completo', 'nombres y apellidos', 'apellidos y nombres', 'nombre y apellidos', 'nombre y apellido'];
    var APELLIDOS = ['apellidos', 'apellido', 'primer apellido', 'segundo apellido'];
    var PARTES_NOMBRE = ['nombres', 'primer nombre', 'segundo nombre', 'apellidos', 'apellido', 'primer apellido', 'segundo apellido'];
    var NOMBRE_DUDOSO = ['usuario', 'afiliado'];

    function indices(limpios, titulos) {
        return titulos.map(function (t) { return limpios.indexOf(t); }).filter(function (i) { return i >= 0; });
    }

    // Devuelve { documento: indice, nombre: [indices], titulos } si la fila es la de títulos
    function buscarColumnas(titulos) {
        var limpios = titulos.map(normal);
        var documento = limpios.findIndex(function (t) { return RE_DOCUMENTO.test(t); });
        if (documento < 0) return null;
        var nombre = indices(limpios, NOMBRE_COMPLETO).slice(0, 1);
        if (!nombre.length && limpios.indexOf('nombre') >= 0) nombre = [limpios.indexOf('nombre')].concat(indices(limpios, APELLIDOS));
        if (!nombre.length) nombre = indices(limpios, PARTES_NOMBRE);
        if (!nombre.length) nombre = indices(limpios, NOMBRE_DUDOSO).slice(0, 1);
        if (!nombre.length) return null;
        return {
            documento: documento,
            nombre: nombre,
            titulos: { documento: String(titulos[documento]).trim(), nombre: nombre.map(function (i) { return String(titulos[i]).trim(); }).join(' + ') }
        };
    }

    // ── Documento: solo dígitos y sin ceros a la izquierda (igual que Paciente::claveDocumento)
    function limpiarDocumento(valor) {
        var s = String(valor == null ? '' : valor).trim();
        if (!s) return { vacio: true };
        // Excel guarda los números largos así: 1.14456789E9. Pero 1.23457E+11 es lo que muestra en pantalla: ya perdió dígitos
        var cientifico = /^\d\.(\d+)E(\+?)\d+$/i.exec(s);
        if (cientifico) {
            if (cientifico[2] && cientifico[1].length <= 5) return { conLetras: true, crudo: s };
            s = Number(s).toFixed(0);
        }
        s = s.replace(/[.,]0{1,2}$/, '').replace(/[\s.,'’-]/g, '');   // 1144567890,00 no son dos dígitos más
        if (!s) return { vacio: true };
        if (!/^\d+$/.test(s)) return { conLetras: true, crudo: s };
        if (s.length < DOC_MIN || s.length > DOC_MAX) return { fueraDeRango: true, crudo: s };
        var clave = s.replace(/^0+/, '');
        if (clave.length < DOC_MIN) return { fueraDeRango: true, crudo: s };
        return { clave: clave };
    }

    // ── Nombre. En el sistema de origen la Ñ a veces llegó como «?» (MU?OZ, ?A?EZ): entre vocales o al empezar la palabra es Ñ
    function limpiarNombre(partes) {
        // '#N/A' y compañía son errores de fórmula, no nombres
        var s = partes.map(function (p) { return p == null || String(p).trim().charAt(0) === '#' ? '' : String(p); }).join(' ')
            .replace(/[\u0000-\u001f\u007f*]/g, ' ').replace(/\s+/g, ' ').trim().toLocaleUpperCase('es');
        if (/\d{4}/.test(s)) return { nombre: '', corregido: false };   // Con cuatro cifras seguidas no es un nombre: una comilla suelta se tragó otras filas
        if (s.indexOf('?') < 0) return { nombre: s, corregido: false };
        var arreglado = s.replace(/(^|\s)\?(?=[AEIOU])/g, '$1Ñ').replace(/([AEIOU])\?(?=[AEIOU])/g, '$1Ñ');
        return { nombre: arreglado, corregido: arreglado !== s };
    }

    // ── Reúne las filas: un paciente puede tener muchas citas (y su nombre escrito de varias formas)
    function nuevoAcumulador() {
        var personas = new Map();     // documento → { nombre → veces }
        var conLetras = new Set(), fueraDeRango = new Set(), conEnie = new Set();
        var cifras = { filas: 0, sinDocumento: 0, sinNombre: 0 };
        var columnas = null, buscando = 0;
        return {
            hayColumnas: function () { return !!columnas; },
            titulos: function () { return columnas && columnas.titulos; },
            // valor(i) devuelve el contenido de la columna i de la fila
            fila: function (valor, anchoFila) {
                if (!columnas) {
                    if (++buscando > FILAS_BUSCANDO_CABECERA) return;
                    var titulos = [];
                    for (var t = 0; t < anchoFila; t++) titulos.push(valor(t));
                    columnas = buscarColumnas(titulos);
                    return;
                }
                cifras.filas++;
                var doc = limpiarDocumento(valor(columnas.documento));
                if (doc.vacio) { cifras.sinDocumento++; return; }
                if (doc.conLetras) { conLetras.add(doc.crudo); return; }
                if (doc.fueraDeRango) { fueraDeRango.add(doc.crudo); return; }
                var nom = limpiarNombre(columnas.nombre.map(valor));
                if (!nom.nombre) { cifras.sinNombre++; return; }
                if (nom.corregido) conEnie.add(doc.clave);
                var veces = personas.get(doc.clave);
                if (!veces) { veces = new Map(); personas.set(doc.clave, veces); }
                veces.set(nom.nombre, (veces.get(nom.nombre) || 0) + 1);
            },
            resultado: function () {
                var pacientes = [], variosNombres = 0, conSigno = 0;
                personas.forEach(function (veces, documento) {
                    if (veces.size > 1) variosNombres++;
                    // El nombre que más se repite; uno con «?» sin resolver solo si no hay otro. En empate, el último visto.
                    var elegido = null, mejor = -1;
                    veces.forEach(function (n, nombre) {
                        var puntos = n + (nombre.indexOf('?') < 0 ? 1e6 : 0);
                        if (puntos >= mejor) { mejor = puntos; elegido = nombre; }
                    });
                    if (elegido.indexOf('?') >= 0) conSigno++;
                    pacientes.push([documento, elegido]);
                });
                return {
                    pacientes: pacientes,
                    titulos: columnas.titulos,
                    filas: cifras.filas,
                    sinDocumento: cifras.sinDocumento,
                    sinNombre: cifras.sinNombre,
                    conLetras: conLetras.size,
                    fueraDeRango: fueraDeRango.size,
                    variosNombres: variosNombres,
                    conEnie: conEnie.size,
                    conSigno: conSigno
                };
            }
        };
    }

    // ── ZIP (un .xlsx es un zip): se lee el índice del final y cada parte se descomprime por flujo
    async function abrirZip(archivo) {
        var cola = await archivo.slice(Math.max(0, archivo.size - 66000)).arrayBuffer();
        var v = new DataView(cola);
        var i = cola.byteLength - 22;
        while (i >= 0 && v.getUint32(i, true) !== 0x06054b50) i--;
        if (i < 0) throw new ErrorLector('El archivo no es un Excel .xlsx válido. Si es un .xls antiguo, ábrelo en Excel y guárdalo como .xlsx.');
        var n = v.getUint16(i + 10, true), tam = v.getUint32(i + 12, true), inicio = v.getUint32(i + 16, true);
        if (inicio === 0xFFFFFFFF || n === 0xFFFF) throw new ErrorLector('El archivo es demasiado grande para leerlo. Guárdalo como CSV e inténtalo de nuevo.');
        var cd = new DataView(await archivo.slice(inicio, inicio + tam).arrayBuffer());
        var texto = new TextDecoder();
        var entradas = new Map();
        var p = 0;
        for (var k = 0; k < n && p + 46 <= cd.byteLength; k++) {
            if (cd.getUint32(p, true) !== 0x02014b50) break;
            var largoNombre = cd.getUint16(p + 28, true);
            var nombre = texto.decode(new Uint8Array(cd.buffer, cd.byteOffset + p + 46, largoNombre));
            entradas.set(nombre.replace(/^\/+/, ''), {
                metodo: cd.getUint16(p + 10, true),
                comprimido: cd.getUint32(p + 20, true),
                tamano: cd.getUint32(p + 24, true),
                local: cd.getUint32(p + 42, true)
            });
            p += 46 + largoNombre + cd.getUint16(p + 30, true) + cd.getUint16(p + 32, true);
        }
        return { archivo: archivo, entradas: entradas };
    }

    async function flujoDeEntrada(zip, ruta) {
        var e = zip.entradas.get(ruta);
        var cabecera = new DataView(await zip.archivo.slice(e.local, e.local + 30).arrayBuffer());
        var datos = e.local + 30 + cabecera.getUint16(26, true) + cabecera.getUint16(28, true);
        var flujo = zip.archivo.slice(datos, datos + e.comprimido).stream();
        if (e.metodo === 8) flujo = flujo.pipeThrough(new DecompressionStream('deflate-raw'));
        else if (e.metodo !== 0) throw new ErrorLector('El archivo usa una compresión que no se puede leer. Ábrelo en Excel y guárdalo de nuevo.');
        return flujo.pipeThrough(new TextDecoderStream('utf-8'));
    }

    // Recorre un flujo de texto; da aire a la pantalla para que se vea el avance y se pueda cancelar
    async function recorrer(flujo, op, alTrozo) {
        var lector = flujo.getReader();
        var ultimoRespiro = performance.now();
        try {
            for (;;) {
                var r = await lector.read();
                if (r.done) return;
                alTrozo(r.value);
                if (performance.now() - ultimoRespiro > 80) {
                    if (op.alProgreso) op.alProgreso();
                    await new Promise(function (ok) { setTimeout(ok); });
                    ultimoRespiro = performance.now();
                }
                if (op.senal && op.senal.aborted) throw new DOMException('Cancelado', 'AbortError');
            }
        } finally {
            lector.cancel().catch(function () {});
        }
    }

    var ENTIDADES = { amp: '&', lt: '<', gt: '>', quot: '"', apos: "'" };
    function decodificar(t) {
        if (t.indexOf('&') < 0 && t.indexOf('_x') < 0) return t;
        return t.replace(/&(?:#(\d+)|#x([0-9a-f]+)|(\w+));/gi, function (m, dec, hex, nombre) {
            if (dec) return String.fromCodePoint(Number(dec));
            if (hex) return String.fromCodePoint(parseInt(hex, 16));
            return ENTIDADES[nombre] !== undefined ? ENTIDADES[nombre] : m;
        }).replace(/_x[0-9A-Fa-f]{4}_/g, ' ');
    }

    // Texto de <si> o <is>: une los <t> (texto con formato viene por partes) y descarta la guía fonética <rPh>
    function textoDe(xml) {
        if (xml.indexOf('<rPh') >= 0) xml = xml.replace(/<rPh\b[\s\S]*?<\/rPh>/g, '');
        var re = /<t\b[^>]*?(?:\/>|>([\s\S]*?)<\/t>)/g, m, s = '';
        while ((m = re.exec(xml))) if (m[1]) s += m[1];
        return decodificar(s);
    }

    // Trocea un flujo por elementos <etiqueta>…</etiqueta> (o <etiqueta/>) sin cargar todo en memoria
    function troceador(etiqueta, alElemento) {
        var abre = '<' + etiqueta, cierra = '</' + etiqueta + '>';
        var resto = '';
        return function (trozo) {
            var buf = resto + trozo, pos = 0;
            for (;;) {
                var ini = buf.indexOf(abre, pos);
                if (ini < 0) { resto = buf.slice(Math.max(pos, buf.length - abre.length)); return; }
                var sig = buf[ini + abre.length];
                if (sig === undefined) { resto = buf.slice(ini); return; }
                if (sig !== ' ' && sig !== '>' && sig !== '/' && sig !== '\t' && sig !== '\n' && sig !== '\r') { pos = ini + abre.length; continue; }   // <rowBreaks>, <sst>…
                var finEtiqueta = buf.indexOf('>', ini);
                if (finEtiqueta < 0) { resto = buf.slice(ini); return; }
                if (buf[finEtiqueta - 1] === '/') { alElemento('', buf.slice(ini, finEtiqueta)); pos = finEtiqueta + 1; continue; }
                var fin = buf.indexOf(cierra, finEtiqueta);
                if (fin < 0) { resto = buf.slice(ini); return; }
                alElemento(buf.slice(finEtiqueta + 1, fin), buf.slice(ini, finEtiqueta));
                pos = fin + cierra.length;
            }
        };
    }

    async function leerXmlEntrada(zip, ruta) {
        if (!zip.entradas.has(ruta)) return '';
        var s = '';
        await recorrer(await flujoDeEntrada(zip, ruta), {}, function (t) { s += t; });
        return s;
    }

    // Las hojas en el orden del libro
    async function rutasDeHojas(zip) {
        var libro = await leerXmlEntrada(zip, 'xl/workbook.xml');
        var rels = await leerXmlEntrada(zip, 'xl/_rels/workbook.xml.rels');
        var destino = {};
        (rels.match(/<Relationship\b[^>]*>/g) || []).forEach(function (r) {
            var id = /\bId="([^"]+)"/.exec(r), t = /\bTarget="([^"]+)"/.exec(r);
            if (id && t) destino[id[1]] = t[1];
        });
        var hojas = [];
        (libro.match(/<sheet\b[^>]*>/g) || []).forEach(function (h) {
            if (/\bstate="(?:hidden|veryHidden)"/.test(h)) return;   // una hoja oculta no es la que el usuario ve
            var id = /\br:id="([^"]+)"/.exec(h);
            var t = id && destino[id[1]];
            if (!t) return;
            var nombre = /\bname="([^"]*)"/.exec(h);
            hojas.push({ ruta: t.charAt(0) === '/' ? t.slice(1) : 'xl/' + t.replace(/^\.\//, ''), nombre: nombre ? decodificar(nombre[1]) : '' });
        });
        hojas = hojas.filter(function (h) { return zip.entradas.has(h.ruta); });
        if (!hojas.length) zip.entradas.forEach(function (e, r) { if (/^xl\/worksheets\/[^/]+\.xml$/.test(r)) hojas.push({ ruta: r, nombre: '' }); });
        return hojas;
    }

    var LETRAS_A = 'A'.charCodeAt(0);
    function indiceColumna(letras) {
        var n = 0;
        for (var i = 0; i < letras.length; i++) n = n * 26 + (letras.charCodeAt(i) - LETRAS_A + 1);
        return n - 1;
    }

    // Una fila de la hoja → valores por columna
    function leerCeldas(xml, compartidas) {
        var valores = [];
        var re = /<c\b([^>]*?)(?:\/>|>([\s\S]*?)<\/c>)/g, m, posicion = 0;
        while ((m = re.exec(xml))) {
            var attrs = m[1], cuerpo = m[2];
            var col = /\br="([A-Z]+)\d+"/.exec(attrs);
            var indice = col ? indiceColumna(col[1]) : posicion;
            posicion = indice + 1;
            if (!cuerpo) continue;
            var tipo = /\bt="(\w+)"/.exec(attrs);
            tipo = tipo ? tipo[1] : 'n';
            if (tipo === 'inlineStr') { valores[indice] = textoDe(cuerpo); continue; }
            var v = /<v\b[^>]*>([\s\S]*?)<\/v>/.exec(cuerpo);
            if (!v) continue;
            valores[indice] = tipo === 's' ? compartidas[Number(v[1])] : decodificar(v[1]);
        }
        return valores;
    }

    async function leerXlsx(archivo, op) {
        var zip = await abrirZip(archivo);
        var hojas = await rutasDeHojas(zip);
        if (!hojas.length) throw new ErrorLector('El archivo no tiene hojas que se puedan leer.');

        // El avance es sobre lo descomprimido: tabla de textos + hojas
        var total = 0, leido = 0;
        var parte = zip.entradas.get('xl/sharedStrings.xml');
        if (parte) total += parte.tamano;
        hojas.forEach(function (h) { total += zip.entradas.get(h.ruta).tamano; });
        var avance = {
            senal: op.senal,
            alProgreso: function () { if (op.alProgreso && total) op.alProgreso(Math.min(0.99, leido / total)); }
        };

        var cuenta = function (fn) { return function (t) { leido += t.length; fn(t); }; };
        // Excel, al guardar, deja los textos en una tabla aparte y las celdas solo apuntan a ella
        var compartidas = [];
        if (parte) {
            var lista = [];
            var trozoSi = troceador('si', function (xml) { var t = textoDe(xml); lista.push(t.length > MAX_COMPARTIDA ? '' : t); });
            await recorrer(await flujoDeEntrada(zip, 'xl/sharedStrings.xml'), avance, cuenta(trozoSi));
            compartidas = lista;
        }

        for (var h = 0; h < hojas.length; h++) {
            var acumulado = nuevoAcumulador();
            var trozoFila = troceador('row', function (xml) {
                var valores = leerCeldas(xml, compartidas);
                if (!valores.length) return;   // fila en blanco
                acumulado.fila(function (i) { return valores[i]; }, valores.length);
            });
            await recorrer(await flujoDeEntrada(zip, hojas[h].ruta), avance, cuenta(trozoFila));
            if (acumulado.hayColumnas()) {
                var resultado = acumulado.resultado();
                resultado.hoja = hojas[h].nombre;      // se lee solo esta: mezclar hojas juntaría catálogos con pacientes
                resultado.hojasVisibles = hojas.length;
                return resultado;
            }
        }
        throw new ErrorLector(MENSAJE_SIN_COLUMNAS);
    }

    // ── CSV: separador por la primera línea; UTF-8 y, si no cuadra, Windows-1252 (el CSV típico de Excel)
    async function leerCsvCon(archivo, op, codificacion) {
        var acumulado = nuevoAcumulador();
        var separador = null, campo = '', fila = [], entreComillas = false, comillaPendiente = false, leido = 0;
        var cerrarFila = function () {
            fila.push(campo); campo = '';
            if (fila.length > 1 || fila[0] !== '') acumulado.fila(function (i) { return fila[i]; }, fila.length);
            fila = [];
        };
        var alTrozo = function (t) {
            leido += t.length;
            var i = 0;
            if (separador === null) {
                if (leido === t.length && t.charCodeAt(0) === 0xFEFF) i = 1;
                var linea = t.slice(i, t.indexOf('\n') < 0 ? t.length : t.indexOf('\n'));
                var mejor = 0;
                [';', ',', '\t', '|'].forEach(function (s) {
                    var n = linea.split(s).length - 1;
                    if (n > mejor) { mejor = n; separador = s; }
                });
                if (separador === null) separador = ',';
            }
            for (; i < t.length; i++) {
                var c = t[i];
                if (entreComillas) {
                    if (comillaPendiente) {
                        comillaPendiente = false;
                        if (c === '"') { campo += '"'; continue; }
                        entreComillas = false;   // cerró: sigue abajo como carácter normal
                    } else if (c === '"') { comillaPendiente = true; continue; }
                    else {
                        campo += c;
                        if (campo.length > MAX_CELDA) throw new ErrorLector(MENSAJE_COMILLA);
                        continue;
                    }
                }
                if (c === '"' && campo === '') { entreComillas = true; }
                else if (c === separador) { fila.push(campo); campo = ''; }
                else if (c === '\n') { cerrarFila(); }
                else if (c !== '\r') { campo += c; }
            }
        };
        var avance = { senal: op.senal, alProgreso: function () { if (op.alProgreso) op.alProgreso(Math.min(0.99, leido / archivo.size)); } };
        await recorrer(archivo.stream().pipeThrough(new TextDecoderStream(codificacion, { fatal: true })), avance, alTrozo);
        if (entreComillas && !comillaPendiente) throw new ErrorLector(MENSAJE_COMILLA);
        if (campo !== '' || fila.length) cerrarFila();
        if (!acumulado.hayColumnas()) throw new ErrorLector(MENSAJE_SIN_COLUMNAS);
        return acumulado.resultado();
    }

    async function leerCsv(archivo, op) {
        try {
            return await leerCsvCon(archivo, op, 'utf-8');
        } catch (e) {
            if (e instanceof ErrorLector || (e && e.name === 'AbortError')) throw e;
            return await leerCsvCon(archivo, op, 'windows-1252');
        }
    }

    var MAX_CELDA = 32767;   // el tope de una celda de Excel
    var MENSAJE_COMILLA = 'El CSV tiene una comilla sin cerrar: las filas se mezclan. Corrígelo o guárdalo de nuevo desde Excel.';
    var MENSAJE_SIN_COLUMNAS = 'No se encontraron las columnas del documento y del nombre. La primera fila debe tener títulos como «Documento» o «Cédula» y «Paciente» o «Nombre».';

    async function leerPacientes(archivo, opciones) {
        var op = opciones || {};
        var nombre = String(archivo.name || '').toLowerCase();
        var esCsv = /\.(csv|txt)$/.test(nombre);
        if (/\.xls$/.test(nombre)) throw new ErrorLector('Los .xls antiguos no se pueden leer. Abre el archivo en Excel y guárdalo como .xlsx.');
        if (typeof TextDecoderStream === 'undefined' || (!esCsv && typeof DecompressionStream === 'undefined')) {
            throw new ErrorLector('Este navegador no puede leer el archivo. Actualiza Chrome o Edge e inténtalo de nuevo.');
        }
        var r = esCsv ? await leerCsv(archivo, op) : await leerXlsx(archivo, op);
        if (op.alProgreso) op.alProgreso(1);
        return r;
    }

    window.leerPacientes = leerPacientes;
    window.ErrorLectorPacientes = ErrorLector;
})();
