@extends('layouts.app')

@section('title', $abogado->nombre)
@section('header', $abogado->nombre)

@section('header-actions')
    @if(auth()->user()->puede('abogados', 'modificar'))
    <a href="{{ route('abogados.index', ['editar' => $abogado->id, 'id' => $abogado->id]) }}"
       class="bg-white border hover:bg-gray-50 text-gray-700 px-3 py-2 rounded-lg text-sm flex items-center gap-2">
        <i class="fas fa-edit"></i> Editar
    </a>
    @endif
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Datos del abogado --}}
    <div class="space-y-4">
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-14 h-14 bg-brand-100 text-brand-700 rounded-full flex items-center justify-center text-2xl font-bold">
                    {{ strtoupper(substr($abogado->nombre, 0, 1)) }}
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800">{{ $abogado->nombre }}</h3>
                    @if($abogado->especialidad)
                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-brand-100 text-brand-800">
                            {{ $abogado->especialidad }}
                        </span>
                    @endif
                </div>
            </div>
            <dl class="space-y-2 text-sm">
                @if($abogado->matricula)
                <div class="flex justify-between">
                    <dt class="text-gray-500">Matrícula</dt>
                    <dd class="font-medium">{{ $abogado->matricula }}</dd>
                </div>
                @endif
                @if($abogado->email)
                <div class="flex justify-between">
                    <dt class="text-gray-500">Email</dt>
                    <dd><a href="mailto:{{ $abogado->email }}" class="text-brand-700 hover:underline">{{ $abogado->email }}</a></dd>
                </div>
                @endif
                @if($abogado->telefono)
                <div class="flex justify-between">
                    <dt class="text-gray-500">Teléfono</dt>
                    <dd>{{ $abogado->telefono }}</dd>
                </div>
                @endif
                <div class="flex justify-between pt-2 border-t">
                    <dt class="text-gray-500">Estado</dt>
                    <dd>
                        <span class="px-2 py-0.5 rounded text-xs font-medium
                            {{ $abogado->activo ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $abogado->activo ? 'Activo' : 'Inactivo' }}
                        </span>
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Asignaciones --}}
    <div class="col-span-2 space-y-6">

        <div class="bg-white rounded-xl shadow-sm">
            <div class="flex items-center justify-between p-5 border-b">
                <h3 class="font-semibold text-gray-700">
                    <i class="fas fa-folder-open text-brand-600 mr-2"></i>
                    Expedientes ({{ $abogado->expedientes->count() }})
                </h3>
            </div>
            <div class="divide-y divide-gray-100 max-h-64 overflow-y-auto">
                @forelse($abogado->expedientes as $exp)
                <div class="px-5 py-3 hover:bg-gray-50 flex items-center justify-between gap-4">
                    <a href="{{ route('expedientes.show', $exp) }}" class="font-medium text-brand-800 hover:underline text-sm truncate">
                        {{ $exp->numero }} — {{ Str::limit($exp->caratula, 40) }}
                    </a>
                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-{{ $exp->estado_color }}-100 text-{{ $exp->estado_color }}-700 flex-shrink-0">
                        {{ $exp->estado_label }}
                    </span>
                </div>
                @empty
                <p class="p-5 text-sm text-gray-400 text-center">Sin expedientes asignados.</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm">
            <div class="flex items-center justify-between p-5 border-b">
                <h3 class="font-semibold text-gray-700">
                    <i class="fas fa-file-circle-check text-cyan-600 mr-2"></i>
                    Trámites ({{ $abogado->tramites->count() }})
                </h3>
            </div>
            <div class="divide-y divide-gray-100 max-h-64 overflow-y-auto">
                @forelse($abogado->tramites as $tra)
                <div class="px-5 py-3 hover:bg-gray-50 flex items-center justify-between gap-4">
                    <a href="{{ route('tramites.show', $tra) }}" class="font-medium text-brand-800 hover:underline text-sm truncate">
                        {{ $tra->codigo }} — {{ Str::limit($tra->nombre, 35) }}
                    </a>
                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-{{ $tra->estado_color }}-100 text-{{ $tra->estado_color }}-700 flex-shrink-0">
                        {{ ucfirst(str_replace('_', ' ', $tra->estado)) }}
                    </span>
                </div>
                @empty
                <p class="p-5 text-sm text-gray-400 text-center">Sin trámites asignados.</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm">
            <div class="flex items-center justify-between p-5 border-b">
                <h3 class="font-semibold text-gray-700">
                    <i class="fas fa-gavel text-purple-600 mr-2"></i>
                    Audiencias ({{ $abogado->audiencias->count() }})
                </h3>
            </div>
            <div class="divide-y divide-gray-100 max-h-64 overflow-y-auto">
                @forelse($abogado->audiencias as $aud)
                <div class="px-5 py-3 hover:bg-gray-50 flex items-center justify-between gap-4">
                    <a href="{{ route('audiencias.index', ['editar' => $aud->id, 'id' => $aud->id]) }}" class="font-medium text-brand-800 hover:underline text-sm truncate">
                        {{ $aud->titulo }}
                    </a>
                    <span class="text-xs text-gray-500 flex-shrink-0">{{ $aud->fecha_hora->format('d/m/Y') }}</span>
                </div>
                @empty
                <p class="p-5 text-sm text-gray-400 text-center">Sin audiencias asignadas.</p>
                @endforelse
            </div>
        </div>

    </div>

</div>
@endsection
