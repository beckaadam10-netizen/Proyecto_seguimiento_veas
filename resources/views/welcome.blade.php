<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Consultá tu trámite | Estudio Jurídico</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#fbf3e4', 100: '#f5e1b8', 200: '#efce87', 300: '#e8bb56',
                            400: '#edbf02', 500: '#e0a400', 600: '#d27012', 700: '#a85a0f',
                            800: '#5c3a12', 900: '#222323',
                        },
                    },
                },
            },
        };
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .hero-pattern {
            background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.06) 1px, transparent 0);
            background-size: 22px 22px;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans min-h-screen flex flex-col">

    <header class="bg-brand-900 text-white relative z-10">
        <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="{{ asset('imagen/logo-icono.png') }}" alt="" class="h-10">
                <h1 class="text-lg font-bold leading-tight">
                    <span class="bg-gradient-to-r from-brand-300 via-brand-400 to-brand-600 bg-clip-text text-transparent">Vidal Escalante</span>
                    <span class="text-sm font-normal text-stone-300">&amp; Asociados</span>
                </h1>
            </div>
            <a href="{{ route('login') }}" class="text-sm text-stone-200 hover:text-brand-300 flex items-center gap-2 transition">
                Iniciar sesión <i class="fas fa-arrow-right-to-bracket"></i>
            </a>
        </div>
    </header>

    {{-- Hero --}}
    <div class="bg-gradient-to-br from-brand-900 via-stone-900 to-brand-900 hero-pattern pt-14 pb-28 px-6 text-center">
        <img src="{{ asset('imagen/logo-icono.png') }}" alt="" class="h-16 mx-auto mb-3">
        <p class="text-lg font-semibold">
            <span class="bg-gradient-to-r from-brand-300 via-brand-400 to-brand-600 bg-clip-text text-transparent">Vidal Escalante</span>
            <span class="text-stone-300 font-normal">&amp; Asociados</span>
        </p>
        <h2 class="text-3xl md:text-4xl font-bold text-white mt-3">Seguimiento de trámites y expedientes</h2>
        <p class="text-stone-300 mt-3 max-w-xl mx-auto">
            Consultá el avance de tu caso, las actuaciones registradas y el estado de cuenta, en cualquier momento.
        </p>
    </div>

    <main class="max-w-5xl mx-auto px-6 -mt-16 pb-14 flex-1 w-full">

        {{-- Buscador --}}
        <div class="bg-white rounded-2xl shadow-xl p-6 md:p-8 mb-10 border border-gray-100">
            <h3 class="text-xl font-semibold text-gray-800 mb-1">
                <i class="fas fa-magnifying-glass text-brand-600 mr-1"></i> Consultá el estado de tu trámite
            </h3>
            <p class="text-sm text-gray-500 mb-5">Ingresá tu nombre completo y tu C.I/NIT, tal como los tenemos registrados.</p>

            <form method="POST" action="{{ route('rastreo.buscar') }}" class="flex flex-wrap gap-3 items-end">
                @csrf
                <div class="flex-1 min-w-56">
                    <label class="block text-xs text-gray-500 mb-1">Nombre completo</label>
                    <div class="relative">
                        <i class="fas fa-user absolute left-3 top-1/2 -translate-y-1/2 text-gray-300 text-sm"></i>
                        <input type="text" name="nombre" value="{{ $nombreBuscado ?? old('nombre') }}" required
                               placeholder="Ej: Rebeca Adan"
                               class="w-full border border-gray-300 rounded-lg pl-9 pr-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 focus:border-brand-400">
                    </div>
                </div>
                <div class="flex-1 min-w-40">
                    <label class="block text-xs text-gray-500 mb-1">C.I/NIT</label>
                    <div class="relative">
                        <i class="fas fa-id-card absolute left-3 top-1/2 -translate-y-1/2 text-gray-300 text-sm"></i>
                        <input type="text" name="dni" value="{{ $dniBuscado ?? old('dni') }}" required
                               placeholder="Ej: 84303480"
                               class="w-full border border-gray-300 rounded-lg pl-9 pr-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 focus:border-brand-400">
                    </div>
                </div>
                <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2.5 rounded-lg text-sm font-semibold flex items-center gap-2 shadow-sm shadow-brand-600/30 transition">
                    <i class="fas fa-magnifying-glass"></i> Consultar
                </button>
            </form>

            @error('nombre') <p class="text-xs text-red-600 mt-3"><i class="fas fa-circle-exclamation mr-1"></i>{{ $message }}</p> @enderror
            @error('dni') <p class="text-xs text-red-600 mt-1"><i class="fas fa-circle-exclamation mr-1"></i>{{ $message }}</p> @enderror

            @isset($error)
                <div class="mt-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 flex items-center gap-2">
                    <i class="fas fa-circle-exclamation"></i> {{ $error }}
                </div>
            @endisset
        </div>

        {{-- Resultado --}}
        @isset($registro)
        <div class="bg-white rounded-2xl shadow-sm p-6 md:p-8 border border-gray-100">

            {{-- Selector si tiene más de un trámite/expediente --}}
            @if($registros->count() > 1)
            <form method="POST" action="{{ route('rastreo.buscar') }}" class="flex flex-wrap gap-2 mb-6">
                @csrf
                <input type="hidden" name="nombre" value="{{ $nombreBuscado }}">
                <input type="hidden" name="dni" value="{{ $dniBuscado }}">
                @foreach($registros as $r)
                    <button type="submit" name="registro" value="{{ $r->tipo }}-{{ $r->id }}"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium border transition flex items-center gap-1.5 {{ $r->tipo === $registro->tipo && $r->id === $registro->id ? 'bg-brand-600 text-white border-brand-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                        <i class="fas {{ $r->tipo === 'tramite' ? 'fa-file-circle-check' : 'fa-folder-open' }}"></i>
                        {{ $r->codigo }}
                    </button>
                @endforeach
            </form>
            @endif

            <div class="flex items-center justify-between mb-1 flex-wrap gap-2">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-timeline text-brand-700 mr-2"></i>
                        Línea de tiempo del {{ $registro->tipo === 'tramite' ? 'trámite' : 'expediente' }}
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ $registro->tipo === 'tramite' ? 'Trámite' : 'Expediente' }}: {{ $registro->titulo }} · Código: {{ $registro->codigo }}
                    </p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-{{ $registro->modelo->estado_color }}-100 text-{{ $registro->modelo->estado_color }}-700">
                    {{ strtoupper($registro->modelo->estado_label) }}
                </span>
            </div>

            <div class="mt-6 space-y-0">
                @foreach($etapas as $i => $etapa)
                @php
                    $tieneDetalle = $etapa['completada'] && ($etapa['gastos']->isNotEmpty() || $etapa['actualizaciones']->isNotEmpty() || $etapa['descripcion'] || $etapa['observaciones']);
                    $idEtapa = 'etapa-' . $i;
                @endphp
                <div class="flex gap-4">
                    {{-- Columna del ícono + línea vertical --}}
                    <div class="flex flex-col items-center">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm flex-shrink-0
                            {{ $etapa['actual'] ? 'bg-brand-600 text-white ring-4 ring-brand-100' : ($etapa['completada'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400') }}">
                            <i class="fas {{ $etapa['completada'] ? 'fa-check' : 'fa-circle-notch' }}"></i>
                        </div>
                        @if(!$loop->last)
                            <div class="w-0.5 flex-1 {{ $etapa['completada'] ? 'bg-green-200' : 'bg-gray-200' }} min-h-8"></div>
                        @endif
                    </div>

                    {{-- Contenido de la etapa --}}
                    <div class="flex-1 pb-6">
                        <button type="button"
                                @if($tieneDetalle) onclick="toggleEtapa('{{ $idEtapa }}')" @endif
                                class="w-full text-left rounded-lg -mx-2 px-2 py-1 transition {{ $tieneDetalle ? 'cursor-pointer hover:bg-gray-50' : 'cursor-default' }}">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <div>
                                    <p class="font-semibold {{ $etapa['completada'] ? 'text-gray-800' : 'text-gray-400' }}">
                                        {{ strtoupper($etapa['label']) }}
                                        @if($etapa['actual'])
                                            <span class="ml-2 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-brand-100 text-brand-700 align-middle">ACTUAL</span>
                                        @endif
                                    </p>
                                    @if($etapa['descripcion'])
                                        <p class="text-xs text-gray-500 mt-0.5">{{ Str::limit($etapa['descripcion'], 90) }}</p>
                                    @endif
                                </div>
                                <div class="text-right text-xs text-gray-500 flex-shrink-0">
                                    <p><i class="far fa-calendar mr-1"></i> {{ $etapa['fecha']?->format('d/m/Y') ?? 'Pendiente' }}</p>
                                </div>
                                @if($tieneDetalle)
                                    <i id="icono-{{ $idEtapa }}" class="fas fa-chevron-down text-gray-300 text-xs"></i>
                                @endif
                            </div>
                        </button>

                        @if($tieneDetalle)
                        <div id="{{ $idEtapa }}" class="hidden mt-3 bg-gray-50 rounded-lg p-4 text-sm space-y-3">
                            @if($etapa['descripcion'])
                                <p class="text-gray-700">{{ $etapa['descripcion'] }}</p>
                            @endif

                            @if($etapa['observaciones'])
                                <p class="text-gray-600 text-xs">{{ $etapa['observaciones'] }}</p>
                            @endif

                            @if($etapa['gastos']->isNotEmpty())
                            <div>
                                <button type="button" onclick="toggleGastos('{{ $idEtapa }}')"
                                        class="w-full flex items-center justify-between text-xs font-semibold text-amber-700 uppercase hover:text-amber-800">
                                    <span><i class="fas fa-coins mr-1"></i> Gastos en esta etapa</span>
                                    <span class="flex items-center gap-2 normal-case">
                                        {{ number_format($etapa['gastos']->sum('monto'), 2) }} Bs
                                        <i id="icono-gastos-{{ $idEtapa }}" class="fas fa-chevron-down text-[10px]"></i>
                                    </span>
                                </button>
                                <ul id="gastos-{{ $idEtapa }}" class="hidden space-y-1 mt-2">
                                    @foreach($etapa['gastos'] as $gasto)
                                    <li class="flex justify-between text-gray-600 text-xs bg-white rounded px-3 py-2">
                                        <span>{{ $gasto->concepto }} · {{ $gasto->fecha->format('d/m/Y') }}</span>
                                        <span class="font-semibold text-amber-700">{{ number_format($gasto->monto, 2) }} Bs</span>
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif

                            @if($etapa['actualizaciones']->isNotEmpty())
                            <div>
                                <button type="button" onclick="toggleSeguimientos('{{ $idEtapa }}')"
                                        class="w-full flex items-center justify-between text-xs font-semibold text-brand-700 uppercase hover:text-brand-800">
                                    <span><i class="fas fa-list-check mr-1"></i> Seguimiento del caso</span>
                                    <span class="flex items-center gap-2 normal-case">
                                        {{ $etapa['actualizaciones']->count() }} {{ Str::plural('novedad', $etapa['actualizaciones']->count()) }}
                                        <i id="icono-seguimientos-{{ $idEtapa }}" class="fas fa-chevron-down text-[10px]"></i>
                                    </span>
                                </button>
                                <ul id="seguimientos-{{ $idEtapa }}" class="hidden space-y-1 mt-2">
                                    @foreach($etapa['actualizaciones'] as $act)
                                    <li class="text-gray-600 text-xs bg-white rounded px-3 py-2">
                                        <div class="flex justify-between gap-2">
                                            <span class="text-gray-700 whitespace-pre-line">{{ $act->texto }}</span>
                                            <span class="text-gray-400 flex-shrink-0">{{ $act->created_at->format('d/m/Y') }}</span>
                                        </div>
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            <p class="text-xs text-gray-400 mt-2">
                <i class="fas fa-circle-info mr-1"></i> La línea de tiempo muestra las etapas principales y el estado actual.
            </p>

            @if($registro->modelo->saldo_pendiente > 0)
            <div class="mt-6 pt-5 border-t border-gray-100">
                <h4 class="text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-file-invoice-dollar text-amber-600 mr-1"></i> Saldo pendiente
                </h4>
                <ul class="space-y-1 text-sm">
                    @foreach($pendientesPorActuacion as $actuacion => $monto)
                    <li class="flex justify-between gap-3 text-gray-600">
                        <span class="truncate">{{ Str::limit($actuacion, 55) }}</span>
                        <span class="font-medium flex-shrink-0">{{ number_format($monto, 2) }} Bs</span>
                    </li>
                    @endforeach
                </ul>
                <div class="flex justify-between items-center mt-2 pt-2 border-t border-gray-100">
                    <span class="text-sm font-semibold text-gray-700">Total a pagar</span>
                    <span class="text-base font-bold text-red-600">{{ number_format($registro->modelo->saldo_pendiente, 2) }} Bs</span>
                </div>
            </div>
            @endif
        </div>
        @endisset

        {{-- Propuesta de valor: solo si todavía no se hizo una búsqueda --}}
        @if(!isset($registro) && !isset($error))
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
                <div class="w-11 h-11 rounded-lg bg-brand-100 text-brand-700 flex items-center justify-center text-lg mb-3">
                    <i class="fas fa-timeline"></i>
                </div>
                <h4 class="font-semibold text-gray-800 text-sm mb-1">Estado en tiempo real</h4>
                <p c    lass="text-xs text-gray-500">Mirá en qué etapa está tu trámite o expediente, actualizado por el estudio.</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
                <div class="w-11 h-11 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center text-lg mb-3">
                    <i class="fas fa-list-check"></i>
                </div>
                <h4     s="font-semibold text-gray-800 text-sm mb-1">Actuaciones al día</h4>
                <p class="text-xs text-gray-500">Consultá cada actuación registrada, con fecha y responsable.</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
                <div class="w-11 h-11 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-lg mb-3">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <h4 class="font-semibold text-gray-800 text-sm mb-1">Gastos y documentos</h4>
                <p class="text-xs text-gray-500">Vé los gastos y los documentos subidos en cada etapa de tu caso.</p>
            </div>
        </div>
        @endif

    </main>

    <footer class="text-center text-xs text-gray-400 py-6">
        &copy; {{ date('Y') }} Vidal Escalante &amp; Asociados. Todos los derechos reservados.
    </footer>

    <script>
        function toggleEtapa(id) {
            const panel = document.getElementById(id);
            const icono = document.getElementById('icono-' + id);
            if (!panel) return;
            panel.classList.toggle('hidden');
            if (icono) icono.classList.toggle('fa-chevron-up');
        }

        function toggleGastos(idEtapa) {
            const lista = document.getElementById('gastos-' + idEtapa);
            const icono = document.getElementById('icono-gastos-' + idEtapa);
            if (!lista) return;
            lista.classList.toggle('hidden');
            if (icono) icono.classList.toggle('fa-chevron-up');
        }

        function toggleSeguimientos(idEtapa) {
            const lista = document.getElementById('seguimientos-' + idEtapa);
            const icono = document.getElementById('icono-seguimientos-' + idEtapa);
            if (!lista) return;
            lista.classList.toggle('hidden');
            if (icono) icono.classList.toggle('fa-chevron-up');
        }
    </script>

</body>
</html>
