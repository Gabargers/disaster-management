<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('person_affecteds', function (Blueprint $table) {
            $table->id();
            $table->string('control_number')->unique();
            $table->timestamps();
        });

        Schema::create('person_affected_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_affected_id')->constrained('person_affecteds')->cascadeOnDelete();
            $table->string('status');
            $table->dateTime('date_tagged', 6);
            $table->timestamps();

            $table->unique(['person_affected_id', 'date_tagged'], 'person_affected_date_tagged_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_affected_statuses');
        Schema::dropIfExists('person_affecteds');
    }
};
