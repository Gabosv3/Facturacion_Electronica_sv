@extends('layouts.app')

@section('title', 'Inicio - Factura Electrónica')

@section('content')
@php
    $tipoLabels = ['01' => 'Factura', '03' => 'CCF', '05' => 'Nota de Crédito', '14' => 'Sujeto Excluido'];
    $tipoColors = ['01' => 'bg-blue-500', '03' => 'bg-emerald-500', '05' => 'bg-violet-500', '14' => 'bg-orange-500'];
    $estadoLabels = [
        'enviado_mh' => 'Enviada al MH',
        'firmado' => 'Firmada',
        'firmado_contingencia' => 'Contingencia',
        'firmado_sin_enviar' => 'Sin enviar',
        'error_firma' => 'Error de firma',
        'rechazado' => 'Rechazada',
        'pendiente' => 'Pendiente',
    ];
    $estadoColors = [
        'enviado_mh' => 'bg-emerald-500',
        'firmado' => 'bg-blue-500',
        'firmado_contingencia' => 'bg-amber-500',
        'firmado_sin_enviar' => 'bg-amber-500',
        'error_firma' => 'bg-red-500',
        'rechazado' => 'bg-red-500',
        'pendiente' => 'bg-slate-400',
    ];
    $maxVentaDia = max(1, $ventasUltimos7Dias->max('total'));
@endphp

