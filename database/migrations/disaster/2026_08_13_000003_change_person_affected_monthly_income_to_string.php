<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('person_affecteds', function (Blueprint $table) {
            $table->string('monthly_income')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('person_affecteds', function (Blueprint $table) {
            $table->decimal('monthly_income', 14, 2)->nullable()->change();
        });
    }
};
