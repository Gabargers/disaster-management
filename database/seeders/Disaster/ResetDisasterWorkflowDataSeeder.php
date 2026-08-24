<?php

namespace Database\Seeders\Disaster;

use App\Models\Disaster\AffectedFamily;
use App\Models\Disaster\AuditLog;
use App\Models\Disaster\DafacRecord;
use App\Models\Disaster\EvacuationCenter;
use App\Models\Disaster\EvacuationCenterPayoutSession;
use App\Models\Disaster\PayoutSchedule;
use App\Models\Disaster\PostPayoutRequirement;
use App\Models\Disaster\TcissMasterlistRecord;
use App\Models\Disaster\UploadedDocument;
use App\Models\Disaster\ValidationRecord;
use App\Models\Integration\PersonAffected;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ResetDisasterWorkflowDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            UploadedDocument::whereIn('documentable_type', [
                DafacRecord::class,
                ValidationRecord::class,
                PostPayoutRequirement::class,
            ])->delete();

            // Clear inbound TCISS test submissions and their cascading status
            // and household-member rows so the workflow can start from zero.
            PersonAffected::query()->delete();
            DB::table('api_idempotency_records')->delete();
            DB::table('api_audit_logs')->delete();

            // TCISS has a nullable family key, so remove it explicitly before
            // deleting the household and its cascading workflow records.
            TcissMasterlistRecord::query()->delete();
            AffectedFamily::query()->delete();

            // These records are independent of the household cascade.
            EvacuationCenterPayoutSession::query()->delete();
            PayoutSchedule::query()->delete();

            AuditLog::whereIn('auditable_type', [
                AffectedFamily::class,
                DafacRecord::class,
                TcissMasterlistRecord::class,
                EvacuationCenter::class,
            ])->delete();

            // Center creation is part of the end-to-end presentation flow.
            EvacuationCenter::query()->delete();
        });

        $this->command?->info('TCISS submissions, household workflow, assignments, payout data, and evacuation centers have been reset.');
    }
}
