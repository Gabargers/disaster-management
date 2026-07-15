<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('affected_families', fn (Blueprint $table) => $table->string('contact_number', 30)->nullable()->after('monthly_income'));
        Schema::table('dafac_records', function (Blueprint $table) {
            $table->string('reference_number')->nullable()->unique()->after('uuid');
            $table->string('interviewed_by_name')->nullable()->after('interviewed_by');
            $table->boolean('attestation_confirmed')->default(false)->after('interviewed_by_name');
        });
    }
    public function down(): void
    {
        Schema::table('dafac_records', fn (Blueprint $table) => $table->dropColumn(['reference_number', 'interviewed_by_name', 'attestation_confirmed']));
        Schema::table('affected_families', fn (Blueprint $table) => $table->dropColumn('contact_number'));
    }
};
