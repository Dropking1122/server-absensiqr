<div>
    @if(session('success'))
    <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
        {{ session('success') }}
    </div>
    @endif

    <div class="mb-5 flex items-center justify-between">
        <p class="text-sm text-gray-500">{{ $pengumuman->count() }} pengumuman</p>
        <button wire:click="openForm"
            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition-colors shadow-sm">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Pengumuman Baru
        </button>
    </div>

    <div class="overflow-hidden rounded-xl bg-white border border-gray-200 shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Judul</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Prioritas</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Berlaku</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Target</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($pengumuman as $p)
                    @php
                        $isCurrentlyActive = $p->is_active
                            && $p->active_from <= now()
                            && ($p->active_until === null || $p->active_until > now());
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3">
                            <p class="text-sm font-medium text-gray-900">{{ $p->title }}</p>
                            <p class="text-xs text-gray-400 mt-0.5 max-w-xs truncate">{{ Str::limit($p->message, 80) }}</p>
                        </td>
                        <td class="px-4 py-3 hidden sm:table-cell">
                            @php
                                $pColors = ['info'=>'blue','warning'=>'yellow','urgent'=>'red'];
                                $pc = $pColors[$p->priority] ?? 'gray';
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-{{ $pc }}-50 text-{{ $pc }}-700 ring-1 ring-inset ring-{{ $pc }}-600/20">
                                {{ $p->priority_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell text-xs text-gray-500">
                            <div>{{ $p->active_from->setTimezone('Asia/Jakarta')->format('d M Y H:i') }}</div>
                            @if($p->active_until)
                            <div class="text-gray-400">s/d {{ $p->active_until->setTimezone('Asia/Jakarta')->format('d M Y H:i') }}</div>
                            @else
                            <div class="text-gray-400">Tanpa batas</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 hidden lg:table-cell text-sm text-gray-600 capitalize">
                            {{ $p->target_channel ?? 'Semua' }}
                        </td>
                        <td class="px-4 py-3">
                            @if($isCurrentlyActive)
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20">
                                    <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span> Aktif
                                </span>
                            @elseif(!$p->is_active)
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium bg-gray-50 text-gray-600 ring-1 ring-inset ring-gray-500/10">
                                    <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span> Nonaktif
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium bg-yellow-50 text-yellow-700 ring-1 ring-inset ring-yellow-600/20">
                                    <span class="h-1.5 w-1.5 rounded-full bg-yellow-400"></span> Terjadwal
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button wire:click="editPengumuman({{ $p->id }})"
                                    class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">Edit</button>
                                <button wire:click="toggleAktif({{ $p->id }})"
                                    class="text-xs text-gray-500 hover:text-gray-700 font-medium">
                                    {{ $p->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                                <button wire:click="hapus({{ $p->id }})" wire:confirm="Hapus pengumuman ini?"
                                    class="text-xs text-red-500 hover:text-red-700 font-medium">Hapus</button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-gray-400">Belum ada pengumuman.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Form --}}
    @if($showForm)
    <div class="fixed inset-0 z-50 flex items-start justify-center p-4 sm:p-6">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50" wire:click="tutupForm"></div>
        <div class="relative w-full max-w-lg rounded-xl bg-white shadow-xl max-h-screen overflow-y-auto">
            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-200 px-6 py-4 bg-white rounded-t-xl">
                <h2 class="text-sm font-semibold text-gray-900">{{ $editMode ? 'Edit Pengumuman' : 'Pengumuman Baru' }}</h2>
                <button wire:click="tutupForm" class="rounded-lg p-1 text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form wire:submit="simpan" class="px-6 py-5 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Judul <span class="text-red-500">*</span></label>
                    <input wire:model="title" type="text"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pesan <span class="text-red-500">*</span></label>
                    <textarea wire:model="message" rows="4"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    @error('message') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prioritas</label>
                        <select wire:model="priority" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="info">Info</option>
                            <option value="warning">Peringatan</option>
                            <option value="urgent">Mendesak</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Target Channel</label>
                        <select wire:model="targetChannel" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Semua</option>
                            <option value="stable">Stable</option>
                            <option value="beta">Beta</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Berlaku Dari <span class="text-red-500">*</span></label>
                        <input wire:model="activeFrom" type="datetime-local"
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('activeFrom') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Berlaku Hingga</label>
                        <input wire:model="activeUntil" type="datetime-local"
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('activeUntil') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        <p class="mt-1 text-xs text-gray-400">Kosongkan jika tanpa batas waktu.</p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-4">
                    <button type="button" wire:click="tutupForm"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm">
                        Batal
                    </button>
                    <button type="submit"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 shadow-sm">
                        {{ $editMode ? 'Simpan Perubahan' : 'Buat Pengumuman' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
