<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'message',
        'priority',
        'target_channel',
        'active_from',
        'active_until',
        'is_active',
    ];

    protected $casts = [
        'active_from'  => 'datetime',
        'active_until' => 'datetime',
        'is_active'    => 'boolean',
    ];

    public function scopeActiveNow($query)
    {
        return $query->where('is_active', true)
            ->where('active_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('active_until')
                  ->orWhere('active_until', '>', now());
            });
    }

    public static function currentForChannel(string $channel): ?self
    {
        return static::activeNow()
            ->where(function ($q) use ($channel) {
                $q->whereNull('target_channel')
                  ->orWhere('target_channel', $channel);
            })
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'warning' THEN 2 ELSE 3 END")
            ->first();
    }

    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            'info'    => 'Info',
            'warning' => 'Peringatan',
            'urgent'  => 'Mendesak',
            default   => ucfirst($this->priority),
        };
    }
}
