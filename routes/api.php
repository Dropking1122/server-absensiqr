<?php

use App\Http\Controllers\Api\HeartbeatController;
use App\Http\Controllers\Api\ReleasesController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:heartbeat')->post('/heartbeat', [HeartbeatController::class, 'store']);
Route::get('/releases/latest', [ReleasesController::class, 'latest']);
Route::get('/releases/changelog', [ReleasesController::class, 'changelog']);
Route::get('/developer-info', function () {
    return response()->json([
        'status'         => 'ok',
        'developer'      => 'REVDSTORE',
        'github'         => 'https://github.com/Dropking1122',
        'contact'        => 'dropking1122@gmail.com',
        'app_name'       => 'Absensi QR Sekolah',
        'copyright'      => '© 2026 REVDSTORE. All Rights Reserved.',
        'signature_hash' => hash_hmac('sha256', 'REVDSTORE|https://github.com/Dropking1122', 'REVDSTORE_SECRET_KEY_2026'),
    ]);
});
