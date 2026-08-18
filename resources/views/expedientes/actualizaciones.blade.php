@extends('layouts.app')

@section('title', 'Actualizaciones del caso')
@section('header', 'Actualizaciones del caso')

@section('header-actions')
    <a href="{{ route('expedientes.show', $expediente) }}"
       class="bg-white border hover:bg-gray-50 text-gray-700 px-3 py-2 rounded-lg text-sm flex items-center gap-2">
        <i class="fas fa-arrow-left"></i> Volver al expediente
    </a>
@endsection

@section('content')

<div class="bg-white rounded-xl shadow-sm p-5 mb-6">
    <p class="text-sm text-gray-500">Expediente</p>
    <p class="font-semibold text-gray-800">{{ $expediente->caratula }} <span class="text-gray-400 font-normal font-mono text-sm">— NUREJ {{ $expediente->numero }}</span></p>
    <p class="text-sm text-gray-500">{{ $expediente->cliente->nombre_completo }}</p>
</div>

<div class="bg-white rounded-xl shadow-sm">
    <div class="flex items-center justify-between p-5 border-b">
        <h3 class="font-semibold text-gray-700">
            <i class="fas fa-comment-dots text-blue-500 mr-2"></i>
            Todas las actualizaciones ({{ $expediente->actualizaciones->count() }})
        </h3>
        @if($expediente->whatsapp_url_actualizaciones)
        <a href="{{ $expediente->whatsapp_url_actualizaciones }}" target="_blank"
           class="bg-[#25D366] hover:bg-[#1ebe57] text-white px-3 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
            <i class="fab fa-whatsapp"></i> Enviar por WhatsApp
        </a>
        @endif
    </div>
    @if(auth()->user()->puede('actualizaciones', 'crear'))
    <form method="POST" action="{{ route('expedientes.actualizaciones.store', $expediente) }}" class="p-5 border-b flex gap-2 items-start">
        @csrf
        <textarea name="texto" required rows="2" placeholder="Escribí una novedad del caso (ej. estado en el juzgado, próximos pasos)..."
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400"></textarea>
        <button type="submit" class="shrink-0 bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
            Agregar
        </button>
    </form>
    @endif
    <div class="divide-y divide-gray-100">
        @forelse($expediente->actualizaciones as $act)
        <div class="px-5 py-3">
            <div id="vista-act-{{ $act->id }}" class="flex items-start gap-3">
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-800 whitespace-pre-line">{{ $act->texto }}</p>
                    <p class="text-xs text-gray-400 mt-1">
                        {{ $act->created_at->format('d/m/Y H:i') }}
                        @if($act->usuario) · {{ $act->usuario->name }} @endif
                        @if($act->created_at != $act->updated_at) · <span class="italic">editado</span> @endif
                    </p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    @if(auth()->user()->puede('actualizaciones', 'modificar'))
                    <button type="button" onclick="toggleEdicionActualizacion({{ $act->id }})" class="text-gray-300 hover:text-brand-700" title="Editar">
                        <i class="fas fa-pen text-xs"></i>
                    </button>
                    @endif
                    @if(auth()->user()->puede('actualizaciones', 'eliminar'))
                    <form method="POST" action="{{ route('actualizaciones.destroy', $act) }}" onsubmit="return confirm('¿Eliminar esta actualización?')">
                        @csrf @method('DELETE')
                        <button class="text-gray-300 hover:text-red-500" title="Eliminar">
                            <i class="fas fa-trash text-xs"></i>
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            @if(auth()->user()->puede('actualizaciones', 'modificar'))
            <form id="form-editar-act-{{ $act->id }}" method="POST" action="{{ route('actualizaciones.update', $act) }}" class="hidden mt-2 flex gap-2 items-start">
                @csrf @method('PUT')
                <textarea name="texto" required rows="2"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">{{ $act->texto }}</textarea>
                <button type="submit" class="shrink-0 bg-brand-600 hover:bg-brand-700 text-white px-3 py-2 rounded-lg text-sm font-medium">
                    Guardar
                </button>
                <button type="button" onclick="toggleEdicionActualizacion({{ $act->id }})" class="shrink-0 text-gray-500 hover:text-gray-700 px-3 py-2 text-sm">
                    Cancelar
                </button>
            </form>
            @endif
        </div>
        @empty
        <p class="p-10 text-sm text-gray-400 text-center">
            <i class="fas fa-comment-dots text-3xl mb-2 block"></i>
            Sin actualizaciones registradas todavía.
        </p>
        @endforelse
    </div>
</div>

@endsection

@push('scripts')
<script>
function toggleEdicionActualizacion(id) {
    document.getElementById('vista-act-' + id).classList.toggle('hidden');
    document.getElementById('form-editar-act-' + id).classList.toggle('hidden');
}
</script>
@endpush
