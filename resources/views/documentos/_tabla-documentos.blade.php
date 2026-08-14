{{-- Requiere: $registros. Se usa tanto en la carga normal de la página como en la
     respuesta AJAX del buscador en vivo (DocumentoController@index). Incluye los
     modales "Ver documentos" por fila porque tienen que viajar junto con la fila
     que los abre. --}}
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Tipo</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Código</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Nombre / Carátula</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Cliente</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600">Documentos</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($registros as $item)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3">
                    <span class="px-2 py-0.5 rounded text-xs font-medium {{ $item->tipo_registro === 'tramite' ? 'bg-cyan-100 text-cyan-700' : 'bg-purple-100 text-purple-700' }}">
                        {{ $item->tipo_registro === 'tramite' ? 'Trámite' : 'Expediente' }}
                    </span>
                </td>
                <td class="px-4 py-3">
                    <a href="{{ $item->ruta_show }}" class="font-mono text-xs {{ $item->tipo_registro === 'tramite' ? 'text-cyan-700' : 'text-brand-800' }} hover:underline">
                        {{ $item->codigo_display }}
                    </a>
                </td>
                <td class="px-4 py-3 text-gray-700">{{ Str::limit($item->titulo_display, 30) }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $item->cliente->nombre_completo }}</td>
                <td class="px-4 py-3 text-center text-gray-600">{{ $item->documentos->count() }}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <button type="button" onclick="abrirModal('modal-documentos-{{ $item->tipo_registro }}-{{ $item->id }}')"
                            class="text-xs text-brand-600 hover:underline font-medium">
                        <i class="fas fa-folder-open"></i> Ver documentos
                    </button>
                    @if($item->documentos->isNotEmpty() && auth()->user()->puede('documentos', 'descargar'))
                    <a href="{{ route($item->tipo_registro . 's.documentos.pdf', $item) }}"
                       class="text-xs text-brand-600 hover:underline font-medium ml-3" title="Descargar en PDF">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-4 py-10 text-center text-gray-400">
                    <i class="fas fa-folder-open text-3xl mb-2"></i>
                    <p>No se encontraron trámites ni expedientes.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
    <div class="px-4 py-3 border-t">{{ $registros->links() }}</div>
</div>

{{-- Modales: Ver documentos --}}
@foreach($registros as $item)
<div id="modal-documentos-{{ $item->tipo_registro }}-{{ $item->id }}" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-documentos-{{ $item->tipo_registro }}-{{ $item->id }}')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white rounded-t-xl">
                <div>
                    <h3 class="font-semibold text-gray-800 text-lg">
                        <i class="fas fa-folder-open text-brand-600 mr-2"></i>
                        Documentos <span class="text-xs font-normal text-gray-400 font-mono">{{ $item->codigo_display }}</span>
                        ({{ $item->documentos->count() }})
                    </h3>
                    @if($item->documentos->isNotEmpty() && auth()->user()->puede('documentos', 'descargar'))
                    <a href="{{ route($item->tipo_registro . 's.documentos.pdf', $item) }}"
                       class="text-xs text-brand-600 hover:underline font-medium">
                        <i class="fas fa-file-pdf"></i> Descargar en PDF
                    </a>
                    @endif
                </div>
                <button type="button" onclick="cerrarModal('modal-documentos-{{ $item->tipo_registro }}-{{ $item->id }}')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($item->documentos as $doc)
                @php($esDocumentoSuelto = $doc instanceof \App\Models\Documento)
                <div class="px-5 py-3 hover:bg-gray-50 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0">
                        <i class="fas {{ $doc->icono_archivo }}"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        @if($esDocumentoSuelto)
                            <p class="font-medium text-sm text-gray-800 truncate">{{ $doc->titulo }}</p>
                        @else
                            <a href="{{ route('seguimientos.show', $doc) }}" class="font-medium text-sm text-gray-800 hover:text-brand-700 truncate block">
                                {{ $doc->titulo }}
                            </a>
                        @endif
                        <p class="text-xs text-gray-500">
                            {{ $doc->created_at->format('d/m/Y H:i') }}h
                            @if($esDocumentoSuelto)
                                @if($doc->fojas) · Fs. {{ $doc->fojas }} @endif
                            @else
                                @if($doc->tipoDocumento) · {{ $doc->tipoDocumento->nombre }} @endif
                            @endif
                            @if($doc->usuario) · {{ $doc->usuario->name }} @endif
                        </p>
                    </div>
                    <div class="flex gap-3 text-xs flex-shrink-0">
                        @if(auth()->user()->puede('documentos', 'ver'))
                        <a href="{{ $esDocumentoSuelto ? route('documentos.archivo.ver', $doc) : route('documentos.ver', $doc) }}" target="_blank"
                           class="text-gray-400 hover:text-brand-700" title="Ver">
                            <i class="fas fa-eye"></i>
                        </a>
                        @endif
                        @if(auth()->user()->puede('documentos', 'descargar'))
                        <a href="{{ $esDocumentoSuelto ? route('documentos.archivo.descargar', $doc) : route('documentos.descargar', $doc) }}"
                           class="text-gray-400 hover:text-brand-700" title="Descargar">
                            <i class="fas fa-download"></i>
                        </a>
                        @endif
                    </div>
                </div>
                @empty
                <p class="p-5 text-sm text-gray-400 text-center">Sin documentos subidos.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endforeach
