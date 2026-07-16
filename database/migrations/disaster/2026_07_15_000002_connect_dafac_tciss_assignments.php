<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tciss_masterlist_records', function (Blueprint $table) {
            $table->foreignId('dafac_record_id')->nullable()->after('affected_family_id')->constrained('dafac_records')->nullOnDelete();
            $table->string('source')->default('MANUAL_TCISS')->after('source_reference')->index();
            $table->index('affected_family_id', 'tciss_affected_family_lookup');
        });
        Schema::table('evacuation_center_assignments', function (Blueprint $table) {
            $table->text('remarks')->nullable()->after('status');
            $table->index(['affected_family_id', 'disaster_id', 'status'], 'family_disaster_assignment_status');
        });
        Schema::create('integration_backfill_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affected_family_id')->nullable()->constrained()->nullOnDelete();
            $table->string('issue_type')->index();
            $table->text('details');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->unique(['affected_family_id', 'issue_type']);
        });

        foreach (DB::table('dafac_records')->orderBy('id')->get() as $dafac) {
            $family = DB::table('affected_families')->where('id', $dafac->affected_family_id)->first();
            if (! $family) continue;
            $tciss = DB::table('tciss_masterlist_records')->where('affected_family_id', $family->id)->first();
            if ($tciss) {
                DB::table('tciss_masterlist_records')->where('id', $tciss->id)->update(['dafac_record_id' => $dafac->id, 'source' => 'DAFAC_INTAKE']);
            } else {
                DB::table('tciss_masterlist_records')->insert([
                    'uuid' => (string) Str::uuid(), 'affected_family_id' => $family->id, 'dafac_record_id' => $dafac->id,
                    'barangay_id' => $family->barangay_id, 'evacuation_center_id' => $family->evacuation_center_id,
                    'household_head_full_name' => trim("{$family->household_head_given_name} {$family->household_head_middle_name} {$family->household_head_surname}"),
                    'birthdate' => $family->birthdate, 'address' => $family->complete_address,
                    'source_reference' => sprintf('TCISS-%s-%04d', substr((string) $dafac->created_at, 0, 4) ?: date('Y'), $dafac->id),
                    'source' => 'DAFAC_INTAKE', 'verification_status' => 'Needs Review', 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            if ($family->evacuation_center_id) {
                DB::table('evacuation_center_assignments')->updateOrInsert(
                    ['affected_family_id' => $family->id, 'disaster_id' => $family->disaster_id, 'status' => 'ACTIVE'],
                    ['uuid' => (string) Str::uuid(), 'evacuation_center_id' => $family->evacuation_center_id, 'assigned_by' => $family->created_by,
                        'assigned_at' => $family->created_at ?? now(), 'remarks' => 'Backfilled from explicit affected_families.evacuation_center_id.', 'created_at' => now(), 'updated_at' => now()]
                );
            } else {
                DB::table('integration_backfill_issues')->updateOrInsert(
                    ['affected_family_id' => $family->id, 'issue_type' => 'MISSING_EVACUATION_CENTER'],
                    ['details' => 'No explicit evacuation center was stored; manual assignment is required.', 'created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_backfill_issues');
        Schema::table('evacuation_center_assignments', function (Blueprint $table) {
            $table->dropIndex('family_disaster_assignment_status');
            $table->dropColumn('remarks');
        });
        Schema::table('tciss_masterlist_records', function (Blueprint $table) {
            $table->dropIndex('tciss_affected_family_lookup');
            $table->dropForeign(['dafac_record_id']);
            $table->dropColumn(['dafac_record_id', 'source']);
        });
    }
};
