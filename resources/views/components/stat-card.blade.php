@props([
    'label'  => '',
    'value'  => '0',
    'color'  => 'indigo', // indigo, green, yellow, red
    'icon'   => 'default',
    'sub'    => null,
])

@php
    $colors = [
        'indigo' => 'bg-indigo-50 text-indigo-600',
        'green'  => 'bg-green-50 text-green-600',
        'yellow' => 'bg-yellow-50 text-yellow-600',
        'red'    => 'bg-red-50 text-red-600',
    ];
    $iconBg = $colors[$color] ?? $colors['indigo'];
@endphp

<div class="overflow-hidden rounded-xl bg-white border border-gray-200 shadow-sm">
    <div class="p-5">
        <div class="flex items-center gap-4">
            <div class="flex-shrink-0 rounded-lg p-3 {{ $iconBg }}">
                {{ $slot }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-gray-500 truncate">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ $value }}</p>
                @if($sub)
                    <p class="mt-0.5 text-xs text-gray-400">{{ $sub }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
