<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'evacuation_center.assign_family',
            'guard_name' => 'web',
        ]);

        Role::where('name', 'encoder')
            ->where('guard_name', 'web')
            ->first()
            ?->givePermissionTo($permission);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permission = Permission::where('name', 'evacuation_center.assign_family')
            ->where('guard_name', 'web')
            ->first();

        if ($permission) {
            Role::where('name', 'encoder')
                ->where('guard_name', 'web')
                ->first()
                ?->revokePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
