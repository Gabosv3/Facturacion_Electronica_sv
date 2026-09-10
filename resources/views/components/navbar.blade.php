<nav class="bg-white/95 backdrop-blur-md border-b border-slate-200 sticky top-0 z-50 mb-6">
    <div class="container mx-auto px-4 sm:px-6">
        <div class="flex items-center justify-between h-16">
            <!-- Logo -->
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 shrink-0">
                <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-600 to-blue-800 flex items-center justify-center shadow-sm">
                    <span class="text-white text-base leading-none">📄</span>
                </span>
                <span class="hidden sm:flex items-center gap-1.5">
                    <span class="text-[15px] font-bold text-slate-800 tracking-tight">DTE El Salvador</span>
                    <span class="text-[10px] font-bold text-blue-700 bg-blue-50 border border-blue-100 px-1.5 py-0.5 rounded-md">v2.0</span>
                </span>
            </a>

            <!-- Enlaces agrupados (desktop) -->
            <div class="hidden lg:flex items-center gap-1">
                <a href="{{ route('home') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('home') ? 'text-blue-700 bg-blue-50' : 'text-slate-600 hover:text-blue-700 hover:bg-slate-50' }}">
                    Home
                </a>

                <!-- Facturación -->
                <div class="relative nav-group" id="navFacturacion">
                    <button type="button" onclick="toggleDropdown('navFacturacionPanel')"
                            class="flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs(['facturas.*','invalidaciones.*']) ? 'text-blue-700 bg-blue-50' : 'text-slate-600 hover:text-blue-700 hover:bg-slate-50' }}">
                        Facturación
                        <svg class="w-3.5 h-3.5 opacity-60" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </button>
                    <div id="navFacturacionPanel" class="dropdown-panel hidden absolute left-0 mt-2 w-56 bg-white rounded-xl shadow-[0_12px_32px_-8px_rgba(30,58,138,0.2)] border border-slate-100 overflow-hidden">
                        <a href="{{ route('facturas.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm {{ request()->routeIs('facturas.*') ? 'text-blue-700 bg-blue-50 font-medium' : 'text-slate-700 hover:bg-slate-50' }} transition">
                            📄 Facturas
                        </a>
                        <a href="{{ route('invalidaciones.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm {{ request()->routeIs('invalidaciones.*') ? 'text-blue-700 bg-blue-50 font-medium' : 'text-slate-700 hover:bg-slate-50' }} transition">
                            ❌ Invalidaciones
                        </a>
                    </div>
                </div>

                <!-- Operaciones -->
                <div class="relative nav-group" id="navOperaciones">
                    <button type="button" onclick="toggleDropdown('navOperacionesPanel')"
                            class="flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs(['consultas.*','contingencias.*']) ? 'text-blue-700 bg-blue-50' : 'text-slate-600 hover:text-blue-700 hover:bg-slate-50' }}">
                        Operaciones
                        <svg class="w-3.5 h-3.5 opacity-60" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </button>
                    <div id="navOperacionesPanel" class="dropdown-panel hidden absolute left-0 mt-2 w-56 bg-white rounded-xl shadow-[0_12px_32px_-8px_rgba(30,58,138,0.2)] border border-slate-100 overflow-hidden">
                        <a href="{{ route('consultas.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm {{ request()->routeIs('consultas.*') ? 'text-blue-700 bg-blue-50 font-medium' : 'text-slate-700 hover:bg-slate-50' }} transition">
                            🔍 Consultas MH
                        </a>
                        <a href="{{ route('contingencias.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm {{ request()->routeIs('contingencias.*') ? 'text-blue-700 bg-blue-50 font-medium' : 'text-slate-700 hover:bg-slate-50' }} transition">
                            🚨 Contingencias
                        </a>
                    </div>
                </div>

                <!-- Herramientas -->
                <div class="relative nav-group" id="navHerramientas">
                    <button type="button" onclick="toggleDropdown('navHerramientasPanel')"
                            class="flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs(['kardex.*','admin.tokens.*']) ? 'text-blue-700 bg-blue-50' : 'text-slate-600 hover:text-blue-700 hover:bg-slate-50' }}">
                        Herramientas
                        <svg class="w-3.5 h-3.5 opacity-60" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </button>
                    <div id="navHerramientasPanel" class="dropdown-panel hidden absolute left-0 mt-2 w-56 bg-white rounded-xl shadow-[0_12px_32px_-8px_rgba(30,58,138,0.2)] border border-slate-100 overflow-hidden">
                        <a href="{{ route('kardex.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm {{ request()->routeIs('kardex.*') ? 'text-blue-700 bg-blue-50 font-medium' : 'text-slate-700 hover:bg-slate-50' }} transition">
                            📦 Kardex
                        </a>
                        <a href="{{ route('admin.tokens.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm {{ request()->routeIs('admin.tokens.*') ? 'text-blue-700 bg-blue-50 font-medium' : 'text-slate-700 hover:bg-slate-50' }} transition">
                            🔑 Tokens
                        </a>
                    </div>
                </div>
            </div>

            <!-- Acciones (derecha) -->
            <div class="flex items-center gap-2">
                <!-- Nuevo documento (desktop) -->
                <div class="hidden md:block relative nav-group" id="createMenuWrap">
                    <button type="button" onclick="toggleDropdown('createMenu')"
                            class="flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white pl-3.5 pr-3 py-2 rounded-lg text-sm font-semibold transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Nuevo
                        <svg class="w-3.5 h-3.5 text-blue-200" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </button>
                    <div id="createMenu" class="dropdown-panel hidden absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-[0_12px_32px_-8px_rgba(30,58,138,0.2)] border border-slate-100 overflow-hidden">
                        <a href="{{ route('facturas.create') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span> Factura (DTE-01)
                        </a>
                        <a href="{{ route('facturas.create_ccf') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> CCF (DTE-03)
                        </a>
                        <a href="{{ route('facturas.create_nota_credito') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition">
                            <span class="w-2 h-2 rounded-full bg-violet-500"></span> Nota de Crédito (DTE-05)
                        </a>
                        <a href="{{ route('facturas.create_sujeto_excluido') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition">
                            <span class="w-2 h-2 rounded-full bg-orange-500"></span> Sujeto Excluido (DTE-14)
                        </a>
                    </div>
                </div>

                <!-- Usuario -->
                <div class="relative nav-group" id="userMenuWrap">
                    <button type="button" onclick="toggleDropdown('userMenu')" class="flex items-center gap-2 pl-1.5 pr-2 py-1.5 rounded-full hover:bg-slate-100 transition">
                        <span class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center text-white text-xs font-bold">
                            @if(session('user_data') && isset(session('user_data')['name']))
                                {{ strtoupper(substr(session('user_data')['name'], 0, 1)) }}
                            @else
                                U
                            @endif
                        </span>
                        <svg class="w-4 h-4 text-slate-400 hidden sm:block" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </button>
                    <div id="userMenu" class="dropdown-panel hidden absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-[0_12px_32px_-8px_rgba(30,58,138,0.2)] border border-slate-100 overflow-hidden">
                        @if(session('user_data'))
                            <div class="px-4 py-3 border-b border-slate-100 bg-slate-50">
                                <p class="font-semibold text-sm text-slate-800 truncate">{{ session('user_data')['name'] ?? 'Usuario' }}</p>
                                <p class="text-xs text-slate-500 truncate">{{ session('user_data')['email'] ?? '' }}</p>
                            </div>
                        @endif
                        <a href="{{ route('profile.show') }}" class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition">
                            👤 Mi Perfil
                        </a>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2.5 text-sm text-red-600 font-medium hover:bg-red-50 transition">
                                🚪 Cerrar Sesión
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Botón hamburguesa (móvil/tablet) -->
                <button type="button" onclick="toggleMobileMenu()" class="lg:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100 transition" aria-label="Abrir menú">
                    <svg id="hamburgerIcon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg id="closeIcon" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Menú móvil (drawer, agrupado por secciones) -->
    <div id="mobileMenu" class="hidden lg:hidden border-t border-slate-100 bg-white max-h-[calc(100vh-4rem)] overflow-y-auto">
        <div class="container mx-auto px-4 py-3 flex flex-col gap-4">

            <a href="{{ route('home') }}" class="px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('home') ? 'text-blue-700 bg-blue-50' : 'text-slate-600' }}">🏠 Home</a>

            <div>
                <p class="px-1 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Facturación</p>
                <div class="flex flex-col gap-0.5">
                    <a href="{{ route('facturas.index') }}" class="px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('facturas.*') ? 'text-blue-700 bg-blue-50' : 'text-slate-600' }}">📄 Facturas</a>
                    <a href="{{ route('invalidaciones.index') }}" class="px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('invalidaciones.*') ? 'text-blue-700 bg-blue-50' : 'text-slate-600' }}">❌ Invalidaciones</a>
                </div>
            </div>

            <div>
                <p class="px-1 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Operaciones</p>
                <div class="flex flex-col gap-0.5">
                    <a href="{{ route('consultas.index') }}" class="px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('consultas.*') ? 'text-blue-700 bg-blue-50' : 'text-slate-600' }}">🔍 Consultas MH</a>
                    <a href="{{ route('contingencias.index') }}" class="px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('contingencias.*') ? 'text-blue-700 bg-blue-50' : 'text-slate-600' }}">🚨 Contingencias</a>
                </div>
            </div>

            <div>
                <p class="px-1 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Herramientas</p>
                <div class="flex flex-col gap-0.5">
                    <a href="{{ route('kardex.index') }}" class="px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('kardex.*') ? 'text-blue-700 bg-blue-50' : 'text-slate-600' }}">📦 Kardex</a>
                    <a href="{{ route('admin.tokens.index') }}" class="px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.tokens.*') ? 'text-blue-700 bg-blue-50' : 'text-slate-600' }}">🔑 Tokens</a>
                </div>
            </div>

            <div class="border-t border-slate-100 pt-3">
                <p class="px-1 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Nuevo documento</p>
                <div class="flex flex-col gap-0.5">
                    <a href="{{ route('facturas.create') }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm text-slate-600">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span> Factura (DTE-01)
                    </a>
                    <a href="{{ route('facturas.create_ccf') }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm text-slate-600">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> CCF (DTE-03)
                    </a>
                    <a href="{{ route('facturas.create_nota_credito') }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm text-slate-600">
                        <span class="w-2 h-2 rounded-full bg-violet-500"></span> Nota de Crédito (DTE-05)
                    </a>
                    <a href="{{ route('facturas.create_sujeto_excluido') }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm text-slate-600">
                        <span class="w-2 h-2 rounded-full bg-orange-500"></span> Sujeto Excluido (DTE-14)
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>

<script>
    function toggleDropdown(panelId) {
        const panel = document.getElementById(panelId);
        if (!panel) return;
        const isOpen = !panel.classList.contains('hidden');
        document.querySelectorAll('.dropdown-panel').forEach(p => p.classList.add('hidden'));
        if (!isOpen) panel.classList.remove('hidden');
    }

    function toggleMobileMenu() {
        const menu = document.getElementById('mobileMenu');
        const hamburger = document.getElementById('hamburgerIcon');
        const close = document.getElementById('closeIcon');
        menu.classList.toggle('hidden');
        hamburger.classList.toggle('hidden');
        close.classList.toggle('hidden');
    }

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.nav-group')) {
            document.querySelectorAll('.dropdown-panel').forEach(p => p.classList.add('hidden'));
        }
    });
</script>
