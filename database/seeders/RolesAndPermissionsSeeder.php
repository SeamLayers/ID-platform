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

            // =========================
            // Business Card
            // =========================
            'business_card.view',
            'business_card.create',
            'business_card.update',
            'business_card.delete',
            'business_card.submit',
            'business_card.approve',
            'business_card.reject',
            'business_card.publish',
            'business_card.deactivate',

            // =========================
            // Business Card Template
            // =========================
            'business_card_template.view',
            'business_card_template.create',
            'business_card_template.update',
            'business_card_template.delete',

            // =========================
            // Role management
            // =========================
            'role.view',
            'role.create',
            'role.update',
            'role.delete',
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
            'company_branch.delete',

            'department.view',
            'department.create',
            'department.update',
            'department.delete',

            'employee.view',
            'employee.create',
            'employee.update',
            'employee.delete',

            'project.view',
            'project.create',
            'project.update',
            'project.delete',

            'employee_project.view',
            'employee_project.create',
            'employee_project.delete',

            // Owners can issue, edit, submit, publish and retire cards for
            // their company's employees. Approval/rejection are explicitly
            // delegated to the mobile reviewer role (employee with the
            // approve/reject permissions) so the owner doesn't self-approve.
            'business_card.view',
            'business_card.create',
            'business_card.update',
            'business_card.submit',
            'business_card.publish',
            'business_card.deactivate',

            'business_card_template.view',
            'business_card_template.create',
            'business_card_template.update',
            'business_card_template.delete',

            // NOTE: owners intentionally get NO role.* permission. Roles are the
            // GLOBAL platform roles; managing them belongs to superadmin only.
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

            'business_card.view',
            'business_card.update',
            'business_card.submit',
            'business_card_template.view',
        ]);

        // Employee → read-only access + mobile card review.
        // The approve/reject endpoints live under /mobile/* and are the only
        // mutating operations an employee can perform.
        $employee->syncPermissions([
            'company.view',
            'company_branch.view',
            'department.view',
            'employee.view',
            'project.view',
            'employee_project.view',

            'business_card.view',
            'business_card.approve',
            'business_card.reject',
        ]);
    }
}
