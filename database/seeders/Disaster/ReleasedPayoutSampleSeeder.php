<?php

namespace Database\Seeders\Disaster;

use App\Enums\FamilyStatus;
use App\Models\Auth\User;
use App\Models\Cms\Barangay;
use App\Models\Disaster\AffectedFamily;
use App\Models\Disaster\DafacRecord;
use App\Models\Disaster\Disaster;
use App\Models\Disaster\EvacuationCenter;
use App\Models\Disaster\FamilyMember;
use App\Models\Disaster\PayoutRelease;
use App\Models\Disaster\PayoutSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ReleasedPayoutSampleSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'superadmin@gmail.com')->firstOrFail();
        $barangay = Barangay::firstOrCreate(['name' => 'Bagumbayan'], ['code' => 'TG-BAG', 'district' => 'Taguig', 'is_active' => true]);
        $disaster = Disaster::firstOrCreate(['name' => 'Released Payout UI Demo'], ['type' => 'Flood', 'incident_date' => now()->subDays(10)->toDateString(), 'description' => 'Sample records for testing the Payroll released-payout modal.', 'is_active' => true]);
        $center = EvacuationCenter::firstOrCreate(['barangay_id' => $barangay->id, 'name' => 'Bagumbayan Multi-Purpose Hall'], ['disaster_id' => $disaster->id, 'address' => 'Bagumbayan, Taguig City', 'capacity' => 250, 'status' => 'ACTIVE', 'payout_availability' => 'COMPLETED', 'is_active' => true, 'created_by' => $user->id, 'updated_by' => $user->id]);
        if (!$center->disaster_id) $center->update(['disaster_id' => $disaster->id]);

        $schedule = PayoutSchedule::firstOrCreate(['disaster_id' => $disaster->id, 'title' => 'Released Payout UI Demo'], ['scheduled_date' => now()->subDays(2)->toDateString(), 'venue' => $center->name, 'notes' => 'Sample released payouts.', 'created_by' => $user->id]);
        $photoPath = 'payout-photos/released-payout-sample.webp';
        Storage::disk('local')->put($photoPath, file_get_contents(public_path('images/default.webp')));

        $samples = [
            ['Maria', 'Santos', 'Owner', 'Totally Damaged', 'With Illness', 10000, 'Emergency Cash Assistance'],
            ['Roberto', 'Dela Cruz', 'Renter', 'Partially Damaged', 'Injured', 8000, 'Financial Assistance'],
            ['Liza', 'Mendoza', 'Sharer', 'Water Damage', 'Missing', 5000, 'Emergency Relief Grant'],
        ];

        foreach ($samples as $index => [$given, $surname, $ownership, $housing, $health, $amount, $assistance]) {
            $family = AffectedFamily::updateOrCreate(
                ['disaster_id' => $disaster->id, 'barangay_id' => $barangay->id, 'household_head_surname' => $surname, 'household_head_given_name' => $given],
                ['evacuation_center_id' => $center->id, 'birthdate' => now()->subYears(35 + $index)->toDateString(), 'contact_number' => '0917000000'.($index + 1), 'complete_address' => (21 + $index).' Demo Street, Bagumbayan, Taguig City', 'house_ownership' => $ownership, 'housing_condition' => $housing, 'health_condition' => $health, 'status' => FamilyStatus::REQUIREMENTS_PENDING, 'created_by' => $user->id, 'updated_by' => $user->id]
            );
            $dafac = DafacRecord::updateOrCreate(['affected_family_id' => $family->id], ['reference_number' => 'DAFAC-DEMO-'.str_pad((string)($index + 1), 4, '0', STR_PAD_LEFT), 'interview_date' => now()->subDays(8)->toDateString(), 'interviewed_by' => $user->id, 'interviewed_by_name' => $user->name, 'attestation_confirmed' => true]);
            FamilyMember::updateOrCreate(['affected_family_id' => $family->id, 'name' => 'Sample Member '.($index + 1).' '.$surname], ['birthdate' => now()->subYears(12 + $index)->toDateString(), 'relationship_to_head' => 'Child', 'sex' => $index % 2 ? 'Male' : 'Female']);
            PayoutRelease::updateOrCreate(['payout_schedule_id' => $schedule->id, 'affected_family_id' => $family->id], ['evacuation_center_id' => $center->id, 'status' => 'Released', 'assistance_kind' => $assistance, 'quantity' => 1, 'amount' => $amount, 'provider' => 'Taguig City Social Welfare and Development Office', 'release_photo_path' => $photoPath, 'payout_photo_path' => $photoPath, 'payout_photo_original_name' => 'released-payout-sample.webp', 'payout_photo_mime_type' => 'image/webp', 'payout_photo_size' => Storage::disk('local')->size($photoPath), 'payout_photo_uploaded_at' => now()->subDays(2), 'photo_caption' => 'Sample payout release proof for UI testing.', 'photo_taken_at' => now()->subDays(2), 'photo_uploaded_by' => $user->id, 'released_by' => $user->id, 'released_at' => now()->subDays(2)->addHours($index), 'idempotency_key' => '10000000-0000-4000-8000-'.str_pad((string)($index + 1), 12, '0', STR_PAD_LEFT)]);
        }

        $this->command?->info('Created 3 released payout samples for the Payroll modal.');
    }
}
