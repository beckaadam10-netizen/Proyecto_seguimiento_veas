@extends('layouts.app')

@section('title', 'Parámetros')
@section('header', 'Parámetros')

@section('content')

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">

    <button type="button" onclick="toggleParametro('tramite')"
            class="text-left bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition flex items-center gap-4 w-full">
        <div class="w-12 h-12 rounded-lg bg-cyan-100 text-cyan-700 flex items-center justify-center text-xl">
            <i class="fas fa-file-circle-check"></i>
        </div>
        <div>
            <h3 class="font-semibold text-gray-800">Tipos de Trámite</h3>
            <p class="text-sm text-gray-500">{{ $tiposTramite->count() }} registrados</p>
        </div>
    </button>

    <button type="button" onclick="toggleParametro('institucion')"
            class="text-left bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition flex items-center gap-4 w-full">
        <div class="w-12 h-12 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center text-xl">
            <i class="fas fa-building-columns"></i>
        </div>
        <div>
            <h3 class="font-semibold text-gray-800">Instituciones Públicas</h3>
            <p class="text-sm text-gray-500">{{ $institucionesPublicas->count() }} registradas</p>
        </div>
    </button>

    <button type="button" onclick="toggleParametro('audiencia')"
            class="text-left bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition flex items-center gap-4 w-full">
        <div class="w-12 h-12 rounded-lg bg-brand-100 text-brand-800 flex items-center justify-center text-xl">
            <i class="fas fa-gavel"></i>
        </div>
        <div>
            <h3 class="font-semibold text-gray-800">Tipos de Audiencia</h3>
            <p class="text-sm text-gray-500">{{ $tiposAudiencia->count() }} registrados</p>
        </div>
    </button>

    <button type="button" onclick="toggleParametro('documento')"
            class="text-left bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition flex items-center gap-4 w-full">
        <div class="w-12 h-12 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center text-xl">
            <i class="fas fa-file-alt"></i>
        </div>
        <div>
            <h3 class="font-semibold text-gray-800">Tipos de Documento</h3>
            <p class="text-sm text-gray-500">{{ $tiposDocumento->count() }} registrados</p>
        </div>
    </button>

    <button type="button" onclick="toggleParametro('actuacion')"
            class="text-left bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition flex items-center gap-4 w-full">
        <div class="w-12 h-12 rounded-lg bg-green-100 text-green-700 flex items-center justify-center text-xl">
            <i class="fas fa-list-check"></i>
        </div>
        <div>
            <h3 class="font-semibold text-gray-800">Tipos de Actuación</h3>
            <p class="text-sm text-gray-500">{{ $tiposActuacion->count() }} registrados</p>
        </div>
    </button>

    <button type="button" onclick="toggleParametro('gasto')"
            class="text-left bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition flex items-center gap-4 w-full">
        <div class="w-12 h-12 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-xl">
            <i class="fas fa-coins"></i>
        </div>
        <div>
            <h3 class="font-semibold text-gray-800">Tipos de Gasto</h3>
            <p class="text-sm text-gray-500">{{ $tiposGasto->count() }} registrados</p>
        </div>
    </button>

    <button type="button" onclick="toggleParametro('estado-expediente')"
            class="text-left bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition flex items-center gap-4 w-full">
        <div class="w-12 h-12 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center text-xl">
            <i class="fas fa-folder-open"></i>
        </div>
        <div>
            <h3 class="font-semibold text-gray-800">Estados de Expediente</h3>
            <p class="text-sm text-gray-500">{{ $estadosExpediente->count() }} registrados</p>
        </div>
    </button>

</div>

<div id="panel-audiencia" class="hidden mb-6">
    <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold text-gray-700">Tipos de Audiencia</h3>
        @if(auth()->user()->puede('parametros', 'crear'))
        <button type="button" onclick="abrirModal('modal-tipo-audiencia-nuevo')"
           class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
            <i class="fas fa-plus"></i> Nuevo Tipo
        </button>
        @endif
    </div>
    @include('parametros._tabla-tipos', [
        'tipos'       => $tiposAudiencia,
        'routePrefix' => 'parametros.tipos-audiencia',
        'key'         => 'tipo-audiencia',
        'confirmMsg'  => '¿Eliminar este tipo de audiencia?',
        'emptyIcon'   => 'fa-gavel',
        'emptyMsg'    => 'No hay tipos de audiencia registrados.',
    ])
    @include('parametros._modales-tipo', [
        'tipos'       => $tiposAudiencia,
        'routePrefix' => 'parametros.tipos-audiencia',
        'key'         => 'tipo-audiencia',
        'tituloNuevo' => 'Nuevo Tipo de Audiencia',
    ])
