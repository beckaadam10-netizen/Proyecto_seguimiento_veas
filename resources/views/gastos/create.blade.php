@extends('layouts.app')

@section('title', 'Nuevo Gasto')
@section('header', 'Nuevo Gasto')

@section('content')
<div class="max-w-lg">
    <form method="POST" action="{{ route('gastos.store') }}" class="space-y-6">
        @csrf
        <input type="hidden" name="tramite_id" value="{{ $tramite->id }}">

        <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
            <h3 class="font-semibold text-gray-700 border-b pb-2">
                Gasto para <span class="font-mono text-xs text-gray-500">{{ $tramite->codigo }}</span> — {{ $tramite->nombre }}
            </h3>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de gasto *</label>
                <div class="flex gap-2">
                    <select name="tipo_gasto_id" id="select-tipo-gasto-crear" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        <option value="">— Seleccioná un tipo —</option>
                        @foreach($tiposGasto as $t)
                            <option value="{{ $t->id }}" {{ old('tipo_gasto_id') == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
                        @endforeach
                    </select>
                    @if(auth()->user()->puede('parametros', 'crear'))
                    <button type="button" onclick="abrirModal('modal-tipo-gasto-rapido')" title="Nuevo tipo de gasto"
                            class="shrink-0 border border-gray-300 rounded-lg w-9 h-9 flex items-center justify-center text-gray-500 hover:text-brand-700 hover:border-brand-400">
                        <i class="fas fa-plus"></i>
                    </button>
                    @endif
                </div>
                @error('tipo_gasto_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Concepto *</label>
                <input type="text" name="concepto" value="{{ old('concepto') }}" required
                       placeholder="Ej: Arancel de inscripción"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                @error('concepto')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Monto *</label>
                    <input type="number" name="monto" value="{{ old('monto') }}" step="0.01" min="0" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    @error('monto')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
                    <input type="date" name="fecha" value="{{ old('fecha', date('Y-m-d')) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-medium">
                <i class="fas fa-save mr-1"></i> Guardar Gasto
            </button>
            <a href="{{ route('tramites.show', $tramite) }}" class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                Cancelar
            </a>
        </div>
    </form>
</div>

{{-- Modal flotante: alta rápida de tipo de gasto --}}
<div id="modal-tipo-gasto-rapido" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-tipo-gasto-rapido')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-sm">
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h3 class="font-semibold text-gray-800 text-lg">Nuevo tipo de gasto</h3>
                <button type="button" onclick="cerrarModal('modal-tipo-gasto-rapido')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text" id="input-tipo-gasto-rapido-nombre" placeholder="Ej: Arancel de inscripción"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    <p id="error-tipo-gasto-rapido" class="text-xs text-red-600 mt-1 hidden"></p>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="crearTipoGastoRapido()"
                            class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                        Guardar
                    </button>
                    <button type="button" onclick="cerrarModal('modal-tipo-gasto-rapido')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50 text-sm">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function crearTipoGastoRapido() {
        const input = document.getElementById('input-tipo-gasto-rapido-nombre');
        const error = document.getElementById('error-tipo-gasto-rapido');
        const nombre = input.value.trim();

        error.classList.add('hidden');

        if (!nombre) {
            error.textContent = 'El nombre es obligatorio.';
            error.classList.remove('hidden');
            return;
        }

        fetch('{{ route('parametros.tipos-gasto.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ nombre }),
        })
            .then(async (res) => {
                const body = await res.json();
                if (!res.ok) {
                    throw new Error(body.errors?.nombre?.[0] ?? 'No se pudo crear el tipo de gasto.');
                }
                return body;
            })
            .then((tipo) => {
                const select = document.getElementById('select-tipo-gasto-crear');
                const opcion = new Option(tipo.nombre, tipo.id, true, true);
                select.add(opcion);
                input.value = '';
                cerrarModal('modal-tipo-gasto-rapido');
            })
            .catch((err) => {
                error.textContent = err.message;
                error.classList.remove('hidden');
            });
    }
</script>
@endpush
@endsection
