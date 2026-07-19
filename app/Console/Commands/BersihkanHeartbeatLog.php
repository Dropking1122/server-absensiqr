<?php

namespace App\Console\Commands;

use App\Models\HeartbeatLog;
use Illuminate\Console\Command;

class BersihkanHeartbeatLog extends Command
{
    protected $signature   = 'heartbeat:bersihkan-log';
    protected $description = 'Hapus log heartbeat yang lebih dari batas hari retensi';

    public function handle(): int
    {
        $days    = (int) config('monitor.heartbeat_log_retention_days', 90);
        $deleted = HeartbeatLog::where('received_at', '<', now()->subDays($days))->delete();

        $this->info("Berhasil menghapus {$deleted} baris log heartbeat (retensi: {$days} hari).");

        return self::SUCCESS;
    }
}
