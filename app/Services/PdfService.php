<?php

namespace App\Services;

use Illuminate\Support\Facades\View;

class PdfService
{
    /**
     * Genera un PDF desde una vista Blade
     *
     * @param string $view Nombre de la vista
     * @param array $data Datos para la vista
     * @return string Contenido del PDF en binario
     */
    public function generateFromView(string $view, array $data = []): string
    {
        // Renderizar la vista HTML
        $html = View::make($view, $data)->render();

        // Verificar si existe DomPDF (barryvdh/laravel-dompdf)
        if (class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
            return $this->generateWithLaravelDomPDF($view, $data);
        }

        // Verificar si existe Dompdf nativo
        if (class_exists('\Dompdf\Dompdf')) {
            return $this->generateWithDomPDF($html);
        }

        // Verificar si existe mPDF
        if (class_exists('\Mpdf\Mpdf')) {
            return $this->generateWithMPDF($html);
        }

        // Verificar si existe la clase TCPDF
        if (class_exists('\TCPDF')) {
            return $this->generateWithTCPDF($html);
        }

        // Fallback: devolver HTML convertido a PDF manualmente
        return $this->generateBasicPDF($html);
    }

    /**
     * Genera PDF usando Laravel DomPDF (Facade)
     */
    private function generateWithLaravelDomPDF(string $view, array $data): string
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, $data)
            ->setPaper('letter', 'portrait');
        return $pdf->output();
    }

    /**
     * Genera PDF usando DomPDF nativo
     */
    private function generateWithDomPDF(string $html): string
    {
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('Letter', 'portrait');
        $dompdf->render();
        return $dompdf->output();
    }

    /**
     * Genera PDF usando mPDF
     */
    private function generateWithMPDF(string $html): string
    {
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'Letter',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 15,
        ]);

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }

    /**
     * Genera PDF usando TCPDF
     */
    private function generateWithTCPDF(string $html): string
    {
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Configuración del documento
        $pdf->SetCreator('Sistema de Facturación Electrónica');
        $pdf->SetAuthor('Sistema de Facturación');
        $pdf->SetTitle('Factura Electrónica');

        // Remover header y footer por defecto
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Configurar márgenes
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);

        // Agregar página
        $pdf->AddPage();

        // Escribir HTML
        $pdf->writeHTML($html, true, false, true, false, '');

        // Retornar PDF como string
        return $pdf->Output('', 'S');
    }

    /**
     * Genera PDF básico usando headers de PHP
     * NOTA: Este es un fallback muy básico que genera un "pseudo-PDF"
     * Para producción, se recomienda instalar una librería apropiada
     */
    private function generateBasicPDF(string $html): string
    {
        // Esta es una solución temporal muy básica
        // En realidad, deberíamos tener una librería PDF instalada

        throw new \Exception(
            'No se encontró ninguna librería PDF instalada. ' .
            'Por favor instala manualmente una de las siguientes: ' .
            'barryvdh/laravel-dompdf, mpdf/mpdf, o tecnickcom/tcpdf. ' .
            PHP_EOL .
            'Comando sugerido: composer require barryvdh/laravel-dompdf'
        );
    }
}
