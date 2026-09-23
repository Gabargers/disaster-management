<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evacuation_centers', function (Blueprint $table) {
            $table->index('barangay_id', 'evacuation_centers_barangay_id_index');
            $table->dropUnique('evacuation_centers_barangay_id_name_unique');
            $table->unique(
                ['barangay_id', 'name', 'disaster_id'],
                'evacuation_centers_barangay_name_disaster_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('evacuation_centers', function (Blueprint $table) {
            $table->dropUnique('evacuation_centers_barangay_name_disaster_unique');
            $table->unique(['barangay_id', 'name']);
            $table->dropIndex('evacuation_centers_barangay_id_index');
        });
    }
};
