<div class="max-w-4xl mx-auto space-y-6">

    @if($pesan)
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ $pesan }}</span>
            </div>
            <button wire:click="$set('pesan', null)" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-6">
        <div class="border-b border-gray-100 pb-4">
            <h3 class="text-base font-bold text-gray-900">Pengaturan Lisensi, Developer & Project Advisor (Terpusat)</h3>
            <p class="text-xs text-gray-500 mt-1">
                Data yang disimpan di sini akan dipancarkan melalui API <code class="bg-gray-100 px-1 rounded font-mono">https://api.revd.dev/api/developer-info</code> dan disinkronkan otomatis ke seluruh aplikasi absensi sekolah klien.
            </p>
        </div>

        <form wire:submit="simpan" class="space-y-6">

            {{-- SECTION 1: DEVELOPER INFO --}}
            <div class="space-y-4">
                <h4 class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Informasi Developer Utama</h4>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Nama Brand Developer <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="developer" placeholder="Contoh: REVDSTORE"
                               class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        @error('developer') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Email Support Developer <span class="text-red-500">*</span></label>
                        <input type="email" wire:model="email" placeholder="dropking1122@gmail.com"
                               class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        @error('email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Link GitHub / Repo Resmi <span class="text-red-500">*</span></label>
                    <input type="url" wire:model="github" placeholder="https://github.com/Dropking1122"
                           class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    @error('github') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">WhatsApp Developer</label>
                        <input type="text" wire:model="wa" placeholder="628123456789"
                               class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        @error('wa') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Instagram Developer</label>
                        <input type="text" wire:model="instagram" placeholder="username"
                               class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        @error('instagram') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Telegram Developer</label>
                        <input type="text" wire:model="telegram" placeholder="username"
                               class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        @error('telegram') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Teks Hak Cipta (Copyright) <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="copyright" placeholder="© 2026 REVDSTORE. All Rights Reserved."
                           class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    @error('copyright') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- SECTION 2: PROJECT ADVISOR --}}
            <div class="space-y-4 pt-4 border-t border-gray-100">
                <h4 class="text-xs font-bold text-slate-600 uppercase tracking-wider">Informasi Project Advisor (Opsional)</h4>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Nama Project Advisor</label>
                    <input type="text" wire:model="advisor_nama" placeholder="Contoh: Wandi Hermawan"
                           class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    @error('advisor_nama') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">WhatsApp Advisor</label>
                        <input type="text" wire:model="advisor_wa" placeholder="628xxxxxxxxxx"
                               class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        @error('advisor_wa') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Instagram Advisor</label>
                        <input type="text" wire:model="advisor_instagram" placeholder="username"
                               class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        @error('advisor_instagram') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Telegram Advisor</label>
                        <input type="text" wire:model="advisor_telegram" placeholder="username"
                               class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        @error('advisor_telegram') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="pt-3 border-t border-gray-100 flex justify-end">
                <button type="submit" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl transition shadow-sm">
                    <svg wire:loading.remove wire:target="simpan" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <svg wire:loading wire:target="simpan" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    Simpan Lisensi & Advisor
                </button>
            </div>
        </form>
    </div>

</div>
