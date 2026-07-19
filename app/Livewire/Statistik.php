<?php

namespace App\Livewire;

use App\Models\HeartbeatLog;
use App\Models\Installation;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Statistik extends Component
{
    public array $distribusiVersi  = [];
    public array $waStatus         = [];
    public array $uptimeSekolah    = [];

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        // Distribusi versi
        $total = Installation::count();
        $this->distribusiVersi = Installation::select('app_version', DB::raw('count(*) as total'))
            ->whereNotNull('app_version')
            ->groupBy('app_version')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'version' => $r->app_version,
                'total'   => $r->total,
                'persen'  => $total > 0 ? round(($r->total / $total) * 100, 1) : 0,
            ])
            ->toArray();

        // Status WA
        $waOnline  = Installation::where('wa_online', true)->count();
        $waOffline = Installation::where('wa_online', false)->count();
        $this->waStatus = [
            ['label' => 'WA Online',  'total' => $waOnline],
            ['label' => 'WA Offline', 'total' => $waOffline],
        ];

        // Uptime sekolah 30 hari (% jam aktif dari total jam)
        $totalHours = 30 * 24;
        $this->uptimeSekolah = Installation::orderByDesc('last_seen_at')
            ->limit(20)
            ->get()
            ->map(function ($inst) use ($totalHours) {
                $activeHours = HeartbeatLog::select(
                        DB::raw("date_trunc('hour', received_at) as jam")
                    )
                    ->where('installation_id', $inst->installation_id)
                    ->where('received_at', '>=', now()->subDays(30))
                    ->distinct()
                    ->count();
                return [
                    'name'    => $inst->app_name ?? $inst->installation_id,
                    'uptime'  => min(100, round(($activeHours / $totalHours) * 100, 1)),
                    'hours'   => $activeHours,
                ];
            })
            ->sortByDesc('uptime')
            ->values()
            ->toArray();
    }

    public function render()
    {
        return view('livewire.statistik')
            ->layout('layouts.app', ['header' => 'Statistik', 'title' => 'Statistik']);
    }
}
