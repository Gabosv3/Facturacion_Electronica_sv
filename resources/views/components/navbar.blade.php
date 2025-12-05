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
            <div class="flex gap-2 items-center">
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
                
                <!-- Separador -->
                <div class="border-l border-gray-700 h-6 mx-2"></div>
                
                <!-- Menú de usuario -->
                <div class="relative group">
                    <button class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700 transition">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-sm">
                            @if(session('user_data') && isset(session('user_data')['name']))
                                {{ substr(session('user_data')['name'], 0, 15) }}
                            @else
                                Usuario
                            @endif
                        </span>
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <div class="absolute right-0 mt-0 w-48 bg-white text-gray-800 rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition duration-200 z-50">
                        @if(session('user_data'))
                            <div class="px-4 py-3 border-b border-gray-200">
                                <p class="font-semibold text-sm">
                                    @if(isset(session('user_data')['name']))
                                        {{ session('user_data')['name'] }}
                                    @else
                                        Usuario
                                    @endif
                                </p>
                                <p class="text-xs text-gray-600">
                                    @if(isset(session('user_data')['email']))
                                        {{ session('user_data')['email'] }}
                                    @else
                                        usuario@example.com
                                    @endif
                                </p>
                            </div>
                        @endif
                        
                        <a href="{{ route('profile.show') }}" class="block px-4 py-2 text-sm hover:bg-gray-100 first:rounded-t-lg">
                            👤 Mi Perfil
                        </a>
                        
                        <form action="{{ route('logout') }}" method="POST" class="block">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100 text-red-600 font-medium last:rounded-b-lg">
                                🚪 Cerrar Sesión
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>
