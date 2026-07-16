<?php

namespace Database\Seeders\Disaster;

use App\Models\Auth\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DisasterRoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect([
            'view disaster dashboard',
            'manage tciss masterlist',
            'manage dafac intake',
            'resolve duplicate checks',
            'manage validation records',
            'prepare payroll list',
            'manage payout schedules',
            'manage payout availability',
            'manage evacuation center assignments',
            'evacuation_center.view_assignment',
            'evacuation_center.assign_family',
            'evacuation_center.transfer_family',
            'evacuation_center.capacity_override',
            'manage post payout requirements',
            'view disaster reports',
        ])->map(fn (string $name) => Permission::firstOrCreate([
            'name' => $name,
            'guard_name' => 'web',
        ]));

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = [
            'admin' => $permissions->pluck('name')->all(),
            'cswdo-coordinator' => [
                'view disaster dashboard',
                'manage tciss masterlist',
                'manage dafac intake',
                'resolve duplicate checks',
                'manage evacuation center assignments',
                'evacuation_center.view_assignment',
                'evacuation_center.assign_family',
                'evacuation_center.transfer_family',
                'view disaster reports',
            ],
            'disaster-operation-officer' => [
                'view disaster dashboard',
                'resolve duplicate checks',
                'manage validation records',
                'view disaster reports',
            ],
            'cares-social-worker' => [
                'view disaster dashboard',
                'manage dafac intake',
                'manage validation records',
                'manage post payout requirements',
            ],
            'payout-payroll-staff' => [
                'view disaster dashboard',
                'prepare payroll list',
                'manage payout schedules',
                'manage post payout requirements',
                'view disaster reports',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ])->syncPermissions($rolePermissions);
        }

        // Superadmin is created by the auth seeder, but this disaster seeder
        // may run later when new permissions are introduced. Keep it current
        // without removing any permissions already granted elsewhere.
        if ($superadmin = Role::where(['name' => 'superadmin', 'guard_name' => 'web'])->first()) {
            $superadmin->givePermissionTo(Permission::where('guard_name', 'web')->get());
        }

        $this->createUser('coordinator@gmail.com', 'CSWDO Coordinator', 'cswdo-coordinator');
        $this->createUser('operation@gmail.com', 'Disaster Operation Officer', 'disaster-operation-officer');
        $this->createUser('socialworker@gmail.com', 'CARES Social Worker', 'cares-social-worker');
        $this->createUser('payroll@gmail.com', 'Payout Payroll Staff', 'payout-payroll-staff');
    }

    private function createUser(string $email, string $name, string $role): void
    {
        $parts = explode(' ', $name);

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'uuid' => (string) Str::uuid(),
                'name' => $name,
                'first_name' => $parts[0] ?? $name,
                'middle_name' => null,
                'last_name' => $parts[array_key_last($parts)] ?? $name,
                'contact_number' => '09000000000',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }
    }
}
