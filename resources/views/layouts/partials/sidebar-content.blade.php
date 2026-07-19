<div class="flex h-16 shrink-0 items-center border-b border-gray-200 px-6">
    <div class="flex items-center gap-2">
        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600">
            <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.7L5.737 5.1a3.375 3.375 0 012.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 01.9 2.7m0 0a3 3 0 01-3 3m0 3h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008zm-3 6h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008z" />
            </svg>
        </div>
        <span class="text-sm font-semibold text-gray-900 leading-tight">Server Pusat<br><span class="font-normal text-gray-500 text-xs">Absensi QR</span></span>
    </div>
</div>

<nav class="flex flex-1 flex-col px-4 py-4">
    <ul role="list" class="flex flex-1 flex-col gap-y-1">
        @php
            $navItems = [
                ['route' => 'dashboard',         'label' => 'Dashboard',      'icon' => 'home'],
                ['route' => 'sekolah.index',      'label' => 'Sekolah',        'icon' => 'building'],
                ['route' => 'rilis.index',        'label' => 'Rilis',          'icon' => 'tag'],
                ['route' => 'pengumuman.index',   'label' => 'Pengumuman',     'icon' => 'bell'],
                ['route' => 'statistik',          'label' => 'Statistik',      'icon' => 'chart'],
            ];
        @endphp

        @foreach ($navItems as $item)
        @php $active = request()->routeIs($item['route']); @endphp
        <li>
            <a href="{{ route($item['route']) }}"
               class="group flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors
                      {{ $active ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
                @if($item['icon'] === 'home')
                <svg class="h-5 w-5 shrink-0 {{ $active ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
                @elseif($item['icon'] === 'building')
                <svg class="h-5 w-5 shrink-0 {{ $active ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
                </svg>
                @elseif($item['icon'] === 'tag')
                <svg class="h-5 w-5 shrink-0 {{ $active ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                </svg>
                @elseif($item['icon'] === 'bell')
                <svg class="h-5 w-5 shrink-0 {{ $active ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                </svg>
                @elseif($item['icon'] === 'chart')
                <svg class="h-5 w-5 shrink-0 {{ $active ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-600' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                </svg>
                @endif
                {{ $item['label'] }}
            </a>
        </li>
        @endforeach
    </ul>

    <div class="mt-auto border-t border-gray-200 pt-4">
        <div class="rounded-lg bg-gray-50 px-3 py-2">
            <p class="text-xs text-gray-500">Versi Server</p>
            <p class="text-sm font-semibold text-gray-700">{{ config('monitor.current_stable_version', '1.0.0') }}</p>
        </div>
    </div>
</nav>