</div>

<div id="panel-documento" class="hidden mb-6">
    <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold text-gray-700">Tipos de Documento</h3>
        @if(auth()->user()->puede('parametros', 'crear'))
        <button type="button" onclick="abrirModal('modal-tipo-documento-nuevo')"
           class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
            <i class="fas fa-plus"></i> Nuevo Tipo
        </button>
        @endif
    </div>
    @include('parametros._tabla-tipos', [
        'tipos'       => $tiposDocumento,
        'routePrefix' => 'parametros.tipos-documento',
        'key'         => 'tipo-documento',
        'confirmMsg'  => '¿Eliminar este tipo de documento?',
        'emptyIcon'   => 'fa-file-alt',
        'emptyMsg'    => 'No hay tipos de documento registrados.',
    ])
    @include('parametros._modales-tipo', [
        'tipos'       => $tiposDocumento,
        'routePrefix' => 'parametros.tipos-documento',
        'key'         => 'tipo-documento',
        'tituloNuevo' => 'Nuevo Tipo de Documento',
    ])
</div>

<div id="panel-actuacion" class="hidden mb-6">
    <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold text-gray-700">Tipos de Actuación</h3>
        @if(auth()->user()->puede('parametros', 'crear'))
        <button type="button" onclick="abrirModal('modal-tipo-actuacion-nuevo')"
           class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
            <i class="fas fa-plus"></i> Nuevo Tipo
        </button>
        @endif
    </div>
    @include('parametros._tabla-tipos', [
        'tipos'       => $tiposActuacion,
        'routePrefix' => 'parametros.tipos-actuacion',
        'key'         => 'tipo-actuacion',
        'confirmMsg'  => '¿Eliminar este tipo de actuación?',
        'emptyIcon'   => 'fa-list-check',
        'emptyMsg'    => 'No hay tipos de actuación registrados.',
    ])
    @include('parametros._modales-tipo', [
        'tipos'       => $tiposActuacion,
        'routePrefix' => 'parametros.tipos-actuacion',
        'key'         => 'tipo-actuacion',
        'tituloNuevo' => 'Nuevo Tipo de Actuación',
    ])
</div>

<div id="panel-tramite" class="hidden mb-6">
    <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold text-gray-700">Tipos de Trámite</h3>
        @if(auth()->user()->puede('parametros', 'crear'))
        <button type="button" onclick="abrirModal('modal-tipo-tramite-nuevo')"
           class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
            <i class="fas fa-plus"></i> Nuevo Tipo
        </button>
        @endif
    </div>
    @include('parametros._tabla-tipos', [
        'tipos'       => $tiposTramite,
        'routePrefix' => 'parametros.tipos-tramite',
        'key'         => 'tipo-tramite',
        'confirmMsg'  => '¿Eliminar este tipo de trámite?',
        'emptyIcon'   => 'fa-file-circle-check',
        'emptyMsg'    => 'No hay tipos de trámite registrados.',
    ])
    @include('parametros._modales-tipo', [
        'tipos'       => $tiposTramite,
        'routePrefix' => 'parametros.tipos-tramite',
        'key'         => 'tipo-tramite',
        'tituloNuevo' => 'Nuevo Tipo de Trámite',
    ])
</div>

