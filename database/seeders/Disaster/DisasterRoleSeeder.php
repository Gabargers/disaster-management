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
            'view affected families',
            'manage tciss masterlist',
            'manage dafac intake',
            'resolve duplicate checks',
            'manage validation records',
            'prepare payroll list',
            'manage payout schedules',
            'view evacuation centers',
            'manage evacuation centers',
            'process payouts',
            'manage household conditions',
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
            'encoder' => [
                'view disaster dashboard',
                'view affected families',
                'view evacuation centers',
                'manage household conditions',
                'evacuation_center.view_assignment',
                'evacuation_center.assign_family',
            ],
            'paymaster-cashier' => [
                'view disaster dashboard',
                'prepare payroll list',
                'view evacuation centers',
                'process payouts',
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

        $this->migrateLegacyOperationalRoles();

        // Superadmin is created by the auth seeder, but this disaster seeder
        // may run later when new permissions are introduced. Keep it current
        // without removing any permissions already granted elsewhere.
        if ($superadmin = Role::where(['name' => 'superadmin', 'guard_name' => 'web'])->first()) {
            $superadmin->givePermissionTo(Permission::where('guard_name', 'web')->get());
        }

        if (app()->environment('testing')) {
            $this->createUser('encoder@gmail.com', 'System Encoder', 'encoder');
            $this->createUser('paymaster@gmail.com', 'Paymaster Cashier', 'paymaster-cashier');
        }
    }

    private function migrateLegacyOperationalRoles(): void
    {
        $legacyNames = [
            'cswdo-coordinator',
            'disaster-operation-officer',
            'cares-social-worker',
            'payout-payroll-staff',
        ];

        User::whereHas('roles', fn ($query) => $query->whereIn('name', $legacyNames))
            ->with('roles')->get()->each(function (User $user) use ($legacyNames): void {
                if ($user->hasAnyRole(['admin', 'superadmin'])) {
                    foreach ($legacyNames as $legacyName) {
                        if ($user->hasRole($legacyName)) {
                            $user->removeRole($legacyName);
                        }
                    }

                    return;
                }

                $replacement = $user->hasRole('payout-payroll-staff') ? 'paymaster-cashier' : 'encoder';
                $user->syncRoles([$replacement]);
            });

        Role::where('guard_name', 'web')->whereIn('name', $legacyNames)->get()->each->delete();
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
