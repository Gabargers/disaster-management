<?php

use App\Models\Auth\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::where('name', 'manage payout availability')
            ->where('guard_name', 'web')
            ->first();

        if (! $permission) {
            return;
        }

        Role::where('guard_name', 'web')
            ->whereNotIn('name', ['admin', 'superadmin'])
            ->get()
            ->each(fn (Role $role) => $role->revokePermissionTo($permission));

        User::permission($permission)
            ->whereDoesntHave('roles', fn ($query) => $query->whereIn('name', ['admin', 'superadmin']))
            ->get()
            ->each(fn (User $user) => $user->revokePermissionTo($permission));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permission = Permission::where('name', 'manage payout availability')
            ->where('guard_name', 'web')
            ->first();

        if ($permission) {
            Role::where('name', 'paymaster-cashier')
                ->where('guard_name', 'web')
                ->first()
                ?->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
