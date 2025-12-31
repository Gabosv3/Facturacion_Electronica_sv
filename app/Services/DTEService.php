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
        $totalSinDescuento = 0;  // AGREGADO: Total antes de descuentos
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

                // CRÍTICO: MH valida que ventaGravada = (cantidad × precioUni) - montoDescu
                // Si hay descuento por ítem, SE RESTA de ventaGravada
                $totalItemSinDescuento = $cantidad * $precioUniSinIva;
                $ventaGravada = round($totalItemSinDescuento - $montoDescu, 2);

                $totalSinDescuento += $totalItemSinDescuento;

                // El precio unitario para el JSON debe ser SIN IVA
                $item['precio_unitario'] = $precioUniSinIva;
                $item['precio_incluye_iva'] = true;

                \Log::info('💰 Item con precio incluye IVA', [
                    'precio_ingresado_con_iva' => $precioUniIngresado,
                    'precio_sin_iva_calculado' => $precioUniSinIva,
                    'cantidad' => $cantidad,
                    'descuento' => $montoDescu,
                    'venta_gravada' => $ventaGravada,
                ]);
            } else {
                // Si el precio NO incluye IVA, usar directamente
                $totalItemSinDescuento = $cantidad * $precioUniIngresado;
                // CRÍTICO: MH valida que ventaGravada = (cantidad × precioUni) - montoDescu
                $ventaGravada = round($totalItemSinDescuento - $montoDescu, 2);

                $totalSinDescuento += $totalItemSinDescuento;

                $item['precio_unitario'] = $precioUniIngresado;
                $item['precio_incluye_iva'] = false;
            }

            // IVA calculado sobre ventaGravada (que ya tiene descuento restado)
            $ivaItem = round($ventaGravada * $tasaIva, 2);

            $item['venta_gravada'] = $ventaGravada;
            $item['venta_exenta'] = 0;
            $item['venta_no_sujeta'] = 0;
            $item['iva_item'] = $ivaItem;

            $totalGravada += $ventaGravada;
            $totalDescuento += $montoDescu;
            $totalIva += $ivaItem;
        }

        // subTotalVentas = suma sin descuento
        // subTotal = suma con descuento aplicado
        $subTotalVentas = round($totalGravada + $totalExenta + $totalNoSujeto, 2);
        $subTotal = round($subTotalVentas - $totalDescuento, 2);
        $totalPagar = round($subTotal, 2);

        return [
            'items' => $items,
            'totales' => [
                'total_gravada' => round($totalGravada, 2),
                'total_exenta' => round($totalExenta, 2),
                'total_no_sujeto' => round($totalNoSujeto, 2),
                'sub_total_ventas' => $subTotalVentas,  // Total ANTES de descuentos
                'total_descuento' => round($totalDescuento, 2),
                'sub_total' => $subTotal,  // subTotalVentas - totalDescuento
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
        // Separar lógica según tipo de DTE para evitar conflictos
        // DTE-03 (CCF) y DTE-05 (Nota de Crédito) deben desglosar IVA
        if ($tipoDte === '03' || $tipoDte === '05') {
            return $this->calcularTotalesCCF($items, $tasaIva, $precioIncluyeIva);
        }

        // Lógica original para DTE-01 y otros
        return $this->calcularTotalesFactura($items, $tasaIva, $precioIncluyeIva, $tipoDte);
    }

    /**
     * Calcular totales para DTE-01 (Factura) y DTE-14 (Sujeto Excluido)
     */
    private function calcularTotalesFactura(array $items, float $tasaIva, bool $precioIncluyeIva, string $tipoDte): array
    {
        // Si los precios NO incluyen IVA, los convertimos a precios CON IVA
        // porque DTE-01 requiere reportar precios y montos con IVA incluido.
        if (!$precioIncluyeIva) {
            foreach ($items as &$item) {
                $precioSinIva = (float) $item['precio_unitario'];
                // El descuento SIEMPRE viene con IVA incluido, no se debe convertir
                // $descuento = (float) ($item['monto_descuento'] ?? 0);
                
                // Convertir precio unitario a con IVA
                $item['precio_unitario'] = round($precioSinIva * (1 + $tasaIva), 2);
                
                // El descuento se mantiene igual porque ya incluye IVA
            }
            // Ahora procesamos como si incluyera IVA
            $precioIncluyeIva = true;
        }

        $totalDescuento = 0;

        if ($precioIncluyeIva) {
            $totalConIva = 0;
            $totalSinDescuento = 0;
            $sumaVentaGravada = 0;
            $sumaIvaItems = 0;
            $numItems = count($items);
            $divisor = 1 + $tasaIva;

            // Primer loop: calcular totales globales
            foreach ($items as $item) {
                $cantidad = (float) $item['cantidad'];
                $precioUniIngresado = (float) $item['precio_unitario'];
                $montoDescu = (float) ($item['monto_descuento'] ?? 0);

                $totalItemSinDescuento = $cantidad * $precioUniIngresado;
                $totalItemConIva = $totalItemSinDescuento - $montoDescu;

                $totalSinDescuento += $totalItemSinDescuento;
                $totalConIva += $totalItemConIva;
                $totalDescuento += $montoDescu;
            }

            // Segundo loop: procesar cada item
            foreach ($items as &$item) {
                $cantidad = (float) $item['cantidad'];
                $precioUniIngresado = (float) $item['precio_unitario'];
                $montoDescu = (float) ($item['monto_descuento'] ?? 0);

                // DTE-01: ventaGravada CON IVA, RESTANDO descuento
                $ventaGravada = round(($cantidad * $precioUniIngresado) - $montoDescu, 2);
                $precioUniParaJson = $precioUniIngresado; // CON IVA

                // Acumular
                $sumaVentaGravada += $ventaGravada;

                // IVA del item (solo informativo, no se envía en DTE-01)
                $baseImponible = round($ventaGravada / $divisor, 2);
                $ivaItem = round($ventaGravada - $baseImponible, 2);
                $sumaIvaItems += $ivaItem;

                $item['precio_unitario'] = floatval($precioUniParaJson);
                $item['venta_gravada'] = floatval($ventaGravada);
                $item['venta_exenta'] = 0.0;
                $item['venta_no_sujeta'] = 0.0;
                $item['iva_item'] = floatval($ivaItem);
                
                if (!isset($item['tipo_item'])) {
                    $item['tipo_item'] = 1;
                }
            }

            // Ajuste de redondeo para DTE-01
            // El objetivo es que la suma de ventaGravada sea igual al totalConIva calculado
            $totalVentaGravadaTarget = $totalConIva;
            $diferenciaBrutaGravada = $totalVentaGravadaTarget - round($sumaVentaGravada, 2);
            
            if ($numItems > 0 && abs($diferenciaBrutaGravada) > 0.001) {
                $items[$numItems - 1]['venta_gravada'] += $diferenciaBrutaGravada;
                // Recalcular IVA del último item ajustado
                $ventaGravadaUltimo = $items[$numItems - 1]['venta_gravada'];
                $baseImponibleUltimo = round($ventaGravadaUltimo / $divisor, 2);
                $items[$numItems - 1]['iva_item'] = round($ventaGravadaUltimo - $baseImponibleUltimo, 2);
            }

            // Totales finales
            $totalGravada = $totalVentaGravadaTarget;
            
            // Calcular IVA total teórico para registros internos
            $baseImponibleGlobal = round($totalGravada / $divisor, 2);
            $totalIva = round($totalGravada - $baseImponibleGlobal, 2);

            return [
                'items' => $items,
                'totales' => [
                    'total_gravada' => $totalGravada,
                    'total_exenta' => 0.0,
                    'total_no_sujeto' => 0.0,
                    'sub_total_ventas' => $totalGravada,
                    'total_descuento' => 0.0, // Descuento ya aplicado en línea
                    'sub_total' => $totalGravada,
                    'total_iva' => $totalIva,
                    'monto_total_operacion' => $totalGravada,
                    'total_pagar' => $totalGravada,
                ]
            ];
        }

        return [];
    }

    /**
     * Calcular totales para DTE-03 (Comprobante de Crédito Fiscal)
     */
    private function calcularTotalesCCF(array $items, float $tasaIva, bool $precioIncluyeIva): array
    {
        $totalDescuento = 0;

        if ($precioIncluyeIva) {
            $totalConIva = 0;
            $sumaVentaGravada = 0;
            $sumaIvaItems = 0;
            $numItems = count($items);
            $divisor = 1 + $tasaIva;

            // Primer loop: calcular totales globales con IVA para referencia
            foreach ($items as $item) {
                $cantidad = (float) $item['cantidad'];
                $precioUniIngresado = (float) $item['precio_unitario'];
                $montoDescu = (float) ($item['monto_descuento'] ?? 0);
                
                $totalItemConIva = ($cantidad * $precioUniIngresado) - $montoDescu;
                $totalConIva += $totalItemConIva;
            }

            // Segundo loop: procesar cada item convirtiendo a SIN IVA
            foreach ($items as &$item) {
                $cantidad = (float) $item['cantidad'];
                $precioUniIngresado = (float) $item['precio_unitario'];
                $montoDescu = (float) ($item['monto_descuento'] ?? 0);

                // Convertir a valores SIN IVA
                $precioUniSinIva = round($precioUniIngresado / $divisor, 2);
                $montoDescuSinIva = round($montoDescu / $divisor, 2);

                // Calcular venta gravada: (cantidad * precioSinIva) - descuentoSinIva
                $ventaGravada = round(($cantidad * $precioUniSinIva) - $montoDescuSinIva, 2);

                // Calcular IVA del item
                $ivaItem = round($ventaGravada * $tasaIva, 2);

                // Acumular
                $sumaVentaGravada += $ventaGravada;
                $sumaIvaItems += $ivaItem;

                // Asignar valores al item
                $item['precio_unitario'] = floatval($precioUniSinIva);
                $item['venta_gravada'] = floatval($ventaGravada);
                $item['monto_descuento'] = floatval($montoDescuSinIva); // IMPORTANTE: Actualizar descuento en el item
                $item['venta_exenta'] = 0.0;
                $item['venta_no_sujeta'] = 0.0;
                $item['iva_item'] = floatval($ivaItem);

                if (!isset($item['tipo_item'])) {
                    $item['tipo_item'] = 1;
                }
            }

            // Ajuste de redondeo para DTE-03
            // El objetivo es que la base imponible sea consistente con el total pagado esperado
            $baseImponibleTarget = round($totalConIva / $divisor, 2);
            $diferenciaBrutaGravada = $baseImponibleTarget - round($sumaVentaGravada, 2);

            if ($numItems > 0 && abs($diferenciaBrutaGravada) > 0.001) {
                $items[$numItems - 1]['venta_gravada'] += $diferenciaBrutaGravada;
                // Recalcular IVA del último item
                $items[$numItems - 1]['iva_item'] = round($items[$numItems - 1]['venta_gravada'] * $tasaIva, 2);
                $sumaIvaItems = 0; // Recalcular suma de IVA
                foreach ($items as $it) {
                    $sumaIvaItems += $it['iva_item'];
                }
            }

            $totalGravada = $baseImponibleTarget;
            $totalIva = $sumaIvaItems; // Suma de IVAs individuales ajustados
            
            // Recalcular total a pagar para asegurar precisión
            $totalPagar = round($totalGravada + $totalIva, 2);

            return [
                'items' => $items,
                'totales' => [
                    'total_gravada' => $totalGravada,
                    'total_exenta' => 0.0,
                    'total_no_sujeto' => 0.0,
                    'sub_total_ventas' => $totalGravada,
                    'total_descuento' => 0.0, // Descuento ya aplicado en línea
                    'sub_total' => $totalGravada,
                    'total_iva' => $totalIva,
                    'monto_total_operacion' => $totalPagar,
                    'total_pagar' => $totalPagar,
                ]
            ];
        } else {
            return $this->calcularTotalesGenericoSinIva($items, $tasaIva);
        }
    }

    /**
     * Lógica genérica para precios SIN IVA (usada por ambos si aplica)
     */
    private function calcularTotalesGenericoSinIva(array $items, float $tasaIva): array
    {
        $totalGravada = 0;
        $totalDescuento = 0;
        $totalIva = 0;
        $sumaVentaGravadaItems = 0;
        $numItems = count($items);
        $itemIndex = 0;

        // Primer loop: calcular totales
        foreach ($items as $item) {
            $cantidad = (float) $item['cantidad'];
            $precioUniIngresado = (float) $item['precio_unitario'];
            $montoDescu = (float) ($item['monto_descuento'] ?? 0);

            $ventaGravada = round(($cantidad * $precioUniIngresado) - $montoDescu, 2);
            $totalGravada += $ventaGravada;
            $totalDescuento += $montoDescu;
        }
        
        $totalIva = round($totalGravada * $tasaIva, 2);
        $totalPagar = round($totalGravada + $totalIva, 2);

        // Segundo loop: asignar items
        foreach ($items as &$item) {
            $cantidad = (float) $item['cantidad'];
            $precioUniIngresado = (float) $item['precio_unitario'];
            $montoDescu = (float) ($item['monto_descuento'] ?? 0);

            $ventaGravada = round(($cantidad * $precioUniIngresado) - $montoDescu, 2);
            
            // Ajuste último item
            $itemIndex++;
            if ($itemIndex === $numItems) {
                $ventaGravada = round($totalGravada - $sumaVentaGravadaItems, 2);
            } else {
                $sumaVentaGravadaItems += $ventaGravada;
            }

            $ivaItem = round($ventaGravada * $tasaIva, 2);

            $item['venta_gravada'] = $ventaGravada;
            $item['venta_exenta'] = 0;
            $item['venta_no_sujeta'] = 0;
            $item['iva_item'] = $ivaItem;
            
            if (!isset($item['tipo_item'])) {
                $item['tipo_item'] = 1;
            }
        }

        return [
            'items' => $items,
            'totales' => [
                'total_gravada' => $totalGravada,
                'total_exenta' => 0.0,
                'total_no_sujeto' => 0.0,
                'sub_total_ventas' => $totalGravada,
                'total_descuento' => $totalDescuento,
                'sub_total' => $totalGravada, // subTotalVentas - totalDescuento (pero aquí descuento ya está restado en ventaGravada?? No, espera)
                // Si descuento está en línea, subTotalVentas = totalGravada.
                // Si descuento es global, es diferente. Asumimos descuento en línea.
                'total_iva' => $totalIva,
                'monto_total_operacion' => $totalPagar,
                'total_pagar' => $totalPagar,
            ]
        ];
    }

    /**
     * Calcular totales de items con tasa de IVA personalizada (LEGACY - MANTENIDO POR COMPATIBILIDAD SI ALGUIEN LO LLAMA DIRECTO)
     * Pero ahora redirige a los nuevos métodos.
     */
    /*
    public function calcularTotalesItemsConTasa_OLD(array $items, float $tasaIva, bool $precioIncluyeIva = true, string $tipoDte = '01'): array
    {
        // ... (código anterior)
    }
    */

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
                        'cantidad' => round(floatval($item['cantidad']), 2),
                        'codigo' => $item['codigo'],
                        'codTributo' => null,
                        'uniMedida' => (int) ($item['uni_medida'] ?? 59),
                        'descripcion' => $item['descripcion'],
                        'precioUni' => round(floatval($item['precio_unitario']), 2),
                        'montoDescu' => round(floatval($item['monto_descuento'] ?? 0), 2),
                        'ventaNoSuj' => round(floatval($item['venta_no_sujeta']), 2),
                        'ventaExenta' => round(floatval($item['venta_exenta']), 2),
                        'ventaGravada' => round(floatval($item['venta_gravada']), 2),
                        'tributos' => null,
                        'psv' => 0,
                        'noGravado' => 0.0,
                        'ivaItem' => round(floatval($item['iva_item']), 2),
                    ];
                }, $calculos['items'], array_keys($calculos['items'])),
                'resumen' => [
                    'totalNoSuj' => round(floatval($calculos['totales']['total_no_sujeto']), 2),
                    'totalExenta' => round(floatval($calculos['totales']['total_exenta']), 2),
                    'totalGravada' => round(floatval($calculos['totales']['total_gravada']), 2),
                    'subTotalVentas' => round(floatval($calculos['totales']['sub_total_ventas']), 2),
                    'descuNoSuj' => 0.0,
                    'descuExenta' => 0.0,
                    'descuGravada' => round(floatval($calculos['totales']['total_descuento']), 2),
                    'porcentajeDescuento' => 0.0,
                    'totalDescu' => round(floatval($calculos['totales']['total_descuento']), 2),
                    'tributos' => null,
                    'subTotal' => round($calculos['totales']['sub_total'], 2),
                    // Los totales ya vienen correctamente calculados desde calcularTotalesItemsConTasa
                    'totalIva' => round($calculos['totales']['total_iva'], 2),
                    'ivaRete1' => 0.0,
                    'reteRenta' => 0.0,
                    'montoTotalOperacion' => round($calculos['totales']['monto_total_operacion'], 2),
                    'totalNoGravado' => 0.0,
                    'saldoFavor' => 0.0,
                    'totalPagar' => round($calculos['totales']['total_pagar'], 2),
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
        \Log::info('🔍 CONSTRUIR DTE-03 (CCF) - INICIANDO CÁLCULOS', [
            'precio_incluye_iva' => $precioIncluyeIva,
            'items_recibidos' => count($datos['items']),
            'primer_item_detalle' => $datos['items'][0] ?? null,
        ]);

        $calculos = $this->calcularTotalesItemsConTasa($datos['items'], 0.13, $precioIncluyeIva, '03');

        \Log::info('🔍 CÁLCULOS COMPLETADOS', [
            'ventaGravada_items' => array_map(fn($item) => [
                'codigo' => $item['codigo'],
                'venta_gravada' => $item['venta_gravada'] ?? 'NO CALCULADA',
            ], $calculos['items']),
            'totales' => $calculos['totales'],
        ]);

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
                    // IMPORTANTE: Para CCF, los valores en $item ya vienen SIN IVA desde calcularTotalesItemsConTasa
                    
                    $cantidad = floatval($item['cantidad']);
                    $montoDescu = floatval($item['monto_descuento'] ?? 0);
                    $ventaGravada = floatval($item['venta_gravada']);
                    
                    // Recalcular precio unitario con alta precisión para asegurar que cuadre con la venta gravada
                    // Fórmula: precio = (ventaGravada + montoDescu) / cantidad
                    if ($cantidad > 0) {
                        $precioUniCalculado = ($ventaGravada + $montoDescu) / $cantidad;
                    } else {
                        $precioUniCalculado = floatval($item['precio_unitario']);
                    }

                    // Validar: ventaGravada debe cumplir: (cantidad × precioUni) - montoDescu
                    // Usamos el precio calculado para la validación
                    $ventaGravadaValidacion = round(($cantidad * $precioUniCalculado) - $montoDescu, 2);
                    
                    \Log::debug('📦 ITEM PARA JSON CCF (Recalculado)', [
                        'numItem' => $index + 1,
                        'cantidad' => $cantidad,
                        'precioUni_original' => floatval($item['precio_unitario']),
                        'precioUni_recalculado' => $precioUniCalculado,
                        'montoDescu' => $montoDescu,
                        'ventaGravada_en_item' => $ventaGravada,
                        'ventaGravada_validacion' => $ventaGravadaValidacion,
                        'coinciden' => abs($ventaGravadaValidacion - $ventaGravada) < 0.001,
                    ]);
                    
                    return [
                        'numItem' => $index + 1,
                        'tipoItem' => intval($item['tipo_item'] ?? 1),
                        'numeroDocumento' => null,
                        'cantidad' => round($cantidad, 2),
                        'codigo' => $item['codigo'],
                        'codTributo' => null,
                        'uniMedida' => (int) ($item['uni_medida'] ?? 59),
                        'descripcion' => $item['descripcion'],
                        // Enviamos precio con hasta 8 decimales para precisión
                        'precioUni' => round($precioUniCalculado, 8),
                        'montoDescu' => round($montoDescu, 2),
                        'ventaNoSuj' => 0.0,
                        'ventaExenta' => 0.0,
                        'ventaGravada' => round($ventaGravada, 2),
                        'tributos' => ['20'],
                        'psv' => 0.0,
                        'noGravado' => 0.0,
                    ];
                }, $calculos['items'], array_keys($calculos['items'])),
                'resumen' => [
                    'totalNoSuj' => 0.0,
                    'totalExenta' => 0.0,
                    'totalGravada' => floatval($calculos['totales']['total_gravada']),
                    'subTotalVentas' => floatval($calculos['totales']['total_gravada']),
                    'descuNoSuj' => 0.0,
                    'descuExenta' => 0.0,
                    'descuGravada' => round(floatval($calculos['totales']['total_descuento']), 2),
                    'porcentajeDescuento' => 0.0,
                    'totalDescu' => round(floatval($calculos['totales']['total_descuento']), 2),
                    'tributos' => [  // En CCF el IVA total va en tributos
                        [
                            'codigo' => '20',
                            'descripcion' => 'Impuesto al Valor Agregado 13%',
                            'valor' => round($totalIva, 2),
                        ]
                    ],
                    'subTotal' => round($calculos['totales']['total_gravada'], 2),
                    'totalIva' => round($totalIva, 2),  // Guardamos para BD (aunque no se envía al MH en este campo)
                    'ivaRete1' => 0.0,  // Requerido en CCF
                    'ivaPerci1' => 0.0,  // Requerido en CCF
                    'reteRenta' => 0.0,  // Requerido en CCF
                    'montoTotalOperacion' => round($totalPagar, 2),
                    'totalNoGravado' => 0.0,
                    'saldoFavor' => 0.0,
                    'totalPagar' => round($totalPagar, 2),
                    'totalLetras' => $totalLetras,
                    'condicionOperacion' => (int) ($datos['condicion_operacion'] ?? 1),
                    'numPagoElectronico' => '1',
                    'pagos' => [
                        [
                            'codigo' => $datos['forma_pago'] ?? '01',
                            'montoPago' => round($totalPagar, 2),
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
        $totalPagar = round($calculos['totales']['total_gravada'] + $totalIva, 2);
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

        // Determinar tipo de documento relacionado
        // IMPORTANTE: Según catálogo CAT-002 del MH, el tipoDocumento en documentoRelacionado
        // debe usar EL MISMO CÓDIGO que el DTE original que se está referenciando:
        //   - Cuando se referencia DTE-01 (Factura) → usar código '01'
        //   - Cuando se referencia DTE-03 (CCF) → usar código '03'
        //   - Cuando se referencia DTE-14 (Sujeto Excluido) → usar código '14'
        // No se debe hacer ninguna conversión, usar el código tal cual
        $tipoDocumentoRelacionado = $tipoDocRelacionado;

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
                        'tipoDocumento' => $tipoDocumentoRelacionado, // CAT-002: 01=Factura, 03=CCF
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
                            'cantidad' => round(floatval($item['cantidad']), 2),
                            'codigo' => $item['codigo'],
                            'codTributo' => null,  // Requerido por MH
                            'uniMedida' => (int) ($item['uni_medida'] ?? 59),
                            'descripcion' => $descripcion,
                            'precioUni' => round($precioUnitarioFinal, 2),  // Precio sin IVA
                            'montoDescu' => round(floatval($item['monto_descuento'] ?? 0), 2),
                            'ventaNoSuj' => 0.0,  // Requerido por MH
                            'ventaExenta' => 0.0,  // Requerido por MH
                            'ventaGravada' => round(floatval($item['venta_gravada']), 2),  // Base sin IVA
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
                    'totalNoSuj' => round(floatval($calculos['totales']['total_no_sujeto']), 2),
                    'totalExenta' => round(floatval($calculos['totales']['total_exenta']), 2),
                    'totalGravada' => round(floatval($calculos['totales']['total_gravada']), 2),
                    'subTotalVentas' => round(floatval($calculos['totales']['sub_total_ventas']), 2),
                    'descuNoSuj' => 0.0,
                    'descuExenta' => 0.0,
                    'descuGravada' => 0.0, // Descuento ya aplicado en línea para evitar doble resta en validación MH
                    'totalDescu' => 0.0, // Descuento ya aplicado en línea
                    'tributos' => [  // REQUERIDO por esquema DTE-05 (igual que CCF)
                        [
                            'codigo' => '20',
                            'descripcion' => 'IVA',
                            'valor' => round($totalIva, 2),
                        ]
                    ],
                    'subTotal' => round($calculos['totales']['sub_total_ventas'], 2),
                    'ivaRete1' => 0.0,
                    'ivaPerci1' => 0.0, // REQUERIDO por esquema DTE-05
                    'reteRenta' => 0.0, // REQUERIDO por esquema DTE-05
                    'montoTotalOperacion' => round($totalPagar, 2),
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
                'cantidad' => round($cantidad, 2),
                'codigo' => $item['codigo'] ?? null,
                'uniMedida' => (int) ($item['uni_medida'] ?? 59),
                'descripcion' => $item['descripcion'],
                'precioUni' => round($precioUnitario, 2),
                'montoDescu' => $montoDescu,
                'compra' => $compra, // Campo requerido para DTE-14
            ];
        }

        // CÁLCULO DE RETENCIÓN (10% sobre el total de la compra)
        // En Sujeto Excluido, el emisor retiene el 10% de Renta
        $reteRenta = round($totalCompra * 0.10, 2);

        // Total a pagar es la compra menos la retención
        $totalPagar = round($totalCompra - $reteRenta, 2);
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
                    'totalCompra' => round($totalCompra, 2),
                    'descu' => round($totalDescuento, 2),
                    'totalDescu' => round($totalDescuento, 2),
                    'subTotal' => round($totalCompra, 2), // totalCompra ya es neto
                    'ivaRete1' => 0,
                    'reteRenta' => $reteRenta, // Retención del 10%
                    'totalPagar' => round($totalPagar, 2),
                    'totalLetras' => $totalLetras,
                    'condicionOperacion' => (int) ($datos['condicion_operacion'] ?? 1),
                    'pagos' => [
                        [
                            'codigo' => $datos['forma_pago'] ?? '01',
                            'montoPago' => round($totalPagar, 2),
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
                'retencion_renta' => $reteRenta,
                'monto_total_operacion' => $totalPagar,
                'total_pagar' => $totalPagar,
            ],
        ];

        return $resultado;
    }
}