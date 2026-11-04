@extends('layouts.app')

@section('title', 'Respaldos')
@section('header', 'Respaldos')

@section('header-actions')
    {{-- La respuesta es la descarga del .zip (la página no cambia): el servidor devuelve la
         cookie "respaldo_listo" con este token para avisar que terminó. --}}
    <form method="POST" action="{{ route('respaldos.crear') }}" id="form-crear-respaldo">
        @csrf
        <input type="hidden" name="token" value="">

        <button type="submit" class="bg-brand-600 hover:bg-brand-700 disabled:opacity-60 text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2">
            <i class="fas fa-file-zipper"></i> <span>Crear respaldo</span>
        </button>
    </form>
@endsection

@section('content')
<div class="max-w-4xl space-y-6">

    @if(session('salida'))
    <pre class="bg-gray-900 text-gray-100 rounded-xl p-4 text-xs whitespace-pre-wrap">{{ session('salida') }}</pre>
    @endif

    <div class="bg-white rounded-xl shadow-sm p-5 text-sm text-gray-600 space-y-3">
        <p>"Crear respaldo" descarga un <strong>.zip</strong> con dos carpetas:</p>
        <div class="grid sm:grid-cols-2 gap-3">
            <div class="border rounded-lg p-3">
                <p class="font-semibold text-gray-800"><i class="fas fa-folder-open text-amber-600 mr-1"></i> DOCUMENTOS</p>
                <p class="text-xs mt-1">
                    Carpetas <strong>EXPEDIENTES</strong> y <strong>TRAMITES</strong>, con una carpeta por caso
                    (<em>Cliente - N° de expediente</em>) y adentro sus documentos y adjuntos, con la fecha y el título como nombre.
                </p>
            </div>
            <div class="border rounded-lg p-3">
                <p class="font-semibold text-gray-800"><i class="fas fa-database text-brand-600 mr-1"></i> BASE DE DATOS</p>
                <p class="text-xs mt-1">
                    <strong>Informacion del sistema.xlsx</strong>: clientes, expedientes, trámites, actuaciones, gastos y cobros, en hojas de Excel.
                    <br><strong>respaldo-completo.sql</strong>: copia exacta de la base, para restaurar el sistema.
                </p>
            </div>
        </div>
        <p class="text-xs text-gray-500">
            También se crea uno solo todos los días a las 3:00 (si el cron del hosting está configurado). Se guardan los de los últimos 14 días.
        </p>
        <p class="text-amber-700">
            <i class="fas fa-triangle-exclamation mr-1"></i>
            Los respaldos de esta lista quedan en el mismo servidor. Guardá uno al menos una vez por semana en tu computadora o en Google Drive:
            si el hosting falla, los de acá se pierden con él.
        </p>
    </div>

    {{-- Google Drive --}}
    <div class="bg-white rounded-xl shadow-sm p-5 text-sm text-gray-600 space-y-3">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <h3 class="font-semibold text-gray-700"><i class="fab fa-google-drive text-green-600 mr-2"></i> Google Drive</h3>
            @if($drive['configurado'])
            <form method="POST" action="{{ route('respaldos.drive.probar') }}">
                @csrf
                <button type="submit" class="text-xs border border-gray-300 hover:bg-gray-50 px-3 py-1.5 rounded-lg font-medium">
                    <i class="fas fa-plug-circle-check"></i> Probar conexión
                </button>
            </form>
            @endif
        </div>

        @if($drive['configurado'])
        <p class="text-green-700">
            <i class="fas fa-circle-check mr-1"></i>
            Activado: cada respaldo (el del botón y el automático de las 3:00) se sube a tu carpeta de Drive.
            En Drive también se conservan {{ config('services.google_drive.conservar') }} días: los más viejos van a la papelera de Drive.
        </p>
        @else
        <p class="text-amber-700"><i class="fas fa-circle-info mr-1"></i> Todavía no está configurado. Se hace una sola vez:</p>
        <ol class="list-decimal ml-5 space-y-2 text-xs">
            <li>Entrá a <a href="https://script.google.com/home/projects/create" target="_blank" class="text-brand-700 underline">script.google.com</a>
                con la cuenta de Google <strong>dueña de la carpeta</strong> y creá un proyecto nuevo (nombre: <em>Respaldos VEAS</em>).</li>
            <li>Borrá lo que tiene y pegá este código. Reemplazá <code>PEGAR_ACA_LA_CLAVE</code> por esta clave
                (se genera una nueva cada vez que abrís esta página; usá la misma en los pasos 2 y 4):
                <div class="flex gap-2 items-center my-2">
                    <code id="clave-drive" class="bg-gray-100 px-2 py-1 rounded select-all">{{ $drive['clave_sugerida'] }}</code>
                    <button type="button" onclick="copiarTexto('clave-drive', this)" class="text-brand-700 hover:underline">Copiar clave</button>
                </div>
                <div class="relative">
                    <pre id="script-drive" class="bg-gray-900 text-gray-100 rounded-lg p-3 overflow-x-auto max-h-64">{{ $drive['script'] }}</pre>
                    <button type="button" onclick="copiarTexto('script-drive', this)" class="absolute top-2 right-2 text-xs bg-white/90 px-2 py-1 rounded">Copiar código</button>
                </div>
            </li>
            <li>Tocá <strong>Implementar → Nueva implementación</strong>, tipo <strong>Aplicación web</strong>:
                <em>Ejecutar como:</em> <strong>Yo</strong>; <em>Quién tiene acceso:</em> <strong>Cualquier usuario</strong>.
                Autorizá los permisos que pide (Google avisa que la app "no está verificada" porque es tuya: <em>Configuración avanzada → Ir a Respaldos VEAS</em>).
                Copiá la <strong>URL de la aplicación web</strong>.</li>
            <li>En el <code>.env</code> del servidor agregá y guardá:
