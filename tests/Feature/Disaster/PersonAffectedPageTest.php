<?php

namespace Tests\Feature\Disaster;

use App\Models\Auth\User;
use App\Models\Integration\PersonAffected;
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
        $person = PersonAffected::create(['control_number' => 'TCISS-CN-10001']);
        $person->statuses()->create(['status' => 'affected', 'date_tagged' => '2026-07-22 08:30:00.000000']);

        $this->actingAs($user)
            ->get(route('disaster.person-affecteds.index'))
            ->assertOk()
            ->assertSee('Person Affected')
            ->assertSee('TCISS-CN-10001')
            ->assertSee('Affected');

        $this->actingAs($user)
            ->getJson(route('disaster.person-affecteds.show', $person))
            ->assertOk()
            ->assertJsonPath('data.control_number', 'TCISS-CN-10001');
    }

    public function test_person_affected_page_requires_tciss_permission(): void
    {
        $this->seed(DisasterRoleSeeder::class);
        $user = User::where('email', 'payroll@gmail.com')->firstOrFail();

        $this->actingAs($user)->get(route('disaster.person-affecteds.index'))->assertForbidden();
    }
}
