<div wire:poll.60s="refresh">
    {{-- Kartu Statistik --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card label="Total Sekolah" value="{{ $totalSekolah }}" color="indigo">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
            </svg>
        </x-stat-card>

        <x-stat-card label="Online Sekarang" value="{{ $onlineSekarang }}" color="green" sub="aktif dalam 90 menit terakhir">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z" />
            </svg>
        </x-stat-card>

        <x-stat-card label="Versi Terbaru" value="{{ $versiTerbaru }}" color="indigo" sub="rilis stable aktif">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
            </svg>
        </x-stat-card>

        <x-stat-card label="Perlu Update" value="{{ $perluUpdate }}" color="yellow" sub="versi di bawah stable terbaru">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
        </x-stat-card>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Distribusi Versi --}}
        <div class="overflow-hidden rounded-xl bg-white border border-gray-200 shadow-sm">
            <div class="border-b border-gray-200 px-6 py-4">
                <h3 class="text-sm font-semibold text-gray-900">Distribusi Versi</h3>
            </div>
            <div class="px-6 py-4">
                @if(count($distribusiVersi) > 0)
                <table class="min-w-full">
                    <thead>
                        <tr class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <th class="pb-3 text-left">Versi</th>
                            <th class="pb-3 text-right">Sekolah</th>
                            <th class="pb-3 text-right">Persentase</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($distribusiVersi as $v)
                        <tr>
                            <td class="py-2.5 text-sm font-medium text-gray-900">{{ $v['version'] }}</td>
                            <td class="py-2.5 text-sm text-gray-600 text-right">{{ $v['total'] }}</td>
                            <td class="py-2.5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <div class="w-24 h-1.5 rounded-full bg-gray-100">
                                        <div class="h-1.5 rounded-full bg-indigo-500" style="width: {{ $v['persen'] }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-500 w-10 text-right">{{ $v['persen'] }}%</span>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <p class="text-sm text-gray-400 py-4 text-center">Belum ada data instalasi.</p>
                @endif
            </div>
        </div>

        {{-- Sekolah Offline > 24 jam --}}
        <div class="overflow-hidden rounded-xl bg-white border border-gray-200 shadow-sm">
            <div class="border-b border-gray-200 px-6 py-4">
                <h3 class="text-sm font-semibold text-gray-900">Sekolah Offline Lebih dari 24 Jam</h3>
            </div>
            <div class="px-6 py-4">
                @if(count($offlineLama) > 0)
                <ul class="divide-y divide-gray-100">
                    @foreach($offlineLama as $s)
                    <li class="py-2.5 flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $s['app_name'] ?? $s['installation_id'] }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ $s['app_url'] ?? '-' }}</p>
                        </div>
                        <span class="text-xs text-gray-500 shrink-0">
                            {{ $s['last_seen_at'] ? \Carbon\Carbon::parse($s['last_seen_at'])->diffForHumans() : 'Tidak pernah' }}
                        </span>
                    </li>
                    @endforeach
                </ul>
                @else
                <p class="text-sm text-gray-400 py-4 text-center">Semua sekolah aktif dalam 24 jam terakhir.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Heartbeat 24 jam terakhir --}}
    <div class="mt-6 overflow-hidden rounded-xl bg-white border border-gray-200 shadow-sm">
        <div class="border-b border-gray-200 px-6 py-4">
            <h3 class="text-sm font-semibold text-gray-900">Aktivitas Heartbeat - 24 Jam Terakhir</h3>
            <p class="text-xs text-gray-500 mt-0.5">Jumlah sekolah aktif per jam (WIB)</p>
        </div>
        <div class="px-6 py-5">
            @if(count($heartbeatChart) > 0)
            <div class="relative h-40">
                <canvas id="heartbeatChart"></canvas>
            </div>
            @else
            <p class="text-sm text-gray-400 py-4 text-center">Belum ada data heartbeat dalam 24 jam terakhir.</p>
            @endif
        </div>
    </div>
</div>

@push('scripts')
@if(count($heartbeatChart) > 0)
<script>
document.addEventListener('livewire:initialized', () => {
    const ctx = document.getElementById('heartbeatChart');
    if (!ctx) return;
    const data = @json($heartbeatChart);
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(d => d.jam),
            datasets: [{
                label: 'Sekolah Aktif',
                data: data.map(d => d.aktif),
                backgroundColor: 'rgba(99, 102, 241, 0.15)',
                borderColor: 'rgb(99, 102, 241)',
                borderWidth: 1.5,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: 'rgba(0,0,0,0.05)' } },
                x: { ticks: { font: { size: 10 } }, grid: { display: false } },
            },
        }
    });
});
</script>
@endif
@endpush
