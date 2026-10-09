@extends('layouts.app')

@section('title', 'Recordatorios de Mantenimiento - Factura Electrónica')

@section('content')
<div class="max-w-6xl mx-auto">

    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Recordatorios de Mantenimiento</h1>
            <p class="text-sm text-slate-500 mt-0.5">Clientes que compraron aceite, filtros u otro servicio de mantenimiento recurrente</p>
        </div>
        <form method="GET" action="{{ route('recordatorios.index') }}" class="flex items-center gap-2">
            <label class="text-sm text-slate-500">Considerar vencido después de</label>
            <select name="dias" onchange="this.form.submit()" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500">
                <option value="60" {{ $diasUmbral == 60 ? 'selected' : '' }}>60 días</option>
                <option value="90" {{ $diasUmbral == 90 ? 'selected' : '' }}>90 días</option>
                <option value="120" {{ $diasUmbral == 120 ? 'selected' : '' }}>120 días</option>
                <option value="180" {{ $diasUmbral == 180 ? 'selected' : '' }}>180 días</option>
            </select>
        </form>
    </div>

    <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-xl mb-6 text-sm">
        📌 Se detectan automáticamente los ítems cuya descripción contiene: <em>aceite, filtro, afinación, lubricante</em>. Es una estimación basada en la última compra de ese tipo de servicio — no reemplaza el criterio del taller.
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-red-100 shadow-[0_1px_3px_0_rgb(0_0_0_/_0.06)] p-5">
            <p class="text-xs font-semibold text-red-600 uppercase tracking-wide">🔴 Vencidos</p>
            <p class="text-2xl font-bold text-slate-800 mt-1.5">{{ $vencidos->count() }}</p>
            <p class="text-xs text-slate-400 mt-1">Más de {{ $diasUmbral }} días sin volver</p>
        </div>
        <div class="bg-white rounded-xl border border-amber-100 shadow-[0_1px_3px_0_rgb(0_0_0_/_0.06)] p-5">
            <p class="text-xs font-semibold text-amber-600 uppercase tracking-wide">🟡 Próximos</p>
            <p class="text-2xl font-bold text-slate-800 mt-1.5">{{ $proximos->count() }}</p>
            <p class="text-xs text-slate-400 mt-1">Entre {{ $diasUmbral - 30 }} y {{ $diasUmbral }} días</p>
        </div>
        <div class="bg-white rounded-xl border border-emerald-100 shadow-[0_1px_3px_0_rgb(0_0_0_/_0.06)] p-5">
            <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wide">🟢 Al día</p>
            <p class="text-2xl font-bold text-slate-800 mt-1.5">{{ $alDia->count() }}</p>
            <p class="text-xs text-slate-400 mt-1">Visitaron hace menos de {{ $diasUmbral - 30 }} días</p>
        </div>
    </div>

    @foreach([['vencidos', $vencidos, '🔴 Vencidos — contactar primero', 'red'], ['proximos', $proximos, '🟡 Próximos a vencer', 'amber'], ['alDia', $alDia, '🟢 Al día', 'emerald']] as [$key, $lista, $titulo, $color])
        <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_1px_3px_0_rgb(0_0_0_/_0.06),0_4px_12px_-2px_rgb(0_0_0_/_0.06)] overflow-hidden mb-5">
            <div class="px-5 py-4 border-b border-slate-100">
                <h2 class="text-sm font-bold text-slate-700">{{ $titulo }} <span class="text-slate-400 font-normal">({{ $lista->count() }})</span></h2>
            </div>
            @if($lista->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-100">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Cliente</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Última visita</th>
                                <th class="px-4 py-2.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">Días</th>
                                <th class="px-4 py-2.5 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide">Visitas</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Contacto</th>
                                <th class="px-4 py-2.5 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide">Ver</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($lista as $cliente)
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-4 py-2.5 text-slate-800 font-medium">{{ $cliente->receptor_nombre }}</td>
                                    <td class="px-4 py-2.5 text-slate-500">{{ $cliente->ultima_visita_formato }}</td>
                                    <td class="px-4 py-2.5 text-right font-semibold text-{{ $color }}-600">{{ $cliente->dias_transcurridos }}</td>
                                    <td class="px-4 py-2.5 text-center text-slate-500">{{ $cliente->veces_visitado }}</td>
                                    <td class="px-4 py-2.5">
                                        @if($cliente->receptor_telefono)
                                            <a href="tel:{{ $cliente->receptor_telefono }}" class="text-blue-600 hover:underline text-xs">📞 {{ $cliente->receptor_telefono }}</a>
                                        @endif
                                        @if($cliente->receptor_correo)
                                            <span class="block text-xs text-slate-400">{{ $cliente->receptor_correo }}</span>
                                        @endif
                                        @if(!$cliente->receptor_telefono && !$cliente->receptor_correo)
                                            <span class="text-xs text-slate-300">Sin contacto</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 text-center">
                                        <a href="{{ route('clientes.show', $cliente->receptor_num_documento) }}" class="text-blue-600">
                                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8 text-slate-400 text-sm">Sin clientes en esta categoría.</div>
            @endif
        </div>
    @endforeach
</div>
@endsection
