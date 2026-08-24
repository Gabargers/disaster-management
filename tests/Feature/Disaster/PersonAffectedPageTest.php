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
            'family_head_control_number' => 'TCISS-CN-10001', 'relationship' => 'Family Head', 'housing' => 'Owner',
        ]);
        $person->statuses()->create(['status' => 'affected', 'date_tagged' => '2026-07-22 08:30:00.000000']);

        $this->actingAs($user)
            ->get(route('disaster.person-affecteds.index'))
            ->assertOk()
            ->assertSee('Family Affected')
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
            ->assertJsonPath('data.family_head_control_number', 'TCISS-CN-10001')
            ->assertJsonPath('data.relationship', 'Family Head')
            ->assertJsonPath('data.housing', 'Owner');
    }

    public function test_person_affected_page_requires_tciss_permission(): void
    {
        $this->seed(DisasterRoleSeeder::class);
        $user = User::where('email', 'payroll@gmail.com')->firstOrFail();

        $this->actingAs($user)->get(route('disaster.person-affecteds.index'))->assertForbidden();
    }

    public function test_family_members_are_hidden_from_the_list_but_can_find_the_family_head(): void
    {
        $this->seed(DisasterRoleSeeder::class);
        $user = User::where('email', 'coordinator@gmail.com')->firstOrFail();
        $head = PersonAffected::create([
            'control_number' => 'FAMILY-A1', 'full_name' => 'JUAN FAMILY HEAD',
            'family_head_name' => 'JUAN FAMILY HEAD', 'family_head_control_number' => 'FAMILY-A1',
            'relationship' => 'Family Head',
        ]);
        $member = PersonAffected::create([
            'control_number' => 'FAMILY-A2', 'full_name' => 'MARIA FAMILY MEMBER',
            'family_head_name' => 'JUAN FAMILY HEAD', 'family_head_control_number' => 'FAMILY-A1',
            'relationship' => 'Daughter',
        ]);
        $head->familyMembers()->create([
            'control_number' => 'FAMILY-A2', 'full_name' => 'MARIA FAMILY MEMBER',
            'relationship' => 'Daughter',
        ]);

        $this->actingAs($user)->get(route('disaster.person-affecteds.index'))
            ->assertOk()
            ->assertSee('FAMILY-A1')
            ->assertDontSee('FAMILY-A2');

        $this->actingAs($user)->get(route('disaster.person-affecteds.index', ['search' => 'MARIA FAMILY MEMBER']))
            ->assertOk()
            ->assertSee('FAMILY-A2')
            ->assertSee('MARIA FAMILY MEMBER')
            ->assertSee('JUAN FAMILY HEAD')
            ->assertSee('Family head:');

        $this->actingAs($user)->getJson(route('disaster.person-affecteds.show', $head))
            ->assertOk()
            ->assertJsonPath('data.family_members.0.control_number', 'FAMILY-A2');

        $this->actingAs($user)->getJson(route('disaster.person-affecteds.show', [
            $head, 'member_control_number' => 'FAMILY-A2',
        ]))->assertOk()
            ->assertJsonPath('data.control_number', 'FAMILY-A1')
            ->assertJsonPath('data.full_name', 'JUAN FAMILY HEAD')
            ->assertJsonCount(1, 'data.family_members')
            ->assertJsonPath('data.family_members.0.control_number', 'FAMILY-A2');
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
        ])->assertOk()
            ->assertJsonPath('data.center.name', 'Assignment Center')
            ->assertJsonPath('data.center.families_count', 1)
            ->assertJsonPath('data.assigned_families_count', 1);

        $this->assertDatabaseHas('person_affecteds', [
            'id' => $person->id, 'evacuation_center_id' => $center->id,
            'evacuation_center_assigned_by' => $user->id,
        ]);

        $user->givePermissionTo('manage payout schedules');
        $centerList = $this->actingAs($user)->get(route('disaster.payouts.index'))->assertOk();
        $this->assertSame(1, $centerList->viewData('centers')->first()->unlinked_person_affecteds_count);

        $this->actingAs($user)
            ->getJson(route('disaster.payouts.centers.families', $center))
            ->assertOk()
            ->assertJsonPath('data.0.control_number', 'CN-ASSIGN-1001')
            ->assertJsonPath('data.0.tciss_reference', 'CN-ASSIGN-1001')
            ->assertJsonPath('data.0.household_head', 'Assignment Test');

        $this->actingAs($user)
            ->getJson(route('disaster.payouts.centers.tciss-families.details', [$center, $person]))
            ->assertOk()
            ->assertJsonPath('data.tciss.reference', 'CN-ASSIGN-1001')
            ->assertJsonPath('data.affected_family.household_head', 'Assignment Test')
            ->assertJsonPath('data.affected_family.validation_status', 'For Validation');

        $this->actingAs($user)
            ->patchJson(route('disaster.payouts.centers.tciss-families.conditions', [$center, $person]), [
                'housing_condition' => 'Totally Damaged',
                'health_condition' => 'With Illness',
            ])->assertOk();

        $this->assertDatabaseHas('person_affecteds', [
            'id' => $person->id,
            'housing_condition' => 'Totally Damaged',
            'health_condition' => 'With Illness',
        ]);

        $this->actingAs($user)
            ->get(route('disaster.person-affecteds.index'))
            ->assertOk()
            ->assertSee('Assigned to Evacuation Center')
            ->assertSee('disabled', false)
            ->assertDontSee('data-url="'.route('disaster.person-affecteds.show', $person).'"', false);

        $dashboard = $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertSee('Assigned Families')
            ->assertSee('1 active evacuation centers');
        $this->assertSame(1, $dashboard->viewData('metrics')['ASSIGNED_FAMILIES']);
        $this->assertSame(1, $dashboard->viewData('metrics')['FAMILY_AFFECTED']);
        $this->assertSame(1, $dashboard->viewData('metrics')['ACTIVE_EVACUATION_CENTERS']);
    }
}
