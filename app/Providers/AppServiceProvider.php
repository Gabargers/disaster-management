<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
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
