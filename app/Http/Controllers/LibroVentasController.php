<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use Illuminate\Http\Request;

class LibroVentasController extends Controller
{
    private const ESTADOS_VALIDOS = ['enviado_mh', 'firmado', 'firmado_contingencia'];

    /**
     * Libro de Ventas mensual, dividido en los 3 libros que exige el MH:
     * - Contribuyentes (CCF - DTE-03, y Notas de Crédito relacionadas - DTE-05)
     * - Consumidor Final (Factura - DTE-01)
     * - Sujeto Excluido (DTE-14)
     */
    public function index(Request $request)
    {
        $mes = (int) $request->input('mes', now()->month);
        $anio = (int) $request->input('anio', now()->year);

        $baseQuery = fn() => Factura::whereIn('estado', self::ESTADOS_VALIDOS)
            ->whereMonth('fecha_emision', $mes)
            ->whereYear('fecha_emision', $anio);

        $contribuyentes = (clone $baseQuery())
            ->whereIn('tipo_dte', ['03', '05'])
            ->orderBy('fecha_emision')
            ->orderBy('numero_control')
            ->get();

        $consumidorFinal = (clone $baseQuery())
            ->where('tipo_dte', '01')
            ->orderBy('fecha_emision')
            ->orderBy('numero_control')
            ->get();

        $sujetoExcluido = (clone $baseQuery())
            ->where('tipo_dte', '14')
            ->orderBy('fecha_emision')
            ->orderBy('numero_control')
            ->get();

        $totales = [
            'contribuyentes' => $this->calcularTotales($contribuyentes),
            'consumidor_final' => $this->calcularTotales($consumidorFinal),
            'sujeto_excluido' => $this->calcularTotales($sujetoExcluido),
        ];

        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        return view('libro_ventas.index', compact(
            'contribuyentes', 'consumidorFinal', 'sujetoExcluido',
            'totales', 'mes', 'anio', 'meses'
        ));
    }

    /**
     * Calcula los totales de una colección de facturas, tratando las Notas
     * de Crédito (DTE-05) como montos negativos (reducen la venta del mes).
     */
    private function calcularTotales($facturas): array
    {
        $totales = [
            'exenta' => 0, 'no_sujeta' => 0, 'gravada' => 0,
            'iva' => 0, 'total' => 0, 'cantidad' => $facturas->count(),
        ];

        foreach ($facturas as $factura) {
            $signo = $factura->tipo_dte === '05' ? -1 : 1;
            $totales['exenta'] += $signo * $factura->total_exenta;
            $totales['no_sujeta'] += $signo * $factura->total_no_sujeto;
            $totales['gravada'] += $signo * $factura->total_gravada;
            $totales['iva'] += $signo * $factura->total_iva;
            $totales['total'] += $signo * $factura->total_pagar;
        }

        return $totales;
    }

