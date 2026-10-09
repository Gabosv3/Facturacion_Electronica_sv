@extends('layouts.app')

@section('title', 'Libro de Ventas - Factura Electrónica')

@section('content')
<div class="max-w-7xl mx-auto">

    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Libro de Ventas</h1>
            <p class="text-sm text-slate-500 mt-0.5">Reporte mensual de IVA — Contribuyentes, Consumidor Final y Sujeto Excluido</p>
        </div>
        <a href="{{ route('libro_ventas.exportar', ['mes' => $mes, 'anio' => $anio]) }}"
           class="flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition shadow-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            Exportar a Excel
        </a>
    </div>

    <!-- Filtro de periodo -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_1px_3px_0_rgb(0_0_0_/_0.06)] p-4 mb-5">
        <form method="GET" action="{{ route('libro_ventas.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Mes</label>
                <select name="mes" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500">
                    @foreach($meses as $num => $nombre)
                        <option value="{{ $num }}" {{ $mes == $num ? 'selected' : '' }}>{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Año</label>
                <select name="anio" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500">
                    @for($y = date('Y'); $y >= 2020; $y--)
                        <option value="{{ $y }}" {{ $anio == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition shadow-sm">
                Consultar
            </button>
        </form>
    </div>

    <!-- Totales resumen -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-100 shadow-[0_1px_3px_0_rgb(0_0_0_/_0.06)] p-5">
            <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wide">Contribuyentes (CCF)</p>
            <p class="text-xl font-bold text-slate-800 mt-1.5">${{ number_format($totales['contribuyentes']['total'], 2) }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ $totales['contribuyentes']['cantidad'] }} documento(s) · IVA ${{ number_format($totales['contribuyentes']['iva'], 2) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 shadow-[0_1px_3px_0_rgb(0_0_0_/_0.06)] p-5">
            <p class="text-xs font-semibold text-blue-600 uppercase tracking-wide">Consumidor Final</p>
            <p class="text-xl font-bold text-slate-800 mt-1.5">${{ number_format($totales['consumidor_final']['total'], 2) }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ $totales['consumidor_final']['cantidad'] }} documento(s) · IVA ${{ number_format($totales['consumidor_final']['iva'], 2) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 shadow-[0_1px_3px_0_rgb(0_0_0_/_0.06)] p-5">
            <p class="text-xs font-semibold text-orange-600 uppercase tracking-wide">Sujeto Excluido</p>
            <p class="text-xl font-bold text-slate-800 mt-1.5">${{ number_format($totales['sujeto_excluido']['total'], 2) }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ $totales['sujeto_excluido']['cantidad'] }} documento(s)</p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 mb-4 bg-slate-100 p-1 rounded-xl w-fit">
        <button type="button" onclick="cambiarTabLibro('contribuyentes')" id="tabBtn-contribuyentes"
                class="tab-btn-libro px-4 py-2 rounded-lg text-sm font-medium transition bg-white shadow-sm text-slate-800">
            Contribuyentes (CCF)
        </button>
        <button type="button" onclick="cambiarTabLibro('consumidor')" id="tabBtn-consumidor"
                class="tab-btn-libro px-4 py-2 rounded-lg text-sm font-medium transition text-slate-500">
            Consumidor Final
        </button>
        <button type="button" onclick="cambiarTabLibro('excluido')" id="tabBtn-excluido"
                class="tab-btn-libro px-4 py-2 rounded-lg text-sm font-medium transition text-slate-500">
            Sujeto Excluido
        </button>
    </div>

    <!-- Contribuyentes -->
    <div id="tabPanel-contribuyentes" class="tab-panel-libro bg-white rounded-2xl border border-slate-100 shadow-[0_1px_3px_0_rgb(0_0_0_/_0.06),0_4px_12px_-2px_rgb(0_0_0_/_0.06)] overflow-hidden">
        @if($contribuyentes->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Fecha</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Documento</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">NRC</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Cliente</th>
                            <th class="px-4 py-2.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">Exenta</th>
                            <th class="px-4 py-2.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">Gravada</th>
                            <th class="px-4 py-2.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">IVA</th>
                            <th class="px-4 py-2.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($contribuyentes as $factura)
                            @php $esNC = $factura->tipo_dte === '05'; $signo = $esNC ? -1 : 1; @endphp
                            <tr class="hover:bg-slate-50/70 {{ $esNC ? 'bg-violet-50/40' : '' }}">
                                <td class="px-4 py-2.5 text-slate-500">{{ $factura->fecha_emision->format('d/m/Y') }}</td>
                                <td class="px-4 py-2.5">
                                    <span class="font-mono text-xs text-slate-600">{{ $factura->numero_control }}</span>
                                    @if($esNC)
                                        <span class="block text-[10px] text-violet-600 font-semibold uppercase">Nota de Crédito</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-slate-500">{{ $factura->receptor_nrc ?: '—' }}</td>
                                <td class="px-4 py-2.5 text-slate-700 max-w-[200px] truncate">{{ $factura->receptor_nombre }}</td>
                                <td class="px-4 py-2.5 text-right text-slate-600">${{ number_format($signo * $factura->total_exenta, 2) }}</td>
                                <td class="px-4 py-2.5 text-right text-slate-600">${{ number_format($signo * $factura->total_gravada, 2) }}</td>
                                <td class="px-4 py-2.5 text-right text-slate-600">${{ number_format($signo * $factura->total_iva, 2) }}</td>
                                <td class="px-4 py-2.5 text-right font-semibold {{ $esNC ? 'text-violet-700' : 'text-slate-800' }}">${{ number_format($signo * $factura->total_pagar, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 border-t border-slate-200 font-semibold">
                        <tr>
                            <td colspan="4" class="px-4 py-3 text-slate-700">TOTALES</td>
                            <td class="px-4 py-3 text-right text-slate-800">${{ number_format($totales['contribuyentes']['exenta'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-slate-800">${{ number_format($totales['contribuyentes']['gravada'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-slate-800">${{ number_format($totales['contribuyentes']['iva'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-slate-800">${{ number_format($totales['contribuyentes']['total'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="text-center py-12 text-slate-400">Sin CCF ni Notas de Crédito en este período.</div>
        @endif
    </div>

    <!-- Consumidor Final -->
    <div id="tabPanel-consumidor" class="tab-panel-libro hidden bg-white rounded-2xl border border-slate-100 shadow-[0_1px_3px_0_rgb(0_0_0_/_0.06),0_4px_12px_-2px_rgb(0_0_0_/_0.06)] overflow-hidden">
        @if($consumidorFinal->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Fecha</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Documento</th>
                            <th class="px-4 py-2.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">Exenta</th>
                            <th class="px-4 py-2.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">Gravada</th>
                            <th class="px-4 py-2.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">IVA</th>
                            <th class="px-4 py-2.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($consumidorFinal as $factura)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-4 py-2.5 text-slate-500">{{ $factura->fecha_emision->format('d/m/Y') }}</td>
                                <td class="px-4 py-2.5 font-mono text-xs text-slate-600">{{ $factura->numero_control }}</td>
                                <td class="px-4 py-2.5 text-right text-slate-600">${{ number_format($factura->total_exenta, 2) }}</td>
                                <td class="px-4 py-2.5 text-right text-slate-600">${{ number_format($factura->total_gravada, 2) }}</td>
                                <td class="px-4 py-2.5 text-right text-slate-600">${{ number_format($factura->total_iva, 2) }}</td>
                                <td class="px-4 py-2.5 text-right font-semibold text-slate-800">${{ number_format($factura->total_pagar, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 border-t border-slate-200 font-semibold">
                        <tr>
                            <td colspan="2" class="px-4 py-3 text-slate-700">TOTALES</td>
                            <td class="px-4 py-3 text-right text-slate-800">${{ number_format($totales['consumidor_final']['exenta'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-slate-800">${{ number_format($totales['consumidor_final']['gravada'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-slate-800">${{ number_format($totales['consumidor_final']['iva'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-slate-800">${{ number_format($totales['consumidor_final']['total'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="text-center py-12 text-slate-400">Sin Facturas de consumidor final en este período.</div>
        @endif
    </div>

    <!-- Sujeto Excluido -->
    <div id="tabPanel-excluido" class="tab-panel-libro hidden bg-white rounded-2xl border border-slate-100 shadow-[0_1px_3px_0_rgb(0_0_0_/_0.06),0_4px_12px_-2px_rgb(0_0_0_/_0.06)] overflow-hidden">
        @if($sujetoExcluido->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Fecha</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Documento</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Cliente</th>
                            <th class="px-4 py-2.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($sujetoExcluido as $factura)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-4 py-2.5 text-slate-500">{{ $factura->fecha_emision->format('d/m/Y') }}</td>
                                <td class="px-4 py-2.5 font-mono text-xs text-slate-600">{{ $factura->numero_control }}</td>
                                <td class="px-4 py-2.5 text-slate-700 max-w-[240px] truncate">{{ $factura->receptor_nombre }}</td>
                                <td class="px-4 py-2.5 text-right font-semibold text-slate-800">${{ number_format($factura->total_pagar, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 border-t border-slate-200 font-semibold">
                        <tr>
                            <td colspan="3" class="px-4 py-3 text-slate-700">TOTAL</td>
                            <td class="px-4 py-3 text-right text-slate-800">${{ number_format($totales['sujeto_excluido']['total'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="text-center py-12 text-slate-400">Sin Facturas de sujeto excluido en este período.</div>
        @endif
    </div>
</div>

@section('extra_scripts')
<script>
    function cambiarTabLibro(tab) {
        document.querySelectorAll('.tab-panel-libro').forEach(panel => panel.classList.add('hidden'));
        document.querySelectorAll('.tab-btn-libro').forEach(btn => {
            btn.classList.remove('bg-white', 'shadow-sm', 'text-slate-800');
            btn.classList.add('text-slate-500');
        });

        document.getElementById('tabPanel-' + tab).classList.remove('hidden');
        const btn = document.getElementById('tabBtn-' + tab);
        btn.classList.add('bg-white', 'shadow-sm', 'text-slate-800');
        btn.classList.remove('text-slate-500');
    }
</script>
@endsection
@endsection
