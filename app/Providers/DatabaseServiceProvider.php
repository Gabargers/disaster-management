<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }
    
    public function boot(): void
    {
        $this->loadMigrationsFrom([
            database_path('migrations/auth'),
            database_path('migrations/cms'),
            database_path('migrations/disaster'),
            database_path('migrations/logs'),
            database_path('migrations/others'),
        ]);
    }
}
