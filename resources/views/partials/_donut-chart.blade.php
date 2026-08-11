{{--
    Gráfico de dona (2D, sin efecto 3D), con leyenda.
    Requiere: $items (Collection de objetos con ->label, ->value, ->color en hex),
              $emptyMessage (string)
--}}
@php
    $total = $items->sum('value');
    $radio = 40;
    $circunferencia = 2 * M_PI * $radio;
    $gapArco = 2; // separación visual entre segmentos, en unidades del viewBox
    $offsetAcumulado = 0;
@endphp

<div class="flex items-center gap-6">
    <div class="flex-shrink-0">
        <svg viewBox="0 0 100 100" width="140" height="140" role="img" aria-label="Distribución de trámites por tipo">
            <circle cx="50" cy="50" r="{{ $radio }}" fill="none" stroke="#e1e0d9" stroke-width="18" />
            @if($total > 0)
                @foreach($items as $item)
                    @php
                        $arcoCompleto = ($item->value / $total) * $circunferencia;
                        $arcoVisible  = max($arcoCompleto - $gapArco, 0);
                        $offset       = -$offsetAcumulado;
                    @endphp
                    <circle cx="50" cy="50" r="{{ $radio }}" fill="none" stroke="{{ $item->color }}" stroke-width="18"
                            stroke-dasharray="{{ $arcoVisible }} {{ $circunferencia - $arcoVisible }}"
                            stroke-dashoffset="{{ $offset }}"
                            transform="rotate(-90 50 50)">
                        <title>{{ $item->label }}: {{ $item->value }} ({{ round($item->value / $total * 100) }}%)</title>
                    </circle>
                    @php $offsetAcumulado += $arcoCompleto; @endphp
                @endforeach
            @endif
            <text x="50" y="47" text-anchor="middle" font-size="17" font-weight="600" fill="#0b0b0b">{{ $total }}</text>
            <text x="50" y="60" text-anchor="middle" font-size="7" fill="#898781">trámites</text>
        </svg>
    </div>

    <div class="flex-1 space-y-2 min-w-0">
        @forelse($items as $item)
            <div class="flex items-center justify-between text-sm gap-2">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="w-2.5 h-2.5 rounded-sm flex-shrink-0" style="background-color: {{ $item->color }}"></span>
                    <span class="text-gray-700 truncate">{{ $item->label }}</span>
                </div>
                <span class="text-gray-500 flex-shrink-0" style="font-variant-numeric: tabular-nums;">
                    {{ $item->value }} · {{ $total ? round($item->value / $total * 100) : 0 }}%
                </span>
            </div>
        @empty
            <p class="text-sm text-gray-400">{{ $emptyMessage }}</p>
        @endforelse
    </div>
</div>
