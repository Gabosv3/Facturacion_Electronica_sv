<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class CotizacionController extends Controller
{
    public function index(Request $request)
    {
        $query = Cotizacion::query();

        if ($request->filled('buscar')) {
            $buscar = $request->input('buscar');
            $query->where(function ($q) use ($buscar) {
                $q->where('cliente_nombre', 'LIKE', "%{$buscar}%")
                  ->orWhere('numero_cotizacion', 'LIKE', "%{$buscar}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        $cotizaciones = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('cotizaciones.index', compact('cotizaciones'));
    }

    public function create()
    {
        return view('cotizaciones.crear');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cliente_nombre' => 'required|string|max:200',
            'cliente_documento' => 'nullable|string|max:20',
            'cliente_telefono' => 'nullable|string|max:20',
            'cliente_correo' => 'nullable|email|max:100',
            'observaciones' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.descripcion' => 'required|string|max:255',
            'items.*.cantidad' => 'required|numeric|min:0.01',
            'items.*.precio_unitario' => 'required|numeric|min:0',
            'items.*.monto_descuento' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $subtotal = 0;
            $totalDescuento = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += $item['cantidad'] * $item['precio_unitario'];
                $totalDescuento += $item['monto_descuento'] ?? 0;
            }
            $total = $subtotal - $totalDescuento;

            $ultimoNumero = Cotizacion::max('id') + 1;
            $numeroCotizacion = 'COT-' . str_pad($ultimoNumero, 6, '0', STR_PAD_LEFT);

            $cotizacion = Cotizacion::create([
                'numero_cotizacion' => $numeroCotizacion,
                'fecha' => now(),
                'cliente_nombre' => strtoupper($validated['cliente_nombre']),
                'cliente_documento' => $validated['cliente_documento'] ?? null,
                'cliente_telefono' => $validated['cliente_telefono'] ?? null,
                'cliente_correo' => $validated['cliente_correo'] ?? null,
                'subtotal' => $subtotal,
                'total_descuento' => $totalDescuento,
                'total_iva' => 0,
                'total' => $total,
                'observaciones' => $validated['observaciones'] ?? null,
                'estado' => 'pendiente',
            ]);

            foreach ($validated['items'] as $index => $item) {
                $cotizacion->items()->create([
                    'num_item' => $index + 1,
                    'codigo' => $item['codigo'] ?? null,
                    'descripcion' => $item['descripcion'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'monto_descuento' => $item['monto_descuento'] ?? 0,
                ]);
            }

            DB::commit();

            return redirect()->route('cotizaciones.show', $cotizacion->id)
                ->with('success', 'Cotización creada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error al crear la cotización: ' . $e->getMessage());
        }
    }

    public function show(Cotizacion $cotizacion)
    {
        $cotizacion->load('items', 'factura');
        return view('cotizaciones.detalle', compact('cotizacion'));
    }

    public function cambiarEstado(Request $request, Cotizacion $cotizacion)
    {
        $request->validate(['estado' => 'required|in:pendiente,aprobada,rechazada']);

        if ($cotizacion->estado === 'convertida') {
            return back()->with('error', 'Esta cotización ya fue convertida en factura, no se puede cambiar su estado.');
        }

        $cotizacion->update(['estado' => $request->input('estado')]);

        return back()->with('success', 'Estado actualizado a "' . $cotizacion->estado_nombre . '".');
    }

    public function destroy(Cotizacion $cotizacion)
    {
        if ($cotizacion->estado === 'convertida') {
            return back()->with('error', 'No se puede eliminar una cotización ya convertida en factura.');
        }

        $cotizacion->delete();

        return redirect()->route('cotizaciones.index')->with('success', 'Cotización eliminada.');
    }

    /**
     * Envía los datos de la cotización a sesión y redirige al formulario de
     * Factura, donde se precargan cliente e ítems para no reescribirlos.
     */
    public function convertirAFactura(Cotizacion $cotizacion)
    {
        if ($cotizacion->estado === 'convertida') {
            return back()->with('error', 'Esta cotización ya fue convertida.');
        }

        $cotizacion->load('items');

        Session::put('cotizacion_prefill', [
            'cotizacion_id' => $cotizacion->id,
            'cliente_nombre' => $cotizacion->cliente_nombre,
            'cliente_documento' => $cotizacion->cliente_documento,
            'cliente_telefono' => $cotizacion->cliente_telefono,
            'cliente_correo' => $cotizacion->cliente_correo,
            'items' => $cotizacion->items->map(fn($item) => [
                'codigo' => $item->codigo,
                'descripcion' => $item->descripcion,
                'cantidad' => (float) $item->cantidad,
                'precio_unitario' => (float) $item->precio_unitario,
                'monto_descuento' => (float) $item->monto_descuento,
            ])->values(),
        ]);

        return redirect()->route('facturas.create')
            ->with('success', 'Datos de la cotización ' . $cotizacion->numero_cotizacion . ' cargados. Completa los datos fiscales que falten y genera la factura.');
    }
}
