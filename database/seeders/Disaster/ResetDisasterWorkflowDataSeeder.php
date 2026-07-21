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

            EvacuationCenter::query()->delete();
        });

        $this->command?->info('TCISS, household workflow, payout, and evacuation-center data have been reset.');
    }
}
