<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
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
     * Used by the Review step to show full details of whichever vehicle
     * id is currently selected in the form.
     */
    public function show(Vehicle $vehicle)
    {
        return response()->json($this->transform($vehicle));
    }

    private function transform(Vehicle $vehicle): array
    {
        return [
            'id' => (string) $vehicle->id,
            'brand' => $vehicle->brand,
            'model' => $vehicle->model,
            'vehicleType' => $vehicle->vehicle_type,
            'plateNumber' => $vehicle->plate_number,
            'engineNumber' => $vehicle->engine_number,
            'chassisNumber' => $vehicle->chassis_number,
            'color' => $vehicle->color,
            'image' => $vehicle->image_path,
            'status' => $vehicle->status,
            'assignedDriverId' => $vehicle->assigned_driver_id ? (string) $vehicle->assigned_driver_id : null,
        ];
    }
}