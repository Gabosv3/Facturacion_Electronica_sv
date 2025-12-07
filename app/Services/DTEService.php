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
    // Por defecto usa 11.5% (DTE-01)
    return $this->calcularTotalesItemsConTasa($items, 0.115, $precioIncluyeIva);
}

    /**
     * Calcular totales para DTE-05 con opción de extraer IVA del precio
     */
    private function calcularTotalesNotaCredito(array $items, float $tasaIva, bool $precioIncluyeIva): array
    {
        $totalGravada = 0;
        $totalExenta = 0;
        $totalNoSujeto = 0;
        $totalDescuento = 0;
        $totalIva = 0;

        foreach ($items as &$item) {
            $cantidad = (float) $item['cantidad'];
            $precioUniIngresado = (float) $item['precio_unitario'];
            $montoDescu = (float) ($item['monto_descuento'] ?? 0);

            if ($precioIncluyeIva) {
                // Si el precio incluye IVA, extraer el precio unitario SIN IVA
                // Fórmula: precioSinIva = precioConIva / (1 + tasaIva)
                $precioUniSinIva = round($precioUniIngresado / (1 + $tasaIva), 2);

                // Calcular venta gravada usando el precio sin IVA
                $ventaGravada = round(($cantidad * $precioUniSinIva) - $montoDescu, 2);

                // El precio unitario para el JSON debe ser SIN IVA
                $item['precio_unitario'] = $precioUniSinIva;
                $item['precio_incluye_iva'] = true;

                \Log::info('💰 Item con precio incluye IVA', [
                    'precio_ingresado_con_iva' => $precioUniIngresado,
                    'precio_sin_iva_calculado' => $precioUniSinIva,
                    'cantidad' => $cantidad,
                    'venta_gravada' => $ventaGravada,
                ]);
            } else {
                // Si el precio NO incluye IVA, usar directamente
                $ventaGravada = round(($cantidad * $precioUniIngresado) - $montoDescu, 2);
                $item['precio_unitario'] = $precioUniIngresado;
                $item['precio_incluye_iva'] = false;
            }

            $ivaItem = round($ventaGravada * $tasaIva, 2);

            $item['venta_gravada'] = $ventaGravada;
            $item['venta_exenta'] = 0;
            $item['venta_no_sujeta'] = 0;
            $item['iva_item'] = $ivaItem;

            $totalGravada += $ventaGravada;
            $totalDescuento += $montoDescu;
            $totalIva += $ivaItem;
        }

        $subTotal = round($totalGravada + $totalExenta + $totalNoSujeto, 2);
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
     * Calcular totales de items con tasa de IVA personalizada
     * Usado para DTE-03 (13%) y DTE-05 (heredado del documento relacionado)
     *
     * IMPORTANTE: Cuando precio incluye IVA, calculamos sobre el total para evitar errores de redondeo
     */
    public function calcularTotalesItemsConTasa(array $items, float $tasaIva, bool $precioIncluyeIva = true, string $tipoDte = '01'): array
    {
        $totalDescuento = 0;

        if ($precioIncluyeIva) {
            // DTE-01: ventaGravada incluye IVA, montoTotalOperacion = totalGravada
            // DTE-03 (CCF): ventaGravada SIN IVA, montoTotalOperacion = totalGravada + totalIva
            $esDTE01 = ($tipoDte === '01' || $tipoDte === '14');

            $totalConIva = 0;
            $sumaVentaGravadaItems = 0;
            $numItems = count($items);
            $itemIndex = 0;

            // Primer loop: calcular total con IVA
            foreach ($items as &$item) {
                $cantidad = (float) $item['cantidad'];
                $precioUniIngresado = (float) $item['precio_unitario'];
                $montoDescu = (float) ($item['monto_descuento'] ?? 0);

                $totalItemConIva = ($cantidad * $precioUniIngresado) - $montoDescu;
                $totalConIva += $totalItemConIva;
                $totalDescuento += $montoDescu;
            }

            $divisor = 1 + $tasaIva; // 1.13

            // Segundo loop: procesar cada item
            $sumaVentaGravada = 0;
            $sumaIvaItems = 0;

            foreach ($items as &$item) {
                $cantidad = (float) $item['cantidad'];
                $precioUniIngresado = (float) $item['precio_unitario'];
                $montoDescu = (float) ($item['monto_descuento'] ?? 0);

                $totalItemConIva = ($cantidad * $precioUniIngresado) - $montoDescu;

                if ($esDTE01) {
                    // DTE-01: ventaGravada CON IVA
                    $ventaGravada = round($totalItemConIva, 2);
                    $precioUniParaJson = $precioUniIngresado; // CON IVA
                } else {
                    // DTE-03 (CCF): ventaGravada SIN IVA
                    // IMPORTANTE: Primero calcular precio unitario SIN IVA
                    $precioUniParaJson = round($precioUniIngresado / $divisor, 2); // SIN IVA

                    // Luego calcular ventaGravada usando la fórmula del MH
                    // CRÍTICO: El MH valida que ventaGravada = cantidad × precioUni - descuento
                    $ventaGravada = round(($cantidad * $precioUniParaJson) - $montoDescu, 2);
                }

                // Acumular
                $sumaVentaGravada += $ventaGravada;

                // IVA del item
                // Para DTE-01 con precio incluye IVA: el IVA se EXTRAE, no se agrega
                // Para DTE-03: el IVA se CALCULA sobre la base
                if ($esDTE01) {
                    // Extraer IVA: IVA = ventaGravada - (ventaGravada / 1.115)
                    $baseImponible = round($ventaGravada / $divisor, 2);
                    $ivaItem = round($ventaGravada - $baseImponible, 2);
                } else {
                    // Calcular IVA: IVA = ventaGravada × 0.13
                    $ivaItem = round($ventaGravada * $tasaIva, 2);
                }

                $sumaIvaItems += $ivaItem;

                $item['precio_unitario'] = floatval($precioUniParaJson);
                $item['venta_gravada'] = floatval($ventaGravada);
                $item['venta_exenta'] = 0.0;
                $item['venta_no_sujeta'] = 0.0;
                $item['iva_item'] = floatval($ivaItem);
            }

            // Calcular totales finales basados en la suma de items (no en el total original)
            $totalGravada = round($sumaVentaGravada, 2);
            $totalIva = round($sumaIvaItems, 2);

            // DTE-01: montoTotalOperacion = totalGravada (ya incluye IVA)
            // DTE-03: montoTotalOperacion = totalConIva (el total que ingresó el usuario CON IVA)
            $totalPagar = $esDTE01 ? $totalGravada : round($totalConIva, 2);

            \Log::info('💰 Cálculo con precio incluye IVA', [
                'tipo_dte' => $tipoDte,
                'total_con_iva_ingresado' => $totalConIva,
                'total_gravada' => $totalGravada,
                'total_iva' => $totalIva,
                'monto_total_operacion' => $totalPagar,
            ]);

            return [
                'items' => $items,
                'totales' => [
                    'total_gravada' => $totalGravada,
                    'total_exenta' => 0.0,
                    'total_no_sujeto' => 0.0,
                    'sub_total_ventas' => $totalGravada,
                    'total_descuento' => round($totalDescuento, 2),
                    'sub_total' => $totalGravada,
                    'total_iva' => $totalIva,
                    'monto_total_operacion' => $totalPagar,
                    'total_pagar' => $totalPagar,
                ]
            ];

        } else {
            // Precio NO incluye IVA - Calcular venta gravada por item, pero IVA sobre el total
            $totalGravada = 0;
            $sumaVentaGravadaItems = 0;
            $numItems = count($items);
            $itemIndex = 0;

            // Primer loop: calcular total de venta gravada
            foreach ($items as &$item) {
                $cantidad = (float) $item['cantidad'];
                $precioUniIngresado = (float) $item['precio_unitario'];
                $montoDescu = (float) ($item['monto_descuento'] ?? 0);

                $ventaGravada = round(($cantidad * $precioUniIngresado) - $montoDescu, 2);
                $totalGravada += $ventaGravada;
                $totalDescuento += $montoDescu;
            }

            // Calcular IVA sobre el total de venta gravada
            $totalGravada = round($totalGravada, 2);
            $totalIva = round($totalGravada * $tasaIva, 2);

            // Segundo loop: asignar valores a cada item
            foreach ($items as &$item) {
                $cantidad = (float) $item['cantidad'];
                $precioUniIngresado = (float) $item['precio_unitario'];
                $montoDescu = (float) ($item['monto_descuento'] ?? 0);

                $ventaGravada = round(($cantidad * $precioUniIngresado) - $montoDescu, 2);

                // Ajustar último item para que suma coincida
                $itemIndex++;
                if ($itemIndex === $numItems) {
                    $ventaGravada = round($totalGravada - $sumaVentaGravadaItems, 2);
                } else {
                    $sumaVentaGravadaItems += $ventaGravada;
                }

                // IVA por item calculado proporcionalmente
                $ivaItem = round($ventaGravada * $tasaIva, 2);

                $item['venta_gravada'] = $ventaGravada;
                $item['venta_exenta'] = 0;
                $item['venta_no_sujeta'] = 0;
                $item['iva_item'] = $ivaItem;
            }

            $totalPagar = round($totalGravada + $totalIva, 2);
        }

        return [
            'items' => $items,
            'totales' => [
                'total_gravada' => round($totalGravada, 2),
                'total_exenta' => 0.0,
                'total_no_sujeto' => 0.0,
                'sub_total_ventas' => round($totalGravada, 2),
                'total_descuento' => round($totalDescuento, 2),
                'sub_total' => round($totalGravada, 2),
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
    private function formatearNumeroDocumento(string $tipoDocumento, string $numeroDocumento, ?string $tipoDte = null): string
    {
        switch ($tipoDocumento) {
            case '13': // DUI - Persona natural SIN IVA
                // Limpiar el número (quitar espacios, letras, etc., pero mantener guiones)
                $numero = preg_replace('/[^0-9\-]/', '', $numeroDocumento);

                // Verificar si ya tiene el formato correcto con guión
                if (preg_match('/^\d{8}-\d$/', $numero)) {
                    // Si es DTE-14, quitar el guión
                    if ($tipoDte === '14') {
                        return str_replace('-', '', $numero);
                    }
                    return $numero; // Ya tiene el formato correcto: 12345678-9
                }

                // Si no tiene guión, limpiar solo números
                $numero = preg_replace('/[^0-9]/', '', $numero);

                // El DUI debe tener exactamente 9 dígitos
                if (strlen($numero) < 9) {
                    // Si tiene menos de 9 dígitos, rellenar con ceros a la izquierda
                    $numero = str_pad($numero, 9, '0', STR_PAD_LEFT);
                } elseif (strlen($numero) > 9) {
                    // Si tiene más de 9 dígitos, tomar solo los primeros 9
                    $numero = substr($numero, 0, 9);
                }

                // IMPORTANTE:
                // - DTE-14: DUI SIN guión (9 dígitos): 012345678
                // - DTE-01: DUI CON guión: 01234567-8
                if ($tipoDte === '14') {
                    return $numero; // Sin guión para DTE-14
                }
                return substr($numero, 0, 8) . '-' . substr($numero, 8, 1); // Con guión para otros DTEs

            case '36': // NIT - Contribuyente inscrito en IVA
                // Limpiar el número (quitar espacios, guiones, etc.)
                $numero = preg_replace('/[^0-9]/', '', $numeroDocumento);

                // Detectar si el NIT está duplicado (28-30 dígitos)
                // Esto puede ocurrir si el NIT se guardó incorrectamente en la BD
                if (strlen($numero) >= 28 && strlen($numero) <= 30) {
                    // Verificar si la primera mitad es igual a la segunda mitad
                    $mitad = intval(strlen($numero) / 2);
                    $primeraParte = substr($numero, 0, $mitad);
                    $segundaParte = substr($numero, $mitad);

                    if ($primeraParte === $segundaParte || strpos($numero, $primeraParte) === 0) {
                        // El NIT está duplicado, tomar solo la primera parte
                        \Log::warning('⚠️ NIT duplicado detectado y corregido', [
                            'nit_duplicado' => $numero,
                            'nit_corregido' => $primeraParte,
                        ]);
                        $numero = $primeraParte;
                    }
                }

                // NO formatear NIT - enviarlo sin guiones (solo números)
                // El NIT debe tener 14 dígitos
                if (strlen($numero) < 14) {
                    $numero = str_pad($numero, 14, '0', STR_PAD_LEFT);
                } elseif (strlen($numero) > 14) {
                    // Si aún tiene más de 14 dígitos, tomar solo los primeros 14
                    \Log::warning('⚠️ NIT con más de 14 dígitos, truncando', [
                        'nit_original' => $numero,
                        'nit_truncado' => substr($numero, 0, 14),
                    ]);
                    $numero = substr($numero, 0, 14);
                }

                return $numero;

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
     * Construir objeto receptor con campos opcionales como null
     * Según esquema MH v2: los campos opcionales deben estar presentes con valor null si no tienen datos
     * IMPORTANTE: DTE-14 (Sujeto Excluido) NO permite el campo 'nrc' en el esquema
     * IMPORTANTE: Clientes Varios - Todos los campos (excepto nombre) son null
     *
     * @param array $data Datos del receptor
     * @param bool $omitirNrc Si es true, no incluye el campo nrc (para DTE-14)
     * @return array Receptor con campos opcionales como null cuando están vacíos
     */
    private function construirReceptor(array $data, bool $omitirNrc = false): array
    {
        // Verificar si es "Clientes Varios" (tipoDocumento y numDocumento son null)
        $esClientesVarios = empty($data['tipoDocumento']) && empty($data['numDocumento']);

        if ($esClientesVarios) {
            // Estructura para Clientes Varios según ejemplo del MH
            $receptor = [
                'tipoDocumento' => null,
                'numDocumento' => null,
            ];

            // NRC: solo incluir si no es DTE-14 (Sujeto Excluido)
            if (!$omitirNrc) {
                $receptor['nrc'] = null;
            }

            $receptor['nombre'] = 'CLIENTES VARIOS';
            $receptor['codActividad'] = null;
            $receptor['descActividad'] = null;
            $receptor['direccion'] = null;
            $receptor['telefono'] = null;
            $receptor['correo'] = null;

            return $receptor;
        }

        // Receptor normal (con datos específicos)
        // Campos requeridos siempre presentes
        $receptor = [
            'tipoDocumento' => $data['tipoDocumento'],
            'numDocumento' => $data['numDocumento'],
        ];

        // NRC: solo incluir si no es DTE-14 (Sujeto Excluido)
        if (!$omitirNrc) {
            $receptor['nrc'] = !empty($data['nrc']) ? $data['nrc'] : null;
        }

        $receptor['nombre'] = $data['nombre'];
        $receptor['codActividad'] = !empty($data['codActividad']) ? $data['codActividad'] : null;
        $receptor['descActividad'] = !empty($data['descActividad']) ? $data['descActividad'] : null;
        $receptor['direccion'] = $data['direccion'];
        $receptor['telefono'] = !empty($data['telefono']) ? $data['telefono'] : null;
        $receptor['correo'] = !empty($data['correo']) ? $data['correo'] : null;

        return $receptor;
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

        // Si es Nota de Crédito (DTE-05), usar método específico
        if ($tipoDte === '05') {
            return $this->construirDteNotaCredito($datos, $precioIncluyeIva);
        }

        // Si es Factura de Sujeto Excluido (DTE-14), usar método específico
        if ($tipoDte === '14') {
            return $this->construirDteSujetoExcluido($datos, $precioIncluyeIva);
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

        // DTE-01 ahora usa 13% de IVA (igual que DTE-03)
        $calculos = $this->calcularTotalesItemsConTasa($datos['items'], 0.13, $precioIncluyeIva, '01');

        // Los totales ya vienen calculados correctamente desde calcularTotalesItemsConTasa
        $totalLetras = $this->numeroALetras($calculos['totales']['total_pagar']);
        
        // Verificar si es Clientes Varios (tipo_documento y num_documento son null)
        $esClientesVarios = empty($datos['receptor']['tipo_documento']) && empty($datos['receptor']['num_documento']);

        // Formatear número de documento del receptor (solo si no es Clientes Varios)
        $numDocumentoFormateado = null;
        if (!$esClientesVarios) {
            $numDocumentoFormateado = $this->formatearNumeroDocumento(
                $datos['receptor']['tipo_documento'],
                $datos['receptor']['num_documento']
            );
        }

        // Determinar versión según tipo de DTE
        // DTE-01 (Factura) = versión 1 (sin distrito)
        // DTE-03 (CCF) = versión 3
        $tipoDte = $datos['tipo_dte'] ?? '01';
        $version = $tipoDte === '03' ? 3 : 1;

        // Construir dirección del receptor (null para Clientes Varios)
        $direccionReceptor = null;
        if (!$esClientesVarios && !empty($datos['receptor']['departamento'])) {
            $direccionReceptor = [
                'departamento' => $datos['receptor']['departamento'],
                'municipio' => $datos['receptor']['municipio'],
                'complemento' => $datos['receptor']['complemento'],
            ];
        }

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
                    'tipoEstablecimiento' => '01',
                    'codEstableMH' => null,
                    'codEstable' => null,
                    'codPuntoVentaMH' => null,
                    'codPuntoVenta' => null,
                    'direccion' => [
                        'departamento' => config('dte.emisor.direccion.departamento'),
                        'municipio' => config('dte.emisor.direccion.municipio'),
                        'complemento' => config('dte.emisor.direccion.complemento'),
                    ],
                    'telefono' => config('dte.emisor.telefono'),
                    'correo' => config('dte.emisor.correo'),
                ],
                'receptor' => $this->construirReceptor([
                    'tipoDocumento' => $datos['receptor']['tipo_documento'] ?? null,
                    'numDocumento' => $numDocumentoFormateado,
                    'nrc' => $datos['receptor']['nrc'] ?? null,
                    'nombre' => $datos['receptor']['nombre'] ?? 'CLIENTES VARIOS',
                    'codActividad' => $datos['receptor']['cod_actividad'] ?? null,
                    'descActividad' => !empty($datos['receptor']['desc_actividad']) && strlen($datos['receptor']['desc_actividad']) >= 5
                        ? $datos['receptor']['desc_actividad']
                        : null,
                    'direccion' => $direccionReceptor,
                    'telefono' => $datos['receptor']['telefono'] ?? null,
                    'correo' => $datos['receptor']['correo'] ?? null,
                ]),
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
                    // Los totales ya vienen correctamente calculados desde calcularTotalesItemsConTasa
                    'totalIva' => floatval($calculos['totales']['total_iva']),
                    'ivaRete1' => 0.0,
                    'reteRenta' => 0.0,
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
                ],
                'extension' => null,
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

        // CCF usa 13% de IVA - Llamar directamente a calcularTotalesItemsConTasa
        $calculos = $this->calcularTotalesItemsConTasa($datos['items'], 0.13, $precioIncluyeIva, '03');

        // Para CCF solo se usa NIT (sin formateo especial)
        $numDocumentoFormateado = $this->formatearNumeroDocumento('36', $datos['receptor']['num_documento']);

        // Los totales ya vienen calculados correctamente desde calcularTotalesItemsConTasa
        $totalIva = $calculos['totales']['total_iva'];
        $totalPagar = $calculos['totales']['total_pagar'];
        $totalLetras = $this->numeroALetras($totalPagar);

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

        return $resultado;
    }

    /**
     * Construir JSON para DTE-05 (Nota de Crédito)
     * La nota de crédito se usa para anular o ajustar facturas o CCF emitidos previamente
     * IMPORTANTE: DTE-05 tiene un esquema JSON completamente diferente a DTE-01 y DTE-03
     */
    private function construirDteNotaCredito(array $datos, bool $precioIncluyeIva = true): array
    {
        $numeroControl = $this->generarNumeroControl('05');
        $codigoGeneracion = $this->generarCodigoGeneracion();
        $fechaEmision = now()->format('Y-m-d');
        $horaEmision = now()->format('H:i:s');

        // IMPORTANTE: DTE-05 SIEMPRE usa IVA del 13% independientemente del documento relacionado
        // Esto es diferente a lo que se esperaría lógicamente, pero es lo que el MH requiere
        $tipoDocRelacionado = $datos['documento_relacionado']['tipo_dte'] ?? '01';
        $tasaIva = 0.13;  // SIEMPRE 13% para DTE-05

        \Log::info('🧮 DTEService - Determinando tasa IVA para DTE-05', [
            'documento_relacionado_completo' => $datos['documento_relacionado'] ?? 'NO EXISTE',
            'tipo_doc_relacionado_extraido' => $tipoDocRelacionado,
            'tasa_iva_seleccionada' => $tasaIva,
            'porcentaje_iva' => ($tasaIva * 100) . '%',
            'nota' => 'DTE-05 SIEMPRE usa 13% según validación MH',
        ]);

        // Calcular totales usando el método específico para notas de crédito
        // que permite extraer IVA del precio cuando está incluido
        $calculos = $this->calcularTotalesNotaCredito($datos['items'], $tasaIva, $precioIncluyeIva);

        // Calcular totales finales - recalcular IVA explícitamente para asegurar precisión
        // (igual que en DTE-03, evita problemas de redondeo acumulativo)
        $totalIva = round($calculos['totales']['total_gravada'] * $tasaIva, 2);

        // IMPORTANTE: En DTE-05 el montoTotalOperacion SÍ incluye el IVA (igual que facturas)
        // Aunque es una nota de crédito, el MH valida que el total incluya IVA
        $totalPagar = $calculos['totales']['total_gravada'] + $totalIva;
        $totalLetras = $this->numeroALetras($totalPagar);

        // Determinar versión (DTE-05 = versión 3 según especificaciones MH)
        $version = 3;

        // Formatear fecha de emisión del documento relacionado (debe ser yyyy-MM-dd, no timestamp)
        $fechaEmisionDocRelacionado = $datos['documento_relacionado']['fecha_emision'];
        if ($fechaEmisionDocRelacionado instanceof \DateTime || $fechaEmisionDocRelacionado instanceof \Carbon\Carbon) {
            $fechaEmisionDocRelacionado = $fechaEmisionDocRelacionado->format('Y-m-d');
        } elseif (is_string($fechaEmisionDocRelacionado) && strlen($fechaEmisionDocRelacionado) > 10) {
            // Si es un timestamp string, convertir a solo fecha
            $fechaEmisionDocRelacionado = substr($fechaEmisionDocRelacionado, 0, 10);
        }

        // Determinar tipo de documento relacionado según CAT-025 (no CAT-026)
        // CAT-025: 03=Factura, 05=Nota de Crédito, 06=Nota de Débito, etc.
        $tipoDocumentoRelacionado = $tipoDocRelacionado === '01' ? '03' : '05';

        return [
            'nit' => config('dte.nit'),
            'activo' => true,
            'passwordPri' => config('dte.password_pri'),
            'dteJson' => [
                'identificacion' => [
                    'version' => $version,
                    'ambiente' => config('dte.ambiente', '00'),
                    'tipoDte' => '05',
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
                'documentoRelacionado' => [
                    [
                        'tipoDocumento' => $tipoDocumentoRelacionado, // CAT-025: 03=Factura, 05=CCF
                        'tipoGeneracion' => 1, // 1=Generado electrónicamente
                        'numeroDocumento' => $datos['documento_relacionado']['numero_control'],
                        'fechaEmision' => $fechaEmisionDocRelacionado,
                    ]
                ],
                'emisor' => [
                    'nit' => config('dte.emisor.nit'),
                    'nrc' => config('dte.emisor.nrc'),
                    'nombre' => config('dte.emisor.nombre'),
                    'codActividad' => config('dte.emisor.cod_actividad'),
                    'descActividad' => config('dte.emisor.desc_actividad'),
                    'nombreComercial' => config('dte.emisor.nombre_comercial'),
                    'tipoEstablecimiento' => '01', // 01=Casa Matriz, 02=Sucursal, 04=Punto de Venta
                    'direccion' => [
                        'departamento' => config('dte.emisor.direccion.departamento'),
                        'municipio' => config('dte.emisor.direccion.municipio'),
                        'complemento' => config('dte.emisor.direccion.complemento'),
                    ],
                    'telefono' => config('dte.emisor.telefono'),
                    'correo' => config('dte.emisor.correo'),
                ],
                'receptor' => [
                    'nit' => $this->formatearNumeroDocumento($datos['receptor']['tipo_documento'], $datos['receptor']['num_documento']),
                    'nrc' => $datos['receptor']['nrc'] ?? null,
                    'nombre' => $datos['receptor']['nombre'],
                    'codActividad' => $datos['receptor']['cod_actividad'] ?? null,
                    'descActividad' => !empty($datos['receptor']['desc_actividad']) && strlen($datos['receptor']['desc_actividad']) >= 5
                        ? $datos['receptor']['desc_actividad']
                        : 'ACTIVIDADES NO ESPECIFICADAS',
                    'nombreComercial' => $datos['receptor']['nombre'], // Requerido por esquema
                    'direccion' => [
                        'departamento' => $datos['receptor']['departamento'],
                        'municipio' => $datos['receptor']['municipio'],
                        'complemento' => $datos['receptor']['complemento'],
                    ],
                    'telefono' => $datos['receptor']['telefono'] ?? null,
                    'correo' => $datos['receptor']['correo'] ?? null,
                ],
                'ventaTercero' => null,
                'cuerpoDocumento' => (function() use ($datos, $calculos, $tasaIva) {
                    $numeroDocRelacionado = $datos['documento_relacionado']['numero_control'] ?? '';
                    $items = [];
                    $numItem = 1;

                    // 🔍 LOG: Ver cuántos items vienen de calcularTotalesItems
                    \Log::info('🔍 DTEService - Items recibidos en construirDteNotaCredito', [
                        'cantidad_items_en_calculos' => count($calculos['items'] ?? []),
                        'items_raw' => $calculos['items'] ?? [],
                        'tasa_iva_usada' => $tasaIva,
                    ]);

                    foreach ($calculos['items'] as $item) {
                        // El precio unitario ya viene procesado (sin IVA si estaba incluido)
                        $precioUnitarioFinal = floatval($item['precio_unitario']);

                        // Si el precio incluía IVA, agregarlo a la descripción
                        $descripcion = $item['descripcion'];
                        if (!empty($item['precio_incluye_iva']) && $item['precio_incluye_iva'] === true) {
                            $descripcion .= ' (precio incluye IVA)';
                        }

                        $itemData = [
                            'numItem' => $numItem,
                            'tipoItem' => 2,  // DTE-05 usa tipoItem=2 igual que version 3
                            'numeroDocumento' => $numeroDocRelacionado, // REQUERIDO por MH
                            'cantidad' => floatval($item['cantidad']),
                            'codigo' => $item['codigo'],
                            'codTributo' => null,  // Requerido por MH
                            'uniMedida' => (int) ($item['uni_medida'] ?? 59),
                            'descripcion' => $descripcion,
                            'precioUni' => $precioUnitarioFinal,  // Precio sin IVA
                            'montoDescu' => floatval($item['monto_descuento'] ?? 0),
                            'ventaNoSuj' => 0.0,  // Requerido por MH
                            'ventaExenta' => 0.0,  // Requerido por MH
                            'ventaGravada' => floatval($item['venta_gravada']),  // Base sin IVA
                            'tributos' => ['20'],  // Array simple de códigos igual que CCF
                            // psv y noGravado NO permitidos en DTE-05
                        ];

                        \Log::info('🔍 DTEService - Agregando item #' . $numItem, [
                            'itemData' => $itemData,
                            'precio_incluye_iva' => $item['precio_incluye_iva'] ?? false,
                            'precio_unitario_json' => $precioUnitarioFinal,
                            'venta_gravada' => $item['venta_gravada'],
                            'validacion_mh' => [
                                'formula' => 'ventaGravada = (cantidad * precioUni) - descuento',
                                'calculo' => ($itemData['cantidad'] * $itemData['precioUni']) - $itemData['montoDescu'],
                                'debe_ser_igual_a' => $itemData['ventaGravada'],
                                'coincide' => abs((($itemData['cantidad'] * $itemData['precioUni']) - $itemData['montoDescu']) - $itemData['ventaGravada']) < 0.01,
                            ],
                        ]);

                        $items[] = $itemData;
                        $numItem++;
                    }

                    // 🔍 LOG: Ver array final de items
                    \Log::info('🔍 DTEService - Array final de cuerpoDocumento', [
                        'cantidad_items_final' => count($items),
                        'items_final' => $items,
                        'items_json' => json_encode($items, JSON_PRETTY_PRINT),
                    ]);

                    return $items;
                })(),
                'resumen' => [
                    'totalNoSuj' => floatval($calculos['totales']['total_no_sujeto']),
                    'totalExenta' => floatval($calculos['totales']['total_exenta']),
                    'totalGravada' => floatval($calculos['totales']['total_gravada']),
                    'subTotalVentas' => floatval($calculos['totales']['sub_total_ventas']),
                    'descuNoSuj' => 0.0,
                    'descuExenta' => 0.0,
                    'descuGravada' => floatval($calculos['totales']['total_descuento']),
                    'totalDescu' => floatval($calculos['totales']['total_descuento']),
                    'tributos' => [  // REQUERIDO por esquema DTE-05 (igual que CCF)
                        [
                            'codigo' => '20',
                            'descripcion' => 'IVA',
                            'valor' => $totalIva,
                        ]
                    ],
                    'subTotal' => floatval($calculos['totales']['total_gravada']),  // Igual que totalGravada (sin IVA)
                    'ivaRete1' => 0.0,
                    'ivaPerci1' => 0.0, // REQUERIDO por esquema DTE-05
                    'reteRenta' => 0.0, // REQUERIDO por esquema DTE-05
                    'montoTotalOperacion' => $totalPagar,
                    // totalPagar NO permitido en DTE-05 según esquema MH
                    'totalLetras' => $totalLetras,
                    'condicionOperacion' => (int) ($datos['condicion_operacion'] ?? 1),
                ],
                'extension' => [ // REQUERIDO por esquema DTE-05
                    'nombEntrega' => null,
                    'docuEntrega' => null,
                    'nombRecibe' => null,
                    'docuRecibe' => null,
                    'observaciones' => $datos['observaciones'] ?? null,
                ],
                'apendice' => null,
            ]
        ];

        // Limpiar valores null del JSON antes de retornar
        return $resultado;
    }

    /**
     * Construir JSON para DTE-14 (Factura de Sujeto Excluido)
     * Esquema según errores del MH:
     * - Versión 3 (no 1)
     * - Usa 'receptor' (no 'sujetoExcluido')
     * - NO incluye 'extension'
     * - Campos específicos en resumen y cuerpoDocumento
     * - Requiere tipoContingencia y motivoContin
     * - Requiere distrito en emisor
     */
    private function construirDteSujetoExcluido(array $datos, bool $precioIncluyeIva = false): array
    {
        $numeroControl = $this->generarNumeroControl('14');
        $codigoGeneracion = $this->generarCodigoGeneracion();
        $fechaEmision = now()->format('Y-m-d');
        $horaEmision = now()->format('H:i:s');

        // Para sujeto excluido todo es "compra" (no hay IVA)
        $totalCompra = 0;
        $totalDescuento = 0;

        $items = [];
        foreach ($datos['items'] as $index => $item) {
            $cantidad = (float) $item['cantidad'];
            $precioUnitario = (float) $item['precio_unitario'];
            $montoDescu = (float) ($item['monto_descuento'] ?? 0);

            // Calcular compra (precio total sin IVA)
            $compra = round(($cantidad * $precioUnitario) - $montoDescu, 2);
            $totalCompra += $compra;
            $totalDescuento += $montoDescu;

            $items[] = [
                'numItem' => $index + 1,
                'tipoItem' => 2, // 2 = Bienes
                'cantidad' => $cantidad,
                'codigo' => $item['codigo'] ?? null,
                'uniMedida' => (int) ($item['uni_medida'] ?? 59),
                'descripcion' => $item['descripcion'],
                'precioUni' => $precioUnitario,
                'montoDescu' => $montoDescu,
                'compra' => $compra, // Campo requerido para DTE-14
            ];
        }

        $totalPagar = $totalCompra;
        $totalLetras = $this->numeroALetras($totalPagar);

        // Formatear número de documento del receptor (DTE-14 requiere sin guión)
        $numDocumentoFormateado = $this->formatearNumeroDocumento(
            $datos['receptor']['tipo_documento'],
            $datos['receptor']['num_documento'],
            '14' // Indicar que es DTE-14
        );

        return [
            'nit' => config('dte.nit'),
            'activo' => true,
            'passwordPri' => config('dte.password_pri'),
            'dteJson' => [
                'identificacion' => [
                    'version' => 1, // Versión 1 para DTE-14
                    'ambiente' => config('dte.ambiente', '00'),
                    'tipoDte' => '14',
                    'numeroControl' => $numeroControl,
                    'codigoGeneracion' => $codigoGeneracion,
                    'tipoModelo' => 1,
                    'tipoOperacion' => 1,
                    'tipoContingencia' => null,
                    'motivoContin' => null,
                    'fecEmi' => $fechaEmision,
                    'horEmi' => $horaEmision,
                    'tipoMoneda' => 'USD',
                ],
                'emisor' => [
                    'nit' => config('dte.emisor.nit'),
                    'nrc' => config('dte.emisor.nrc'),
                    'nombre' => config('dte.emisor.nombre'),
                    'codActividad' => config('dte.emisor.cod_actividad'),
                    'descActividad' => config('dte.emisor.desc_actividad'),
                    'codEstableMH' => null,
                    'codPuntoVentaMH' => null,
                    'direccion' => [
                        'departamento' => str_pad(config('dte.emisor.direccion.departamento'), 2, '0', STR_PAD_LEFT),
                        'municipio' => str_pad(config('dte.emisor.direccion.municipio'), 2, '0', STR_PAD_LEFT),
                        'complemento' => config('dte.emisor.direccion.complemento'),
                    ],
                    'telefono' => config('dte.emisor.telefono'),
                    'correo' => config('dte.emisor.correo'),
                    'codEstable' => null,
                    'codPuntoVenta' => null,
                ],
                'sujetoExcluido' => [
                    'tipoDocumento' => $datos['receptor']['tipo_documento'],
                    'numDocumento' => $numDocumentoFormateado,
                    'nombre' => $datos['receptor']['nombre'],
                    'codActividad' => !empty($datos['receptor']['cod_actividad']) ? $datos['receptor']['cod_actividad'] : null,
                    'descActividad' => !empty($datos['receptor']['desc_actividad']) && strlen($datos['receptor']['desc_actividad']) >= 5
                        ? $datos['receptor']['desc_actividad']
                        : null,
                    'direccion' => [
                        'departamento' => str_pad($datos['receptor']['departamento'], 2, '0', STR_PAD_LEFT),
                        'municipio' => str_pad($datos['receptor']['municipio'], 2, '0', STR_PAD_LEFT),
                        'complemento' => $datos['receptor']['complemento'],
                    ],
                    'telefono' => $datos['receptor']['telefono'] ?? null,
                    'correo' => $datos['receptor']['correo'] ?? null,
                ],
                'cuerpoDocumento' => $items,
                'resumen' => [
                    'totalCompra' => $totalCompra,
                    'descu' => $totalDescuento,
                    'totalDescu' => $totalDescuento,
                    'subTotal' => $totalCompra - $totalDescuento,
                    'ivaRete1' => 0,
                    'reteRenta' => 0,
                    'totalPagar' => $totalPagar,
                    'totalLetras' => $totalLetras,
                    'condicionOperacion' => (int) ($datos['condicion_operacion'] ?? 1),
                    'pagos' => [
                        [
                            'codigo' => $datos['forma_pago'] ?? '01',
                            'montoPago' => $totalPagar,
                            'referencia' => $datos['referencia_pago'] ?? null,
                            'plazo' => null,
                            'periodo' => null,
                        ]
                    ],
                    'observaciones' => $datos['observaciones'] ?? null,
                ],
                'apendice' => null,
            ],
            // Datos para BD
            'items' => $items,
            'totales' => [
                'total_no_sujeto' => 0.0,
                'total_exenta' => $totalCompra,
                'total_gravada' => 0.0,
                'sub_total_ventas' => $totalCompra,
                'total_descuento' => $totalDescuento,
                'sub_total' => $totalCompra,
                'total_iva' => 0.0, // SIEMPRE 0
                'monto_total_operacion' => $totalPagar,
                'total_pagar' => $totalPagar,
            ],
        ];

        return $resultado;
    }
}