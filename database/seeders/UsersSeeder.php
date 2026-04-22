<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Super Admin User
        |--------------------------------------------------------------------------
        */
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@system.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password123'),
                'user_type' => 'superadmin',
            ]
        );
        $superAdmin->assignRole('superadmin');

        /*
        |--------------------------------------------------------------------------
        | Owner User
        |--------------------------------------------------------------------------
        */
        $owner = User::firstOrCreate(
            ['email' => 'owner@company.com'],
            [
                'name' => 'Company Owner',
                'password' => Hash::make('password123'),
                'user_type' => 'owner',
            ]
        );
        $owner->assignRole('owner');

        /*
        |--------------------------------------------------------------------------
        | Employee User
        |--------------------------------------------------------------------------
        */
        $employee = User::firstOrCreate(
            ['email' => 'employee@mobile.com'],
            [
                'name' => 'Employee User',
                'password' => Hash::make('password123'),
                'user_type' => 'employee',
            ]
        );
        $employee->assignRole('employee');
    }
}
