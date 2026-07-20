<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Konfigurasi rate limiter aplikasi.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('heartbeat', function (Request $request) {
            $id = $request->input('installation_id') ?: $request->header('X-Installation-ID');
            return Limit::perHour(20)->by($id ?: $request->ip())
                ->response(fn () => response()->json(['error' => 'Terlalu banyak request'], 429));
        });
    }
}
