<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('person_affecteds', function (Blueprint $table) {
            $table->foreignId('affected_family_id')->nullable()->after('id')->constrained('affected_families')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('person_affecteds', function (Blueprint $table) {
            $table->dropConstrainedForeignId('affected_family_id');
        });
    }
};
