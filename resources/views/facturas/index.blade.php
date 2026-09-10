<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturas - DTE El Salvador</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .filters-panel { transition: all .2s ease; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">
    @include('components.navbar')

    <div class="container mx-auto px-4 sm:px-6 pb-10">
        <div class="max-w-7xl mx-auto">

            <!-- Encabezado -->
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 mb-5">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Facturas Electrónicas</h1>
                    <p class="text-sm text-slate-500 mt-0.5">Gestión de documentos tributarios emitidos</p>
                </div>
                <div class="flex gap-2 flex-wrap">
                    <a href="{{ route('facturas.create') }}" class="flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Nueva Factura
                    </a>
                </div>
            </div>

            <!-- Panel de filtros -->
            <div class="filters-panel bg-white rounded-2xl border border-slate-100 shadow-[0_1px_3px_0_rgb(0_0_0_/_0.06),0_4px_12px_-2px_rgb(0_0_0_/_0.06)] p-5 mb-5">
                <form method="GET" action="{{ route('facturas.index') }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="lg:col-span-2">
                            <label for="buscar" class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Buscar</label>
                            <div class="relative">
                                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <input type="text"
                                       id="buscar"
                                       name="buscar"
                                       value="{{ request('buscar') }}"
                                       placeholder="Número de control, cliente, documento..."
                                       class="w-full pl-9 pr-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                            </div>
                        </div>

                        <div>
                            <label for="tipo_dte" class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Tipo DTE</label>
                            <select id="tipo_dte"
                                    name="tipo_dte"
                                    class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                                <option value="">Todos</option>
                                <option value="01" {{ request('tipo_dte') == '01' ? 'selected' : '' }}>DTE-01 (Factura)</option>
                                <option value="03" {{ request('tipo_dte') == '03' ? 'selected' : '' }}>DTE-03 (CCF)</option>
                                <option value="05" {{ request('tipo_dte') == '05' ? 'selected' : '' }}>DTE-05 (Nota Crédito)</option>
                                <option value="14" {{ request('tipo_dte') == '14' ? 'selected' : '' }}>DTE-14 (Sujeto Excluido)</option>
                            </select>
                        </div>

                        <div>
                            <label for="estado" class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Estado</label>
                            <select id="estado"
                                    name="estado"
                                    class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                                <option value="">Todos</option>
                                <option value="enviado_mh" {{ request('estado') == 'enviado_mh' ? 'selected' : '' }}>Enviada MH</option>
                                <option value="firmado" {{ request('estado') == 'firmado' ? 'selected' : '' }}>Firmada</option>
                                <option value="firmado_sin_enviar" {{ request('estado') == 'firmado_sin_enviar' ? 'selected' : '' }}>Sin enviar</option>
                                <option value="firmado_contingencia" {{ request('estado') == 'firmado_contingencia' ? 'selected' : '' }}>Contingencia</option>
                                <option value="error_firma" {{ request('estado') == 'error_firma' ? 'selected' : '' }}>Error Firma</option>
                                <option value="rechazado" {{ request('estado') == 'rechazado' ? 'selected' : '' }}>Rechazado</option>
                                <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                            </select>
                        </div>
                    </div>

                    <!-- Filtros de Fecha -->
                    <div class="border-t border-slate-100 mt-4 pt-4">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <label for="fecha_desde" class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Desde</label>
                                <input type="date"
                                       id="fecha_desde"
                                       name="fecha_desde"
                                       value="{{ request('fecha_desde') }}"
                                       class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                            </div>

                            <div>
                                <label for="fecha_hasta" class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Hasta</label>
                                <input type="date"
                                       id="fecha_hasta"
                                       name="fecha_hasta"
                                       value="{{ request('fecha_hasta') }}"
                                       class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                            </div>

                            <div>
                                <label for="mes" class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Mes</label>
                                <select id="mes"
                                        name="mes"
                                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                                    <option value="">Todos</option>
                                    <option value="1" {{ request('mes') == '1' ? 'selected' : '' }}>Enero</option>
                                    <option value="2" {{ request('mes') == '2' ? 'selected' : '' }}>Febrero</option>
                                    <option value="3" {{ request('mes') == '3' ? 'selected' : '' }}>Marzo</option>
                                    <option value="4" {{ request('mes') == '4' ? 'selected' : '' }}>Abril</option>
                                    <option value="5" {{ request('mes') == '5' ? 'selected' : '' }}>Mayo</option>
                                    <option value="6" {{ request('mes') == '6' ? 'selected' : '' }}>Junio</option>
                                    <option value="7" {{ request('mes') == '7' ? 'selected' : '' }}>Julio</option>
                                    <option value="8" {{ request('mes') == '8' ? 'selected' : '' }}>Agosto</option>
                                    <option value="9" {{ request('mes') == '9' ? 'selected' : '' }}>Septiembre</option>
                                    <option value="10" {{ request('mes') == '10' ? 'selected' : '' }}>Octubre</option>
                                    <option value="11" {{ request('mes') == '11' ? 'selected' : '' }}>Noviembre</option>
                                    <option value="12" {{ request('mes') == '12' ? 'selected' : '' }}>Diciembre</option>
                                </select>
                            </div>

                            <div>
                                <label for="anio" class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Año</label>
                                <select id="anio"
                                        name="anio"
                                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition">
                                    <option value="">Todos</option>
                                    @for($year = date('Y'); $year >= 2020; $year--)
                                        <option value="{{ $year }}" {{ request('anio') == $year ? 'selected' : '' }}>{{ $year }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="flex items-center gap-2.5 pt-4 mt-4 border-t border-slate-100 flex-wrap">
                        <button type="submit"
                                class="flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium text-sm transition shadow-sm">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Buscar
                        </button>

                        <a href="{{ route('facturas.index') }}"
                           class="flex items-center gap-1.5 bg-white hover:bg-slate-50 text-slate-600 px-4 py-2 rounded-lg font-medium text-sm transition border border-slate-200">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Limpiar
                        </a>

                        <button type="button"
                                onclick="exportarExcel()"
                                class="flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg font-medium text-sm transition shadow-sm">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Exportar a Excel
                        </button>

                        @if(request()->hasAny(['buscar', 'tipo_dte', 'estado', 'fecha_desde', 'fecha_hasta', 'mes', 'anio']))
                            <span class="text-sm text-slate-500 ml-1">
                                <span class="font-semibold text-blue-600">{{ $facturas->total() }}</span> resultado(s) encontrado(s)
                            </span>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Tabla -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_1px_3px_0_rgb(0_0_0_/_0.06),0_4px_12px_-2px_rgb(0_0_0_/_0.06)] overflow-hidden">
                @if($facturas->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-slate-50 border-b border-slate-100">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Tipo</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Número Control</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Fecha</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Cliente</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">Total</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide">Estado</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide">Invalidación</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide">N. Crédito</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($facturas as $factura)
                                @php
                                    $tipoStyles = [
                                        '01' => 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-200',
                                        '03' => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200',
                                        '05' => 'bg-violet-50 text-violet-700 ring-1 ring-inset ring-violet-200',
                                        '14' => 'bg-orange-50 text-orange-700 ring-1 ring-inset ring-orange-200',
                                    ];
                                    $tipoLabel = ['01' => 'DTE-01', '03' => 'CCF-03', '05' => 'NC-05', '14' => 'FSE-14'];
                                @endphp
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="px-5 py-3.5 text-sm">
                                        <span class="px-2.5 py-1 {{ $tipoStyles[$factura->tipo_dte] ?? 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-200' }} text-xs font-semibold rounded-full">
                                            {{ $tipoLabel[$factura->tipo_dte] ?? 'DTE-' . $factura->tipo_dte }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-sm font-mono text-slate-600">{{ $factura->numero_control }}</td>
                                    <td class="px-5 py-3.5 text-sm text-slate-500">{{ $factura->fecha_emision->format('d/m/Y') }}</td>
                                    <td class="px-5 py-3.5 text-sm text-slate-700 max-w-[220px] truncate">{{ $factura->receptor_nombre }}</td>
                                    <td class="px-5 py-3.5 text-sm text-right font-semibold text-slate-800">${{ number_format($factura->total_pagar, 2) }}</td>
                                    <td class="px-5 py-3.5 text-center">
                                        @if($factura->estado == 'enviado_mh')
                                            <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200 text-xs font-semibold rounded-full">Enviada MH</span>
                                        @elseif($factura->estado == 'firmado')
                                            <span class="px-2.5 py-1 bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-200 text-xs font-semibold rounded-full">Firmada</span>
                                        @elseif($factura->estado == 'firmado_sin_enviar')
                                            <span class="px-2.5 py-1 bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200 text-xs font-semibold rounded-full">Sin enviar</span>
                                        @elseif($factura->estado == 'error_firma')
                                            <span class="px-2.5 py-1 bg-red-50 text-red-700 ring-1 ring-inset ring-red-200 text-xs font-semibold rounded-full">Error Firma</span>
                                        @elseif($factura->estado == 'rechazado')
                                            <span class="px-2.5 py-1 bg-red-50 text-red-700 ring-1 ring-inset ring-red-200 text-xs font-semibold rounded-full">Rechazado</span>
                                        @else
                                            <span class="px-2.5 py-1 bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200 text-xs font-semibold rounded-full">Pendiente</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        @php
                                            $invalidacionActiva = $factura->getInvalidacionActiva();
                                        @endphp
                                        @if($factura->invalidada || $invalidacionActiva)
                                            @if($invalidacionActiva)
                                                @if($invalidacionActiva->estado == 'procesado')
                                                    <span class="px-2.5 py-1 bg-red-50 text-red-700 ring-1 ring-inset ring-red-200 text-xs font-semibold rounded-full">
                                                        Invalidado
                                                    </span>
                                                @elseif($invalidacionActiva->estado == 'enviado')
                                                    <span class="px-2.5 py-1 bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200 text-xs font-semibold rounded-full">
                                                        En proceso
                                                    </span>
                                                @endif
                                            @else
                                                <span class="px-2.5 py-1 bg-red-50 text-red-700 ring-1 ring-inset ring-red-200 text-xs font-semibold rounded-full">
                                                    Invalidado
                                                </span>
                                            @endif
                                        @elseif($factura->tieneInvalidacionPendiente())
                                            <span class="px-2.5 py-1 bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200 text-xs font-semibold rounded-full">
                                                Pendiente
                                            </span>
                                        @else
                                            <span class="text-slate-300 text-xs">—</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        @if($factura->nota_credito_estado)
                                            @if($factura->nota_credito_estado == 'anulacion')
                                                <span class="px-2.5 py-1 bg-red-50 text-red-700 ring-1 ring-inset ring-red-200 text-xs font-semibold rounded-full">Anulado</span>
                                            @elseif($factura->nota_credito_estado == 'ajuste_precio')
                                                <span class="px-2.5 py-1 bg-orange-50 text-orange-700 ring-1 ring-inset ring-orange-200 text-xs font-semibold rounded-full">Ajuste</span>
                                            @elseif($factura->nota_credito_estado == 'devolucion')
                                                <span class="px-2.5 py-1 bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-200 text-xs font-semibold rounded-full">Devolución</span>
                                            @else
                                                <span class="px-2.5 py-1 bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200 text-xs font-semibold rounded-full">{{ ucfirst($factura->nota_credito_estado) }}</span>
                                            @endif
                                        @else
                                            <span class="text-slate-300 text-xs">—</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center justify-center gap-1">
                                            <a href="{{ route('facturas.show', $factura) }}"
                                               title="Ver detalle"
                                               class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition">
                                                <svg class="w-4.5 h-4.5" style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </a>

                                            @if($factura->estado !== 'enviado_mh')
                                                <form id="delete-form-{{ $factura->id }}" action="{{ route('facturas.destroy', $factura) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button"
                                                            onclick="confirmarEliminacion({{ $factura->id }}, '{{ $factura->numero_control }}', '{{ addslashes($factura->receptor_nombre) }}', '{{ number_format($factura->total_pagar, 2) }}', '{{ ucfirst(str_replace('_', ' ', $factura->estado)) }}')"
                                                            title="Eliminar factura"
                                                            class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition">
                                                        <svg style="width:18px;height:18px" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="px-5 py-4 border-t border-slate-100">
                        {{ $facturas->links() }}
                    </div>
                @else
                    <div class="text-center py-16">
                        <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
                            <svg class="h-7 w-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <p class="text-slate-600 font-medium">No hay facturas registradas</p>
                        <p class="text-sm text-slate-400 mt-1 mb-5">Prueba ajustando los filtros o crea tu primera factura.</p>
                        <a href="{{ route('facturas.create') }}" class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2.5 rounded-lg text-sm transition shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Crear Primera Factura
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function confirmarEliminacion(facturaId, numeroControl, cliente, total, estado) {
            Swal.fire({
                title: '¿Eliminar esta factura?',
                html: `
                    <div class="text-left space-y-2 mt-4">
                        <div class="bg-slate-50 p-3 rounded">
                            <p class="text-sm"><span class="font-semibold text-slate-700">Número de Control:</span><br>
                            <span class="text-gray-900">${numeroControl}</span></p>
                        </div>
                        <div class="bg-slate-50 p-3 rounded">
                            <p class="text-sm"><span class="font-semibold text-slate-700">Cliente:</span><br>
                            <span class="text-gray-900">${cliente}</span></p>
                        </div>
                        <div class="bg-slate-50 p-3 rounded">
                            <p class="text-sm"><span class="font-semibold text-slate-700">Total:</span><br>
                            <span class="text-gray-900 text-lg font-bold">$${total}</span></p>
                        </div>
                        <div class="bg-slate-50 p-3 rounded">
                            <p class="text-sm"><span class="font-semibold text-slate-700">Estado:</span><br>
                            <span class="text-gray-900">${estado}</span></p>
                        </div>
                    </div>
                    <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded">
                        <p class="text-sm text-red-800 font-semibold">⚠️ Esta acción no se puede deshacer</p>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: '<i class="mr-2">🗑️</i> Sí, eliminar',
                cancelButtonText: 'Cancelar',
                width: '500px',
                customClass: {
                    confirmButton: 'px-6 py-2 text-white font-semibold rounded-lg',
                    cancelButton: 'px-6 py-2 text-white font-semibold rounded-lg'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Eliminando...',
                        text: 'Por favor espere',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    document.getElementById('delete-form-' + facturaId).submit();
                }
            });
        }

        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: '{{ session('success') }}',
                confirmButtonColor: '#10b981',
                timer: 3000,
                timerProgressBar: true,
            });
        @endif

        @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '{{ session('error') }}',
                confirmButtonColor: '#dc2626',
            });
        @endif

        @if(session('warning'))
            Swal.fire({
                icon: 'warning',
                title: 'Advertencia',
                text: '{{ session('warning') }}',
                confirmButtonColor: '#f59e0b',
            });
        @endif

        function exportarExcel() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("facturas.exportar_excel") }}';
            form.style.display = 'none';

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);

            const searchForm = document.querySelector('form[action="{{ route('facturas.index') }}"]');
            const inputs = searchForm.querySelectorAll('input, select');

            inputs.forEach(input => {
                if (input.name && input.value) {
                    const clone = input.cloneNode(true);
                    form.appendChild(clone);
                }
            });

            document.body.appendChild(form);

            Swal.fire({
                title: 'Exportando...',
                html: 'Generando archivo Excel con las facturas filtradas.<br>Por favor espere...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            form.submit();

            setTimeout(() => {
                Swal.close();
                document.body.removeChild(form);
            }, 2000);
        }
    </script>
</body>
</html>
