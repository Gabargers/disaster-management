<?php

namespace Tests\Feature\Auth;

use App\Models\Auth\User;
use App\Models\Disaster\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['superadmin', 'admin', 'cswdo-coordinator'] as $role) {
            Role::create(['name' => $role, 'guard_name' => 'web']);
        }
    }

    public function test_only_superadmin_can_access_activity_logs(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($superadmin)->get(route('activity-logs.index'))
            ->assertOk()->assertSee('Activity Logs');
        $this->actingAs($admin)->get(route('activity-logs.index'))->assertForbidden();
        $this->actingAs($admin)->getJson(route('activity-logs.data'))->assertForbidden();
    }

    public function test_authenticated_account_actions_are_recorded_without_sensitive_values(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('accounts.store'), [
            'first_name' => 'Logged', 'last_name' => 'Worker',
            'email' => 'logged.worker@example.com', 'contact_number' => '09171234567',
            'roles' => ['cswdo-coordinator'], 'password' => 'SecretPassword123!',
            'password_confirmation' => 'SecretPassword123!', 'is_active' => '1',
        ])->assertRedirect();

        $log = AuditLog::where('action', 'accounts.store')->latest('id')->firstOrFail();
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame('POST', $log->new_values['method']);
        $this->assertSame(302, $log->new_values['status_code']);
        $this->assertArrayNotHasKey('password', $log->new_values['input']);
        $this->assertArrayNotHasKey('password_confirmation', $log->new_values['input']);
        $this->assertStringNotContainsString('SecretPassword123!', json_encode($log->new_values));
    }

    public function test_activity_log_datatable_returns_actor_and_role(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        $coordinator = User::factory()->create(['name' => 'Test Coordinator']);
        $coordinator->assignRole('cswdo-coordinator');

        AuditLog::create([
            'user_id' => $coordinator->id,
            'auditable_type' => User::class,
            'auditable_id' => $coordinator->id,
            'action' => 'disaster.tciss.verify',
            'new_values' => ['route' => 'disaster.tciss.verify', 'method' => 'PATCH', 'status_code' => 200],
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($superadmin)->getJson(route('activity-logs.data', [
            'draw' => 1, 'start' => 0, 'length' => 10,
        ]))->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('data.0.account', 'Test Coordinator')
            ->assertJsonPath('data.0.roles', 'Cswdo Coordinator');
    }
}
