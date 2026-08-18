<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Estudio Jurídico') | Sistema de Gestión</title>
    <link rel="icon" href="{{ asset('imagen/logo.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50:  '#fbf3e4',
                            100: '#f5e1b8',
                            200: '#efce87',
                            300: '#e8bb56',
                            400: '#edbf02',
                            500: '#e0a400',
                            600: '#d27012',
                            700: '#a85a0f',
                            800: '#5c3a12',
                            900: '#222323',
                        },
                    },
                },
            },
        };
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar-link.active {
            @apply bg-gradient-to-r from-brand-600 to-brand-400 text-brand-900 font-semibold shadow-sm;
        }
        .sidebar-scroll {
            scrollbar-width: thin;
            scrollbar-color: #a85a0f transparent;
        }
        .sidebar-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .sidebar-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background-color: #a85a0f;
            border-radius: 9999px;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background-color: #e0a400;
        }

        /* Sidebar colapsable (rail de iconos) en escritorio. El texto se "apaga" con
           font-size:0 (así no hay que envolver cada label en un span aparte) y se
           restaura solo en los íconos. */
        #sidebar {
            width: 16rem;
            transition: width 0.2s ease-in-out;
        }
        body.sidebar-collapsed #sidebar {
            width: 5rem;
        }
        body.sidebar-collapsed #sidebar * {
            font-size: 0;
        }
        body.sidebar-collapsed #sidebar i {
            font-size: 1rem !important;
        }
        body.sidebar-collapsed #sidebar .sidebar-link {
            justify-content: center;
            gap: 0;
        }
        body.sidebar-collapsed #sidebar .sidebar-grouptitle {
            display: none;
        }
        body.sidebar-collapsed #sidebar [id^="grupo-"] {
            display: block !important;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 0.75rem;
            margin-top: 0.5rem;
        }
        #icono-colapsar-der {
            display: none;
        }
        body.sidebar-collapsed #icono-colapsar-izq {
            display: none;
        }
        body.sidebar-collapsed #icono-colapsar-der {
            display: inline-block;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans {{ request()->cookie('sidebar_colapsado') === '1' ? 'sidebar-collapsed' : '' }}">

