<?php

use App\Http\Controllers\Api\HeartbeatController;
use App\Http\Controllers\Api\ReleasesController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:heartbeat')->post('/heartbeat', [HeartbeatController::class, 'store']);
Route::get('/releases/latest', [ReleasesController::class, 'latest']);
Route::get('/releases/changelog', [ReleasesController::class, 'changelog']);
