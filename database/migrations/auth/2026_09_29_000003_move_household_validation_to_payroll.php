<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $householdPermission = Permission::firstOrCreate([
            'name' => 'manage household conditions',
            'guard_name' => 'web',
        ]);

        Role::where('guard_name', 'web')
            ->whereIn('name', ['admin', 'superadmin', 'paymaster-cashier'])
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($householdPermission));

        Permission::where('name', 'manage payout availability')
            ->where('guard_name', 'web')
            ->first()
            ?->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $availabilityPermission = Permission::firstOrCreate([
            'name' => 'manage payout availability',
            'guard_name' => 'web',
        ]);

        Role::where('guard_name', 'web')
            ->whereIn('name', ['admin', 'superadmin'])
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($availabilityPermission));

        Role::where('name', 'paymaster-cashier')
            ->where('guard_name', 'web')
            ->first()
            ?->revokePermissionTo('manage household conditions');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
