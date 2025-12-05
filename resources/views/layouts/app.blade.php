<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Factura Electrónica')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .navbar-dropdown {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 200px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 0.375rem;
        }

        .navbar-dropdown.show {
            display: block;
        }

        .navbar-dropdown a, .navbar-dropdown button {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            width: 100%;
            text-align: left;
        }

        .navbar-dropdown a:hover, .navbar-dropdown button:hover {
            background-color: #f3f4f6;
        }

        .navbar-dropdown button {
            border: none;
            background: none;
            cursor: pointer;
            font-size: 0.875rem;
        }

        .navbar-user-btn {
            cursor: pointer;
        }
    </style>
    @yield('extra_styles')
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <!-- Logo/Nombre del Sistema -->
                <div class="flex items-center space-x-2">
                    <div class="text-2xl font-bold text-blue-600">📄</div>
                    <a href="{{ route('facturas.index') }}" class="text-xl font-bold text-gray-800 hover:text-blue-600">
                        Factura Electrónica
                    </a>
                </div>

                <!-- Enlaces de navegación -->
                <div class="hidden md:flex space-x-6">
                    <a href="{{ route('facturas.index') }}" class="text-gray-600 hover:text-blue-600 font-medium transition">
                        Facturas
                    </a>
                    <a href="{{ route('consultas.index') }}" class="text-gray-600 hover:text-blue-600 font-medium transition">
                        Consultas
                    </a>
                    <a href="{{ route('invalidaciones.index') }}" class="text-gray-600 hover:text-blue-600 font-medium transition">
                        Invalidaciones
                    </a>
                    <a href="{{ route('contingencias.index') }}" class="text-gray-600 hover:text-blue-600 font-medium transition">
                        Contingencias
                    </a>
                    <a href="{{ route('admin.tokens.index') }}" class="text-gray-600 hover:text-blue-600 font-medium transition">
                        Tokens
                    </a>
                </div>

                <!-- Menú de usuario -->
                <div class="relative">
                    <button class="navbar-user-btn flex items-center space-x-2 px-3 py-2 rounded-md hover:bg-gray-100 transition" onclick="toggleUserMenu()">
                        <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white text-sm font-bold">
                            @if(session('user_data') && isset(session('user_data')['name']))
                                {{ strtoupper(substr(session('user_data')['name'], 0, 1)) }}
                            @else
                                U
                            @endif
                        </div>
                        <span class="text-gray-700 text-sm">
                            @if(session('user_data') && isset(session('user_data')['name']))
                                {{ session('user_data')['name'] }}
                            @else
                                Usuario
                            @endif
                        </span>
                        <svg class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div id="userMenu" class="navbar-dropdown">
                        @if(session('user_data') && isset(session('user_data')['email']))
                            <div class="px-4 py-2 text-sm text-gray-600 border-b">
                                {{ session('user_data')['email'] }}
                            </div>
                        @endif
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full text-red-600 hover:bg-red-50">
                                🚪 Cerrar Sesión
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenido Principal -->
    <div class="container mx-auto px-4 py-8">
        <!-- Alertas de sesión -->
        @if(session('success'))
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg flex justify-between items-center">
                <div>
                    <strong class="font-bold">¡Éxito!</strong>
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.style.display='none';" class="text-green-700 hover:text-green-900">
                    ✕
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg flex justify-between items-center">
                <div>
                    <strong class="font-bold">Error</strong>
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.style.display='none';" class="text-red-700 hover:text-red-900">
                    ✕
                </button>
            </div>
        @endif

        @if(session('warning'))
            <div class="mb-6 bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded-lg flex justify-between items-center">
                <div>
                    <strong class="font-bold">Advertencia</strong>
                    <span class="block sm:inline">{{ session('warning') }}</span>
                </div>
                <button onclick="this.parentElement.style.display='none';" class="text-yellow-700 hover:text-yellow-900">
                    ✕
                </button>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                <strong class="font-bold">Errores de validación:</strong>
                <ul class="mt-2 list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Contenido de la página -->
        @yield('content')
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-12">
        <div class="container mx-auto px-4 py-6 text-center">
            <p class="text-gray-400">
                &copy; {{ date('Y') }} Factura Electrónica. Todos los derechos reservados.
            </p>
        </div>
    </footer>

    <script>
        function toggleUserMenu() {
            const menu = document.getElementById('userMenu');
            menu.classList.toggle('show');
        }

        // Cerrar el menú al hacer clic fuera
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('userMenu');
            const btn = document.querySelector('.navbar-user-btn');
            
            if (!btn.contains(event.target) && !menu.contains(event.target)) {
                menu.classList.remove('show');
            }
        });

        // Auto-cerrar alertas después de 5 segundos
        document.querySelectorAll('[role="alert"]').forEach(alert => {
            setTimeout(() => {
                if (alert.style.display !== 'none') {
                    alert.style.display = 'none';
                }
            }, 5000);
        });
    </script>

    @yield('extra_scripts')
</body>
</html>
