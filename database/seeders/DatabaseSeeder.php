<?php

namespace Database\Seeders;

use Database\Seeders\Auth\SuperAdminSeeder;
use Database\Seeders\Auth\AdminSeeder;
use Database\Seeders\Disaster\DisasterRoleSeeder;
use Database\Seeders\Disaster\DisasterDemoDataSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DisasterRoleSeeder::class,
            SuperAdminSeeder::class,
            AdminSeeder::class,
            DisasterDemoDataSeeder::class,
        ]);
    }
}
