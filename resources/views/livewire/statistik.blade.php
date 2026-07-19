<div>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Distribusi Versi --}}
        <div class="overflow-hidden rounded-xl bg-white border border-gray-200 shadow-sm">
            <div class="border-b border-gray-200 px-6 py-4">
                <h3 class="text-sm font-semibold text-gray-900">Distribusi Versi</h3>
            </div>
            <div class="px-6 py-5">
                @if(count($distribusiVersi) > 0)
                <div class="relative h-52 mb-4">
                    <canvas id="versiChart"></canvas>
                </div>
                <table class="min-w-full">
                    <tbody class="divide-y divide-gray-100">
                        @foreach($distribusiVersi as $v)
                        <tr>
                            <td class="py-2 text-sm font-medium text-gray-900">{{ $v['version'] }}</td>
                            <td class="py-2 text-sm text-gray-600">{{ $v['total'] }} sekolah</td>
                            <td class="py-2 text-sm text-gray-500 text-right">{{ $v['persen'] }}%</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <p class="text-sm text-gray-400 py-8 text-center">Belum ada data.</p>
                @endif
            </div>
        </div>

        {{-- Status WA --}}
        <div class="overflow-hidden rounded-xl bg-white border border-gray-200 shadow-sm">
            <div class="border-b border-gray-200 px-6 py-4">
                <h3 class="text-sm font-semibold text-gray-900">Status WA Gateway</h3>
            </div>
            <div class="px-6 py-5">
                @if(array_sum(array_column($waStatus, 'total')) > 0)
                <div class="relative h-52 mb-4">
                    <canvas id="waChart"></canvas>
                </div>
                <div class="flex gap-6 justify-center mt-2">
                    @foreach($waStatus as $wa)
                    <div class="text-center">
                        <p class="text-2xl font-bold text-gray-900">{{ $wa['total'] }}</p>
                        <p class="text-sm text-gray-500">{{ $wa['label'] }}</p>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-sm text-gray-400 py-8 text-center">Belum ada data.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Uptime Sekolah --}}
    <div class="mt-6 overflow-hidden rounded-xl bg-white border border-gray-200 shadow-sm">
        <div class="border-b border-gray-200 px-6 py-4">
            <h3 class="text-sm font-semibold text-gray-900">Uptime Sekolah - 30 Hari Terakhir</h3>
            <p class="text-xs text-gray-500 mt-0.5">Persentase jam aktif dari total jam dalam 30 hari.</p>
        </div>
        <div class="px-6 py-4">
            @forelse($uptimeSekolah as $s)
            <div class="mb-3">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm font-medium text-gray-700 truncate max-w-xs">{{ $s['name'] }}</span>
                    <span class="text-sm font-semibold text-gray-900 ml-2">{{ $s['uptime'] }}%</span>
                </div>
                <div class="w-full h-2 rounded-full bg-gray-100">
                    <div class="h-2 rounded-full transition-all
                        {{ $s['uptime'] >= 90 ? 'bg-green-500' : ($s['uptime'] >= 60 ? 'bg-yellow-400' : 'bg-red-400') }}"
                        style="width: {{ $s['uptime'] }}%"></div>
                </div>
            </div>
            @empty
            <p class="text-sm text-gray-400 py-8 text-center">Belum ada data.</p>
            @endforelse
        </div>
    </div>
</div>

<script>
document.addEventListener('livewire:initialized', () => {
    @if(count($distribusiVersi) > 0)
    const versiCtx = document.getElementById('versiChart');
    if (versiCtx) {
        const vData = @json($distribusiVersi);
        new Chart(versiCtx, {
            type: 'doughnut',
            data: {
                labels: vData.map(d => 'v' + d.version),
                datasets: [{
                    data: vData.map(d => d.total),
                    backgroundColor: ['#6366f1','#22c55e','#f59e0b','#ef4444','#8b5cf6','#14b8a6'],
                    borderWidth: 2,
                    borderColor: '#fff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { font: { size: 11 }, boxWidth: 12 } }
                },
            }
        });
    }
    @endif

    @if(array_sum(array_column($waStatus, 'total')) > 0)
    const waCtx = document.getElementById('waChart');
    if (waCtx) {
        const waData = @json($waStatus);
        new Chart(waCtx, {
            type: 'doughnut',
            data: {
                labels: waData.map(d => d.label),
                datasets: [{
                    data: waData.map(d => d.total),
                    backgroundColor: ['#22c55e','#9ca3af'],
                    borderWidth: 2,
                    borderColor: '#fff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { font: { size: 11 }, boxWidth: 12 } }
                },
            }
        });
    }
    @endif
});
</script>
