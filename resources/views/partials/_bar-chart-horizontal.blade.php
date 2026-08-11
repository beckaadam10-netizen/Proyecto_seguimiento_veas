{{--
    Gráfico de barras horizontales, minimalista.
    Requiere: $items (Collection de objetos con ->label, ->value, ->color en hex),
              $emptyMessage (string)
--}}
@php
    $max = $items->max('value') ?: 1;
@endphp
<div class="space-y-3">
    @forelse($items as $item)
        <div class="group" title="{{ $item->label }}: {{ $item->value }}">
            <div class="flex justify-between items-baseline text-sm mb-1 gap-3">
                <span class="text-gray-700 truncate">{{ $item->label }}</span>
                <span class="text-gray-500 font-medium flex-shrink-0" style="font-variant-numeric: tabular-nums;">{{ $item->value }}</span>
            </div>
            <div class="w-full bg-gray-100 rounded-sm h-[10px] overflow-hidden">
                <div class="h-[10px] transition-all group-hover:brightness-90"
                     style="width: {{ round($item->value / $max * 100) }}%; background-color: {{ $item->color }}; border-radius: 0 4px 4px 0;"></div>
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-400 text-center py-6">{{ $emptyMessage }}</p>
    @endforelse
</div>
