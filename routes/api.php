<?php

use App\Http\Controllers\Api\HeartbeatController;
use App\Http\Controllers\Api\ReleasesController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:heartbeat')->post('/heartbeat', [HeartbeatController::class, 'store']);
Route::get('/releases/latest', [ReleasesController::class, 'latest']);
Route::get('/releases/changelog', [ReleasesController::class, 'changelog']);
Route::get('/developer-info', function () {
    $file = storage_path('app/developer_license.json');
    $data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];

    $developer = $data['developer'] ?? config('monitor.developer', 'REVDSTORE');
    $github    = $data['github']    ?? config('monitor.github', 'https://github.com/Dropking1122');
    $email     = $data['email']     ?? config('monitor.email', 'dropking1122@gmail.com');
    $copyright = $data['copyright'] ?? config('monitor.copyright', '© 2026 REVDSTORE. All Rights Reserved.');

    return response()->json([
        'status'         => 'ok',
        'developer'      => $developer,
        'github'         => $github,
        'contact'        => $email,
        'app_name'       => 'Absensi QR Sekolah',
        'copyright'      => $copyright,
        'signature_hash' => hash_hmac('sha256', $developer . '|' . $github, 'REVDSTORE_SECRET_KEY_2026'),
    ]);
});
