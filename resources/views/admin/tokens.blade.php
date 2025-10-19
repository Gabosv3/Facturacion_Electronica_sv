<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Tokens MH</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <div class="mb-4">
                <a href="{{ route('facturas.index') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                    ← Volver a Facturas
                </a>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-3xl font-bold text-gray-800">Gestión de Tokens MH</h1>
                    <form action="{{ route('admin.tokens.renovar') }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-lg">
                            🔄 Renovar Token Ahora
                        </button>
                    </form>
                </div>

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

                <!-- Token Activo -->
                @if($tokenActivo)
                    <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">
                        <h2 class="text-xl font-semibold text-green-800 mb-4">✓ Token Activo</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Creado:</p>
                                <p class="font-semibold">{{ $tokenActivo->created_at->format('d/m/Y H:i:s') }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Expira:</p>
                                <p class="font-semibold">{{ $tokenActivo->expires_at->format('d/m/Y H:i:s') }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <p class="text-sm text-gray-600">Tiempo restante:</p>
                                <p class="font-semibold text-lg">
                                    @php
                                        $diff = now()->diff($tokenActivo->expires_at);
                                        echo $diff->h . ' horas ' . $diff->i . ' minutos';
                                    @endphp
                                </p>
                            </div>
                            <div class="md:col-span-2">
                                <p class="text-sm text-gray-600 mb-2">Token (primeros 50 caracteres):</p>
                                <div class="bg-gray-100 p-3 rounded font-mono text-xs break-all">
                                    {{ substr($tokenActivo->token, 0, 50) }}...
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6">
                        <h2 class="text-xl font-semibold text-yellow-800 mb-2">⚠ No hay token activo</h2>
                        <p class="text-gray-700">Genera un nuevo token para poder enviar DTEs al Ministerio de Hacienda.</p>
                    </div>
                @endif

                <!-- Información del Sistema -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                    <h2 class="text-xl font-semibold text-blue-800 mb-4">ℹ️ Información del Sistema</h2>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Usuario MH:</span>
                            <span class="font-semibold">{{ config('dte.mh_user') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">URL Autenticación:</span>
                            <span class="font-mono text-sm">{{ config('dte.mh_auth_url') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">URL Recepción:</span>
                            <span class="font-mono text-sm">{{ config('dte.mh_recepcion_url') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Ambiente:</span>
                            <span class="font-semibold">
                                {{ config('dte.ambiente') == '00' ? 'Pruebas' : 'Producción' }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Renovación automática:</span>
                            <span class="font-semibold">Diariamente a las 2:00 AM</span>
                        </div>
                    </div>
                </div>

                <!-- Historial de Tokens -->
                <div class="mb-6">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">📜 Historial de Tokens</h2>
                    
                    @if($tokens->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">ID</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Creado</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Expira</th>
                                        <th class="px-4 py-3 text-center text-sm font-semibold text-gray-600">Estado</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Token</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($tokens as $token)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $token->id }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $token->created_at->format('d/m/Y H:i') }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $token->expires_at->format('d/m/Y H:i') }}</td>
                                        <td class="px-4 py-3 text-center">
                                            @if($token->isValid())
                                                <span class="px-3 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">Válido</span>
                                            @elseif($token->isExpired())
                                                <span class="px-3 py-1 bg-gray-100 text-gray-800 text-xs font-semibold rounded-full">Expirado</span>
                                            @else
                                                <span class="px-3 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full">Inactivo</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm font-mono text-gray-600">{{ substr($token->token, 0, 30) }}...</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-6">
                            {{ $tokens->links() }}
                        </div>
                    @else
                        <div class="text-center py-8 text-gray-500">
                            No hay tokens registrados
                        </div>
                    @endif
                </div>

                <!-- Comandos útiles -->
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">💻 Comandos Artisan</h2>
                    <div class="space-y-3">
                        <div class="bg-white p-3 rounded border">
                            <p class="text-sm text-gray-600 mb-1">Renovar token manualmente:</p>
                            <code class="text-sm bg-gray-800 text-green-400 px-3 py-2 rounded block">php artisan mh:renovar-token</code>
                        </div>
                        <div class="bg-white p-3 rounded border">
                            <p class="text-sm text-gray-600 mb-1">Ejecutar tareas programadas:</p>
                            <code class="text-sm bg-gray-800 text-green-400 px-3 py-2 rounded block">php artisan schedule:work</code>
                        </div>
                        <div class="bg-white p-3 rounded border">
                            <p class="text-sm text-gray-600 mb-1">Ver logs:</p>
                            <code class="text-sm bg-gray-800 text-green-400 px-3 py-2 rounded block">tail -f storage/logs/laravel.log</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>