    /**
     * Exportar el Libro de Ventas del mes a Excel (.xlsx), un libro por hoja,
     * en el formato de columnas usado para la declaración de IVA.
     */
    public function exportar(Request $request)
    {
        $mes = (int) $request->input('mes', now()->month);
        $anio = (int) $request->input('anio', now()->year);

        $baseQuery = fn() => Factura::whereIn('estado', self::ESTADOS_VALIDOS)
            ->whereMonth('fecha_emision', $mes)
            ->whereYear('fecha_emision', $anio);

        $contribuyentes = (clone $baseQuery())->whereIn('tipo_dte', ['03', '05'])
            ->orderBy('fecha_emision')->orderBy('numero_control')->get();
        $consumidorFinal = (clone $baseQuery())->where('tipo_dte', '01')
            ->orderBy('fecha_emision')->orderBy('numero_control')->get();
        $sujetoExcluido = (clone $baseQuery())->where('tipo_dte', '14')
            ->orderBy('fecha_emision')->orderBy('numero_control')->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        $this->construirHojaContribuyentes($spreadsheet->getActiveSheet(), $contribuyentes);
        $this->construirHojaConsumidorFinal($spreadsheet->createSheet(), $consumidorFinal, 'Consumidor Final');
        $this->construirHojaConsumidorFinal($spreadsheet->createSheet(), $sujetoExcluido, 'Sujeto Excluido');

        $spreadsheet->setActiveSheetIndex(0);

        $meses = [1=>'01',2=>'02',3=>'03',4=>'04',5=>'05',6=>'06',7=>'07',8=>'08',9=>'09',10=>'10',11=>'11',12=>'12'];
        $filename = "LibroVentas_{$anio}-{$meses[$mes]}.xlsx";

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function construirHojaContribuyentes($sheet, $facturas): void
    {
        $sheet->setTitle('Contribuyentes (CCF)');

        $headers = ['Fecha', 'Clase Doc.', 'N° Documento', 'NRC Cliente', 'Nombre Cliente', 'Ventas Exentas', 'Ventas No Sujetas', 'Ventas Gravadas', 'Débito Fiscal (IVA)', 'Ventas Totales'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValueByColumnAndRow($i + 1, 1, $h);
        }
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);

        $fila = 2;
        foreach ($facturas as $factura) {
            $signo = $factura->tipo_dte === '05' ? -1 : 1;
            $sheet->setCellValueByColumnAndRow(1, $fila, $factura->fecha_emision->format('d/m/Y'));
            $sheet->setCellValueByColumnAndRow(2, $fila, $factura->tipo_dte === '05' ? 'Nota de Crédito' : 'Crédito Fiscal');
            $sheet->setCellValueByColumnAndRow(3, $fila, $factura->numero_control);
            $sheet->setCellValueByColumnAndRow(4, $fila, $factura->receptor_nrc);
            $sheet->setCellValueByColumnAndRow(5, $fila, $factura->receptor_nombre);
            $sheet->setCellValueByColumnAndRow(6, $fila, $signo * (float) $factura->total_exenta);
            $sheet->setCellValueByColumnAndRow(7, $fila, $signo * (float) $factura->total_no_sujeto);
            $sheet->setCellValueByColumnAndRow(8, $fila, $signo * (float) $factura->total_gravada);
            $sheet->setCellValueByColumnAndRow(9, $fila, $signo * (float) $factura->total_iva);
            $sheet->setCellValueByColumnAndRow(10, $fila, $signo * (float) $factura->total_pagar);
            $fila++;
        }

        $this->agregarFilaTotales($sheet, $fila, 5, [6, 7, 8, 9, 10]);
        $sheet->getColumnDimension('E')->setWidth(32);
        $sheet->getColumnDimension('C')->setWidth(30);
    }

    private function construirHojaConsumidorFinal($sheet, $facturas, string $titulo): void
    {
        $sheet->setTitle($titulo);

        $headers = ['Fecha', 'N° Documento', 'Ventas Exentas', 'Ventas No Sujetas', 'Ventas Gravadas', 'IVA', 'Ventas Totales'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValueByColumnAndRow($i + 1, 1, $h);
        }
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);

        $fila = 2;
        foreach ($facturas as $factura) {
            $sheet->setCellValueByColumnAndRow(1, $fila, $factura->fecha_emision->format('d/m/Y'));
            $sheet->setCellValueByColumnAndRow(2, $fila, $factura->numero_control);
            $sheet->setCellValueByColumnAndRow(3, $fila, (float) $factura->total_exenta);
            $sheet->setCellValueByColumnAndRow(4, $fila, (float) $factura->total_no_sujeto);
            $sheet->setCellValueByColumnAndRow(5, $fila, (float) $factura->total_gravada);
            $sheet->setCellValueByColumnAndRow(6, $fila, (float) $factura->total_iva);
            $sheet->setCellValueByColumnAndRow(7, $fila, (float) $factura->total_pagar);
            $fila++;
        }

        $this->agregarFilaTotales($sheet, $fila, 2, [3, 4, 5, 6, 7]);
        $sheet->getColumnDimension('B')->setWidth(30);
    }

    private function agregarFilaTotales($sheet, int $fila, int $colEtiqueta, array $colsMontos): void
    {
        $sheet->setCellValueByColumnAndRow($colEtiqueta, $fila, 'TOTALES');
        $sheet->getStyleByColumnAndRow($colEtiqueta, $fila)->getFont()->setBold(true);

        foreach ($colsMontos as $col) {
            $letra = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValueByColumnAndRow($col, $fila, "=SUM({$letra}2:{$letra}" . ($fila - 1) . ")");
            $sheet->getStyleByColumnAndRow($col, $fila)->getFont()->setBold(true);
            $sheet->getStyleByColumnAndRow($col, $fila)->getNumberFormat()->setFormatCode('#,##0.00');
        }

        // Formato de moneda en todas las filas de datos
        foreach ($colsMontos as $col) {
            $letra = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->getStyle("{$letra}2:{$letra}" . ($fila - 1))->getNumberFormat()->setFormatCode('#,##0.00');
        }
    }
}
