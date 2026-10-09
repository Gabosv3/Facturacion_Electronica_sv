@extends('layouts.app')

@section('title', 'Nueva Cotización - Factura Electrónica')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('cotizaciones.index') }}" class="text-slate-400 hover:text-slate-600 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Nueva Cotización</h1>
            <p class="text-sm text-slate-500 mt-0.5">Presupuesto informal — no genera ningún documento fiscal</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-[0_1px_3px_0_rgb(0_0_0/0.06),0_4px_12px_-2px_rgb(0_0_0/0.06)] border border-slate-100 p-6 sm:p-8">
        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-5 text-sm">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('cotizaciones.store') }}" method="POST" id="cotizacionForm">
            @csrf

            <!-- Cliente -->
            <div class="mb-8">
                <h2 class="text-sm font-bold text-slate-700 mb-4 pb-2 border-b border-slate-100 uppercase tracking-wide">Datos del Cliente</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Nombre *</label>
                        <input type="text" name="cliente_nombre" required value="{{ old('cliente_nombre') }}"
                               class="w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Documento (opcional)</label>
                        <input type="text" name="cliente_documento" value="{{ old('cliente_documento') }}"
                               class="w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Teléfono</label>
                        <input type="text" name="cliente_telefono" value="{{ old('cliente_telefono') }}" placeholder="7777-7777"
                               class="w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Correo</label>
                        <input type="email" name="cliente_correo" value="{{ old('cliente_correo') }}"
                               class="w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500">
                    </div>
                </div>
            </div>

            <!-- Items -->
            <div class="mb-8">
                <h2 class="text-sm font-bold text-slate-700 mb-4 pb-2 border-b border-slate-100 uppercase tracking-wide">Productos/Servicios</h2>
                <div id="itemsContainer">
                    <div class="item-row bg-slate-50 border border-slate-100 p-4 rounded-xl mb-4">
                        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                            <div class="md:col-span-3">
                                <label class="block text-sm font-medium text-slate-700 mb-2">Descripción *</label>
                                <input type="text" name="items[0][descripcion]" class="descripcion w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Cantidad *</label>
                                <input type="number" step="0.01" min="0.01" name="items[0][cantidad]" value="1" class="cantidad w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Precio Unit. *</label>
                                <input type="number" step="0.01" min="0" name="items[0][precio_unitario]" class="precio w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Descuento</label>
                                <input type="number" step="0.01" min="0" name="items[0][monto_descuento]" value="0" class="descuento w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500">
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" id="addItem" class="flex items-center gap-1.5 bg-white hover:bg-slate-50 text-blue-600 font-medium text-sm px-4 py-2 rounded-lg border border-blue-200 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Agregar Item
                </button>
            </div>

            <!-- Observaciones -->
            <div class="mb-8">
                <label class="block text-sm font-medium text-slate-700 mb-2">Observaciones</label>
                <textarea name="observaciones" rows="2" class="w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500">{{ old('observaciones') }}</textarea>
            </div>

            <!-- Resumen -->
            <div class="bg-slate-50 border border-slate-100 p-5 rounded-xl mb-6">
                <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wide mb-3">Resumen</h3>
                <div class="flex justify-between text-xl font-bold text-blue-600">
                    <span>Total:</span>
                    <span id="totalCotizacion">$0.00</span>
                </div>
                <p class="text-xs text-slate-400 mt-1.5">Sin IVA — esto es solo un presupuesto informal.</p>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-lg transition shadow-sm">
                    Guardar Cotización
                </button>
                <a href="{{ route('cotizaciones.index') }}" class="bg-white hover:bg-slate-50 text-slate-600 font-medium px-6 py-3 rounded-lg border border-slate-200 transition">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

@section('extra_scripts')
<script>
    let itemCount = 1;

    function calcularTotalCotizacion() {
        let total = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            const cantidad = parseFloat(row.querySelector('.cantidad').value) || 0;
            const precio = parseFloat(row.querySelector('.precio').value) || 0;
            const descuento = parseFloat(row.querySelector('.descuento').value) || 0;
            total += (cantidad * precio) - descuento;
        });
        document.getElementById('totalCotizacion').textContent = '$' + total.toFixed(2);
    }

    function attachRowEvents(row) {
        row.querySelectorAll('.cantidad, .precio, .descuento').forEach(input => {
            input.addEventListener('input', calcularTotalCotizacion);
        });
    }

    document.querySelectorAll('.item-row').forEach(attachRowEvents);

    document.getElementById('addItem').addEventListener('click', function () {
        const container = document.getElementById('itemsContainer');
        const nuevaFila = document.createElement('div');
        nuevaFila.className = 'item-row bg-slate-50 border border-slate-100 p-4 rounded-xl mb-4';
        nuevaFila.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Descripción *</label>
                    <input type="text" name="items[${itemCount}][descripcion]" class="descripcion w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Cantidad *</label>
                    <input type="number" step="0.01" min="0.01" name="items[${itemCount}][cantidad]" value="1" class="cantidad w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Precio Unit. *</label>
                    <input type="number" step="0.01" min="0" name="items[${itemCount}][precio_unitario]" class="precio w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Descuento</label>
                    <input type="number" step="0.01" min="0" name="items[${itemCount}][monto_descuento]" value="0" class="descuento w-full border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500">
                </div>
            </div>
            <button type="button" class="removeItem mt-2 text-red-500 hover:text-red-700 text-sm">Eliminar</button>
        `;
        container.appendChild(nuevaFila);
        attachRowEvents(nuevaFila);
        itemCount++;
    });

    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('removeItem')) {
            e.target.closest('.item-row').remove();
            calcularTotalCotizacion();
        }
    });
</script>
@endsection
@endsection
