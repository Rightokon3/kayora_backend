<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shapes a Driver (with profile/vehicleAssignment/orders loaded) into the
 * JSON the account.tsx screen consumes.
 *
 * IMPORTANT — read before wiring the frontend:
 * Several fields in the account.tsx demo data have NO column anywhere in
 * the current schema (drivers / driver_profiles / vehicles). Rather than
 * invent fake values, this resource either omits them or derives them from
 * real data. See the inline comments below for exactly what's missing and
 * what a migration would need to add if you want them persisted for real:
 *
 *   - Rating (driver.rating)              → no column anywhere. Omitted.
 *   - License class / issuing authority /
 *     issue date                          → driver_profiles only has
 *                                            license_number + license_expiry.
 *   - Nationality                         → no column. Omitted (or hardcode
 *                                            if every driver is Nigerian).
 *   - Vehicle: fuel type, insurance status/
 *     expiry, vehicle license number,
 *     registration date, road worthiness
 *     expiry, assigned depot, mileage     → none of these columns exist on
 *                                            `vehicles`. Omitted.
 *   - Documents / compliance section      → no table at all. Omitted
 *                                            entirely — see note at bottom
 *                                            of the PHP file for the
 *                                            migration you'd need.
 *   - Current shift / working hours       → no column. Omitted.
 */
class DriverAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = $this->profile; // hasOne DriverProfile, may be null
        $vehicle = $this->vehicleAssignment; // hasOne Vehicle (assigned_driver_id), may be null

        $today = Carbon::today();

        // "Today's tasks" bucket — same 8-status-to-3-bucket collapse used
        // elsewhere in the app (dashboard/orders/tasks screens).
        $todaysOrders = $this->orders()->whereDate('created_at', $today)->get();
        $completedToday = $this->orders()
            ->where('status', 'Delivered')
            ->whereDate('completed_at', $today)
            ->count();
        $pendingTasks = $todaysOrders->whereIn('status', ['Pending', 'Accepted', 'Assigned', 'Preparing'])->count();

        $completedDeliveries = $this->orders()->where('status', 'Delivered')->count();

        $currentAssignment = $this->orders()
            ->where('status', 'Out For Delivery')
            ->latest('assigned_at')
            ->first();

        // duty_status is only ever 'on_duty' / 'off_duty' in the schema —
        // there's no separate "Busy" state stored. We derive Busy vs
        // Available from whether the driver currently has an active order,
        // which is the same signal used to pick $currentAssignment above.
        $onlineStatus = 'Off Duty';
        if ($this->duty_status === 'on_duty') {
            $onlineStatus = $currentAssignment ? 'Busy' : 'Available';
        }

        $yearsWithKayora = $profile?->employment_date
            ? Carbon::parse($profile->employment_date)->diffInYears($today)
            : null;

        return [
            'name' => $this->name,
            'employeeId' => $profile?->employee_id,
            'driverId' => $this->driver_id,
            'profilePicture' => $profile?->profile_image,
            'onlineStatus' => $onlineStatus,
            'yearsWithKayora' => $yearsWithKayora,
            'completedDeliveries' => $completedDeliveries,
            'currentAssignment' => $currentAssignment
                ? "{$currentAssignment->order_number} · {$currentAssignment->delivery_address}"
                : null,

            'personal' => [
                'fullName' => trim("{$this->name} " . ($profile?->middle_name ?? '')),
                'gender' => $profile?->gender,
                'dob' => $profile?->date_of_birth ? Carbon::parse($profile->date_of_birth)->format('d F Y') : null,
                'maritalStatus' => $profile?->marital_status,
                // No dedicated "state of origin" column — `state` on
                // driver_profiles is the closest fit (paired with city/
                // home_address as residence, not origin). Relabel or add a
                // real state_of_origin column if that distinction matters.
                'stateOfOrigin' => $profile?->state,
                'residentialAddress' => $profile?->home_address,
                'phone' => $this->phone,
                'email' => $this->email,
                'emergencyContactName' => $profile?->emergency_contact_name,
                'emergencyContactPhone' => $profile?->emergency_contact_phone,
                'bloodGroup' => $profile?->blood_group,
                'genotype' => $profile?->genotype,
                'nationalId' => $profile?->national_id_number,
                'employmentDate' => $profile?->employment_date
                    ? Carbon::parse($profile->employment_date)->format('d F Y')
                    : null,
                'department' => $profile?->department,
                'branch' => $profile?->branch,
                'supervisor' => $profile?->supervisor,
            ],

            'license' => [
                'number' => $profile?->license_number,
                'expiryDate' => $profile?->license_expiry
                    ? Carbon::parse($profile->license_expiry)->format('d M Y')
                    : null,
                'status' => $profile?->license_expiry
                    ? (Carbon::parse($profile->license_expiry)->isPast() ? 'Expired' : 'Valid')
                    : null,
                'frontImage' => $profile?->license_front_image,
                'backImage' => $profile?->license_back_image,
                'nationalIdImage' => $profile?->national_id_image,
            ],

            'vehicle' => $vehicle ? [
                'type' => $vehicle->vehicle_type,
                'brand' => $vehicle->brand,
                'model' => $vehicle->model,
                'color' => $vehicle->color,
                'plateNumber' => $vehicle->plate_number,
                'engineNumber' => $vehicle->engine_number,
                'chassisNumber' => $vehicle->chassis_number,
                'image' => $vehicle->image_path,
                'registrationImage' => $vehicle->registration_image_path,
                'status' => $vehicle->status,
            ] : null,

            'work' => [
                'todaysTasks' => $todaysOrders->count(),
                'completedToday' => $completedToday,
                'pendingTasks' => $pendingTasks,
                'depot' => $profile?->branch,
                'supervisor' => $profile?->supervisor,
            ],
        ];
    }
}

/*
 * ------------------------------------------------------------------
 * If you want the Documents/compliance section back, it needs a real
 * table — something like:
 *
 *   Schema::create('driver_documents', function (Blueprint $table) {
 *       $table->id();
 *       $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
 *       $table->string('type'); // 'insurance' | 'roadworthiness' | ...
 *       $table->string('title');
 *       $table->string('file_path')->nullable();
 *       $table->boolean('verified')->default(false);
 *       $table->timestamps();
 *   });
 *
 * Same story for a `rating` column on drivers (or a separate ratings
 * table if you want per-delivery ratings averaged) and for the missing
 * vehicle columns (fuel_type, insurance_status, insurance_expiry,
 * vehicle_license_number, registration_date, roadworthiness_expiry,
 * assigned_depot, current_mileage) — happy to write those migrations
 * too if you want this data persisted for real rather than omitted.
 * ------------------------------------------------------------------
 */