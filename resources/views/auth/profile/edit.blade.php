@extends('layouts.app')

@section('title', 'Editar Perfil')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-[0_1px_3px_0_rgb(0_0_0/0.06),0_4px_12px_-2px_rgb(0_0_0/0.06)] border border-slate-100 p-8">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold text-slate-800">Editar Perfil</h1>
        </div>

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

        <form action="{{ route('profile.update') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Nombre Completo -->
            <div class="form-group">
                <label for="name" class="block text-sm font-medium text-slate-700 mb-2">
                    Nombre Completo *
                </label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name', $user['name'] ?? '') }}"
                    required
                    class="w-full px-4 py-2 border @error('name') border-red-500 @else border-slate-200 @enderror rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    placeholder="Tu nombre completo"
                >
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Correo Electrónico -->
            <div class="form-group">
                <label for="email" class="block text-sm font-medium text-slate-700 mb-2">
                    Correo Electrónico *
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email', $user['email'] ?? '') }}"
                    required
                    class="w-full px-4 py-2 border @error('email') border-red-500 @else border-slate-200 @enderror rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    placeholder="tu@email.com"
                >
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Teléfono -->
            <div class="form-group">
                <label for="phone" class="block text-sm font-medium text-slate-700 mb-2">
                    Teléfono (Opcional)
                </label>
                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    value="{{ old('phone', $user['phone'] ?? '') }}"
                    class="w-full px-4 py-2 border @error('phone') border-red-500 @else border-slate-200 @enderror rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    placeholder="+503 XXXX-XXXX"
                >
                @error('phone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Nota importante -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
                <p class="text-sm text-blue-700">
                    <strong>ℹ️ Nota:</strong> Algunos campos de tu perfil pueden estar sincronizados con el sistema de Motors González y no pueden ser editados aquí.
                </p>
            </div>

            <!-- Botones de acción -->
            <div class="flex gap-4 mt-8 pt-6 border-t border-slate-200">
                <a href="{{ route('profile.show') }}" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white font-semibold px-4 py-3 rounded-lg text-center transition">
                    ← Cancelar
                </a>
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-3 rounded-lg transition">
                    ✓ Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('extra_scripts')
<script>
    // Validación en cliente
    const form = document.querySelector('form');
    
    form.addEventListener('submit', function(e) {
        const name = document.getElementById('name').value.trim();
        const email = document.getElementById('email').value.trim();

        if (!name) {
            e.preventDefault();
            alert('El nombre es requerido');
            return false;
        }

        if (!email) {
            e.preventDefault();
            alert('El email es requerido');
            return false;
        }

        // Validar formato de email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            e.preventDefault();
            alert('Por favor ingresa un email válido');
            return false;
        }
    });

    // Limitar teléfono a números
    document.getElementById('phone').addEventListener('input', function(e) {
        const value = e.target.value.replace(/[^0-9\s\-\+()]/g, '');
        e.target.value = value;
    });
</script>
@endsection