<div class="flex h-screen overflow-hidden">

    {{-- Overlay para cerrar el sidebar en mobile --}}
    <div id="sidebar-overlay" onclick="toggleSidebar()"
         class="hidden fixed inset-0 bg-black/50 z-30 lg:hidden"></div>

    {{-- ── Sidebar ── --}}
    <aside id="sidebar" class="w-64 bg-brand-900 text-white flex flex-col flex-shrink-0
        fixed inset-y-0 left-0 z-40 -translate-x-full transition-transform duration-200 ease-in-out
        lg:translate-x-0 lg:static">
        <div class="p-5 border-b border-brand-800 flex items-center gap-3">
            <img src="{{ asset('imagen/logo-icono.png') }}" alt="" class="h-11 w-11 object-contain flex-shrink-0">
            <div class="min-w-0">
                <h1 class="text-base font-bold leading-tight">
                    <span class="bg-gradient-to-r from-brand-300 via-brand-400 to-brand-600 bg-clip-text text-transparent">Vidal Escalante</span>
                    <span class="block text-xs font-normal text-stone-300">&amp; Asociados</span>
                </h1>
                <p class="text-[11px] text-stone-400 mt-0.5">Seguimiento de expedientes y trámites</p>
            </div>
            <button type="button" onclick="toggleSidebar()" class="ml-auto text-stone-400 hover:text-white flex-shrink-0 lg:hidden">
                <i class="fas fa-xmark text-lg"></i>
            </button>
            <button type="button" onclick="toggleSidebarCollapse()" title="Colapsar menú"
                    class="hidden lg:flex ml-auto text-stone-400 hover:text-white flex-shrink-0">
                <i id="icono-colapsar-izq" class="fas fa-angles-left"></i>
                <i id="icono-colapsar-der" class="fas fa-angles-right"></i>
            </button>
        </div>

        @php
            $abiertoTramites    = request()->routeIs(['clientes.*', 'abogados.*', 'expedientes.*', 'tramites.*', 'gastos-cobros.*']);
            $abiertoSeguimiento = request()->routeIs(['seguimientos.*', 'audiencias.*', 'documentos.*']);
            $abiertoParametros  = request()->routeIs(['parametros.*', 'administracion.*', 'bitacora.*']);
            $abiertoReportes    = request()->routeIs('reportes.*');

            $u = auth()->user();
            $vDashboard     = $u->puede('dashboard');
            $vClientes      = $u->puede('clientes');
            $vAbogados      = $u->puede('abogados');
            $vExpedientes   = $u->puede('expedientes');
            $vTramites      = $u->puede('tramites');
            $vGastosCobros  = $u->puede('gastos_cobros');
            $vSeguimientos  = $u->puede('seguimientos');
            $vHistorialRevision = $u->puede('historial_revision');
            $vAudiencias    = $u->puede('audiencias');
            $vParametros    = $u->puede('parametros');
            $vAdministracion= $u->puede('administracion');
            $vBitacora      = $u->puede('bitacora');
            $vReportes      = $u->puede('reportes');
            $vReportesPasantes = $u->puede('reportes', 'pasantes');
        @endphp

        <nav class="flex-1 p-4 space-y-1 overflow-y-auto sidebar-scroll">
            @if($vDashboard)
            <a href="{{ route('dashboard') }}" title="Panel de Control"
               class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-stone-300 hover:bg-brand-800 hover:text-brand-300 transition {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fas fa-chart-pie w-5 text-center"></i> Panel de Control
            </a>
            @endif

            @if($vClientes || $vAbogados || $vExpedientes || $vTramites || $vGastosCobros)
            <div class="pt-3 sidebar-grouptitle">
                <button type="button" onclick="toggleGrupo('tramites')"
                        class="w-full flex items-center justify-between px-3 py-1 text-xs uppercase text-brand-500 font-semibold tracking-wider hover:text-brand-300 transition">
                    <span>GESTION DE TRAMITES</span>
                    <i id="chevron-tramites" class="fas fa-chevron-down text-[10px] transition-transform {{ $abiertoTramites ? 'rotate-180' : '' }}"></i>
                </button>
            </div>

            <div id="grupo-tramites" class="space-y-1 {{ $abiertoTramites ? '' : 'hidden' }}">
                @if($vClientes)
                <a href="{{ route('clientes.index') }}" title="Clientes"
                   class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-stone-300 hover:bg-brand-800 hover:text-brand-300 transition {{ request()->routeIs('clientes.*') ? 'active' : '' }}">
                    <i class="fas fa-users w-5 text-center"></i> Clientes
                </a>
                @endif

                @if($vAbogados)
                <a href="{{ route('abogados.index') }}" title="Abogados"
                   class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-stone-300 hover:bg-brand-800 hover:text-brand-300 transition {{ request()->routeIs('abogados.*') ? 'active' : '' }}">
                    <i class="fas fa-user-tie w-5 text-center"></i> Abogados
                </a>
                @endif

                @if($vExpedientes)
                <a href="{{ route('expedientes.index') }}" title="Expedientes"
                   class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-stone-300 hover:bg-brand-800 hover:text-brand-300 transition {{ request()->routeIs('expedientes.*') ? 'active' : '' }}">
                    <i class="fas fa-folder-open w-5 text-center"></i> Expedientes
                </a>
                @endif

                @if($vTramites)
                <a href="{{ route('tramites.index') }}" title="Trámites"
                   class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-stone-300 hover:bg-brand-800 hover:text-brand-300 transition {{ request()->routeIs('tramites.*') ? 'active' : '' }}">
                    <i class="fas fa-file-circle-check w-5 text-center"></i> Trámites
                </a>
                @endif

                @if($vGastosCobros)
                <a href="{{ route('gastos-cobros.index') }}" title="Gastos y Cobros"
                   class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-stone-300 hover:bg-brand-800 hover:text-brand-300 transition {{ request()->routeIs('gastos-cobros.*') ? 'active' : '' }}">
                    <i class="fas fa-hand-holding-dollar w-5 text-center"></i> Gastos y Cobros
                </a>
                @endif
            </div>
            @endif

            @if($vSeguimientos || $vAudiencias || $vHistorialRevision)
            <div class="pt-3 sidebar-grouptitle">
                <button type="button" onclick="toggleGrupo('seguimiento')"
                        class="w-full flex items-center justify-between px-3 py-1 text-xs uppercase text-brand-500 font-semibold tracking-wider hover:text-brand-300 transition">
                    <span>SEGUIMIENTO</span>
                    <i id="chevron-seguimiento" class="fas fa-chevron-down text-[10px] transition-transform {{ $abiertoSeguimiento ? 'rotate-180' : '' }}"></i>
                </button>
            </div>

            <div id="grupo-seguimiento" class="space-y-1 {{ $abiertoSeguimiento ? '' : 'hidden' }}">
                @if($vSeguimientos)
                <a href="{{ route('seguimientos.index') }}" title="Seguimientos"
                   class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-stone-300 hover:bg-brand-800 hover:text-brand-300 transition {{ request()->routeIs('seguimientos.*') ? 'active' : '' }}">
                    <i class="fas fa-list-check w-5 text-center"></i> Seguimientos
                </a>
                @endif

                @if($vAudiencias)
                <a href="{{ route('audiencias.index') }}" title="Audiencias"
                   class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-stone-300 hover:bg-brand-800 hover:text-brand-300 transition {{ request()->routeIs('audiencias.*') ? 'active' : '' }}">
                    <i class="fas fa-gavel w-5 text-center"></i> Audiencias
                </a>
                @endif

                @if($vHistorialRevision)
                <a href="{{ route('historial-revision.index') }}" title="Historial de Revisión"
                   class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-stone-300 hover:bg-brand-800 hover:text-brand-300 transition {{ request()->routeIs('historial-revision.*') ? 'active' : '' }}">
                    <i class="fas fa-clipboard-check w-5 text-center"></i> Historial de Revisión
                </a>
                @endif

                @if($vSeguimientos)
                <a href="{{ route('documentos.index') }}" title="Documentos"
                   class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-stone-300 hover:bg-brand-800 hover:text-brand-300 transition {{ request()->routeIs('documentos.*') ? 'active' : '' }}">
                    <i class="fas fa-folder-open w-5 text-center"></i> Documentos
                </a>
                @endif
            </div>
            @endif

            @if($vParametros || $vAdministracion || $vBitacora)
            <div class="pt-3 sidebar-grouptitle">
                <button type="button" onclick="toggleGrupo('parametros')"
                        class="w-full flex items-center justify-between px-3 py-1 text-xs uppercase text-brand-500 font-semibold tracking-wider hover:text-brand-300 transition">
                    <span>PARAMETROS</span>
                    <i id="chevron-parametros" class="fas fa-chevron-down text-[10px] transition-transform {{ $abiertoParametros ? 'rotate-180' : '' }}"></i>
                </button>
            </div>

            <div id="grupo-parametros" class="space-y-1 {{ $abiertoParametros ? '' : 'hidden' }}">
                @if($vParametros)
                <a href="{{ route('parametros.index') }}" title="Gestion de Tipos"
                   class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-stone-300 hover:bg-brand-800 hover:text-brand-300 transition {{ request()->routeIs('parametros.*') ? 'active' : '' }}">
                    <i class="fas fa-sliders w-5 text-center"></i> Gestion de Tipos
                </a>
                @endif

                @if($vAdministracion)
                <a href="{{ route('administracion.index') }}" title="Administración"
                   class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-stone-300 hover:bg-brand-800 hover:text-brand-300 transition {{ request()->routeIs('administracion.*') ? 'active' : '' }}">
                    <i class="fas fa-user-shield w-5 text-center"></i> Administración
                </a>
                @endif

                @if($vBitacora)
                <a href="{{ route('bitacora.index') }}" title="Bitácora"
                   class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-stone-300 hover:bg-brand-800 hover:text-brand-300 transition {{ request()->routeIs('bitacora.*') ? 'active' : '' }}">
                    <i class="fas fa-clipboard-list w-5 text-center"></i> Bitácora
                </a>
                @endif
            </div>
            @endif

            @if($vReportes || $vReportesPasantes)
            <div class="pt-3 sidebar-grouptitle">
                <button type="button" onclick="toggleGrupo('reportes')"
                        class="w-full flex items-center justify-between px-3 py-1 text-xs uppercase text-brand-500 font-semibold tracking-wider hover:text-brand-300 transition">
                    <span>REPORTES</span>
                    <i id="chevron-reportes" class="fas fa-chevron-down text-[10px] transition-transform {{ $abiertoReportes ? 'rotate-180' : '' }}"></i>
                </button>
            </div>

            <div id="grupo-reportes" class="space-y-1 {{ $abiertoReportes ? '' : 'hidden' }}">
                @if($vReportes)
                <a href="{{ route('reportes.clientes') }}" title="Reporte de Clientes"
                   class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-stone-300 hover:bg-brand-800 hover:text-brand-300 transition {{ request()->routeIs('reportes.clientes') ? 'active' : '' }}">
                    <i class="fas fa-users w-5 text-center"></i> Reporte de Clientes
                </a>

                <a href="{{ route('reportes.expedientes') }}" title="Reporte de Expedientes"
                   class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-stone-300 hover:bg-brand-800 hover:text-brand-300 transition {{ request()->routeIs('reportes.expedientes') ? 'active' : '' }}">
                    <i class="fas fa-folder-open w-5 text-center"></i> Reporte de Expedientes
                </a>

                <a href="{{ route('reportes.tramites') }}" title="Reporte de Trámites"
                   class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-stone-300 hover:bg-brand-800 hover:text-brand-300 transition {{ request()->routeIs('reportes.tramites') ? 'active' : '' }}">
                    <i class="fas fa-file-circle-check w-5 text-center"></i> Reporte de Trámites
                </a>

                <a href="{{ route('reportes.gastos-cobros') }}" title="Reporte de Gastos y Cobros"
                   class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-stone-300 hover:bg-brand-800 hover:text-brand-300 transition {{ request()->routeIs('reportes.gastos-cobros') ? 'active' : '' }}">
                    <i class="fas fa-hand-holding-dollar w-5 text-center"></i> Reporte de Gastos y Cobros
                </a>
                @endif

                @if($vReportesPasantes)
                <a href="{{ route('reportes.pasantes') }}" title="Reporte Pasantes"
                   class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-stone-300 hover:bg-brand-800 hover:text-brand-300 transition {{ request()->routeIs('reportes.pasantes') ? 'active' : '' }}">
                    <i class="fas fa-user-graduate w-5 text-center"></i> Reporte Pasantes
                </a>
                @endif
            </div>
            @endif
        </nav>

        <div class="p-4 border-t border-brand-800">
            <p class="text-xs text-stone-400">{{ auth()->user()->name ?? 'Usuario' }}</p>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="text-xs text-stone-400 hover:text-brand-300 mt-1">
                    <i class="fas fa-sign-out-alt mr-1"></i> Cerrar sesión
                </button>
            </form>
        </div>
    </aside>

    {{-- ── Contenido principal ── --}}
    <div class="flex-1 flex flex-col overflow-hidden">

        {{-- Header --}}
        <header class="bg-white shadow-sm px-4 sm:px-6 py-4 flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-3 min-w-0">
                <button type="button" onclick="toggleSidebar()" class="lg:hidden text-gray-500 hover:text-brand-700 flex-shrink-0">
                    <i class="fas fa-bars text-lg"></i>
                </button>
                <h2 class="text-lg sm:text-xl font-semibold text-gray-800 truncate">@yield('header', 'Panel')</h2>
            </div>
            <div class="flex items-center gap-2 sm:gap-3 flex-wrap justify-end">
                @yield('header-actions')
            </div>
        </header>

        {{-- Flash messages --}}
        <div class="px-6 pt-4">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded-lg mb-4 flex items-center justify-between gap-3 flex-wrap">
                    <span class="flex items-center gap-2"><i class="fas fa-check-circle"></i> {{ session('success') }}</span>
                    @if(session('whatsapp_url'))
                        <a href="{{ session('whatsapp_url') }}" target="_blank"
                           class="bg-[#25D366] hover:bg-[#1ebe57] text-white px-3 py-1.5 rounded-lg text-sm font-medium flex items-center gap-2 flex-shrink-0">
                            <i class="fab fa-whatsapp"></i> Abrir WhatsApp
                        </a>
                    @endif
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded-lg mb-4 flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                </div>
            @endif
            @if($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded-lg mb-4">
                    <p class="font-semibold"><i class="fas fa-exclamation-triangle"></i> Errores de validación:</p>
                    <ul class="list-disc list-inside mt-1 text-sm">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        {{-- Main content --}}
        <main class="flex-1 overflow-y-auto p-6">
            @yield('content')
        </main>
    </div>
</div>

{{-- Modal genérico: vista previa del mensaje de WhatsApp antes de enviar --}}
<div id="modal-previa-whatsapp" class="hidden fixed inset-0 z-[60] overflow-y-auto">
    <div class="fixed inset-0 bg-black/60" onclick="cerrarModal('modal-previa-whatsapp')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h3 class="font-semibold text-gray-800 text-lg flex items-center gap-2">
                    <i class="fab fa-whatsapp text-emerald-600"></i> Notificar al cliente
                </h3>
                <button type="button" onclick="cerrarModal('modal-previa-whatsapp')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <p class="text-sm text-gray-500">Se enviará este mensaje por WhatsApp:</p>
                <p id="previa-whatsapp-texto" class="text-sm text-gray-800 bg-gray-50 rounded-lg p-3 whitespace-pre-line border max-h-56 overflow-y-auto"></p>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="confirmarEnvioWhatsapp()"
                            class="bg-[#25D366] hover:bg-[#1ebe57] text-white px-5 py-2 rounded-lg font-medium flex items-center gap-2">
                        <i class="fab fa-whatsapp"></i> Enviar
                    </button>
                    <button type="button" onclick="cerrarModal('modal-previa-whatsapp')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal genérico: alta rápida de un catálogo (solo nombre) desde cualquier <select>,
     sin salir del formulario actual. Lo reconfigura abrirAltaRapida() antes de mostrarlo. --}}
