<?php

namespace App\Support;

use DateTimeInterface;
use RuntimeException;
use ZipArchive;

// Genera un .xlsx mínimo (varias hojas, encabezado en negrita con filtro, números y
// fechas reales) sin depender de una librería externa. Se usa .xlsx y no .csv porque el
// Excel configurado en español toma el punto decimal de un CSV como separador de miles.
// Tipos de celda: int/float → número, DateTimeInterface → fecha, el resto → texto (así un
// C.I. o NIT con ceros adelante no se convierte en número).
class XlsxSimple
{
    private const ESTILO_ENCABEZADO = 1;
    private const ESTILO_MONTO      = 2;
    private const ESTILO_FECHA      = 3;

    /** @var array<int, array{nombre: string, cuerpo: string, columnas: int, filas: int, anchos: array<int,int>}> */
    private array $hojas = [];

    /**
     * @param  string[]  $encabezados
     * @param  iterable<array>  $filas
     * @param  int[]  $columnasMonto  Índices (desde 0) de columnas con formato 0,00
     */
    public function hoja(string $nombre, array $encabezados, iterable $filas, array $columnasMonto = []): void
    {
        $cuerpo = tempnam(sys_get_temp_dir(), 'xlsx');
        $f = fopen($cuerpo, 'wb');
        $anchos = array_map(fn ($e) => mb_strlen($e), $encabezados);

        fwrite($f, $this->fila($encabezados, $anchos, [], true));
        $cantidad = 1;

        foreach ($filas as $fila) {
            fwrite($f, $this->fila(array_values($fila), $anchos, $columnasMonto));
            $cantidad++;
        }
        fclose($f);

        $this->hojas[] = [
            // Excel no acepta estos caracteres en el nombre de una hoja (máx. 31).
            'nombre'   => mb_substr(str_replace(['\\', '/', '?', '*', '[', ']', ':'], '-', $nombre), 0, 31),
            'cuerpo'   => $cuerpo,
            'columnas' => count($encabezados),
            'filas'    => $cantidad,
            'anchos'   => $anchos,
        ];
    }

    public function guardar(string $destino): void
    {
        if (! $this->hojas) {
            throw new RuntimeException('El libro no tiene hojas.');
        }

        $zip = new ZipArchive();
        $zip->open($destino, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $temporales = [];

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
        $zip->addFromString('xl/styles.xml', $this->estilos());

        foreach ($this->hojas as $i => $hoja) {
            $temporales[] = $archivo = $this->armarHoja($hoja);
            $zip->addFile($archivo, 'xl/worksheets/sheet' . ($i + 1) . '.xml');
        }

        $zip->close();

        foreach ($temporales as $t) {
            @unlink($t);
        }
        foreach ($this->hojas as $hoja) {
            @unlink($hoja['cuerpo']);
        }
        $this->hojas = [];
    }

    private function fila(array $valores, array &$anchos, array $columnasMonto, bool $encabezado = false): string
    {
        $xml = '<row>';

        foreach ($valores as $i => $valor) {
            if ($valor === null || $valor === '') {
                $xml .= '<c/>';
                continue;
            }

            if ($encabezado) {
                $xml .= '<c t="inlineStr" s="' . self::ESTILO_ENCABEZADO . '"><is><t>' . $this->texto($valor) . '</t></is></c>';
            } elseif ($valor instanceof DateTimeInterface) {
                // Número de serie de Excel: días desde el 30/12/1899.
                $serie = (int) floor(($valor->getTimestamp() + $valor->getOffset()) / 86400) + 25569;
                $xml .= '<c s="' . self::ESTILO_FECHA . '"><v>' . $serie . '</v></c>';
                $anchos[$i] = max($anchos[$i] ?? 0, 10);
                continue;
            } elseif (is_int($valor) || is_float($valor)) {
                $estilo = in_array($i, $columnasMonto, true) ? ' s="' . self::ESTILO_MONTO . '"' : '';
                $xml .= '<c' . $estilo . '><v>' . $valor . '</v></c>';
            } else {
                $xml .= '<c t="inlineStr"><is><t xml:space="preserve">' . $this->texto($valor) . '</t></is></c>';
            }

            $anchos[$i] = max($anchos[$i] ?? 0, min(60, mb_strlen((string) $valor)));
        }

        return $xml . '</row>';
    }

    private function armarHoja(array $hoja): string
    {
        $ruta = tempnam(sys_get_temp_dir(), 'xlsh');
        $f = fopen($ruta, 'wb');
        $ultima = $this->columna($hoja['columnas'] - 1);

        fwrite($f, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            // Encabezado fijo al bajar.
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<cols>');
        foreach ($hoja['anchos'] as $i => $ancho) {
            fwrite($f, '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . max(8, $ancho + 3) . '" customWidth="1"/>');
        }
        fwrite($f, '</cols><sheetData>');

        $cuerpo = fopen($hoja['cuerpo'], 'rb');
        stream_copy_to_stream($cuerpo, $f);
        fclose($cuerpo);

        fwrite($f, '</sheetData><autoFilter ref="A1:' . $ultima . $hoja['filas'] . '"/></worksheet>');
        fclose($f);

        return $ruta;
    }

    private function columna(int $indice): string
    {
        $letras = '';
        for ($n = $indice + 1; $n > 0; $n = intdiv($n - 1, 26)) {
            $letras = chr(65 + ($n - 1) % 26) . $letras;
        }

        return $letras;
    }

    private function texto(mixed $valor): string
    {
        // Saca los caracteres de control que el XML no admite.
        $limpio = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', (string) $valor) ?? '';

        return htmlspecialchars($limpio, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function contentTypes(): string
    {
        $hojas = '';
        foreach ($this->hojas as $i => $_) {
            $hojas .= '<Override PartName="/xl/worksheets/sheet' . ($i + 1) . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $hojas . '</Types>';
    }

    private function workbook(): string
    {
        $hojas = '';
        foreach ($this->hojas as $i => $hoja) {
            $hojas .= '<sheet name="' . $this->texto($hoja['nombre']) . '" sheetId="' . ($i + 1) . '" r:id="rId' . ($i + 1) . '"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $hojas . '</sheets></workbook>';
    }

    private function workbookRels(): string
    {
        $rels = '';
        foreach ($this->hojas as $i => $_) {
            $rels .= '<Relationship Id="rId' . ($i + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . ($i + 1) . '.xml"/>';
        }
        $rels .= '<Relationship Id="rId' . (count($this->hojas) + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $rels . '</Relationships>';
    }

    private function estilos(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1"><numFmt numFmtId="164" formatCode="dd/mm/yyyy"/></numFmts>'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF3E8D8"/></patternFill></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="4">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '<xf numFmtId="4" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '</cellXfs></styleSheet>';
    }
}
