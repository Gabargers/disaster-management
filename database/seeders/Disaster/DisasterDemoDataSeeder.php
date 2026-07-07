<?php

namespace Database\Seeders\Disaster;

use App\Enums\FamilyStatus;
use App\Models\Cms\Barangay;
use App\Models\Disaster\AffectedFamily;
use App\Models\Disaster\DafacRecord;
use App\Models\Disaster\Disaster;
use App\Models\Disaster\EvacuationCenter;
use App\Models\Disaster\FamilyMember;
use App\Models\Disaster\PostPayoutRequirement;
use App\Models\Disaster\ValidationRecord;
use Illuminate\Database\Seeder;

class DisasterDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $barangays = collect(['Western Bicutan', 'Ususan', 'Tuktukan'])
            ->mapWithKeys(fn (string $name) => [
                $name => Barangay::firstOrCreate(
                    ['name' => $name],
                    ['district' => 'District 1', 'is_active' => true]
                ),
            ]);

        $disaster = Disaster::firstOrCreate(
            ['name' => 'July 2026 Flooding'],
            [
                'type' => 'Flood',
                'incident_date' => '2026-07-01',
                'description' => 'Demo incident for disaster assistance workflow testing.',
                'is_active' => true,
            ]
        );

        $centers = [
            'Western Bicutan' => 'City University Gym',
            'Ususan' => 'Ususan Covered Court',
            'Tuktukan' => 'Tuktukan Elementary School',
        ];

        $evacuationCenters = collect($centers)->mapWithKeys(fn (string $center, string $barangay) => [
            $barangay => EvacuationCenter::firstOrCreate(
                ['barangay_id' => $barangays[$barangay]->id, 'name' => $center],
                ['address' => $barangay, 'capacity' => 500, 'is_active' => true]
            ),
        ]);

        $families = [
            [
                'surname' => 'Santos',
                'given' => 'Maria',
                'middle' => 'Lopez',
                'birthdate' => '1985-05-12',
                'barangay' => 'Western Bicutan',
                'address' => 'Blk 12 Lot 8 Phase 2',
                'ownership' => 'Owner',
                'housing' => 'Partially Damaged',
                'status' => FamilyStatus::VALIDATED,
            ],
            [
                'surname' => 'Cruz',
                'given' => 'Roberto',
                'middle' => null,
                'birthdate' => '1978-11-20',
                'barangay' => 'Ususan',
                'address' => 'Purok 4 Riverside',
                'ownership' => 'Renter',
                'housing' => 'Water Damage',
                'status' => FamilyStatus::DUPLICATE_CHECKED,
            ],
            [
                'surname' => 'Reyes',
                'given' => 'Lorna',
                'middle' => 'Dela',
                'birthdate' => '1991-02-03',
                'barangay' => 'Tuktukan',
                'address' => 'Sampaguita Street',
                'ownership' => 'Sharer',
                'housing' => 'Totally Damaged',
                'status' => FamilyStatus::PAYROLL_READY,
            ],
        ];

        foreach ($families as $familyData) {
            $family = AffectedFamily::firstOrCreate(
                [
                    'barangay_id' => $barangays[$familyData['barangay']]->id,
                    'household_head_surname' => $familyData['surname'],
                    'household_head_given_name' => $familyData['given'],
                    'birthdate' => $familyData['birthdate'],
                ],
                [
                    'disaster_id' => $disaster->id,
                    'evacuation_center_id' => $evacuationCenters[$familyData['barangay']]->id,
                    'household_head_middle_name' => $familyData['middle'],
                    'occupation' => 'Vendor',
                    'monthly_income' => 12000,
                    'complete_address' => $familyData['address'],
                    'house_ownership' => $familyData['ownership'],
                    'housing_condition' => $familyData['housing'],
                    'health_condition' => 'With Illness',
                    'status' => $familyData['status'],
                ]
            );

            FamilyMember::firstOrCreate(
                ['affected_family_id' => $family->id, 'name' => $familyData['given'].' Jr.'],
                [
                    'birthdate' => '2014-08-15',
                    'relationship_to_head' => 'Child',
                    'sex' => 'Male',
                    'remarks_codes' => 'F',
                ]
            );

            DafacRecord::firstOrCreate(
                ['affected_family_id' => $family->id],
                ['interview_date' => '2026-07-02']
            );

            ValidationRecord::firstOrCreate(
                ['affected_family_id' => $family->id],
                [
                    'validated_house_ownership' => $familyData['ownership'],
                    'validated_housing_condition' => $familyData['housing'],
                    'notes' => 'Demo validation record.',
                    'status' => $familyData['status'] === FamilyStatus::VALIDATED ? 'Validated' : 'Pending Validation',
                ]
            );

            PostPayoutRequirement::firstOrCreate(['affected_family_id' => $family->id]);
        }
    }
}
