<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('person_affecteds', function (Blueprint $table) {
            $table->foreignId('evacuation_center_id')->nullable()->after('housing')->constrained()->nullOnDelete();
            $table->foreignId('evacuation_center_assigned_by')->nullable()->after('evacuation_center_id')->constrained('users')->nullOnDelete();
            $table->timestamp('evacuation_center_assigned_at')->nullable()->after('evacuation_center_assigned_by');
        });
    }

    public function down(): void
    {
        Schema::table('person_affecteds', function (Blueprint $table) {
            $table->dropForeign(['evacuation_center_id']);
            $table->dropForeign(['evacuation_center_assigned_by']);
            $table->dropColumn(['evacuation_center_id', 'evacuation_center_assigned_by', 'evacuation_center_assigned_at']);
        });
    }
};
