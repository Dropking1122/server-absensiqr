<?php

namespace Database\Seeders;

use App\Models\Release;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Akun developer
        $email    = config('monitor.dev_email', 'developer@yourdomain.com');
        $password = config('monitor.dev_password', 'rahasia123');

        User::firstOrCreate(
            ['email' => $email],
            [
                'name'     => 'Developer',
                'password' => Hash::make($password),
            ]
        );

        // Rilis awal
        Release::firstOrCreate(
            ['version' => '1.0.0'],
            [
                'released_at' => now()->toDateString(),
                'channel'     => 'stable',
                'category'    => 'feature',
                'title'       => 'Rilis Awal',
                'notes'       => "## Rilis Awal\n\n- Sistem absensi QR kode\n- Dashboard monitoring guru\n- Laporan kehadiran",
                'mandatory'   => false,
                'is_active'   => true,
            ]
        );
    }
}
