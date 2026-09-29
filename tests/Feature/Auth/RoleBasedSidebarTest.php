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

    public function test_encoder_sees_affected_families_and_evacuation_centers(): void
    {
        $encoder = User::where('email', 'encoder@gmail.com')->firstOrFail();

        $response = $this->actingAs($encoder)->get(route('dashboard'))->assertOk();

        $response->assertDontSee('data-sidebar-route="disaster.dafac.index"', false)
            ->assertSee('data-sidebar-route="disaster.person-affecteds.index"', false)
            ->assertDontSee('data-sidebar-route="disaster.reports.index"', false)
            ->assertDontSee('data-sidebar-route="disaster.payroll.index"', false)
            ->assertSee('data-sidebar-route="disaster.payouts.index"', false)
            ->assertDontSee('data-sidebar-route="accounts.index"', false);
    }

    public function test_paymaster_cashier_only_sees_payout_modules(): void
    {
        $payroll = User::where('email', 'paymaster@gmail.com')->firstOrFail();

        $response = $this->actingAs($payroll)->get(route('dashboard'))->assertOk();

        $response->assertSee('data-sidebar-route="disaster.payroll.index"', false)
            ->assertSee('data-sidebar-route="disaster.payouts.index"', false)
            ->assertSee('data-sidebar-route="disaster.reports.index"', false)
            ->assertDontSee('data-sidebar-route="disaster.dafac.index"', false)
            ->assertDontSee('data-sidebar-route="disaster.person-affecteds.index"', false)
            ->assertDontSee('data-sidebar-route="accounts.index"', false);
    }

    public function test_dafac_intake_is_not_listed_in_any_role_sidebar(): void
    {
        foreach (['superadmin@gmail.com', 'admin@gmail.com', 'encoder@gmail.com', 'paymaster@gmail.com'] as $email) {
            $user = User::where('email', $email)->firstOrFail();

            $this->actingAs($user)->get(route('dashboard'))
                ->assertOk()
                ->assertDontSee('data-sidebar-route="disaster.dafac.index"', false)
                ->assertDontSee('DAFAC Intake');
        }
    }

    public function test_role_routes_are_enforced_server_side(): void
    {
        $encoder = User::where('email', 'encoder@gmail.com')->firstOrFail();
        $paymaster = User::where('email', 'paymaster@gmail.com')->firstOrFail();

        $this->actingAs($encoder)->get(route('disaster.dafac.index'))->assertForbidden();
        $this->actingAs($encoder)->get(route('disaster.payouts.index'))->assertOk();
        $this->actingAs($encoder)->get(route('disaster.payroll.index'))->assertForbidden();
        $this->assertTrue($encoder->can('manage household conditions'));
        $this->assertFalse($encoder->can('process payouts'));

        $this->actingAs($paymaster)->get(route('disaster.payouts.index'))->assertOk();
        $this->assertFalse($paymaster->can('manage household conditions'));
        $this->assertTrue($paymaster->can('process payouts'));
        $this->actingAs($paymaster)->get(route('disaster.dafac.index'))->assertForbidden();
        $this->actingAs($paymaster)->get(route('accounts.index'))->assertForbidden();
    }
}
