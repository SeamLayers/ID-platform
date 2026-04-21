<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
    

        \App\Models\User::create([
            'name' => 'SultanAdmin',
            'email' => 'sultan@example.com',
            'user_type'=>'admin',
            'sex'=>'male',
           'password' => Hash::make('88888888'),
        ]);
    }
}
