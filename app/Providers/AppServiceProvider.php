<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
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
        RateLimiter::for('person-affected-api', function (Request $request): array {
            $clientKey = $request->attributes->get('api_client_id');
            $key = is_string($clientKey) && $clientKey !== ''
                ? $clientKey
                : 'ip:'.$request->ip();

            return [
                Limit::perMinute((int) config('services.system_a.rate_limit_per_minute', 60))
                    ->by('minute:'.$key),
                Limit::perSecond((int) config('services.system_a.rate_limit_burst_per_second', 10))
                    ->by('second:'.$key),
            ];
        });

        // Metronic is Bootstrap-based. Laravel's default paginator uses Tailwind
        // utility classes, which leaves duplicate controls and oversized SVG
        // arrows when Tailwind is not loaded.
        Paginator::useBootstrapFive();

        // The canonical superadmin role is the system-wide authorization
        // bypass. Business-rule validation still runs inside controllers and
        // services after authorization succeeds.
        Gate::before(fn ($user) => $user->hasRole('superadmin') ? true : null);
    }
}
