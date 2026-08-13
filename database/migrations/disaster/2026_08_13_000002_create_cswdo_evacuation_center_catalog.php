<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cswdo_evacuation_center_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('district', 20)->index();
            $table->foreignId('barangay_id')->nullable()->constrained('barangays')->nullOnDelete();
            $table->string('barangay_name')->index();
            $table->string('name');
            $table->string('street');
            $table->string('coordinator')->nullable();
            $table->string('assistant_coordinator')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->timestamps();
            $table->unique(['district', 'barangay_name', 'name', 'street'], 'cswdo_center_catalog_unique');
        });

        Schema::table('evacuation_centers', function (Blueprint $table) {
            $table->foreignId('cswdo_catalog_id')->nullable()->after('id')->constrained('cswdo_evacuation_center_catalog')->nullOnDelete();
            $table->string('district', 20)->nullable()->after('barangay_id');
            $table->string('assistant_coordinator')->nullable()->after('contact_person');
        });
    }

    public function down(): void
    {
        Schema::table('evacuation_centers', function (Blueprint $table) {
            $table->dropForeign(['cswdo_catalog_id']);
            $table->dropColumn(['cswdo_catalog_id', 'district', 'assistant_coordinator']);
        });
        Schema::dropIfExists('cswdo_evacuation_center_catalog');
    }
};