<pre class="bg-gray-100 rounded-lg p-2 mt-1">GOOGLE_DRIVE_SCRIPT_URL=la URL de la aplicación web
GOOGLE_DRIVE_CLAVE=la misma clave del paso 2</pre>
                y corré <code>php artisan optimize:clear</code>. Al volver a esta página aparece "Probar conexión".</li>
        </ol>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-5 border-b">
            <h3 class="font-semibold text-gray-700">
                <i class="fas fa-box-archive text-brand-600 mr-2"></i> Respaldos guardados ({{ $respaldos->count() }})
            </h3>
        </div>
        <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Fecha</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Archivo</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Tamaño</th>
                    @if($drive['configurado'])
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Google Drive</th>
                    @endif
                    <th class="px-4 py-3 text-right font-semibold text-gray-600"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($respaldos as $r)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 text-gray-700 whitespace-nowrap">{{ $r->fecha->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ $r->nombre }}</td>
                    <td class="px-4 py-3 text-right text-gray-600 whitespace-nowrap">{{ $r->tamanio }}</td>
                    @if($drive['configurado'])
                    @php $enDrive = $drive['registro'][$r->nombre] ?? null; @endphp
                    <td class="px-4 py-3 text-xs whitespace-nowrap">
                        @if(($enDrive['estado'] ?? null) === 'subido')
                            <a href="{{ $enDrive['link'] }}" target="_blank" class="text-green-700 hover:underline"><i class="fas fa-circle-check"></i> Ver en Drive</a>
                        @else
                            @if($enDrive)
                            <span class="text-red-600 block max-w-[16rem] truncate" title="{{ $enDrive['error'] ?? '' }}"><i class="fas fa-circle-exclamation"></i> Falló: {{ $enDrive['error'] ?? '' }}</span>
                            @endif
                            <form method="POST" action="{{ route('respaldos.drive.subir', $r->nombre) }}" class="inline"
                                  onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').textContent = 'Subiendo…';">
                                @csrf
                                <button type="submit" class="text-brand-700 hover:underline"><i class="fas fa-cloud-arrow-up"></i> Subir a Drive</button>
                            </form>
                        @endif
                    </td>
                    @endif
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <a href="{{ route('respaldos.descargar', $r->nombre) }}" class="text-xs text-brand-700 hover:underline font-medium">
                            <i class="fas fa-download"></i> Descargar
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $drive['configurado'] ? 5 : 4 }}" class="px-4 py-10 text-center text-gray-400">
                        <i class="fas fa-box-archive text-3xl mb-2"></i>
                        <p>Todavía no hay respaldos. Creá el primero con "Crear respaldo".</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-5 text-sm text-gray-600 space-y-2">
        <h3 class="font-semibold text-gray-700"><i class="fas fa-rotate-left mr-1"></i> Cómo restaurar el sistema</h3>
        <p><strong>Base de datos:</strong> en phpMyAdmin, elegí la base → <em>Importar</em> → subí <code>BASE DE DATOS/respaldo-completo.sql</code>.
            Reemplaza todos los datos actuales por los del respaldo.</p>
        <p><strong>Documentos:</strong> la hoja <em>Archivos</em> del Excel indica, para cada documento, dónde está en el respaldo y en qué
            ruta del sistema tiene que volver a quedar (dentro de <code>storage/app/public</code>).</p>
    </div>
</div>
@endsection

@push('scripts')
<script>
function copiarTexto(id, boton) {
    navigator.clipboard.writeText(document.getElementById(id).innerText).then(() => {
        const texto = boton.textContent;
        boton.textContent = '¡Copiado!';
        setTimeout(() => boton.textContent = texto, 1500);
    });
}

document.getElementById('form-crear-respaldo')?.addEventListener('submit', function () {
    const boton = this.querySelector('button');
    const token = Date.now().toString();
    this.querySelector('[name=token]').value = token;

    boton.disabled = true;
    boton.querySelector('span').textContent = 'Creando respaldo… puede tardar unos minutos';
    boton.querySelector('i').className = 'fas fa-spinner fa-spin';

    // No se recarga antes de tiempo: cancelaría la descarga. Se espera la cookie que
    // manda el servidor junto con el archivo y recién ahí se refresca la lista.
    const espera = setInterval(() => {
        if (document.cookie.split('; ').includes('respaldo_listo=' + token)) {
            clearInterval(espera);
            setTimeout(() => location.reload(), 1500);
        }
    }, 1000);
});
</script>
@endpush
