<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DriverController extends Controller
{
    /**
     * GET /api/admin/drivers?search=
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $query = Driver::with(['profile', 'vehicleAssignment']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('driver_id', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('vehicleAssignment', function ($vq) use ($search) {
                        $vq->where('plate_number', 'like', "%{$search}%");
                    });
            });
        }

        $drivers = $query->orderByDesc('created_at')->get();

        return response()->json($drivers->map(fn ($driver) => $this->transform($driver)));
    }

    public function show(Request $request, Driver $driver)
    {
        $driver->load(['profile', 'vehicleAssignment']);
        return response()->json($this->transform($driver));
    }

    /**
     * POST /api/admin/drivers
     * Every field is optional — only whatever's actually sent gets saved.
     * A random unusable password is set at creation; the real password is
     * set right after via PATCH /drivers/{id}/password (the "set password
     * after review" step).
     */
    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);

        $driver = DB::transaction(function () use ($validated) {
            $first = $validated['personal']['firstName'] ?? '';
            $last = $validated['personal']['lastName'] ?? '';
            $name = trim("{$first} {$last}");

            $driver = Driver::create([
                'driver_id' => $this->generateDriverId(),
                'name' => $name !== '' ? $name : 'New Driver',
                'email' => $validated['personal']['email'] ?? (Str::uuid() . '@placeholder.kayora.internal'),
                'phone' => $validated['personal']['phone'] ?? null,
                'password' => Hash::make(Str::random(32)),
            ]);

            $this->saveProfile($driver, $validated);
            $this->assignVehicle($driver, $validated['vehicle']['vehicleId'] ?? null);

            return $driver;
        });

        $driver->load(['profile', 'vehicleAssignment']);
        return response()->json($this->transform($driver), 201);
    }

    /**
     * PUT /api/admin/drivers/{driver}
     */
    public function update(Request $request, Driver $driver)
    {
        $validated = $this->validatePayload($request);

        DB::transaction(function () use ($driver, $validated) {
            $p = $validated['personal'] ?? [];
            $driverUpdate = [];

            if (($p['firstName'] ?? null) !== null || ($p['lastName'] ?? null) !== null) {
                $currentParts = preg_split('/\s+/', trim($driver->name), 2);
                $first = $p['firstName'] ?? ($currentParts[0] ?? '');
                $last = $p['lastName'] ?? ($currentParts[1] ?? '');
                $driverUpdate['name'] = trim("{$first} {$last}");
            }
            if (!empty($p['email'])) {
                $driverUpdate['email'] = $p['email'];
            }
            if (array_key_exists('phone', $p)) {
                $driverUpdate['phone'] = $p['phone'];
            }
            if ($driverUpdate) {
                $driver->update($driverUpdate);
            }

            $this->saveProfile($driver, $validated);
            $this->assignVehicle($driver, $validated['vehicle']['vehicleId'] ?? null);
        });

        $driver->load(['profile', 'vehicleAssignment']);
        return response()->json($this->transform($driver));
    }

    /**
     * DELETE /api/admin/drivers/{driver}
     */
    public function destroy(Driver $driver)
    {
        DB::transaction(function () use ($driver) {
            Vehicle::where('assigned_driver_id', $driver->id)
                ->update(['assigned_driver_id' => null, 'status' => 'Available']);
            $driver->profile()->delete();
            $driver->delete();
        });

        return response()->json(['success' => true]);
    }

    /**
     * PATCH /api/admin/drivers/{driver}/password
     * The "set password" form shown right after a driver is successfully
     * created via the review step.
     */
    public function setPassword(Request $request, Driver $driver)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $driver->update(['password' => Hash::make($validated['password'])]);

        return response()->json(['success' => true]);
    }

    /**
     * GET /api/admin/drivers/{driver}/track
     * Real current_latitude/current_longitude from the drivers table —
     * updated by the driver app's own POST /driver/location endpoint.
     * onDuty tells the frontend whether to keep polling.
     */
    public function track(Driver $driver)
    {
        return response()->json([
            'latitude' => $driver->current_latitude !== null ? (float) $driver->current_latitude : null,
            'longitude' => $driver->current_longitude !== null ? (float) $driver->current_longitude : null,
            'speed' => 0, // no speed column exists anywhere yet — see note below
            'updatedAt' => optional($driver->last_seen_at)->toIso8601String(),
            'onDuty' => $driver->duty_status === 'on_duty',
        ]);
    }

    private function transform(Driver $driver): array
    {
        $nameParts = preg_split('/\s+/', trim($driver->name), 2);
        $profile = $driver->profile;
        $vehicle = $driver->vehicleAssignment;

        $hasActiveOrder = Order::where('driver_id', $driver->id)
            ->whereIn('status', ['Accepted', 'Assigned', 'Preparing', 'Out For Delivery'])
            ->exists();

        // duty_status only ever stores on_duty/off_duty — "delivering" vs
        // "active" is derived from whether the driver currently has a live
        // order, same signal used elsewhere in this app. "break" has no
        // backing data anywhere and is never emitted.
        $status = 'offline';
        if ($driver->duty_status === 'on_duty') {
            $status = $hasActiveOrder ? 'delivering' : 'active';
        }

        return [
            'id' => (string) $driver->id,
            'driverId' => $driver->driver_id,
            'firstName' => $nameParts[0] ?? '',
            'lastName' => $nameParts[1] ?? '',
            'email' => $driver->email,
            'phone' => $driver->phone ?? '',
            'profileImage' => $profile?->profile_image,
            'status' => $status,
            'vehicle' => [
                'vehicleId' => $vehicle ? (string) $vehicle->id : '',
                'brand' => $vehicle->brand ?? '—',
                'model' => $vehicle->model ?? '—',
                'plateNumber' => $vehicle->plate_number ?? '—',
            ],
            'location' => ($driver->current_latitude !== null && $driver->current_longitude !== null) ? [
                'latitude' => (float) $driver->current_latitude,
                'longitude' => (float) $driver->current_longitude,
                'speed' => 0,
                'updatedAt' => optional($driver->last_seen_at)->toIso8601String() ?? '',
            ] : null,
            // Used only to pre-fill the edit form — see driverToForm() in
            // DriverFormModal.tsx.
            'profileDetails' => [
                'middleName' => $profile?->middle_name ?? '',
                'gender' => $profile?->gender ?? '',
                'dateOfBirth' => $profile?->date_of_birth?->toDateString() ?? '',
                'maritalStatus' => $profile?->marital_status ?? '',
                'alternativePhone' => $profile?->alternative_phone ?? '',
                'homeAddress' => $profile?->home_address ?? '',
                'city' => $profile?->city ?? '',
                'state' => $profile?->state ?? '',
                'emergencyContactName' => $profile?->emergency_contact_name ?? '',
                'emergencyContactPhone' => $profile?->emergency_contact_phone ?? '',
            ],
            'roadDetails' => [
                'licenseNumber' => $profile?->license_number ?? '',
                'licenseExpiry' => $profile?->license_expiry?->toDateString() ?? '',
                'licenseFrontImage' => $profile?->license_front_image,
                'licenseBackImage' => $profile?->license_back_image,
                'nationalIdNumber' => $profile?->national_id_number ?? '',
                'nationalIdImage' => $profile?->national_id_image,
                'yearsOfExperience' => $profile?->years_of_experience !== null ? (string) $profile->years_of_experience : '',
                'previousEmployer' => $profile?->previous_employer ?? '',
                'additionalNotes' => $profile?->additional_notes ?? '',
            ],
        ];
    }

    /**
     * Every field nullable on purpose — "all fields in the edit section
     * must not be required" applies here too, not just the frontend.
     * date/integer type rules are deliberately loose (plain 'nullable',
     * not 'nullable|date' etc.) since the frontend always sends every key
     * (possibly as an empty string to intentionally clear a field), and
     * Laravel's date/integer rules reject empty strings outright.
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'personal' => 'nullable|array',
            'personal.firstName' => 'nullable|string|max:255',
            'personal.lastName' => 'nullable|string|max:255',
            'personal.middleName' => 'nullable|string|max:255',
            'personal.gender' => 'nullable|in:Male,Female,',
            'personal.dateOfBirth' => 'nullable',
            'personal.maritalStatus' => 'nullable|in:Single,Married,Divorced,Widowed,',
            'personal.email' => 'nullable|string|max:255',
            'personal.phone' => 'nullable|string|max:255',
            'personal.alternativePhone' => 'nullable|string|max:255',
            'personal.homeAddress' => 'nullable|string|max:255',
            'personal.city' => 'nullable|string|max:255',
            'personal.state' => 'nullable|string|max:255',
            'personal.emergencyContactName' => 'nullable|string|max:255',
            'personal.emergencyContactPhone' => 'nullable|string|max:255',
            'personal.profileImage' => 'nullable|string',
            'vehicle' => 'nullable|array',
            'vehicle.vehicleId' => 'nullable',
            'road' => 'nullable|array',
            'road.licenseNumber' => 'nullable|string|max:255',
            'road.licenseExpiry' => 'nullable',
            'road.licenseFrontImage' => 'nullable|string',
            'road.licenseBackImage' => 'nullable|string',
            'road.nationalIdNumber' => 'nullable|string|max:255',
            'road.nationalIdImage' => 'nullable|string',
            'road.yearsOfExperience' => 'nullable',
            'road.previousEmployer' => 'nullable|string|max:255',
            'road.additionalNotes' => 'nullable|string',
        ]);
    }

    private function saveProfile(Driver $driver, array $validated): void
    {
        $p = $validated['personal'] ?? [];
        $r = $validated['road'] ?? [];

        $blankToNull = fn ($v) => ($v === '' || $v === null) ? null : $v;

        $data = [
            'middle_name' => $blankToNull($p['middleName'] ?? null),
            'gender' => $blankToNull($p['gender'] ?? null),
            'date_of_birth' => $blankToNull($p['dateOfBirth'] ?? null),
            'marital_status' => $blankToNull($p['maritalStatus'] ?? null),
            'alternative_phone' => $blankToNull($p['alternativePhone'] ?? null),
            'home_address' => $blankToNull($p['homeAddress'] ?? null),
            'city' => $blankToNull($p['city'] ?? null),
            'state' => $blankToNull($p['state'] ?? null),
            'emergency_contact_name' => $blankToNull($p['emergencyContactName'] ?? null),
            'emergency_contact_phone' => $blankToNull($p['emergencyContactPhone'] ?? null),
            'profile_image' => $blankToNull($p['profileImage'] ?? null),
            'license_number' => $blankToNull($r['licenseNumber'] ?? null),
            'license_expiry' => $blankToNull($r['licenseExpiry'] ?? null),
            'license_front_image' => $blankToNull($r['licenseFrontImage'] ?? null),
            'license_back_image' => $blankToNull($r['licenseBackImage'] ?? null),
            'national_id_number' => $blankToNull($r['nationalIdNumber'] ?? null),
            'national_id_image' => $blankToNull($r['nationalIdImage'] ?? null),
            'years_of_experience' => isset($r['yearsOfExperience']) && $r['yearsOfExperience'] !== ''
                ? (int) $r['yearsOfExperience'] : null,
            'previous_employer' => $blankToNull($r['previousEmployer'] ?? null),
            'additional_notes' => $blankToNull($r['additionalNotes'] ?? null),
        ];

        // Only touch driver_profiles at all if at least one of these keys
        // was actually present in the request — avoids creating an empty
        // row for a driver whose personal/road step was skipped entirely.
        if (!isset($validated['personal']) && !isset($validated['road'])) {
            return;
        }

        $driver->profile()->updateOrCreate(['driver_id' => $driver->id], $data);
    }

    private function assignVehicle(Driver $driver, $vehicleId): void
    {
        if (!$vehicleId) {
            return;
        }

        DB::transaction(function () use ($driver, $vehicleId) {
            // Free whatever this driver had assigned before, if different.
            Vehicle::where('assigned_driver_id', $driver->id)
                ->where('id', '!=', $vehicleId)
                ->update(['assigned_driver_id' => null, 'status' => 'Available']);

            Vehicle::where('id', $vehicleId)->update([
                'assigned_driver_id' => $driver->id,
                'status' => 'Assigned',
            ]);
        });
    }

    private function generateDriverId(): string
    {
        $lastId = Driver::orderByDesc('id')->value('id') ?? 0;
        return 'DRV' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
    }
}