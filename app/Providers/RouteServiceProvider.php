<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Rate limiters para sistema anti-scraping
        RateLimiter::for('anti-scraping-token', function (Request $request) {
            $ip = $request->ip();
            $fingerprint = $request->header('X-Fingerprint');
            
            $limits = config('anti-scraping.limits.token');
            
            return [
                Limit::perMinute($limits['per_minute_ip'])->by('ip:' . $ip),
                Limit::perMinute($limits['per_minute_fingerprint'])->by('fp:' . $fingerprint),
                Limit::perHour($limits['per_hour_ip'])->by('ip:' . $ip),
                Limit::perDay($limits['per_day_ip'])->by('ip:' . $ip),
            ];
        });

        RateLimiter::for('anti-scraping-ofertas', function (Request $request) {
            $ip = $request->ip();
            $token = $request->header('X-Auth-Token');
            $fingerprint = $request->header('X-Fingerprint');
            
            $limits = config('anti-scraping.limits.ofertas');
            
            return [
                Limit::perMinute($limits['per_minute_token'])->by('token:' . $token),
                Limit::perMinute($limits['per_minute_ip'])->by('ip:' . $ip),
                Limit::perHour($limits['per_hour_ip'])->by('ip:' . $ip),
                Limit::perDay($limits['per_day_ip'])->by('ip:' . $ip),
            ];
        });

        RateLimiter::for('anti-scraping-historicos', function (Request $request) {
            $ip = $request->ip();
            
            $limits = config('anti-scraping.limits.historicos');
            
            return [
                Limit::perMinute($limits['per_minute_ip'])->by('ip:' . $ip),
                Limit::perHour($limits['per_hour_ip'])->by('ip:' . $ip),
                Limit::perDay($limits['per_day_ip'])->by('ip:' . $ip),
            ];
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
