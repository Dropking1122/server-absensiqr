<?php

namespace App\Livewire;

use App\Services\DatabaseBackupService;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupDatabase extends Component
{
    use WithFileUploads;

    public $fileSql      = null;
    public ?string $pesan     = null;
    public string  $tipePesan = 'sukses';

    public function downloadBackupSql(DatabaseBackupService $service): ?BinaryFileResponse
    {
        $fileName = 'backup-server-monitor-' . date('Y-m-d_H-i-s') . '.sql';
        $tempPath = storage_path('app/' . $fileName);

        $sukses = $service->dumpToFile($tempPath);

        if (!$sukses || !file_exists($tempPath)) {
            $this->pesan     = 'Gagal membuat file backup SQL.';
            $this->tipePesan = 'error';
            return null;
        }

        return response()->download($tempPath, $fileName)->deleteFileAfterSend(true);
    }

    public function restoreSql(DatabaseBackupService $service): void
    {
        $this->validate([
            'fileSql' => 'required|file|max:51200', // Maks 50MB
        ], [
            'fileSql.required' => 'Pilih file backup (.sql) terlebih dahulu.',
            'fileSql.max'      => 'Ukuran file backup tidak boleh lebih dari 50 MB.',
        ]);

        $path = $this->fileSql->getRealPath();
        $hasil = $service->restoreFromFile($path);

        if ($hasil['sukses']) {
            $this->pesan     = 'Restore database berhasil dieksekusi.';
            $this->tipePesan = 'sukses';
            $this->fileSql   = null;
        } else {
            $this->pesan     = 'Restore gagal: ' . ($hasil['pesan'] ?? 'Terjadi kesalahan.');
            $this->tipePesan = 'error';
        }
    }

    public function render()
    {
        return view('livewire.backup-database')
            ->layout('layouts.app', ['header' => 'Backup & Restore Database', 'title' => 'Backup DB']);
    }
}
