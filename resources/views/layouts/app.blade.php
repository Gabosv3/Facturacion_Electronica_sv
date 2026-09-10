<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Factura Electrónica')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    boxShadow: {
                        soft: '0 1px 2px 0 rgb(0 0 0 / 0.04), 0 1px 3px 0 rgb(0 0 0 / 0.06)',
                    },
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }

        .app-alert {
            animation: alertIn .2s ease-out;
        }
        @keyframes alertIn {
            from { opacity: 0; transform: translateY(-6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
    @yield('extra_styles')
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
    @include('components.navbar')

    <!-- Contenido Principal -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Alertas de sesión -->
        @if(session('success'))
            <div class="app-alert mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3.5 rounded-xl shadow-soft flex justify-between items-start gap-3">
                <div class="flex items-start gap-3">
                    <span class="text-emerald-500 text-lg leading-none mt-0.5">✅</span>
                    <div>
                        <strong class="font-semibold block">¡Éxito!</strong>
                        <span class="text-sm">{{ session('success') }}</span>
                    </div>
                </div>
                <button onclick="this.closest('.app-alert').style.display='none';" class="text-emerald-500 hover:text-emerald-700 shrink-0">
                    ✕
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="app-alert mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3.5 rounded-xl shadow-soft flex justify-between items-start gap-3">
                <div class="flex items-start gap-3">
                    <span class="text-red-500 text-lg leading-none mt-0.5">⚠️</span>
                    <div>
                        <strong class="font-semibold block">Error</strong>
                        <span class="text-sm">{{ session('error') }}</span>
                    </div>
                </div>
                <button onclick="this.closest('.app-alert').style.display='none';" class="text-red-500 hover:text-red-700 shrink-0">
                    ✕
                </button>
            </div>
        @endif

        @if(session('warning'))
            <div class="app-alert mb-6 bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3.5 rounded-xl shadow-soft flex justify-between items-start gap-3">
                <div class="flex items-start gap-3">
                    <span class="text-amber-500 text-lg leading-none mt-0.5">🔔</span>
                    <div>
                        <strong class="font-semibold block">Advertencia</strong>
                        <span class="text-sm">{{ session('warning') }}</span>
                    </div>
                </div>
                <button onclick="this.closest('.app-alert').style.display='none';" class="text-amber-500 hover:text-amber-700 shrink-0">
                    ✕
                </button>
            </div>
        @endif

        @if($errors->any())
            <div class="app-alert mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3.5 rounded-xl shadow-soft">
                <strong class="font-semibold flex items-center gap-2"><span class="text-lg">⚠️</span> Errores de validación</strong>
                <ul class="mt-2 ml-7 list-disc text-sm space-y-0.5">
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
    <footer class="bg-slate-900 text-slate-300 mt-16 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col sm:flex-row items-center justify-between gap-2">
            <p class="text-slate-400 text-sm">
                &copy; {{ date('Y') }} Factura Electrónica &mdash; Sistema de Facturación DTE El Salvador
            </p>
            <p class="text-slate-500 text-xs font-medium">v2.0</p>
        </div>
    </footer>

    <script>
        // Auto-cerrar alertas después de 5 segundos
        document.querySelectorAll('.app-alert').forEach(alert => {
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
