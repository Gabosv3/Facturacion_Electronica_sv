<?php

namespace App\Providers;

use App\Models\Cotizacion;
use App\Models\Factura;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Si la factura se generó a partir de una cotización (ver
        // CotizacionController::convertirAFactura), marcarla como convertida
        // y enlazarla, sin acoplar esta lógica al controlador de facturas.
        Factura::created(function (Factura $factura) {
            $prefill = Session::get('cotizacion_prefill');

            if (!$prefill || empty($prefill['cotizacion_id'])) {
                return;
            }

            $cotizacion = Cotizacion::find($prefill['cotizacion_id']);
            if ($cotizacion && $cotizacion->estado !== 'convertida') {
                $cotizacion->update([
                    'estado' => 'convertida',
                    'factura_id' => $factura->id,
                ]);
            }

            Session::forget('cotizacion_prefill');
        });
    }
}
