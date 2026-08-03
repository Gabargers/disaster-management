<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('person_affecteds', function (Blueprint $table) {
            $table->string('full_name')->nullable()->after('control_number')->index();
            $table->date('birthdate')->nullable()->after('full_name');
            $table->unsignedSmallInteger('age')->nullable()->after('birthdate');
            $table->string('sex', 30)->nullable()->after('age');
            $table->string('code')->nullable()->after('sex');
            $table->string('occupation')->nullable()->after('code');
            $table->decimal('monthly_income', 14, 2)->nullable()->after('occupation');
            $table->string('health_condition')->nullable()->after('monthly_income');
            $table->string('district')->nullable()->after('health_condition');
            $table->string('barangay')->nullable()->after('district');
            $table->string('street')->nullable()->after('barangay');
            $table->string('city')->nullable()->after('street');
            $table->string('family_head_name')->nullable()->after('city');
            $table->string('family_head_control_number')->nullable()->after('family_head_name');
            $table->string('relationship')->nullable()->after('family_head_control_number');
            $table->string('housing')->nullable()->after('relationship');
        });

        Schema::create('person_affected_family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_affected_id')->constrained('person_affecteds')->cascadeOnDelete();
            $table->string('control_number');
            $table->string('full_name');
            $table->string('relationship')->nullable();
            $table->unsignedSmallInteger('age')->nullable();
            $table->string('sex', 30)->nullable();
            $table->string('code')->nullable();
            $table->string('housing')->nullable();
            $table->timestamps();
            $table->unique(['person_affected_id', 'control_number'], 'person_affected_member_control_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_affected_family_members');
        Schema::table('person_affecteds', function (Blueprint $table) {
            $table->dropIndex(['full_name']);
            $table->dropColumn([
                'full_name', 'birthdate', 'age', 'sex', 'code', 'occupation', 'monthly_income',
                'health_condition', 'district', 'barangay', 'street', 'city', 'family_head_name',
                'family_head_control_number', 'relationship', 'housing',
            ]);
        });
    }
};
