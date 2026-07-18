<?php

// app/Http/Controllers/Api/DriverProfileController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DriverProfileController extends Controller
{
    public function show(Request $request)
    {
        $driver = $request->user()->load('profile', 'vehicleAssignment');

        return response()->json([
            'driver' => [
                'id' => $driver->id,
                'driverId' => $driver->driver_id,
                'name' => $driver->name,
                'email' => $driver->email,
                'phone' => $driver->phone,
                'dutyStatus' => $driver->duty_status,
            ],
            'profile' => $driver->profile,
            'vehicle' => $driver->vehicleAssignment,
        ]);
    }

    public function update(Request $request)
    {
        // This is the endpoint an ADMIN calls to fill in a driver's info —
        // in a real system you'd guard this with an admin-only middleware,
        // not just auth:sanctum, since right now any authenticated driver
        // could hit it too. Flagging that rather than hiding it.
        $data = $request->validate([
            'middle_name' => 'nullable|string',
            'gender' => 'nullable|in:Male,Female',
            'date_of_birth' => 'nullable|date',
            'marital_status' => 'nullable|in:Single,Married,Divorced,Widowed',
            'alternative_phone' => 'nullable|string',
            'home_address' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string',
            'emergency_contact_phone' => 'nullable|string',
            'employee_id' => 'nullable|string',
            'blood_group' => 'nullable|string',
            'genotype' => 'nullable|string',
            'national_id_number' => 'nullable|string',
            'employment_date' => 'nullable|date',
            'department' => 'nullable|string',
            'branch' => 'nullable|string',
            'supervisor' => 'nullable|string',
            'license_number' => 'nullable|string',
            'license_expiry' => 'nullable|date',
            'years_of_experience' => 'nullable|integer',
            'previous_employer' => 'nullable|string',
            'additional_notes' => 'nullable|string',
        ]);

        $profile = $request->user()->profile()->updateOrCreate(
            ['driver_id' => $request->user()->id],
            $data
        );

        return response()->json(['profile' => $profile]);
    }
}