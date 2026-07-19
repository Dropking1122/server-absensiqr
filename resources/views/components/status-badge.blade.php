@props(['status'])

@php
    $configs = [
        'online'       => ['dot' => 'bg-green-500',  'text' => 'text-green-700',  'bg' => 'bg-green-50',  'ring' => 'ring-green-600/20',  'label' => 'Online'],
        'offline'      => ['dot' => 'bg-gray-400',   'text' => 'text-gray-600',   'bg' => 'bg-gray-50',   'ring' => 'ring-gray-500/10',   'label' => 'Offline'],
        'offline_long' => ['dot' => 'bg-red-500',    'text' => 'text-red-700',    'bg' => 'bg-red-50',    'ring' => 'ring-red-600/20',    'label' => 'Offline Lama'],
        'never'        => ['dot' => 'bg-gray-300',   'text' => 'text-gray-500',   'bg' => 'bg-gray-50',   'ring' => 'ring-gray-400/20',   'label' => 'Belum Aktif'],
    ];
    $c = $configs[$status] ?? $configs['offline'];
@endphp

<span class="inline-flex items-center gap-x-1.5 rounded-full px-2 py-1 text-xs font-medium {{ $c['text'] }} {{ $c['bg'] }} ring-1 ring-inset {{ $c['ring'] }}">
    <span class="h-1.5 w-1.5 rounded-full {{ $c['dot'] }}"></span>
    {{ $c['label'] }}
</span>
