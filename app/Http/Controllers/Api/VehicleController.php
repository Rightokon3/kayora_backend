<?php

// app/Http/Controllers/Api/VehicleController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function myVehicle(Request $request)
    {
        $vehicle = $request->user()->vehicleAssignment;

        if (!$vehicle) {
            return response()->json(['message' => 'No vehicle currently assigned.'], 404);
        }

        return response()->json($vehicle);
    }
}