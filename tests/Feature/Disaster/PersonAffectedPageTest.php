<?php

namespace Tests\Feature\Disaster;

use App\Models\Auth\User;
use App\Models\Integration\PersonAffected;
use App\Models\Cms\Barangay;
use App\Models\Disaster\Disaster;
use App\Models\Disaster\EvacuationCenter;
use Database\Seeders\Disaster\DisasterRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonAffectedPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_tciss_user_can_view_api_person_affected_records(): void
    {
        $this->seed(DisasterRoleSeeder::class);
        $user = User::where('email', 'coordinator@gmail.com')->firstOrFail();
        $person = PersonAffected::create([
            'control_number' => 'TCISS-CN-10001', 'full_name' => 'Juan Dela Cruz',
            'birthdate' => '1990-05-15', 'age' => 36, 'sex' => 'Male', 'code' => 'PWD',
            'occupation' => 'Carpenter', 'monthly_income' => 'PHP 15,000 monthly', 'health_condition' => 'Asthma',
            'district' => 'District 1', 'barangay' => 'Central Signal', 'street' => 'Rizal Street',
            'city' => 'Taguig City', 'family_head_name' => 'Maria Dela Cruz',
            'family_head_control_number' => 'FH-10001', 'relationship' => 'Spouse', 'housing' => 'Owner',
        ]);
        $person->statuses()->create(['status' => 'affected', 'date_tagged' => '2026-07-22 08:30:00.000000']);

        $this->actingAs($user)
            ->get(route('disaster.person-affecteds.index'))
            ->assertOk()
            ->assertSee('Person Affected')
            ->assertSee('TCISS-CN-10001')
            ->assertSee('Juan Dela Cruz')
            ->assertSee('Central Signal')
            ->assertSee('Rizal Street')
            ->assertSee('Affected');

        $this->actingAs($user)
            ->getJson(route('disaster.person-affecteds.show', $person))
            ->assertOk()
            ->assertJsonPath('data.control_number', 'TCISS-CN-10001')
            ->assertJsonPath('data.occupation', 'Carpenter')
            ->assertJsonPath('data.health_condition', 'Asthma')
            ->assertJsonPath('data.family_head_name', 'Maria Dela Cruz')
            ->assertJsonPath('data.family_head_control_number', 'FH-10001')
            ->assertJsonPath('data.relationship', 'Spouse')
            ->assertJsonPath('data.housing', 'Owner');
    }

    public function test_person_affected_page_requires_tciss_permission(): void
    {
        $this->seed(DisasterRoleSeeder::class);
        $user = User::where('email', 'payroll@gmail.com')->firstOrFail();

        $this->actingAs($user)->get(route('disaster.person-affecteds.index'))->assertForbidden();
    }

    public function test_person_can_only_be_assigned_after_an_active_evacuation_center_exists(): void
    {
        $this->seed(DisasterRoleSeeder::class);
        $user = User::where('email', 'coordinator@gmail.com')->firstOrFail();
        $person = PersonAffected::create(['control_number' => 'CN-ASSIGN-1001', 'full_name' => 'Assignment Test']);

        $this->actingAs($user)->getJson(route('disaster.person-affecteds.show', $person))
            ->assertOk()
            ->assertJsonPath('data.evacuation_center_assignment.has_centers', false)
            ->assertJsonPath('data.evacuation_center_assignment.can_assign', false);

        $barangay = Barangay::create(['name' => 'Assignment Barangay', 'code' => 'AB-01', 'district' => 'District 1', 'is_active' => true]);
        $disaster = Disaster::create(['name' => 'Assignment Disaster', 'type' => 'Flood', 'incident_date' => today(), 'is_active' => true]);
        $center = EvacuationCenter::create([
            'name' => 'Assignment Center', 'barangay_id' => $barangay->id, 'disaster_id' => $disaster->id,
            'address' => 'Center Address', 'capacity' => 100, 'status' => 'ACTIVE',
            'payout_availability' => 'NOT_AVAILABLE', 'is_active' => true,
        ]);

        $this->actingAs($user)->getJson(route('disaster.person-affecteds.show', $person))
            ->assertOk()
            ->assertJsonPath('data.evacuation_center_assignment.has_centers', true)
            ->assertJsonPath('data.evacuation_center_assignment.can_assign', true)
            ->assertJsonPath('data.evacuation_center_assignment.centers.0.name', 'Assignment Center');

        $this->actingAs($user)->postJson(route('disaster.person-affecteds.assign-evacuation-center', $person), [
            'evacuation_center_id' => $center->id,
        ])->assertOk()->assertJsonPath('data.center.name', 'Assignment Center');

        $this->assertDatabaseHas('person_affecteds', [
            'id' => $person->id, 'evacuation_center_id' => $center->id,
            'evacuation_center_assigned_by' => $user->id,
        ]);

        $dashboard = $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertSee('Assigned Evacuees')
            ->assertSee('1 active evacuation centers');
        $this->assertSame(1, $dashboard->viewData('metrics')['ASSIGNED_EVACUEES']);
        $this->assertSame(1, $dashboard->viewData('metrics')['ACTIVE_EVACUATION_CENTERS']);
    }
}
