<?php

namespace Database\Seeders;

use Database\Seeders\Auth\SuperAdminSeeder;
use Database\Seeders\Auth\AdminSeeder;
use Database\Seeders\Disaster\DisasterRoleSeeder;
use Database\Seeders\Disaster\DisasterDemoDataSeeder;
use Database\Seeders\Disaster\ConnectedLocationSampleSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DisasterRoleSeeder::class,
            SuperAdminSeeder::class,
            AdminSeeder::class,
        ]);

        // Deterministic fixtures belong in the isolated test database only.
        // Normal local/production seeding must never recreate sample households.
        if (app()->environment('testing')) {
            $this->call([
                DisasterDemoDataSeeder::class,
                ConnectedLocationSampleSeeder::class,
            ]);
        }
    }
}
