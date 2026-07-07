<?php

namespace Database\Seeders\Auth;

use App\Models\Auth\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $role->syncPermissions(Permission::pluck('name')->toArray());

        $user = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'System Admin',
                'first_name' => 'System',
                'middle_name' => 'Test',
                'last_name' => 'Admin',
                'contact_number' => '09000000000',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }

        $user->syncPermissions(Permission::pluck('name')->toArray());
    }
}
