<?php

namespace Tests\Feature\Disaster;

use App\Models\Auth\User;
use App\Models\Disaster\AuditLog;
use App\Models\Disaster\Disaster;
use App\Models\Disaster\EvacuationCenter;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DafacIntakeTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->staff = User::where('email', 'coordinator@gmail.com')->firstOrFail();
    }

    public function test_complete_intake_and_family_members_are_saved_atomically(): void
    {
        $response = $this->actingAs($this->staff)->postJson(route('disaster.dafac.store'), $this->validData());
        $response->assertCreated()->assertJsonPath('success', true)->assertJsonPath('data.status', 'DUPLICATE_CHECK_PENDING');
        $familyId = $response->json('data.affected_family_id');
        $this->assertDatabaseHas('affected_families', ['id' => $familyId, 'household_head_surname' => 'Villanueva', 'created_by' => $this->staff->id]);
        $this->assertSame(3, \App\Models\Disaster\FamilyMember::where('affected_family_id', $familyId)->count());
        $this->assertDatabaseHas('dafac_records', ['affected_family_id' => $familyId, 'attestation_confirmed' => true]);
        $this->assertDatabaseHas('tciss_masterlist_records', ['affected_family_id'=>$familyId,'dafac_record_id'=>$response->json('data.dafac_id'),'source'=>'DAFAC_INTAKE']);
        $this->assertDatabaseHas('evacuation_center_assignments', ['affected_family_id'=>$familyId,'evacuation_center_id'=>$this->validData()['evacuation_center_id'],'status'=>'ACTIVE']);
        $this->assertTrue(AuditLog::where('auditable_id', $response->json('data.id'))->where('action', 'dafac_intake_created')->exists());
    }

    public function test_duplicate_submission_creates_only_one_intake(): void
    {
        $before=\App\Models\Disaster\DafacRecord::count();
        $this->actingAs($this->staff)->postJson(route('disaster.dafac.store'), $this->validData())->assertCreated();
        $this->actingAs($this->staff)->postJson(route('disaster.dafac.store'), $this->validData())->assertConflict();
        $this->assertSame($before+1,\App\Models\Disaster\DafacRecord::count());
    }

    public function test_nested_validation_errors_are_returned_and_nothing_is_saved(): void
    {
        $before = \App\Models\Disaster\DafacRecord::count();
        $data = $this->validData(); $data['family_members'][0]['birthdate'] = now()->addDay()->format('Y-m-d');
        $this->actingAs($this->staff)->postJson(route('disaster.dafac.store'), $data)
            ->assertUnprocessable()->assertJsonValidationErrors('family_members.0.birthdate');
        $this->assertSame($before, \App\Models\Disaster\DafacRecord::count());
    }

    public function test_user_without_permission_cannot_create_intake(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user)->postJson(route('disaster.dafac.store'), $this->validData())->assertForbidden();
    }

    private function validData(): array
    {
        $center = EvacuationCenter::firstOrFail();
        return [
            'disaster_id' => $center->disaster_id ?: Disaster::firstOrFail()->id, 'barangay_id' => $center->barangay_id,
            'evacuation_center_id' => $center->id, 'intake_date' => now()->format('Y-m-d'),
            'household_head' => ['surname' => 'Villanueva', 'given_name' => 'Elena', 'middle_name' => 'Reyes', 'complete_address' => '999 Integration Test Street', 'birthdate' => '1985-04-18', 'occupation' => 'Vendor', 'monthly_income' => 15000, 'contact_number' => '09171234567'],
            'house_ownership' => 'Renter', 'housing_condition' => 'Totally Damaged', 'health_condition' => null,
            'interviewed_by' => 'Maria Reyes', 'attestation_confirmed' => true,
            'family_members' => [
                ['full_name' => 'Ana Villanueva', 'birthdate' => '1987-09-22', 'relationship_to_head' => 'Spouse', 'sex' => 'Female', 'occupation' => 'Vendor', 'monthly_income' => 8000],
                ['full_name' => 'Mark Villanueva', 'birthdate' => '2012-02-15', 'relationship_to_head' => 'Son', 'sex' => 'Male', 'occupation' => 'Student', 'monthly_income' => 0, 'remarks_code' => 'F'],
                ['full_name' => 'Angela Villanueva', 'birthdate' => '2025-11-05', 'relationship_to_head' => 'Daughter', 'sex' => 'Female', 'occupation' => 'None', 'monthly_income' => 0, 'remarks_code' => 'C'],
            ],
        ];
    }
}
