{{-- Requiere: $tipos, $routePrefix, $key, $tituloNuevo --}}
@php($colores = \App\Http\Controllers\EstadoExpedienteController::COLORES)
<div id="modal-{{ $key }}-nuevo" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-{{ $key }}-nuevo')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white rounded-t-xl">
                <h3 class="font-semibold text-gray-800 text-lg">{{ $tituloNuevo }}</h3>
                <button type="button" onclick="cerrarModal('modal-{{ $key }}-nuevo')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <form method="POST" action="{{ route($routePrefix . '.store') }}" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="_modal" value="modal-{{ $key }}-nuevo">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text" name="nombre" value="{{ old('nombre') }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    @error('nombre')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                    <textarea name="descripcion" rows="3"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">{{ old('descripcion') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Color *</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($colores as $color)
                        <label class="cursor-pointer">
                            <input type="radio" name="color" value="{{ $color }}" class="peer hidden"
                                   {{ old('color', 'gray') === $color ? 'checked' : '' }} required>
                            <span class="w-7 h-7 rounded-full bg-{{ $color }}-500 border-2 border-transparent peer-checked:border-gray-800 flex items-center justify-center" title="{{ $color }}"></span>
                        </label>
                        @endforeach
                    </div>
                    @error('color')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-medium">
                        <i class="fas fa-save mr-1"></i> Guardar
                    </button>
                    <button type="button" onclick="cerrarModal('modal-{{ $key }}-nuevo')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($tipos as $tipo)
<div id="modal-{{ $key }}-editar-{{ $tipo->id }}" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-{{ $key }}-editar-{{ $tipo->id }}')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white rounded-t-xl">
                <h3 class="font-semibold text-gray-800 text-lg">Editar: {{ $tipo->nombre }}</h3>
                <button type="button" onclick="cerrarModal('modal-{{ $key }}-editar-{{ $tipo->id }}')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <form method="POST" action="{{ route($routePrefix . '.update', $tipo) }}" class="p-6 space-y-4">
                @csrf @method('PUT')
                <input type="hidden" name="_modal" value="modal-{{ $key }}-editar-{{ $tipo->id }}">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text" name="nombre" value="{{ $tipo->nombre }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                    <textarea name="descripcion" rows="3"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">{{ $tipo->descripcion }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Color *</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($colores as $color)
                        <label class="cursor-pointer">
                            <input type="radio" name="color" value="{{ $color }}" class="peer hidden"
                                   {{ $tipo->color === $color ? 'checked' : '' }} required>
                            <span class="w-7 h-7 rounded-full bg-{{ $color }}-500 border-2 border-transparent peer-checked:border-gray-800 flex items-center justify-center" title="{{ $color }}"></span>
                        </label>
                        @endforeach
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <input type="hidden" name="activo" value="0">
                    <input type="checkbox" name="activo" id="activo-{{ $key }}-{{ $tipo->id }}" value="1" {{ $tipo->activo ? 'checked' : '' }}
                           class="rounded border-gray-300 text-brand-700 focus:ring-brand-400">
                    <label for="activo-{{ $key }}-{{ $tipo->id }}" class="text-sm text-gray-700">Activo</label>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-medium">
                        <i class="fas fa-save mr-1"></i> Guardar cambios
                    </button>
                    <button type="button" onclick="cerrarModal('modal-{{ $key }}-editar-{{ $tipo->id }}')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
