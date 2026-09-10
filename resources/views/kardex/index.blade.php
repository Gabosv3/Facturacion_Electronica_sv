@extends('layouts.app')

@section('title', 'Kardex - Factura Electrónica')

@section('content')
<div class="max-w-7xl mx-auto">

    {{-- Encabezado --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">📦 Kardex</h1>
            <p class="text-gray-500 text-sm mt-1">Entradas (compras) y salidas (facturas emitidas)</p>
        </div>
        <form method="POST" action="{{ route('kardex.exportar') }}" id="formExportar">
            @csrf
            <input type="hidden" name="from" value="{{ $from }}">
            <input type="hidden" name="to" value="{{ $to }}">
            <input type="hidden" name="codigo_producto" value="{{ $codigoProducto }}">
            <input type="hidden" name="proveedor_id" value="{{ $proveedorId }}">
            <button type="submit"
                class="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg transition flex items-center gap-2">
                📥 Exportar CSV
            </button>
        </form>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <form method="GET" action="{{ route('kardex.index') }}" class="grid grid-cols-2 md:grid-cols-6 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Desde</label>
                <input type="date" name="from" value="{{ $from }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Hasta</label>
                <input type="date" name="to" value="{{ $to }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Código producto</label>
                <input type="text" name="codigo_producto" value="{{ $codigoProducto }}" placeholder="Ej: ACEITE-001"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Tipo de ítem</label>
                <select name="tipo_item"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="ambos"    {{ $tipoItem === 'ambos'    ? 'selected' : '' }}>Productos y Servicios</option>
                    <option value="producto" {{ $tipoItem === 'producto' ? 'selected' : '' }}>Solo Productos</option>
                    <option value="servicio" {{ $tipoItem === 'servicio' ? 'selected' : '' }}>Solo Servicios</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Vista</label>
                <select name="vista"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="movimientos" {{ $vista === 'movimientos' ? 'selected' : '' }}>Movimientos</option>
                    <option value="resumen"     {{ $vista === 'resumen'     ? 'selected' : '' }}>Resumen por producto</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-lg transition text-sm">
                    🔍 Filtrar
                </button>
            </div>
        </form>
    </div>

    {{-- Error API entradas --}}
    @if($errorEntradas === 'SESSION_EXPIRED')
    <div class="mb-4 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-lg text-sm flex items-center justify-between">
        <div>
            <strong>⚠ Tu sesión con el sistema de compras ha expirado.</strong>
            Las entradas no se pueden cargar. Cierra sesión e ingresa de nuevo para renovar el token.
        </div>
        <form action="{{ route('logout') }}" method="POST" class="ml-4">
            @csrf
            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white text-xs font-medium px-3 py-1.5 rounded-lg transition">
                Cerrar sesión
            </button>
        </form>
    </div>
    @elseif(!empty($errorEntradas))
    <div class="mb-4 bg-yellow-50 border border-yellow-300 text-yellow-800 px-4 py-3 rounded-lg text-sm">
        <strong>⚠ No se pudieron cargar las entradas (compras):</strong> {{ $errorEntradas }}
    </div>
    @endif

    {{-- Tarjetas resumen --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-green-50 border border-green-200 rounded-xl p-4">
            <p class="text-xs text-green-600 font-medium uppercase tracking-wide">Total Entradas</p>
            <p class="text-2xl font-bold text-green-700 mt-1">${{ number_format($totalEntradas, 2) }}</p>
            <p class="text-xs text-green-500 mt-1">{{ collect($movimientos)->where('tipo','entrada')->count() }} movimientos</p>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4">
            <p class="text-xs text-red-600 font-medium uppercase tracking-wide">Total Salidas</p>
            <p class="text-2xl font-bold text-red-700 mt-1">${{ number_format($totalSalidas, 2) }}</p>
            <p class="text-xs text-red-500 mt-1">{{ collect($movimientos)->where('tipo','salida')->count() }} movimientos</p>
        </div>
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
            <p class="text-xs text-blue-600 font-medium uppercase tracking-wide">Balance</p>
            <p class="text-2xl font-bold text-blue-700 mt-1">${{ number_format($totalEntradas - $totalSalidas, 2) }}</p>
            <p class="text-xs text-blue-500 mt-1">{{ count($movimientos) }} movimientos totales</p>
        </div>
    </div>

    @if($vista === 'resumen')
    {{-- Vista resumen por producto --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-800">Resumen por Producto</h2>
            <span class="text-sm text-gray-500">{{ count($resumen) }} productos</span>
        </div>
        @if(count($resumen) > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-3 text-left">Código</th>
                        <th class="px-4 py-3 text-left">Descripción</th>
                        <th class="px-4 py-3 text-right">Cant. Entradas</th>
                        <th class="px-4 py-3 text-right">Total Compras</th>
                        <th class="px-4 py-3 text-right">Cant. Salidas</th>
                        <th class="px-4 py-3 text-right">Total Ventas</th>
                        <th class="px-4 py-3 text-right">Utilidad</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($resumen as $prod)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 font-mono text-gray-700">{{ $prod['codigo'] }}</td>
                        <td class="px-4 py-3 text-gray-800">{{ $prod['descripcion'] }}</td>
                        <td class="px-4 py-3 text-right text-green-700 font-medium">{{ number_format($prod['entradas'], 2) }}</td>
                        <td class="px-4 py-3 text-right text-green-700">${{ number_format($prod['total_compras'], 2) }}</td>
                        <td class="px-4 py-3 text-right text-red-700 font-medium">{{ number_format($prod['salidas'], 2) }}</td>
                        <td class="px-4 py-3 text-right text-red-700">${{ number_format($prod['total_ventas'], 2) }}</td>
                        <td class="px-4 py-3 text-right font-semibold {{ ($prod['total_ventas'] - $prod['total_compras']) >= 0 ? 'text-blue-700' : 'text-red-700' }}">
                            ${{ number_format($prod['total_ventas'] - $prod['total_compras'], 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="px-6 py-12 text-center text-gray-400">
            <p class="text-4xl mb-3">📭</p>
            <p>No hay movimientos en el período seleccionado.</p>
        </div>
        @endif
    </div>

    @else
    {{-- Vista movimientos --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-800">Movimientos</h2>
            <span class="text-sm text-gray-500">{{ count($movimientos) }} registros</span>
        </div>
        @if(count($movimientos) > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-3 text-left">Tipo</th>
                        <th class="px-4 py-3 text-left">Fecha</th>
                        <th class="px-4 py-3 text-left">N° Control / Orden</th>
                        <th class="px-4 py-3 text-left">Cód. Generación</th>
                        <th class="px-4 py-3 text-left">Documento</th>
                        <th class="px-4 py-3 text-left">Proveedor/Cliente</th>
                        <th class="px-4 py-3 text-left">Código</th>
                        <th class="px-4 py-3 text-left">Descripción</th>
                        <th class="px-4 py-3 text-right">Cantidad</th>
                        <th class="px-4 py-3 text-right">Precio Unit.</th>
                        <th class="px-4 py-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($movimientos as $mov)
                    <tr class="hover:bg-gray-50 transition {{ ($mov['es_servicio'] ?? false) ? 'bg-gray-50' : '' }}">
                        <td class="px-4 py-3">
                            @if($mov['tipo'] === 'entrada')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">↑ Entrada</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">↓ Salida</span>
                            @endif
                            @if($mov['es_servicio'] ?? false)
                                <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700">Servicio</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $mov['fecha'] }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-700">
                            @if($mov['tipo'] === 'salida' && isset($mov['factura_id']))
                                <a href="{{ route('facturas.show', $mov['factura_id']) }}"
                                   class="text-blue-600 hover:underline">{{ $mov['referencia'] }}</a>
                            @else
                                {{ $mov['referencia'] }}
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-500">
                            @if(!empty($mov['codigo_generacion']))
                                <span title="{{ $mov['codigo_generacion'] }}">
                                    {{ strtoupper(substr($mov['codigo_generacion'], 0, 8)) }}…
                                </span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $mov['tipo_documento'] }}</td>
                        <td class="px-4 py-3 text-gray-700 max-w-xs truncate">
                            {{ $mov['proveedor'] ?? $mov['cliente'] ?? '-' }}
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $mov['codigo'] }}</td>
                        <td class="px-4 py-3 text-gray-800">{{ $mov['descripcion'] }}</td>
                        <td class="px-4 py-3 text-right text-gray-700">{{ number_format($mov['cantidad'], 2) }}</td>
                        <td class="px-4 py-3 text-right text-gray-700">${{ number_format($mov['precio_unitario'], 2) }}</td>
                        <td class="px-4 py-3 text-right font-semibold {{ $mov['tipo'] === 'entrada' ? 'text-green-700' : 'text-red-700' }}">
                            ${{ number_format($mov['total'], 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="px-6 py-12 text-center text-gray-400">
            <p class="text-4xl mb-3">📭</p>
            <p>No hay movimientos en el período seleccionado.</p>
        </div>
        @endif
    </div>
    @endif

</div>
@endsection
