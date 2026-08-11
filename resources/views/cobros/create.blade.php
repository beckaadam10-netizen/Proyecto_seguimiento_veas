@extends('layouts.app')

@section('title', 'Cobrar')
@section('header', 'Cobrar')

@section('content')
<div class="max-w-2xl">

    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h3 class="font-semibold text-gray-700 border-b pb-2 mb-3">
            <span class="font-mono text-xs text-gray-500">{{ $tramite->codigo }}</span> — {{ $tramite->nombre }}
        </h3>
        <dl class="space-y-1.5 text-sm">
            <div class="flex justify-between">
                <dt class="text-gray-500">Total gastado</dt>
                <dd class="font-medium text-amber-700">{{ number_format($tramite->total_gastos, 2) }} Bs</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Total cobrado</dt>
                <dd class="font-medium text-emerald-700">{{ number_format($tramite->total_cobrado, 2) }} Bs</dd>
            </div>
            <div class="flex justify-between pt-1.5 border-t">
                <dt class="text-gray-600 font-medium">Saldo pendiente</dt>
                <dd class="font-semibold {{ $tramite->saldo_pendiente > 0 ? 'text-red-600' : 'text-gray-800' }}">
                    {{ number_format($tramite->saldo_pendiente, 2) }} Bs
                </dd>
            </div>
        </dl>
    </div>

    <form method="POST" action="{{ route('cobros.store') }}" class="space-y-6">
        @csrf
        <input type="hidden" name="tramite_id" value="{{ $tramite->id }}">
        <input type="hidden" name="modo" id="modo" value="total">

        <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">¿Cómo querés cobrar?</label>
                <div class="flex gap-2">
                    <button type="button" data-modo="total" class="modo-btn flex-1 px-3 py-2 rounded-lg text-sm font-medium border border-brand-600 bg-brand-600 text-white">
                        Cobro total
                    </button>
                    <button type="button" data-modo="abono" class="modo-btn flex-1 px-3 py-2 rounded-lg text-sm font-medium border border-gray-300 text-gray-700">
                        Abono
                    </button>
                    <button type="button" data-modo="item" class="modo-btn flex-1 px-3 py-2 rounded-lg text-sm font-medium border border-gray-300 text-gray-700">
                        Por ítem
                    </button>
                </div>
            </div>

            {{-- Modo: total --}}
            <div id="panel-total" class="modo-panel">
                @if($tramite->saldo_pendiente > 0)
                    <p class="text-sm text-gray-600 bg-gray-50 rounded-lg p-3">
                        Se va a registrar un cobro por el saldo pendiente completo:
                        <span class="font-semibold text-red-600">{{ number_format($tramite->saldo_pendiente, 2) }} Bs</span>
                    </p>
                @else
                    <p class="text-sm text-gray-400 bg-gray-50 rounded-lg p-3">Este trámite no tiene saldo pendiente.</p>
                @endif
            </div>

            {{-- Modo: abono --}}
            <div id="panel-abono" class="modo-panel hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">Monto del abono *</label>
                <input type="number" name="monto" value="{{ old('monto') }}" step="0.01" min="0.01"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                @error('monto')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Modo: por ítem --}}
            <div id="panel-item" class="modo-panel hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">Elegí los gastos a cobrar</label>
                <div class="space-y-1 max-h-56 overflow-y-auto border rounded-lg p-3">
                    @php $gastosPendientes = $tramite->gastos->where('cubierto', false); @endphp
                    @forelse($gastosPendientes as $gasto)
                        @php $pendiente = (float) $gasto->monto - $gasto->total_cobrado; @endphp
                        <label class="flex items-center justify-between gap-3 text-sm py-1.5 hover:bg-gray-50 rounded px-1 cursor-pointer">
                            <span class="flex items-center gap-2 min-w-0">
                                <input type="checkbox" name="gastos_seleccionados[]" value="{{ $gasto->id }}"
                                       class="rounded border-gray-300 text-brand-700 focus:ring-brand-400 flex-shrink-0">
                                <span class="truncate">{{ $gasto->concepto }}</span>
                            </span>
                            <span class="text-gray-500 flex-shrink-0">{{ number_format($pendiente, 2) }} Bs</span>
                        </label>
                    @empty
                        <p class="text-xs text-gray-400">No hay gastos pendientes de cobro.</p>
                    @endforelse
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 pt-2 border-t">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
                    <input type="date" name="fecha" value="{{ old('fecha', date('Y-m-d')) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Método de pago *</label>
                    <select name="metodo_pago" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        <option value="efectivo" {{ old('metodo_pago') === 'efectivo' ? 'selected' : '' }}>Efectivo</option>
                        <option value="qr" {{ old('metodo_pago') === 'qr' ? 'selected' : '' }}>QR</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2 rounded-lg font-medium">
                <i class="fas fa-hand-holding-dollar mr-1"></i> Registrar Cobro
            </button>
            <a href="{{ route('tramites.show', $tramite) }}" class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                Cancelar
            </a>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function seleccionarModoCobro(modo) {
        document.getElementById('modo').value = modo;

        document.querySelectorAll('.modo-panel').forEach(function (panel) {
            panel.classList.add('hidden');
        });
        document.getElementById('panel-' + modo).classList.remove('hidden');

        document.querySelectorAll('.modo-btn').forEach(function (btn) {
            const activo = btn.dataset.modo === modo;
            btn.classList.toggle('bg-brand-600', activo);
            btn.classList.toggle('text-white', activo);
            btn.classList.toggle('border-brand-600', activo);
            btn.classList.toggle('border-gray-300', !activo);
            btn.classList.toggle('text-gray-700', !activo);
        });
    }

    document.querySelectorAll('.modo-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            seleccionarModoCobro(btn.dataset.modo);
        });
    });
</script>
@endpush
@endsection
