<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::updateOrCreate(
            ['employee_id' => 'SUP-0001'],
            [
                'first_name' => 'Right',
                'last_name' => 'Okon',
                'username' => 'SUP-0001',
                'name' => 'Super Administrator',
                'email' => 'superadmin@kayora.com',
                'profile_picture' => '',
                'phone' => null,
                'avatar_url' => null,
                'department' => null,
                'password' => Hash::make('Admin@123'),
                'role' => 'super_admin',
                'status' => 'active',
                'permissions' => null,
                'notify_system' => true,
                'notify_new_orders' => true,
                'notify_driver_alerts' => true,
                'notify_customer_reports' => true,
            ]
        );

        Admin::updateOrCreate(
            ['employee_id' => 'ADM-0001'],
            [
                'first_name' => 'Right',
                'last_name' => 'Okon',
                'username' => 'right',
                'name' => 'Kayora Administrator',
                'email' => 'admin@kayora.com',
                'profile_picture' => '',
                'phone' => null,
                'avatar_url' => null,
                'department' => null,
                'password' => Hash::make('Admin@123'),
                'role' => 'admin',
                'status' => 'active',
                'permissions' => [],
                'notify_system' => true,
                'notify_new_orders' => true,
                'notify_driver_alerts' => true,
                'notify_customer_reports' => false,
            ]
        );
    }
}