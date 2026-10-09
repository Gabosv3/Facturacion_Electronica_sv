@extends('layouts.app')

@section('title', $primera->receptor_nombre . ' - Cliente')

@section('content')
@php
    $tipoLabel = ['01' => 'DTE-01', '03' => 'CCF-03', '05' => 'NC-05', '14' => 'FSE-14'];
    $tipoClase = [
        '01' => 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-200',
        '03' => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200',
        '05' => 'bg-violet-50 text-violet-700 ring-1 ring-inset ring-violet-200',
        '14' => 'bg-orange-50 text-orange-700 ring-1 ring-inset ring-orange-200',
    ];
@endphp
<div class="max-w-6xl mx-auto">

    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('clientes.index') }}" class="text-slate-400 hover:text-slate-600 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">{{ $primera->receptor_nombre }}</h1>
            <p class="text-sm text-slate-500 mt-0.5 font-mono">{{ $documento }}</p>
        </div>
    </div>

    <!-- KPIs del cliente -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-100 shadow-[0_1px_3px_0_rgb(0_0_0_/_0.06)] p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Total Consumido</p>
            <p class="text-2xl font-bold text-slate-800 mt-1.5">${{ number_format($totalConsumido, 2) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 shadow-[0_1px_3px_0_rgb(0_0_0_/_0.06)] p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Documentos Emitidos</p>
            <p class="text-2xl font-bold text-slate-800 mt-1.5">{{ $totalDocumentos }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 shadow-[0_1px_3px_0_rgb(0_0_0_/_0.06)] p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Contacto</p>
            <p class="text-sm text-slate-700 mt-1.5">
                @if($primera->receptor_telefono) 📞 {{ $primera->receptor_telefono }} <br> @endif
                @if($primera->receptor_correo) ✉️ {{ $primera->receptor_correo }} @endif
                @if(!$primera->receptor_telefono && !$primera->receptor_correo)
                    <span class="text-slate-400">Sin datos de contacto</span>
                @endif
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">
        <!-- Consumo por producto/servicio -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_1px_3px_0_rgb(0_0_0_/_0.06)] overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Productos/Servicios más consumidos</h2>
            </div>
            <div class="overflow-x-auto max-h-96 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 sticky top-0">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Producto/Servicio</th>
                            <th class="px-4 py-2.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">Cant.</th>
                            <th class="px-4 py-2.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">Monto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($productos as $producto)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-4 py-2.5 text-slate-700">
                                    {{ $producto['descripcion'] }}
                                    @if($producto['codigo'])
                                        <span class="block text-xs text-slate-400 font-mono">{{ $producto['codigo'] }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-right text-slate-600">{{ number_format($producto['cantidad_total'], 2) }}</td>
                                <td class="px-4 py-2.5 text-right font-semibold text-slate-800">${{ number_format($producto['monto_total'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-8 text-center text-slate-400">Sin productos registrados</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Historial de documentos -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_1px_3px_0_rgb(0_0_0_/_0.06)] overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Historial de Documentos</h2>
            </div>
            <div class="overflow-x-auto max-h-96 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 sticky top-0">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Tipo</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Fecha</th>
                            <th class="px-4 py-2.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">Total</th>
                            <th class="px-4 py-2.5 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide">Ver</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($facturas as $factura)
                            <tr class="hover:bg-slate-50/70 transition-colors cursor-pointer" onclick="window.location='{{ route('facturas.show', $factura) }}'">
                                <td class="px-4 py-2.5">
                                    <span class="px-2 py-0.5 {{ $tipoClase[$factura->tipo_dte] ?? 'bg-slate-100 text-slate-600' }} text-xs font-semibold rounded-full">
                                        {{ $tipoLabel[$factura->tipo_dte] ?? 'DTE-' . $factura->tipo_dte }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-slate-500">{{ $factura->fecha_emision->format('d/m/Y') }}</td>
                                <td class="px-4 py-2.5 text-right font-semibold text-slate-800">${{ number_format($factura->total_pagar, 2) }}</td>
                                <td class="px-4 py-2.5 text-center text-blue-600">
                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Detalle línea por línea de cada documento -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_1px_3px_0_rgb(0_0_0_/_0.06),0_4px_12px_-2px_rgb(0_0_0_/_0.06)] overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Detalle Completo por Documento</h2>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach($facturas as $factura)
                <details class="group">
                    <summary class="px-5 py-3.5 flex items-center justify-between cursor-pointer hover:bg-slate-50/70 transition-colors list-none">
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-0.5 {{ $tipoClase[$factura->tipo_dte] ?? 'bg-slate-100 text-slate-600' }} text-xs font-semibold rounded-full">
                                {{ $tipoLabel[$factura->tipo_dte] ?? 'DTE-' . $factura->tipo_dte }}
                            </span>
                            <span class="text-sm font-mono text-slate-600">{{ $factura->numero_control }}</span>
                            <span class="text-sm text-slate-400">{{ $factura->fecha_emision->format('d/m/Y') }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-semibold text-slate-800">${{ number_format($factura->total_pagar, 2) }}</span>
                            <svg class="w-4 h-4 text-slate-400 group-open:rotate-90 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </div>
                    </summary>
                    <div class="px-5 pb-4">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="text-slate-400 uppercase tracking-wide">
                                    <th class="text-left py-1.5">Descripción</th>
                                    <th class="text-right py-1.5">Cant.</th>
                                    <th class="text-right py-1.5">P. Unit.</th>
                                    <th class="text-right py-1.5">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @foreach($factura->items as $item)
                                    <tr>
                                        <td class="py-1.5 text-slate-700">{{ $item->descripcion }}</td>
                                        <td class="py-1.5 text-right text-slate-500">{{ number_format($item->cantidad, 2) }}</td>
                                        <td class="py-1.5 text-right text-slate-500">${{ number_format($item->precio_unitario, 2) }}</td>
                                        <td class="py-1.5 text-right font-medium text-slate-700">
                                            @if($factura->tipo_dte === '01' || $factura->tipo_dte === '14')
                                                ${{ number_format(($item->cantidad * $item->precio_unitario) - $item->monto_descuento, 2) }}
                                            @else
                                                ${{ number_format($item->venta_gravada, 2) }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @endforeach
        </div>
    </div>
</div>
@endsection
