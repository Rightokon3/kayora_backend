<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task; // Replace with your actual Task model if named differently

class DriverTaskController extends Controller
{
    public function index(Request $request)
    {
        // Fetch tasks assigned to the authenticated driver
        // For testing/demo purposes, you can return a JSON array structured exactly like your app expects:
        return response()->json([
            'success' => true,
            'data' => [
                [
                    "id" => "TASK-1001",
                    "customerName" => "Amaka Obi",
                    "customerPicture" => null,
                    "phone" => "+2348023456789",
                    "address" => "12 Sapele Road, Benin City",
                    "bottleName" => "30cl Sharp-Sharp",
                    "quantity" => "20 Packs",
                    "status" => "Assigned",
                    "priority" => "High",
                    "distanceKm" => 5.4,
                    "eta" => "12:30 PM",
                    "lat" => 6.339,
                    "lng" => 5.6216
                ],
                [
                    "id" => "TASK-1002",
                    "customerName" => "Emeka Nwosu",
                    "customerPicture" => null,
                    "phone" => "+2348034567890",
                    "address" => "45 Airport Road, Benin City",
                    "bottleName" => "50cl Kayora Table Water",
                    "quantity" => "12 Packs",
                    "status" => "Assigned",
                    "priority" => "Medium",
                    "distanceKm" => 3.1,
                    "eta" => "1:05 PM",
                    "lat" => 6.3423,
                    "lng" => 5.6109
                ]
            ]
        ], 200);
    }
}