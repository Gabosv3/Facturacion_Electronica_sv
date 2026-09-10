<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contingencias - DTE El Salvador</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-slate-50">
    @include('components.navbar')

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-xl shadow-[0_1px_3px_0_rgb(0_0_0/0.06),0_4px_12px_-2px_rgb(0_0_0/0.06)] border border-slate-100 p-6 mb-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800">Gestión de Contingencias</h1>
                        <p class="text-slate-500 mt-2">Administrar eventos de fuerza mayor que impiden el envío de DTEs al MH</p>
                    </div>
                    <div class="flex gap-3">
                        @if(!$eventoActivo)
                        <a href="{{ route('contingencias.create') }}" class="bg-red-600 hover:bg-red-700 text-white font-semibold px-6 py-3 rounded-lg flex items-center gap-2">
                            <span>🚨</span>
                            <span>Activar Contingencia</span>
                        </a>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Alerta de Contingencia Activa -->
            @if($eventoActivo)
            <div class="bg-yellow-50 border-l-4 border-yellow-500 p-6 mb-6 rounded-lg shadow">
                <div class="flex items-start">
                    <div class="text-4xl mr-4">⚠️</div>
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-yellow-800 mb-2">MODO CONTINGENCIA ACTIVO</h3>
                        <p class="text-yellow-700 mb-3">
                            <strong>Evento #{{ $eventoActivo->id }}:</strong> {{ $eventoActivo->tipo_contingencia_descripcion }}
                        </p>
                        <p class="text-yellow-700 mb-3">
                            <strong>Inicio:</strong> {{ $eventoActivo->fecha_hora_inicio->format('d/m/Y H:i:s') }}
                        </p>
                        <p class="text-yellow-700 mb-4">
                            <strong>DTEs emitidos:</strong> {{ $eventoActivo->facturas()->count() }} facturas en contingencia
                        </p>
                        <div class="flex gap-3">
                            <a href="{{ route('contingencias.show', $eventoActivo) }}" class="bg-yellow-600 hover:bg-yellow-700 text-white font-semibold px-4 py-2 rounded-lg">
                                Ver Detalles
                            </a>
                            <form action="{{ route('contingencias.cerrar', $eventoActivo) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold px-4 py-2 rounded-lg" onclick="return confirm('¿Estás seguro de cerrar esta contingencia? No podrás agregar más DTEs.')">
                                    🔒 Cerrar Contingencia
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Mensajes -->
            @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
            @endif

            @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
            @endif

            @if(session('info'))
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4">
                {{ session('info') }}
            </div>
            @endif

            @if(session('warning'))
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                {{ session('warning') }}
            </div>
            @endif

            <!-- Lista de Eventos -->
            <div class="bg-white rounded-xl shadow-[0_1px_3px_0_rgb(0_0_0/0.06),0_4px_12px_-2px_rgb(0_0_0/0.06)] border border-slate-100 p-6">
                <h2 class="text-2xl font-bold text-slate-800 mb-4">Historial de Contingencias</h2>

                @if($eventos->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-500">ID</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-500">Tipo</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-500">Inicio</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-500">Fin</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold text-slate-500">DTEs</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold text-slate-500">Estado</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold text-slate-500">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($eventos as $evento)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 text-sm font-mono">#{{ $evento->id }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="font-semibold">{{ $evento->tipo_contingencia }}</span> - {{ $evento->tipo_contingencia_descripcion }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">{{ $evento->fecha_hora_inicio->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        @if($evento->fecha_hora_fin)
                                            {{ $evento->fecha_hora_fin->format('d/m/Y H:i') }}
                                        @else
                                            <span class="text-yellow-600">En curso</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm font-semibold">{{ $evento->cantidad_dte }}</td>
                                    <td class="px-4 py-3 text-center text-sm">
                                        @if($evento->estado == 'activo')
                                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded">🚨 ACTIVO</span>
                                        @elseif($evento->estado == 'cerrado')
                                            <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded">🔒 CERRADO</span>
                                        @elseif($evento->estado == 'enviado')
                                            <span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs font-semibold rounded">📤 ENVIADO</span>
                                        @elseif($evento->estado == 'procesado')
                                            <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded">✅ PROCESADO</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm">
                                        <a href="{{ route('contingencias.show', $evento) }}" class="text-blue-600 hover:text-blue-800 font-semibold">
                                            Ver Detalles →
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $eventos->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="text-6xl mb-4">📋</div>
                        <p class="text-xl text-slate-500 mb-4">No hay eventos de contingencia registrados</p>
                        <p class="text-slate-500">Las contingencias se activan cuando hay eventos de fuerza mayor que impiden el envío de DTEs al MH</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
