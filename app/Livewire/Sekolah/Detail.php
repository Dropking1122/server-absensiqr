<?php

namespace App\Livewire\Sekolah;

use App\Models\HeartbeatLog;
use App\Models\Installation;
use App\Models\Release;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Detail extends Component
{
    public string $installationId;
    public string $activeTab = 'info';
    public ?Installation $installation = null;

    public function mount(string $installation_id): void
    {
        $this->installationId = $installation_id;
        $this->installation   = Installation::where('installation_id', $installation_id)->firstOrFail();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        $latestRelease = Release::latestForChannel($this->installation->update_channel ?? 'stable');
        $latestVersion = $latestRelease?->version;

        $heatmapData    = [];
        $riwayatLog     = collect();
        $riwayatUpdate  = collect();

        if ($this->activeTab === 'heartbeat') {
            // Data untuk heatmap 30 hari: per hari per jam
            $raw = HeartbeatLog::select(
                    DB::raw("date_trunc('hour', received_at) as jam"),
                    DB::raw('count(*) as total')
                )
                ->where('installation_id', $this->installationId)
                ->where('received_at', '>=', now()->subDays(30))
                ->groupBy('jam')
                ->get();

            foreach ($raw as $r) {
                $dt = \Carbon\Carbon::parse($r->jam)->setTimezone('Asia/Jakarta');
                $key = $dt->format('Y-m-d') . '_' . $dt->format('H');
                $heatmapData[$key] = $r->total;
            }

            $riwayatLog = HeartbeatLog::where('installation_id', $this->installationId)
                ->orderByDesc('received_at')
                ->limit(50)
                ->get();
        }

        if ($this->activeTab === 'update') {
            // Deteksi perubahan versi dari heartbeat_logs
            $riwayatUpdate = HeartbeatLog::select(
                    'app_version',
                    DB::raw('min(received_at) as pertama_kali'),
                    DB::raw('max(received_at) as terakhir_kali')
                )
                ->where('installation_id', $this->installationId)
                ->whereNotNull('app_version')
                ->groupBy('app_version')
                ->orderByDesc('pertama_kali')
                ->get();
        }

        return view('livewire.sekolah.detail', compact(
            'latestVersion', 'heatmapData', 'riwayatLog', 'riwayatUpdate'
        ))->layout('layouts.app', [
            'header' => $this->installation->app_name ?? 'Detail Sekolah',
            'title'  => 'Detail Sekolah',
        ]);
    }
}
