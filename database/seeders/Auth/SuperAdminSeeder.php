<?php

namespace Database\Seeders\Auth;

use Illuminate\Database\Seeder;
use App\Models\Auth\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission; 

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'superadmin@gmail.com'],
            [
                'uuid'           => (string) Str::uuid(),
                'first_name'     => 'Super',
                'middle_name'    => 'System',
                'last_name'      => 'Admin',
                'contact_number' => '09271852712',
                'password'       => Hash::make('password'),
                'is_active'      => true,
            ]
        );
        
        if (! $user->hasRole('superadmin')) {
            $user->assignRole('superadmin');
        }

        $allPermissions = Permission::pluck('name')->toArray();
        $user->syncPermissions($allPermissions);
    }
}
