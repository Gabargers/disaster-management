<?php

namespace Database\Seeders\Disaster;

use App\Models\Disaster\AffectedFamily;
use App\Models\Disaster\AuditLog;
use App\Models\Disaster\Disaster;
use App\Models\Disaster\EvacuationCenter;
use App\Models\Disaster\EvacuationCenterPayoutSession;
use App\Models\Disaster\PayoutSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClearDisasterSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $familyIds = DB::table('workflow_histories')
                ->whereIn('action', ['demo_workflow_seeded', 'connected_sample_seeded'])
                ->pluck('affected_family_id');

            // These records are all linked by foreign keys; deleting the central
            // household removes its DAFAC, TCISS, members, assignments, payouts,
            // workflow, payroll, validation, and requirements records.
            AffectedFamily::whereKey($familyIds)->delete();

            $sampleDisasterNames = [
                'Residential Fire - Barangay Central Signal',
                'Flooding - Barangay Ususan',
                'Typhoon Evacuation',
                'Taguig Connected Data Demonstration',
            ];
            $disasterIds = Disaster::whereIn('name', $sampleDisasterNames)->pluck('id');

            // Clean independent sample records that do not cascade from families.
            EvacuationCenterPayoutSession::whereIn('disaster_id', $disasterIds)->delete();
            PayoutSchedule::whereIn('disaster_id', $disasterIds)->delete();
            AuditLog::where('action', 'connected_sample_seeded')->delete();

            EvacuationCenter::whereIn('disaster_id', $disasterIds)
                ->whereDoesntHave('assignments')
                ->whereDoesntHave('affectedFamilies')
                ->whereDoesntHave('payoutReleases')
                ->delete();

            Disaster::whereIn('id', $disasterIds)
                ->whereDoesntHave('affectedFamilies')
                ->whereDoesntHave('payoutSchedules')
                ->delete();
        });
    }
}
