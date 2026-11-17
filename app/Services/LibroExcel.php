<?php

namespace App\Services;

use ZipStream\ZipStream;

/**
 * Escribe un .xlsx fila por fila, sin guardar el libro en memoria.
 *
 * PhpSpreadsheet arma todo el libro en memoria (cerca de 1 KB por celda): un informe de un año, con cientos de miles
 * de turnos, pasa del límite de memoria del servidor y la página no entrega nada. Aquí cada fila se escribe en un archivo
 * temporal en cuanto llega (en disco, salvo un búfer pequeño por hoja) y al final todo se empaqueta en el .xlsx: la memoria
 * no crece con el número de filas.
 *
 * Lo justo para los informes: textos y números, unos estilos fijos (ESTILO_*), celdas combinadas, alto de fila, ancho de
 * columna (fijo o según el contenido) y encabezado fijo al desplazarse.
 */
class LibroExcel
{
    public const NORMAL = 0;
    public const ENCABEZADO = 1;          // negrita blanca sobre azul institucional, con bordes
    public const BORDE = 2;
    public const CLARO_BORDE = 3;         // fila alterna, azul claro
    public const BLANCO_BORDE = 4;
    public const TITULO = 5;              // 16 pt blanca sobre azul, centrada
    public const SECCION = 6;             // 12 pt blanca sobre azul, centrada
    public const NEGRITA = 7;
    public const AZUL = 8;
    public const NOTA = 9;                // 9 pt cursiva gris, ajustada y arriba
    public const NOTA_SIMPLE = 10;        // 9 pt cursiva gris
    public const CLARO_BORDE_NEGRITA = 11;
    public const ENCABEZADO_AMBAR = 12;   // negrita blanca sobre ámbar (canales no presenciales), con bordes

    /** Filas por hoja que admite Excel (incluido el encabezado). */
    public const MAX_FILAS = 1048576;

    /** Excel no admite más caracteres en una celda (los cuenta en unidades UTF-16). */
    private const MAX_TEXTO = 32767;

    /** Lo que cada temporal guarda en memoria antes de pasar a disco: poco, porque hay uno abierto por hoja. */
    private const BUFER_TEMPORAL = 256 * 1024;

    /** @var array<int, array{nombre: string, filas: resource, n: int, anchos: array<int, float>, fijos: array<int, float>, combinadas: string[], fijar: bool}> */
    private array $hojas = [];

    private static array $letras = [];

    /**
     * Abre una hoja nueva; las filas que siguen van a ella. El nombre se ajusta a lo que Excel admite y no se repite.
     */
    public function hoja(string $nombre): static
    {
        $filas = fopen('php://temp/maxmemory:' . self::BUFER_TEMPORAL, 'w+b');
        if ($filas === false) {
            throw new \RuntimeException('No se pudo crear el archivo temporal de la hoja.');
        }
        $this->hojas[] = [
            'nombre' => $this->nombreLibre($nombre),
            'filas' => $filas,
            'n' => 0,
            'anchos' => [],
            'fijos' => [],
            'combinadas' => [],
            'fijar' => false,
        ];

        return $this;
    }

    /** Filas escritas en la hoja actual. */
    public function filas(): int
    {
        return $this->actual()['n'];
    }

    /** Ancho fijo de una columna (A = 1), en caracteres. Sin ancho fijo, la columna se ajusta a su contenido. */
    public function ancho(int $columna, float $ancho): static
    {
        $this->hojas[array_key_last($this->hojas)]['fijos'][$columna] = $ancho;

        return $this;
    }

    /** La primera fila queda fija al desplazarse (encabezado de tabla). */
    public function fijarEncabezado(): static
    {
        $this->hojas[array_key_last($this->hojas)]['fijar'] = true;

        return $this;
    }

    /** Combinar celdas, p. ej. 'A1:D1'. */
    public function combinar(string $rango): static
    {
        $this->hojas[array_key_last($this->hojas)]['combinadas'][] = $rango;

        return $this;
    }

