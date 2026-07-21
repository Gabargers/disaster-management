<?php

namespace Database\Seeders\Disaster;

use App\Enums\FamilyStatus;
use App\Models\Auth\User;
use App\Models\Cms\Barangay;
use App\Models\Disaster\AffectedFamily;
use App\Models\Disaster\Disaster;
use App\Models\Disaster\EvacuationCenter;
use App\Models\Disaster\TcissMasterlistRecord;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TcissHouseholdSampleSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $latestCenter = EvacuationCenter::with(['barangay', 'disaster'])
                ->where('is_active', true)
                ->where('status', 'ACTIVE')
                ->latest('id')
                ->first();
            $barangay = $latestCenter?->barangay ?? Barangay::where('name', 'Western Bicutan')->firstOrFail();
            $disaster = $latestCenter?->disaster ?? Disaster::where('name', 'July 2026 Flooding')->firstOrFail();
            $user = User::where('email', 'superadmin@gmail.com')->first();

            $existingTciss = TcissMasterlistRecord::where('source_reference', 'TCISS-2026-0001')->first();
            $existingFamily = $existingTciss?->affectedFamily;

            $family = AffectedFamily::updateOrCreate(
                $existingFamily ? ['id' => $existingFamily->id] : [
                    'disaster_id' => $disaster->id,
                    'barangay_id' => $barangay->id,
                    'household_head_surname' => 'Garcia',
                    'household_head_given_name' => 'Andrea',
                    'birthdate' => '1988-05-14',
                ],
                [
                    'disaster_id' => $disaster->id,
                    'barangay_id' => $barangay->id,
                    'evacuation_center_id' => null,
                    'household_head_middle_name' => 'Reyes',
                    'occupation' => 'Market Vendor',
                    'monthly_income' => 12000,
                    'contact_number' => '09171234567',
                    'complete_address' => '25 Sampaguita Street, Western Bicutan, Taguig City',
                    'house_ownership' => 'Renter',
                    'housing_condition' => 'Water Damage',
                    'health_condition' => null,
                    'status' => $existingFamily?->status ?? FamilyStatus::DRAFT,
                    'created_by' => $user?->id,
                    'updated_by' => $user?->id,
                ]
            );

            $family->familyMembers()->updateOrCreate(
                ['name' => 'Miguel Garcia'],
                [
                    'birthdate' => '1986-09-20',
                    'relationship_to_head' => 'Spouse',
                    'sex' => 'Male',
                    'occupation' => 'Delivery Rider',
                    'monthly_income' => 10000,
                ]
            );

            $family->familyMembers()->updateOrCreate(
                ['name' => 'Sofia Garcia'],
                [
                    'birthdate' => '2016-03-08',
                    'relationship_to_head' => 'Daughter',
                    'sex' => 'Female',
                    'occupation' => 'Student',
                    'monthly_income' => 0,
                    'remarks_codes' => 'F',
                ]
            );

            TcissMasterlistRecord::updateOrCreate(
                ['affected_family_id' => $family->id],
                [
                    'dafac_record_id' => null,
                    'barangay_id' => $barangay->id,
                    'evacuation_center_id' => null,
                    'household_head_full_name' => $family->household_head_full_name,
                    'birthdate' => $family->birthdate,
                    'address' => $family->complete_address,
                    'source_reference' => 'TCISS-2026-0001',
                    'source' => 'MANUAL_TCISS',
                    'verification_status' => $existingTciss?->verification_status ?? 'Needs Review',
                    'verified_by' => $existingTciss?->verified_by,
                    'verified_at' => $existingTciss?->verified_at,
                ]
            );

            $family->workflowHistories()->firstOrCreate(
                ['action' => 'tciss_sample_created'],
                [
                    'from_status' => null,
                    'to_status' => FamilyStatus::DRAFT->value,
                    'remarks' => 'Single TCISS household for manual end-to-end testing.',
                    'performed_by' => $user?->id,
                    'performed_at' => now(),
                ]
            );

            $this->command?->info("TCISS-2026-0001 is ready for assignment in {$barangay->name} / {$disaster->name}.");
        });
    }
}
