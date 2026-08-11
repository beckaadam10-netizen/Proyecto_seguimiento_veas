@extends('layouts.app')

@section('title', 'Reporte de Gastos y Cobros')
@section('header', 'Reporte de Gastos y Cobros')

@section('header-actions')
    <a href="{{ route('reportes.gastos-cobros.pdf', request()->query()) }}"
       class="bg-red-700 hover:bg-red-800 text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2">
        <i class="fas fa-file-pdf"></i> Descargar PDF
    </a>
@endsection

@section('content')

@php
    $porcentajeCobrado = $resumen['total_gastos'] > 0
        ? min(100, round(($resumen['total_cobrado'] / $resumen['total_gastos']) * 100))
        : ($resumen['total_cobrado'] > 0 ? 100 : 0);
@endphp

<div class="bg-white rounded-xl shadow-sm p-5 mb-6">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-5">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center text-xl flex-shrink-0">
                <i class="fas fa-coins"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-800">{{ number_format($resumen['total_gastos'], 2) }} Bs</p>
                <p class="text-sm text-gray-500">Total gastos (filtro actual)</p>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center text-xl flex-shrink-0">
                <i class="fas fa-hand-holding-dollar"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-800">{{ number_format($resumen['total_cobrado'], 2) }} Bs</p>
                <p class="text-sm text-gray-500">Total cobrado (filtro actual)</p>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 {{ $resumen['saldo_pendiente'] > 0 ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600' }} rounded-xl flex items-center justify-center text-xl flex-shrink-0">
                <i class="fas fa-scale-balanced"></i>
            </div>
            <div>
                <p class="text-2xl font-bold {{ $resumen['saldo_pendiente'] > 0 ? 'text-red-600' : 'text-gray-800' }}">
                    {{ number_format($resumen['saldo_pendiente'], 2) }} Bs
                </p>
                <p class="text-sm text-gray-500">Saldo pendiente</p>
            </div>
        </div>
    </div>

    <div>
        <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
            <span>Cobrado sobre lo gastado</span>
            <span class="font-semibold text-gray-700">{{ $porcentajeCobrado }}%</span>
        </div>
        <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
            <div class="h-2 rounded-full {{ $porcentajeCobrado >= 100 ? 'bg-green-500' : 'bg-emerald-500' }}" style="width: {{ $porcentajeCobrado }}%"></div>
        </div>
    </div>
</div>

<form method="GET" class="bg-white rounded-xl shadow-sm p-4 mb-6 flex flex-wrap gap-3 items-end">
    <div>
        <label class="block text-xs text-gray-500 mb-1">Tipo de gasto</label>
        <select name="tipo_gasto_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Todos</option>
            @foreach($tiposGasto as $t)
                <option value="{{ $t->id }}" {{ request('tipo_gasto_id') == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Método de pago</label>
        <select name="metodo_pago" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Todos</option>
            <option value="efectivo" {{ request('metodo_pago') === 'efectivo' ? 'selected' : '' }}>Efectivo</option>
            <option value="qr" {{ request('metodo_pago') === 'qr' ? 'selected' : '' }}>QR</option>
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Desde</label>
        <input type="date" name="desde" value="{{ request('desde') }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Hasta</label>
        <input type="date" name="hasta" value="{{ request('hasta') }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
    </div>
    <button type="submit" class="bg-brand-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-brand-700">
        <i class="fas fa-search"></i> Filtrar
    </button>
    <a href="{{ route('reportes.gastos-cobros') }}" class="text-gray-500 text-sm py-2 hover:text-gray-700">
        <i class="fas fa-times"></i> Limpiar
    </a>
</form>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="p-5 border-b flex items-center justify-between">
        <h3 class="font-semibold text-gray-700">
            <i class="fas fa-scale-balanced text-brand-700 mr-2"></i>
            Trámites y expedientes con movimientos ({{ $registros->total() }})
        </h3>
        <span class="text-xs text-gray-400">Página {{ $registros->currentPage() }} de {{ $registros->lastPage() }}</span>
    </div>
    <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Registro</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Cliente</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">Gastado</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">Cobrado</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">Por cobrar</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($registros as $registro)
            @php
                $porCobrar = $registro->gastado - $registro->cobrado;
            @endphp
            <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3">
                    <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-medium mr-1
                        {{ $registro->tipo_registro === 'expediente' ? 'bg-purple-100 text-purple-700' : 'bg-cyan-100 text-cyan-700' }}">
                        {{ $registro->tipo_registro === 'expediente' ? 'Expediente' : 'Trámite' }}
                    </span>
                    <a href="{{ $registro->ruta_show }}" class="font-mono text-xs text-cyan-700 hover:underline">
                        {{ $registro->codigo_display }}
                    </a>
                    <p class="text-xs text-gray-500">{{ Str::limit($registro->titulo_display, 35) }}</p>
                </td>
                <td class="px-4 py-3 text-gray-600">{{ $registro->cliente->nombre_completo }}</td>
                <td class="px-4 py-3 text-right text-amber-700 font-medium">{{ number_format($registro->gastado, 2) }} Bs</td>
                <td class="px-4 py-3 text-right text-emerald-700 font-medium">{{ number_format($registro->cobrado, 2) }} Bs</td>
                <td class="px-4 py-3 text-right font-semibold {{ $porCobrar > 0 ? 'text-red-600' : 'text-gray-500' }}">
                    {{ number_format($porCobrar, 2) }} Bs
                </td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <a href="{{ $registro->ruta_show }}" class="text-xs text-gray-500 hover:text-brand-700 font-medium mr-3">
                        <i class="fas fa-eye"></i> Ver detalle
                    </a>
                    <a href="{{ $registro->ruta_pdf }}" target="_blank" class="text-xs text-red-700 hover:underline font-medium">
                        <i class="fas fa-file-pdf"></i> Ver PDF
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-4 py-10 text-center text-gray-400">
                    <i class="fas fa-scale-balanced text-3xl mb-2"></i>
                    <p>No hay trámites ni expedientes con gastos o cobros para los filtros aplicados.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
        @if($registros->isNotEmpty())
        <tfoot>
            <tr class="bg-gray-50 border-t-2 border-gray-200">
                <td colspan="2" class="px-4 py-2.5 text-xs font-semibold text-gray-700 uppercase tracking-wide">
                    Total filtrado
                </td>
                <td class="px-4 py-2.5 text-right font-bold text-amber-800">{{ number_format($resumen['total_gastos'], 2) }} Bs</td>
                <td class="px-4 py-2.5 text-right font-bold text-emerald-800">{{ number_format($resumen['total_cobrado'], 2) }} Bs</td>
                <td class="px-4 py-2.5 text-right font-bold text-red-700">{{ number_format($resumen['saldo_pendiente'], 2) }} Bs</td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>
    </div>
    <div class="px-4 py-3 border-t">{{ $registros->links() }}</div>
</div>
@endsection
