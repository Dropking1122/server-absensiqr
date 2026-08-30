<?php

namespace App\Livewire;

use Livewire\Component;

class PengaturanLisensi extends Component
{
    public string $developer  = '';
    public string $github     = '';
    public string $email      = '';
    public string $copyright  = '';
    public ?string $pesan      = null;

    public function mount(): void
    {
        $this->developer = config('monitor.developer', 'REVDSTORE');
        $this->github    = config('monitor.github', 'https://github.com/Dropking1122');
        $this->email     = config('monitor.email', 'dropking1122@gmail.com');
        $this->copyright = config('monitor.copyright', '© 2026 REVDSTORE. All Rights Reserved.');
    }

    public function simpan(): void
    {
        $this->validate([
            'developer' => 'required|string|max:100',
            'github'    => 'required|url|max:200',
            'email'     => 'required|email|max:100',
            'copyright' => 'required|string|max:200',
        ]);

        // Simpan variabel ke storage/config
        $data = [
            'developer' => $this->developer,
            'github'    => $this->github,
            'email'     => $this->email,
            'copyright' => $this->copyright,
        ];

        file_put_contents(storage_path('app/developer_license.json'), json_encode($data, JSON_PRETTY_PRINT));

        $this->pesan = 'Pengaturan Lisensi Developer berhasil disimpan dan disinkronkan ke seluruh VPS sekolah terhubung.';
    }

    public function render()
    {
        return view('livewire.pengaturan-lisensi')
            ->layout('layouts.app', ['header' => 'Lisensi Developer Pusat', 'title' => 'Lisensi Developer']);
    }
}
