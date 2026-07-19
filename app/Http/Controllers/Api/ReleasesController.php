<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Release;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReleasesController extends Controller
{
    public function latest(Request $request): JsonResponse
    {
        $channel = $request->query('channel', 'stable');
        $version = $request->query('version', '0.0.0');

        $latest = Release::latestForChannel($channel);
        $announcement = Announcement::currentForChannel($channel);

        $latestVersion = $latest?->version ?? $version;
        $releasedAt    = $latest?->released_at?->toDateString() ?? now()->toDateString();
        $category      = $latest?->category ?? 'feature';
        $title         = $latest?->title ?? '';
        $notes         = $latest?->notes ?? '';
        $mandatory     = $latest?->mandatory ?? false;

        // Jika versi sama, tidak ada update
        if (version_compare($latestVersion, $version, '<=')) {
            $latestVersion = $version;
            $mandatory     = false;
        }

        return response()->json([
            'latest_version' => $latestVersion,
            'released_at'    => $releasedAt,
            'channel'        => $channel,
            'category'       => $category,
            'title'          => $title,
            'notes'          => $notes,
            'mandatory'      => $mandatory,
            'announcement'   => $announcement ? [
                'title'    => $announcement->title,
                'message'  => $announcement->message,
                'priority' => $announcement->priority,
                'until'    => $announcement->active_until?->toISOString(),
            ] : null,
        ]);
    }

    public function changelog(Request $request): JsonResponse
    {
        $channel = $request->query('channel', 'stable');
        $limit   = min((int) $request->query('limit', 20), 50);

        $releases = Release::active()
            ->forChannel($channel)
            ->orderByDesc('released_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['version', 'released_at', 'channel', 'category', 'title', 'notes', 'mandatory']);

        return response()->json([
            'releases' => $releases->map(fn ($r) => [
                'version'     => $r->version,
                'released_at' => $r->released_at->toDateString(),
                'channel'     => $r->channel,
                'category'    => $r->category,
                'title'       => $r->title,
                'notes'       => $r->notes,
                'mandatory'   => $r->mandatory,
            ]),
        ]);
    }
}