    /**
     * Agrega una fila. Cada celda es un valor (texto, número o null = vacía) o [valor, estilo]. $estilo se aplica a las
     * celdas que no traen uno propio; con estilo, una celda vacía también se escribe (para que lleve borde o fondo).
     * $medir = false: la fila no cuenta para el ancho de las columnas (notas en celdas combinadas).
     */
    public function fila(array $celdas, int $estilo = self::NORMAL, ?float $alto = null, bool $medir = true): static
    {
        $i = array_key_last($this->hojas);
        if ($i === null) {
            throw new \LogicException('Primero se abre una hoja.');
        }
        $hoja = &$this->hojas[$i];
        if ($hoja['n'] >= self::MAX_FILAS) {
            throw new \OverflowException('La hoja «' . $hoja['nombre'] . '» ya tiene las filas que admite Excel.');
        }
        $r = ++$hoja['n'];

        $xml = '<row r="' . $r . '"' . ($alto !== null ? ' ht="' . $alto . '" customHeight="1"' : '') . '>';
        $columna = 0;
        foreach ($celdas as $celda) {
            $columna++;
            [$valor, $s] = is_array($celda) ? [$celda[0] ?? null, $celda[1] ?? $estilo] : [$celda, $estilo];
            $ref = self::letra($columna) . $r;
            $atrEstilo = $s ? ' s="' . $s . '"' : '';

            if ($valor === null || $valor === '') {
                if ($s) {
                    $xml .= '<c r="' . $ref . '"' . $atrEstilo . '/>';
                }
                continue;
            }
            if (is_int($valor) || is_float($valor)) {
                $texto = is_float($valor) ? rtrim(rtrim(sprintf('%.10F', $valor), '0'), '.') : (string) $valor;
                $xml .= '<c r="' . $ref . '"' . $atrEstilo . '><v>' . $texto . '</v></c>';
            } else {
                $texto = self::limpiar((string) $valor);
                $xml .= '<c r="' . $ref . '"' . $atrEstilo . ' t="inlineStr"><is><t xml:space="preserve">'
                    . htmlspecialchars($texto, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</t></is></c>';
            }

            if ($medir) {
                $largo = mb_strlen($texto, 'UTF-8') * ($s === self::ENCABEZADO || $s === self::NEGRITA ? 1.15 : 1.05);
                if ($largo > ($hoja['anchos'][$columna] ?? 0)) {
                    $hoja['anchos'][$columna] = $largo;
                }
            }
        }
        fwrite($hoja['filas'], $xml . '</row>');

        return $this;
    }

    /**
     * Empaqueta el libro en $ruta (.xlsx) y libera los temporales.
     */
    public function guardar(string $ruta): void
    {
        if (!$this->hojas) {
            $this->hoja('Hoja1');
        }

        $salida = fopen($ruta, 'wb');
        if ($salida === false) {
            throw new \RuntimeException('No se pudo crear el archivo del informe.');
        }

        try {
            // Mismas opciones que usa PhpSpreadsheet: sin ZIP64 ni encabezados en cero, lo que mejor abre Excel.
            $zip = new ZipStream(enableZip64: false, outputStream: $salida, sendHttpHeaders: false, defaultEnableZeroHeader: false);

            $tipos = '';
            $hojas = '';
            $relaciones = '';
            foreach ($this->hojas as $i => $hoja) {
                $n = $i + 1;
                $tipos .= '<Override PartName="/xl/worksheets/sheet' . $n . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
                $hojas .= '<sheet name="' . htmlspecialchars($hoja['nombre'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '" sheetId="' . $n . '" r:id="rId' . $n . '"/>';
                $relaciones .= '<Relationship Id="rId' . $n . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $n . '.xml"/>';
            }
            $nEstilos = count($this->hojas) + 1;

            $zip->addFile('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                . '<Default Extension="xml" ContentType="application/xml"/>'
                . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
                . $tipos . '</Types>');
            $zip->addFile('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                . '</Relationships>');
            $zip->addFile('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                . '<bookViews><workbookView activeTab="0"/></bookViews><sheets>' . $hojas . '</sheets></workbook>');
            $zip->addFile('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $relaciones
                . '<Relationship Id="rId' . $nEstilos . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
                . '</Relationships>');
            $zip->addFile('xl/styles.xml', self::estilos());

            foreach ($this->hojas as $i => $hoja) {
                $completa = $this->armarHoja($hoja, $i === 0);
                $zip->addFileFromStream('xl/worksheets/sheet' . ($i + 1) . '.xml', $completa);
                fclose($completa);
            }

            $zip->finish();
        } finally {
            fclose($salida);
            $this->cerrarTemporales();
        }
    }

    public function __destruct()
    {
        $this->cerrarTemporales();
    }

    private function cerrarTemporales(): void
    {
        foreach ($this->hojas as &$hoja) {
            if (is_resource($hoja['filas'])) {
                fclose($hoja['filas']);
            }
        }
    }

    /**
     * La hoja completa: lo que va antes de las filas depende de ellas (anchos), así que se arma al final.
     *
     * @return resource
     */
    private function armarHoja(array $hoja, bool $primera)
    {
        $completa = fopen('php://temp/maxmemory:' . self::BUFER_TEMPORAL, 'w+b');

        $vista = '<sheetView workbookViewId="0"' . ($primera ? ' tabSelected="1"' : '') . '>';
        if ($hoja['fijar'] && $hoja['n'] > 1) {
            $vista .= '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/><selection pane="bottomLeft" activeCell="A2" sqref="A2"/>';
        }
        $vista .= '</sheetView>';

        $anchos = $hoja['fijos'];
        foreach ($hoja['anchos'] as $columna => $largo) {
            $anchos[$columna] ??= min(80, max(8, round($largo + 2.5, 1)));
        }
        ksort($anchos);
        $columnas = '';
        foreach ($anchos as $columna => $ancho) {
            $columnas .= '<col min="' . $columna . '" max="' . $columna . '" width="' . $ancho . '" customWidth="1"/>';
        }

        fwrite($completa, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetViews>' . $vista . '</sheetViews><sheetFormatPr defaultRowHeight="15"/>'
            . ($columnas !== '' ? '<cols>' . $columnas . '</cols>' : '') . '<sheetData>');
        rewind($hoja['filas']);
        stream_copy_to_stream($hoja['filas'], $completa);
        fwrite($completa, '</sheetData>');
        if ($hoja['combinadas']) {
            fwrite($completa, '<mergeCells count="' . count($hoja['combinadas']) . '">');
            foreach ($hoja['combinadas'] as $rango) {
                fwrite($completa, '<mergeCell ref="' . $rango . '"/>');
            }
            fwrite($completa, '</mergeCells>');
        }
        fwrite($completa, '<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/></worksheet>');
        rewind($completa);

        return $completa;
    }

    private function actual(): array
    {
        $i = array_key_last($this->hojas);
        if ($i === null) {
            throw new \LogicException('Primero se abre una hoja.');
        }

        return $this->hojas[$i];
    }

    /** Excel: hasta 31 caracteres, sin : \ / ? * [ ] ni comilla al inicio o al final, y sin repetirse (sin distinguir mayúsculas). */
    private function nombreLibre(string $nombre): string
    {
        $nombre = str_replace([':', '\\', '/', '?', '*', '[', ']'], '_', self::limpiar($nombre));
        // Se recorta primero y se quitan las comillas después: si no, el recorte podría dejar una al final.
        $cortar = fn (int $max) => trim(self::recortar(trim($nombre, " '"), $max), " '");
        $usados = array_map(fn ($h) => mb_strtolower($h['nombre'], 'UTF-8'), $this->hojas);

        $candidato = $cortar(31);
        $candidato = $candidato !== '' ? $candidato : 'Hoja';
        for ($n = 2; in_array(mb_strtolower($candidato, 'UTF-8'), $usados, true); $n++) {
            $sufijo = ' (' . $n . ')';
            $candidato = $cortar(31 - strlen($sufijo)) . $sufijo;
        }

        return $candidato;
    }

    /** Sin caracteres que el XML no admite (controles, UTF-8 mal formado) y sin pasar del máximo de una celda. */
    private static function limpiar(string $texto): string
    {
        if (!mb_check_encoding($texto, 'UTF-8')) {
            $texto = mb_convert_encoding($texto, 'UTF-8', 'UTF-8');
        }
        $texto = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $texto) ?? '';

        return self::recortar($texto, self::MAX_TEXTO);
    }

    /**
     * Recorta a $max unidades UTF-16, que es como cuenta Excel (un emoji son dos), sin partir un carácter.
     */
    private static function recortar(string $texto, int $max): string
    {
        // Un texto nunca tiene más unidades UTF-16 que bytes UTF-8: casi siempre se sale aquí.
        if (strlen($texto) <= $max) {
            return $texto;
        }
        $utf16 = mb_convert_encoding($texto, 'UTF-16LE', 'UTF-8');
        if (strlen($utf16) <= 2 * $max) {
            return $texto;
        }
        $utf16 = substr($utf16, 0, 2 * $max);
        $ultima = unpack('v', substr($utf16, -2))[1];
        if ($ultima >= 0xD800 && $ultima <= 0xDBFF) {
            $utf16 = substr($utf16, 0, -2);  // mitad de un par sustituto
        }

        return mb_convert_encoding($utf16, 'UTF-8', 'UTF-16LE');
    }

    /** A, B, … Z, AA, AB… */
    private static function letra(int $columna): string
    {
        if (!isset(self::$letras[$columna])) {
            $letra = '';
            for ($n = $columna; $n > 0; $n = intdiv($n - 1, 26)) {
                $letra = chr(65 + ($n - 1) % 26) . $letra;
            }
            self::$letras[$columna] = $letra;
        }

        return self::$letras[$columna];
    }

    /** Los estilos fijos, en el mismo orden que las constantes. */
    private static function estilos(): string
    {
        $fuente = fn (string $extra = '', int $tam = 11, ?string $color = null) => '<font>' . $extra . '<sz val="' . $tam . '"/>'
            . ($color ? '<color rgb="FF' . $color . '"/>' : '') . '<name val="Calibri"/><family val="2"/></font>';
        $relleno = fn (string $color) => '<fill><patternFill patternType="solid"><fgColor rgb="FF' . $color . '"/><bgColor indexed="64"/></patternFill></fill>';
        $lado = fn (string $l) => '<' . $l . ' style="thin"><color indexed="64"/></' . $l . '>';

        $fuentes = [
            $fuente(),                          // 0 normal
            $fuente('<b/>', 11, 'FFFFFF'),      // 1 negrita blanca
            $fuente('<b/>', 16, 'FFFFFF'),      // 2 título
            $fuente('<b/>', 12, 'FFFFFF'),      // 3 sección
            $fuente('<b/>'),                    // 4 negrita
            $fuente('', 11, '064B9E'),          // 5 azul
            $fuente('<i/>', 9, '555555'),       // 6 nota
        ];
        $rellenos = [
            '<fill><patternFill patternType="none"/></fill>',
            '<fill><patternFill patternType="gray125"/></fill>',
            $relleno('064B9E'),                 // 2 azul institucional
            $relleno('E6F0FF'),                 // 3 azul claro
            $relleno('FFFFFF'),                 // 4 blanco
            $relleno('F59E0B'),                 // 5 ámbar
        ];
        $bordes = [
            '<border><left/><right/><top/><bottom/><diagonal/></border>',
            '<border>' . $lado('left') . $lado('right') . $lado('top') . $lado('bottom') . '<diagonal/></border>',
        ];
        $xf = function (int $fuente, int $relleno = 0, int $borde = 0, string $alineacion = '') {
            return '<xf numFmtId="0" fontId="' . $fuente . '" fillId="' . $relleno . '" borderId="' . $borde . '" xfId="0"'
                . ($fuente ? ' applyFont="1"' : '') . ($relleno ? ' applyFill="1"' : '') . ($borde ? ' applyBorder="1"' : '')
                . ($alineacion ? ' applyAlignment="1"><alignment ' . $alineacion . '/></xf>' : '/>');
        };
        $celdas = [
            self::NORMAL => $xf(0),
            self::ENCABEZADO => $xf(1, 2, 1),
            self::BORDE => $xf(0, 0, 1),
            self::CLARO_BORDE => $xf(0, 3, 1),
            self::BLANCO_BORDE => $xf(0, 4, 1),
            self::TITULO => $xf(2, 2, 0, 'horizontal="center" vertical="center"'),
            self::SECCION => $xf(3, 2, 0, 'horizontal="center"'),
            self::NEGRITA => $xf(4),
            self::AZUL => $xf(5),
            self::NOTA => $xf(6, 0, 0, 'vertical="top" wrapText="1"'),
            self::NOTA_SIMPLE => $xf(6),
            self::CLARO_BORDE_NEGRITA => $xf(4, 3, 1),
            self::ENCABEZADO_AMBAR => $xf(1, 5, 1),
        ];
        ksort($celdas);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="' . count($fuentes) . '">' . implode('', $fuentes) . '</fonts>'
            . '<fills count="' . count($rellenos) . '">' . implode('', $rellenos) . '</fills>'
            . '<borders count="' . count($bordes) . '">' . implode('', $bordes) . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="' . count($celdas) . '">' . implode('', $celdas) . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }
}
