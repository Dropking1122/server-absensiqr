<?php

use Illuminate\Support\Facades\Route;

// Redirect root ke dashboard jika sudah login, atau ke login
Route::redirect('/', '/dashboard');

// Dashboard (dilindungi auth)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', \App\Livewire\Dashboard::class)->name('dashboard');
    Route::get('/sekolah', \App\Livewire\Sekolah\Index::class)->name('sekolah.index');
    Route::get('/sekolah/{installation_id}', \App\Livewire\Sekolah\Detail::class)->name('sekolah.detail');
    Route::get('/rilis', \App\Livewire\Rilis\Index::class)->name('rilis.index');
    Route::get('/pengumuman', \App\Livewire\Pengumuman\Index::class)->name('pengumuman.index');
    Route::get('/statistik', \App\Livewire\Statistik::class)->name('statistik');
    Route::get('/backup', \App\Livewire\BackupDatabase::class)->name('backup');
});

require __DIR__.'/auth.php';
