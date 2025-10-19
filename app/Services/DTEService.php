<?php

namespace App\Services;

use Illuminate\Support\Str;
use App\Models\Factura;

class DTEService
{
    /**
     * Generar código de generación UUID v4
     */
    public function generarCodigoGeneracion(): string
    {
        return strtoupper(Str::uuid()->toString());
    }

    /**
     * Generar número de control
     * Formato: DTE-[tipoDte]-[establecimiento]-[puntoVenta]-[correlativo]
     */
    public function generarNumeroControl(string $tipoDte = '01'): string
    {
        $establecimiento = config('dte.establecimiento', 'S001');
        $puntoVenta = config('dte.punto_venta', 'P001');
        
        // Obtener el último correlativo
        $ultimaFactura = Factura::where('tipo_dte', $tipoDte)
            ->orderBy('id', 'desc')
            ->first();
        
        $correlativo = $ultimaFactura ? $this->extraerCorrelativo($ultimaFactura->numero_control) + 1 : 1;
        
        // Formato: 15 dígitos con ceros a la izquierda
        $correlativoFormateado = str_pad($correlativo, 15, '0', STR_PAD_LEFT);
        
        return "DTE-{$tipoDte}-{$establecimiento}{$puntoVenta}-{$correlativoFormateado}";
    }

    /**
     * Extraer correlativo del número de control
     */
    private function extraerCorrelativo(string $numeroControl): int
    {
        $partes = explode('-', $numeroControl);
        return (int) end($partes);
    }

    /**
     * Calcular totales de items según normativa MH
     * Cuando precio incluye IVA: ventaGravada = precio/1.13, ivaItem = precio - ventaGravada
     * Cuando precio NO incluye IVA: aplica fórmula [(VG)/1.13]*13%
     */
public function calcularTotalesItems(array $items, bool $precioIncluyeIva = true): array
{
    $totalGravada = 0;
    $totalExenta = 0;
    $totalNoSujeto = 0;
    $totalDescuento = 0;
    $totalIva = 0;

    foreach ($items as &$item) {
        $cantidad = (float) $item['cantidad'];
        $precioUni = (float) $item['precio_unitario'];
        $montoDescu = (float) ($item['monto_descuento'] ?? 0);

        // Total sin IVA (base gravada)
        $ventaGravada = round(($cantidad * $precioUni) - $montoDescu, 2);
        
        // IVA = ventaGravada * 0.115
        $ivaItem = round($ventaGravada * 0.115, 2);

        $item['venta_gravada'] = $ventaGravada;
        $item['venta_exenta'] = 0;
        $item['venta_no_sujeta'] = 0;
        $item['iva_item'] = $ivaItem;

        $totalGravada += $ventaGravada;
        $totalDescuento += $montoDescu;
        $totalIva += $ivaItem;
    }

    $subTotal = round($totalGravada + $totalExenta + $totalNoSujeto, 2);

    // Monto total de operación = subtotal (sin IVA)
    $totalPagar = round($subTotal, 2);

    return [
        'items' => $items,
        'totales' => [
            'total_gravada' => round($totalGravada, 2),
            'total_exenta' => round($totalExenta, 2),
            'total_no_sujeto' => round($totalNoSujeto, 2),
            'sub_total_ventas' => round($subTotal, 2),
            'total_descuento' => round($totalDescuento, 2),
            'sub_total' => round($subTotal, 2),
            'total_iva' => round($totalIva, 2),
            'monto_total_operacion' => $totalPagar,
            'total_pagar' => $totalPagar,
        ]
    ];
}

    /**
     * Convertir número a letras
     */
    public function numeroALetras(float $numero): string
    {
        $entero = floor($numero);
        $decimales = round(($numero - $entero) * 100);

        $letras = $this->convertirEntero($entero);
        
        return strtoupper($letras) . " DÓLARES " . str_pad($decimales, 2, '0', STR_PAD_LEFT) . "/100";
    }

