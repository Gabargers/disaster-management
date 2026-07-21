<?php

namespace Database\Seeders\Disaster;

use App\Models\Disaster\AffectedFamily;
use App\Models\Disaster\AuditLog;
use App\Models\Disaster\Disaster;
use App\Models\Disaster\EvacuationCenter;
use App\Models\Disaster\EvacuationCenterPayoutSession;
use App\Models\Disaster\PayoutSchedule;
use App\Models\Disaster\TcissMasterlistRecord;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClearDisasterSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $markedFamilyIds = DB::table('workflow_histories')
                ->whereIn('action', ['demo_workflow_seeded', 'connected_sample_seeded'])
                ->pluck('affected_family_id');

            // Older demo runs may have had their marker replaced by a workflow
            // backfill. Match only the deterministic demo household signatures.
            $knownDemoFamilyIds = AffectedFamily::query()
                ->where(function ($query) {
                    $query->where(fn ($query) => $query->where('household_head_given_name', 'Juan')->where('household_head_surname', 'Dela Cruz'))
                        ->orWhere(fn ($query) => $query->where('household_head_given_name', 'Lorna')->where('household_head_surname', 'Mendoza'))
                        ->orWhere(fn ($query) => $query->where('household_head_given_name', 'Joel')->where('household_head_surname', 'Ramos'));
                })
                ->whereHas('disaster', fn ($query) => $query->whereIn('name', [
                    'Residential Fire - Barangay Central Signal',
                    'Flooding - Barangay Ususan',
                    'Typhoon Evacuation',
                ]))
                ->pluck('id');

            $releasedPayoutDemoIds = AffectedFamily::whereHas(
                'disaster',
                fn ($query) => $query->where('name', 'Released Payout UI Demo')
            )->pluck('id');

            $familyIds = $markedFamilyIds
                ->merge($knownDemoFamilyIds)
                ->merge($releasedPayoutDemoIds)
                ->unique()
                ->values();

            // TCISS uses a nullable foreign key, so these rows do not cascade
            // when their sample household is deleted.
            TcissMasterlistRecord::whereIn('affected_family_id', $familyIds)
                ->orWhere('source_reference', 'like', 'TCISS-%-SEED-%')
                ->delete();

            // These records are all linked by foreign keys; deleting the central
            // household removes its DAFAC, TCISS, members, assignments, payouts,
            // workflow, payroll, validation, and requirements records.
            AffectedFamily::whereKey($familyIds)->delete();

            $sampleDisasterNames = [
                'Residential Fire - Barangay Central Signal',
                'Flooding - Barangay Ususan',
                'Typhoon Evacuation',
                'Taguig Connected Data Demonstration',
                'Released Payout UI Demo',
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
