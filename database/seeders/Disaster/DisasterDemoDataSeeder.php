<?php

namespace Database\Seeders\Disaster;

use App\Enums\FamilyStatus;
use App\Models\Auth\User;
use App\Models\Cms\Barangay;
use App\Models\Disaster\AffectedFamily;
use App\Models\Disaster\AssistanceRecord;
use App\Models\Disaster\DafacRecord;
use App\Models\Disaster\Disaster;
use App\Models\Disaster\DuplicateCheck;
use App\Models\Disaster\EvacuationCenter;
use App\Models\Disaster\EvacuationCenterAssignment;
use App\Models\Disaster\EvacuationCenterPayoutSession;
use App\Models\Disaster\FamilyMember;
use App\Models\Disaster\TcissMasterlistRecord;
use App\Models\Disaster\ValidationRecord;
use App\Models\Disaster\WorkflowHistory;
use Illuminate\Database\Seeder;
use App\Models\Disaster\PayoutSchedule;
use App\Models\Disaster\PayoutRelease;
use Illuminate\Support\Facades\Storage;

class DisasterDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'superadmin@gmail.com')->first();
        $samples = [
            [
                'tciss' => 'TCISS-2026-0001', 'disaster' => 'Residential Fire - Barangay Central Signal', 'type' => 'Fire',
                'incident_date' => '2026-07-10', 'barangay' => 'Central Signal Village', 'center' => 'Central Signal Covered Court',
                'surname' => 'Dela Cruz', 'given' => 'Juan', 'middle' => 'Santos', 'birthdate' => '1985-04-18',
                'occupation' => 'Tricycle Driver', 'income' => 15000, 'address' => '123 Sampaguita Street, Central Signal Village, Taguig City',
                'ownership' => 'Renter', 'housing' => 'Totally Damaged', 'health' => null, 'status' => FamilyStatus::PAYOUT_SCHEDULED,
                'verification' => 'Verified', 'interview_date' => '2026-07-10', 'notes' => 'House was completely destroyed by fire.',
                'members' => [
                    ['name' => 'Ana Dela Cruz', 'birthdate' => '1987-09-22', 'relationship' => 'Spouse', 'sex' => 'Female', 'occupation' => 'Vendor', 'income' => 8000, 'health' => null, 'remarks' => null],
                    ['name' => 'Mark Dela Cruz', 'birthdate' => '2012-02-15', 'relationship' => 'Son', 'sex' => 'Male', 'occupation' => 'Student', 'income' => 0, 'health' => null, 'remarks' => 'F'],
                    ['name' => 'Angela Dela Cruz', 'birthdate' => '2025-11-05', 'relationship' => 'Daughter', 'sex' => 'Female', 'occupation' => 'None', 'income' => 0, 'health' => null, 'remarks' => 'C'],
                ],
            ],
            [
                'tciss' => 'TCISS-2026-0002', 'disaster' => 'Flooding - Barangay Ususan', 'type' => 'Flood',
                'incident_date' => '2026-07-10', 'barangay' => 'Ususan', 'center' => 'Ususan Elementary School',
                'surname' => 'Mendoza', 'given' => 'Lorna', 'middle' => 'Cruz', 'birthdate' => '1970-06-30',
                'occupation' => 'Laundry Worker', 'income' => 9000, 'address' => '45 Riverside Street, Ususan, Taguig City',
                'ownership' => 'Owner', 'housing' => 'Water Damage', 'health' => null, 'status' => FamilyStatus::POSSIBLE_DUPLICATE,
                'verification' => 'Verified', 'interview_date' => '2026-07-10', 'notes' => null,
                'members' => [
                    ['name' => 'Carlo Mendoza', 'birthdate' => '2000-01-10', 'relationship' => 'Son', 'sex' => 'Male', 'occupation' => 'Delivery Rider', 'income' => 12000, 'health' => 'Injured', 'remarks' => null],
                    ['name' => 'Rosa Mendoza', 'birthdate' => '1945-08-08', 'relationship' => 'Mother', 'sex' => 'Female', 'occupation' => 'None', 'income' => 0, 'health' => 'With Illness', 'remarks' => 'A'],
                ],
            ],
            [
                'tciss' => 'TCISS-2026-0003', 'disaster' => 'Typhoon Evacuation', 'type' => 'Typhoon',
                'incident_date' => '2026-07-10', 'barangay' => 'Bagumbayan', 'center' => 'Bagumbayan Multi-Purpose Hall',
                'surname' => 'Ramos', 'given' => 'Joel', 'middle' => 'Aquino', 'birthdate' => '1992-12-03',
                'occupation' => 'Construction Worker', 'income' => 13000, 'address' => '77 Maharlika Street, Bagumbayan, Taguig City',
                'ownership' => 'Sharer', 'housing' => 'Partially Damaged', 'health' => null, 'status' => FamilyStatus::PAYROLL_READY,
                'verification' => 'Verified', 'interview_date' => '2026-07-10', 'notes' => null, 'members' => [],
            ],
        ];

        $created = [];
        foreach ($samples as $sampleIndex => $sample) {
            $barangay = Barangay::firstOrCreate(['name' => $sample['barangay']], ['district' => 'District 1', 'is_active' => true]);
            $disaster = Disaster::firstOrCreate(['name' => $sample['disaster']], ['type' => $sample['type'], 'incident_date' => $sample['incident_date'], 'is_active' => true]);
            $center = EvacuationCenter::firstOrCreate(['barangay_id' => $barangay->id, 'name' => $sample['center']], ['address' => $sample['barangay'], 'capacity' => 500, 'is_active' => true]);
            $family = AffectedFamily::updateOrCreate(
                ['barangay_id' => $barangay->id, 'household_head_surname' => $sample['surname'], 'household_head_given_name' => $sample['given'], 'birthdate' => $sample['birthdate']],
                ['disaster_id' => $disaster->id, 'evacuation_center_id' => $center->id, 'household_head_middle_name' => $sample['middle'], 'occupation' => $sample['occupation'], 'monthly_income' => $sample['income'], 'complete_address' => $sample['address'], 'house_ownership' => $sample['ownership'], 'housing_condition' => $sample['housing'], 'health_condition' => $sample['health'], 'status' => $sample['status'], 'created_by' => $user?->id, 'updated_by' => $user?->id]
            );
            foreach ($sample['members'] as $member) {
                FamilyMember::updateOrCreate(['affected_family_id' => $family->id, 'name' => $member['name']], ['birthdate' => $member['birthdate'], 'relationship_to_head' => $member['relationship'], 'sex' => $member['sex'], 'occupation' => $member['occupation'], 'monthly_income' => $member['income'], 'health_condition' => $member['health'], 'remarks_codes' => $member['remarks']]);
            }
            DafacRecord::updateOrCreate(['affected_family_id' => $family->id], ['reference_number' => sprintf('DAFAC-2026-%04d', $sampleIndex + 1), 'interview_date' => $sample['interview_date'], 'interviewed_by' => $user?->id, 'validated_by' => $sample['notes'] ? $user?->id : null]);
            WorkflowHistory::firstOrCreate(['affected_family_id' => $family->id, 'action' => 'demo_workflow_seeded'], ['from_status' => null, 'to_status' => $family->status->value, 'remarks' => 'Connected demonstration workflow record.', 'performed_by' => $user?->id, 'performed_at' => now()]);
            ValidationRecord::updateOrCreate(['affected_family_id' => $family->id], ['validated_house_ownership' => $sample['ownership'], 'validated_housing_condition' => $sample['housing'], 'notes' => $sample['notes'], 'status' => $sample['notes'] ? 'Validated' : 'Pending Validation', 'validated_by' => $sample['notes'] ? $user?->id : null, 'validated_at' => $sample['notes'] ? '2026-07-11 09:00:00' : null]);
            TcissMasterlistRecord::updateOrCreate(['source_reference' => $sample['tciss']], ['affected_family_id' => $family->id, 'barangay_id' => $barangay->id, 'evacuation_center_id' => $center->id, 'household_head_full_name' => $family->household_head_full_name, 'birthdate' => $sample['birthdate'], 'address' => $sample['address'], 'verification_status' => $sample['verification'], 'verified_by' => $user?->id, 'verified_at' => '2026-07-11 08:00:00']);
            $created[] = $family;
        }

        DuplicateCheck::updateOrCreate(['affected_family_id' => $created[1]->id], ['possible_duplicate_family_id' => $created[0]->id, 'match_score' => 88, 'matched_fields' => ['Similar household head name', 'Same birthdate', 'Similar address'], 'resolution' => 'Pending']);
        AssistanceRecord::updateOrCreate(['affected_family_id' => $created[0]->id, 'assistance_kind' => 'Emergency Cash Assistance'], ['date_assistance_provided' => '2026-07-12', 'quantity_amount' => 10000, 'provider' => 'City Social Welfare and Development Office', 'released_by' => $user?->id]);

        $center = EvacuationCenter::where('name', 'Central Signal Covered Court')->firstOrFail();
        $center->update(['disaster_id' => $created[0]->disaster_id, 'address' => 'Central Signal Village, Taguig City', 'contact_person' => 'Maria Reyes', 'contact_number' => '09171234567', 'capacity' => 100, 'status' => 'ACTIVE', 'payout_availability' => 'AVAILABLE', 'is_active' => true, 'created_by' => $user?->id, 'updated_by' => $user?->id]);
        foreach ($created as $family) {
            EvacuationCenterAssignment::firstOrCreate(['evacuation_center_id' => $center->id, 'affected_family_id' => $family->id, 'disaster_id' => $center->disaster_id, 'status' => 'ACTIVE'], ['assigned_by' => $user?->id, 'assigned_at' => now()]);
        }
        $session = EvacuationCenterPayoutSession::firstOrCreate(['evacuation_center_id' => $center->id, 'payout_date' => '2026-07-15'], ['disaster_id' => $center->disaster_id, 'start_time' => '09:00', 'end_time' => '16:00', 'payout_area' => 'Main Covered Court', 'assigned_officer_id' => $user?->id, 'assistance_type' => 'Emergency Cash Assistance', 'default_amount' => 10000, 'provider' => 'City Social Welfare and Development Office', 'status' => 'OPEN', 'created_by' => $user?->id]);
        $schedule = PayoutSchedule::firstOrCreate(['disaster_id' => $center->disaster_id, 'title' => 'Central Signal Covered Court Payout'], ['scheduled_date' => '2026-07-15', 'venue' => 'Main Covered Court', 'created_by' => $user?->id]);
        $photoPath = 'payout-photos/demo-ready.jpg';
        if (! Storage::disk('local')->exists($photoPath)) Storage::disk('local')->put($photoPath, base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABBQJ//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPwF//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPwF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQAGPwJ//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPyF//9oADAMBAAIAAwAAABAf/8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPxB//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPxB//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxB//9k='));
        foreach ($created as $index => $family) {
            PayoutRelease::updateOrCreate(['payout_session_id' => $session->id, 'affected_family_id' => $family->id], ['payout_schedule_id' => $schedule->id, 'evacuation_center_id' => $center->id, 'assistance_kind' => 'Emergency Cash Assistance', 'quantity' => 1, 'amount' => 10000, 'provider' => 'City Social Welfare and Development Office', 'status' => ['Scheduled', 'Scheduled', 'Released'][$index], 'payout_photo_path' => $index > 0 ? $photoPath : null, 'release_photo_path' => $index > 0 ? $photoPath : null, 'released_by' => $index === 2 ? $user?->id : null, 'released_at' => $index === 2 ? '2026-07-15 10:30:00' : null, 'photo_uploaded_by' => $index > 0 ? $user?->id : null, 'photo_taken_at' => $index > 0 ? '2026-07-15 10:00:00' : null]);
        }
    }
}
