@extends('layouts.app')

@section('title', $cotizacion->numero_cotizacion . ' - Cotización')

@section('content')
@php
    $estadoClase = [
        'pendiente' => 'bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200',
        'aprobada' => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200',
        'rechazada' => 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-200',
        'convertida' => 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-200',
    ];
@endphp
<div class="max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('cotizaciones.index') }}" class="text-slate-400 hover:text-slate-600 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight font-mono">{{ $cotizacion->numero_cotizacion }}</h1>
            <p class="text-sm text-slate-500 mt-0.5">{{ $cotizacion->fecha->format('d/m/Y') }}</p>
        </div>
        <span class="ml-auto px-3 py-1.5 {{ $estadoClase[$cotizacion->estado] ?? 'bg-slate-100 text-slate-600' }} text-sm font-semibold rounded-lg">
            {{ $cotizacion->estado_nombre }}
        </span>
    </div>

    <div class="bg-white rounded-2xl shadow-[0_1px_3px_0_rgb(0_0_0/0.06),0_4px_12px_-2px_rgb(0_0_0/0.06)] border border-slate-100 p-6 sm:p-8">

        <div class="bg-slate-50 border border-slate-100 rounded-xl p-4 mb-6">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-2">Cliente</h3>
            <p class="text-sm font-semibold text-slate-800">{{ $cotizacion->cliente_nombre }}</p>
            <div class="flex flex-wrap gap-x-4 gap-y-0.5 mt-1 text-xs text-slate-500">
                @if($cotizacion->cliente_documento)<span>{{ $cotizacion->cliente_documento }}</span>@endif
                @if($cotizacion->cliente_telefono)<span>📞 {{ $cotizacion->cliente_telefono }}</span>@endif
                @if($cotizacion->cliente_correo)<span>✉️ {{ $cotizacion->cliente_correo }}</span>@endif
            </div>
        </div>

        @if($cotizacion->factura)
            <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-xl mb-6 text-sm flex justify-between items-center">
                <span>✅ Convertida en factura {{ $cotizacion->factura->numero_control }}</span>
                <a href="{{ route('facturas.show', $cotizacion->factura->id) }}" class="font-semibold underline">Ver factura →</a>
            </div>
        @endif

        <table class="w-full text-sm mb-6">
            <thead>
                <tr class="text-xs text-slate-400 uppercase tracking-wide border-b border-slate-100">
                    <th class="text-left py-2">Descripción</th>
                    <th class="text-right py-2">Cant.</th>
                    <th class="text-right py-2">P. Unit.</th>
                    <th class="text-right py-2">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($cotizacion->items as $item)
                    <tr>
                        <td class="py-2 text-slate-700">{{ $item->descripcion }}</td>
                        <td class="py-2 text-right text-slate-500">{{ number_format($item->cantidad, 2) }}</td>
                        <td class="py-2 text-right text-slate-500">${{ number_format($item->precio_unitario, 2) }}</td>
                        <td class="py-2 text-right font-medium text-slate-800">${{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="flex justify-end mb-6">
            <div class="w-64 bg-slate-50 border border-slate-100 rounded-xl p-4 space-y-1.5">
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500">Subtotal:</span>
                    <span class="font-semibold text-slate-700">${{ number_format($cotizacion->subtotal, 2) }}</span>
                </div>
                @if($cotizacion->total_descuento > 0)
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Descuento:</span>
                        <span class="font-semibold text-red-600">-${{ number_format($cotizacion->total_descuento, 2) }}</span>
                    </div>
                @endif
                <div class="flex justify-between text-lg font-bold text-blue-600 border-t border-slate-200 pt-1.5">
                    <span>Total:</span>
                    <span>${{ number_format($cotizacion->total, 2) }}</span>
                </div>
            </div>
        </div>

        @if($cotizacion->observaciones)
            <div class="mb-6">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-1">Observaciones</h3>
                <p class="text-sm text-slate-600">{{ $cotizacion->observaciones }}</p>
            </div>
        @endif

        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl mb-5 text-sm">✅ {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-5 text-sm">⚠️ {{ session('error') }}</div>
        @endif

        <!-- Acciones -->
        <div class="flex flex-wrap gap-2.5 pt-6 border-t border-slate-100">
            @if($cotizacion->estado !== 'convertida')
                <form action="{{ route('cotizaciones.cambiarEstado', $cotizacion) }}" method="POST">
                    @csrf
                    <input type="hidden" name="estado" value="aprobada">
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition shadow-sm">
                        ✓ Marcar Aprobada
                    </button>
                </form>
                <form action="{{ route('cotizaciones.cambiarEstado', $cotizacion) }}" method="POST">
                    @csrf
                    <input type="hidden" name="estado" value="rechazada">
                    <button type="submit" class="bg-white hover:bg-slate-50 text-red-600 text-sm font-medium px-4 py-2.5 rounded-lg border border-red-200 transition">
                        ✗ Marcar Rechazada
                    </button>
                </form>
                <form action="{{ route('cotizaciones.convertir', $cotizacion) }}" method="POST" class="ml-auto">
                    @csrf
                    <button type="submit" class="flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition shadow-sm">
                        📄 Convertir en Factura
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
