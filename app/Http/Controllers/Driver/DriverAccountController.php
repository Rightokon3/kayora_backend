<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Http\Resources\DriverAccountResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DriverAccountController extends Controller
{
    /**
     * GET /api/driver/me
     * Returns the authenticated driver's full account payload
     * (drivers + driver_profiles + assigned vehicle + computed stats).
     */
    public function show(Request $request)
    {
        $driver = $request->user()->load(['profile', 'vehicleAssignment']);

        return new DriverAccountResource($driver);
    }

    /**
     * PATCH /api/driver/me/profile
     * Updates the editable driver_profiles fields — the "Edit Profile"
     * button in account.tsx should hit this. Only accepts fields that
     * actually exist as columns; anything not in $fillable on
     * DriverProfile is silently ignored by updateOrCreate's mass
     * assignment, so this validation list is the real contract.
     */
    public function updateProfile(Request $request)
    {
        $driver = $request->user();

        $validated = $request->validate([
            'middle_name' => 'nullable|string|max:255',
            'gender' => ['nullable', Rule::in(['Male', 'Female'])],
            'date_of_birth' => 'nullable|date',
            'marital_status' => ['nullable', Rule::in(['Single', 'Married', 'Divorced', 'Widowed'])],
            'alternative_phone' => 'nullable|string|max:255',
            'home_address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:255',
            'blood_group' => 'nullable|string|max:255',
            'genotype' => 'nullable|string|max:255',
        ]);

        $profile = $driver->profile()->updateOrCreate(
            ['driver_id' => $driver->id],
            $validated
        );

        return new DriverAccountResource($driver->fresh()->load(['profile', 'vehicleAssignment']));
    }

    /**
     * PATCH /api/driver/me/duty-status
     * Toggles on_duty / off_duty — separate endpoint since this is a
     * much higher-frequency write than profile edits and shouldn't
     * require re-sending the whole profile payload.
     */
    public function updateDutyStatus(Request $request)
    {
        $validated = $request->validate([
            'duty_status' => ['required', Rule::in(['on_duty', 'off_duty'])],
        ]);

        $driver = $request->user();
        $driver->update(['duty_status' => $validated['duty_status']]);

        return new DriverAccountResource($driver->fresh()->load(['profile', 'vehicleAssignment']));
    }
}