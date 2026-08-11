<?php

namespace App\Services;

use Illuminate\Support\Collection;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\Settings as WordSettings;
use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * Convierte a PDF y fusiona en un único archivo los documentos adjuntos
 * (PDF, Word e imágenes) de un expediente o trámite, sin tocar los
 * archivos originales almacenados. La conversión y fusión se hacen en
 * archivos temporales que se eliminan al terminar.
 */
class ConsolidadorDocumentosService
{
    /** Formatos soportados hoy. Para sumar uno nuevo, agregar un case en convertirYAgregar(). */
    private const EXTENSIONES_SOPORTADAS = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];

    private string $carpetaTmp;

    public function __construct()
    {
        $this->carpetaTmp = storage_path('app/tmp');

        if (! is_dir($this->carpetaTmp)) {
            mkdir($this->carpetaTmp, 0755, true);
        }
    }

    /**
     * @param Collection $documentos Modelos con ->archivo_adjunto (ruta relativa en el disco "public") y ->titulo.
     * @param string $nombreBase Nombre de archivo sin extensión, ya sanitizado (ej. "Expediente_70657472").
     * @return array{path: ?string, errores: array<int, array{titulo: string, motivo: string}>}
     */
    public function generar(Collection $documentos, string $nombreBase): array
    {
        // Conversión + fusión de varias decenas de documentos puede tardar; este límite
        // aplica solo a la request actual, no al resto de la app.
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $this->limpiarTemporalesViejos();

        $pdf = new Fpdi();
        $pdf->SetCreator('Sistema de Seguimiento VEAS');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);

        $errores = [];
        $paginasAgregadas = 0;

        foreach ($documentos as $doc) {
            $titulo = $doc->titulo ?: basename($doc->archivo_adjunto ?? 'documento');

            try {
                $rutaOriginal = storage_path('app/public/' . $doc->archivo_adjunto);

                if (! is_file($rutaOriginal)) {
                    throw new \RuntimeException('El archivo no se encuentra en el servidor.');
                }

                $extension = strtolower(pathinfo($rutaOriginal, PATHINFO_EXTENSION));

                if (! in_array($extension, self::EXTENSIONES_SOPORTADAS, true)) {
                    throw new \RuntimeException("Formato .{$extension} no soportado todavía.");
                }

                $paginasAgregadas += $this->convertirYAgregar($pdf, $rutaOriginal, $extension);
            } catch (\Throwable $e) {
                $errores[] = ['titulo' => $titulo, 'motivo' => $e->getMessage()];
            }
        }

        if ($paginasAgregadas === 0) {
            return ['path' => null, 'errores' => $errores];
        }

        if (! empty($errores)) {
            $this->agregarPaginaDeErrores($pdf, $errores);
        }

        $rutaFinal = $this->carpetaTmp . '/' . $nombreBase . '-' . uniqid() . '.pdf';
        $pdf->Output($rutaFinal, 'F');

        return ['path' => $rutaFinal, 'errores' => $errores];
    }

    /**
     * Agrega al $pdf las páginas correspondientes a un documento, convirtiéndolo
     * primero si hace falta. Devuelve la cantidad de páginas agregadas.
     */
    private function convertirYAgregar(Fpdi $pdf, string $rutaOriginal, string $extension): int
    {
        return match ($extension) {
            'pdf' => $this->importarPdf($pdf, $rutaOriginal),
            'jpg', 'jpeg', 'png' => $this->agregarImagen($pdf, $rutaOriginal),
            'doc', 'docx' => $this->importarWord($pdf, $rutaOriginal),
        };
    }

    private function importarPdf(Fpdi $pdf, string $ruta): int
    {
        $cantidadPaginas = $pdf->setSourceFile($ruta);

        for ($i = 1; $i <= $cantidadPaginas; $i++) {
            $plantilla = $pdf->importPage($i);
            $tamano = $pdf->getTemplateSize($plantilla);

            $pdf->AddPage(
                $tamano['orientation'],
                [$tamano['width'], $tamano['height']]
            );
            $pdf->useTemplate($plantilla);
        }

        return $cantidadPaginas;
    }

    /** Se dibuja directamente sobre el PDF final (sin archivo intermedio) para no perder calidad. */
    private function agregarImagen(Fpdi $pdf, string $ruta): int
    {
        [$anchoPx, $altoPx] = getimagesize($ruta);

        // A4 en mm, con un margen chico para que la imagen no quede pegada al borde.
        $margen = 8;
        $anchoPagina = 210;
        $altoPagina = 297;
        $anchoMax = $anchoPagina - ($margen * 2);
        $altoMax = $altoPagina - ($margen * 2);

        // Asumimos 96 DPI (0.264583 mm/px) para el tamaño "nativo" y escalamos
        // por la proporción más restrictiva para que entre completa en la página.
        $anchoNativoMm = $anchoPx * 0.264583;
        $altoNativoMm = $altoPx * 0.264583;
        $escala = min($anchoMax / $anchoNativoMm, $altoMax / $altoNativoMm);

        $anchoMm = $anchoNativoMm * $escala;
        $altoMm = $altoNativoMm * $escala;

        $pdf->AddPage('P', [$anchoPagina, $altoPagina]);
        $pdf->Image(
            $ruta,
            ($anchoPagina - $anchoMm) / 2,
            ($altoPagina - $altoMm) / 2,
            $anchoMm,
            $altoMm,
            '',
            '',
            '',
            false,
            300
        );

        return 1;
    }

    private function importarWord(Fpdi $pdf, string $ruta): int
    {
        WordSettings::setPdfRendererName(WordSettings::PDF_RENDERER_DOMPDF);
        WordSettings::setPdfRendererPath(base_path('vendor/dompdf/dompdf'));

        $phpWord = WordIOFactory::load($ruta);
        $writer = WordIOFactory::createWriter($phpWord, 'PDF');

        $rutaTemporal = $this->carpetaTmp . '/word-' . uniqid() . '.pdf';
        $writer->save($rutaTemporal);

        try {
            return $this->importarPdf($pdf, $rutaTemporal);
        } finally {
            @unlink($rutaTemporal);
        }
    }

    private function agregarPaginaDeErrores(Fpdi $pdf, array $errores): void
    {
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage('P', 'A4');
        $pdf->SetFont('dejavusans', '', 11);

        $pdf->SetFont('dejavusans', 'B', 13);
        $pdf->MultiCell(0, 8, 'Documentos no incluidos en este PDF', 0, 'L');
        $pdf->Ln(2);

        $pdf->SetFont('dejavusans', '', 10);
        $pdf->MultiCell(0, 6, 'Los siguientes documentos no pudieron convertirse o incluirse automáticamente. Podés consultarlos por separado desde el listado de documentos.', 0, 'L');
        $pdf->Ln(4);

        foreach ($errores as $error) {
            $pdf->SetFont('dejavusans', 'B', 10);
            $pdf->MultiCell(0, 6, '• ' . $error['titulo'], 0, 'L');
            $pdf->SetFont('dejavusans', '', 10);
            $pdf->MultiCell(0, 6, '   ' . $error['motivo'], 0, 'L');
            $pdf->Ln(2);
        }
    }

    private function limpiarTemporalesViejos(): void
    {
        // deleteFileAfterSend() no siempre libera el archivo en Windows, así que
        // además limpiamos acá lo que haya quedado de descargas anteriores.
        foreach (glob($this->carpetaTmp . '/*.pdf') as $archivo) {
            if (time() - filemtime($archivo) > 300) {
                @unlink($archivo);
            }
        }
    }
}
