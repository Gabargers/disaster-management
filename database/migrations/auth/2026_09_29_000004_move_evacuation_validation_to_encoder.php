<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $viewCenters = Permission::firstOrCreate([
            'name' => 'view evacuation centers',
            'guard_name' => 'web',
        ]);
        $manageConditions = Permission::firstOrCreate([
            'name' => 'manage household conditions',
            'guard_name' => 'web',
        ]);

        Role::where('name', 'encoder')
            ->where('guard_name', 'web')
            ->first()
            ?->givePermissionTo([$viewCenters, $manageConditions]);

        Role::where('name', 'paymaster-cashier')
            ->where('guard_name', 'web')
            ->first()
            ?->revokePermissionTo($manageConditions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $encoder = Role::where('name', 'encoder')
            ->where('guard_name', 'web')
            ->first();

        $encoder?->revokePermissionTo('view evacuation centers');
        $encoder?->revokePermissionTo('manage household conditions');

        Role::where('name', 'paymaster-cashier')
            ->where('guard_name', 'web')
            ->first()
            ?->givePermissionTo('manage household conditions');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
