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

            // =========================
            // Company
            // =========================
            'company.view',
            'company.create',
            'company.update',
            'company.delete',

            // =========================
            // Company Branch
            // =========================
            'company_branch.view',
            'company_branch.create',
            'company_branch.update',
            'company_branch.delete',

            // =========================
            // Department
            // =========================
            'department.view',
            'department.create',
            'department.update',
            'department.delete',

            // =========================
            // Employee
            // =========================
            'employee.view',
            'employee.create',
            'employee.update',
            'employee.delete',

            // =========================
            // Project
            // =========================
            'project.view',
            'project.create',
            'project.update',
            'project.delete',

            // =========================
            // Employee Project (Pivot)
            // =========================
            'employee_project.view',
            'employee_project.create',
            'employee_project.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission,  'guard_name' => 'api']);
        }

        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        */

        $superadmin = Role::firstOrCreate([
            'name' => 'superadmin',
            'guard_name' => 'api',
        ]);

        $owner = Role::firstOrCreate([
            'name' => 'owner',
            'guard_name' => 'api',
        ]);

        $manager = Role::firstOrCreate([
            'name' => 'manager',
            'guard_name' => 'api',
        ]);

        $employee = Role::firstOrCreate([
            'name' => 'employee',
            'guard_name' => 'api',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Role Permissions
        |--------------------------------------------------------------------------
        */

        // Superadmin → full access
        $superadmin->syncPermissions($permissions);

        // Owner → company-level control (no delete restriction optional)
        $owner->syncPermissions([
            'company.view',
            'company.update',

            'company_branch.view',
            'company_branch.create',
            'company_branch.update',

            'department.view',
            'department.create',
            'department.update',

            'employee.view',
            'employee.create',
            'employee.update',

            'project.view',
            'project.create',
            'project.update',

            'employee_project.view',
            'employee_project.create',
        ]);

        // Manager → operational access
        $manager->syncPermissions([
            'company_branch.view',

            'department.view',

            'employee.view',
            'employee.update',

            'project.view',
            'project.update',

            'employee_project.view',
            'employee_project.create',
        ]);

        // Employee → read-only access
        $employee->syncPermissions([
            'company.view',
            'company_branch.view',
            'department.view',
            'employee.view',
            'project.view',
            'employee_project.view',
        ]);
    }
}
