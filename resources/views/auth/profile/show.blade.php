@extends('layouts.app')

@section('title', 'Mi Perfil')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-[0_1px_3px_0_rgb(0_0_0/0.06),0_4px_12px_-2px_rgb(0_0_0/0.06)] border border-slate-100 p-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-slate-800">Mi Perfil</h1>
        </div>

        <!-- Información del usuario -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Información Personal -->
            <div class="bg-slate-50 rounded-lg p-6">
                <h2 class="text-lg font-semibold text-slate-800 mb-4">📋 Información Personal</h2>
                
                <div class="mb-6">
                    <label class="text-sm font-medium text-slate-500">Nombre Completo</label>
                    <p class="text-gray-900 text-lg font-semibold">
                        {{ $user['name'] ?? 'No disponible' }}
                    </p>
                </div>

                <div class="mb-6">
                    <label class="text-sm font-medium text-slate-500">Correo Electrónico</label>
                    <p class="text-gray-900 text-lg">
                        {{ $user['email'] ?? 'No disponible' }}
                    </p>
                </div>

                @if(isset($user['phone']))
                    <div class="mb-6">
                        <label class="text-sm font-medium text-slate-500">Teléfono</label>
                        <p class="text-gray-900 text-lg">
                            {{ $user['phone'] ?? 'No registrado' }}
                        </p>
                    </div>
                @endif
            </div>

            <!-- Información de Cuenta -->
            <div class="bg-slate-50 rounded-lg p-6">
                <h2 class="text-lg font-semibold text-slate-800 mb-4">🔐 Información de Cuenta</h2>
                
                @if(isset($user['id']))
                    <div class="mb-6">
                        <label class="text-sm font-medium text-slate-500">ID de Usuario</label>
                        <p class="text-gray-900 text-lg font-mono">
                            {{ $user['id'] }}
                        </p>
                    </div>
                @endif

                @if(isset($user['created_at']))
                    <div class="mb-6">
                        <label class="text-sm font-medium text-slate-500">Cuenta Creada</label>
                        <p class="text-gray-900 text-lg">
                            {{ \Carbon\Carbon::parse($user['created_at'])->format('d/m/Y H:i') }}
                        </p>
                    </div>
                @endif

                @if(isset($user['roles']))
                    <div class="mb-6">
                        <label class="text-sm font-medium text-slate-500">Rol</label>
                        <p class="text-gray-900 text-lg">
                            @if(is_array($user['roles']) && count($user['roles']) > 0)
                                <span class="inline-block bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-semibold">
                                    {{ implode(', ', $user['roles']) }}
                                </span>
                            @else
                                Usuario
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Detalles adicionales si existen -->
        @if(isset($user['updated_at']))
            <div class="mt-8 pt-8 border-t border-slate-200">
                <p class="text-sm text-slate-500">
                    <strong>Última actualización:</strong> 
                    {{ \Carbon\Carbon::parse($user['updated_at'])->format('d/m/Y H:i') }}
                </p>
            </div>
        @endif
    </div>

    <!-- Acciones adicionales -->
    <div class="mt-8 grid grid-cols-2 gap-4">
        <a href="{{ route('facturas.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold px-4 py-3 rounded-lg text-center transition">
            ← Volver a Facturas
        </a>
        <form action="{{ route('logout') }}" method="POST" class="w-full">
            @csrf
            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold px-4 py-3 rounded-lg transition">
                🚪 Cerrar Sesión
            </button>
        </form>
    </div>
</div>
@endsection
