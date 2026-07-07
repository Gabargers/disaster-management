<?php

use App\Enums\FamilyStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disasters', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('type')->index();
            $table->date('incident_date')->index();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('evacuation_centers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('barangay_id')->constrained('barangays')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('name');
            $table->string('address')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['barangay_id', 'name']);
        });

        Schema::create('affected_families', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('disaster_id')->constrained('disasters')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('barangay_id')->constrained('barangays')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('evacuation_center_id')->nullable()->constrained('evacuation_centers')->nullOnDelete();
            $table->string('household_head_surname');
            $table->string('household_head_given_name');
            $table->string('household_head_middle_name')->nullable();
            $table->date('birthdate');
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('occupation')->nullable();
            $table->decimal('monthly_income', 12, 2)->nullable();
            $table->text('complete_address');
            $table->enum('house_ownership', ['Owner', 'Renter', 'Sharer']);
            $table->enum('housing_condition', ['Totally Damaged', 'Partially Damaged', 'Water Damage']);
            $table->enum('health_condition', ['Dead', 'Injured', 'Missing', 'With Illness'])->nullable();
            $table->string('status')->default(FamilyStatus::NEEDS_REVIEW->value)->index();
            $table->string('exact_household_hash')->nullable()->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['barangay_id', 'status']);
            $table->index(['household_head_surname', 'household_head_given_name', 'birthdate'], 'affected_families_head_birthdate_idx');
        });

        Schema::create('family_members', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('affected_family_id')->constrained('affected_families')->cascadeOnDelete();
            $table->string('name');
            $table->date('birthdate')->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('relationship_to_head');
            $table->enum('sex', ['Male', 'Female'])->nullable();
            $table->string('occupation')->nullable();
            $table->decimal('monthly_income', 12, 2)->nullable();
            $table->string('health_condition')->nullable();
            $table->string('remarks_codes')->nullable();
            $table->timestamps();
        });

        Schema::create('dafac_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('affected_family_id')->unique()->constrained('affected_families')->cascadeOnDelete();
            $table->date('interview_date');
            $table->string('thumbmark_signature_path')->nullable();
            $table->foreignId('interviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('tciss_masterlist_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('affected_family_id')->nullable()->constrained('affected_families')->nullOnDelete();
            $table->foreignId('barangay_id')->constrained('barangays')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('evacuation_center_id')->nullable()->constrained('evacuation_centers')->nullOnDelete();
            $table->string('household_head_full_name');
            $table->date('birthdate')->nullable();
            $table->text('address');
            $table->string('source_reference')->nullable();
            $table->enum('verification_status', ['Verified', 'Needs Review'])->default('Needs Review')->index();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('validation_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('affected_family_id')->constrained('affected_families')->cascadeOnDelete();
            $table->enum('validated_house_ownership', ['Owner', 'Renter', 'Sharer'])->nullable();
            $table->enum('validated_housing_condition', ['Totally Damaged', 'Partially Damaged', 'Water Damage'])->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['Pending Validation', 'Validated', 'Rejected', 'Needs Correction'])->default('Pending Validation')->index();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('duplicate_checks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('affected_family_id')->constrained('affected_families')->cascadeOnDelete();
            $table->foreignId('possible_duplicate_family_id')->nullable()->constrained('affected_families')->nullOnDelete();
            $table->unsignedTinyInteger('match_score')->default(0);
            $table->json('matched_fields')->nullable();
            $table->enum('resolution', ['Pending', 'Duplicate', 'Merged', 'Separate Household'])->default('Pending')->index();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('assistance_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('affected_family_id')->constrained('affected_families')->cascadeOnDelete();
            $table->date('date_assistance_provided')->nullable();
            $table->string('assistance_kind');
            $table->decimal('quantity_amount', 12, 2)->nullable();
            $table->string('provider')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payout_schedules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('disaster_id')->constrained('disasters')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('title');
            $table->date('scheduled_date')->index();
            $table->string('venue');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payout_releases', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('payout_schedule_id')->constrained('payout_schedules')->cascadeOnDelete();
            $table->foreignId('affected_family_id')->constrained('affected_families')->cascadeOnDelete();
            $table->foreignId('assistance_record_id')->nullable()->constrained('assistance_records')->nullOnDelete();
            $table->enum('status', ['Pending', 'Scheduled', 'Released', 'Cancelled'])->default('Pending')->index();
            $table->string('release_photo_path')->nullable();
            $table->boolean('photo_override')->default(false);
            $table->text('override_reason')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['payout_schedule_id', 'affected_family_id']);
        });

        Schema::create('post_payout_requirements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('affected_family_id')->unique()->constrained('affected_families')->cascadeOnDelete();
            $table->enum('bfp_certificate_status', ['Pending', 'Submitted', 'Verified'])->default('Pending')->index();
            $table->enum('barangay_certification_status', ['Pending', 'Submitted', 'Verified'])->default('Pending')->index();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('uploaded_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->morphs('documentable');
            $table->string('document_type')->index();
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->morphs('auditable');
            $table->string('action')->index();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('uploaded_documents');
        Schema::dropIfExists('post_payout_requirements');
        Schema::dropIfExists('payout_releases');
        Schema::dropIfExists('payout_schedules');
        Schema::dropIfExists('assistance_records');
        Schema::dropIfExists('duplicate_checks');
        Schema::dropIfExists('validation_records');
        Schema::dropIfExists('tciss_masterlist_records');
        Schema::dropIfExists('dafac_records');
        Schema::dropIfExists('family_members');
        Schema::dropIfExists('affected_families');
        Schema::dropIfExists('evacuation_centers');
        Schema::dropIfExists('disasters');
    }
};
