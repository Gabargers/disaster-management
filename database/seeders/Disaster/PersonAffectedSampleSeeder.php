<?php

namespace Database\Seeders\Disaster;

use App\Models\Integration\PersonAffected;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PersonAffectedSampleSeeder extends Seeder
{
    public function run(): void
    {
        $family = [
            ['1011200170A7', 'DRIO, CHRISELDA CRUZ', '1983-02-14', 43, 'Female', 'Daughter'],
            ['1011200170A6', 'DRIO, MARIA REHINA RICA CRUZ', '1986-06-23', 40, 'Female', 'Daughter'],
            ['1011200170A5', 'DRIO, MA. CRISTINA CRUZ', '1989-10-08', 36, 'Female', 'Daughter'],
            ['1011200170A4', 'DRIO, MARIA ANTONETTE CRUZ', '1992-04-17', 34, 'Female', 'Daughter'],
            ['1011200170A3', 'DRIO, NATASHA JASMIN CRUZ', '1996-09-11', 29, 'Female', 'Daughter'],
            ['1011200170A2', 'DRIO, ARMIDA CRUZ', '1961-12-05', 64, 'Female', 'Spouse'],
            ['1011200170A1', 'DRIO, CHRISTOPHER MALAPITAN', '1958-03-19', 68, 'Male', 'Family Head'],
        ];

        DB::transaction(function () use ($family) {
            foreach ($family as [$controlNumber, $name, $birthdate, $age, $sex, $relationship]) {
                $person = PersonAffected::updateOrCreate(
                    ['control_number' => $controlNumber],
                    [
                        'full_name' => $name,
                        'birthdate' => $birthdate,
                        'age' => $age,
                        'sex' => $sex,
                        'code' => null,
                        'occupation' => $relationship === 'Family Head' ? 'Driver' : null,
                        'monthly_income' => null,
                        'health_condition' => null,
                        'district' => 'District 2',
                        'barangay' => 'BAGUMBAYAN',
                        'street' => 'ROCKYSIDE VILLAGE',
                        'city' => 'TAGUIG CITY',
                        'family_head_name' => 'DRIO, CHRISTOPHER MALAPITAN',
                        'family_head_control_number' => '1011200170A1',
                        'relationship' => $relationship,
                        'housing' => 'Owner',
                    ]
                );

                $person->statuses()->updateOrCreate(
                    ['date_tagged' => '2026-08-19 08:00:00.000000'],
                    ['status' => 'affected']
                );

                $memberControlNumbers = collect($family)->pluck(0)->reject(fn ($number) => $number === $controlNumber);
                $person->familyMembers()->whereNotIn('control_number', $memberControlNumbers)->delete();
                foreach ($family as [$memberControlNumber, $memberName, , $memberAge, $memberSex, $memberRelationship]) {
                    if ($memberControlNumber === $controlNumber) {
                        continue;
                    }

                    $person->familyMembers()->updateOrCreate(
                        ['control_number' => $memberControlNumber],
                        [
                            'full_name' => $memberName,
                            'relationship' => $memberRelationship,
                            'age' => $memberAge,
                            'sex' => $memberSex,
                            'code' => null,
                            'housing' => 'Owner',
                        ]
                    );
                }
            }

        });

        $this->command?->info('Drio A1-A7 Family Affected records are ready.');
    }
}