<div id="modal-alta-rapida" class="hidden fixed inset-0 z-[70] overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-alta-rapida')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-sm">
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h3 id="alta-rapida-titulo" class="font-semibold text-gray-800 text-lg">Nuevo registro</h3>
                <button type="button" onclick="cerrarModal('modal-alta-rapida')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label id="alta-rapida-label" class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text" id="alta-rapida-input" onkeydown="if(event.key==='Enter'){event.preventDefault();guardarAltaRapida();}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    <p id="alta-rapida-error" class="text-xs text-red-600 mt-1 hidden"></p>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="guardarAltaRapida()"
                            class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                        Guardar
                    </button>
                    <button type="button" onclick="cerrarModal('modal-alta-rapida')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50 text-sm">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal genérico: alta rápida de un Cliente (persona física o jurídica) desde cualquier <select>. --}}
<div id="modal-cliente-rapido" class="hidden fixed inset-0 z-[70] overflow-y-auto">
    <div class="fixed inset-0 bg-black/50" onclick="cerrarModal('modal-cliente-rapido')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-lg w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h3 class="font-semibold text-gray-800 text-lg">Nuevo cliente</h3>
                <button type="button" onclick="cerrarModal('modal-cliente-rapido')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de cliente *</label>
                        <select id="cliente-rapido-tipo" onchange="toggleClienteRapidoTipo()"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                            <option value="persona_fisica">Persona Física</option>
                            <option value="persona_juridica">Persona Jurídica</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">C.I/NIT *</label>
                        <input type="text" id="cliente-rapido-dni"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                        <input type="text" id="cliente-rapido-nombre"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Apellido *</label>
                        <input type="text" id="cliente-rapido-apellido"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                    </div>
                </div>
                <div id="cliente-rapido-juridica" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Razón Social *</label>
                    <input type="text" id="cliente-rapido-razon-social"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400">
                </div>
                <p id="cliente-rapido-error" class="text-xs text-red-600 hidden"></p>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="guardarClienteRapido()"
                            class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                        Guardar
                    </button>
                    <button type="button" onclick="cerrarModal('modal-cliente-rapido')"
                            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50 text-sm">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Vista previa de WhatsApp: muestra el mensaje antes de enviarlo y, al confirmar,
    // abre WhatsApp (clic directo del usuario, no bloqueado) y recién ahí somete el formulario.
    let formWhatsappPendiente = null;
    let urlWhatsappPendiente = null;

    function mostrarPreviaWhatsapp(form, texto, telefono) {
        document.getElementById('previa-whatsapp-texto').textContent = texto;
        formWhatsappPendiente = form;
        urlWhatsappPendiente = 'https://wa.me/' + telefono + '?text=' + encodeURIComponent(texto);
        abrirModal('modal-previa-whatsapp');
    }

    function confirmarEnvioWhatsapp() {
        if (urlWhatsappPendiente) {
            window.open(urlWhatsappPendiente, '_blank');
        }
        cerrarModal('modal-previa-whatsapp');
        if (formWhatsappPendiente) {
            formWhatsappPendiente.dataset.whatsappConfirmado = '1';
            formWhatsappPendiente.requestSubmit();
        }
    }
