<div>
    @if(session('success'))
    <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
        {{ session('success') }}
    </div>
    @endif

    {{-- Header --}}
    <div class="mb-5 flex items-center justify-between">
        <p class="text-sm text-gray-500">{{ $releases->count() }} rilis terdaftar</p>
        <button wire:click="openForm"
            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition-colors shadow-sm">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Rilis Baru
        </button>
    </div>

    {{-- Tabel rilis --}}
    <div class="overflow-hidden rounded-xl bg-white border border-gray-200 shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Versi</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Kategori</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Channel</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Mandatory</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($releases as $r)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3">
                            <p class="text-sm font-semibold text-gray-900">{{ $r->version }}</p>
                            <p class="text-xs text-gray-400 mt-0.5 hidden sm:hidden md:block lg:hidden">{{ $r->released_at->isoFormat('D MMM YYYY') }}</p>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 hidden sm:table-cell">
                            {{ $r->released_at->isoFormat('D MMM YYYY') }}
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell">
                            @php
                                $catColors = ['feature'=>'indigo','bugfix'=>'blue','security'=>'red','hotfix'=>'orange'];
                                $cat = $catColors[$r->category] ?? 'gray';
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-{{ $cat }}-50 text-{{ $cat }}-700 ring-1 ring-inset ring-{{ $cat }}-600/20">
                                {{ $r->category_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 hidden lg:table-cell text-sm capitalize text-gray-600">{{ $r->channel }}</td>
                        <td class="px-4 py-3 hidden lg:table-cell text-sm text-gray-600">{{ $r->mandatory ? 'Ya' : 'Tidak' }}</td>
                        <td class="px-4 py-3">
                            @if($r->is_active)
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20">
                                    <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span> Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium bg-gray-50 text-gray-600 ring-1 ring-inset ring-gray-500/10">
                                    <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span> Arsip
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button wire:click="editRilis({{ $r->id }})"
                                    class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">Edit</button>
                                <button wire:click="toggleAktif({{ $r->id }})"
                                    class="text-xs {{ $r->is_active ? 'text-gray-500 hover:text-red-600' : 'text-gray-500 hover:text-green-600' }} font-medium">
                                    {{ $r->is_active ? 'Arsipkan' : 'Aktifkan' }}
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-sm text-gray-400">Belum ada rilis.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Form --}}
    @if($showForm)
    <div class="fixed inset-0 z-50 flex items-start justify-center p-4 sm:p-6 lg:p-8" x-data x-cloak>
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" wire:click="tutupForm"></div>
        <div class="relative w-full max-w-3xl rounded-xl bg-white shadow-xl max-h-screen overflow-y-auto">
            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-200 px-6 py-4 bg-white rounded-t-xl">
                <h2 class="text-sm font-semibold text-gray-900">{{ $editMode ? 'Edit Rilis' : 'Rilis Baru' }}</h2>
                <button wire:click="tutupForm" class="rounded-lg p-1 text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form wire:submit="simpan" class="px-6 py-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Versi <span class="text-red-500">*</span></label>
                        <input wire:model="version" type="text" placeholder="1.2.0" {{ $editMode ? 'disabled' : '' }}
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 {{ $editMode ? 'bg-gray-50 text-gray-500' : '' }}">
                        @error('version') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Rilis <span class="text-red-500">*</span></label>
                        <input wire:model="releasedAt" type="date"
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('releasedAt') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kategori <span class="text-red-500">*</span></label>
                        <select wire:model="category" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="feature">Fitur</option>
                            <option value="bugfix">Perbaikan</option>
                            <option value="security">Keamanan</option>
                            <option value="hotfix">Hotfix</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Channel <span class="text-red-500">*</span></label>
                        <select wire:model="channel" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="stable">Stable</option>
                            <option value="beta">Beta</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Judul Rilis <span class="text-red-500">*</span></label>
                        <input wire:model="title" type="text" placeholder="Contoh: Pembaruan Juli 2026"
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2 flex items-center gap-3">
                        <input wire:model="mandatory" type="checkbox" id="mandatory"
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="mandatory" class="text-sm font-medium text-gray-700">Wajib diperbarui</label>
                    </div>
                    @if($mandatory)
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Min Versi (wajib dari versi ini ke bawah)</label>
                        <input wire:model="minVersion" type="text" placeholder="1.0.0"
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    @endif
                </div>

                {{-- Release Notes dengan preview --}}
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Release Notes (Markdown) <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Editor</p>
                            <textarea wire:model.live="notes" rows="12" placeholder="## Fitur Baru&#10;&#10;- Tambah ekspor PDF&#10;- Fix live monitor mobile"
                                class="w-full rounded-lg border-gray-300 text-sm font-mono shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                            @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Preview</p>
                            <div class="min-h-[200px] rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm prose prose-sm max-w-none overflow-auto">
                                {!! $notesPreview ?: '<span class="text-gray-400">Preview akan muncul di sini...</span>' !!}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3 border-t border-gray-200 pt-4">
                    <button type="button" wire:click="tutupForm"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm">
                        Batal
                    </button>
                    <button type="submit"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 shadow-sm">
                        {{ $editMode ? 'Simpan Perubahan' : 'Buat Rilis' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
