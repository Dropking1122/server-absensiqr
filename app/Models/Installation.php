<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Installation extends Model
{
    protected $fillable = [
        'installation_id',
        'app_name',
        'app_url',
        'app_version',
        'php_version',
        'db_driver',
        'wa_online',
        'update_channel',
        'last_seen_at',
        'first_seen_at',
    ];

    protected $casts = [
        'wa_online'     => 'boolean',
        'last_seen_at'  => 'datetime',
        'first_seen_at' => 'datetime',
    ];

    public function heartbeatLogs(): HasMany
    {
        return $this->hasMany(HeartbeatLog::class, 'installation_id', 'installation_id');
    }

    public function getIsOnlineAttribute(): bool
    {
        if (! $this->last_seen_at) {
            return false;
        }
        $threshold = (int) config('monitor.online_threshold_minutes', 90);
        return $this->last_seen_at->greaterThan(now()->subMinutes($threshold));
    }

    public function getOnlineStatusAttribute(): string
    {
        if (! $this->last_seen_at) {
            return 'never';
        }
        if ($this->is_online) {
            return 'online';
        }
        if ($this->last_seen_at->lessThan(now()->subHours(48))) {
            return 'offline_long';
        }
        return 'offline';
    }

    public function scopeOnline($query)
    {
        $threshold = (int) config('services.monitor.online_threshold_minutes', 90);
        return $query->where('last_seen_at', '>', now()->subMinutes($threshold));
    }

    public function scopeOfflineLong($query)
    {
        return $query->where('last_seen_at', '<', now()->subHours(48));
    }

    public function scopeNeedsUpdate($query, ?string $latestVersion)
    {
        if (! $latestVersion) {
            return $query->whereRaw('1=0');
        }
        return $query->where('app_version', '<', $latestVersion)
                     ->whereNotNull('app_version');
    }
}
