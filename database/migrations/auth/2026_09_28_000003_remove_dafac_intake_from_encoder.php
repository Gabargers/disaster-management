<?php

use Database\Seeders\Disaster\DisasterRoleSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (! app()->environment('testing')) {
            (new DisasterRoleSeeder)->run();
        }
    }

    public function down(): void
    {
        // Access restrictions are intentionally retained on rollback.
    }
};
