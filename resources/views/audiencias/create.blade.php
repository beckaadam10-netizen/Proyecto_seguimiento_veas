@extends('layouts.app')

@section('title', 'Nueva Audiencia')
@section('header', 'Nueva Audiencia')

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ route('audiencias.store') }}" class="space-y-6" id="form-audiencia-crear">
        @csrf

        <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
            <h3 class="font-semibold text-gray-700 border-b pb-2">Datos de la audiencia</h3>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Expediente *</label>
                <select name="expediente_id" id="select-expediente-audiencia-crear" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    <option value="">— Seleccioná un expediente —</option>
                    @foreach($expedientes as $exp)
                        <option value="{{ $exp->id }}" {{ old('expediente_id', $expediente_id) == $exp->id ? 'selected' : '' }}
                                data-cliente-nombre="{{ $exp->cliente->nombre_completo }}"
                                data-cliente-whatsapp="{{ $exp->cliente->telefono_whatsapp }}">
                            {{ $exp->numero }} — {{ $exp->cliente->nombre_completo }} | {{ Str::limit($exp->caratula, 45) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Título *</label>
                <input type="text" name="titulo" value="{{ old('titulo') }}" required
                       placeholder="Ej: Audiencia preliminar"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                    <div class="flex gap-2">
                        <select name="tipo_audiencia_id" id="select-tipo-audiencia-crear" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            <option value="">— Seleccioná un tipo —</option>
                            @foreach($tiposAudiencia as $t)
                                <option value="{{ $t->id }}" {{ old('tipo_audiencia_id') == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
                            @endforeach
                        </select>
                        @if(auth()->user()->puede('parametros', 'crear'))
                        <button type="button" onclick="abrirModal('modal-tipo-audiencia-rapido')" title="Nuevo tipo de audiencia"
                                class="shrink-0 border border-gray-300 rounded-lg w-9 h-9 flex items-center justify-center text-gray-500 hover:text-brand-700 hover:border-brand-400">
                            <i class="fas fa-plus"></i>
                        </button>
                        @endif
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado *</label>
                    <select name="estado" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        @foreach($estados as $e)
                            <option value="{{ $e }}" {{ old('estado', 'programada') === $e ? 'selected' : '' }}>{{ ucfirst($e) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha y hora *</label>
                    <input type="datetime-local" name="fecha_hora" value="{{ old('fecha_hora') }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Modalidad *</label>
                    <select name="modalidad" id="select-modalidad-crear" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        <option value="presencial" {{ old('modalidad', 'presencial') === 'presencial' ? 'selected' : '' }}>Presencial</option>
                        <option value="virtual" {{ old('modalidad') === 'virtual' ? 'selected' : '' }}>Virtual</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4" id="campos-presencial-crear">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lugar</label>
                    <input type="text" name="lugar" value="{{ old('lugar') }}"
                           placeholder="Ej: Tribunales Civiles, Edificio A"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sala</label>
                    <input type="text" name="sala" value="{{ old('sala') }}" placeholder="Ej: Sala 3"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Abogado responsable</label>
                <div class="flex gap-2">
                    <select name="abogado_id" id="select-abogado-audiencia-crear"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                        <option value="">— Sin asignar —</option>
                        @foreach($abogados as $a)
                            <option value="{{ $a->id }}" {{ old('abogado_id') == $a->id ? 'selected' : '' }}>{{ $a->nombre }}</option>
                        @endforeach
                    </select>
                    @if(auth()->user()->puede('abogados', 'crear'))
                    <button type="button" title="Nuevo abogado"
                            onclick="abrirAltaRapida({titulo: 'Nuevo abogado', etiqueta: 'Nombre', placeholder: 'Ej: Juan Pérez', url: '{{ route('abogados.store') }}', selectId: 'select-abogado-audiencia-crear'})"
                            class="shrink-0 border border-gray-300 rounded-lg w-9 h-9 flex items-center justify-center text-gray-500 hover:text-brand-700 hover:border-brand-400">
                        <i class="fas fa-plus"></i>
                    </button>
                    @endif
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                <input type="checkbox" name="notificar_cliente" value="1" {{ old('notificar_cliente') ? 'checked' : '' }}
                       class="w-4 h-4 text-brand-700 rounded">
                <i class="fab fa-whatsapp text-emerald-600"></i>
                Notificar al cliente por WhatsApp
            </label>
        </div>

        <!-- Botones de acción -->
        <div class="flex gap-3">
            <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-medium">
                <i class="fas fa-save mr-1"></i> Registrar Audiencia
            </button>
            <a href="{{ route('audiencias.index') }}" class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                Cancelar
            </a>
        </div>
    </form>
</div>

{{-- Modal flotante: alta rápida de tipo de audiencia --}}
<div id="modal-tipo-audiencia-rapido" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-tipo-audiencia-rapido')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-sm">
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h3 class="font-semibold text-gray-800 text-lg">Nuevo tipo de audiencia</h3>
                <button type="button" onclick="cerrarModal('modal-tipo-audiencia-rapido')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text" id="input-tipo-audiencia-rapido-nombre" placeholder="Ej: Audiencia de conciliación"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    <p id="error-tipo-audiencia-rapido" class="text-xs text-red-600 mt-1 hidden"></p>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="crearTipoAudienciaRapido()"
                            class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                        Guardar
                    </button>
                    <button type="button" onclick="cerrarModal('modal-tipo-audiencia-rapido')"
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
    (function () {
        const selectModalidad = document.getElementById('select-modalidad-crear');
        const camposPresencial = document.getElementById('campos-presencial-crear');
        if (!selectModalidad || !camposPresencial) return;

        function actualizarModalidad() {
            const esVirtual = selectModalidad.value === 'virtual';
            camposPresencial.classList.toggle('hidden', esVirtual);
            if (esVirtual) {
                camposPresencial.querySelectorAll('input').forEach(input => input.value = '');
            }
        }

        selectModalidad.addEventListener('change', actualizarModalidad);
        actualizarModalidad();
    })();

    function crearTipoAudienciaRapido() {
        const input = document.getElementById('input-tipo-audiencia-rapido-nombre');
        const error = document.getElementById('error-tipo-audiencia-rapido');
        const nombre = input.value.trim();

        error.classList.add('hidden');

        if (!nombre) {
            error.textContent = 'El nombre es obligatorio.';
            error.classList.remove('hidden');
            return;
        }

        fetch('{{ route('parametros.tipos-audiencia.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    ?? document.querySelector('input[name=_token]').value,
            },
            body: JSON.stringify({ nombre }),
        })
            .then(async (res) => {
                const body = await res.json();
                if (!res.ok) {
                    throw new Error(body.errors?.nombre?.[0] ?? 'No se pudo crear el tipo de audiencia.');
                }
                return body;
            })
            .then((tipo) => {
                const select = document.getElementById('select-tipo-audiencia-crear');
                const opcion = new Option(tipo.nombre, tipo.id, true, true);
                select.add(opcion);
                input.value = '';
                cerrarModal('modal-tipo-audiencia-rapido');
            })
            .catch((err) => {
                error.textContent = err.message;
                error.classList.remove('hidden');
            });
    }
    (function () {
        const form = document.getElementById('form-audiencia-crear');
        if (!form) return;
        form.addEventListener('submit', function (e) {
            const checkbox = form.querySelector('input[name=notificar_cliente]');
            if (!checkbox || !checkbox.checked || form.dataset.whatsappConfirmado === '1') {
                return;
            }
            const select = document.getElementById('select-expediente-audiencia-crear');
            const opcion = select.options[select.selectedIndex];
            if (!opcion || !opcion.dataset.clienteWhatsapp) {
                return;
            }
            e.preventDefault();
            const titulo = form.querySelector('input[name=titulo]').value.trim();
            const fecha = form.querySelector('input[name=fecha_hora]').value;
            const lugar = form.querySelector('input[name=lugar]').value.trim();
            const sala = form.querySelector('input[name=sala]').value.trim();

            let fechaTexto = '';
            if (fecha) {
                const [fechaParte, horaParte] = fecha.split('T');
                fechaTexto = `${fechaParte.split('-').reverse().join('/')} a las ${horaParte}`;
            }

            let texto = `Hola ${opcion.dataset.clienteNombre}, te confirmamos que se programó una audiencia: "${titulo}", el ${fechaTexto}.`;
            if (lugar && sala) {
                texto += ` Lugar: ${lugar} (Sala ${sala}).`;
            } else if (lugar) {
                texto += ` Lugar: ${lugar}.`;
            } else if (sala) {
                texto += ` Sala: ${sala}.`;
            }

            mostrarPreviaWhatsapp(form, texto, opcion.dataset.clienteWhatsapp);
        });
    })();
</script>
@endpush
@endsection
