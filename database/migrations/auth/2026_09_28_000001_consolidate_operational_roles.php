<?php

use Database\Seeders\Disaster\DisasterRoleSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (! app()->environment('testing')) {
            app(DisasterRoleSeeder::class)->run();
        }
    }

    public function down(): void
    {
        // Role consolidation intentionally keeps existing user access intact on rollback.
    }
};
