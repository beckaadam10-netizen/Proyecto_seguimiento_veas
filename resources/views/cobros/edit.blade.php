@extends('layouts.app')

@section('title', 'Editar Cobro')
@section('header', 'Editar Cobro')

@section('content')
<div class="max-w-lg">
    <form method="POST" action="{{ route('cobros.update', $cobro) }}" class="space-y-6">
        @csrf @method('PUT')

        <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
            <h3 class="font-semibold text-gray-700 border-b pb-2">
                Cobro para <span class="font-mono text-xs text-gray-500">{{ $tramite->codigo }}</span> — {{ $tramite->nombre }}
            </h3>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Vincular a</label>
                <select name="gasto_id"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    <option value="">— General (no vinculado a un gasto puntual) —</option>
                    @foreach($tramite->gastos as $gasto)
                        <option value="{{ $gasto->id }}" {{ old('gasto_id', $cobro->gasto_id) == $gasto->id ? 'selected' : '' }}>
                            {{ $gasto->concepto }} ({{ number_format($gasto->monto, 2) }} Bs — {{ $gasto->fecha->format('d/m/Y') }})
                        </option>
                    @endforeach
                </select>
                @error('gasto_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Monto *</label>
                    <input type="number" name="monto" value="{{ old('monto', $cobro->monto) }}" step="0.01" min="0" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    @error('monto')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
                    <input type="date" name="fecha" value="{{ old('fecha', $cobro->fecha->format('Y-m-d')) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Método de pago *</label>
                <select name="metodo_pago" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    <option value="efectivo" {{ old('metodo_pago', $cobro->metodo_pago) === 'efectivo' ? 'selected' : '' }}>Efectivo</option>
                    <option value="qr" {{ old('metodo_pago', $cobro->metodo_pago) === 'qr' ? 'selected' : '' }}>QR</option>
                </select>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-medium">
                <i class="fas fa-save mr-1"></i> Guardar cambios
            </button>
            <a href="{{ route('tramites.show', $tramite) }}" class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
