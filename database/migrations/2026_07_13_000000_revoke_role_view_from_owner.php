<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles & Permissions manages the GLOBAL platform roles (superadmin, owner,
 * manager, employee). Owners were previously seeded with `role.view`, which
 * surfaced the "Roles & Permissions" screen (and the superadmin role card) in
 * the owner dashboard. That is superadmin-only, so this migration removes the
 * permission from the owner role WITHOUT touching any other role customisation
 * (unlike re-running the seeder, which resyncs every role).
 */
return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $owner = Role::where('name', 'owner')->where('guard_name', 'api')->first();
        $permission = Permission::where('name', 'role.view')->where('guard_name', 'api')->first();

        if ($owner && $permission && $owner->hasPermissionTo($permission)) {
            $owner->revokePermissionTo($permission);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $owner = Role::where('name', 'owner')->where('guard_name', 'api')->first();
        $permission = Permission::where('name', 'role.view')->where('guard_name', 'api')->first();

        if ($owner && $permission && ! $owner->hasPermissionTo($permission)) {
            $owner->givePermissionTo($permission);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