</script>

<script>
    // Helper genérico de modales flotantes, usado por los formularios de crear/editar.
    function abrirModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function cerrarModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('[id^="modal-"]:not(.hidden)').forEach(function (modal) {
            modal.classList.add('hidden');
        });
        document.body.classList.remove('overflow-hidden');
    });

    // Alta rápida genérica: botón "+" junto a un <select> que crea un registro simple
    // (un solo campo "nombre") en otro módulo sin salir del formulario actual.
    // Uso: abrirAltaRapida({ titulo, etiqueta, placeholder, url, selectId, campo })
    let altaRapidaConfig = null;

    function abrirAltaRapida(config) {
        altaRapidaConfig = config;
        document.getElementById('alta-rapida-titulo').textContent = config.titulo || 'Nuevo registro';
        document.getElementById('alta-rapida-label').textContent = (config.etiqueta || 'Nombre') + ' *';
        const input = document.getElementById('alta-rapida-input');
        input.value = '';
        input.placeholder = config.placeholder || '';
        document.getElementById('alta-rapida-error').classList.add('hidden');
        abrirModal('modal-alta-rapida');
        setTimeout(() => input.focus(), 50);
    }

    function guardarAltaRapida() {
        if (!altaRapidaConfig) return;
        const { url, campo, selectId } = altaRapidaConfig;
        const nombreCampo = campo || 'nombre';
        const input = document.getElementById('alta-rapida-input');
        const error = document.getElementById('alta-rapida-error');
        const valor = input.value.trim();

        error.classList.add('hidden');
        if (!valor) {
            error.textContent = 'Este campo es obligatorio.';
            error.classList.remove('hidden');
            return;
        }

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ [nombreCampo]: valor }),
        })
            .then(async (res) => {
                const body = await res.json();
                if (!res.ok) {
                    const primerError = body.errors ? Object.values(body.errors)[0][0] : null;
                    throw new Error(primerError ?? 'No se pudo guardar.');
                }
                return body;
            })
            .then((registro) => {
                const select = document.getElementById(selectId);
                if (select) {
                    select.add(new Option(registro[nombreCampo], registro.id, true, true));
                    select.dispatchEvent(new Event('change'));
                }
                cerrarModal('modal-alta-rapida');
                altaRapidaConfig = null;
            })
            .catch((err) => {
                error.textContent = err.message;
                error.classList.remove('hidden');
            });
    }

    // Alta rápida de Cliente (persona física o jurídica) desde cualquier <select name="cliente_id">.
    let clienteRapidoSelectId = null;

    function abrirClienteRapido(selectId) {
        clienteRapidoSelectId = selectId;
        document.getElementById('cliente-rapido-tipo').value = 'persona_fisica';
        ['cliente-rapido-dni', 'cliente-rapido-nombre', 'cliente-rapido-apellido', 'cliente-rapido-razon-social']
            .forEach((id) => { document.getElementById(id).value = ''; });
        document.getElementById('cliente-rapido-error').classList.add('hidden');
        toggleClienteRapidoTipo();
        abrirModal('modal-cliente-rapido');
    }

    function toggleClienteRapidoTipo() {
        const esJuridica = document.getElementById('cliente-rapido-tipo').value === 'persona_juridica';
        document.getElementById('cliente-rapido-juridica').classList.toggle('hidden', !esJuridica);
    }

    function guardarClienteRapido() {
        const error = document.getElementById('cliente-rapido-error');
        error.classList.add('hidden');

        const payload = {
            tipo:         document.getElementById('cliente-rapido-tipo').value,
            dni:          document.getElementById('cliente-rapido-dni').value.trim(),
            nombre:       document.getElementById('cliente-rapido-nombre').value.trim(),
            apellido:     document.getElementById('cliente-rapido-apellido').value.trim(),
            razon_social: document.getElementById('cliente-rapido-razon-social').value.trim(),
        };

        fetch('{{ route('clientes.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(payload),
        })
            .then(async (res) => {
                const body = await res.json();
                if (!res.ok) {
                    const primerError = body.errors ? Object.values(body.errors)[0][0] : null;
                    throw new Error(primerError ?? 'No se pudo guardar el cliente.');
                }
                return body;
            })
            .then((cliente) => {
                const select = document.getElementById(clienteRapidoSelectId);
                if (select) {
                    const opcion = new Option(cliente.nombre_completo, cliente.id, true, true);
                    opcion.dataset.tipo = cliente.tipo;
                    select.add(opcion);
                    select.dispatchEvent(new Event('change'));
                }
                cerrarModal('modal-cliente-rapido');
            })
            .catch((err) => {
                error.textContent = err.message;
                error.classList.remove('hidden');
            });
    }

    // Sidebar off-canvas en mobile (hamburguesa).
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('-translate-x-full');
        document.getElementById('sidebar-overlay').classList.toggle('hidden');
    }

    // Grupos desplegables del sidebar (Gestión de Trámites, Parámetros, Reportes).
    function toggleGrupo(key) {
        const grupo = document.getElementById('grupo-' + key);
        const chevron = document.getElementById('chevron-' + key);
        if (!grupo) return;
        grupo.classList.toggle('hidden');
        if (chevron) chevron.classList.toggle('rotate-180');
    }

    // Sidebar colapsable en escritorio (rail de iconos). El estado se guarda en una
    // cookie (no localStorage) para que el propio servidor pueda renderizar la clase
    // correcta desde el primer HTML y no haya parpadeo al cargar la página.
    function toggleSidebarCollapse() {
        const colapsado = document.body.classList.toggle('sidebar-collapsed');
        document.cookie = 'sidebar_colapsado=' + (colapsado ? '1' : '0') + ';path=/;max-age=31536000;samesite=lax';
    }

    // Búsqueda en vivo genérica: reemplaza solo un contenedor de resultados por
    // fetch(), sin recargar la página (evita perder foco/scroll). El controlador
    // debe devolver, cuando la petición trae el header X-Requested-With, solo el
    // fragmento de resultados (misma vista parcial que usa la carga normal).
    function iniciarBusquedaEnVivo({ input, contenedor, url, delay = 300 }) {
        const inputEl = document.querySelector(input);
        const contenedorEl = document.getElementById(contenedor);
        if (!inputEl || !contenedorEl) return;

        let timeout = null;
        let controller = null;

        function cargar(destino) {
            if (controller) controller.abort();
            controller = new AbortController();
            fetch(destino, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal })
                .then(r => r.text())
                .then(html => {
                    contenedorEl.innerHTML = html;
                    history.replaceState(null, '', destino);
                    // innerHTML no ejecuta los <script> que traiga el fragmento (ej. los que
                    // inicializan el estado de cada modal por fila); hay que recrearlos a mano.
                    contenedorEl.querySelectorAll('script').forEach((scriptViejo) => {
                        const scriptNuevo = document.createElement('script');
                        scriptNuevo.textContent = scriptViejo.textContent;
                        scriptViejo.replaceWith(scriptNuevo);
                    });
                })
                .catch(err => { if (err.name !== 'AbortError') console.error(err); });
        }

        inputEl.addEventListener('input', () => {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                const params = new URLSearchParams(window.location.search);
                params.set('buscar', inputEl.value);
                params.delete('page');
                cargar(url + '?' + params.toString());
            }, delay);
        });

        // Los links de paginación (dentro de un <nav>, como los genera Laravel) se
        // cargan en vivo también, sin recargar la página.
        contenedorEl.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (!link || !link.closest('nav')) return;
            e.preventDefault();
            cargar(link.href);
        });
    }
</script>

@stack('scripts')
</body>
</html>
