<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class VehicleController extends Controller
{
    /**
     * GET /api/admin/vehicles?search=&status=
     * status is one of All|Available|Assigned|Maintenance — matches the
     * `status` enum on the vehicles table exactly (see the SQL dump), so
     * "Available" IS "unassigned" and "Assigned" IS "assigned" — there's
     * no separate concept to reconcile between the two.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));

        $query = Vehicle::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('brand', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('plate_number', 'like', "%{$search}%")
                    ->orWhere('vehicle_type', 'like', "%{$search}%")
                    ->orWhere('color', 'like', "%{$search}%");
            });
        }

        if ($status !== '' && $status !== 'All') {
            $query->where('status', $status);
        }

        $vehicles = $query->with('driver')->orderByDesc('created_at')->get();

        return response()->json($vehicles->map(fn ($v) => $this->transform($v)));
    }

    /**
     * GET /api/admin/vehicles/assignable?currentDriverId=
     * Vehicles with assigned_driver_id null OR 0 (unassigned), PLUS —
     * when editing an existing driver — whatever vehicle is currently
     * assigned to that driver, so it still shows up as a pickable option
     * (and doesn't just vanish from the list while editing).
     */
    public function assignable(Request $request)
    {
        $currentDriverId = $request->query('currentDriverId');

        $vehicles = Vehicle::where(function ($query) use ($currentDriverId) {
            $query->whereNull('assigned_driver_id')
                ->orWhere('assigned_driver_id', 0);

            if ($currentDriverId) {
                $query->orWhere('assigned_driver_id', $currentDriverId);
            }
        })->orderBy('brand')->get();

        return response()->json($vehicles->map(fn ($v) => $this->transform($v)));
    }

    /**
     * GET /api/admin/vehicles/{vehicle}
     * Used by the driver Review step AND by the Vehicle Details view
     * modal on the manage-vehicles page — includes the full assigned
     * driver record (not just the id) so the view modal doesn't need a
     * second request.
     */
    public function show(Vehicle $vehicle)
    {
        $vehicle->load('driver');

        return response()->json($this->transform($vehicle));
    }

    /**
     * POST /api/admin/vehicles
     * image/registrationImage are expected to already be hosted URLs —
     * upload the picked file via POST /admin/upload-image first (same
     * pattern as everywhere else in the app: AdminManagementController's
     * avatarUrl, DriverController's profileImage/licenseFrontImage/etc.),
     * then send the returned URL here. This endpoint does not accept raw
     * files.
     */
    public function store(Request $request)
    {
        $data = $this->validatePayload($request);

        $vehicle = DB::transaction(function () use ($data) {
            $vehicle = Vehicle::create([
                'brand' => $data['brand'],
                'model' => $data['model'],
                'vehicle_type' => $data['vehicleType'],
                'plate_number' => $data['plateNumber'],
                'engine_number' => $data['engineNumber'] ?? null,
                'chassis_number' => $data['chassisNumber'] ?? null,
                'color' => $data['color'] ?? null,
                'image_path' => $data['image'] ?? null,
                'registration_image_path' => $data['registrationImage'] ?? null,
                'status' => 'Available',
                'assigned_driver_id' => null,
            ]);

            if (! empty($data['assignedDriverId'])) {
                $this->doAssign($vehicle, $data['assignedDriverId']);
            }

            return $vehicle;
        });

        $vehicle->refresh();
        $vehicle->load('driver');

        return response()->json($this->transform($vehicle), 201);
    }

    /**
     * PUT /api/admin/vehicles/{vehicle}
     * Assignment is intentionally NOT handled here — it goes through the
     * dedicated assign()/unassign() endpoints below, same separation
     * DriverController keeps between "edit driver" and vehicle
     * assignment, so a plain details edit never accidentally changes who
     * the vehicle is assigned to.
     */
    public function update(Request $request, Vehicle $vehicle)
    {
        $data = $this->validatePayload($request, $vehicle->id);

        $vehicle->update([
            'brand' => $data['brand'],
            'model' => $data['model'],
            'vehicle_type' => $data['vehicleType'],
            'plate_number' => $data['plateNumber'],
            'engine_number' => $data['engineNumber'] ?? null,
            'chassis_number' => $data['chassisNumber'] ?? null,
            'color' => $data['color'] ?? null,
            'image_path' => $data['image'] ?? $vehicle->image_path,
            'registration_image_path' => $data['registrationImage'] ?? $vehicle->registration_image_path,
        ]);

        $vehicle->refresh();
        $vehicle->load('driver');

        return response()->json($this->transform($vehicle));
    }

    /**
     * DELETE /api/admin/vehicles/{vehicle}
     */
    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();

        return response()->json(['success' => true]);
    }

    /**
     * POST /api/admin/vehicles/{vehicle}/assign
     * Body: { "driverId": "3" }
     * Mirrors DriverController::assignVehicle() exactly — a driver can
     * only ever have one vehicle, so assigning here also frees whatever
     * vehicle that driver had before.
     */
    public function assign(Request $request, Vehicle $vehicle)
    {
        $data = $request->validate([
            'driverId' => ['required', 'exists:drivers,id'],
        ]);

        DB::transaction(function () use ($vehicle, $data) {
            $this->doAssign($vehicle, $data['driverId']);
        });

        $vehicle->refresh();
        $vehicle->load('driver');

        return response()->json($this->transform($vehicle));
    }

    /**
     * POST /api/admin/vehicles/{vehicle}/unassign
     */
    public function unassign(Vehicle $vehicle)
    {
        $vehicle->update([
            'assigned_driver_id' => null,
            'status' => 'Available',
        ]);

        return response()->json($this->transform($vehicle->fresh()));
    }

    /**
     * A driver can only have one vehicle at a time — free whatever this
     * driver already had assigned before handing them this one. Kept as
     * its own method since both store() (assign-on-create) and assign()
     * need exactly this behavior.
     */
    private function doAssign(Vehicle $vehicle, $driverId): void
    {
        Vehicle::where('assigned_driver_id', $driverId)
            ->where('id', '!=', $vehicle->id)
            ->update(['assigned_driver_id' => null, 'status' => 'Available']);

        $vehicle->update([
            'assigned_driver_id' => $driverId,
            'status' => 'Assigned',
        ]);
    }

    /**
     * Shared validation for store() and update(). plateNumber uniqueness
     * ignores the current vehicle's own row on update, same pattern
     * AdminManagementController uses for username/email.
     */
    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'brand' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'vehicleType' => ['required', 'string', 'max:255'],
            'plateNumber' => [
                'required', 'string', 'max:255',
                $ignoreId
                    ? Rule::unique('vehicles', 'plate_number')->ignore($ignoreId)
                    : Rule::unique('vehicles', 'plate_number'),
            ],
            'engineNumber' => ['nullable', 'string', 'max:255'],
            'chassisNumber' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'string'],
            'registrationImage' => ['nullable', 'string'],
            'assignedDriverId' => ['nullable'],
        ]);
    }

    private function transform(Vehicle $vehicle): array
    {
        $driver = $vehicle->relationLoaded('driver') ? $vehicle->driver : $vehicle->driver()->first();

        return [
            'id' => (string) $vehicle->id,
            'brand' => $vehicle->brand,
            'model' => $vehicle->model,
            'vehicleType' => $vehicle->vehicle_type,
            'plateNumber' => $vehicle->plate_number,
            'engineNumber' => $vehicle->engine_number ?? '',
            'chassisNumber' => $vehicle->chassis_number ?? '',
            'color' => $vehicle->color ?? '',
            'image' => $vehicle->image_path,
            'registrationImage' => $vehicle->registration_image_path,
            'status' => $vehicle->status,
            'assignedDriverId' => $vehicle->assigned_driver_id ? (string) $vehicle->assigned_driver_id : null,
            'assignedDriver' => $driver ? [
                'id' => (string) $driver->id,
                'driverId' => $driver->driver_id,
                'name' => $driver->name,
                'phone' => $driver->phone ?? '',
                'email' => $driver->email,
            ] : null,
            'dateAdded' => optional($vehicle->created_at)->format('M j, Y') ?? '',
        ];
    }
}