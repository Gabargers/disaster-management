<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // MySQL DDL is not transactional. These guards let a partially executed
        // migration resume safely instead of failing on tables already created.
        if (! Schema::hasTable('workflow_histories')) {
            Schema::create('workflow_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('affected_family_id')->constrained()->cascadeOnDelete();
                $table->string('from_status')->nullable();
                $table->string('to_status')->index();
                $table->string('action');
                $table->text('remarks')->nullable();
                $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('performed_at');
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('payroll_batches')) {
            Schema::create('payroll_batches', function (Blueprint $table) {
                $table->id(); $table->uuid('uuid')->unique();
                $table->string('reference_number')->unique(); $table->date('payroll_date');
                $table->decimal('total_amount', 14, 2)->default(0);
                $table->string('status')->default('DRAFT')->index();
                $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('submitted_at')->nullable(); $table->timestamps();
            });
        }

        if (! Schema::hasTable('payroll_records')) {
            Schema::create('payroll_records', function (Blueprint $table) {
                $table->id(); $table->foreignId('payroll_batch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('affected_family_id')->unique()->constrained()->cascadeOnDelete();
                $table->foreignId('dafac_record_id')->constrained()->restrictOnDelete();
                $table->decimal('amount', 12, 2)->default(0); $table->string('status')->default('READY')->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('post_payout_requirements', 'notes')) {
            Schema::table('post_payout_requirements', fn (Blueprint $table) => $table->text('notes')->nullable());
        }

        DB::table('affected_families')->where('status', 'DUPLICATE_CHECKED')->update(['status' => 'DUPLICATE_CLEARED']);
        DB::table('affected_families')->where('status', 'DUPLICATE')->update(['status' => 'DUPLICATE_CONFIRMED']);
        DB::table('affected_families')->where('status', 'NEEDS_REVIEW')->update(['status' => 'DUPLICATE_CHECK_PENDING']);

        foreach (DB::table('dafac_records')->whereNull('reference_number')->orderBy('id')->get(['id', 'created_at']) as $record) {
            $year = substr((string) $record->created_at, 0, 4) ?: date('Y');
            $sequence = $record->id;
            do {
                $reference = sprintf('DAFAC-%s-%04d', $year, $sequence++);
            } while (DB::table('dafac_records')->where('reference_number', $reference)->exists());
            DB::table('dafac_records')->where('id', $record->id)->update(['reference_number' => $reference]);
        }

        foreach (DB::table('affected_families')->get(['id', 'status', 'created_by', 'created_at']) as $family) {
            DB::table('workflow_histories')->updateOrInsert(
                ['affected_family_id' => $family->id, 'action' => 'legacy_record_backfilled'],
                ['from_status' => null, 'to_status' => $family->status,
                    'remarks' => 'Initial workflow history created during safe integration backfill.',
                    'performed_by' => $family->created_by, 'performed_at' => $family->created_at ?? now(),
                    'metadata' => json_encode(['source' => 'legacy_backfill']), 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('post_payout_requirements', 'notes')) {
            Schema::table('post_payout_requirements', fn (Blueprint $table) => $table->dropColumn('notes'));
        }
        Schema::dropIfExists('payroll_records'); Schema::dropIfExists('payroll_batches'); Schema::dropIfExists('workflow_histories');
    }
};