    private function convertirEntero(int $numero): string
    {
        $unidades = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
        $decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $especiales = ['DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
        $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

        if ($numero == 0) return 'CERO';
        if ($numero == 100) return 'CIEN';
        
        $letras = '';

        // Millones
        if ($numero >= 1000000) {
            $millones = floor($numero / 1000000);
            $letras .= $this->convertirEntero($millones) . ' MILLÓN ';
            if ($millones > 1) $letras = str_replace('MILLÓN', 'MILLONES', $letras);
            $numero %= 1000000;
        }

        // Miles
        if ($numero >= 1000) {
            $miles = floor($numero / 1000);
            if ($miles == 1) {
                $letras .= 'MIL ';
            } else {
                $letras .= $this->convertirEntero($miles) . ' MIL ';
            }
            $numero %= 1000;
        }

        // Centenas
        if ($numero >= 100) {
            $letras .= $centenas[floor($numero / 100)] . ' ';
            $numero %= 100;
        }

        // Decenas y unidades
        if ($numero >= 10 && $numero < 20) {
            $letras .= $especiales[$numero - 10];
        } else {
            if ($numero >= 20) {
                $letras .= $decenas[floor($numero / 10)];
                $numero %= 10;
                if ($numero > 0) $letras .= ' Y ';
            }
            if ($numero > 0 && $numero < 10) {
                $letras .= $unidades[$numero];
            }
        }

        return trim($letras);
    }

    /**
     * Formatear número de documento según tipo
     */
    private function formatearNumeroDocumento(string $tipoDocumento, string $numeroDocumento): string
    {
        // Limpiar el número (quitar espacios, guiones, etc.)
        $numero = preg_replace('/[^0-9]/', '', $numeroDocumento);
        
        switch ($tipoDocumento) {
            case '13': // DUI
                // Formato: ########-#
                if (strlen($numero) === 9) {
                    return substr($numero, 0, 8) . '-' . substr($numero, 8, 1);
                }
                return $numero;
                
            case '36': // NIT
                // NO formatear NIT - enviarlo sin guiones (solo números)
                return $numero;
                
            default:
                // Para otros tipos, devolver sin formato (solo números)
                return $numero;
        }
    }

    /**
     * Construir JSON completo del DTE
     */
    public function construirDteJson(array $datos, bool $precioIncluyeIva = true): array
    {
        $numeroControl = $this->generarNumeroControl($datos['tipo_dte'] ?? '01');
        $codigoGeneracion = $this->generarCodigoGeneracion();
        $fechaEmision = now()->format('Y-m-d');
        $horaEmision = now()->format('H:i:s');

        $calculos = $this->calcularTotalesItems($datos['items'], $precioIncluyeIva);
        $totalLetras = $this->numeroALetras($calculos['totales']['total_pagar']);
        
        // Formatear número de documento del receptor
        $numDocumentoFormateado = $this->formatearNumeroDocumento(
            $datos['receptor']['tipo_documento'],
            $datos['receptor']['num_documento']
        );

        return [
            'nit' => config('dte.nit'),
            'activo' => true,
            'passwordPri' => config('dte.password_pri'),
            'dteJson' => [
                'identificacion' => [
                    'version' => 2,
                    'ambiente' => config('dte.ambiente', '00'),
                    'tipoDte' => $datos['tipo_dte'] ?? '01',
                    'numeroControl' => $numeroControl,
                    'codigoGeneracion' => $codigoGeneracion,
                    'tipoModelo' => 1,
                    'tipoOperacion' => 1,
                    'fecEmi' => $fechaEmision,
                    'horEmi' => $horaEmision,
                    'tipoMoneda' => 'USD',
                    'tipoContingencia' => null,
                    'motivoContin' => null,
                ],
                'documentoRelacionado' => null,
                'emisor' => [
                    'nit' => config('dte.emisor.nit'),
                    'nrc' => config('dte.emisor.nrc'),
                    'nombre' => config('dte.emisor.nombre'),
                    'nombreComercial' => config('dte.emisor.nombre_comercial'),
                    'codActividad' => config('dte.emisor.cod_actividad'),
                    'descActividad' => config('dte.emisor.desc_actividad'),
                    'codEstable' => config('dte.emisor.cod_estable'),
                    'codPuntoVenta' => config('dte.emisor.cod_punto_venta'),
                    'direccion' => config('dte.emisor.direccion'),
                    'telefono' => config('dte.emisor.telefono'),
                    'correo' => config('dte.emisor.correo'),
                ],
                'receptor' => [
    'tipoDocumento' => $datos['receptor']['tipo_documento'],
    'numDocumento' => $numDocumentoFormateado,
    'nrc' => $datos['receptor']['nrc'] ?? null,
    'nombre' => $datos['receptor']['nombre'],
    'codActividad' => $datos['receptor']['cod_actividad'] ?? null,
    'descActividad' => !empty($datos['receptor']['desc_actividad']) && strlen($datos['receptor']['desc_actividad']) >= 5 
        ? $datos['receptor']['desc_actividad'] 
        : 'ACTIVIDADES NO ESPECIFICADAS',
    'direccion' => [
        'departamento' => $datos['receptor']['departamento'],
        'municipio' => $datos['receptor']['municipio'],
        'distrito' => $datos['receptor']['distrito'] ?? '03',  // ← DEBE ESTAR AQUÍ
        'complemento' => $datos['receptor']['complemento'],
    ],
    'telefono' => $datos['receptor']['telefono'] ?? null,
    'correo' => $datos['receptor']['correo'] ?? null,
],
                'ventaTercero' => null,
                'cuerpoDocumento' => array_map(function ($item, $index) {
                    return [
                        'numItem' => $index + 1,
                        'tipoItem' => 1,
                        'numeroDocumento' => null,
                        'cantidad' => floatval($item['cantidad']),
                        'codigo' => $item['codigo'],
                        'codTributo' => null,
                        'uniMedida' => (int) ($item['uni_medida'] ?? 59),
                        'descripcion' => $item['descripcion'],
                        'precioUni' => floatval($item['precio_unitario']),
                        'montoDescu' => floatval($item['monto_descuento'] ?? 0),
                        'ventaNoSuj' => floatval($item['venta_no_sujeta']),
                        'ventaExenta' => floatval($item['venta_exenta']),
                        'ventaGravada' => floatval($item['venta_gravada']),
                        'tributos' => null,
                        'psv' => 0,
                        'noGravado' => 0.0,
                        'ivaItem' => floatval($item['iva_item']),
                    ];
                }, $calculos['items'], array_keys($calculos['items'])),
                'resumen' => [
                    'totalNoSuj' => floatval($calculos['totales']['total_no_sujeto']),
                    'totalExenta' => floatval($calculos['totales']['total_exenta']),
                    'totalGravada' => floatval($calculos['totales']['total_gravada']),
                    'subTotalVentas' => floatval($calculos['totales']['sub_total_ventas']),
                    'descuNoSuj' => 0.0,
                    'descuExenta' => 0.0,
                    'descuGravada' => floatval($calculos['totales']['total_descuento']),
                    'porcentajeDescuento' => 0.0,
                    'totalDescu' => floatval($calculos['totales']['total_descuento']),
                    'tributos' => null,
                    'subTotal' => floatval($calculos['totales']['sub_total']),
                    'totalIva' => floatval($calculos['totales']['total_iva']),
                    'ivaRete1' => 0.0,
                    'montoTotalOperacion' => floatval($calculos['totales']['monto_total_operacion']),
                    'totalNoGravado' => 0.0,
                    'saldoFavor' => 0.0,
                    'totalPagar' => floatval($calculos['totales']['total_pagar']),
                    'totalLetras' => $totalLetras,
                    'condicionOperacion' => (int) ($datos['condicion_operacion'] ?? 1),
                    'numPagoElectronico' => '1',
                    'pagos' => [
                        [
                            'codigo' => $datos['forma_pago'] ?? '01',
                            'montoPago' => floatval($calculos['totales']['total_pagar']),
                            'referencia' => $datos['referencia_pago'] ?? null,
                            'plazo' => null,
                            'periodo' => null,
                        ]
                    ],
                    'observaciones' => $datos['observaciones'] ?? null,
                ],
                'otrosDocumentos' => null,
                'apendice' => null,
            ]
        ];
    }
}