<?php

namespace App\Http\Controllers;

use App\Models\EventoContingencia;
use App\Models\EventoInvalidacion;
use App\Models\Factura;
use App\Models\TokenMH;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $inicioMes = now()->startOfMonth();
        $finMes = now()->endOfMonth();

        // Ventas del mes (solo documentos aceptados por el MH)
        $facturasDelMes = Factura::whereBetween('fecha_emision', [$inicioMes, $finMes])
            ->whereIn('estado', ['enviado_mh', 'firmado', 'firmado_contingencia']);

        $totalVentasMes = (clone $facturasDelMes)->sum('total_pagar');
        $cantidadFacturasMes = (clone $facturasDelMes)->count();

        // Comparativo con el mes anterior
        $inicioMesAnterior = now()->subMonthNoOverflow()->startOfMonth();
        $finMesAnterior = now()->subMonthNoOverflow()->endOfMonth();
        $totalVentasMesAnterior = Factura::whereBetween('fecha_emision', [$inicioMesAnterior, $finMesAnterior])
            ->whereIn('estado', ['enviado_mh', 'firmado', 'firmado_contingencia'])
            ->sum('total_pagar');

        $variacionVentas = $totalVentasMesAnterior > 0
            ? round((($totalVentasMes - $totalVentasMesAnterior) / $totalVentasMesAnterior) * 100, 1)
            : null;

        // Documentos por tipo (histórico total)
        $porTipo = Factura::selectRaw('tipo_dte, count(*) as total')
            ->groupBy('tipo_dte')
            ->pluck('total', 'tipo_dte');

        // Documentos por estado (histórico total)
        $porEstado = Factura::selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $totalFacturas = Factura::count();
        $totalVentasHistorico = Factura::whereIn('estado', ['enviado_mh', 'firmado', 'firmado_contingencia'])->sum('total_pagar');

        // Documentos que requieren atención
        $facturasConError = Factura::whereIn('estado', ['error_firma', 'firmado_sin_enviar', 'rechazado'])->count();

        // Invalidaciones recientes
        $invalidacionesPendientes = EventoInvalidacion::whereIn('estado', ['pendiente', 'firmado', 'error'])->count();
        $invalidacionesProcesadas = EventoInvalidacion::where('estado', 'procesado')->count();

        // Contingencia activa
        $contingenciaActiva = EventoContingencia::where('estado', 'activo')->first();

        // Estado del token MH
        $tokenActivo = TokenMH::where('is_active', true)->latest('expires_at')->first();

        // Últimas facturas
        $ultimasFacturas = Factura::orderBy('created_at', 'desc')->limit(8)->get();

        // Ventas de los últimos 7 días (para el mini-gráfico)
        $ventasUltimos7Dias = collect(range(6, 0))->map(function ($diasAtras) {
            $fecha = now()->subDays($diasAtras);
            $total = Factura::whereDate('fecha_emision', $fecha->toDateString())
                ->whereIn('estado', ['enviado_mh', 'firmado', 'firmado_contingencia'])
                ->sum('total_pagar');
            return [
                'fecha' => $fecha->format('d/m'),
                'total' => (float) $total,
            ];
        });

        // Comparativo de ventas de los últimos 12 meses
        $ventasUltimosMeses = collect(range(11, 0))->map(function ($mesesAtras) {
            $fecha = now()->subMonthsNoOverflow($mesesAtras);
            $total = Factura::whereMonth('fecha_emision', $fecha->month)
                ->whereYear('fecha_emision', $fecha->year)
                ->whereIn('estado', ['enviado_mh', 'firmado', 'firmado_contingencia'])
                ->sum('total_pagar');
            return [
                'mes' => ucfirst($fecha->translatedFormat('M')) . ' ' . $fecha->format('y'),
                'total' => (float) $total,
                'esMesActual' => $fecha->isSameMonth(now()) && $fecha->isSameYear(now()),
            ];
        });

        return view('home.index', compact(
            'totalVentasMes',
            'cantidadFacturasMes',
            'variacionVentas',
            'porTipo',
            'porEstado',
            'totalFacturas',
            'totalVentasHistorico',
            'facturasConError',
            'invalidacionesPendientes',
            'invalidacionesProcesadas',
            'contingenciaActiva',
            'tokenActivo',
            'ultimasFacturas',
            'ventasUltimos7Dias',
            'ventasUltimosMeses'
        ));
    }
}
