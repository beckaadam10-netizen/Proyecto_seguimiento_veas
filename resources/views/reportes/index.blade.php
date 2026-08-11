@extends('layouts.app')

@section('title', 'Reportes')
@section('header', 'Reportes')

@section('content')

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4">
        <div class="w-12 h-12 bg-brand-100 text-brand-700 rounded-xl flex items-center justify-center text-xl">
            <i class="fas fa-users"></i>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800">{{ $resumen['clientes'] }}</p>
            <p class="text-sm text-gray-500">Clientes registrados</p>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4">
        <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center text-xl">
            <i class="fas fa-folder-open"></i>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800">{{ $resumen['expedientes'] }}</p>
            <p class="text-sm text-gray-500">Expedientes registrados</p>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4">
        <div class="w-12 h-12 bg-cyan-100 text-cyan-600 rounded-xl flex items-center justify-center text-xl">
            <i class="fas fa-file-circle-check"></i>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800">{{ $resumen['tramites'] }}</p>
            <p class="text-sm text-gray-500">Trámites registrados</p>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4">
        <div class="w-12 h-12 {{ $resumen['saldo_pendiente'] > 0 ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600' }} rounded-xl flex items-center justify-center text-xl">
            <i class="fas fa-hand-holding-dollar"></i>
        </div>
        <div>
            <p class="text-2xl font-bold {{ $resumen['saldo_pendiente'] > 0 ? 'text-red-600' : 'text-gray-800' }}">
                {{ number_format($resumen['saldo_pendiente'], 2) }} Bs
            </p>
            <p class="text-sm text-gray-500">Saldo pendiente global</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    <a href="{{ route('reportes.clientes') }}"
       class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition flex items-center gap-4">
        <div class="w-14 h-14 rounded-lg bg-brand-100 text-brand-800 flex items-center justify-center text-2xl">
            <i class="fas fa-users"></i>
        </div>
        <div>
            <h3 class="font-semibold text-gray-800 text-lg">Reporte de Clientes</h3>
            <p class="text-sm text-gray-500">Listado y estadísticas de clientes registrados.</p>
        </div>
    </a>

    <a href="{{ route('reportes.expedientes') }}"
       class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition flex items-center gap-4">
        <div class="w-14 h-14 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center text-2xl">
            <i class="fas fa-folder-open"></i>
        </div>
        <div>
            <h3 class="font-semibold text-gray-800 text-lg">Reporte de Expedientes</h3>
            <p class="text-sm text-gray-500">Expedientes por estado, tipo de causa y abogado.</p>
        </div>
    </a>

    <a href="{{ route('reportes.tramites') }}"
       class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition flex items-center gap-4">
        <div class="w-14 h-14 rounded-lg bg-cyan-100 text-cyan-700 flex items-center justify-center text-2xl">
            <i class="fas fa-file-circle-check"></i>
        </div>
        <div>
            <h3 class="font-semibold text-gray-800 text-lg">Reporte de Trámites</h3>
            <p class="text-sm text-gray-500">Trámites por estado, prioridad e institución pública.</p>
        </div>
    </a>

    <a href="{{ route('reportes.gastos-cobros') }}"
       class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition flex items-center gap-4">
        <div class="w-14 h-14 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-2xl">
            <i class="fas fa-hand-holding-dollar"></i>
        </div>
        <div>
            <h3 class="font-semibold text-gray-800 text-lg">Reporte de Gastos y Cobros</h3>
            <p class="text-sm text-gray-500">Gastos, cobros y saldo pendiente por período.</p>
        </div>
    </a>

    @if(auth()->user()->puede('reportes', 'pasantes'))
    <a href="{{ route('reportes.pasantes') }}"
       class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition flex items-center gap-4">
        <div class="w-14 h-14 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-2xl">
            <i class="fas fa-user-graduate"></i>
        </div>
        <div>
            <h3 class="font-semibold text-gray-800 text-lg">Reporte Pasantes</h3>
            <p class="text-sm text-gray-500">Tus gastos registrados, por expediente o trámite.</p>
        </div>
    </a>
    @endif

</div>
@endsection
