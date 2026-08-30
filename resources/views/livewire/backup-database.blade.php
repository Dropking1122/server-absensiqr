<div class="space-y-6">

    @if($pesan)
        <div @class([
            'p-4 rounded-xl border flex items-start gap-3 text-sm',
            'bg-emerald-50 border-emerald-200 text-emerald-800 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300' => $tipePesan === 'sukses',
            'bg-red-50 border-red-200 text-red-800 dark:bg-red-950/40 dark:border-red-800 dark:text-red-300'       => $tipePesan === 'error',
        ])>
            @if($tipePesan === 'sukses')
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            @else
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            @endif
            <div class="flex-1">{{ $pesan }}</div>
            <button wire:click="$set('pesan', null)" class="text-gray-400 hover:text-gray-600 dark:text-gray-400 flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    @endif

    {{-- Download Backup Card --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm p-6">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">Backup Database Server Monitor</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Unduh file cadangan database <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded font-mono">.sql</code> berisi seluruh data sekolah terhubung, rilis, pengumuman, dan riwayat heartbeat.
                </p>

                <div class="mt-4">
                    <button wire:click="downloadBackupSql" wire:loading.attr="disabled"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:scale-[0.97] text-white text-xs font-semibold rounded-xl transition shadow-sm">
                        <svg wire:loading.remove wire:target="downloadBackupSql" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <svg wire:loading wire:target="downloadBackupSql" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="downloadBackupSql">Download Database (.sql)</span>
                        <span wire:loading wire:target="downloadBackupSql">Membuat file SQL...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Restore Database Card --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm p-6">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-950/60 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">Restore Database dari Server Lain</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Unggah file backup SQL dari server monitor sebelumnya untuk memulihkan seluruh instalasi sekolah dan riwayat monitoring.
                </p>

                <form wire:submit="restoreSql" class="mt-4 space-y-3">
                    <div class="border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl p-5 text-center bg-gray-50/50 dark:bg-gray-800/40">
                        <input type="file" wire:model="fileSql" id="input-file-sql" accept=".sql" class="hidden" />
                        <label for="input-file-sql" class="cursor-pointer flex flex-col items-center gap-1.5">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            @if($fileSql)
                                <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400">{{ $fileSql->getClientOriginalName() }}</span>
                                <span class="text-[11px] text-gray-400">Klik untuk ganti file</span>
                            @else
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Klik untuk memilih file .sql backup</span>
                                <span class="text-[11px] text-gray-400">Maksimal 50 MB</span>
                            @endif
                        </label>
                    </div>
                    @error('fileSql') <p class="text-xs text-red-500">{{ $message }}</p> @enderror

                    @if($fileSql)
                        <button type="submit" wire:loading.attr="disabled"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-600 hover:bg-amber-700 active:scale-[0.97] text-white text-xs font-semibold rounded-xl transition shadow-sm">
                            <svg wire:loading.remove wire:target="restoreSql" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            <svg wire:loading wire:target="restoreSql" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="restoreSql">Jalankan Restore Database</span>
                            <span wire:loading wire:target="restoreSql">Memproses restore database...</span>
                        </button>
                    @endif
                </form>
            </div>
        </div>
    </div>

</div>
