<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Matches the DEMO_ACCOUNTS block in login.tsx exactly — once the
        // frontend is wired to the real API, these are the actual accounts
        // that make those displayed demo credentials work for real.
        Admin::updateOrCreate(
            ['employee_id' => 'SUP-0001'],
            [
                'name' => 'Super Administrator',
                'email' => 'superadmin@kayora.com',
                'password' => Hash::make('Admin@123'),
                'role' => 'super_admin',
                'status' => 'active',
            ]
        );

        Admin::updateOrCreate(
            ['employee_id' => 'ADM-0001'],
            [
                'name' => 'Kayora Administrator',
                'email' => 'admin@kayora.com',
                'password' => Hash::make('Admin@123'),
                'role' => 'admin',
                'status' => 'active',
            ]
        );
    }
}