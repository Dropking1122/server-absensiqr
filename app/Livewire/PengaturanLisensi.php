<?php

namespace App\Livewire;

use Livewire\Component;

class PengaturanLisensi extends Component
{
    public string $developer        = '';
    public string $github           = '';
    public string $email            = '';
    public string $wa               = '';
    public string $instagram        = '';
    public string $telegram         = '';
    public string $copyright        = '';

    // Project Advisor
    public string $advisor_nama      = '';
    public string $advisor_wa        = '';
    public string $advisor_instagram = '';
    public string $advisor_telegram  = '';

    public ?string $pesan            = null;

    public function mount(): void
    {
        $file = storage_path('app/developer_license.json');
        $data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];

        $this->developer         = $data['developer']         ?? config('monitor.developer', 'REVDSTORE');
        $this->github            = $data['github']            ?? config('monitor.github', 'https://github.com/Dropking1122');
        $this->email             = $data['email']             ?? config('monitor.email', 'dropking1122@gmail.com');
        $this->wa                = $data['wa']                ?? '628123456789';
        $this->instagram         = $data['instagram']         ?? 'revdstore';
        $this->telegram          = $data['telegram']          ?? 'revdstore';
        $this->copyright         = $data['copyright']         ?? config('monitor.copyright', '© 2026 REVDSTORE. All Rights Reserved.');

        $this->advisor_nama      = $data['advisor_nama']      ?? '';
        $this->advisor_wa        = $data['advisor_wa']        ?? '';
        $this->advisor_instagram = $data['advisor_instagram'] ?? '';
        $this->advisor_telegram  = $data['advisor_telegram']  ?? '';
    }

    public function simpan(): void
    {
        $this->validate([
            'developer'         => 'required|string|max:100',
            'github'            => 'required|url|max:200',
            'email'             => 'required|email|max:100',
            'wa'                => 'nullable|string|max:20',
            'instagram'         => 'nullable|string|max:60',
            'telegram'          => 'nullable|string|max:60',
            'copyright'         => 'required|string|max:200',
            'advisor_nama'      => 'nullable|string|max:100',
            'advisor_wa'        => 'nullable|string|max:20',
            'advisor_instagram' => 'nullable|string|max:60',
            'advisor_telegram'  => 'nullable|string|max:60',
        ]);

        $data = [
            'developer'         => $this->developer,
            'github'            => $this->github,
            'email'             => $this->email,
            'wa'                => $this->wa,
            'instagram'         => $this->instagram,
            'telegram'          => $this->telegram,
            'copyright'         => $this->copyright,
            'advisor_nama'      => $this->advisor_nama,
            'advisor_wa'        => $this->advisor_wa,
            'advisor_instagram' => $this->advisor_instagram,
            'advisor_telegram'  => $this->advisor_telegram,
        ];

        file_put_contents(storage_path('app/developer_license.json'), json_encode($data, JSON_PRETTY_PRINT));

        $this->pesan = 'Pengaturan Lisensi Developer & Project Advisor berhasil disimpan dan disinkronkan ke seluruh VPS sekolah terhubung.';
    }

    public function render()
    {
        return view('livewire.pengaturan-lisensi')
            ->layout('layouts.app', ['header' => 'Lisensi Developer Pusat', 'title' => 'Lisensi Developer']);
    }
}
