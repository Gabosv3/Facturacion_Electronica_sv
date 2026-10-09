@extends('layouts.app')

@section('title', 'Cotizaciones - Factura Electrónica')

@section('content')
@php
    $estadoClase = [
        'pendiente' => 'bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200',
        'aprobada' => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200',
        'rechazada' => 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-200',
        'convertida' => 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-200',
    ];
@endphp
<div class="max-w-6xl mx-auto">

    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Cotizaciones</h1>
            <p class="text-sm text-slate-500 mt-0.5">Presupuestos para clientes, sin generar documento fiscal</p>
        </div>
        <a href="{{ route('cotizaciones.create') }}" class="flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nueva Cotización
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_1px_3px_0_rgb(0_0_0_/_0.06)] p-4 mb-5">
        <form method="GET" action="{{ route('cotizaciones.index') }}" class="flex flex-wrap gap-2">
            <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por cliente o número..."
                   class="flex-1 min-w-[200px] border border-slate-200 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500">
            <select name="estado" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500">
                <option value="">Todos los estados</option>
                <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                <option value="aprobada" {{ request('estado') == 'aprobada' ? 'selected' : '' }}>Aprobada</option>
                <option value="rechazada" {{ request('estado') == 'rechazada' ? 'selected' : '' }}>Rechazada</option>
                <option value="convertida" {{ request('estado') == 'convertida' ? 'selected' : '' }}>Convertida a Factura</option>
            </select>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition shadow-sm">Buscar</button>
        </form>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_1px_3px_0_rgb(0_0_0_/_0.06),0_4px_12px_-2px_rgb(0_0_0_/_0.06)] overflow-hidden">
        @if($cotizaciones->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">N°</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Cliente</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Fecha</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">Total</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide">Estado</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide">Ver</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($cotizaciones as $cotizacion)
                            <tr class="hover:bg-slate-50/70 transition-colors cursor-pointer" onclick="window.location='{{ route('cotizaciones.show', $cotizacion) }}'">
                                <td class="px-5 py-3.5 font-mono text-xs text-slate-600">{{ $cotizacion->numero_cotizacion }}</td>
                                <td class="px-5 py-3.5 text-slate-800 font-medium max-w-[220px] truncate">{{ $cotizacion->cliente_nombre }}</td>
                                <td class="px-5 py-3.5 text-slate-500">{{ $cotizacion->fecha->format('d/m/Y') }}</td>
                                <td class="px-5 py-3.5 text-right font-semibold text-slate-800">${{ number_format($cotizacion->total, 2) }}</td>
                                <td class="px-5 py-3.5 text-center">
                                    <span class="px-2.5 py-1 {{ $estadoClase[$cotizacion->estado] ?? 'bg-slate-100 text-slate-600' }} text-xs font-semibold rounded-full">
                                        {{ $cotizacion->estado_nombre }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-center text-blue-600">
                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-4 border-t border-slate-100">{{ $cotizaciones->links() }}</div>
        @else
            <div class="text-center py-16">
                <p class="text-slate-600 font-medium">No hay cotizaciones registradas</p>
                <a href="{{ route('cotizaciones.create') }}" class="mt-4 inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2.5 rounded-lg text-sm transition shadow-sm">
                    Crear la primera cotización
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
