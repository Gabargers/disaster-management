<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('evacuation_centers', function (Blueprint $table) {
            $table->foreignId('disaster_id')->nullable()->after('uuid')->constrained('disasters')->nullOnDelete();
            $table->string('contact_person')->nullable();
            $table->string('contact_number', 30)->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('ACTIVE')->index();
            $table->string('payout_availability')->default('NOT_AVAILABLE')->index();
            $table->date('default_payout_date')->nullable();
            $table->time('default_payout_start_time')->nullable();
            $table->time('default_payout_end_time')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::create('evacuation_center_assignments', function (Blueprint $table) {
            $table->id(); $table->uuid('uuid')->unique();
            $table->foreignId('evacuation_center_id')->constrained()->cascadeOnDelete();
            $table->foreignId('affected_family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('disaster_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at'); $table->timestamp('unassigned_at')->nullable();
            $table->string('status')->default('ACTIVE')->index(); $table->timestamps();
            $table->index(['affected_family_id', 'disaster_id', 'status'], 'active_family_disaster_assignment_idx');
        });

        Schema::create('evacuation_center_payout_sessions', function (Blueprint $table) {
            $table->id(); $table->uuid('uuid')->unique();
            $table->foreignId('evacuation_center_id')->constrained()->cascadeOnDelete();
            $table->foreignId('disaster_id')->constrained()->restrictOnDelete();
            $table->date('payout_date'); $table->time('start_time'); $table->time('end_time');
            $table->string('payout_area'); $table->foreignId('assigned_officer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('assistance_type'); $table->decimal('default_amount', 12, 2)->nullable();
            $table->string('provider'); $table->string('status')->default('OPEN')->index(); $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
        });

        Schema::table('payout_releases', function (Blueprint $table) {
            $table->foreignId('evacuation_center_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payout_session_id')->nullable()->constrained('evacuation_center_payout_sessions')->nullOnDelete();
            $table->string('assistance_kind')->nullable(); $table->decimal('quantity', 12, 2)->nullable();
            $table->decimal('amount', 12, 2)->nullable(); $table->string('provider')->nullable();
            $table->string('payout_photo_path')->nullable(); $table->string('photo_caption')->nullable();
            $table->timestamp('photo_taken_at')->nullable(); $table->foreignId('photo_uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('idempotency_key')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('payout_releases', function (Blueprint $table) {
            $table->dropForeign(['evacuation_center_id']); $table->dropForeign(['payout_session_id']); $table->dropForeign(['photo_uploaded_by']);
            $table->dropColumn(['evacuation_center_id','payout_session_id','assistance_kind','quantity','amount','provider','payout_photo_path','photo_caption','photo_taken_at','photo_uploaded_by','idempotency_key']);
        });
        Schema::dropIfExists('evacuation_center_payout_sessions'); Schema::dropIfExists('evacuation_center_assignments');
        Schema::table('evacuation_centers', function (Blueprint $table) {
            $table->dropForeign(['disaster_id']); $table->dropForeign(['created_by']); $table->dropForeign(['updated_by']);
            $table->dropColumn(['disaster_id','contact_person','contact_number','description','status','payout_availability','default_payout_date','default_payout_start_time','default_payout_end_time','created_by','updated_by']);
        });
    }
};
