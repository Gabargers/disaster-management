<?php

namespace Database\Seeders\Auth;

use App\Models\Auth\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::firstOrCreate([
            'name' => 'superadmin',
            'guard_name' => 'web',
        ]);

        $user = User::firstOrCreate(
            ['email' => 'superadmin@gmail.com'],
            [
                'uuid' => (string) Str::uuid(),
                'id_number' => 'SA-0001',
                'name' => 'Super Admin',
                'first_name' => 'Super',
                'middle_name' => 'System',
                'last_name' => 'Admin',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }

        $allPermissions = Permission::pluck('name')->toArray();
        $role->syncPermissions($allPermissions);
        $user->syncPermissions($allPermissions);
    }
}
