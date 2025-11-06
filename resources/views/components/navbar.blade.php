<nav class="bg-gray-800 text-white shadow-lg mb-6">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between py-4">
            <div class="flex items-center gap-6">
                <a href="{{ route('facturas.index') }}" class="text-xl font-bold hover:text-gray-300">
                    📄 DTE El Salvador
                </a>
                <div class="flex gap-4">
                    <a href="{{ route('facturas.index') }}" class="hover:text-gray-300 px-3 py-2 rounded {{ request()->routeIs('facturas.*') ? 'bg-gray-700' : '' }}">
                        Facturas
                    </a>
                    <a href="{{ route('invalidaciones.index') }}" class="hover:text-gray-300 px-3 py-2 rounded {{ request()->routeIs('invalidaciones.*') ? 'bg-gray-700' : '' }}">
                        ❌ Invalidaciones
                    </a>
                    <a href="{{ route('consultas.index') }}" class="hover:text-gray-300 px-3 py-2 rounded {{ request()->routeIs('consultas.*') ? 'bg-gray-700' : '' }}">
                        🔍 Consultas MH
                    </a>
                    <a href="{{ route('contingencias.index') }}" class="hover:text-gray-300 px-3 py-2 rounded {{ request()->routeIs('contingencias.*') ? 'bg-gray-700' : '' }}">
                        🚨 Contingencias
                    </a>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('facturas.create') }}" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded text-sm">
                    + Factura
                </a>
                <a href="{{ route('facturas.create_ccf') }}" class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded text-sm">
                    + CCF
                </a>
                <a href="{{ route('facturas.create_nota_credito') }}" class="bg-purple-600 hover:bg-purple-700 px-4 py-2 rounded text-sm">
                    + Nota Crédito
                </a>
                <a href="{{ route('facturas.create_sujeto_excluido') }}" class="bg-orange-600 hover:bg-orange-700 px-4 py-2 rounded text-sm">
                    + Sujeto Excluido
                </a>
            </div>
        </div>
    </div>
</nav>
