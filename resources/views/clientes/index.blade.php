@extends('layouts.app')

@section('title', 'Clientes - Factura Electrónica')

@section('content')
<div class="max-w-6xl mx-auto">

    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Clientes</h1>
            <p class="text-sm text-slate-500 mt-0.5">Consumo detallado por cliente, en todos los tipos de documento</p>
        </div>
    </div>

    <!-- Buscador -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_1px_3px_0_rgb(0_0_0_/_0.06)] p-4 mb-5">
        <form method="GET" action="{{ route('clientes.index') }}" class="flex gap-2">
            <div class="relative flex-1">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" name="buscar" value="{{ request('buscar') }}"
                       placeholder="Buscar por nombre o número de documento..."
                       class="w-full pl-9 pr-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2.5 rounded-lg transition shadow-sm">
                Buscar
            </button>
            @if(request('buscar'))
                <a href="{{ route('clientes.index') }}" class="bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium px-4 py-2.5 rounded-lg border border-slate-200 transition">
                    Limpiar
                </a>
            @endif
        </form>
    </div>

    @if($totalClientesVarios && $totalClientesVarios->total_documentos > 0)
        <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-xl mb-5 text-sm flex justify-between items-center flex-wrap gap-2">
            <span>📌 Además hay <strong>{{ $totalClientesVarios->total_documentos }}</strong> documento(s) de "Clientes Varios" (sin datos específicos) por <strong>${{ number_format($totalClientesVarios->total_consumido, 2) }}</strong>, no se pueden desglosar por cliente individual.</span>
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_1px_3px_0_rgb(0_0_0_/_0.06),0_4px_12px_-2px_rgb(0_0_0_/_0.06)] overflow-hidden">
        @if($clientes->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Cliente</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Documento</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide">Documentos Emitidos</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">Total Consumido</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Última Compra</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide">Ver</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($clientes as $cliente)
                            <tr class="hover:bg-slate-50/70 transition-colors cursor-pointer"
                                onclick="window.location='{{ route('clientes.show', $cliente->receptor_num_documento) }}'">
                                <td class="px-5 py-3.5 text-sm text-slate-800 font-medium max-w-[240px] truncate">{{ $cliente->receptor_nombre }}</td>
                                <td class="px-5 py-3.5 text-sm text-slate-500 font-mono">{{ $cliente->receptor_num_documento }}</td>
                                <td class="px-5 py-3.5 text-sm text-center text-slate-600">{{ $cliente->total_documentos }}</td>
                                <td class="px-5 py-3.5 text-sm text-right font-semibold text-slate-800">${{ number_format($cliente->total_consumido, 2) }}</td>
                                <td class="px-5 py-3.5 text-sm text-slate-500">{{ \Carbon\Carbon::parse($cliente->ultima_compra)->format('d/m/Y') }}</td>
                                <td class="px-5 py-3.5 text-center">
                                    <span class="text-blue-600">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-4 border-t border-slate-100">
                {{ $clientes->links() }}
            </div>
        @else
            <div class="text-center py-16">
                <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="h-7 w-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 100-8 4 4 0 000 8zm6 3v-1a4 4 0 00-3-3.87m-9 3.87a4 4 0 013-3.87"></path>
                    </svg>
                </div>
                <p class="text-slate-600 font-medium">No hay clientes con documentos emitidos</p>
                <p class="text-sm text-slate-400 mt-1">Prueba ajustando la búsqueda.</p>
            </div>
        @endif
    </div>
</div>
@endsection
