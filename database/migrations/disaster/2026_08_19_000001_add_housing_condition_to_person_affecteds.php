<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('person_affecteds', function (Blueprint $table) {
            $table->string('housing_condition')->nullable()->after('housing');
        });
    }

    public function down(): void
    {
        Schema::table('person_affecteds', function (Blueprint $table) {
            $table->dropColumn('housing_condition');
        });
    }
};
