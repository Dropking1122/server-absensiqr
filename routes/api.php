<?php

use App\Http\Controllers\Api\HeartbeatController;
use App\Http\Controllers\Api\ReleasesController;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Rate limiter untuk heartbeat
RateLimiter::for('heartbeat', function (Request $request) {
    $id = $request->input('installation_id') ?: $request->header('X-Installation-ID');
    return Limit::perHour(20)->by($id ?: $request->ip())
        ->response(fn () => response()->json(['error' => 'Terlalu banyak request'], 429));
});

Route::middleware('throttle:heartbeat')->post('/heartbeat', [HeartbeatController::class, 'store']);
Route::get('/releases/latest', [ReleasesController::class, 'latest']);
Route::get('/releases/changelog', [ReleasesController::class, 'changelog']);
