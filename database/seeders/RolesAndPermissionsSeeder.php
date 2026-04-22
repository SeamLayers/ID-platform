<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles/permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        /*
        |--------------------------------------------------------------------------
        | Permissions
        |--------------------------------------------------------------------------
        */

        $permissions = [
            // Company CRUD
            'company.view',
            'company.create',
            'company.update',
            'company.delete',

            // Dashboards
            'dashboard.superadmin',
            'dashboard.owner',
            'dashboard.employee',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        */

        $superadmin = Role::firstOrCreate(['name' => 'superadmin']);
        $owner      = Role::firstOrCreate(['name' => 'owner']);
        $employee   = Role::firstOrCreate(['name' => 'employee']);

        /*
        |--------------------------------------------------------------------------
        | Assign Permissions to Roles
        |--------------------------------------------------------------------------
        */

        // Superadmin → full access
        $superadmin->syncPermissions($permissions);

        // Owner → only company management (no delete if you want restriction)
        $owner->syncPermissions([
            'company.view',
            'company.update',
            'dashboard.owner',
        ]);

        // Employee → minimal access (mobile)
        $employee->syncPermissions([
            'dashboard.employee',
        ]);
    }
}
