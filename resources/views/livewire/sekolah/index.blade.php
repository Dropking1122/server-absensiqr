<div>
    {{-- Filter dan Search --}}
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center flex-1">
            <div class="relative flex-1 max-w-sm">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama atau domain..."
                    class="block w-full rounded-lg border-gray-300 pl-9 pr-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
            </div>
            <select wire:model.live="filterStatus" class="rounded-lg border-gray-300 py-2 pl-3 pr-8 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                <option value="">Semua Status</option>
                <option value="online">Online</option>
                <option value="offline">Offline</option>
                <option value="offline_long">Offline Lama (48j+)</option>
            </select>
            <select wire:model.live="filterChannel" class="rounded-lg border-gray-300 py-2 pl-3 pr-8 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                <option value="">Semua Channel</option>
                <option value="stable">Stable</option>
                <option value="beta">Beta</option>
            </select>
            <select wire:model.live="filterUpdate" class="rounded-lg border-gray-300 py-2 pl-3 pr-8 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                <option value="">Semua Versi</option>
                <option value="perlu">Perlu Update</option>
                <option value="terbaru">Sudah Terbaru</option>
            </select>
        </div>
        <div class="text-sm text-gray-500">
            {{ $installations->total() }} sekolah
        </div>
    </div>

    {{-- Tabel --}}
    <div class="overflow-hidden rounded-xl bg-white border border-gray-200 shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sekolah</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Versi</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">WA</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Terakhir Online</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden xl:table-cell">Channel</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($installations as $s)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $s->app_name ?? '-' }}</p>
                                <p class="text-xs text-gray-400 truncate max-w-[200px]">{{ $s->app_url ?? $s->installation_id }}</p>
                            </div>
                        </td>
                        <td class="px-4 py-3 hidden sm:table-cell">
                            @if($s->app_version)
                                @if($latestVersion && version_compare($s->app_version, $latestVersion, '>='))
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20">Terbaru</span>
                                @else
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-yellow-50 text-yellow-700 ring-1 ring-inset ring-yellow-600/20">{{ $s->app_version }}</span>
                                @endif
                            @else
                                <span class="text-xs text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <x-status-badge :status="$s->online_status" />
                        </td>
                        <td class="px-4 py-3 hidden lg:table-cell">
                            @if($s->wa_online)
                                <span class="inline-flex items-center gap-1 text-xs text-green-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>Online
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs text-gray-500">
                                    <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>Offline
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 hidden lg:table-cell text-sm text-gray-500">
                            {{ $s->last_seen_at ? $s->last_seen_at->setTimezone('Asia/Jakarta')->diffForHumans() : 'Belum pernah' }}
                        </td>
                        <td class="px-4 py-3 hidden xl:table-cell">
                            <span class="text-xs capitalize text-gray-600">{{ $s->update_channel }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('sekolah.detail', $s->installation_id) }}"
                               class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                                Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-400">
                            Tidak ada sekolah yang sesuai filter.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($installations->hasPages())
        <div class="border-t border-gray-200 px-4 py-3">
            {{ $installations->links() }}
        </div>
        @endif
    </div>
</div>
