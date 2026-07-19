<div>
    {{-- Tab navigation --}}
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex gap-6">
            @foreach([['info', 'Informasi'], ['heartbeat', 'Riwayat Heartbeat'], ['update', 'Riwayat Update']] as [$tab, $label])
            <button wire:click="setTab('{{ $tab }}')"
                class="pb-3 text-sm font-medium transition-colors whitespace-nowrap
                    {{ $activeTab === $tab
                        ? 'border-b-2 border-indigo-600 text-indigo-600'
                        : 'text-gray-500 hover:text-gray-700 hover:border-b-2 hover:border-gray-300' }}">
                {{ $label }}
            </button>
            @endforeach
        </nav>
    </div>

    {{-- Tab: Informasi --}}
    @if($activeTab === 'info')
    <div class="overflow-hidden rounded-xl bg-white border border-gray-200 shadow-sm">
        <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900">Informasi Instalasi</h3>
                <x-status-badge :status="$installation->online_status" />
            </div>
        </div>
        <dl class="divide-y divide-gray-100">
            @php
                $rows = [
                    ['Nama Sekolah',      $installation->app_name ?? '-'],
                    ['Domain',            $installation->app_url ?? '-'],
                    ['PHP',               $installation->php_version ?? '-'],
                    ['Database',          $installation->db_driver === 'pgsql' ? 'PostgreSQL' : ($installation->db_driver ?? '-')],
                    ['Channel',           ucfirst($installation->update_channel ?? 'stable')],
                    ['Pertama Terdaftar', $installation->first_seen_at?->setTimezone('Asia/Jakarta')->isoFormat('D MMMM YYYY') ?? '-'],
                    ['Terakhir Online',   $installation->last_seen_at?->setTimezone('Asia/Jakarta')->diffForHumans() ?? 'Tidak pernah'],
                ];
            @endphp
            @foreach($rows as [$label, $value])
            <div class="px-6 py-3.5 sm:grid sm:grid-cols-3 sm:gap-4">
                <dt class="text-sm font-medium text-gray-500">{{ $label }}</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $value }}</dd>
            </div>
            @endforeach

            {{-- Versi dengan indikator update --}}
            <div class="px-6 py-3.5 sm:grid sm:grid-cols-3 sm:gap-4">
                <dt class="text-sm font-medium text-gray-500">Versi Aplikasi</dt>
                <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-gray-900">{{ $installation->app_version ?? '-' }}</span>
                        @if($latestVersion && $installation->app_version && version_compare($installation->app_version, $latestVersion, '<'))
                            <span class="text-xs text-yellow-700 bg-yellow-50 ring-1 ring-yellow-600/20 px-2 py-0.5 rounded-full">
                                Perlu update ke {{ $latestVersion }}
                            </span>
                        @elseif($installation->app_version)
                            <span class="text-xs text-green-700 bg-green-50 ring-1 ring-green-600/20 px-2 py-0.5 rounded-full">Terbaru</span>
                        @endif
                    </div>
                </dd>
            </div>

            {{-- Status WA --}}
            <div class="px-6 py-3.5 sm:grid sm:grid-cols-3 sm:gap-4">
                <dt class="text-sm font-medium text-gray-500">WA Service</dt>
                <dd class="mt-1 sm:col-span-2 sm:mt-0">
                    @if($installation->wa_online)
                        <span class="inline-flex items-center gap-1.5 text-sm text-green-700">
                            <span class="h-2 w-2 rounded-full bg-green-500"></span> Online
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 text-sm text-gray-500">
                            <span class="h-2 w-2 rounded-full bg-gray-400"></span> Offline
                        </span>
                    @endif
                </dd>
            </div>
        </dl>
    </div>
    @endif

    {{-- Tab: Riwayat Heartbeat --}}
    @if($activeTab === 'heartbeat')
    <div class="space-y-6">
        {{-- Heatmap 30 hari --}}
        <div class="overflow-hidden rounded-xl bg-white border border-gray-200 shadow-sm">
            <div class="border-b border-gray-200 px-6 py-4">
                <h3 class="text-sm font-semibold text-gray-900">Heatmap Heartbeat - 30 Hari Terakhir</h3>
                <p class="text-xs text-gray-500 mt-0.5">Setiap kotak mewakili 1 jam. Hijau = ada heartbeat, abu = tidak ada.</p>
            </div>
            <div class="px-6 py-4 overflow-x-auto">
                <div class="flex gap-1 min-w-max" x-data>
                    @for($day = 29; $day >= 0; $day--)
                    @php
                        $date = now()->subDays($day)->format('Y-m-d');
                        $dayLabel = now()->subDays($day)->setTimezone('Asia/Jakarta')->format('d/m');
                    @endphp
                    <div class="flex flex-col gap-1">
                        <span class="text-xs text-gray-400 text-center" style="font-size:9px">{{ $dayLabel }}</span>
                        @for($hour = 0; $hour < 24; $hour++)
                        @php
                            $key = $date . '_' . str_pad($hour, 2, '0', STR_PAD_LEFT);
                            $hasData = isset($heatmapData[$key]) && $heatmapData[$key] > 0;
                        @endphp
                        <div class="h-3 w-3 rounded-sm {{ $hasData ? 'bg-green-400' : 'bg-gray-100' }}"
                             title="{{ $date }} {{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}:00 - {{ $hasData ? $heatmapData[$key].' heartbeat' : 'tidak ada' }}">
                        </div>
                        @endfor
                    </div>
                    @endfor
                </div>
            </div>
        </div>

        {{-- Tabel riwayat --}}
        <div class="overflow-hidden rounded-xl bg-white border border-gray-200 shadow-sm">
            <div class="border-b border-gray-200 px-6 py-4">
                <h3 class="text-sm font-semibold text-gray-900">50 Heartbeat Terakhir</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu Diterima (WIB)</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Versi</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">WA</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PHP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($riwayatLog as $log)
                        <tr>
                            <td class="px-4 py-2.5 text-sm text-gray-700">
                                {{ \Carbon\Carbon::parse($log->received_at)->setTimezone('Asia/Jakarta')->format('d M Y H:i') }}
                            </td>
                            <td class="px-4 py-2.5 text-sm text-gray-600">{{ $log->app_version ?? '-' }}</td>
                            <td class="px-4 py-2.5">
                                <span class="h-1.5 w-1.5 inline-block rounded-full {{ $log->wa_online ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                            </td>
                            <td class="px-4 py-2.5 text-sm text-gray-500">{{ $log->php_version ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400">Belum ada log heartbeat.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Tab: Riwayat Update --}}
    @if($activeTab === 'update')
    <div class="overflow-hidden rounded-xl bg-white border border-gray-200 shadow-sm">
        <div class="border-b border-gray-200 px-6 py-4">
            <h3 class="text-sm font-semibold text-gray-900">Riwayat Versi Aplikasi</h3>
            <p class="text-xs text-gray-500 mt-0.5">Dideteksi dari perubahan versi pada log heartbeat.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Versi</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pertama Terdeteksi</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Terakhir Terdeteksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($riwayatUpdate as $row)
                    <tr>
                        <td class="px-4 py-2.5 text-sm font-medium text-gray-900">{{ $row->app_version }}</td>
                        <td class="px-4 py-2.5 text-sm text-gray-600">
                            {{ \Carbon\Carbon::parse($row->pertama_kali)->setTimezone('Asia/Jakarta')->format('d M Y H:i') }}
                        </td>
                        <td class="px-4 py-2.5 text-sm text-gray-500">
                            {{ \Carbon\Carbon::parse($row->terakhir_kali)->setTimezone('Asia/Jakarta')->format('d M Y H:i') }}
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="px-4 py-8 text-center text-sm text-gray-400">Belum ada riwayat update.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
