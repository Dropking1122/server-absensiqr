<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\HeartbeatLog;
use App\Models\Installation;
use App\Models\Release;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    private array $sekolah = [
        ['name' => 'SMA Negeri 1 Jakarta',    'url' => 'https://absensi.sman1jakarta.sch.id'],
        ['name' => 'SMA Negeri 2 Bandung',    'url' => 'https://absensi.sman2bandung.sch.id'],
        ['name' => 'SMK Negeri 1 Surabaya',   'url' => 'https://absensi.smkn1sby.sch.id'],
        ['name' => 'SMA Negeri 3 Yogyakarta', 'url' => 'https://absensi.sman3jogja.sch.id'],
        ['name' => 'SMK Negeri 2 Medan',      'url' => 'https://absensi.smkn2medan.sch.id'],
        ['name' => 'SMA Negeri 1 Semarang',   'url' => 'https://absensi.sman1smg.sch.id'],
        ['name' => 'SMA Negeri 5 Makassar',   'url' => 'https://absensi.sman5mks.sch.id'],
        ['name' => 'SMK Negeri 3 Palembang',  'url' => 'https://absensi.smkn3plg.sch.id'],
        ['name' => 'SMA Negeri 2 Malang',     'url' => 'https://absensi.sman2malang.sch.id'],
        ['name' => 'SMK Negeri 1 Denpasar',   'url' => 'https://absensi.smkn1dps.sch.id'],
    ];

    public function run(): void
    {
        // Rilis
        $releases = [
            ['version' => '1.0.0', 'days_ago' => 60, 'category' => 'feature',  'title' => 'Rilis Awal'],
            ['version' => '1.1.0', 'days_ago' => 30, 'category' => 'bugfix',   'title' => 'Perbaikan Juni 2026'],
            ['version' => '1.2.0', 'days_ago' => 4,  'category' => 'feature',  'title' => 'Pembaruan Juli 2026'],
        ];

        foreach ($releases as $r) {
            Release::updateOrCreate(
                ['version' => $r['version']],
                [
                    'released_at' => now()->subDays($r['days_ago'])->toDateString(),
                    'channel'     => 'stable',
                    'category'    => $r['category'],
                    'title'       => $r['title'],
                    'notes'       => "## {$r['title']}\n\n- Perbaikan sistem\n- Optimasi performa\n- Update keamanan",
                    'mandatory'   => false,
                    'is_active'   => true,
                ]
            );
        }

        // Pengumuman aktif
        Announcement::firstOrCreate(
            ['title' => 'Maintenance Server Terjadwal'],
            [
                'message'        => 'Server pusat akan dilakukan maintenance pada Minggu dini hari pukul 02.00 - 04.00 WIB. Harap maklum jika terjadi gangguan layanan.',
                'priority'       => 'info',
                'target_channel' => null,
                'active_from'    => now(),
                'active_until'   => now()->addDays(7),
                'is_active'      => true,
            ]
        );

        // Versi untuk simulasi distribusi
        $versionPool = ['1.0.0', '1.0.0', '1.1.0', '1.1.0', '1.2.0', '1.2.0', '1.2.0', '1.2.0', '1.1.0', '1.0.0'];

        foreach ($this->sekolah as $i => $school) {
            $installId = md5($school['name']);
            $isActive  = $i < 7; // 7 sekolah aktif, 3 sering offline
            $version   = $versionPool[$i];

            $firstSeen = now()->subDays(60);
            $lastSeen  = $isActive
                ? now()->subMinutes(rand(5, 60))
                : now()->subHours(rand(25, 72));

            $installation = Installation::firstOrCreate(
                ['installation_id' => $installId],
                [
                    'app_name'       => $school['name'],
                    'app_url'        => $school['url'],
                    'app_version'    => $version,
                    'php_version'    => '8.4.' . rand(0, 5),
                    'db_driver'      => 'pgsql',
                    'wa_online'      => (bool) rand(0, 1),
                    'update_channel' => 'stable',
                    'last_seen_at'   => $lastSeen,
                    'first_seen_at'  => $firstSeen,
                ]
            );

            // Generate heartbeat logs 30 hari ke belakang
            $this->generateHeartbeatLogs($installId, $version, $isActive);
        }
    }

    private function generateHeartbeatLogs(string $installId, string $version, bool $active): void
    {
        $logs = [];
        $now  = now();

        for ($day = 29; $day >= 0; $day--) {
            // Sekolah aktif: ~20 heartbeat/hari, sekolah kurang aktif: ~5-10
            $count = $active ? rand(16, 23) : rand(3, 10);

            for ($h = 0; $h < $count; $h++) {
                $hour = rand(0, 23);
                $logs[] = [
                    'installation_id' => $installId,
                    'app_version'     => $version,
                    'wa_online'       => (bool) rand(0, 1),
                    'php_version'     => '8.4.2',
                    'received_at'     => $now->copy()->subDays($day)->setHour($hour)->setMinute(rand(0, 59)),
                ];
            }
        }

        foreach (array_chunk($logs, 500) as $chunk) {
            HeartbeatLog::insert($chunk);
        }
    }
}
