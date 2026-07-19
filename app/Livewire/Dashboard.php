<?php

namespace App\Livewire;

use App\Models\Installation;
use App\Models\HeartbeatLog;
use App\Models\Release;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public $totalSekolah;
    public $onlineSekarang;
    public $versiTerbaru;
    public $perluUpdate;
    public $distribusiVersi;
    public $offlineLama;
    public $heartbeatChart;

    protected $listeners = ['$refresh' => 'refresh'];

    public function mount(): void
    {
        $this->refresh();
    }

    public function refresh(): void
    {
        $threshold = (int) config('monitor.online_threshold_minutes', 90);
        $latestRelease = Release::latestForChannel('stable');
        $this->versiTerbaru = $latestRelease?->version ?? '-';

        $this->totalSekolah   = Installation::count();
        $this->onlineSekarang = Installation::online()->count();
        $this->perluUpdate    = $this->versiTerbaru !== '-'
            ? Installation::where('app_version', '<', $this->versiTerbaru)->whereNotNull('app_version')->count()
            : 0;

        $this->distribusiVersi = Installation::select('app_version', DB::raw('count(*) as total'))
            ->whereNotNull('app_version')
            ->groupBy('app_version')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) {
                return [
                    'version'    => $row->app_version,
                    'total'      => $row->total,
                    'persen'     => $this->totalSekolah > 0
                        ? round(($row->total / $this->totalSekolah) * 100, 1)
                        : 0,
                ];
            })
            ->toArray();

        $this->offlineLama = Installation::offlineLong()
            ->orderBy('last_seen_at')
            ->limit(10)
            ->get()
            ->toArray();

        // Heartbeat per jam 24 jam terakhir
        $this->heartbeatChart = HeartbeatLog::select(
                DB::raw("date_trunc('hour', received_at) as jam"),
                DB::raw('count(distinct installation_id) as aktif')
            )
            ->where('received_at', '>=', now()->subHours(24))
            ->groupBy('jam')
            ->orderBy('jam')
            ->get()
            ->map(fn ($r) => [
                'jam'   => \Carbon\Carbon::parse($r->jam)->setTimezone('Asia/Jakarta')->format('H:00'),
                'aktif' => $r->aktif,
            ])
            ->toArray();
    }

    public function render()
    {
        return view('livewire.dashboard')
            ->layout('layouts.app', ['header' => 'Dashboard', 'title' => 'Dashboard']);
    }
}
