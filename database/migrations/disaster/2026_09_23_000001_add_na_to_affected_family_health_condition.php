<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE affected_families MODIFY health_condition ENUM('N/A','Dead','Injured','Missing','With Illness') NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::table('affected_families')->where('health_condition', 'N/A')->update(['health_condition' => null]);
            DB::statement("ALTER TABLE affected_families MODIFY health_condition ENUM('Dead','Injured','Missing','With Illness') NULL");
        }
    }
};
