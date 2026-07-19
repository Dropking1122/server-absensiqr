<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use League\CommonMark\CommonMarkConverter;

class Release extends Model
{
    protected $fillable = [
        'version',
        'released_at',
        'channel',
        'category',
        'title',
        'notes',
        'notes_html',
        'mandatory',
        'min_version',
        'is_active',
    ];

    protected $casts = [
        'released_at' => 'date',
        'mandatory'   => 'boolean',
        'is_active'   => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Release $release) {
            if ($release->isDirty('notes') || empty($release->notes_html)) {
                $converter = new CommonMarkConverter(['html_input' => 'strip', 'allow_unsafe_links' => false]);
                $release->notes_html = $converter->convert($release->notes)->getContent();
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public static function latestForChannel(string $channel): ?self
    {
        return static::active()
            ->forChannel($channel)
            ->orderByDesc('released_at')
            ->orderByDesc('id')
            ->first();
    }

    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            'feature'  => 'Fitur',
            'bugfix'   => 'Perbaikan',
            'security' => 'Keamanan',
            'hotfix'   => 'Hotfix',
            default    => ucfirst($this->category),
        };
    }
}
