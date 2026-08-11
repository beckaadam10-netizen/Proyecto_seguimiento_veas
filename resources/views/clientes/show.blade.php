@extends('layouts.app')

@section('title', $cliente->nombre_completo)
@section('header', $cliente->nombre_completo)

@section('header-actions')
    @if(auth()->user()->puede('expedientes', 'crear'))
    <a href="{{ route('expedientes.index', ['nuevo' => 1, 'cliente_id' => $cliente->id]) }}"
       class="bg-brand-600 hover:bg-brand-700 text-white px-3 py-2 rounded-lg text-sm flex items-center gap-2">
        <i class="fas fa-plus"></i> Nuevo Expediente
    </a>
    @endif
    @if(auth()->user()->puede('clientes', 'modificar'))
    <a href="{{ route('clientes.index', ['editar' => $cliente->id, 'id' => $cliente->id]) }}"
       class="bg-white border hover:bg-gray-50 text-gray-700 px-3 py-2 rounded-lg text-sm flex items-center gap-2">
        <i class="fas fa-edit"></i> Editar
    </a>
    @endif
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Datos del cliente --}}
    <div class="space-y-4">
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-14 h-14 bg-brand-100 text-brand-700 rounded-full flex items-center justify-center text-2xl font-bold">
                    {{ strtoupper(substr($cliente->nombre_completo, 0, 1)) }}
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800">{{ $cliente->nombre_completo }}</h3>
                    <span class="px-2 py-0.5 rounded text-xs font-medium
                        {{ $cliente->tipo === 'persona_juridica' ? 'bg-purple-100 text-purple-700' : 'bg-brand-100 text-brand-800' }}">
                        {{ $cliente->tipo === 'persona_juridica' ? 'Persona Jurídica' : 'Persona Física' }}
                    </span>
                </div>
            </div>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">DNI / NIT</dt>
                    <dd class="font-medium">{{ $cliente->dni }}</dd>
                </div>
                @if($cliente->email)
                <div class="flex justify-between">
                    <dt class="text-gray-500">Email</dt>
                    <dd><a href="mailto:{{ $cliente->email }}" class="text-brand-700 hover:underline">{{ $cliente->email }}</a></dd>
                </div>
                @endif
                @if($cliente->telefono)
                <div class="flex justify-between">
                    <dt class="text-gray-500">Teléfono</dt>
                    <dd>{{ $cliente->telefono }}</dd>
                </div>
                @endif
                @if($cliente->direccion)
                <div class="flex justify-between">
                    <dt class="text-gray-500">Dirección</dt>
                    <dd class="text-right">{{ $cliente->direccion }}</dd>
                </div>
                @endif
                <div class="flex justify-between pt-2 border-t">
                    <dt class="text-gray-500">Estado</dt>
                    <dd>
                        <span class="px-2 py-0.5 rounded text-xs font-medium
                            {{ $cliente->activo ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $cliente->activo ? 'Activo' : 'Inactivo' }}
                        </span>
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Expedientes y Trámites --}}
    <div class="col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-sm">
            <div class="flex items-center justify-between p-5 border-b">
                <h3 class="font-semibold text-gray-700">
                    <i class="fas fa-folder-open text-brand-600 mr-2"></i>
                    Expedientes ({{ $cliente->expedientes->count() }})
                </h3>
                @if(auth()->user()->puede('expedientes', 'crear'))
                <a href="{{ route('expedientes.index', ['nuevo' => 1, 'cliente_id' => $cliente->id]) }}"
                   class="text-xs text-brand-700 hover:underline">+ Nuevo</a>
                @endif
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($cliente->expedientes as $exp)
                <div class="px-5 py-4 hover:bg-gray-50 transition flex items-center gap-4">
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('expedientes.show', $exp) }}"
                           class="font-medium text-brand-800 hover:underline block truncate">
                            {{ $exp->caratula }}
                        </a>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $exp->numero }} · {{ ucfirst($exp->tipo_causa) }} · {{ $exp->juzgado ?? 'Sin juzgado' }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        @if($exp->fecha_recepcion)
                        <span class="text-xs text-gray-500">Recepción {{ $exp->fecha_recepcion->format('d/m/Y') }}</span>
                        @endif
                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-{{ $exp->estado_color }}-100 text-{{ $exp->estado_color }}-700">
                            {{ $exp->estado_label }}
                        </span>
                        <a href="{{ route('expedientes.show', $exp) }}" class="text-gray-400 hover:text-brand-700">
                            <i class="fas fa-arrow-right text-xs"></i>
                        </a>
                    </div>
                </div>
                @empty
                <div class="px-5 py-10 text-center text-gray-400">
                    <i class="fas fa-folder-open text-3xl mb-2"></i>
                    <p class="text-sm">Este cliente no tiene expedientes aún.</p>
                    @if(auth()->user()->puede('expedientes', 'crear'))
                    <a href="{{ route('expedientes.index', ['nuevo' => 1, 'cliente_id' => $cliente->id]) }}"
                       class="mt-3 inline-block text-sm text-brand-700 hover:underline">
                        Crear primer expediente
                    </a>
                    @endif
                </div>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm">
            <div class="flex items-center justify-between p-5 border-b">
                <h3 class="font-semibold text-gray-700">
                    <i class="fas fa-file-circle-check text-cyan-600 mr-2"></i>
                    Trámites ({{ $cliente->tramites->count() }})
                </h3>
                @if(auth()->user()->puede('tramites', 'crear'))
                <a href="{{ route('tramites.index', ['nuevo' => 1]) }}"
                   class="text-xs text-brand-700 hover:underline">+ Nuevo</a>
                @endif
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($cliente->tramites as $tra)
                <div class="px-5 py-4 hover:bg-gray-50 transition flex items-center gap-4">
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('tramites.show', $tra) }}"
                           class="font-medium text-brand-800 hover:underline block truncate">
                            {{ $tra->nombre }}
                        </a>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $tra->codigo }} · {{ $tra->tipoTramite?->nombre ?? 'Sin tipo' }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <span class="text-xs text-gray-500">Inicio {{ $tra->fecha_inicio->format('d/m/Y') }}</span>
                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-{{ $tra->estado_color }}-100 text-{{ $tra->estado_color }}-700">
                            {{ $tra->estado_label }}
                        </span>
                        <a href="{{ route('tramites.show', $tra) }}" class="text-gray-400 hover:text-brand-700">
                            <i class="fas fa-arrow-right text-xs"></i>
                        </a>
                    </div>
                </div>
                @empty
                <div class="px-5 py-10 text-center text-gray-400">
                    <i class="fas fa-file-circle-check text-3xl mb-2"></i>
                    <p class="text-sm">Este cliente no tiene trámites aún.</p>
                    @if(auth()->user()->puede('tramites', 'crear'))
                    <a href="{{ route('tramites.index', ['nuevo' => 1]) }}"
                       class="mt-3 inline-block text-sm text-brand-700 hover:underline">
                        Crear primer trámite
                    </a>
                    @endif
                </div>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection
