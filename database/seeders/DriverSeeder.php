<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Driver;
use App\Models\DriverProfile;
use App\Models\Vehicle;

class DriverSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // ---------- Vehicle assigned to this driver ----------
            $vehicle = Vehicle::updateOrCreate(
                ['plate_number' => 'AKD-245-KY'],
                [
                    'brand' => 'Toyota',
                    'model' => 'Hiace',
                    'vehicle_type' => 'Van',
                    'engine_number' => 'ENG-88213409',
                    'chassis_number' => 'CHS-99213409',
                    'color' => 'Kayora Blue',
                    'status' => 'Assigned',
                ]
            );

            // ---------- Driver login ----------
            $driver = Driver::updateOrCreate(
                ['driver_id' => 'DRV0001'],
                [
                    'name' => 'John Sunday',
                    'email' => 'driver@kayora.com',
                    'password' => Hash::make('driver123'),
                    'phone' => '+2348012345678',
                    'vehicle' => 'Kayora Delivery Van',
                    'plate_number' => 'AKD-245-KY',
                ]
            );

            // Link the vehicle to the driver now that we have the driver's id
            $vehicle->update(['assigned_driver_id' => $driver->id]);

            // ---------- Full profile (Personal / Work / Road info) ----------
            DriverProfile::updateOrCreate(
                ['driver_id' => $driver->id],
                [
                    'middle_name' => 'Chukwuemeka',
                    'gender' => 'Male',
                    'date_of_birth' => '1991-03-14',
                    'marital_status' => 'Married',
                    'alternative_phone' => '+2348098765432',
                    'home_address' => '9 Isekhure Street, Benin City',
                    'city' => 'Benin City',
                    'state' => 'Edo State',
                    'emergency_contact_name' => 'Blessing Sunday',
                    'emergency_contact_phone' => '+2348098765432',

                    'employee_id' => 'KYA-EMP-0452',
                    'blood_group' => 'O+',
                    'genotype' => 'AA',
                    'national_id_number' => 'NIN-2938471029',
                    'employment_date' => '2022-06-02',
                    'department' => 'Fleet Operations',
                    'branch' => 'Benin City Depot',
                    'supervisor' => 'Engr. Patrick Obaseki',

                    'license_number' => 'EDS-DL-88213409',
                    'license_expiry' => '2028-01-10',
                    'years_of_experience' => 6,
                    'previous_employer' => 'Nestle Nigeria (Logistics)',
                    'additional_notes' => 'Certified for long-distance haulage.',
                ]
            );
        });

        // ---------- A few spare vehicles sitting in the "Available" pool ----------
        $availableVehicles = [
            ['brand' => 'Toyota', 'model' => 'Hilux', 'vehicle_type' => 'Pickup', 'plate_number' => 'EDS-101-KY', 'engine_number' => 'ENG-10192837', 'chassis_number' => 'CHS-20293847', 'color' => 'Kayora Blue'],
            ['brand' => 'Foton', 'model' => 'Aumark', 'vehicle_type' => 'Truck', 'plate_number' => 'EDS-202-KY', 'engine_number' => 'ENG-30394857', 'chassis_number' => 'CHS-40495867', 'color' => 'White'],
            ['brand' => 'Toyota', 'model' => 'Hiace', 'vehicle_type' => 'Van', 'plate_number' => 'EDS-404-KY', 'engine_number' => 'ENG-70798988', 'chassis_number' => 'CHS-80899099', 'color' => 'White'],
        ];

        foreach ($availableVehicles as $vehicleData) {
            Vehicle::updateOrCreate(
                ['plate_number' => $vehicleData['plate_number']],
                array_merge($vehicleData, ['status' => 'Available'])
            );
        }
    }
}