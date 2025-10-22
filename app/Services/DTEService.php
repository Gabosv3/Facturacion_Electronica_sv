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
     * Formatear número de documento según tipo (CAT-022)
     *
     * Tipos de documento según Ministerio de Hacienda:
     * 36 = NIT (Número de Identificación Tributaria) - Contribuyentes IVA
     * 13 = DUI (Documento Único de Identidad) - Personas naturales SIN IVA
     * 02 = Carnet de Residente
     * 03 = Pasaporte
     * 37 = Otro
     *
     * IMPORTANTE: El MH requiere que el DUI se envíe SIN guión (solo 9 dígitos)
     */
    private function formatearNumeroDocumento(string $tipoDocumento, string $numeroDocumento): string
    {
        switch ($tipoDocumento) {
            case '13': // DUI - Persona natural SIN IVA
                // Limpiar el número (quitar espacios, guiones, letras, etc.)
                $numero = preg_replace('/[^0-9]/', '', $numeroDocumento);

                // El DUI debe tener exactamente 9 dígitos
                // IMPORTANTE: Se envía SIN guión al MH (solo números)
                if (strlen($numero) < 9) {
                    // Si tiene menos de 9 dígitos, rellenar con ceros a la izquierda
                    $numero = str_pad($numero, 9, '0', STR_PAD_LEFT);
                } elseif (strlen($numero) > 9) {
                    // Si tiene más de 9 dígitos, tomar solo los primeros 9
                    $numero = substr($numero, 0, 9);
                }

                // Retornar solo números, SIN guión
                return $numero;

            case '36': // NIT - Contribuyente inscrito en IVA
                // Limpiar el número (quitar espacios, guiones, etc.)
                $numero = preg_replace('/[^0-9]/', '', $numeroDocumento);

                // NO formatear NIT - enviarlo sin guiones (solo números)
                // El NIT debe tener 14 dígitos
                if (strlen($numero) < 14) {
                    $numero = str_pad($numero, 14, '0', STR_PAD_LEFT);
                }
                // Solo enviar los primeros 14 dígitos
                return substr($numero, 0, 14);

            case '02': // Carnet de Residente
            case '03': // Pasaporte
            case '37': // Otro
                // Para estos tipos, se permite alfanumérico
                // Quitar solo espacios, pero permitir letras y números
                return trim($numeroDocumento);

            default:
                // Por defecto, limpiar espacios
                return trim($numeroDocumento);
        }
    }

    /**
     * Construir JSON según tipo de DTE
     */
    public function construirDteJson(array $datos, bool $precioIncluyeIva = true): array
    {
        $tipoDte = $datos['tipo_dte'] ?? '01';

        // Si es CCF (DTE-03), usar método específico
        if ($tipoDte === '03') {
            return $this->construirDteCCF($datos, $precioIncluyeIva);
        }

        // Para DTE-01 (Factura) usar el método original
        return $this->construirDteFactura($datos, $precioIncluyeIva);
    }

    /**
     * Construir JSON para DTE-01 (Factura)
     */
    private function construirDteFactura(array $datos, bool $precioIncluyeIva = true): array
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

        // Determinar versión según tipo de DTE
        // DTE-01 (Factura) = versión 2
        // DTE-03 (CCF) = versión 3
        $tipoDte = $datos['tipo_dte'] ?? '01';
        $version = $tipoDte === '03' ? 3 : 2;

        return [
            'nit' => config('dte.nit'),
            'activo' => true,
            'passwordPri' => config('dte.password_pri'),
            'dteJson' => [
                'identificacion' => [
                    'version' => $version,
                    'ambiente' => config('dte.ambiente', '00'),
                    'tipoDte' => $tipoDte,
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
        'distrito' => $datos['receptor']['distrito'] ? str_pad($datos['receptor']['distrito'], 2, '0', STR_PAD_LEFT) : '03', // Código de distrito según CAT-12 (formato 2 dígitos)
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

    /**
     * Construir JSON para DTE-03 (Comprobante de Crédito Fiscal - CCF)
     * El esquema del CCF es diferente al DTE-01
     */
    private function construirDteCCF(array $datos, bool $precioIncluyeIva = true): array
    {
        $numeroControl = $this->generarNumeroControl('03');
        $codigoGeneracion = $this->generarCodigoGeneracion();
        $fechaEmision = now()->format('Y-m-d');
        $horaEmision = now()->format('H:i:s');

        $calculos = $this->calcularTotalesItems($datos['items'], $precioIncluyeIva);
        $totalLetras = $this->numeroALetras($calculos['totales']['total_pagar']);

        // Para CCF solo se usa NIT (sin formateo especial)
        $numDocumentoFormateado = $this->formatearNumeroDocumento('36', $datos['receptor']['num_documento']);

        // Calcular IVA (13% en lugar de 11.5% que usa el sistema actual)
        $totalIva = round($calculos['totales']['total_gravada'] * 0.13, 2);
        $totalPagar = $calculos['totales']['total_gravada'] + $totalIva;

        return [
            'nit' => config('dte.nit'),
            'activo' => true,
            'passwordPri' => config('dte.password_pri'),
            'dteJson' => [
                'identificacion' => [
                    'version' => 3,  // CCF usa versión 3
                    'ambiente' => config('dte.ambiente', '00'),
                    'tipoDte' => '03',
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
                    'tipoEstablecimiento' => '01', // Requerido para CCF
                    'codEstable' => config('dte.emisor.cod_estable'),
                    'codPuntoVenta' => config('dte.emisor.cod_punto_venta'),
                    'codEstableMH' => config('dte.emisor.cod_estable') ?? null,
                    'codPuntoVentaMH' => config('dte.emisor.cod_punto_venta') ?? null,
                    'direccion' => [
                        'departamento' => config('dte.emisor.direccion.departamento'),
                        'municipio' => config('dte.emisor.direccion.municipio'),
                        // NO incluir distrito en CCF
                        'complemento' => config('dte.emisor.direccion.complemento'),
                    ],
                    'telefono' => config('dte.emisor.telefono'),
                    'correo' => config('dte.emisor.correo'),
                ],
                'receptor' => [
                    'nit' => $numDocumentoFormateado,  // En CCF se usa 'nit' en lugar de 'numDocumento'
                    'nrc' => $datos['receptor']['nrc'],  // Obligatorio en CCF
                    'nombre' => $datos['receptor']['nombre'],
                    'nombreComercial' => $datos['receptor']['nombre'],  // Requerido en CCF
                    'codActividad' => $datos['receptor']['cod_actividad'],  // Obligatorio en CCF
                    'descActividad' => !empty($datos['receptor']['desc_actividad']) && strlen($datos['receptor']['desc_actividad']) >= 5
                        ? $datos['receptor']['desc_actividad']
                        : 'ACTIVIDADES NO ESPECIFICADAS',
                    'direccion' => [
                        'departamento' => $datos['receptor']['departamento'],
                        'municipio' => $datos['receptor']['municipio'],
                        // NO incluir distrito en CCF
                        'complemento' => $datos['receptor']['complemento'],
                    ],
                    'telefono' => $datos['receptor']['telefono'] ?? null,
                    'correo' => $datos['receptor']['correo'] ?? null,
                ],
                'ventaTercero' => null,
                'cuerpoDocumento' => array_map(function ($item, $index) {
                    $ivaItem = round(floatval($item['venta_gravada']) * 0.13, 2);

                    return [
                        'numItem' => $index + 1,
                        'tipoItem' => 2,  // Para CCF es tipo 2 (bienes) o 3 (servicios), no 1
                        'numeroDocumento' => null,
                        'cantidad' => floatval($item['cantidad']),
                        'codigo' => $item['codigo'],
                        'codTributo' => null,  // NO se usa array en CCF, va null
                        'uniMedida' => (int) ($item['uni_medida'] ?? 59),
                        'descripcion' => $item['descripcion'],
                        'precioUni' => floatval($item['precio_unitario']),
                        'montoDescu' => floatval($item['monto_descuento'] ?? 0),
                        'ventaNoSuj' => 0.0,
                        'ventaExenta' => 0.0,
                        'ventaGravada' => floatval($item['venta_gravada']),
                        'tributos' => ['20'],  // En CCF es array simple de strings, no objetos
                        'psv' => 0.0,
                        'noGravado' => 0.0,
                        // NO incluir ivaItem en CCF
                    ];
                }, $calculos['items'], array_keys($calculos['items'])),
                'resumen' => [
                    'totalNoSuj' => 0.0,
                    'totalExenta' => 0.0,
                    'totalGravada' => floatval($calculos['totales']['total_gravada']),
                    'subTotalVentas' => floatval($calculos['totales']['total_gravada']),
                    'descuNoSuj' => 0.0,
                    'descuExenta' => 0.0,
                    'descuGravada' => floatval($calculos['totales']['total_descuento']),
                    'porcentajeDescuento' => 0.0,
                    'totalDescu' => floatval($calculos['totales']['total_descuento']),
                    'tributos' => [  // En CCF el IVA total va en tributos
                        [
                            'codigo' => '20',
                            'descripcion' => 'IVA',
                            'valor' => $totalIva,
                        ]
                    ],
                    'subTotal' => floatval($calculos['totales']['total_gravada']),
                    'totalIva' => $totalIva,  // Guardamos para BD (aunque no se envía al MH en este campo)
                    'ivaRete1' => 0.0,  // Requerido en CCF
                    'ivaPerci1' => 0.0,  // Requerido en CCF
                    'reteRenta' => 0.0,  // Requerido en CCF
                    'montoTotalOperacion' => $totalPagar,
                    'totalNoGravado' => 0.0,
                    'saldoFavor' => 0.0,
                    'totalPagar' => $totalPagar,
                    'totalLetras' => $totalLetras,
                    'condicionOperacion' => (int) ($datos['condicion_operacion'] ?? 1),
                    'numPagoElectronico' => '1',
                    'pagos' => [
                        [
                            'codigo' => $datos['forma_pago'] ?? '01',
                            'montoPago' => $totalPagar,
                            'referencia' => $datos['referencia_pago'] ?? null,
                            'plazo' => null,
                            'periodo' => null,
                        ]
                    ],
                    // NO incluir observaciones en resumen de CCF
                ],
                'extension' => null,  // Campo requerido en CCF (puede ser null)
                'apendice' => null,
                'otrosDocumentos' => null,  // Requerido en CCF (puede ser null según algunos casos)
            ]
        ];
    }
}