<div class="max-w-7xl mx-auto">

    <!-- Encabezado -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">
                Hola{{ session('user_data')['name'] ? ', ' . explode(' ', session('user_data')['name'])[0] : '' }} 👋
            </h1>
            <p class="text-sm text-slate-500 mt-0.5">{{ ucfirst(now()->translatedFormat('l d \d\e F \d\e Y')) }}</p>
        </div>
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('facturas.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition shadow-sm">
                + Nueva Factura
            </a>
            <a href="{{ route('facturas.index') }}" class="bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium px-4 py-2 rounded-lg transition border border-slate-200">
                Ver todas
            </a>
        </div>
    </div>

    <!-- Alertas operativas -->
    @if($contingenciaActiva)
        <div class="mb-4 bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3.5 rounded-xl flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="text-lg">🚨</span>
                <div>
                    <strong class="font-semibold block text-sm">Modo contingencia activo</strong>
                    <span class="text-xs">Las facturas nuevas se firman localmente y no se envían al MH hasta cerrar el evento.</span>
                </div>
            </div>
            <a href="{{ route('contingencias.show', $contingenciaActiva->id) }}" class="text-xs font-semibold text-amber-800 underline whitespace-nowrap">Ver evento →</a>
        </div>
    @endif

    @if(!$tokenActivo || !$tokenActivo->isValid())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3.5 rounded-xl flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="text-lg">⚠️</span>
                <div>
                    <strong class="font-semibold block text-sm">Token del MH no disponible o expirado</strong>
                    <span class="text-xs">El envío de documentos al Ministerio de Hacienda podría fallar hasta renovarlo.</span>
                </div>
            </div>
            <a href="{{ route('admin.tokens.index') }}" class="text-xs font-semibold text-red-800 underline whitespace-nowrap">Renovar →</a>
        </div>
    @endif

    <!-- KPIs principales -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-100 shadow-[0_1px_3px_0_rgb(0_0_0_/_0.06)] p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Ventas este mes</p>
            <p class="text-2xl font-bold text-slate-800 mt-1.5">${{ number_format($totalVentasMes, 2) }}</p>
            @if($variacionVentas !== null)
                <p class="text-xs mt-1.5 {{ $variacionVentas >= 0 ? 'text-emerald-600' : 'text-red-600' }} font-medium">
                    {{ $variacionVentas >= 0 ? '▲' : '▼' }} {{ abs($variacionVentas) }}% vs. mes anterior
                </p>
            @else
                <p class="text-xs mt-1.5 text-slate-400">Sin datos del mes anterior</p>
            @endif
        </div>

        <div class="bg-white rounded-xl border border-slate-100 shadow-[0_1px_3px_0_rgb(0_0_0_/_0.06)] p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Documentos este mes</p>
            <p class="text-2xl font-bold text-slate-800 mt-1.5">{{ number_format($cantidadFacturasMes) }}</p>
            <p class="text-xs mt-1.5 text-slate-400">{{ number_format($totalFacturas) }} en total histórico</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-100 shadow-[0_1px_3px_0_rgb(0_0_0_/_0.06)] p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Ventas históricas</p>
            <p class="text-2xl font-bold text-slate-800 mt-1.5">${{ number_format($totalVentasHistorico, 2) }}</p>
            <p class="text-xs mt-1.5 text-slate-400">Documentos aceptados por el MH</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-100 shadow-[0_1px_3px_0_rgb(0_0_0_/_0.06)] p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Requieren atención</p>
            <p class="text-2xl font-bold {{ $facturasConError > 0 ? 'text-red-600' : 'text-slate-800' }} mt-1.5">{{ $facturasConError }}</p>
            <p class="text-xs mt-1.5 text-slate-400">Con error, rechazadas o sin enviar</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <!-- Ventas últimos 7 días -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-100 shadow-[0_1px_3px_0_rgb(0_0_0_/_0.06)] p-5">
            <h2 class="text-sm font-semibold text-slate-700 mb-4">Ventas de los últimos 7 días</h2>
            <div class="flex items-end justify-between gap-2 h-36">
                @foreach($ventasUltimos7Dias as $dia)
                    <div class="flex-1 flex flex-col items-center justify-end h-full gap-1.5 group">
                        <span class="text-[10px] text-slate-400 font-medium opacity-0 group-hover:opacity-100 transition">${{ number_format($dia['total'], 0) }}</span>
                        <div class="w-full bg-blue-100 hover:bg-blue-500 transition-colors rounded-md"
                             style="height: {{ max(4, round(($dia['total'] / $maxVentaDia) * 100)) }}%"></div>
                        <span class="text-[10px] text-slate-400 font-medium">{{ $dia['fecha'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Estado operativo -->
        <div class="bg-white rounded-xl border border-slate-100 shadow-[0_1px_3px_0_rgb(0_0_0_/_0.06)] p-5">
            <h2 class="text-sm font-semibold text-slate-700 mb-4">Estado operativo</h2>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-600">Token MH</span>
                    @if($tokenActivo && $tokenActivo->isValid())
                        <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-xs font-semibold rounded-full">Válido</span>
                    @else
                        <span class="px-2 py-0.5 bg-red-100 text-red-700 text-xs font-semibold rounded-full">Expirado</span>
                    @endif
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-600">Contingencia</span>
                    @if($contingenciaActiva)
                        <span class="px-2 py-0.5 bg-amber-100 text-amber-700 text-xs font-semibold rounded-full">Activa</span>
                    @else
                        <span class="px-2 py-0.5 bg-slate-100 text-slate-600 text-xs font-semibold rounded-full">Normal</span>
                    @endif
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-600">Invalidaciones pendientes</span>
                    <span class="px-2 py-0.5 {{ $invalidacionesPendientes > 0 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600' }} text-xs font-semibold rounded-full">{{ $invalidacionesPendientes }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-600">Invalidaciones procesadas</span>
                    <span class="px-2 py-0.5 bg-slate-100 text-slate-600 text-xs font-semibold rounded-full">{{ $invalidacionesProcesadas }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Comparativo mensual -->
    @php $maxVentaMes = max(1, $ventasUltimosMeses->max('total')); @endphp
    <div class="bg-white rounded-xl border border-slate-100 shadow-[0_1px_3px_0_rgb(0_0_0_/_0.06)] p-5 mb-6">
        <h2 class="text-sm font-semibold text-slate-700 mb-4">Comparativo de ventas — últimos 12 meses</h2>
        <div class="flex items-end justify-between gap-1.5 h-44 overflow-x-auto">
            @foreach($ventasUltimosMeses as $mesData)
                <div class="flex-1 min-w-[32px] flex flex-col items-center justify-end h-full gap-1.5 group">
                    <span class="text-[10px] text-slate-400 font-medium opacity-0 group-hover:opacity-100 transition whitespace-nowrap">${{ number_format($mesData['total'], 0) }}</span>
                    <div class="w-full {{ $mesData['esMesActual'] ? 'bg-blue-500' : 'bg-blue-100 hover:bg-blue-400' }} transition-colors rounded-md"
                         style="height: {{ max(4, round(($mesData['total'] / $maxVentaMes) * 100)) }}%"></div>
                    <span class="text-[10px] {{ $mesData['esMesActual'] ? 'text-blue-600 font-bold' : 'text-slate-400 font-medium' }} whitespace-nowrap">{{ $mesData['mes'] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <!-- Documentos por tipo -->
        <div class="bg-white rounded-xl border border-slate-100 shadow-[0_1px_3px_0_rgb(0_0_0_/_0.06)] p-5">
            <h2 class="text-sm font-semibold text-slate-700 mb-4">Documentos por tipo</h2>
            <div class="space-y-3">
                @forelse($tipoLabels as $codigo => $label)
                    @php $cantidad = $porTipo[$codigo] ?? 0; @endphp
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-slate-600 font-medium">{{ $label }}</span>
                            <span class="text-slate-400">{{ $cantidad }}</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2">
                            <div class="{{ $tipoColors[$codigo] }} h-2 rounded-full" style="width: {{ $totalFacturas > 0 ? round(($cantidad / $totalFacturas) * 100) : 0 }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">Sin datos todavía.</p>
                @endforelse
            </div>
        </div>

        <!-- Documentos por estado -->
        <div class="bg-white rounded-xl border border-slate-100 shadow-[0_1px_3px_0_rgb(0_0_0_/_0.06)] p-5">
            <h2 class="text-sm font-semibold text-slate-700 mb-4">Documentos por estado</h2>
            <div class="space-y-3">
                @forelse($porEstado as $estado => $cantidad)
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-slate-600 font-medium">{{ $estadoLabels[$estado] ?? ucfirst($estado) }}</span>
                            <span class="text-slate-400">{{ $cantidad }}</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2">
                            <div class="{{ $estadoColors[$estado] ?? 'bg-slate-400' }} h-2 rounded-full" style="width: {{ $totalFacturas > 0 ? round(($cantidad / $totalFacturas) * 100) : 0 }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">Sin datos todavía.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Últimas facturas -->
    <div class="bg-white rounded-xl border border-slate-100 shadow-[0_1px_3px_0_rgb(0_0_0_/_0.06)] overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center">
            <h2 class="text-sm font-semibold text-slate-700">Últimos documentos emitidos</h2>
            <a href="{{ route('facturas.index') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700">Ver todos →</a>
        </div>
        @if($ultimasFacturas->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-xs text-slate-500 uppercase tracking-wide">
                        <tr>
                            <th class="px-5 py-2.5 text-left">Tipo</th>
                            <th class="px-5 py-2.5 text-left">Número Control</th>
                            <th class="px-5 py-2.5 text-left">Cliente</th>
                            <th class="px-5 py-2.5 text-right">Total</th>
                            <th class="px-5 py-2.5 text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($ultimasFacturas as $factura)
                            <tr class="hover:bg-slate-50/80 transition-colors cursor-pointer" onclick="window.location='{{ route('facturas.show', $factura) }}'">
                                <td class="px-5 py-2.5">
                                    <span class="px-2 py-0.5 {{ $tipoColors[$factura->tipo_dte] ?? 'bg-slate-400' }} bg-opacity-10 text-xs font-semibold rounded-full" style="color: inherit;">
                                        DTE-{{ $factura->tipo_dte }}
                                    </span>
                                </td>
                                <td class="px-5 py-2.5 font-mono text-xs text-slate-600">{{ $factura->numero_control }}</td>
                                <td class="px-5 py-2.5 text-slate-700">{{ \Illuminate\Support\Str::limit($factura->receptor_nombre, 30) }}</td>
                                <td class="px-5 py-2.5 text-right font-semibold text-slate-700">${{ number_format($factura->total_pagar, 2) }}</td>
                                <td class="px-5 py-2.5 text-center">
                                    <span class="px-2 py-0.5 {{ $estadoColors[$factura->estado] ?? 'bg-slate-400' }} bg-opacity-15 text-slate-700 text-xs font-semibold rounded-full">
                                        {{ $estadoLabels[$factura->estado] ?? ucfirst($factura->estado) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-12 text-slate-400">
                <p class="text-3xl mb-2">📭</p>
                <p class="text-sm">Todavía no hay facturas registradas.</p>
            </div>
        @endif
    </div>
</div>
@endsection
