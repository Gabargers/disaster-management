<?php

use Database\Seeders\Disaster\CswdoEvacuationCenterCatalogSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new CswdoEvacuationCenterCatalogSeeder)->run();
    }

    public function down(): void
    {
        // Official reference data is retained when rolling back application code.
    }
};
