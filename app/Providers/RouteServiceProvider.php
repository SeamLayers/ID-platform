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

        // Reverse contact exchange is unauthenticated and writes a row per call,
        // so it is keyed on IP alone. The per-minute limit stops a scripted
        // burst; the daily cap stops a slow drip that would otherwise flood one
        // employee's inbox overnight. Conference Wi-Fi shares an egress IP, so
        // the daily figure is generous rather than tight.
        RateLimiter::for('card-share', function (Request $request) {
            // The two keys must differ: ThrottleRequests hashes limiterName+key,
            // so identical keys would make both limits share one counter and the
            // shorter decay would silently reset the daily cap.
            return [
                Limit::perMinute(5)->by('card-share:min:' . $request->ip()),
                Limit::perDay(50)->by('card-share:day:' . $request->ip()),
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
