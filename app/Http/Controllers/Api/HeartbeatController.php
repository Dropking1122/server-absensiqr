<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HeartbeatLog;
use App\Models\Installation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HeartbeatController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'installation_id' => ['required', 'string', 'max:64', 'regex:/^[a-f0-9]+$/'],
            'app_version'     => ['required', 'string', 'max:20'],
            'app_name'        => ['nullable', 'string', 'max:255'],
            'app_url'         => ['nullable', 'string', 'max:500'],
            'php_version'     => ['nullable', 'string', 'max:20'],
            'db_driver'       => ['nullable', 'string', 'max:20'],
            'wa_online'       => ['nullable', 'boolean'],
            'update_channel'  => ['nullable', 'string', 'in:stable,beta'],
            'timestamp'       => ['nullable', 'date'],
        ]);

        // Anti-replay: tolak timestamp > 10 menit di masa lalu atau depan
        if (isset($validated['timestamp'])) {
            $ts = \Carbon\Carbon::parse($validated['timestamp']);
            if (abs(now()->diffInMinutes($ts)) > 10) {
                return response()->json(['error' => 'Timestamp tidak valid'], 422);
            }
        }

        $id = $validated['installation_id'];
        $now = now();

        DB::transaction(function () use ($id, $validated, $now) {
            $existing = Installation::where('installation_id', $id)->first();

            Installation::updateOrCreate(
                ['installation_id' => $id],
                [
                    'app_name'       => $validated['app_name'] ?? $existing?->app_name,
                    'app_url'        => $validated['app_url'] ?? $existing?->app_url,
                    'app_version'    => $validated['app_version'],
                    'php_version'    => $validated['php_version'] ?? $existing?->php_version,
                    'db_driver'      => $validated['db_driver'] ?? $existing?->db_driver,
                    'wa_online'      => $validated['wa_online'] ?? false,
                    'update_channel' => $validated['update_channel'] ?? ($existing?->update_channel ?? 'stable'),
                    'last_seen_at'   => $now,
                    'first_seen_at'  => $existing?->first_seen_at ?? $now,
                ]
            );

            HeartbeatLog::create([
                'installation_id' => $id,
                'app_version'     => $validated['app_version'],
                'wa_online'       => $validated['wa_online'] ?? false,
                'php_version'     => $validated['php_version'] ?? null,
                'received_at'     => $now,
            ]);
        });

        return response()->json([
            'status'      => 'ok',
            'received_at' => $now->toISOString(),
        ]);
    }
}