<div id="panel-institucion" class="hidden mb-6">
    <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold text-gray-700">Instituciones Públicas</h3>
        @if(auth()->user()->puede('parametros', 'crear'))
        <button type="button" onclick="abrirModal('modal-institucion-publica-nuevo')"
           class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
            <i class="fas fa-plus"></i> Nueva Institución
        </button>
        @endif
    </div>
    @include('parametros._tabla-tipos', [
        'tipos'       => $institucionesPublicas,
        'routePrefix' => 'parametros.instituciones-publicas',
        'key'         => 'institucion-publica',
        'confirmMsg'  => '¿Eliminar esta institución pública?',
        'emptyIcon'   => 'fa-building-columns',
        'emptyMsg'    => 'No hay instituciones públicas registradas.',
    ])
    @include('parametros._modales-tipo', [
        'tipos'       => $institucionesPublicas,
        'routePrefix' => 'parametros.instituciones-publicas',
        'key'         => 'institucion-publica',
        'tituloNuevo' => 'Nueva Institución Pública',
    ])
</div>

<div id="panel-gasto" class="hidden mb-6">
    <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold text-gray-700">Tipos de Gasto</h3>
        @if(auth()->user()->puede('parametros', 'crear'))
        <button type="button" onclick="abrirModal('modal-tipo-gasto-nuevo')"
           class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
            <i class="fas fa-plus"></i> Nuevo Tipo
        </button>
        @endif
    </div>
    @include('parametros._tabla-tipos', [
        'tipos'       => $tiposGasto,
        'routePrefix' => 'parametros.tipos-gasto',
        'key'         => 'tipo-gasto',
        'confirmMsg'  => '¿Eliminar este tipo de gasto?',
        'emptyIcon'   => 'fa-coins',
        'emptyMsg'    => 'No hay tipos de gasto registrados.',
    ])
    @include('parametros._modales-tipo', [
        'tipos'       => $tiposGasto,
        'routePrefix' => 'parametros.tipos-gasto',
        'key'         => 'tipo-gasto',
        'tituloNuevo' => 'Nuevo Tipo de Gasto',
    ])
</div>

<div id="panel-estado-expediente" class="hidden mb-6">
    <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold text-gray-700">Estados de Expediente</h3>
        @if(auth()->user()->puede('parametros', 'crear'))
        <button type="button" onclick="abrirModal('modal-estado-expediente-nuevo')"
           class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
            <i class="fas fa-plus"></i> Nuevo Estado
        </button>
        @endif
    </div>
    @include('parametros._tabla-estados-expediente', [
        'tipos'       => $estadosExpediente,
        'routePrefix' => 'parametros.estados-expediente',
        'key'         => 'estado-expediente',
        'confirmMsg'  => '¿Eliminar este estado de expediente?',
        'emptyIcon'   => 'fa-folder-open',
        'emptyMsg'    => 'No hay estados de expediente registrados.',
    ])
    @include('parametros._modales-estado-expediente', [
        'tipos'       => $estadosExpediente,
        'routePrefix' => 'parametros.estados-expediente',
        'key'         => 'estado-expediente',
        'tituloNuevo' => 'Nuevo Estado de Expediente',
    ])
</div>

@push('scripts')
<script>
    function toggleParametro(key) {
        ['audiencia', 'documento', 'actuacion', 'tramite', 'institucion', 'gasto', 'estado-expediente'].forEach(function (k) {
            const panel = document.getElementById('panel-' + k);
            if (k === key) {
                panel.classList.toggle('hidden');
            } else {
                panel.classList.add('hidden');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const abrir = @json(request('abrir'));
        if (abrir) {
            const panel = document.getElementById('panel-' + abrir);
            if (panel) {
                panel.classList.remove('hidden');
                panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    });

    @if($errors->any() && old('_modal'))
        (function () {
            const modalId = @json(old('_modal'));
            const panelPorPrefijo = {
                'modal-tipo-audiencia':      'audiencia',
                'modal-tipo-documento':      'documento',
                'modal-tipo-actuacion':      'actuacion',
                'modal-tipo-tramite':        'tramite',
                'modal-institucion-publica': 'institucion',
                'modal-tipo-gasto':          'gasto',
                'modal-estado-expediente':   'estado-expediente',
            };
            const prefijo = Object.keys(panelPorPrefijo).find(function (p) { return modalId.startsWith(p); });
            if (prefijo) {
                toggleParametro(panelPorPrefijo[prefijo]);
            }
            abrirModal(modalId);
        })();
    @endif
</script>
@endpush

@endsection
