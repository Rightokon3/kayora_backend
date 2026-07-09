<?php

// app/Http/Controllers/Api/DriverLocationController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DriverDailyStat;
use App\Services\HaversineService;
use Illuminate\Http\Request;

class DriverLocationController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $driver = $request->user();
        $today = now()->toDateString();

        $stat = DriverDailyStat::firstOrCreate(
            ['driver_id' => $driver->id, 'date' => $today],
            ['distance_km' => 0]
        );

        // Only add distance if we have a previous point to measure FROM —
        // the very first ping of the day just establishes the starting point.
        if ($stat->last_latitude && $stat->last_longitude) {
            $incrementKm = HaversineService::distanceKm(
                (float) $stat->last_latitude,
                (float) $stat->last_longitude,
                (float) $request->latitude,
                (float) $request->longitude
            );

            // Ignore GPS noise/jitter under 5 meters so standing still
            // doesn't slowly rack up fake distance.
            if ($incrementKm > 0.005) {
                $stat->distance_km += $incrementKm;
            }
        }

        $stat->last_latitude = $request->latitude;
        $stat->last_longitude = $request->longitude;
        $stat->save();

        $driver->update([
            'current_latitude' => $request->latitude,
            'current_longitude' => $request->longitude,
            'last_seen_at' => now(),
        ]);

        return response()->json([
            'distanceKm' => round($stat->distance_km, 2),
        ]);
    }
}