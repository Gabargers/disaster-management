<?php

namespace Tests\Feature\Auth;

use App\Models\Auth\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleBasedSidebarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_coordinator_only_sees_sidebar_modules_allowed_by_its_permissions(): void
    {
        $coordinator = User::where('email', 'coordinator@gmail.com')->firstOrFail();

        $response = $this->actingAs($coordinator)->get(route('dashboard'))->assertOk();

        $response->assertSee('data-sidebar-route="disaster.tciss.index"', false)
            ->assertSee('data-sidebar-route="disaster.reports.index"', false)
            ->assertDontSee('data-sidebar-route="disaster.payroll.index"', false)
            ->assertDontSee('data-sidebar-route="disaster.payouts.index"', false)
            ->assertDontSee('data-sidebar-route="accounts.index"', false);
    }

    public function test_payroll_staff_only_sees_sidebar_modules_allowed_by_its_permissions(): void
    {
        $payroll = User::where('email', 'payroll@gmail.com')->firstOrFail();

        $response = $this->actingAs($payroll)->get(route('dashboard'))->assertOk();

        $response->assertSee('data-sidebar-route="disaster.payroll.index"', false)
            ->assertSee('data-sidebar-route="disaster.payouts.index"', false)
            ->assertSee('data-sidebar-route="disaster.reports.index"', false)
            ->assertDontSee('data-sidebar-route="disaster.tciss.index"', false)
            ->assertDontSee('data-sidebar-route="accounts.index"', false);
    }

    public function test_multiple_roles_combine_their_allowed_sidebar_modules(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['cswdo-coordinator', 'payout-payroll-staff']);

        $response = $this->actingAs($user)->get(route('dashboard'))->assertOk();

        foreach (['disaster.tciss.index', 'disaster.payroll.index', 'disaster.payouts.index', 'disaster.reports.index'] as $route) {
            $response->assertSee('data-sidebar-route="'.$route.'"', false);
        }
    }
}
