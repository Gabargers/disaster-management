<?php

namespace Tests\Feature\Auth;

use App\Models\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['superadmin', 'admin', 'cswdo-coordinator', 'disaster-operation-officer', 'cares-social-worker', 'payout-payroll-staff'] as $role) {
            Role::create(['name' => $role, 'guard_name' => 'web']);
        }
    }

    public function test_admin_and_superadmin_can_open_account_management(): void
    {
        foreach (['admin', 'superadmin'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);

            $this->actingAs($user)->get(route('accounts.index'))
                ->assertOk()
                ->assertSee('Create Account');
        }
    }

    public function test_operational_users_cannot_access_account_management(): void
    {
        $user = User::factory()->create();
        $user->assignRole('cswdo-coordinator');

        $this->actingAs($user)->get(route('accounts.index'))->assertForbidden();
        $this->actingAs($user)->getJson(route('accounts.data'))->assertForbidden();
    }

    public function test_admin_can_create_an_operational_account(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('accounts.store'), [
            'first_name' => 'Juan',
            'middle_name' => 'Santos',
            'last_name' => 'Dela Cruz',
            'email' => 'validator@example.com',
            'contact_number' => '09171234567',
            'roles' => ['disaster-operation-officer', 'payout-payroll-staff'],
            'password' => 'Temporary123!',
            'password_confirmation' => 'Temporary123!',
            'is_active' => '1',
        ])->assertRedirect()->assertSessionHas('success');

        $account = User::where('email', 'validator@example.com')->firstOrFail();
        $this->assertSame('Juan Santos Dela Cruz', $account->name);
        $this->assertTrue($account->hasRole('disaster-operation-officer'));
        $this->assertTrue($account->hasRole('payout-payroll-staff'));
    }

    public function test_admin_cannot_assign_an_administrator_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('accounts.store'), [
            'first_name' => 'Other',
            'last_name' => 'Admin',
            'email' => 'other-admin@example.com',
            'contact_number' => '09171234567',
            'roles' => ['disaster-operation-officer', 'superadmin'],
            'password' => 'Temporary123!',
            'password_confirmation' => 'Temporary123!',
            'is_active' => '1',
        ])->assertSessionHasErrors('roles.1');

        $this->assertDatabaseMissing('users', ['email' => 'other-admin@example.com']);
    }

    public function test_admin_can_update_an_operational_account_with_multiple_roles(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $account = User::factory()->create([
            'email' => 'worker@example.com',
            'password' => 'OriginalPassword!',
        ]);
        $account->assignRole('cswdo-coordinator');

        $this->actingAs($admin)->put(route('accounts.update', $account), [
            'first_name' => 'Updated',
            'middle_name' => 'Middle',
            'last_name' => 'Worker',
            'email' => 'updated.worker@example.com',
            'contact_number' => '09179999999',
            'roles' => ['disaster-operation-officer', 'payout-payroll-staff'],
            'password' => '',
            'password_confirmation' => '',
            'is_active' => '0',
        ])->assertRedirect()->assertSessionHas('success');

        $account->refresh();
        $this->assertSame('Updated Middle Worker', $account->name);
        $this->assertFalse($account->is_active);
        $this->assertTrue(Hash::check('OriginalPassword!', $account->password));
        $this->assertEqualsCanonicalizing(
            ['disaster-operation-officer', 'payout-payroll-staff'],
            $account->getRoleNames()->all()
        );
    }

    public function test_superadmin_can_delete_an_operational_account(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        $account = User::factory()->create();
        $account->assignRole('cares-social-worker');

        $this->actingAs($superadmin)->delete(route('accounts.destroy', $account))
            ->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $account->id]);
    }

    public function test_administrator_accounts_cannot_be_updated_or_deleted_through_managed_actions(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $payload = [
            'first_name' => 'Changed', 'last_name' => 'Admin', 'email' => $admin->email,
            'contact_number' => '09171234567', 'roles' => ['cswdo-coordinator'],
            'password' => '', 'password_confirmation' => '', 'is_active' => '1',
        ];

        $this->actingAs($superadmin)->put(route('accounts.update', $admin), $payload)->assertForbidden();
        $this->actingAs($superadmin)->delete(route('accounts.destroy', $admin))->assertForbidden();
        $this->assertTrue($admin->fresh()->hasRole('admin'));
    }
}
